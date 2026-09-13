<?php
/**
 * Payment Processor
 * Ödeme sonrası hesap atama ve stok yönetimi
 */

// Output buffering başlat (eğer başlatılmamışsa)
if (!ob_get_level()) {
    ob_start();
}

require_once 'StockManager.php';
require_once 'TelegramNotifier.php';

class PaymentProcessor {
    private $pdo;
    private $stockManager;
    private $telegramNotifier;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->stockManager = new StockManager();
        $this->telegramNotifier = new TelegramNotifier();
    }
    
    /**
     * Ödeme başarılı olduğunda hesapları kullanıcıya ata
     */
    public function processSuccessfulPayment($orderId, $paymentMethod = 'cryptomus') {
        try {
            $this->pdo->beginTransaction();
            
            // Sipariş bilgilerini al
            $stmt = $this->pdo->prepare("
                SELECT o.*, u.username, u.email 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.order_id = ?
            ");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            
            if (!$order) {
                throw new Exception("Sipariş bulunamadı: $orderId");
            }
            
            // Ödeme durumunu güncelle
            $stmt = $this->pdo->prepare("
                UPDATE orders 
                SET status = 'completed', delivery_status = 'delivered', updated_at = NOW() 
                WHERE order_id = ?
            ");
            $stmt->execute([$orderId]);
            
            // Ürün bilgilerini al (orders tablosundan)
            $stmt = $this->pdo->prepare("
                SELECT a.*, o.quantity, o.unit_price 
                FROM orders o 
                JOIN accounts a ON o.product_name = a.title 
                WHERE o.order_id = ?
            ");
            $stmt->execute([$orderId]);
            $orderItems = $stmt->fetchAll();
            
            $assignedAccounts = [];
            $stockShortages = [];
            
            // Her ürün için hesapları ata
            foreach ($orderItems as $item) {
                $result = $this->assignAccountsToUser(
                    $orderId,
                    $order['user_id'],
                    $item['id'], // account_id
                    $item['quantity'],
                    $item['title'],
                    $order['username'],
                    $order['email']
                );
                
                if ($result['success']) {
                    $assignedAccounts[] = $result;
                } else {
                    $stockShortages[] = [
                        'product_name' => $item['title'],
                        'requested_quantity' => $item['quantity'],
                        'available_stock' => $result['available_stock'] ?? 0,
                        'error' => $result['message']
                    ];
                }
            }
            
            // Stok yetersizliği varsa Telegram bildirimi gönder
            if (!empty($stockShortages)) {
                foreach ($stockShortages as $shortage) {
                    $this->telegramNotifier->sendStockShortageNotification(
                        $orderId,
                        $shortage['product_name'],
                        $shortage['requested_quantity'],
                        $shortage['available_stock'],
                        ['username' => $order['username'], 'email' => $order['email']]
                    );
                }
            }
            
            // Başarılı atamalar varsa Telegram bildirimi gönder
            if (!empty($assignedAccounts)) {
                $totalAssigned = array_sum(array_column($assignedAccounts, 'assigned_count'));
                $this->telegramNotifier->sendPaymentSuccessNotification(
                    $orderId,
                    $order['total_price'],
                    ['username' => $order['username'], 'email' => $order['email']],
                    $order['product_name'],
                    $totalAssigned
                );
            }
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'assigned_accounts' => $assignedAccounts,
                'stock_shortages' => $stockShortages,
                'message' => 'Ödeme işlemi tamamlandı'
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            error_log("Payment processing error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Kullanıcıya hesap ata
     */
    private function assignAccountsToUser($orderId, $userId, $accountId, $quantity, $productName, $username, $email) {
        try {
            // Mevcut stok kontrolü
            $stockCounts = $this->stockManager->getStockCounts($accountId);
            $availableStock = $stockCounts['available'] ?? 0;
            
            if ($availableStock < $quantity) {
                return [
                    'success' => false,
                    'available_stock' => $availableStock,
                    'message' => "Yetersiz stok! Mevcut: $availableStock, İstenen: $quantity"
                ];
            }
            
            // Hesapları rezerve et
            $reserveResult = $this->stockManager->reserveAccounts($accountId, $quantity, $userId, $orderId);
            
            if (!$reserveResult['success']) {
                return [
                    'success' => false,
                    'message' => $reserveResult['message']
                ];
            }
            
            $accounts = $reserveResult['accounts'];
            $assignedCount = count($accounts);
            
            // order_accounts tablosuna ekle
            foreach ($accounts as $accountData) {
                $parts = explode(':', $accountData, 2);
                if (count($parts) >= 2) {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO order_accounts (order_id, username, password, email, email_password, account_created_date, is_active) 
                        VALUES (?, ?, ?, ?, ?, CURDATE(), 1)
                    ");
                    $stmt->execute([
                        $orderId,
                        $parts[0], // username
                        $parts[1], // password
                        $parts[0], // email (username olarak kullan)
                        $parts[1]  // email_password (password olarak kullan)
                    ]);
                }
            }
            
            // Stok miktarını güncelle
            $stmt = $this->pdo->prepare("
                UPDATE accounts 
                SET stock_quantity = stock_quantity - ?, sales_count = sales_count + ? 
                WHERE id = ?
            ");
            $stmt->execute([$assignedCount, $assignedCount, $accountId]);
            
            return [
                'success' => true,
                'product_name' => $productName,
                'assigned_count' => $assignedCount,
                'requested_count' => $quantity,
                'message' => "$assignedCount hesap başarıyla atandı"
            ];
            
        } catch (Exception $e) {
            error_log("Account assignment error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Ödeme iptal edildiğinde işlemleri geri al
     */
    public function processCancelledPayment($orderId) {
        try {
            $this->pdo->beginTransaction();
            
            // Sipariş durumunu güncelle
            $stmt = $this->pdo->prepare("
                UPDATE orders 
                SET status = 'cancelled', delivery_status = 'failed', updated_at = NOW() 
                WHERE order_id = ?
            ");
            $stmt->execute([$orderId]);
            
            // Rezerve edilen hesapları geri al
            $stmt = $this->pdo->prepare("
                UPDATE account_stock 
                SET is_sold = 0, sold_to_user_id = NULL, sold_at = NULL, order_id = NULL 
                WHERE order_id = ?
            ");
            $stmt->execute([$orderId]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => 'Ödeme iptali işlendi'
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            error_log("Payment cancellation error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Sipariş durumunu kontrol et
     */
    public function getOrderStatus($orderId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT o.*, u.username, u.email,
                       COUNT(oa.id) as assigned_accounts_count
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                LEFT JOIN order_accounts oa ON o.order_id = oa.order_id
                WHERE o.order_id = ?
                GROUP BY o.id
            ");
            $stmt->execute([$orderId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Order status check error: " . $e->getMessage());
            return null;
        }
    }
} 
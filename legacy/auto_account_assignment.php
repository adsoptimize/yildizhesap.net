<?php
require_once 'config.php';
require_once 'functions.php';

/**
 * Otomatik Hesap Atama Sistemi
 * Admin panelinden ödeme durumu manuel olarak onaylandığında
 * kullanıcıya otomatik olarak hesapları atar
 */
class AutoAccountAssignment {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Ödeme onaylandığında hesapları otomatik ata
     */
    public function assignAccountsOnPaymentApproval($orderId) {
        try {
            // Siparişi kontrol et
            $stmt = $this->pdo->prepare("
                SELECT o.*, cp.status as payment_status 
                FROM orders o 
                LEFT JOIN crypto_payments cp ON o.order_id = cp.order_id 
                WHERE o.order_id = ?
            ");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            
            if (!$order) {
                throw new Exception("Sipariş bulunamadı: $orderId");
            }
            
            // Ödeme durumunu kontrol et
            if ($order['payment_status'] !== 'paid') {
                throw new Exception("Ödeme henüz onaylanmamış");
            }
            
            // Sipariş durumunu kontrol et
            if ($order['status'] === 'completed') {
                throw new Exception("Sipariş zaten tamamlanmış");
            }
            
            // Ürün bilgilerini al
            $productInfo = $this->getProductInfo($order['product_name']);
            
            if (!$productInfo) {
                throw new Exception("Ürün bilgisi bulunamadı: {$order['product_name']}");
            }
            
            // Stoktan hesapları al
            $accounts = $this->getAvailableAccounts($productInfo['id'], $order['quantity']);
            
            if (count($accounts) < $order['quantity']) {
                throw new Exception("Yeterli stok bulunamadı. Gereken: {$order['quantity']}, Mevcut: " . count($accounts));
            }
            
            // Hesapları siparişe ata
            $this->assignAccountsToOrder($orderId, $accounts, $order['user_id']);
            
            // Sipariş durumunu güncelle
            $this->updateOrderStatus($orderId, 'completed');
            
            // Kullanıcıya bildirim gönder
            $this->sendNotificationToUser($order['user_id'], $orderId, count($accounts));
            
            return [
                'success' => true,
                'message' => "{$order['quantity']} adet hesap başarıyla atandı",
                'accounts_assigned' => count($accounts)
            ];
            
        } catch (Exception $e) {
            error_log("AutoAccountAssignment Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Ürün bilgilerini al
     */
    private function getProductInfo($productName) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM accounts 
            WHERE title = ? AND status = 'active'
        ");
        $stmt->execute([$productName]);
        return $stmt->fetch();
    }
    
    /**
     * Stoktan uygun hesapları al
     */
    private function getAvailableAccounts($accountId, $quantity) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM account_stock 
            WHERE account_id = ? AND is_sold = 0
            ORDER BY created_at ASC 
            LIMIT ?
        ");
        $stmt->execute([$accountId, $quantity]);
        return $stmt->fetchAll();
    }
    
    /**
     * Hesapları siparişe ata
     */
    private function assignAccountsToOrder($orderId, $accounts, $userId) {
        $this->pdo->beginTransaction();
        
        try {
            foreach ($accounts as $account) {
                // order_accounts tablosuna ekle
                $stmt = $this->pdo->prepare("
                    INSERT INTO order_accounts (
                        order_id, username, password, email, email_password, 
                        totp_secret, account_created_date, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $orderId,
                    $account['username'],
                    $account['password'],
                    $account['email'] ?? '',
                    $account['email_password'] ?? '',
                    $account['totp_secret'] ?? null,
                    date('Y-m-d')
                ]);
                
                // Hesabı satıldı olarak işaretle
                $stmt = $this->pdo->prepare("
                    UPDATE account_stock 
                    SET is_sold = 1, sold_to_user_id = ?, order_id = ?, sold_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$userId, $orderId, $account['id']]);
            }
            
            $this->pdo->commit();
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Sipariş durumunu güncelle
     */
    private function updateOrderStatus($orderId, $status) {
        $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET status = ?, updated_at = NOW() 
            WHERE order_id = ?
        ");
        $stmt->execute([$status, $orderId]);
    }
    
    /**
     * Kullanıcıya bildirim gönder
     */
    private function sendNotificationToUser($userId, $orderId, $accountCount) {
        // E-posta bildirimi
        $this->sendEmailNotification($userId, $orderId, $accountCount);
        
        // Telegram bildirimi (varsa)
        $this->sendTelegramNotification($userId, $orderId, $accountCount);
    }
    
    /**
     * E-posta bildirimi gönder
     */
    private function sendEmailNotification($userId, $orderId, $accountCount) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.email, u.first_name, o.product_name 
                FROM users u 
                JOIN orders o ON u.id = o.user_id 
                WHERE u.id = ? AND o.order_id = ?
            ");
            $stmt->execute([$userId, $orderId]);
            $user = $stmt->fetch();
            
            if ($user) {
                $subject = "Hesaplarınız Hazır! - Sipariş #$orderId";
                $message = "
                    Merhaba {$user['first_name']},
                    
                    Siparişiniz (#$orderId) başarıyla tamamlandı ve hesaplarınız atandı.
                    
                    Ürün: {$user['product_name']}
                    Hesap Sayısı: $accountCount adet
                    
                    Hesaplarınızı görüntülemek için: https://yildizhesap.net/hesaplarim.php
                    
                    Teşekkürler,
                    Yıldız Hesap Ekibi
                ";
                
                // E-posta gönderme işlemi burada yapılacak
                // mail($user['email'], $subject, $message);
                
                error_log("Email notification sent to: {$user['email']} for order: $orderId");
            }
        } catch (Exception $e) {
            error_log("Email notification error: " . $e->getMessage());
        }
    }
    
    /**
     * Telegram bildirimi gönder
     */
    private function sendTelegramNotification($userId, $orderId, $accountCount) {
        try {
            // Telegram bot token ve chat ID ayarları
            $telegramSettings = $this->getTelegramSettings();
            
            if ($telegramSettings['enabled'] && !empty($telegramSettings['bot_token'])) {
                $message = "🎉 Yeni hesap ataması!\n\n";
                $message .= "Sipariş ID: #$orderId\n";
                $message .= "Hesap Sayısı: $accountCount adet\n";
                $message .= "Tarih: " . date('d.m.Y H:i');
                
                $url = "https://api.telegram.org/bot{$telegramSettings['bot_token']}/sendMessage";
                $data = [
                    'chat_id' => $telegramSettings['chat_id'],
                    'text' => $message,
                    'parse_mode' => 'HTML'
                ];
                
                $options = [
                    'http' => [
                        'method' => 'POST',
                        'header' => 'Content-Type: application/json',
                        'content' => json_encode($data)
                    ]
                ];
                
                $context = stream_context_create($options);
                file_get_contents($url, false, $context);
            }
        } catch (Exception $e) {
            error_log("Telegram notification error: " . $e->getMessage());
        }
    }
    
    /**
     * Telegram ayarlarını al
     */
    private function getTelegramSettings() {
        $stmt = $this->pdo->prepare("
            SELECT setting_key, setting_value 
            FROM site_settings 
            WHERE setting_key IN ('telegram_enabled', 'telegram_bot_token', 'telegram_chat_id')
        ");
        $stmt->execute();
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        return [
            'enabled' => $settings['telegram_enabled'] ?? '0',
            'bot_token' => $settings['telegram_bot_token'] ?? '',
            'chat_id' => $settings['telegram_chat_id'] ?? ''
        ];
    }
    
    /**
     * Toplu hesap atama (admin panel için)
     */
    public function bulkAssignAccounts($orderIds) {
        $results = [];
        
        foreach ($orderIds as $orderId) {
            $result = $this->assignAccountsOnPaymentApproval($orderId);
            $results[$orderId] = $result;
        }
        
        return $results;
    }
    
    /**
     * Stok durumunu kontrol et
     */
    public function checkStockStatus($category = null) {
        if ($category) {
            $stmt = $this->pdo->prepare("
                SELECT 
                    a.id,
                    a.title,
                    a.platform,
                    COUNT(ast.id) as total,
                    SUM(CASE WHEN ast.is_sold = 0 THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN ast.is_sold = 1 THEN 1 ELSE 0 END) as sold
                FROM accounts a
                LEFT JOIN account_stock ast ON a.id = ast.account_id
                WHERE a.platform = ? AND a.status = 'active'
                GROUP BY a.id, a.title, a.platform
                ORDER BY available DESC
            ");
            $stmt->execute([$category]);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT 
                    a.id,
                    a.title,
                    a.platform,
                    COUNT(ast.id) as total,
                    SUM(CASE WHEN ast.is_sold = 0 THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN ast.is_sold = 1 THEN 1 ELSE 0 END) as sold
                FROM accounts a
                LEFT JOIN account_stock ast ON a.id = ast.account_id
                WHERE a.status = 'active'
                GROUP BY a.id, a.title, a.platform
                ORDER BY available DESC
            ");
            $stmt->execute();
        }
        
        return $stmt->fetchAll();
    }
    
    /**
     * Kategori listesini al
     */
    public function getCategories() {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT platform as category 
            FROM accounts 
            WHERE status = 'active'
            ORDER BY platform
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

// API endpoint olarak kullanılabilir
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $assignment = new AutoAccountAssignment($pdo);
    
    switch ($_POST['action']) {
        case 'assign_single':
            $orderId = $_POST['order_id'] ?? '';
            if (empty($orderId)) {
                echo json_encode(['success' => false, 'message' => 'Sipariş ID gerekli']);
                exit;
            }
            
            $result = $assignment->assignAccountsOnPaymentApproval($orderId);
            echo json_encode($result);
            break;
            
        case 'assign_bulk':
            $orderIds = $_POST['order_ids'] ?? [];
            if (empty($orderIds)) {
                echo json_encode(['success' => false, 'message' => 'Sipariş ID listesi gerekli']);
                exit;
            }
            
            $result = $assignment->bulkAssignAccounts($orderIds);
            echo json_encode(['success' => true, 'results' => $result]);
            break;
            
        case 'check_stock':
            $category = $_POST['category'] ?? '';
            $stock = $assignment->checkStockStatus($category);
            echo json_encode(['success' => true, 'stock' => $stock]);
            break;
            
        case 'get_categories':
            $categories = $assignment->getCategories();
            echo json_encode(['success' => true, 'categories' => $categories]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
    }
    exit;
}
?> 
<?php
class OrderManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Kullanıcının siparişlerini getir
     */
    public function getUserOrders($userId, $limit = null, $offset = 0) {
        try {
            $sql = "SELECT 
                        o.order_id,
                        o.product_name,
                        o.category,
                        o.quantity,
                        o.unit_price,
                        o.total_price,
                        o.order_date,
                        o.status,
                        o.delivery_status,
                        COUNT(CASE WHEN ast.is_sold = 1 THEN ast.id END) as account_count
                    FROM orders o
                    LEFT JOIN account_stock ast ON o.id = ast.order_id
                    WHERE o.user_id = :user_id 
                    GROUP BY o.id
                    ORDER BY o.order_date DESC";
            
            if ($limit) {
                $sql .= " LIMIT :limit OFFSET :offset";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            
            if ($limit) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("OrderManager::getUserOrders Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Kullanıcının toplam sipariş sayısını getir
     */
    public function getUserOrderCount($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("OrderManager::getUserOrderCount Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Sipariş hesaplarını getir
     */
    public function getOrderAccounts($orderId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    ast.username,
                    ast.password,
                    ast.email,
                    ast.additional_info,
                    ast.account_data,
                    ast.created_at
                FROM account_stock ast
                JOIN orders o ON ast.order_id = o.id
                WHERE o.order_id = :order_id AND ast.is_sold = 1
                ORDER BY ast.id ASC
            ");
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("OrderManager::getOrderAccounts Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Kullanıcının sipariş istatistiklerini getir
     */
    public function getUserOrderStats($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_orders,
                    SUM(total_price) as total_spent,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
                    COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_orders,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
                    COUNT(CASE WHEN delivery_status = 'delivered' THEN 1 END) as delivered_orders
                FROM orders 
                WHERE user_id = :user_id
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("OrderManager::getUserOrderStats Error: " . $e->getMessage());
            return [
                'total_orders' => 0,
                'total_spent' => 0,
                'completed_orders' => 0,
                'processing_orders' => 0,
                'pending_orders' => 0,
                'delivered_orders' => 0
            ];
        }
    }
    
    /**
     * Sipariş detaylarını getir
     */
    public function getOrderDetails($orderId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    order_id,
                    product_name,
                    category,
                    quantity,
                    unit_price,
                    total_price,
                    order_date,
                    status,
                    delivery_status
                FROM orders 
                WHERE order_id = :order_id AND user_id = :user_id
            ");
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("OrderManager::getOrderDetails Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Yeni sipariş oluştur
     */
    public function createOrder($userId, $orderData) {
        try {
            $this->pdo->beginTransaction();
            
            // Sipariş ID oluştur
            $orderId = 'SP' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            
            // Sipariş tablosuna ekle
            $stmt = $this->pdo->prepare("
                INSERT INTO orders (
                    order_id, user_id, product_name, category, quantity, 
                    unit_price, total_price, status, delivery_status
                ) VALUES (
                    :order_id, :user_id, :product_name, :category, :quantity,
                    :unit_price, :total_price, :status, :delivery_status
                )
            ");
            
            $stmt->execute([
                ':order_id' => $orderId,
                ':user_id' => $userId,
                ':product_name' => $orderData['product_name'],
                ':category' => $orderData['category'],
                ':quantity' => $orderData['quantity'],
                ':unit_price' => $orderData['unit_price'],
                ':total_price' => $orderData['total_price'],
                ':status' => $orderData['status'] ?? 'pending',
                ':delivery_status' => $orderData['delivery_status'] ?? 'pending'
            ]);
            
            $this->pdo->commit();
            return $orderId;
        } catch (PDOException $e) {
            $this->pdo->rollback();
            error_log("OrderManager::createOrder Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sipariş durumunu güncelle
     */
    public function updateOrderStatus($orderId, $status, $deliveryStatus = null) {
        try {
            $sql = "UPDATE orders SET status = :status";
            $params = [':status' => $status, ':order_id' => $orderId];
            
            if ($deliveryStatus) {
                $sql .= ", delivery_status = :delivery_status";
                $params[':delivery_status'] = $deliveryStatus;
            }
            
            $sql .= " WHERE order_id = :order_id";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("OrderManager::updateOrderStatus Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Siparişe hesap ekle
     */
    public function addAccountToOrder($orderId, $accountData) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO order_accounts (
                    order_id, username, password, email, email_password, 
                    totp_secret, account_created_date
                ) VALUES (
                    :order_id, :username, :password, :email, :email_password,
                    :totp_secret, :account_created_date
                )
            ");
            
            return $stmt->execute([
                ':order_id' => $orderId,
                ':username' => $accountData['username'],
                ':password' => $accountData['password'],
                ':email' => $accountData['email'],
                ':email_password' => $accountData['email_password'],
                ':totp_secret' => $accountData['totp_secret'] ?? null,
                ':account_created_date' => $accountData['account_created_date'] ?? date('Y-m-d')
            ]);
        } catch (PDOException $e) {
            error_log("OrderManager::addAccountToOrder Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Hesabı deaktif et
     */
    public function deactivateAccount($orderId, $username) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE order_accounts 
                SET is_active = 0 
                WHERE order_id = :order_id AND username = :username
            ");
            return $stmt->execute([
                ':order_id' => $orderId,
                ':username' => $username
            ]);
        } catch (PDOException $e) {
            error_log("OrderManager::deactivateAccount Error: " . $e->getMessage());
            return false;
        }
    }
}
?>

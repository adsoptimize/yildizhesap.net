<?php
require_once '../config.php';
require_once '../functions.php';

// Veritabanı bağlantısı
try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    error_log("Database connection error: " . $e->getMessage());
    exit("Database connection error");
}

// Gelen veriyi al
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (!$data) {
    http_response_code(400);
    exit("Invalid JSON data");
}

// İmza doğrulama
$stmt = $db->prepare("SELECT setting_value FROM crypto_settings WHERE setting_key = 'cryptomus_payment_key'");
$stmt->execute();
$APIKEY = $stmt->fetchColumn() ?: 'Fpw0XJtQV4xNMGZk4elsbeoXbhNbD4FF2n91CsjfBWeSmHZUPJ0qLmqp71xK9SGLEkHG0P9KBjp1SBmJspoIXgL252OLXRT4J6wLXTlBEZhAF7XKFvbsREe0PEg6DeDm';

$receivedSign = $_SERVER['HTTP_SIGN'] ?? '';
$calculatedSign = md5(base64_encode($payload) . $APIKEY);

if ($receivedSign !== $calculatedSign) {
    http_response_code(403);
    error_log("Invalid webhook signature. Received: $receivedSign, Calculated: $calculatedSign");
    exit("Invalid signature");
}

// Ödeme durumunu kontrol et
if (isset($data['status']) && $data['status'] === 'paid') {
    $order_id = $data['order_id'];
    $amount = $data['amount'];
    $payment_uuid = $data['uuid'];
    
    try {
        $db->beginTransaction();
        
        // Ödeme kaydını bul ve güncelle
        $stmt = $db->prepare("SELECT * FROM crypto_payments WHERE order_id = ? AND status = 'pending'");
        $stmt->execute([$order_id]);
        $payment = $stmt->fetch();
        
        if (!$payment) {
            throw new Exception("Payment not found or already processed: $order_id");
        }
        
        // Ödeme durumunu güncelle
        $stmt = $db->prepare("UPDATE crypto_payments SET status = 'paid', payment_amount = ?, updated_at = NOW() WHERE order_id = ?");
        $stmt->execute([$amount, $order_id]);
        
        // Ödeme öğelerini al
        $stmt = $db->prepare("SELECT * FROM payment_items WHERE payment_id = ?");
        $stmt->execute([$payment['id']]);
        $paymentItems = $stmt->fetchAll();
        
        // Her öğe için hesapları kullanıcıya aktar
        foreach ($paymentItems as $item) {
            // Hesap bilgilerini al
            $stmt = $db->prepare("SELECT * FROM accounts WHERE id = ? AND status = 'active'");
            $stmt->execute([$item['account_id']]);
            $account = $stmt->fetch();
            
            if (!$account) {
                throw new Exception("Account not found: " . $item['account_id']);
            }
            
            // Stok kontrolü
            if ($account['stock_quantity'] < $item['quantity']) {
                throw new Exception("Insufficient stock for account: " . $item['account_id']);
            }
            
            // Hesapları rezerve et (StockManager kullanarak)
            require_once '../StockManager.php';
            $stockManager = new StockManager();
            
            $reservedAccounts = $stockManager->reserveAccounts(
                $item['account_id'], 
                $item['quantity'], 
                $payment['user_id'], 
                $order_id
            );
            
            if (count($reservedAccounts) < $item['quantity']) {
                throw new Exception("Could not reserve enough accounts: " . $item['account_id']);
            }
            
            // Kullanıcının siparişlerine ekle
            foreach ($reservedAccounts as $reservedAccount) {
                $stmt = $db->prepare("
                    INSERT INTO orders (user_id, product_name, category, quantity, unit_price, total_price, status, delivery_status, account_data, created_at) 
                    VALUES (?, ?, 'Social Media Account', 1, ?, ?, 'completed', 'delivered', ?, NOW())
                ");
                $stmt->execute([
                    $payment['user_id'],
                    $account['title'],
                    $item['unit_price'],
                    $item['unit_price'],
                    $reservedAccount['account_data']
                ]);
            }
            
            // Stok miktarını güncelle
            $stmt = $db->prepare("UPDATE accounts SET stock_quantity = stock_quantity - ?, sales_count = sales_count + ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['quantity'], $item['account_id']]);
        }
        
        // İşlem logu
        $stmt = $db->prepare("INSERT INTO payment_logs (user_id, amount, status, payment_uuid, order_id, created_at) VALUES (?, ?, 'paid', ?, ?, NOW())");
        $stmt->execute([$payment['user_id'], $amount, $payment_uuid, $order_id]);
        
        $db->commit();
        
        error_log("Payment processed successfully: $order_id, User: {$payment['user_id']}, Amount: $amount");
        echo "success";
        
    } catch (Exception $e) {
        $db->rollback();
        http_response_code(500);
        error_log("Payment processing error: " . $e->getMessage() . " | Order ID: $order_id");
        echo "Database error: " . $e->getMessage();
    }
    exit();
}

// Diğer durumlar için (pending, failed, etc.)
http_response_code(200);
echo "ignored";
?>
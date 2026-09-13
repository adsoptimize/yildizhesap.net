<?php
/**
 * Shopier Webhook Handler
 * Ödeme durumu değişikliklerini işler
 */

require_once 'config.php';
require_once 'functions.php';
require_once 'PaymentProcessor.php';

// Shopier ayarlarını al
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM crypto_settings WHERE setting_key IN ('shopier_username', 'shopier_key')");
$stmt->execute();
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$username = $settings['shopier_username'] ?? '';
$key = $settings['shopier_key'] ?? '';

// Webhook log fonksiyonu
function logWebhook($message) {
    $logFile = 'shopier_webhook.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

try {
    logWebhook("Webhook received - POST data: " . json_encode($_POST));
    
    // Gelmesi gereken veriler kontrol edilir
    if (!(isset($_POST['res']) && isset($_POST['hash']))) {
        logWebhook("Missing parameter: res or hash");
        echo "missing parameter";
        die();
    }
    
    // Özet kontrolü yapılır
    $hash = hash_hmac('sha256', $_POST['res'] . $username, $key, false);
    if (strcmp($hash, $_POST['hash']) != 0) {
        logWebhook("Hash verification failed");
        die();
    }
    
    // Veriler alınır
    $json_result = base64_decode($_POST['res']);
    $array_result = json_decode($json_result, true);
    
    if (!$array_result) {
        logWebhook("JSON decode failed");
        echo "invalid data";
        die();
    }
    
    logWebhook("Decoded data: " . json_encode($array_result));
    
    // Verilerle ilgili yapmanız gereken işlemleri yapınız
    // Bildirim çeşitli ağ sorunları nedeni ile birden fazla kez gelebilir
    // İlk olarak orderid parametresini kullanıp siparişin işlenme durumunu kontrol ediniz
    
    $email = $array_result['email'];
    $orderid = $array_result['orderid'];
    $currency = $array_result['currency']; // 0..TL, 1..USD, 2...EUR
    $price = $array_result['price'];
    $buyername = $array_result['buyername'];
    $buyersurname = $array_result['buyersurname'];
    $productcount = $array_result['productcount'];
    $productid = $array_result['productid'];
    $productlist = $array_result['productlist'];
    $chartdetails = $array_result['chartdetails'];
    $customernote = $array_result['customernote']; // Müşterinizin siparişte doldurduğu not alanı
    $istest = $array_result['istest']; // 0..canlı, 1..test
    
    logWebhook("Processing order: $orderid, Email: $email, Price: $price, Test: $istest");
    
    // Siparişin daha önce işlenip işlenmediğini kontrol et
    $stmt = $pdo->prepare("SELECT id FROM crypto_payments WHERE order_id = ? AND status = 'paid'");
    $stmt->execute([$orderid]);
    if ($stmt->fetch()) {
        logWebhook("Order already processed: $orderid");
        echo "success";
        die();
    }
    
    // Siparişin ziyaretçi siparişi mi yoksa kullanıcı siparişi mi olduğunu kontrol et
    $stmt = $pdo->prepare("SELECT user_id, is_guest_order FROM crypto_payments WHERE order_id = ?");
    $stmt->execute([$orderid]);
    $existingPayment = $stmt->fetch();
    
    $isGuestOrder = true;
    $userId = 0;
    
    if ($existingPayment) {
        $isGuestOrder = $existingPayment['is_guest_order'];
        $userId = $existingPayment['user_id'];
    }
    
    try {
        $pdo->beginTransaction();
        
        // Ödeme durumunu güncelle
        $stmt = $pdo->prepare("
            UPDATE crypto_payments 
            SET status = 'paid', 
                shopier_payment_id = ?,
                customer_name = ?,
                email = ?,
                updated_at = NOW()
            WHERE order_id = ?
        ");
        $stmt->execute([
            $orderid,
            $buyername . ' ' . $buyersurname,
            $email,
            $orderid
        ]);
        
        if ($isGuestOrder) {
             // Ziyaretçi siparişi için hesapları doğrudan order_accounts tablosuna ekle
             $result = processGuestOrder($orderid, $email, $buyername . ' ' . $buyersurname);
         } else {
             // Kayıtlı kullanıcı siparişi için PaymentProcessor kullan
             $paymentProcessor = new PaymentProcessor();
             $result = $paymentProcessor->processSuccessfulPayment($orderid, 'shopier');
         }
        
        if (!$result['success']) {
            throw new Exception("Account assignment failed: " . $result['message']);
        }
        
        $pdo->commit();
        
        logWebhook("Payment processed successfully: $orderid, Email: $email");
        
        // Sonuç bilgilerini logla
        if (!empty($result['assigned_accounts'])) {
            $totalAssigned = array_sum(array_column($result['assigned_accounts'], 'assigned_count'));
            logWebhook("Assigned $totalAssigned accounts for order $orderid");
        }
        
        if (!empty($result['stock_shortages'])) {
            foreach ($result['stock_shortages'] as $shortage) {
                logWebhook("Stock shortage for order $orderid: {$shortage['product_name']} - Requested: {$shortage['requested_quantity']}, Available: {$shortage['available_stock']}");
            }
        }
        
    } catch (Exception $e) {
        $pdo->rollback();
        logWebhook("Payment processing error: " . $e->getMessage());
        throw $e;
    }
    
    // İşlem başarılı olduğunda success yazılarak OSB'nin başarılı geldiği doğrulanmış olunur
    echo "success";
    
} catch (Exception $e) {
    logWebhook("Hata: " . $e->getMessage());
    echo "error";
}

/**
 * Ziyaretçi siparişlerini işle
 */
function processGuestOrder($orderId, $email, $customerName) {
    global $pdo;
    
    try {
        // Sipariş detaylarını al
        $stmt = $pdo->prepare("
            SELECT oi.product_id, oi.quantity, p.name as product_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        $orderItems = $stmt->fetchAll();
        
        if (empty($orderItems)) {
            return ['success' => false, 'message' => 'Sipariş öğeleri bulunamadı'];
        }
        
        $totalAssigned = 0;
        
        foreach ($orderItems as $item) {
            $productId = $item['product_id'];
            $quantity = $item['quantity'];
            
            // Stokta bulunan hesapları al
            $stmt = $pdo->prepare("
                SELECT id, username, password, email as account_email, additional_info
                FROM accounts 
                WHERE product_id = ? AND status = 'available' 
                ORDER BY created_at ASC 
                LIMIT ?
            ");
            $stmt->execute([$productId, $quantity]);
            $accounts = $stmt->fetchAll();
            
            if (count($accounts) < $quantity) {
                return [
                    'success' => false, 
                    'message' => "Yeterli stok yok. İstenen: {$quantity}, Mevcut: " . count($accounts)
                ];
            }
            
            // Hesapları sipariş ile ilişkilendir
            foreach ($accounts as $account) {
                // order_accounts tablosuna ekle
                $stmt = $pdo->prepare("
                    INSERT INTO order_accounts (
                        order_id, account_id, product_id, assigned_at, 
                        customer_email, customer_name
                    ) VALUES (?, ?, ?, NOW(), ?, ?)
                ");
                $stmt->execute([
                    $orderId, 
                    $account['id'], 
                    $productId,
                    $email,
                    $customerName
                ]);
                
                // Hesap durumunu güncelle
                $stmt = $pdo->prepare("
                    UPDATE accounts 
                    SET status = 'sold', sold_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$account['id']]);
                
                $totalAssigned++;
            }
        }
        
        return [
            'success' => true, 
            'message' => "{$totalAssigned} hesap başarıyla atandı",
            'assigned_count' => $totalAssigned
        ];
        
    } catch (Exception $e) {
        error_log("Guest order processing error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

?>
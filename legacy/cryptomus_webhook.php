<?php
/**
 * Cryptomus Webhook Handler
 * Ödeme durumu değişikliklerini işler
 */

require_once 'config.php';
require_once 'functions.php';
require_once 'PaymentProcessor.php';

// Sadece POST isteklerini kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Content-Type kontrolü
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') === false) {
    http_response_code(400);
    exit('Invalid content type');
}

try {
    // Webhook verilerini al
    $rawData = file_get_contents('php://input');
    if (empty($rawData)) {
        throw new Exception('Empty webhook data');
    }
    
    $webhookData = json_decode($rawData, true);
    if (!$webhookData) {
        throw new Exception('Invalid JSON data');
    }
    
    // İmzayı al
    $signature = $_SERVER['HTTP_SIGN'] ?? $_SERVER['HTTP_X_SIGN'] ?? '';
    if (empty($signature)) {
        throw new Exception('Missing signature');
    }
    
    // Veritabanı bağlantısı
    $db = Database::getInstance()->getConnection();
    
    // İmza doğrulama
    $stmt = $db->prepare("SELECT setting_value FROM crypto_settings WHERE setting_key = 'cryptomus_payment_key'");
    $stmt->execute();
    $APIKEY = $stmt->fetchColumn() ?: 'Fpw0XJtQV4xNMGZk4elsbeoXbhNbD4FF2n91CsjfBWeSmHZUPJ0qLmqp71xK9SGLEkHG0P9KBjp1SBmJspoIXgL252OLXRT4J6wLXTlBEZhAF7XKFvbsREe0PEg6DeDm';
    
    $calculatedSign = md5(base64_encode($rawData) . $APIKEY);
    
    if ($signature !== $calculatedSign) {
        throw new Exception('Invalid webhook signature');
    }
    
    // Webhook'u logla
    $stmt = $db->prepare("
        INSERT INTO crypto_webhook_logs (order_id, cryptomus_uuid, webhook_data, signature, status, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, 'valid', ?, ?)
    ");
    $stmt->execute([
        $webhookData['order_id'] ?? null,
        $webhookData['uuid'] ?? null,
        $rawData,
        $signature,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    // Ödeme durumunu kontrol et
    if (isset($webhookData['status']) && $webhookData['status'] === 'paid') {
        $order_id = $webhookData['order_id'];
        $amount = $webhookData['amount'];
        $payment_uuid = $webhookData['uuid'];
        
        try {
            $db->beginTransaction();
            
            // Çift işlem koruması - Siparişin daha önce işlenip işlenmediğini kontrol et
            $stmt = $db->prepare("SELECT id FROM crypto_payments WHERE order_id = ? AND status = 'paid'");
            $stmt->execute([$order_id]);
            if ($stmt->fetch()) {
                error_log("Order already processed: $order_id");
                $db->commit();
                http_response_code(200);
                echo json_encode(['status' => 'already_processed']);
                exit;
            }
            
            // Ödeme kaydını bul
            $stmt = $db->prepare("SELECT * FROM crypto_payments WHERE order_id = ? AND status = 'pending'");
            $stmt->execute([$order_id]);
            $payment = $stmt->fetch();
            
            if (!$payment) {
                throw new Exception("Payment not found or already processed: $order_id");
            }
            
            // Ödeme durumunu güncelle
            $stmt = $db->prepare("UPDATE crypto_payments SET status = 'paid', payment_amount = ?, updated_at = NOW() WHERE order_id = ?");
            $stmt->execute([$amount, $order_id]);
            
            // PaymentProcessor ile hesapları ata
            $paymentProcessor = new PaymentProcessor();
            $result = $paymentProcessor->processSuccessfulPayment($order_id, 'cryptomus');
            
            if (!$result['success']) {
                throw new Exception("Account assignment failed: " . $result['message']);
            }
            
            // İşlem logu (guest user için user_id = 0)
            $user_id = $payment['is_guest_order'] ? 0 : $payment['user_id'];
            $stmt = $db->prepare("INSERT INTO payment_logs (user_id, amount, status, payment_uuid, order_id, created_at) VALUES (?, ?, 'paid', ?, ?, NOW())");
            $stmt->execute([$user_id, $amount, $payment_uuid, $order_id]);
            
            $db->commit();
            
            // Webhook logunu güncelle
            $stmt = $db->prepare("UPDATE crypto_webhook_logs SET status = 'processed', processed_at = NOW() WHERE order_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$order_id]);
            
            // Log mesajı (guest/user ayrımı ile)
            $userType = $payment['is_guest_order'] ? 'Guest' : 'User';
            $userId = $payment['is_guest_order'] ? 'Guest' : $payment['user_id'];
            error_log("Payment processed successfully: $order_id, $userType: $userId, Amount: $amount");
            
            // Sonuç bilgilerini logla
            if (!empty($result['assigned_accounts'])) {
                $totalAssigned = array_sum(array_column($result['assigned_accounts'], 'assigned_count'));
                error_log("Assigned $totalAssigned accounts to $userType $userId for order $order_id");
            }
            
            if (!empty($result['stock_shortages'])) {
                foreach ($result['stock_shortages'] as $shortage) {
                    error_log("Stock shortage for order $order_id: {$shortage['product_name']} - Requested: {$shortage['requested_quantity']}, Available: {$shortage['available_stock']}");
                }
            }
        
        http_response_code(200);
        echo json_encode(['status' => 'ok']);
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    } elseif (isset($webhookData['status']) && in_array($webhookData['status'], ['failed', 'cancelled'])) {
        // Ödeme başarısız olduğunda
        $order_id = $webhookData['order_id'];
        
        try {
            $db->beginTransaction();
            
            // Ödeme kaydını güncelle
            $stmt = $db->prepare("UPDATE crypto_payments SET status = 'failed', updated_at = NOW() WHERE order_id = ?");
            $stmt->execute([$order_id]);
            
            // PaymentProcessor ile iptal işlemini gerçekleştir
            $paymentProcessor = new PaymentProcessor();
            $result = $paymentProcessor->processCancelledPayment($order_id);
            
            if (!$result['success']) {
                error_log("Payment cancellation failed: " . $result['message']);
            }
            
            $db->commit();
            
            error_log("Payment cancelled: $order_id");
            
        } catch (Exception $e) {
            $db->rollback();
            error_log("Payment cancellation error: " . $e->getMessage());
        }
        
        http_response_code(200);
        echo json_encode(['status' => 'cancelled']);
    } else {
        // Diğer durumlar için (pending, etc.)
        http_response_code(200);
        echo json_encode(['status' => 'ignored']);
    }
    
} catch (Exception $e) {
    // Kritik hataları logla
    error_log('Cryptomus Webhook Error: ' . $e->getMessage() . ' | Data: ' . ($rawData ?? 'N/A'));
    
    // Webhook logunu hata olarak işaretle
    if (isset($webhookData['order_id'])) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE crypto_webhook_logs SET status = 'error' WHERE order_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$webhookData['order_id']]);
        } catch (Exception $logError) {
            error_log('Failed to update webhook log: ' . $logError->getMessage());
        }
    }
    
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

// İşlem tamamlandı
exit;
?>

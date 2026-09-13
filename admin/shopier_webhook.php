<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'ShopierClient.php';

// Webhook log dosyası
$logFile = 'logs/shopier_webhook.log';

// Log fonksiyonu
function logWebhook($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// Webhook başlangıç logu
logWebhook("Shopier webhook başlatıldı");

try {
    // POST verilerini al
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        $data = $_POST; // Fallback olarak POST verilerini kullan
    }
    
    logWebhook("Gelen veri: " . json_encode($data));
    
    // Gerekli alanları kontrol et
    if (!isset($data['payment_id']) || !isset($data['order_id']) || !isset($data['status'])) {
        throw new Exception('Eksik webhook verileri');
    }
    
    // Shopier ayarlarını al
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM crypto_settings WHERE setting_key IN ('shopier_api_key', 'shopier_secret_key', 'shopier_test_mode')");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $apiKey = $settings['shopier_api_key'] ?? '';
    $secretKey = $settings['shopier_secret_key'] ?? '';
    $testMode = ($settings['shopier_test_mode'] ?? '0') === '1';
    
    if (empty($apiKey) || empty($secretKey)) {
        throw new Exception('Shopier API ayarları eksik');
    }
    
    // Shopier client'ı oluştur
    $shopier = new ShopierClient($apiKey, $secretKey, $testMode);
    
    // Webhook imzasını doğrula
    $signature = $_SERVER['HTTP_X_SHOPIER_SIGNATURE'] ?? '';
    if (!$shopier->verifyWebhook($data, $signature)) {
        throw new Exception('Webhook imzası geçersiz');
    }
    
    logWebhook("Webhook imzası doğrulandı");
    
    // Ödeme durumunu kontrol et
    $paymentId = $data['payment_id'];
    $orderId = $data['order_id'];
    $status = $data['status'];
    
    logWebhook("Ödeme ID: $paymentId, Sipariş ID: $orderId, Durum: $status");
    
    // Siparişi veritabanından bul
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        throw new Exception("Sipariş bulunamadı: $orderId");
    }
    
    logWebhook("Sipariş bulundu: " . json_encode($order));
    
    // Ödeme durumuna göre işlem yap
    if ($status === 'success' || $status === 'completed') {
        // Ödeme başarılı - siparişi güncelle
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid', payment_method = 'shopier', updated_at = NOW() WHERE order_id = ?");
        $stmt->execute([$orderId]);
        
        // Hesapları kullanıcıya ata
        $stmt = $pdo->prepare("SELECT * FROM order_accounts WHERE order_id = ? AND status = 'pending'");
        $stmt->execute([$orderId]);
        $pendingAccounts = $stmt->fetchAll();
        
        foreach ($pendingAccounts as $account) {
            // Hesabı kullanıcıya ata
            $stmt = $pdo->prepare("UPDATE order_accounts SET status = 'assigned', assigned_at = NOW() WHERE id = ?");
            $stmt->execute([$account['id']]);
            
            logWebhook("Hesap atandı: " . $account['username']);
        }
        
        logWebhook("Ödeme başarılı - Sipariş tamamlandı");
        
        // Kullanıcıya bildirim gönder (opsiyonel)
        // TODO: Email bildirimi eklenebilir
        
    } elseif ($status === 'failed' || $status === 'cancelled') {
        // Ödeme başarısız - siparişi iptal et
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'failed', updated_at = NOW() WHERE order_id = ?");
        $stmt->execute([$orderId]);
        
        logWebhook("Ödeme başarısız - Sipariş iptal edildi");
        
    } else {
        // Bilinmeyen durum
        logWebhook("Bilinmeyen ödeme durumu: $status");
    }
    
    // Başarılı yanıt döndür
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Webhook işlendi']);
    
} catch (Exception $e) {
    logWebhook("Hata: " . $e->getMessage());
    
    // Hata yanıtı döndür
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} 
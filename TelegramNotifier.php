<?php
/**
 * Telegram Notification System
 * Admin bildirimleri için Telegram entegrasyonu
 */

// Output buffering başlat (eğer başlatılmamışsa)
if (!ob_get_level()) {
    ob_start();
}

class TelegramNotifier {
    private $botToken;
    private $chatId;
    private $enabled;
    
    public function __construct() {
        global $pdo;
        
        // Telegram ayarlarını veritabanından al
        try {
            $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM crypto_settings WHERE setting_key IN ('telegram_bot_token', 'telegram_chat_id', 'telegram_enabled')");
            $stmt->execute();
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $this->botToken = $settings['telegram_bot_token'] ?? '';
            $this->chatId = $settings['telegram_chat_id'] ?? '';
            $this->enabled = ($settings['telegram_enabled'] ?? '0') === '1';
        } catch (Exception $e) {
            error_log("Telegram settings error: " . $e->getMessage());
            $this->enabled = false;
        }
    }
    
    /**
     * Stok yetersizliği bildirimi gönder
     */
    public function sendStockShortageNotification($orderId, $productName, $requestedQuantity, $availableStock, $userInfo) {
        if (!$this->enabled || empty($this->botToken) || empty($this->chatId)) {
            error_log("Telegram notification disabled or not configured");
            return false;
        }
        
        $message = "🚨 *STOK YETERSİZLİĞİ UYARISI*\n\n";
        $message .= "📦 **Ürün:** " . $productName . "\n";
        $message .= "🆔 **Sipariş Kodu:** `" . $orderId . "`\n";
        $message .= "👤 **Müşteri:** " . $userInfo['username'] . " (" . $userInfo['email'] . ")\n";
        $message .= "📊 **İstenen Miktar:** " . $requestedQuantity . "\n";
        $message .= "📉 **Mevcut Stok:** " . $availableStock . "\n";
        $message .= "⏰ **Tarih:** " . date('d.m.Y H:i:s') . "\n\n";
        $message .= "⚠️ Bu sipariş stok yetersizliği sebebi ile teslim edilemedi!";
        
        return $this->sendMessage($message);
    }
    
    /**
     * Başarılı ödeme bildirimi gönder
     */
    public function sendPaymentSuccessNotification($orderId, $amount, $userInfo, $productName, $quantity) {
        if (!$this->enabled || empty($this->botToken) || empty($this->chatId)) {
            return false;
        }
        
        $message = "✅ *BAŞARILI ÖDEME*\n\n";
        $message .= "💰 **Tutar:** " . formatPrice($amount) . "\n";
        $message .= "🆔 **Sipariş Kodu:** `" . $orderId . "`\n";
        $message .= "👤 **Müşteri:** " . $userInfo['username'] . " (" . $userInfo['email'] . ")\n";
        $message .= "📦 **Ürün:** " . $productName . "\n";
        $message .= "📊 **Miktar:** " . $quantity . "\n";
        $message .= "⏰ **Tarih:** " . date('d.m.Y H:i:s');
        
        return $this->sendMessage($message);
    }
    
    /**
     * Genel mesaj gönder
     */
    public function sendMessage($message) {
        if (!$this->enabled || empty($this->botToken) || empty($this->chatId)) {
            return false;
        }
        
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
        $data = [
            'chat_id' => $this->chatId,
            'text' => $message,
            'parse_mode' => 'Markdown'
        ];
        
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === false) {
            error_log("Telegram notification failed: " . error_get_last()['message']);
            return false;
        }
        
        $response = json_decode($result, true);
        return isset($response['ok']) && $response['ok'];
    }
    
    /**
     * Test mesajı gönder
     */
    public function sendTestMessage() {
        $message = "🧪 *TEST MESAJI*\n\n";
        $message .= "Telegram bildirim sistemi başarıyla çalışıyor!\n";
        $message .= "⏰ **Tarih:** " . date('d.m.Y H:i:s');
        
        return $this->sendMessage($message);
    }
} 
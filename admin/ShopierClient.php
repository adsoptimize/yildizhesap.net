<?php
/**
 * Shopier Payment Client
 * Shopier API entegrasyonu için sınıf
 */

// Output buffering başlat (eğer başlatılmamışsa)
if (!ob_get_level()) {
    ob_start();
}

class ShopierClient {
    private $apiKey;
    private $secretKey;
    private $testMode;
    private $baseUrl;
    
    public function __construct($apiKey, $secretKey, $testMode = false) {
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
        $this->testMode = $testMode;
        $this->baseUrl = $testMode ? 'https://test.shopier.com/api' : 'https://www.shopier.com/api';
    }
    
    /**
     * Ödeme oluştur - Shopier API v4 formatında
     */
    public function createPayment($orderData) {
        // Shopier API v4 için gerekli parametreler
        srand(time());
        $args = [
            'API_key' => $this->apiKey,
            'website_index' => 1,
            'platform_order_id' => $orderData['order_id'],
            'product_name' => $orderData['description'] ?? 'Hesap Satın Alma',
            'product_type' => 1,
            'buyer_name' => explode(' ', $orderData['customer_name'])[0] ?? 'Müşteri',
            'buyer_surname' => explode(' ', $orderData['customer_name'], 2)[1] ?? 'Soyadı',
            'buyer_email' => $orderData['customer_email'],
            'buyer_account_age' => 30,
            'buyer_id_nr' => rand(1, 999999),
            'buyer_phone' => $orderData['customer_phone'] ?? '05000000000',
            'billing_address' => 'Adres',
            'billing_city' => 'İstanbul',
            'billing_country' => 'Türkiye',
            'billing_postcode' => '34000',
            'shipping_address' => 'Adres',
            'shipping_city' => 'İstanbul',
            'shipping_country' => 'Türkiye',
            'shipping_postcode' => '34000',
            'total_order_value' => $orderData['amount'],
            'currency' => $this->getCurrencyCode($orderData['currency'] ?? 'TRY'),
            'platform' => 2,
            'is_in_frame' => 0,
            'current_language' => 0,
            'random_nr' => rand(100000, 999999)
        ];
        
        // Signature oluştur
        $signature = $this->customHmac(
            $args['random_nr'] . $args['platform_order_id'] . $args['total_order_value'] . $args['currency'],
            $this->secretKey
        );
        $args['signature'] = base64_encode($signature);
        
        return [
            'success' => true,
            'payment_data' => $args,
            'payment_url' => 'https://www.shopier.com/ShowProduct/api_pay4.php'
        ];
    }
    
    /**
     * Ödeme durumunu kontrol et
     */
    public function checkPaymentStatus($paymentId) {
        $data = [
            'api_key' => $this->apiKey,
            'payment_id' => $paymentId,
            'timestamp' => time()
        ];
        
        $data['hash'] = $this->createHash($data);
        
        return $this->makeRequest('POST', '/payment/status', $data);
    }
    
    /**
     * Hash oluştur
     */
    private function createHash($data) {
        // Hash için gerekli alanları sırala
        $hashString = $data['api_key'] . $data['amount'] . $data['currency'] . $data['order_id'] . $data['timestamp'] . $this->secretKey;
        return hash('sha256', $hashString);
    }
    
    /**
     * Shopier için özel HMAC fonksiyonu
     */
    private function customHmac($data, $key) {
        return hash_hmac('SHA256', $data, $key, true);
    }
    
    /**
     * Para birimi kodunu dönüştür
     */
    private function getCurrencyCode($currency) {
        switch (strtoupper($currency)) {
            case 'TRY':
            case 'TL':
                return 0;
            case 'USD':
                return 1;
            case 'EUR':
                return 2;
            default:
                return 0; // Varsayılan TL
        }
    }
    
    /**
     * Webhook doğrulama
     */
    public function verifyWebhook($data, $signature) {
        $expectedHash = hash('sha256', $data['api_key'] . $data['payment_id'] . $data['order_id'] . $data['status'] . $data['timestamp'] . $this->secretKey);
        return hash_equals($expectedHash, $signature);
    }
    
    /**
     * API isteği gönder
     */
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: ShopierClient/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('CURL Error: ' . $error);
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode !== 200) {
            throw new Exception('HTTP Error: ' . $httpCode . ' - ' . ($result['message'] ?? 'Unknown error'));
        }
        
        return $result;
    }
    
    /**
     * Test bağlantısı
     */
    public function testConnection() {
        try {
            $data = [
                'api_key' => $this->apiKey,
                'timestamp' => time()
            ];
            
            $data['hash'] = $this->createHash($data);
            
            $result = $this->makeRequest('POST', '/test/connection', $data);
            return ['success' => true, 'message' => 'Bağlantı başarılı'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
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
     * Ödeme oluştur - Shopier API v4 formatında (1test.php formatı)
     */
    public function createPayment($orderData) {
        try {
            // 1test.php ile tam uyumlu format
            srand(time());
            $args = array(
                'API_key' => $this->apiKey,
                'website_index' => 1,
                'platform_order_id' => $orderData['order_id'],
                'product_name' => 'Prestashop Mutli Kur Modülü;',
                'product_type' => 1,
                'buyer_name' => 'Bera',
                'buyer_surname' => 'Ramazan',
                'buyer_email' => 'bera_ramazan@gmail.com',
                'buyer_account_age' => 578,
                'buyer_id_nr' => 3,
                'buyer_phone' => '05448412171',
                'billing_address' => 'Şabaniye',
                'billing_city' => 'Van',
                'billing_country' => 'Türkiye',
                'billing_postcode' => '65300',
                'shipping_address' => 'Şabaniye',
                'shipping_city' => 'Van',
                'shipping_country' => 'Türkiye',
                'shipping_postcode' => '65300',
                'total_order_value' => $this->convertToTRY($orderData['amount'], $orderData['currency'] ?? 'TRY'),
                'currency' => 0,
                'platform' => 2,
                'is_in_frame' => 0,
                'current_language' => 0,
                'random_nr' => rand(100000,999999)
            );
            
            // 1test.php ile aynı signature oluşturma
            $data = implode('', $args);
            $signature = $this->customHmac($args['random_nr'].$args['platform_order_id'].$args['total_order_value'].$args['currency'], $this->secretKey);
            $signature = base64_encode($signature);
            $args['signature'] = $signature;
            
            // Debug: Shopier'a gönderilecek verileri logla
            error_log('SHOPIER DEBUG - Payment Data: ' . json_encode($args));
            error_log('SHOPIER DEBUG - Signature String: ' . $args['random_nr'].$args['platform_order_id'].$args['total_order_value'].$args['currency']);
            error_log('SHOPIER DEBUG - Secret Key Length: ' . strlen($this->secretKey));
            
            // Test için Shopier'a POST isteği gönder ve yanıtı kontrol et
            $testResponse = $this->testShopierPayment($args);
            if ($testResponse && isset($testResponse['error'])) {
                error_log('SHOPIER ERROR: ' . json_encode($testResponse));
                return [
                    'success' => false,
                    'error' => $testResponse['error'],
                    'error_code' => $testResponse['error_code'] ?? 'unknown'
                ];
            }
            
            return [
                'success' => true,
                'payment_data' => $args,
                'payment_url' => 'https://www.shopier.com/ShowProduct/api_pay4.php'
            ];
        } catch (Exception $e) {
            error_log('SHOPIER EXCEPTION: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
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
     * Shopier için özel HMAC fonksiyonu - 1test.php ile aynı
     */
    private function customHmac($data, $key) {
        return @hash_hmac('SHA256', $data, $key, true);
    }
    
    /**
     * Para birimini TL'ye çevir
     */
    private function convertToTRY($amount, $currency) {
        // Veritabanından döviz kurlarını al
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=crypto_shop', 'root', '');
            $stmt = $pdo->prepare("SELECT usd_to_try_rate, eur_to_try_rate FROM crypto_settings WHERE id = 1");
            $stmt->execute();
            $rates = $stmt->fetch(PDO::FETCH_ASSOC);
            
            switch(strtoupper($currency)) {
                case 'USD':
                    return round($amount * ($rates['usd_to_try_rate'] ?? 30.0), 2);
                case 'EUR':
                    return round($amount * ($rates['eur_to_try_rate'] ?? 33.0), 2);
                case 'TRY':
                default:
                    return $amount;
            }
        } catch (Exception $e) {
            // Hata durumunda varsayılan kurları kullan
            switch(strtoupper($currency)) {
                case 'USD':
                    return round($amount * 30.0, 2);
                case 'EUR':
                    return round($amount * 33.0, 2);
                case 'TRY':
                default:
                    return $amount;
            }
        }
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
     * Shopier ödeme test et
     */
    private function testShopierPayment($args) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.shopier.com/ShowProduct/api_pay4.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($args));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        error_log('SHOPIER RESPONSE CODE: ' . $httpCode);
        error_log('SHOPIER RESPONSE BODY: ' . substr($response, 0, 1000));
        
        if ($error) {
            error_log('SHOPIER CURL ERROR: ' . $error);
            return ['error' => 'Connection error: ' . $error, 'error_code' => 'curl_error'];
        }
        
        // Shopier hata kodlarını kontrol et
        if (strpos($response, 'Hata kodu: 508') !== false) {
            return ['error' => 'Shopier Error 508: Invalid payment data', 'error_code' => '508'];
        }
        
        if (strpos($response, 'Hata kodu: 506') !== false) {
            return ['error' => 'Shopier Error 506: Currency not supported', 'error_code' => '506'];
        }
        
        if (strpos($response, 'Hata kodu:') !== false) {
            preg_match('/Hata kodu: (\d+)/', $response, $matches);
            $errorCode = $matches[1] ?? 'unknown';
            return ['error' => 'Shopier Error ' . $errorCode, 'error_code' => $errorCode];
        }
        
        return null; // No error
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
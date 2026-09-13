<?php
/**
 * Cryptomus API Client
 * Composer kullanmadan Cryptomus API entegrasyonu
 */

class CryptomusClient {
    private $merchantUuid;
    private $paymentKey;
    private $payoutKey;
    private $baseUrl = 'https://api.cryptomus.com/v1';
    private $testMode = false;
    
    public function __construct($merchantUuid, $paymentKey, $payoutKey = null, $testMode = false) {
        $this->merchantUuid = $merchantUuid;
        $this->paymentKey = $paymentKey;
        $this->payoutKey = $payoutKey;
        $this->testMode = $testMode;
        
        if ($testMode) {
            $this->baseUrl = 'https://api.cryptomus.com/v1'; // Test ve prod aynı endpoint
        }
    }
    
    /**
     * Ödeme servislerini getir
     */
    public function getServices() {
        return $this->makeRequest('POST', '/payment/services', [], 'payment');
    }
    
    /**
     * Ödeme oluştur
     */
    public function createPayment($data) {
        $requiredFields = ['amount', 'currency', 'order_id'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Required field missing: $field");
            }
        }
        
        return $this->makeRequest('POST', '/payment', $data, 'payment');
    }
    
    /**
     * Ödeme bilgilerini getir
     */
    public function getPaymentInfo($data) {
        if (!isset($data['uuid']) && !isset($data['order_id'])) {
            throw new Exception("Either 'uuid' or 'order_id' is required");
        }
        
        return $this->makeRequest('POST', '/payment/info', $data, 'payment');
    }
    
    /**
     * Ödeme geçmişini getir
     */
    public function getPaymentHistory($page = 1) {
        return $this->makeRequest('POST', '/payment/history', ['page' => $page], 'payment');
    }
    
    /**
     * Bakiye bilgilerini getir
     */
    public function getBalance() {
        return $this->makeRequest('POST', '/payment/balance', [], 'payment');
    }
    
    /**
     * Webhook bildirimini yeniden gönder
     */
    public function resendNotification($data) {
        if (!isset($data['uuid']) && !isset($data['order_id'])) {
            throw new Exception("Either 'uuid' or 'order_id' is required");
        }
        
        return $this->makeRequest('POST', '/payment/resend', $data, 'payment');
    }
    
    /**
     * Statik cüzdan oluştur
     */
    public function createWallet($data) {
        $requiredFields = ['network', 'currency', 'order_id'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Required field missing: $field");
            }
        }
        
        return $this->makeRequest('POST', '/payment/wallet', $data, 'payment');
    }
    
    /**
     * Payout oluştur (ödeme gönder)
     */
    public function createPayout($data) {
        if (!$this->payoutKey) {
            throw new Exception("Payout key is required for payout operations");
        }
        
        $requiredFields = ['amount', 'currency', 'network', 'order_id', 'address'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Required field missing: $field");
            }
        }
        
        return $this->makeRequest('POST', '/payout/create', $data, 'payout');
    }
    
    /**
     * Payout bilgilerini getir
     */
    public function getPayoutInfo($data) {
        if (!$this->payoutKey) {
            throw new Exception("Payout key is required for payout operations");
        }
        
        if (!isset($data['uuid']) && !isset($data['order_id'])) {
            throw new Exception("Either 'uuid' or 'order_id' is required");
        }
        
        return $this->makeRequest('POST', '/payout/info', $data, 'payout');
    }
    
    /**
     * API isteği gönder
     */
    private function makeRequest($method, $endpoint, $data = [], $keyType = 'payment') {
    $url = $this->baseUrl . $endpoint;
    $apiKey = ($keyType === 'payout') ? $this->payoutKey : $this->paymentKey;
    
    if (!$apiKey) {
        throw new Exception("API key is required for $keyType operations");
    }
    
    // Data'ya merchant UUID ekle
    $data['merchant'] = $this->merchantUuid;
    
    // JSON encode
    $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
    if ($jsonData === false) {
        $error = json_last_error_msg();
        error_log('JSON Encode Error: ' . $error);
        throw new Exception("Failed to encode data to JSON: " . $error);
    }
    
    // İmza oluştur
    $signature = $this->generateSignature($jsonData, $apiKey);
    
    // HTTP headers
    $headers = [
        'Content-Type: application/json',
        'merchant: ' . $this->merchantUuid,
        'sign: ' . $signature
    ];
    
    // Debug log
    error_log("Cryptomus Request URL: " . $url);
    error_log("Request Data: " . $jsonData);
    error_log("Generated Signature: " . $signature);
    
    // cURL isteği
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST', // Cryptomus sadece POST kabul eder
        CURLOPT_POSTFIELDS => $jsonData,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    // Debug log
    error_log("HTTP Code: " . $httpCode);
    error_log("Response: " . $response);
    error_log("cURL Error: " . $error);
    
    if ($response === false) {
        throw new Exception("cURL Error: $error");
    }
    
    $decodedResponse = json_decode($response, true);
    if ($decodedResponse === null) {
        throw new Exception("Invalid JSON response: $response");
    }
    
    return $decodedResponse;
}
    
    /**
     * API imzası oluştur
     */
  private function generateSignature($data, $apiKey) {
    // Cryptomus dokümantasyonuna göre güncellenmiş imza yöntemi
    $encodedData = base64_encode($data);
    return md5($encodedData . $apiKey);
}
    
    /**
     * Webhook imzasını doğrula
     */
    public function verifyWebhookSignature($data, $signature, $webhookSecret = null) {
        $secret = $webhookSecret ?: $this->paymentKey;
        $encodedData = base64_encode($data);
        $expectedSignature = md5($encodedData . $secret);
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * Test modu durumunu al
     */
    public function isTestMode() {
        return $this->testMode;
    }
    
    /**
     * Hata mesajlarını formatla
     */
    public function formatError($exception) {
        return [
            'success' => false,
            'error' => $exception->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

/**
 * Cryptomus Request Builder Exception
 */
class CryptomusRequestBuilderException extends Exception {
    private $method;
    
    public function __construct($message, $method = '', $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->method = $method;
    }
    
    public function getMethod() {
        return $this->method;
    }
}
?>

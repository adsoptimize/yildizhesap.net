<?php
/**
 * Cryptomus Payment Manager
 * Ödeme işlemlerini yöneten ana sınıf
 */

require_once 'config.php';
require_once 'CryptomusClient.php';

class CryptomusPaymentManager {
    private $db;
    private $client;
    private $settings;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->loadSettings();
        $this->initializeClient();
    }
    
    /**
     * Ayarları yükle
     */
    private function loadSettings() {
        $stmt = $this->db->prepare("SELECT setting_key, setting_value, is_encrypted FROM crypto_settings");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        
        $this->settings = [];
        foreach ($rows as $row) {
            $value = $row['setting_value'];
            
            // Şifreli değerleri çöz (basit XOR encryption)
            if ($row['is_encrypted'] && !empty($value)) {
                $value = $this->decrypt($value);
            }
            
            $this->settings[$row['setting_key']] = $value;
        }
    }
    
    /**
     * Cryptomus client'ı başlat
     */
    private function initializeClient() {
        $merchantUuid = $this->settings['cryptomus_merchant_uuid'] ?? '';
        $paymentKey = $this->settings['cryptomus_payment_key'] ?? '';
        $payoutKey = $this->settings['cryptomus_payout_key'] ?? '';
        $testMode = ($this->settings['cryptomus_test_mode'] ?? '1') === '1';
        
        // UTF-8 karakterleri temizle
        $merchantUuid = $this->cleanUtf8String($merchantUuid);
        $paymentKey = $this->cleanUtf8String($paymentKey);
        $payoutKey = $this->cleanUtf8String($payoutKey);
        
        if (empty($merchantUuid) || empty($paymentKey)) {
            throw new Exception('Cryptomus API ayarları eksik. Lütfen admin panelinden ayarları yapın.');
        }
        
        $this->client = new CryptomusClient($merchantUuid, $paymentKey, $payoutKey, $testMode);
    }
    
    /**
     * Ödeme oluştur
     */
    public function createPayment($userId, $items, $options = []) {
        try {
            $this->db->beginTransaction();
            
            // Toplam tutarı hesapla
            $totalAmount = 0;
            $validatedItems = [];
            
            foreach ($items as $item) {
                // Hesap bilgilerini kontrol et
                $account = $this->getAccount($item['account_id']);
                if (!$account) {
                    throw new Exception("Hesap bulunamadı: {$item['account_id']}");
                }
                
                if ($account['stock_quantity'] < $item['quantity']) {
                    throw new Exception("Yetersiz stok: {$account['title']}");
                }
                
                $itemTotal = $account['price'] * $item['quantity'];
                $totalAmount += $itemTotal;
                
                $validatedItems[] = [
                    'account_id' => $account['id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $account['price'],
                    'total_price' => $itemTotal,
                    'title' => $account['title']
                ];
            }
            
            // Benzersiz order ID oluştur
            $orderId = 'order_' . time() . '_' . $userId . '_' . uniqid();
            
            // Veritabanına ödeme kaydı oluştur
            $paymentId = $this->createPaymentRecord($userId, $orderId, $totalAmount, $validatedItems);
            
            // Cryptomus'a ödeme isteği gönder
            $currency = $this->settings['default_currency'] ?? 'USD';
            $network = $options['network'] ?? 'TRON';
            $toCurrency = $options['to_currency'] ?? 'USDT';
            
            $paymentData = [
                'amount' => number_format($totalAmount, 2, '.', ''),
                'currency' => $currency,
                'network' => $network,
                'order_id' => $orderId,
                'url_return' => SITE_URL . 'payment_return.php?order_id=' . $orderId,
                'url_callback' => SITE_URL . 'cryptomus_webhook.php',
                'is_payment_multiple' => false,
                'lifetime' => 7200, // 2 saat
                'to_currency' => $toCurrency
            ];
            
            // UTF-8 karakterleri temizle
            $paymentData = $this->cleanUtf8Data($paymentData);
            
            $response = $this->client->createPayment($paymentData);
            
            // Cryptomus yanıtını veritabanında güncelle
            $this->updatePaymentRecord($paymentId, [
                'cryptomus_uuid' => $response['uuid'] ?? null,
                'network' => $response['network'] ?? $network,
                'to_currency' => $toCurrency,
                'address' => $response['address'] ?? null,
                'payment_url' => $response['url'] ?? null,
                'payment_status' => $response['payment_status'] ?? 'check',
                'expired_at' => isset($response['expired_at']) ? date('Y-m-d H:i:s', $response['expired_at']) : null
            ]);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'payment_id' => $paymentId,
                'order_id' => $orderId,
                'payment_url' => $response['url'] ?? null,
                'amount' => $totalAmount,
                'currency' => $currency,
                'to_currency' => $toCurrency,
                'address' => $response['address'] ?? null,
                'expired_at' => $response['expired_at'] ?? null,
                'cryptomus_uuid' => $response['uuid'] ?? null
            ];
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            
            error_log('Cryptomus Payment Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Ödeme durumunu kontrol et
     */
    public function checkPaymentStatus($orderId) {
        try {
            $payment = $this->getPaymentByOrderId($orderId);
            if (!$payment) {
                throw new Exception('Ödeme bulunamadı');
            }
            
            // Cryptomus'dan güncel durumu al
            $response = $this->client->getPaymentInfo(['order_id' => $orderId]);
            
            // Veritabanını güncelle
            $updateData = [
                'payment_status' => $response['payment_status'] ?? $payment['payment_status'],
                'status' => $this->mapCryptomusStatus($response['status'] ?? $payment['status']),
                'payment_amount' => $response['payment_amount'] ?? $payment['payment_amount'],
                'txid' => $response['txid'] ?? $payment['txid']
            ];
            
            $this->updatePaymentRecord($payment['id'], $updateData);
            
            // Ödeme tamamlandıysa hesapları kullanıcıya aktar
            if ($updateData['status'] === 'paid' && $payment['status'] !== 'paid') {
                $this->processSuccessfulPayment($payment['id']);
            }
            
            return [
                'success' => true,
                'status' => $updateData['status'],
                'payment_status' => $updateData['payment_status'],
                'payment_amount' => $updateData['payment_amount'],
                'txid' => $updateData['txid']
            ];
            
        } catch (Exception $e) {
            error_log('Payment Status Check Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Webhook işle
     */
    public function processWebhook($webhookData, $signature) {
        try {
            // İmzayı doğrula
            $webhookSecret = $this->settings['cryptomus_webhook_secret'] ?? '';
            
            // Webhook verilerini JSON string olarak hazırla
            $jsonData = json_encode($webhookData, JSON_UNESCAPED_UNICODE);
            
            if (!$this->client->verifyWebhookSignature($jsonData, $signature, $webhookSecret)) {
                throw new Exception('Invalid webhook signature');
            }
            
            // Webhook'u logla
            $this->logWebhook($webhookData, $signature, 'valid');
            
            $orderId = $webhookData['order_id'] ?? null;
            if (!$orderId) {
                throw new Exception('Missing order_id in webhook');
            }
            
            $payment = $this->getPaymentByOrderId($orderId);
            if (!$payment) {
                throw new Exception('Payment not found');
            }
            
            // Ödeme durumunu güncelle
            $status = $this->mapCryptomusStatus($webhookData['status'] ?? 'pending');
            $updateData = [
                'status' => $status,
                'payment_status' => $webhookData['payment_status'] ?? $payment['payment_status'],
                'payment_amount' => $webhookData['payment_amount'] ?? $payment['payment_amount'],
                'txid' => $webhookData['txid'] ?? $payment['txid']
            ];
            
            $this->updatePaymentRecord($payment['id'], $updateData);
            
            // Ödeme başarılıysa hesapları aktar
            if ($status === 'paid' && $payment['status'] !== 'paid') {
                $this->processSuccessfulPayment($payment['id']);
            }
            
            $this->updateWebhookLog($webhookData['order_id'], 'processed');
            
            return ['success' => true];
            
        } catch (Exception $e) {
            error_log('Webhook Processing Error: ' . $e->getMessage());
            
            if (isset($webhookData['order_id'])) {
                $this->logWebhook($webhookData, $signature, 'error');
            }
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Başarılı ödeme işlemi
     */
    private function processSuccessfulPayment($paymentId) {
        try {
            $this->db->beginTransaction();
            
            // Ödeme detaylarını al
            $payment = $this->getPaymentById($paymentId);
            $items = $this->getPaymentItems($paymentId);
            
            // StockManager'ı başlat
            require_once 'StockManager.php';
            $stockManager = new StockManager();
            
            foreach ($items as $item) {
                // Hesapları rezerve et ve kullanıcıya ata
                $reservedAccounts = $stockManager->reserveAccounts(
                    $item['account_id'], 
                    $item['quantity'], 
                    $payment['user_id'], 
                    $payment['order_id']
                );
                
                if (count($reservedAccounts) < $item['quantity']) {
                    throw new Exception('Yeterli stok bulunamadı: ' . $item['account_id']);
                }
                
                // Kullanıcının siparişlerine ekle (orders tablosuna)
                $this->addToUserOrders($payment['user_id'], $item, $payment, $reservedAccounts);
            }
            
            $this->db->commit();
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            throw $e;
        }
    }
    
    /**
     * Hesap bilgilerini al
     */
    private function getAccount($accountId) {
        $stmt = $this->db->prepare("SELECT * FROM accounts WHERE id = ? AND status = 'active'");
        $stmt->execute([$accountId]);
        return $stmt->fetch();
    }
    
    /**
     * Ödeme kaydı oluştur
     */
    private function createPaymentRecord($userId, $orderId, $amount, $items) {
        // Ana ödeme kaydı
        $stmt = $this->db->prepare("
            INSERT INTO crypto_payments (user_id, order_id, amount, currency, status, created_at) 
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$userId, $orderId, $amount, $this->settings['default_currency'] ?? 'USD']);
        
        $paymentId = $this->db->lastInsertId();
        
        // Ödeme öğelerini kaydet
        $stmt = $this->db->prepare("
            INSERT INTO payment_items (payment_id, account_id, quantity, unit_price, total_price) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($items as $item) {
            $stmt->execute([
                $paymentId,
                $item['account_id'],
                $item['quantity'],
                $item['unit_price'],
                $item['total_price']
            ]);
        }
        
        return $paymentId;
    }
    
    /**
     * Ödeme kaydını güncelle
     */
    private function updatePaymentRecord($paymentId, $data) {
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        
        $values[] = $paymentId;
        
        $sql = "UPDATE crypto_payments SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
    }
    
    /**
     * Order ID ile ödeme al
     */
    private function getPaymentByOrderId($orderId) {
        $stmt = $this->db->prepare("SELECT * FROM crypto_payments WHERE order_id = ?");
        $stmt->execute([$orderId]);
        return $stmt->fetch();
    }
    
    /**
     * Payment ID ile ödeme al
     */
    private function getPaymentById($paymentId) {
        $stmt = $this->db->prepare("SELECT * FROM crypto_payments WHERE id = ?");
        $stmt->execute([$paymentId]);
        return $stmt->fetch();
    }
    
    /**
     * Ödeme öğelerini al
     */
    private function getPaymentItems($paymentId) {
        $stmt = $this->db->prepare("SELECT * FROM payment_items WHERE payment_id = ?");
        $stmt->execute([$paymentId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Cryptomus durumunu sistem durumuna çevir
     */
    private function mapCryptomusStatus($cryptomusStatus) {
        $statusMap = [
            'check' => 'pending',
            'process' => 'pending', 
            'paid' => 'paid',
            'fail' => 'failed',
            'wrong_amount' => 'failed',
            'cancel' => 'cancelled',
            'expired' => 'expired'
        ];
        
        return $statusMap[$cryptomusStatus] ?? 'pending';
    }
    
    /**
     * Hesap stokunu güncelle
     */
    private function updateAccountStock($accountId, $quantity) {
        $stmt = $this->db->prepare("UPDATE accounts SET stock_quantity = stock_quantity - ?, sales_count = sales_count + ? WHERE id = ?");
        $stmt->execute([$quantity, $quantity, $accountId]);
    }
    
    /**
     * Kullanıcı siparişlerine ekle
     */
    private function addToUserOrders($userId, $item, $payment, $reservedAccounts = []) {
        // Her rezerve edilen hesap için ayrı sipariş kaydı oluştur
        if (!empty($reservedAccounts)) {
            foreach ($reservedAccounts as $account) {
                $stmt = $this->db->prepare("
                    INSERT INTO orders (user_id, product_name, category, quantity, unit_price, total_price, status, delivery_status, account_data, created_at) 
                    VALUES (?, ?, 'Social Media Account', 1, ?, ?, 'completed', 'delivered', ?, NOW())
                ");
                $stmt->execute([
                    $userId,
                    "Account #" . $item['account_id'],
                    $item['unit_price'],
                    $item['unit_price'],
                    $account['account_data']
                ]);
            }
        } else {
            // Eski yöntem (geriye dönük uyumluluk için)
            $stmt = $this->db->prepare("
                INSERT INTO orders (user_id, product_name, category, quantity, unit_price, total_price, status, delivery_status, created_at) 
                VALUES (?, ?, 'Social Media Account', ?, ?, ?, 'completed', 'delivered', NOW())
            ");
            $stmt->execute([
                $userId,
                "Account #" . $item['account_id'],
                $item['quantity'],
                $item['unit_price'],
                $item['total_price']
            ]);
        }
    }
    
    /**
     * Webhook logla
     */
    private function logWebhook($webhookData, $signature, $status) {
        $stmt = $this->db->prepare("
            INSERT INTO crypto_webhook_logs (order_id, cryptomus_uuid, webhook_data, signature, status, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $webhookData['order_id'] ?? null,
            $webhookData['uuid'] ?? null,
            json_encode($webhookData),
            $signature,
            $status,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }
    
    /**
     * Webhook log güncelle
     */
    private function updateWebhookLog($orderId, $status) {
        $stmt = $this->db->prepare("UPDATE crypto_webhook_logs SET status = ?, processed_at = NOW() WHERE order_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$status, $orderId]);
    }
    
    /**
     * Desteklenen ağları al
     */
    public function getSupportedNetworks() {
        $networks = $this->settings['supported_networks'] ?? 'BTC,ETH,TRON,LTC';
        return explode(',', $networks);
    }
    
    /**
     * Kullanıcının ödemelerini al
     */
    public function getUserPayments($userId, $limit = 10, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT cp.*, COUNT(pi.id) as item_count 
            FROM crypto_payments cp 
            LEFT JOIN payment_items pi ON cp.id = pi.payment_id 
            WHERE cp.user_id = ? 
            GROUP BY cp.id 
            ORDER BY cp.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    /**
     * Basit şifreleme
     */
    private function encrypt($data) {
        $key = 'crypto_secret_key_2024';
        return base64_encode($data . '|' . $key); // Basit concatenation
    }
    
    /**
     * Basit şifre çözme
     */
    private function decrypt($data) {
        $decoded = base64_decode($data);
        $key = 'crypto_secret_key_2024';
        $parts = explode('|', $decoded);
        return isset($parts[0]) ? $parts[0] : '';
    }
    
    /**
     * Ayar güncelle
     */
    public function updateSetting($key, $value, $isEncrypted = false) {
        if ($isEncrypted) {
            $value = $this->encrypt($value);
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO crypto_settings (setting_key, setting_value, is_encrypted) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?, is_encrypted = ?
        ");
        $stmt->execute([$key, $value, $isEncrypted ? 1 : 0, $value, $isEncrypted ? 1 : 0]);
        
        // Ayarları yeniden yükle
        $this->loadSettings();
        $this->initializeClient();
    }
    
    /**
     * Cryptomus aktif mi?
     */
    public function isEnabled() {
        return ($this->settings['cryptomus_enabled'] ?? '0') === '1';
    }
    
    /**
     * UTF-8 karakterleri temizle
     */
    private function cleanUtf8Data($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->cleanUtf8Data($value);
            }
            return $data;
        }
        
        if (is_string($data)) {
            return $this->cleanUtf8String($data);
        }
        
        return $data;
    }
    
    /**
     * String için UTF-8 temizliği
     */
    private function cleanUtf8String($data) {
        if (!is_string($data)) {
            return $data;
        }
        
        // Sadece ASCII karakterleri ve temel UTF-8 karakterleri bırak
        $data = preg_replace('/[^\x20-\x7E]/', '', $data);
        
        // Null byte'ları temizle
        $data = str_replace("\0", '', $data);
        
        // Kontrol karakterlerini temizle
        $data = preg_replace('/[\x00-\x1F\x7F]/', '', $data);
        
        return trim($data);
    }
}
?>

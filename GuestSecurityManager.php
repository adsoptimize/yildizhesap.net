<?php
/**
 * Ziyaretçi Güvenlik Yöneticisi
 * Ziyaretçi satın alma ve sipariş takip işlemlerini güvenli hale getirir
 */
class GuestSecurityManager {
    private $pdo;
    private $ipAddress;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->ipAddress = $this->getClientIP();
    }
    
    /**
     * Ziyaretçi satın alma işlemini kontrol et
     */
    public function checkPurchaseAllowed($email) {
        // Rate limiting kontrolü
        if (!$this->checkRateLimit('purchase')) {
            return [
                'allowed' => false,
                'message' => 'Çok fazla deneme yaptınız. Lütfen daha sonra tekrar deneyin.',
                'wait_time' => $this->getRemainingBlockTime('purchase')
            ];
        }
        
        // E-posta spam kontrolü
        if (!$this->checkEmailSpam($email)) {
            return [
                'allowed' => false,
                'message' => 'Bu e-posta adresi ile çok fazla sipariş verildi. Lütfen farklı bir e-posta kullanın.'
            ];
        }
        
        return ['allowed' => true];
    }
    
    /**
     * Ziyaretçi sipariş takip işlemini kontrol et
     */
    public function checkTrackAllowed() {
        // Rate limiting kontrolü
        if (!$this->checkRateLimit('track')) {
            return [
                'allowed' => false,
                'message' => 'Çok fazla sorgulama yaptınız. Lütfen daha sonra tekrar deneyin.',
                'wait_time' => $this->getRemainingBlockTime('track')
            ];
        }
        
        return ['allowed' => true];
    }
    
    /**
     * Rate limiting kontrolü
     */
    private function checkRateLimit($actionType) {
        $rateLimit = $this->getRateLimit($actionType);
        $blockDuration = $this->getBlockDuration($actionType);
        
        // Mevcut kaydı kontrol et
        $stmt = $this->pdo->prepare("
            SELECT * FROM guest_rate_limits 
            WHERE ip_address = ? AND action_type = ?
        ");
        $stmt->execute([$this->ipAddress, $actionType]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $now = new DateTime();
        
        if ($record) {
            $lastAttempt = new DateTime($record['last_attempt']);
            $timeDiff = $now->getTimestamp() - $lastAttempt->getTimestamp();
            
            // Blok süresi kontrolü
            if ($record['is_blocked'] && $record['block_until']) {
                $blockUntil = new DateTime($record['block_until']);
                if ($now < $blockUntil) {
                    return false;
                } else {
                    // Blok süresi geçmiş, sıfırla
                    $this->resetRateLimit($actionType);
                    return true;
                }
            }
            
            // 1 saat içinde limit kontrolü
            if ($timeDiff < 3600) {
                if ($record['attempt_count'] >= $rateLimit) {
                    // Limit aşıldı, blokla
                    $this->blockIP($actionType, $blockDuration);
                    return false;
                } else {
                    // Deneme sayısını artır
                    $this->incrementAttempt($actionType);
                    return true;
                }
            } else {
                // 1 saat geçmiş, sıfırla
                $this->resetRateLimit($actionType);
                return true;
            }
        } else {
            // İlk deneme
            $this->createRateLimitRecord($actionType);
            return true;
        }
    }
    
    /**
     * E-posta spam kontrolü
     */
    private function checkEmailSpam($email) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as order_count 
            FROM orders 
            WHERE email = ? AND is_guest_order = 1 AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 24 saat içinde maksimum 5 sipariş
        return $result['order_count'] < 5;
    }
    
    /**
     * Rate limit kaydı oluştur
     */
    private function createRateLimitRecord($actionType) {
        $stmt = $this->pdo->prepare("
            INSERT INTO guest_rate_limits (ip_address, action_type, attempt_count, last_attempt)
            VALUES (?, ?, 1, NOW())
        ");
        $stmt->execute([$this->ipAddress, $actionType]);
    }
    
    /**
     * Deneme sayısını artır
     */
    private function incrementAttempt($actionType) {
        $stmt = $this->pdo->prepare("
            UPDATE guest_rate_limits 
            SET attempt_count = attempt_count + 1, last_attempt = NOW()
            WHERE ip_address = ? AND action_type = ?
        ");
        $stmt->execute([$this->ipAddress, $actionType]);
    }
    
    /**
     * IP'yi blokla
     */
    private function blockIP($actionType, $duration) {
        $blockUntil = date('Y-m-d H:i:s', time() + $duration);
        $stmt = $this->pdo->prepare("
            UPDATE guest_rate_limits 
            SET is_blocked = 1, block_until = ?
            WHERE ip_address = ? AND action_type = ?
        ");
        $stmt->execute([$blockUntil, $this->ipAddress, $actionType]);
    }
    
    /**
     * Rate limit'i sıfırla
     */
    private function resetRateLimit($actionType) {
        $stmt = $this->pdo->prepare("
            UPDATE guest_rate_limits 
            SET attempt_count = 1, last_attempt = NOW(), is_blocked = 0, block_until = NULL
            WHERE ip_address = ? AND action_type = ?
        ");
        $stmt->execute([$this->ipAddress, $actionType]);
    }
    
    /**
     * Kalan blok süresini al
     */
    private function getRemainingBlockTime($actionType) {
        $stmt = $this->pdo->prepare("
            SELECT block_until FROM guest_rate_limits 
            WHERE ip_address = ? AND action_type = ? AND is_blocked = 1
        ");
        $stmt->execute([$this->ipAddress, $actionType]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($record && $record['block_until']) {
            $blockUntil = new DateTime($record['block_until']);
            $now = new DateTime();
            $diff = $blockUntil->getTimestamp() - $now->getTimestamp();
            return max(0, $diff);
        }
        
        return 0;
    }
    
    /**
     * Rate limit değerini al
     */
    private function getRateLimit($actionType) {
        $key = 'guest_' . $actionType . '_rate_limit';
        $stmt = $this->pdo->prepare("
            SELECT setting_value FROM site_settings WHERE setting_key = ?
        ");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? intval($result['setting_value']) : 10;
    }
    
    /**
     * Blok süresini al
     */
    private function getBlockDuration($actionType) {
        $key = 'guest_' . $actionType . '_block_duration';
        $stmt = $this->pdo->prepare("
            SELECT setting_value FROM site_settings WHERE setting_key = ?
        ");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? intval($result['setting_value']) : 3600;
    }
    
    /**
     * Client IP adresini al
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Güvenlik logu kaydet
     */
    public function logSecurityEvent($action, $details = []) {
        $logData = [
            'ip_address' => $this->ipAddress,
            'action' => $action,
            'details' => json_encode($details),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Log dosyasına kaydet
        $logEntry = sprintf(
            "[%s] IP: %s | Action: %s | Details: %s | UA: %s\n",
            $logData['timestamp'],
            $logData['ip_address'],
            $logData['action'],
            $logData['details'],
            $logData['user_agent']
        );
        
        file_put_contents('logs/guest_security.log', $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Ziyaretçi satın alma özelliği aktif mi?
     */
    public function isGuestPurchaseEnabled() {
        $stmt = $this->pdo->prepare("
            SELECT setting_value FROM site_settings WHERE setting_key = 'guest_purchase_enabled'
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result && $result['setting_value'] == '1';
    }
    
    /**
     * Güvenlik istatistiklerini al
     */
    public function getSecurityStats() {
        $stats = [];
        
        // Toplam bloklu IP sayısı
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as blocked_count FROM guest_rate_limits WHERE is_blocked = 1
        ");
        $stmt->execute();
        $stats['blocked_ips'] = $stmt->fetch(PDO::FETCH_ASSOC)['blocked_count'];
        
        // Son 24 saatteki deneme sayısı
        $stmt = $this->pdo->prepare("
            SELECT action_type, SUM(attempt_count) as total_attempts 
            FROM guest_rate_limits 
            WHERE last_attempt > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY action_type
        ");
        $stmt->execute();
        $stats['recent_attempts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
}
?> 
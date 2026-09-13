<?php
/**
 * Database Session Manager
 * Session token'ları veritabanında yönetir ve uzaktan invalidation sağlar
 */

class DatabaseSessionManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->createSessionTable();
    }
    
    /**
     * Session tablosunu oluştur
     */
    private function createSessionTable() {
        $sql = "CREATE TABLE IF NOT EXISTS active_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_token VARCHAR(128) UNIQUE NOT NULL,
            user_id INT NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT NOT NULL,
            device_fingerprint VARCHAR(64) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            expires_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP + INTERVAL 2 HOUR),
            is_active BOOLEAN DEFAULT TRUE,
            invalidated_by ENUM('user', 'admin', 'security', 'expired') NULL,
            invalidated_at TIMESTAMP NULL,
            login_location VARCHAR(100) NULL,
            device_info JSON NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_session_token (session_token),
            INDEX idx_expires (expires_at),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->pdo->exec($sql);
    }
    
    /**
     * Yeni session token oluştur ve kaydet
     */
    public function createSession($userId, $sessionToken = null) {
        if (!$sessionToken) {
            $sessionToken = bin2hex(random_bytes(64));
        }
        
        $ipAddress = SessionSecurity::getRealIpAddress();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $fingerprint = hash('sha256', $ipAddress . $userAgent . time());
        
        // Cihaz bilgilerini JSON olarak kaydet
        $deviceInfo = [
            'browser' => $this->getBrowserInfo($userAgent),
            'os' => $this->getOSInfo($userAgent),
            'screen_resolution' => $_SERVER['HTTP_SEC_CH_VIEWPORT_WIDTH'] ?? null,
            'timezone' => $_SERVER['HTTP_SEC_CH_UA_TIMEZONE'] ?? null,
            'language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null
        ];
        
        // Lokasyon bilgisi (IP-based)
        $location = $this->getLocationFromIP($ipAddress);
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO active_sessions 
                (session_token, user_id, ip_address, user_agent, device_fingerprint, login_location, device_info) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $sessionToken,
                $userId,
                $ipAddress,
                $userAgent,
                $fingerprint,
                $location,
                json_encode($deviceInfo)
            ]);
            
            return $sessionToken;
            
        } catch (PDOException $e) {
            error_log("Session creation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Session token'ı doğrula
     */
    public function validateSession($sessionToken) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.*, u.first_name, u.last_name, u.email, u.is_admin 
                FROM active_sessions s
                JOIN users u ON s.user_id = u.id 
                WHERE s.session_token = ? 
                AND s.is_active = TRUE 
                AND s.expires_at > NOW()
            ");
            
            $stmt->execute([$sessionToken]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$session) {
                return ['valid' => false, 'reason' => 'Session not found or expired'];
            }
            
            // Güvenlik kontrolleri (geçici olarak devre dışı)
            $currentIP = SessionSecurity::getRealIpAddress();
            $currentUA = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // IP değişimi kontrolü (geçici olarak devre dışı)
            /*
            if ($session['ip_address'] !== $currentIP) {
                $this->invalidateSession($sessionToken, 'security', 'IP address change detected');
                error_log("SECURITY ALERT: IP change for session {$sessionToken}. Original: {$session['ip_address']}, Current: {$currentIP}");
                return ['valid' => false, 'reason' => 'IP address changed'];
            }
            */
            
            // User Agent değişimi kontrolü (geçici olarak devre dışı)
            /*
            if ($session['user_agent'] !== $currentUA) {
                $this->invalidateSession($sessionToken, 'security', 'User agent change detected');
                error_log("SECURITY ALERT: User agent change for session {$sessionToken}");
                return ['valid' => false, 'reason' => 'Device fingerprint changed'];
            }
            */
            
            // Last activity'yi güncelle
            $this->updateLastActivity($sessionToken);
            
            return [
                'valid' => true,
                'user' => [
                    'id' => $session['user_id'],
                    'first_name' => $session['first_name'],
                    'last_name' => $session['last_name'],
                    'email' => $session['email'],
                    'is_admin' => $session['is_admin']
                ],
                'session_info' => [
                    'created_at' => $session['created_at'],
                    'last_activity' => $session['last_activity'],
                    'ip_address' => $session['ip_address'],
                    'device_info' => json_decode($session['device_info'], true)
                ]
            ];
            
        } catch (PDOException $e) {
            error_log("Session validation failed: " . $e->getMessage());
            return ['valid' => false, 'reason' => 'Database error'];
        }
    }
    
    /**
     * Session'ı geçersiz kıl
     */
    public function invalidateSession($sessionToken, $invalidatedBy = 'user', $reason = null) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE active_sessions 
                SET is_active = FALSE, 
                    invalidated_by = ?, 
                    invalidated_at = NOW() 
                WHERE session_token = ?
            ");
            
            $result = $stmt->execute([$invalidatedBy, $sessionToken]);
            
            if ($reason) {
                error_log("Session invalidated: {$sessionToken} by {$invalidatedBy}. Reason: {$reason}");
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Session invalidation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Kullanıcının tüm session'larını geçersiz kıl
     */
    public function invalidateAllUserSessions($userId, $exceptToken = null) {
        try {
            $sql = "UPDATE active_sessions 
                    SET is_active = FALSE, 
                        invalidated_by = 'user', 
                        invalidated_at = NOW() 
                    WHERE user_id = ? AND is_active = TRUE";
            
            $params = [$userId];
            
            if ($exceptToken) {
                $sql .= " AND session_token != ?";
                $params[] = $exceptToken;
            }
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
            
        } catch (PDOException $e) {
            error_log("Bulk session invalidation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Kullanıcının aktif session'larını listele
     */
    public function getUserActiveSessions($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT session_token, ip_address, device_info, created_at, last_activity, login_location
                FROM active_sessions 
                WHERE user_id = ? AND is_active = TRUE AND expires_at > NOW()
                ORDER BY last_activity DESC
            ");
            
            $stmt->execute([$userId]);
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Device info'yu decode et
            foreach ($sessions as &$session) {
                $session['device_info'] = json_decode($session['device_info'], true);
                $session['session_token_short'] = substr($session['session_token'], 0, 16) . '...';
            }
            
            return $sessions;
            
        } catch (PDOException $e) {
            error_log("Get user sessions failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Last activity'yi güncelle
     */
    private function updateLastActivity($sessionToken) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE active_sessions 
                SET last_activity = NOW() 
                WHERE session_token = ?
            ");
            
            return $stmt->execute([$sessionToken]);
            
        } catch (PDOException $e) {
            error_log("Update last activity failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Süresi dolan session'ları temizle
     */
    public function cleanExpiredSessions() {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM active_sessions 
                WHERE expires_at < NOW() OR (invalidated_at < NOW() - INTERVAL 30 DAY)
            ");
            
            $stmt->execute();
            return $stmt->rowCount();
            
        } catch (PDOException $e) {
            error_log("Clean expired sessions failed: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * User Agent'dan tarayıcı bilgisi çıkar
     */
    private function getBrowserInfo($userAgent) {
        if (strpos($userAgent, 'Chrome') !== false) {
            return 'Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            return 'Edge';
        } elseif (strpos($userAgent, 'Opera') !== false) {
            return 'Opera';
        }
        
        return 'Unknown';
    }
    
    /**
     * User Agent'dan OS bilgisi çıkar
     */
    private function getOSInfo($userAgent) {
        if (strpos($userAgent, 'Windows') !== false) {
            return 'Windows';
        } elseif (strpos($userAgent, 'Mac') !== false) {
            return 'macOS';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            return 'Linux';
        } elseif (strpos($userAgent, 'Android') !== false) {
            return 'Android';
        } elseif (strpos($userAgent, 'iOS') !== false) {
            return 'iOS';
        }
        
        return 'Unknown';
    }
    
    /**
     * IP'den lokasyon bilgisi al (basit implementasyon)
     */
    private function getLocationFromIP($ip) {
        // Gerçek uygulamada GeoIP servisi kullanılabilir
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return 'Localhost';
        }
        
        // Basit country detection (production'da gerçek GeoIP servisi kullanın)
        return 'Unknown Location';
    }
    
    /**
     * Session güvenlik raporu
     */
    public function getSecurityReport($days = 7) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_sessions,
                    COUNT(CASE WHEN invalidated_by = 'security' THEN 1 END) as security_violations,
                    COUNT(CASE WHEN is_active = TRUE THEN 1 END) as active_sessions,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT ip_address) as unique_ips
                FROM active_sessions 
                WHERE created_at > NOW() - INTERVAL ? DAY
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Security report failed: " . $e->getMessage());
            return null;
        }
    }
}
?>

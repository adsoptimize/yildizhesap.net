<?php
class IPBanManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * IP adresinin banlanıp banlanmadığını kontrol eder
     */
    public function isIPBanned($ipAddress) {
        try {
            // Aktif banları kontrol et
            $stmt = $this->pdo->prepare("
                SELECT id, reason, ban_type, banned_until, created_at 
                FROM ip_bans 
                WHERE ip_address = ? 
                AND is_active = 1 
                AND (ban_type = 'permanent' OR banned_until > NOW())
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$ipAddress]);
            
            $ban = $stmt->fetch();
            
            if ($ban) {
                // Geçici ban süresi dolmuş mu kontrol et
                if ($ban['ban_type'] === 'temporary' && $ban['banned_until'] && strtotime($ban['banned_until']) <= time()) {
                    // Ban süresini pasif yap
                    $this->deactivateBan($ban['id']);
                    return false;
                }
                return $ban;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("IP ban check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * IP adresini banlar
     */
    public function banIP($ipAddress, $reason, $bannedBy, $banType = 'temporary', $duration = null) {
        try {
            $bannedUntil = null;
            
            if ($banType === 'temporary' && $duration) {
                $bannedUntil = date('Y-m-d H:i:s', strtotime("+{$duration}"));
            }
            
            // Varolan aktif banları pasif yap
            $stmt = $this->pdo->prepare("
                UPDATE ip_bans 
                SET is_active = 0, updated_at = NOW() 
                WHERE ip_address = ? AND is_active = 1
            ");
            $stmt->execute([$ipAddress]);
            
            // Yeni ban ekle
            $stmt = $this->pdo->prepare("
                INSERT INTO ip_bans (ip_address, reason, ban_type, banned_until, banned_by) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$ipAddress, $reason, $banType, $bannedUntil, $bannedBy]);
            
            // O IP'den olan tüm kullanıcıların oturumlarını sonlandır
            $this->terminateIPSessions($ipAddress);
            
            return [
                'success' => true,
                'message' => 'IP adresi başarıyla banlandı.',
                'ban_id' => $this->pdo->lastInsertId()
            ];
            
        } catch (Exception $e) {
            error_log("IP ban error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ban işlemi sırasında hata oluştu: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * IP ban'ını kaldırır
     */
    public function unbanIP($ipAddress, $removedBy) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE ip_bans 
                SET is_active = 0, updated_at = NOW() 
                WHERE ip_address = ? AND is_active = 1
            ");
            $stmt->execute([$ipAddress]);
            
            return [
                'success' => true,
                'message' => 'IP ban kaldırıldı.'
            ];
            
        } catch (Exception $e) {
            error_log("IP unban error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ban kaldırma işlemi sırasında hata oluştu.'
            ];
        }
    }
    
    /**
     * Ban'ı deaktif eder
     */
    private function deactivateBan($banId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE ip_bans 
                SET is_active = 0, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$banId]);
        } catch (Exception $e) {
            error_log("Ban deactivation error: " . $e->getMessage());
        }
    }
    
    /**
     * IP'den olan tüm kullanıcı oturumlarını sonlandırır
     */
    private function terminateIPSessions($ipAddress) {
        try {
            // Veritabanından IP'ye ait kullanıcıları bulup session'larını temizle
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE registration_ip = ?");
            $stmt->execute([$ipAddress]);
            $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($users)) {
                $placeholders = str_repeat('?,', count($users) - 1) . '?';
                $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE user_id IN ($placeholders)");
                $stmt->execute($users);
            }
            
            // Alternatif olarak session dosyalarını temizleme (izin sorunlarını önlemek için daha güvenli)
            $sessionPath = session_save_path() ?: sys_get_temp_dir();
            
            if (is_dir($sessionPath) && is_readable($sessionPath)) {
                $sessions = glob($sessionPath . '/sess_*');
                if ($sessions) {
                    foreach ($sessions as $sessionFile) {
                        if (is_readable($sessionFile) && is_writable($sessionFile)) {
                            $content = @file_get_contents($sessionFile);
                            if ($content !== false && strpos($content, $ipAddress) !== false) {
                                @unlink($sessionFile);
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Session termination error: " . $e->getMessage());
        }
    }
    
    /**
     * IP'nin ban bilgisini alır
     */
    public function getBanInfo($ipAddress) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT ib.*, u.username as banned_by_username
                FROM ip_bans ib
                LEFT JOIN users u ON ib.banned_by = u.id
                WHERE ib.ip_address = ? 
                AND ib.is_active = 1
                ORDER BY ib.created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$ipAddress]);
            
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Ban info error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Tüm aktif banları listeler
     */
    public function getActiveBans($limit = 50, $offset = 0) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT ib.*, u.username as banned_by_username
                FROM ip_bans ib
                LEFT JOIN users u ON ib.banned_by = u.id
                WHERE ib.is_active = 1 
                AND (ib.ban_type = 'permanent' OR ib.banned_until > NOW())
                ORDER BY ib.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Active bans list error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Süresi dolan banları otomatik temizler
     */
    public function cleanExpiredBans() {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE ip_bans 
                SET is_active = 0, updated_at = NOW() 
                WHERE ban_type = 'temporary' 
                AND banned_until <= NOW() 
                AND is_active = 1
            ");
            $stmt->execute();
            
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Clean expired bans error: " . $e->getMessage());
            return 0;
        }
    }
}
?>

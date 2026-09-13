<?php
class UserManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Kullanıcı hesabını pasif yapar
     */
    public function deactivateUser($userId, $reason, $deactivatedBy, $statusType = 'permanent', $duration = null) {
        try {
            $this->pdo->beginTransaction();
            
            // Mevcut durumu al
            $stmt = $this->pdo->prepare("SELECT is_active FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $currentUser = $stmt->fetch();
            
            if (!$currentUser) {
                throw new Exception("Kullanıcı bulunamadı.");
            }
            
            $restoreAt = null;
            if ($statusType === 'temporary' && $duration) {
                $restoreAt = date('Y-m-d H:i:s', strtotime("+{$duration}"));
            }
            
            // Kullanıcıyı pasif yap
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET is_active = 0, 
                    deactivation_reason = ?, 
                    deactivated_until = ?, 
                    deactivated_by = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$reason, $restoreAt, $deactivatedBy, $userId]);
            
            // Status history kaydet
            $stmt = $this->pdo->prepare("
                INSERT INTO user_status_history 
                (user_id, old_status, new_status, reason, status_type, restore_at, changed_by) 
                VALUES (?, ?, 0, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId, 
                $currentUser['is_active'], 
                $reason, 
                $statusType, 
                $restoreAt, 
                $deactivatedBy
            ]);
            
            // Kullanıcının oturumunu sonlandır
            $this->terminateUserSession($userId);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => 'Kullanıcı başarıyla pasif edildi.'
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            error_log("User deactivation error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Kullanıcı pasif etme işlemi sırasında hata oluştu: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Kullanıcı hesabını aktif yapar
     */
    public function reactivateUser($userId, $reactivatedBy, $reason = 'Admin tarafından yeniden aktif edildi') {
        try {
            $this->pdo->beginTransaction();
            
            // Mevcut durumu al
            $stmt = $this->pdo->prepare("SELECT is_active FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $currentUser = $stmt->fetch();
            
            if (!$currentUser) {
                throw new Exception("Kullanıcı bulunamadı.");
            }
            
            // Kullanıcıyı aktif yap
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET is_active = 1, 
                    deactivation_reason = NULL, 
                    deactivated_until = NULL, 
                    deactivated_by = NULL,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            
            // Status history kaydet
            $stmt = $this->pdo->prepare("
                INSERT INTO user_status_history 
                (user_id, old_status, new_status, reason, status_type, changed_by) 
                VALUES (?, ?, 1, ?, 'permanent', ?)
            ");
            $stmt->execute([$userId, $currentUser['is_active'], $reason, $reactivatedBy]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => 'Kullanıcı başarıyla aktif edildi.'
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            error_log("User reactivation error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Kullanıcı aktif etme işlemi sırasında hata oluştu: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Kullanıcıyı siler
     */
    public function deleteUser($userId, $deletedBy, $reason = 'Admin tarafından silindi') {
        try {
            $this->pdo->beginTransaction();
            
            // Kullanıcı bilgilerini al
            $stmt = $this->pdo->prepare("SELECT username, email, is_active FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                throw new Exception("Kullanıcı bulunamadı.");
            }
            
            // Status history kaydet
            $stmt = $this->pdo->prepare("
                INSERT INTO user_status_history 
                (user_id, old_status, new_status, reason, status_type, changed_by) 
                VALUES (?, ?, -1, ?, 'permanent', ?)
            ");
            $stmt->execute([$userId, $user['is_active'], $reason, $deletedBy]);
            
            // Kullanıcının oturumunu sonlandır
            $this->terminateUserSession($userId);
            
            // Kullanıcıyı sil
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => 'Kullanıcı başarıyla silindi.'
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            error_log("User deletion error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Kullanıcı silme işlemi sırasında hata oluştu: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * IP adresindeki tüm kullanıcıları yönetir
     */
    public function manageIPUsers($ipAddress, $action, $reason, $adminId, $duration = null, $userIds = null) {
        try {
            $this->pdo->beginTransaction();
            
            // IP'deki kullanıcıları al
            $whereClause = "registration_ip = ?";
            $params = [$ipAddress];
            
            if ($userIds && is_array($userIds)) {
                $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
                $whereClause .= " AND id IN ($placeholders)";
                $params = array_merge($params, $userIds);
            }
            
            $stmt = $this->pdo->prepare("SELECT id, username, is_active FROM users WHERE $whereClause");
            $stmt->execute($params);
            $users = $stmt->fetchAll();
            
            $results = [];
            
            foreach ($users as $user) {
                switch ($action) {
                    case 'deactivate':
                        $result = $this->deactivateUser($user['id'], $reason, $adminId, 'permanent', null);
                        break;
                    case 'deactivate_temporary':
                        $result = $this->deactivateUser($user['id'], $reason, $adminId, 'temporary', $duration);
                        break;
                    case 'activate':
                        $result = $this->reactivateUser($user['id'], $adminId, $reason);
                        break;
                    case 'delete':
                        $result = $this->deleteUser($user['id'], $adminId, $reason);
                        break;
                    default:
                        $result = ['success' => false, 'message' => 'Geçersiz işlem'];
                }
                
                $results[] = [
                    'user' => $user,
                    'result' => $result
                ];
            }
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => 'Toplu işlem tamamlandı.',
                'results' => $results
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            error_log("IP users management error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Toplu işlem sırasında hata oluştu: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Kullanıcının oturumunu sonlandırır
     */
    private function terminateUserSession($userId) {
        try {
            // Veritabanından kullanıcının session'larını temizle
            $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Alternatif olarak session dosyalarını temizleme (daha güvenli)
            $sessionPath = session_save_path() ?: sys_get_temp_dir();
            
            if (is_dir($sessionPath) && is_readable($sessionPath)) {
                $sessions = glob($sessionPath . '/sess_*');
                if ($sessions) {
                    foreach ($sessions as $sessionFile) {
                        if (is_readable($sessionFile) && is_writable($sessionFile)) {
                            $content = @file_get_contents($sessionFile);
                            if ($content !== false && strpos($content, "user_id|i:$userId") !== false) {
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
     * Geçici olarak pasif edilmiş kullanıcıları otomatik aktif eder
     */
    public function reactivateExpiredUsers() {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET is_active = 1, 
                    deactivation_reason = NULL, 
                    deactivated_until = NULL, 
                    deactivated_by = NULL,
                    updated_at = NOW()
                WHERE is_active = 0 
                AND deactivated_until <= NOW() 
                AND deactivated_until IS NOT NULL
            ");
            $stmt->execute();
            
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Auto reactivation error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Kullanıcının pasif durumu hakkında bilgi alır
     */
    public function getUserDeactivationInfo($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.deactivation_reason, u.deactivated_until, 
                       u.deactivated_by, admin.username as deactivated_by_username
                FROM users u
                LEFT JOIN users admin ON u.deactivated_by = admin.id
                WHERE u.id = ? AND u.is_active = 0
            ");
            $stmt->execute([$userId]);
            
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("User deactivation info error: " . $e->getMessage());
            return false;
        }
    }
}
?>

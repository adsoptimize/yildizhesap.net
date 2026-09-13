<?php
require_once 'DatabaseSessionManager.php';

class Auth {
    private $pdo;
    private $sessionManager;
    private $max_login_attempts = 5;
    private $lockout_time = 15; // dakika
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->sessionManager = new DatabaseSessionManager($pdo);
        
        // SiteSettings sınıfını yükle
        if (!class_exists('SiteSettings')) {
            require_once __DIR__ . '/SiteSettings.php';
        }
    }
    
    /**
     * Kullanıcı kaydı
     */
    public function register($data) {
        try {
            // Veri doğrulama
            $errors = $this->validateRegistrationData($data);
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            // E-posta ve kullanıcı adı kontrolü
            if ($this->userExists($data['email'], $data['username'])) {
                return ['success' => false, 'errors' => ['Bu e-posta veya kullanıcı adı zaten kullanılıyor.']];
            }
            
            // IP limit kontrolü
            $ipCheckResult = $this->checkIPAccountLimit();
            if (!$ipCheckResult['allowed']) {
                return ['success' => false, 'errors' => [$ipCheckResult['message']]];
            }
            
            // Şifre hash'leme (SHA256 + salt)
            $salt = bin2hex(random_bytes(16)); // 32 karakter salt
            $passwordHash = hash('sha256', $data['password'] . $salt);
            $fullPasswordHash = $salt . ':' . $passwordHash; // salt:hash formatında sakla
            
            // Doğrulama token'ı
            $verificationToken = bin2hex(random_bytes(32));
            
            // Veritabanına kaydet
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password_hash, first_name, last_name, phone, verification_token, registration_ip)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $data['username'],
                $data['email'],
                $fullPasswordHash,
                $data['first_name'],
                $data['last_name'],
                $data['phone'] ?? null,
                $verificationToken,
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);
            
            if ($result) {
                $userId = $this->pdo->lastInsertId();
                
                // Kullanıcıyı aktif yap (e-posta doğrulaması gerekmiyor)
                $stmt = $this->pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
                $stmt->execute([$userId]);
                
                // Otomatik giriş için session oluştur
                $sessionToken = $this->sessionManager->createSession($userId);
                
                // Session cookie'sini ayarla
                setcookie('session_token', $sessionToken, time() + (2 * 60 * 60), '/', '', false, true); // 2 saat
                
                // Aktivite log'u
                $this->logActivity($userId, 'register', 'Kullanıcı kaydı oluşturuldu ve otomatik giriş yapıldı');
                
                // Kullanıcı bilgilerini al
                $stmt = $this->pdo->prepare("SELECT id, username, email, first_name, last_name, balance, is_admin FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return [
                    'success' => true,
                    'user_id' => $userId,
                    'session_token' => $sessionToken,
                    'user' => $user,
                    'auto_login' => true,
                    'message' => 'Kayıt başarılı! Hesabınıza otomatik giriş yapıldı.'
                ];
            }
            
            return ['success' => false, 'errors' => ['Kayıt sırasında bir hata oluştu.']];
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Sistem hatası. Lütfen tekrar deneyin.']];
        }
    }
    
    /**
     * Kullanıcı girişi
     */
    public function login($emailOrUsername, $password, $rememberMe = false) {
        try {
            // Rate limiting kontrolü
            if (!SessionSecurity::checkRateLimit('login', 5, 300)) {
                return ['success' => false, 'errors' => ['Fazla giriş denemesi. 5 dakika bekleyin.']];
            }
            
            // Input sanitizasyonu
            $emailOrUsername = XSSProtection::sanitizeInput($emailOrUsername);
            
            // Brute force koruması
            if ($this->isAccountLocked($emailOrUsername)) {
                return ['success' => false, 'errors' => ['Hesap geçici olarak kilitlendi. 15 dakika sonra tekrar deneyin.']];
            }
            
            // Kullanıcıyı bul
            $user = $this->getUserByEmailOrUsername($emailOrUsername);
            
            if (!$user) {
                $this->incrementLoginAttempts($emailOrUsername);
                return ['success' => false, 'errors' => ['Geçersiz e-posta/kullanıcı adı veya şifre.']];
            }
            
            // Şifre kontrolü (hem eski hem yeni formatı destekle)
            $passwordValid = $this->verifyPassword($password, $user['password_hash']);
            
            if (!$passwordValid) {
                $this->incrementLoginAttempts($emailOrUsername);
                $this->logActivity($user['id'], 'failed_login', 'Hatalı şifre girişimi');
                return ['success' => false, 'errors' => ['Geçersiz e-posta/kullanıcı adı veya şifre.']];
            }
            
            // Eski format kullanıyorsa yeni formata dönüştür
            if (!$this->isNewPasswordFormat($user['password_hash'])) {
                $this->upgradePasswordHash($user['id'], $password);
            }
            
            // Not: is_active kontrolü login.php'de daha detaylı yapılacak
            
            // Başarılı giriş
            $this->resetLoginAttempts($user['id']);
            $this->updateLastLogin($user['id']);
            
            // Session oluştur
            $sessionToken = $this->sessionManager->createSession($user['id']);
            
            // Aktivite log'u
            $this->logActivity($user['id'], 'login', 'Başarılı giriş');
            
            return [
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'balance' => $user['balance'],
                    'is_admin' => $user['is_admin'],
                    'is_active' => $user['is_active']
                ],
                'session_token' => $sessionToken
            ];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Sistem hatası. Lütfen tekrar deneyin.']];
        }
    }
    
    /**
     * Çıkış
     */
    public function logout($sessionToken) {
        try {
            $stmt = $this->pdo->prepare("UPDATE active_sessions SET is_active = 0, invalidated_by = 'user', invalidated_at = NOW() WHERE session_token = ?");
            $stmt->execute([$sessionToken]);
            
            return ['success' => true];
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            return ['success' => false];
        }
    }
    
    /**
     * Session doğrulama
     */
    public function validateSession($sessionToken) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.*, u.id as user_id, u.username, u.email, u.first_name, u.last_name, u.balance, u.is_active, u.is_admin
                FROM active_sessions s
                JOIN users u ON s.user_id = u.id
                WHERE s.session_token = ? AND s.expires_at > NOW() AND u.is_active = 1 AND s.is_active = 1
            ");
            $stmt->execute([$sessionToken]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($session) {
                // Session süresini uzat
                $this->extendSession($sessionToken);
                return [
                    'valid' => true,
                    'user' => [
                        'id' => $session['user_id'],
                        'username' => $session['username'],
                        'email' => $session['email'],
                        'first_name' => $session['first_name'],
                        'last_name' => $session['last_name'],
                        'balance' => $session['balance'],
                        'is_admin' => $session['is_admin']
                    ]
                ];
            }
            
            return ['valid' => false];
        } catch (Exception $e) {
            error_log("Session validation error: " . $e->getMessage());
            return ['valid' => false];
        }
    }
    
    // Private helper methods
    
    private function validateRegistrationData($data) {
        $errors = [];
        
        if (empty($data['username']) || strlen($data['username']) < 3) {
            $errors[] = 'Kullanıcı adı en az 3 karakter olmalıdır.';
        }
        
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Geçerli bir e-posta adresi girin.';
        }
        
        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errors[] = 'Şifre en az 6 karakter olmalıdır.';
        }
        
        if ($data['password'] !== $data['password_confirm']) {
            $errors[] = 'Şifreler eşleşmiyor.';
        }
        
        if (empty($data['first_name'])) {
            $errors[] = 'Ad alanı gereklidir.';
        }
        
        if (empty($data['last_name'])) {
            $errors[] = 'Soyad alanı gereklidir.';
        }
        
        return $errors;
    }
    
    private function userExists($email, $username) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        return $stmt->fetch() !== false;
    }
    
    private function getUserByEmailOrUsername($emailOrUsername) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$emailOrUsername, $emailOrUsername]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function isAccountLocked($emailOrUsername) {
        $stmt = $this->pdo->prepare("
            SELECT login_attempts, last_login_attempt 
            FROM users 
            WHERE (email = ? OR username = ?) AND login_attempts >= ?
        ");
        $stmt->execute([$emailOrUsername, $emailOrUsername, $this->max_login_attempts]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $user['last_login_attempt']) {
            $lockoutEnd = strtotime($user['last_login_attempt']) + ($this->lockout_time * 60);
            return time() < $lockoutEnd;
        }
        
        return false;
    }
    
    private function incrementLoginAttempts($emailOrUsername) {
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET login_attempts = login_attempts + 1, last_login_attempt = NOW() 
            WHERE email = ? OR username = ?
        ");
        $stmt->execute([$emailOrUsername, $emailOrUsername]);
    }
    
    private function resetLoginAttempts($userId) {
        $stmt = $this->pdo->prepare("UPDATE users SET login_attempts = 0, last_login_attempt = NULL WHERE id = ?");
        $stmt->execute([$userId]);
    }
    
    private function updateLastLogin($userId) {
        $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }
    
    private function createSession($userId, $rememberMe = false) {
        $sessionToken = bin2hex(random_bytes(64));
        $expiresAt = $rememberMe ? date('Y-m-d H:i:s', strtotime('+30 days')) : date('Y-m-d H:i:s', strtotime('+1 day'));
        
        $stmt = $this->pdo->prepare("
            INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $sessionToken,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $expiresAt
        ]);
        
        return $sessionToken;
    }
    
    private function extendSession($sessionToken) {
        $stmt = $this->pdo->prepare("UPDATE active_sessions SET expires_at = DATE_ADD(NOW(), INTERVAL 2 HOUR), last_activity = NOW() WHERE session_token = ? AND is_active = 1");
        $stmt->execute([$sessionToken]);
    }
    
    /**
     * Şifre doğrulama (hem eski hem yeni formatı destekler)
     */
    private function verifyPassword($password, $storedHash) {
        // Yeni format kontrolü (salt:hash)
        if ($this->isNewPasswordFormat($storedHash)) {
            list($salt, $hash) = explode(':', $storedHash, 2);
            $computedHash = hash('sha256', $password . $salt);
            return hash_equals($hash, $computedHash);
        }
        
        // Eski format kontrolü (password_hash)
        return password_verify($password, $storedHash);
    }
    
    /**
     * Yeni şifre formatı kontrolü
     */
    private function isNewPasswordFormat($hash) {
        return strpos($hash, ':') !== false && strlen($hash) > 64;
    }
    
    /**
     * Eski şifre hash'ini yeni formata dönüştür
     */
    private function upgradePasswordHash($userId, $plainPassword) {
        try {
            $salt = bin2hex(random_bytes(16));
            $passwordHash = hash('sha256', $plainPassword . $salt);
            $fullPasswordHash = $salt . ':' . $passwordHash;
            
            $stmt = $this->pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $result = $stmt->execute([$fullPasswordHash, $userId]);
            
            if ($result) {
                error_log("Password upgraded to SHA256 for user ID: $userId");
            }
        } catch (Exception $e) {
            error_log("Password upgrade error for user ID $userId: " . $e->getMessage());
        }
    }
    
    /**
     * Şifre değiştirme
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        try {
            // Mevcut kullanıcıyı al
            $stmt = $this->pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Kullanıcı bulunamadı.'];
            }
            
            // Eski şifre kontrolü
            if (!$this->verifyPassword($oldPassword, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Mevcut şifre hatalı.'];
            }
            
            // Yeni şifre hash'leme
            $salt = bin2hex(random_bytes(16));
            $passwordHash = hash('sha256', $newPassword . $salt);
            $fullPasswordHash = $salt . ':' . $passwordHash;
            
            // Şifre güncelleme
            $stmt = $this->pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $result = $stmt->execute([$fullPasswordHash, $userId]);
            
            if ($result) {
                $this->logActivity($userId, 'password_change', 'Şifre değiştirildi');
                return ['success' => true, 'message' => 'Şifre başarıyla değiştirildi.'];
            }
            
            return ['success' => false, 'message' => 'Şifre değiştirilemedi.'];
            
        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Sistem hatası.'];
        }
    }
    
    private function logActivity($userId, $action, $details = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_activity_log (user_id, action, details, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $action,
                $details,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Activity log error: " . $e->getMessage());
        }
    }
    
    /**
     * Kullanıcı giriş yapmış mı kontrolü
     */
    public function isLoggedIn() {
        if (!isset($_COOKIE['session_token'])) {
            return false;
        }
        
        $sessionResult = $this->validateSession($_COOKIE['session_token']);
        return $sessionResult['valid'];
    }
    
    /**
     * Mevcut kullanıcı bilgilerini al
     */
    public function getCurrentUser() {
        if (!isset($_COOKIE['session_token'])) {
            return null;
        }
        
        $sessionResult = $this->validateSession($_COOKIE['session_token']);
        return $sessionResult['valid'] ? $sessionResult['user'] : null;
    }
    
    /**
     * Kullanıcının admin olup olmadığını kontrol et
     */
    public function isAdmin() {
        $currentUser = $this->getCurrentUser();
        return $currentUser && isset($currentUser['is_admin']) && $currentUser['is_admin'] == 1;
    }
    
    /**
     * IP adresine göre zaman tabanlı hesap oluşturma limitini kontrol et
     */
    private function checkIPAccountLimit() {
        try {
            // Site ayarlarını al
            $siteSettings = SiteSettings::getInstance();
            
            // IP limit kontrolü aktif mi?
            $ipLimitEnabled = $siteSettings->get('ip_limit_enabled', '1');
            if ($ipLimitEnabled !== '1') {
                return ['allowed' => true];
            }
            
            // Ayarları al
            $maxAccountsPerIp = (int) $siteSettings->get('max_accounts_per_ip', '1');
            $limitDays = (int) $siteSettings->get('ip_limit_days', '365');
            
            // Mevcut IP adresini al
            $currentIp = $_SERVER['REMOTE_ADDR'] ?? null;
            
            // CLI veya geliştirme ortamı için fallback
            if (!$currentIp) {
                $currentIp = '127.0.0.1'; // Localhost IP
                error_log("IP adresi bulunamadı, fallback IP kullanıldı: $currentIp");
            }
            
            // Belirtilen gün aralığında bu IP'den oluşturulan hesap sayısını say
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as account_count 
                FROM users 
                WHERE registration_ip = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$currentIp, $limitDays]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $currentAccountCount = $result['account_count'] ?? 0;
            
            if ($currentAccountCount >= $maxAccountsPerIp) {
                $dayText = $limitDays == 1 ? 'gün' : 'gün';
                return [
                    'allowed' => false, 
                    'message' => "Bu IP adresinden son $limitDays $dayText içinde maksimum $maxAccountsPerIp hesap oluşturulabilir. Mevcut: $currentAccountCount hesap"
                ];
            }
            
            return ['allowed' => true];
            
        } catch (Exception $e) {
            error_log("IP limit check error: " . $e->getMessage());
            error_log("IP limit check stack trace: " . $e->getTraceAsString());
            // Hata durumunda kayda izin ver (güvenli taraf)
            return ['allowed' => true];
        }
    }
    
    /**
     * IP limit bilgilerini admin için al (zaman tabanlı)
     */
    public function getIPLimitInfo($ipAddress = null) {
        try {
            $targetIp = $ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? null);
            
            if (!$targetIp) {
                return ['error' => 'IP adresi bulunamadı'];
            }
            
            // Site ayarlarını al
            $siteSettings = SiteSettings::getInstance();
            $maxAccountsPerIp = (int) $siteSettings->get('max_accounts_per_ip', '1');
            $limitDays = (int) $siteSettings->get('ip_limit_days', '365');
            $ipLimitEnabled = $siteSettings->get('ip_limit_enabled', '1');
            
            // Bu IP'den son X günde oluşturulan hesapları al
            $stmt = $this->pdo->prepare("
                SELECT u.id, u.username, u.email, u.created_at, u.is_active
                FROM users u
                WHERE u.registration_ip = ?
                AND u.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ORDER BY u.created_at DESC
            ");
            $stmt->execute([$targetIp, $limitDays]);
            $recentAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Tüm zamanlar bu IP'den oluşturulan hesapları al
            $stmt = $this->pdo->prepare("
                SELECT u.id, u.username, u.email, u.created_at, u.is_active
                FROM users u
                WHERE u.registration_ip = ?
                ORDER BY u.created_at DESC
            ");
            $stmt->execute([$targetIp]);
            $allAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $recentCount = count($recentAccounts);
            $canCreateNew = ($recentCount < $maxAccountsPerIp) || ($ipLimitEnabled !== '1');
            
            return [
                'ip_address' => $targetIp,
                'current_count' => $recentCount,
                'total_count' => count($allAccounts),
                'max_allowed' => $maxAccountsPerIp,
                'limit_days' => $limitDays,
                'limit_enabled' => $ipLimitEnabled === '1',
                'can_create_new' => $canCreateNew,
                'recent_accounts' => $recentAccounts,
                'all_accounts' => $allAccounts
            ];
            
        } catch (Exception $e) {
            error_log("IP limit info error: " . $e->getMessage());
            return ['error' => 'Bilgi alınırken hata oluştu: ' . $e->getMessage()];
        }
    }
}

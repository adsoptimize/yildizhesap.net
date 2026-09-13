<?php
/**
 * Session Security Manager
 * Session Hijacking ve diğer güvenlik tehditlerine karşı koruma sağlar
 */
class SessionSecurity {
    
    /**
     * Güvenli session başlatma
     */
    public static function startSecureSession() {
        // Session konfigürasyonu
        ini_set('session.cookie_httponly', 1); // JavaScript erişimini engelle
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0); // HTTPS üzerinde güvenli
        ini_set('session.use_only_cookies', 1); // Sadece cookie kullan
        ini_set('session.use_strict_mode', 1); // Strict mode aktif
        ini_set('session.cookie_samesite', 'Strict'); // CSRF koruması
        ini_set('session.gc_maxlifetime', 7200); // 2 saat maksimum yaşam süresi
        
        // Session name'i rastgele yap (güvenlik için)
        session_name('BSEC_' . substr(md5($_SERVER['HTTP_HOST'] ?? 'localhost'), 0, 8));
        
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Session hijacking koruması
        self::validateSession();
        
        // Session regeneration (her 5 dakikada bir)
        self::regenerateSessionId();
    }
    
    /**
     * Session doğrulama - Dengeli güvenlik koruması
     */
    private static function validateSession() {
        $ipAddress = self::getRealIpAddress();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // İlk kez giriş yapıyorsa temel bilgileri kaydet
        if (!isset($_SESSION['_security_fingerprint'])) {
            $_SESSION['_original_ip'] = $ipAddress;
            $_SESSION['_original_user_agent'] = $userAgent;
            $_SESSION['_session_created'] = time();
            $_SESSION['_last_regeneration'] = time();
            $_SESSION['_security_fingerprint'] = self::generateCoreFingerprint();
            return;
        }
        
        // Kritik güvenlik kontrolleri (sadece temel önemli alanlar)
        $coreFingerprint = self::generateCoreFingerprint();
        
        // IP değişim kontrolü (SIFIR TOLERANS)
        if (isset($_SESSION['_original_ip']) && $_SESSION['_original_ip'] !== $ipAddress) {
            error_log("SECURITY ALERT: IP change detected. Original: {$_SESSION['_original_ip']}, Current: $ipAddress");
            self::destroySession();
            http_response_code(403);
            die(self::getSecurityViolationPage());
        }
        
        // User Agent değişim kontrolü (SIFIR TOLERANS)
        if (isset($_SESSION['_original_user_agent']) && $_SESSION['_original_user_agent'] !== $userAgent) {
            error_log("SECURITY ALERT: User Agent change detected. Session terminated.");
            self::destroySession();
            http_response_code(403);
            die(self::getSecurityViolationPage());
        }
        
        // Core fingerprint kontrolü (sadece kritik değişiklikler için)
        if ($_SESSION['_security_fingerprint'] !== $coreFingerprint) {
            // Önemli değişiklik tespit edildi
            error_log("SECURITY ALERT: Critical device fingerprint change detected.");
            self::destroySession();
            http_response_code(403);
            die(self::getSecurityViolationPage());
        }
        
        // Session yaşlanma kontrolü (2 saat)
        if (isset($_SESSION['_session_created']) && (time() - $_SESSION['_session_created']) > 7200) {
            error_log("SECURITY: Session expired for IP: " . $ipAddress);
            self::destroySession();
            return;
        }
    }
    
    /**
     * Session ID yenileme (fixation saldırılarına karşı)
     */
/**
 * Session ID yenileme (fixation saldırılarına karşı)
 */
private static function regenerateSessionId() {
    // Her 5 dakikada bir session ID'yi yenile
    if (!isset($_SESSION['_last_regeneration']) || (time() - $_SESSION['_last_regeneration']) > 300) {
        // Sadece aktif bir oturum varsa ID'yi yenile
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $_SESSION['_last_regeneration'] = time();
        }
    }
}
    
    /**
     * Session'ı güvenli şekilde yok et
     */
    public static function destroySession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            
            // Session cookie'sini sil
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
            
            session_destroy();
        }
    }
    
    /**
     * Gerçek IP adresini al (proxy'ler arkasında bile)
     */
    public static function getRealIpAddress() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                   'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                // Private IP'leri ve localhost'u atla
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * CSRF Token oluştur
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['_csrf_token']) || !isset($_SESSION['_csrf_created']) || 
            (time() - $_SESSION['_csrf_created']) > 3600) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['_csrf_created'] = time();
        }
        return $_SESSION['_csrf_token'];
    }
    
    /**
     * CSRF Token doğrula
     */
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['_csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['_csrf_token'], $token);
    }
    
    /**
     * Rate limiting - Brute force koruması
     */
    public static function checkRateLimit($action, $maxAttempts = 5, $timeWindow = 300) {
        $ip = self::getRealIpAddress();
        $key = "rate_limit_{$action}_{$ip}";
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
        }
        
        $data = $_SESSION[$key];
        
        // Zaman penceresi geçtiyse sıfırla
        if ((time() - $data['first_attempt']) > $timeWindow) {
            $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
            return true;
        }
        
        // Limit aşıldı mı?
        if ($data['count'] >= $maxAttempts) {
            error_log("SECURITY: Rate limit exceeded for action '$action' from IP: $ip");
            return false;
        }
        
        $_SESSION[$key]['count']++;
        return true;
    }
    
    /**
     * Güvenli header'lar ekle
     */
    public static function setSecurityHeaders() {
        // XSS koruması
        header('X-XSS-Protection: 1; mode=block');
        
        // Content sniffing koruması
        header('X-Content-Type-Options: nosniff');
        
        // Clickjacking koruması
        header('X-Frame-Options: DENY');
        
        // Content Security Policy (XSS koruması)
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://cdn.ckeditor.com; " .
               "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; " .
               "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
               "img-src 'self' data: https:; " .
               "connect-src 'self'; " .
               "frame-ancestors 'none'; " .
               "base-uri 'self'; " .
               "form-action 'self';";
        header("Content-Security-Policy: $csp");
        
        // HSTS (HTTPS zorunlu)
        if (isset($_SERVER['HTTPS'])) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Feature policy
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
    }
    
    /**
     * Temel (stabil) parmak izi oluştur - sadece kritik değişiklikler için
     */
    private static function generateCoreFingerprint() {
        $components = [
            // Sadece temel ve sabit özellikler (değişmeyenler)
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            self::getRealIpAddress(),
            $_SERVER['HTTP_HOST'] ?? ''
        ];
        
        // Null değerleri filtrele
        $components = array_filter($components, function($value) {
            return $value !== null && $value !== '';
        });
        
        return hash('sha256', implode('|', $components));
    }
    
    /**
     * Gelişmiş cihaz parmak izi oluştur
     */
    private static function generateAdvancedFingerprint() {
        $components = [
            // Temel tarayıcı bilgileri
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
            $_SERVER['HTTP_ACCEPT'] ?? '',
            $_SERVER['HTTP_ACCEPT_CHARSET'] ?? '',
            
            // IP adresi (kesin)
            self::getRealIpAddress(),
            
            // Tarayıcı özellikleri
            $_SERVER['HTTP_DNT'] ?? '', // Do Not Track
            $_SERVER['HTTP_CONNECTION'] ?? '',
            $_SERVER['HTTP_UPGRADE_INSECURE_REQUESTS'] ?? '',
            $_SERVER['HTTP_CACHE_CONTROL'] ?? '',
            
            // Modern tarayıcı güvenlik header'ları
            $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '',
            $_SERVER['HTTP_SEC_FETCH_MODE'] ?? '',
            $_SERVER['HTTP_SEC_FETCH_USER'] ?? '',
            $_SERVER['HTTP_SEC_FETCH_DEST'] ?? '',
            $_SERVER['HTTP_SEC_CH_UA'] ?? '',
            $_SERVER['HTTP_SEC_CH_UA_MOBILE'] ?? '',
            $_SERVER['HTTP_SEC_CH_UA_PLATFORM'] ?? '',
            
            // İstemci hint'leri
            $_SERVER['HTTP_SEC_CH_UA_ARCH'] ?? '',
            $_SERVER['HTTP_SEC_CH_UA_BITNESS'] ?? '',
            $_SERVER['HTTP_SEC_CH_UA_MODEL'] ?? '',
            $_SERVER['HTTP_SEC_CH_UA_PLATFORM_VERSION'] ?? '',
            
            // TLS fingerprinting için
            $_SERVER['SSL_PROTOCOL'] ?? '',
            $_SERVER['SSL_CIPHER'] ?? '',
            
            // Server port ve protokol
            $_SERVER['SERVER_PORT'] ?? '',
            $_SERVER['REQUEST_SCHEME'] ?? '',
            
            // İlave güvenlik
            $_SERVER['HTTP_HOST'] ?? '',
            session_id(), // Session ID de dahil et
        ];
        
        // Null ve boş değerleri filtrele
        $components = array_filter($components, function($value) {
            return $value !== null && $value !== '';
        });
        
        // Güçlü hash oluştur
        $fingerprint = hash('sha256', implode('|', $components));
        
        return $fingerprint;
    }
    
    /**
     * Şüpheli aktiviteyi logla
     */
    private static function logSuspiciousActivity($ipAddress, $activity) {
        $logFile = __DIR__ . '/logs/security_violations.log';
        
        // Log dizini yoksa oluştur
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $ipAddress,
            'activity' => $activity,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ];
        
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Güvenlik ihlali sayfası HTML'i
     */
    private static function getSecurityViolationPage() {
        return '<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Güvenlik İhlali Tespit Edildi</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 50px auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); text-align: center; }
        .error-icon { font-size: 64px; color: #dc3545; margin-bottom: 20px; }
        h1 { color: #dc3545; margin-bottom: 20px; }
        p { color: #6c757d; line-height: 1.6; margin-bottom: 15px; }
        .alert { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #dc3545; }
        .btn { display: inline-block; padding: 12px 24px; background: #6c63ff; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .btn:hover { background: #5a52d5; }
        .security-info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin-top: 20px; font-size: 14px; color: #1565c0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-icon">🛡️</div>
        <h1>Güvenlik İhlali Tespit Edildi</h1>
        
        <div class="alert">
            <strong>⚠️ CRİTİK GÜVENLİK UYARISI</strong><br>
            Oturumunuz başka bir cihaz, tarayıcı veya IP adresinden erişilmeye çalışıldığı tespit edilmiştir.
        </div>
        
        <p><strong>Tespit edilen değişiklikler:</strong></p>
        <ul style="text-align: left; color: #495057;">
            <li>Cihaz parmak izi değişimi</li>
            <li>IP adresi değişimi</li>
            <li>Tarayıcı özelliklerinde farklılık</li>
            <li>Şüpheli session manipülasyonu</li>
        </ul>
        
        <p>Güvenliğiniz için oturumunuz <strong>derhal sonlandırılmıştır</strong>.</p>
        
        <div class="security-info">
            <strong>🔒 Bu güvenlik önlemi şunları korur:</strong><br>
            • Session hijacking (oturum çalma) saldırıları<br>
            • Çerez hırsızlığı<br>
            • Yetkisiz hesap erişimi<br>
            • Kimlik avı saldırıları
        </div>
        
        <a href="/login.php" class="btn">🔐 Güvenli Giriş Yap</a>
        
        <p style="margin-top: 30px; font-size: 12px; color: #999;">
            Bu olay güvenlik günlüğüne kaydedilmiştir.<br>
            Sorun devam ederse lütfen yöneticiye başvurun.
        </p>
    </div>
</body>
</html>';
    }
    
    /**
     * Session token'ı force destroy et (admin kullanımı)
     */
    public static function forceDestroySessionByToken($sessionToken) {
        // Bu fonksiyon database tabanlı session yönetimi gerektirir
        // Şu an için session file bazlı çalışıyor
        return false;
    }
    
    /**
     * Cihaz değişimi tespit et
     */
    public static function detectDeviceChange() {
        if (!isset($_SESSION['_security_fingerprint'])) {
            return false;
        }
        
        $currentFingerprint = self::generateAdvancedFingerprint();
        return $_SESSION['_security_fingerprint'] !== $currentFingerprint;
    }
    
    /**
     * Session güvenlik raporunu al
     */
    public static function getSessionSecurityReport() {
        if (!isset($_SESSION['_session_created'])) {
            return null;
        }
        
        return [
            'session_age' => time() - $_SESSION['_session_created'],
            'ip_address' => $_SESSION['_original_ip'] ?? 'unknown',
            'user_agent' => $_SESSION['_original_user_agent'] ?? 'unknown',
            'fingerprint_changes' => count($_SESSION['_fingerprint_history'] ?? []),
            'last_regeneration' => $_SESSION['_last_regeneration'] ?? 0,
            'csrf_token_age' => time() - ($_SESSION['_csrf_created'] ?? time())
        ];
    }
}
?>

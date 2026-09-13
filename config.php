<?php
// config.php - Veritabanı bağlantı ayarları

// Güvenlik sınıflarını dahil et
require_once __DIR__ . '/SessionSecurity.php';
require_once __DIR__ . '/XSSProtection.php';
require_once __DIR__ . '/includes/FormSecurity.php';

// Güvenli session başlat
SessionSecurity::startSecureSession();

// Güvenlik header'larını ekle
SessionSecurity::setSecurityHeaders();

// Hata raporlamayı aç (geliştirme aşamasında) - Production'da kapanmalı
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Input sanitizasyonu (otomatik)
XSSProtection::sanitizeAllGET();
XSSProtection::sanitizeAllPOST();

// Veritabanı bilgileri
define('DB_HOST', 'localhost');
define('DB_NAME', 'yildippf_fbhspyeni');
define('DB_USER', 'yildippf_fbhspyeni'); // Kendi kullanıcı adınızı yazın
define('DB_PASS', 'KI&ftuo]_&1Noik7JU$L_j{T');     // Kendi şifrenizi yazın
define('DB_CHARSET', 'utf8mb4');

// Site ayarları - Dinamik URL algılama
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$siteUrl = $protocol . $host . ($path === '/' ? '/' : $path . '/');
define('SITE_URL', $siteUrl);
define('ITEMS_PER_PAGE', 6); // Her sayfada kaç hesap gösterilecek
define('ADMIN_PANEL_PATH', 'admin'); // Admin panel yolu (admin klasör adı)

// Güvenlik ayarları
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 7200); // 2 saat

// Dosya yükleme ayarları
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Veritabanı bağlantısı başarısız. Lütfen daha sonra tekrar deneyin.");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    // Güvenli sorgu hazırlama
    public function prepare($sql) {
        return $this->pdo->prepare($sql);
    }
    
    // Son eklenen ID'yi al
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    // Transaction başlat
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    // Transaction commit
    public function commit() {
        return $this->pdo->commit();
    }
    
    // Transaction rollback
    public function rollback() {
        return $this->pdo->rollback();
    }
}

// CSRF Token oluşturma fonksiyonu
function generateCSRFToken() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION[CSRF_TOKEN_NAME];
}

// CSRF Token doğrulama fonksiyonu
function verifyCSRFToken($token) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Güvenli input temizleme
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Güvenli sayı kontrolü
function sanitizeNumber($number, $min = 0, $max = PHP_INT_MAX) {
    $number = filter_var($number, FILTER_VALIDATE_INT);
    if ($number === false || $number < $min || $number > $max) {
        return false;
    }
    return $number;
}

// JSON yanıt gönderme
function sendJSONResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Oturum başlatma
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Veritabanı bağlantısını global olarak kullanılabilir yap
$db = Database::getInstance();
$pdo = $db->getConnection();

// Site Settings sınıfını dahil et
require_once __DIR__ . '/SiteSettings.php';

// Global site settings instance
$siteSettings = SiteSettings::getInstance();

// URL Helper sınıfını dahil et (SEO-friendly URLs için)
require_once __DIR__ . '/URLHelper.php';
?>
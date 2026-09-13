<?php
/**
 * Admin Panel IP Ban Kontrolü
 * Bu dosya tüm admin sayfalarında include edilmeli
 */

// Eğer zaten include edilmişse tekrar çalıştırma
if (defined('IP_BAN_CHECK_LOADED')) {
    return;
}
define('IP_BAN_CHECK_LOADED', true);

// Gerekli dosyaları include et
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../IPBanManager.php';

/**
 * IP Ban kontrolü yap
 * Eğer IP banlanmışsa banned.php sayfasına yönlendir
 */
function checkIPBanForAdmin() {
    // IP adresini al
    $ipAddress = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? 
                 $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
                 $_SERVER['REMOTE_ADDR'] ?? 
                 '127.0.0.1';
    
    // Eğer virgülle ayrılmış IP'ler varsa ilkini al
    if (strpos($ipAddress, ',') !== false) {
        $ipAddress = trim(explode(',', $ipAddress)[0]);
    }
    
    // IPBanManager ile ban kontrolü yap
    global $pdo;
    $ipBanManager = new IPBanManager($pdo);
    
    if ($ipBanManager->isIPBanned($ipAddress)) {
        // Ban bilgisini al
        $banInfo = $ipBanManager->getBanInfo($ipAddress);
        
        // Log olarak kaydet
        error_log("SECURITY: Banned IP {$ipAddress} tried to access admin panel. Ban reason: " . ($banInfo['reason'] ?? 'Unknown'));
        
        // Ana dizindeki banned.php sayfasına yönlendir
        header('Location: ../banned.php');
        exit();
    }
}

// Otomatik olarak kontrolü çalıştır
checkIPBanForAdmin();
?>

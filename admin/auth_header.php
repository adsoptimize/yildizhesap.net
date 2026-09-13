<?php
/**
 * Admin Authentication Header
 * Tüm admin sayfaları için ortak authentication
 */

// IP Ban kontrolü - En başta olmalı
if (file_exists('check-ip-ban.php')) {
    require_once 'check-ip-ban.php';
}

require_once '../config.php';
require_once '../functions.php';
require_once '../Auth.php';
require_once '../DatabaseSessionManager.php';

// Admin kontrolü - DatabaseSessionManager kullan
$sessionManager = new DatabaseSessionManager($pdo);
$currentUser = null;
$isLoggedIn = false;

$sessionToken = $_COOKIE['session_token'] ?? null;
if ($sessionToken) {
    $sessionResult = $sessionManager->validateSession($sessionToken);
    if ($sessionResult['valid']) {
        $currentUser = $sessionResult['user'];
        $isLoggedIn = true;
    } else {
        // Invalid session - clear cookie
        setcookie('session_token', '', time() - 1, '/', '', false, true);
        
        // Log suspicious activity
        error_log("SECURITY: Invalid session token attempted in admin panel from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ". Reason: {$sessionResult['reason']}");
        
        // If security violation (IP/UA change) show security page
        if (in_array($sessionResult['reason'], ['IP address changed', 'Device fingerprint changed'])) {
            http_response_code(403);
            die(getAdminSecurityViolationPage());
        }
    }
}

if (!$isLoggedIn) {
    $redirectPath = 'admin/' . basename($_SERVER['PHP_SELF']);
    header('Location: ../login.php?redirect=' . urlencode($redirectPath));
    exit;
}

if (!$currentUser || $currentUser['is_admin'] != 1) {
    http_response_code(403);
    die(getAccessDeniedPage());
}

// Backward compatibility
$user = $currentUser;
$auth = new stdClass();
$auth->user = $currentUser;

/**
 * Admin güvenlik ihlali sayfası
 */
function getAdminSecurityViolationPage() {
    return '<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Güvenlik İhlali</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .security-card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); text-align: center; max-width: 600px; }
        .security-icon { font-size: 64px; color: #dc3545; margin-bottom: 20px; }
        .alert-danger { border-left: 4px solid #dc3545; }
    </style>
</head>
<body>
    <div class="security-card">
        <div class="security-icon">🛡️</div>
        <h1 class="text-danger mb-4">Admin Panel - Güvenlik İhlali</h1>
        
        <div class="alert alert-danger">
            <strong>⚠️ CRİTİK GÜVENLİK UYARISI</strong><br>
            Admin panel oturumunuz başka bir cihaz, tarayıcı veya IP adresinden erişilmeye çalışıldığı tespit edilmiştir.
        </div>
        
        <p><strong>Tespit edilen değişiklikler:</strong></p>
        <ul class="list-unstyled">
            <li>🌐 IP adresi değişimi (VPN/Proxy değişimi)</li>
            <li>🖥️ Cihaz parmak izi değişimi</li>
            <li>🌏 Tarayıcı özelliklerinde farklılık</li>
            <li>⚠️ Şüpheli admin session manipülasyonu</li>
        </ul>
        
        <p class="text-danger"><strong>Admin panel erişiminiz güvenlik nedeniyle sonlandırılmıştır.</strong></p>
        
        <div class="mt-4">
            <a href="../login.php" class="btn btn-primary btn-lg">
                🔐 Admin Girişi Yap
            </a>
        </div>
        
        <p class="mt-4 text-muted small">
            Bu güvenlik ihlali admin log dosyasına kaydedilmiştir.<br>
            Admin panel erişimi için güvenlik protokolleri katı şekilde uygulanmaktadır.
        </p>
    </div>
</body>
</html>';
}

/**
 * Erişim reddedildi sayfası
 */
function getAccessDeniedPage() {
    return '<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Erişim Reddedildi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .access-card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); text-align: center; max-width: 500px; }
        .access-icon { font-size: 64px; color: #ffc107; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="access-card">
        <div class="access-icon">🚫</div>
        <h1 class="text-warning mb-4">Erişim Reddedildi</h1>
        
        <div class="alert alert-warning">
            <strong>⚠️ YETKİ HATASI</strong><br>
            Bu sayfaya erişim için admin yetkisine sahip olmanız gerekir.
        </div>
        
        <p>Mevcut durumunuz:</p>
        <ul class="list-unstyled">
            <li>❌ Admin yetkisi: Yok</li>
            <li>🔐 Oturum durumu: Geçerli ama yetkisiz</li>
        </ul>
        
        <div class="mt-4">
            <a href="../index.php" class="btn btn-primary me-2">
                🏠 Ana Sayfa
            </a>
            <a href="../logout.php" class="btn btn-secondary">
                🚪 Çıkış Yap
            </a>
        </div>
        
        <p class="mt-4 text-muted small">
            Admin yetkisi için site yöneticisi ile iletişime geçin.
        </p>
    </div>
</body>
</html>';
}
?>

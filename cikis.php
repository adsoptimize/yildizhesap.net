<?php
require_once 'config.php';
require_once 'Auth.php';

$auth = new Auth();

// Kullanıcı oturumunu kontrol et
$sessionToken = $_COOKIE['session_token'] ?? null;

if ($sessionToken) {
    // Çıkış işlemi
    $result = $auth->logout($sessionToken);
    
    // Cookie'yi sil
    setcookie('session_token', '', [
        'expires' => time() - 3600, // Geçmişte bir zaman
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

// Anasayfaya yönlendir
header('Location: index.php?logout=success');
exit;
?>

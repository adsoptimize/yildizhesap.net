<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'functions.php';
require_once 'DatabaseSessionManager.php';
require_once 'includes/PopupAd.php';

// CSRF koruması
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // IP adresi kontrolü
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if ($ipAddress === 'unknown' || empty($ipAddress)) {
        throw new Exception('Invalid IP address');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['action']) || $input['action'] !== 'record_view') {
        throw new Exception('Invalid action');
    }
    
    // Kullanıcı oturum kontrolü (DatabaseSessionManager kullan)
    $sessionManager = new DatabaseSessionManager($pdo);
    $currentUser = null;
    $userId = null;
    
    // Session token kontrolü
    $sessionToken = $_COOKIE['session_token'] ?? null;
    if ($sessionToken) {
        try {
            $sessionResult = $sessionManager->validateSession($sessionToken);
            if ($sessionResult['valid']) {
                $currentUser = $sessionResult['user'];
                $userId = $currentUser['id'];
            }
        } catch (Exception $e) {
            error_log('Session validation error: ' . $e->getMessage());
        }
    }
    
    // Popup görüntüleme kaydı
    $popupAd = new PopupAd($pdo, $siteSettings);
    $result = $popupAd->recordPopupView($userId);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Popup view recorded',
            'user_type' => $userId ? 'registered' : 'visitor',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        throw new Exception('Failed to record popup view');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>

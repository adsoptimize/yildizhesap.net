<?php
require_once 'config.php';
require_once 'Auth.php';
require_once 'OrderManager.php';

header('Content-Type: application/json');

// Kullanıcı kontrolü
$auth = new Auth();
$sessionToken = $_COOKIE['session_token'] ?? null;

if (!$sessionToken) {
    echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı']);
    exit;
}

$sessionResult = $auth->validateSession($sessionToken);
if (!$sessionResult['valid']) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz oturum']);
    exit;
}

$currentUser = $sessionResult['user'];

// Sipariş ID'sini al
$orderId = $_GET['order_id'] ?? '';

if (empty($orderId)) {
    echo json_encode(['success' => false, 'message' => 'Sipariş ID gerekli']);
    exit;
}

try {
    $orderManager = new OrderManager($pdo);
    
    // Siparişin kullanıcıya ait olduğunu kontrol et
    $orderDetails = $orderManager->getOrderDetails($orderId, $currentUser['id']);
    
    if (!$orderDetails) {
        echo json_encode(['success' => false, 'message' => 'Sipariş bulunamadı']);
        exit;
    }
    
    // Sipariş hesaplarını getir
    $accounts = $orderManager->getOrderAccounts($orderId);
    
    echo json_encode([
        'success' => true,
        'order' => $orderDetails,
        'accounts' => $accounts
    ]);
    
} catch (Exception $e) {
    error_log("get_order_accounts.php Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu']);
}
?>

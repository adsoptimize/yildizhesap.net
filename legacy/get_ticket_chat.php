<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'Auth.php';
require_once 'TicketManager.php';

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
    $ticketManager = new TicketManager($pdo);
    
    // Ticket bilgilerini getir
    $ticketInfo = $ticketManager->getOrderTicketInfo($currentUser['id'], $orderId);
    
    if (!$ticketInfo) {
        echo json_encode(['success' => false, 'message' => 'Bu sipariş için ticket bulunamadı']);
        exit;
    }
    
    // Ticket detaylarını getir
    $ticketDetails = $ticketManager->getTicketDetails($ticketInfo['ticket_id'], $currentUser['id']);
    
    if (!$ticketDetails) {
        echo json_encode(['success' => false, 'message' => 'Ticket detayları getirilemedi']);
        exit;
    }
    
    // Ticket yanıtlarını getir
    $messages = $ticketManager->getTicketReplies($ticketInfo['ticket_id'], $currentUser['id']);
    
    // Kullanıcının yanıt verebilip veremeyeceğini kontrol et
    $canReply = $ticketManager->canUserReply($ticketInfo['ticket_id'], $currentUser['id']);
    
    echo json_encode([
        'success' => true,
        'ticket' => $ticketDetails,
        'messages' => $messages,
        'canReply' => $canReply
    ]);
    
} catch (Exception $e) {
    error_log("get_ticket_chat.php Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ticket verileri getirilemedi']);
}
?>

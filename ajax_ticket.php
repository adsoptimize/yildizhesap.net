<?php
// ajax_ticket.php - Ticket AJAX işlemleri
require_once 'config.php';
require_once 'functions.php';
require_once 'Auth.php';
require_once 'TicketManager.php';

// Kullanıcı giriş kontrolü
$auth = new Auth();
$sessionToken = $_COOKIE['session_token'] ?? null;

if (!$sessionToken) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
    exit;
}

$sessionResult = $auth->validateSession($sessionToken);
if (!$sessionResult['valid']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
    exit;
}

$currentUser = $sessionResult['user'];

// CSRF token kontrolü
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Güvenlik hatası.']);
    exit;
}

$ticketManager = new TicketManager($pdo);
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_messages':
            handleGetMessages($ticketManager, $currentUser);
            break;
            
        case 'send_message':
            handleSendMessage($ticketManager, $currentUser);
            break;
            
        case 'create_ticket':
            handleCreateTicket($ticketManager, $currentUser);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
            break;
    }
} catch (Exception $e) {
    error_log("Ticket AJAX error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu.']);
}

function handleGetMessages($ticketManager, $currentUser) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    
    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Sipariş ID gerekli.']);
        return;
    }
    
    // Kullanıcının bu siparişe erişim hakkı olup olmadığını kontrol et
    if (!$ticketManager->canUserAccessOrder($currentUser['id'], $orderId)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bu siparişe erişim yetkiniz yok.']);
        return;
    }
    
    $ticketInfo = $ticketManager->getOrderTicketInfo($currentUser['id'], $orderId);
    $messages = [];
    
    if ($ticketInfo) {
        $messages = $ticketManager->getTicketReplies($ticketInfo['ticket_id'], $currentUser['id']);
    }
    
    echo json_encode([
        'success' => true, 
        'messages' => $messages,
        'ticket_info' => $ticketInfo,
        'can_reply' => $ticketInfo ? $ticketManager->canUserReply($ticketInfo['ticket_id'], $currentUser['id']) : false
    ]);
}

function handleSendMessage($ticketManager, $currentUser) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $message = sanitizeInput($_POST['message'] ?? '');
    
    if (!$orderId || empty($message)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Sipariş ID ve mesaj gerekli.']);
        return;
    }
    
    // Kullanıcının bu siparişe erişim hakkı olup olmadığını kontrol et
    if (!$ticketManager->canUserAccessOrder($currentUser['id'], $orderId)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bu siparişe erişim yetkiniz yok.']);
        return;
    }
    
    $ticketInfo = $ticketManager->getOrderTicketInfo($currentUser['id'], $orderId);
    
    if (!$ticketInfo) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Ticket bulunamadı.']);
        return;
    }
    
    // Kullanıcının cevap verebilip veremeyeceğini kontrol et
    if (!$ticketManager->canUserReply($ticketInfo['ticket_id'], $currentUser['id'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bu ticket\'a cevap veremezsiniz. Admin cevabını bekleyin.']);
        return;
    }
    
    $result = $ticketManager->addReply($ticketInfo['ticket_id'], $currentUser['id'], $message);
    
    if ($result['success']) {
        echo json_encode(['success' => true, 'message' => $result['message']]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
}

function handleCreateTicket($ticketManager, $currentUser) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    
    if (!$orderId || empty($subject) || empty($message)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tüm alanlar gereklidir.']);
        return;
    }
    
    // Kullanıcının bu siparişe erişim hakkı olup olmadığını kontrol et
    if (!$ticketManager->canUserAccessOrder($currentUser['id'], $orderId)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bu siparişe erişim yetkiniz yok.']);
        return;
    }
    
    // Bu sipariş için zaten ticket var mı kontrol et
    $existingTicket = $ticketManager->getOrderTicketInfo($currentUser['id'], $orderId);
    if ($existingTicket) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Bu sipariş için zaten bir destek talebi mevcut.']);
        return;
    }
    
    $result = $ticketManager->createTicket($currentUser['id'], $orderId, $subject, $message);
    
    if ($result['success']) {
        echo json_encode(['success' => true, 'message' => $result['message'], 'ticket_id' => $result['ticket_id']]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
}
?>

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

// POST verilerini kontrol et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Sadece POST istekleri kabul edilir']);
    exit;
}

// CSRF token kontrolü
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrfToken)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz form verisi']);
    exit;
}

// Form verilerini al ve temizle
$ticketId = trim($_POST['ticket_id'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validasyon
$errors = [];

if (empty($ticketId)) {
    $errors[] = 'Ticket ID gerekli';
}

if (empty($message)) {
    $errors[] = 'Mesaj gerekli';
} elseif (strlen($message) < 10) {
    $errors[] = 'Mesaj en az 10 karakter olmalı';
} elseif (strlen($message) > 5000) {
    $errors[] = 'Mesaj en fazla 5000 karakter olabilir';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

try {
    $ticketManager = new TicketManager($pdo);
    
    // Yanıt ekle
    $result = $ticketManager->addReply($ticketId, $currentUser['id'], $message);
    
    if ($result['success']) {
        // Başarılı yanıt
        echo json_encode([
            'success' => true,
            'message' => $result['message']
        ]);
        
        // Log kaydet
        error_log("Ticket reply: User {$currentUser['id']} replied to ticket {$ticketId}");
    } else {
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    error_log("reply_ticket.php Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Yanıt gönderilirken hata oluştu']);
}
?>

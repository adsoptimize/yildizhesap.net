<?php
require_once 'config.php';
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
$orderId = trim($_POST['order_id'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
$priority = trim($_POST['priority'] ?? 'medium');

// Validasyon
$errors = [];

if (empty($orderId)) {
    $errors[] = 'Sipariş ID gerekli';
}

if (empty($subject)) {
    $errors[] = 'Konu başlığı gerekli';
} elseif (strlen($subject) < 5) {
    $errors[] = 'Konu başlığı en az 5 karakter olmalı';
} elseif (strlen($subject) > 255) {
    $errors[] = 'Konu başlığı en fazla 255 karakter olabilir';
}

if (empty($message)) {
    $errors[] = 'Mesaj gerekli';
} elseif (strlen($message) < 10) {
    $errors[] = 'Mesaj en az 10 karakter olmalı';
} elseif (strlen($message) > 5000) {
    $errors[] = 'Mesaj en fazla 5000 karakter olabilir';
}

if (!in_array($priority, ['low', 'medium', 'high', 'urgent'])) {
    $priority = 'medium';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

try {
    $ticketManager = new TicketManager($pdo);
    
    // Ticket oluştur
    $result = $ticketManager->createTicket($currentUser['id'], $orderId, $subject, $message, $priority);
    
    if ($result['success']) {
        // Başarılı yanıt
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'ticket_id' => $result['ticket_id']
        ]);
        
        // Log kaydet
        error_log("Ticket created: User {$currentUser['id']} created ticket {$result['ticket_id']} for order {$orderId}");
    } else {
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    error_log("create_ticket.php Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Destek talebi oluşturulurken hata oluştu']);
}
?>

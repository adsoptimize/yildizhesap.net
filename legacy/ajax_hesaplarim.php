<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'Auth.php';
require_once 'PaymentProcessor.php';

// Output buffering
ob_start();

// AJAX isteği kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Kullanıcı giriş kontrolü
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$currentUser = $auth->getCurrentUser();

// Action kontrolü
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'check_payment_status':
        checkPaymentStatus();
        break;
    case 'get_order_details':
        getOrderDetails();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function checkPaymentStatus() {
    global $pdo, $currentUser;
    
    $order_id = $_POST['order_id'] ?? '';
    
    if (!$order_id) {
        echo json_encode(['error' => 'Order ID required']);
        return;
    }
    
    try {
        // Ödeme durumunu kontrol et
        $stmt = $pdo->prepare("
            SELECT cp.status as payment_status, cp.order_id, o.id as order_id_int, o.quantity, o.product_name
            FROM crypto_payments cp
            LEFT JOIN orders o ON cp.order_id = o.order_id
            WHERE cp.order_id = ? AND cp.user_id = ?
        ");
        $stmt->execute([$order_id, $currentUser['id']]);
        $payment = $stmt->fetch();
        
        if (!$payment) {
            echo json_encode(['error' => 'Order not found']);
            return;
        }
        
        // Eğer ödeme tamamlandıysa ve hesaplar henüz atanmamışsa
        if ($payment['payment_status'] === 'paid') {
            // Hesapların atanıp atanmadığını kontrol et
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM account_stock ast JOIN orders o ON ast.order_id = o.id WHERE o.order_id = ? AND ast.is_sold = 1");
            $stmt->execute([$order_id]);
            $accountCount = $stmt->fetchColumn();
            
            if ($accountCount == 0) {
                // PaymentProcessor ile hesapları ata
                $paymentProcessor = new PaymentProcessor();
                $result = $paymentProcessor->processSuccessfulPayment($order_id);
                
                if ($result['success']) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Hesaplar başarıyla atandı',
                        'payment_status' => $payment['payment_status'],
                        'accounts_assigned' => true
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => $result['message'],
                        'payment_status' => $payment['payment_status'],
                        'accounts_assigned' => false
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => 'Hesaplar zaten atanmış',
                    'payment_status' => $payment['payment_status'],
                    'accounts_assigned' => true,
                    'account_count' => $accountCount
                ]);
            }
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'Ödeme henüz tamamlanmadı',
                'payment_status' => $payment['payment_status'],
                'accounts_assigned' => false
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Payment status check error: " . $e->getMessage());
        echo json_encode(['error' => 'Internal server error']);
    }
}

function getOrderDetails() {
    global $pdo, $currentUser;
    
    $order_id = $_POST['order_id'] ?? '';
    
    if (!$order_id) {
        echo json_encode(['error' => 'Order ID required']);
        return;
    }
    
    try {
        // Sipariş detaylarını al
        $stmt = $pdo->prepare("
            SELECT o.*, cp.status as payment_status, cp.order_id as payment_order_id,
                   COUNT(CASE WHEN ast.is_sold = 1 THEN ast.id END) as account_count
            FROM orders o
            LEFT JOIN crypto_payments cp ON o.order_id COLLATE utf8mb4_unicode_ci = cp.order_id COLLATE utf8mb4_unicode_ci
            LEFT JOIN account_stock ast ON o.id = ast.order_id
            WHERE o.order_id = ? AND o.user_id = ?
            GROUP BY o.id
        ");
        $stmt->execute([$order_id, $currentUser['id']]);
        $order = $stmt->fetch();
        
        if (!$order) {
            echo json_encode(['error' => 'Order not found']);
            return;
        }
        
        // Hesap detaylarını al
        $stmt = $pdo->prepare("
            SELECT ast.username, ast.password, ast.email, ast.additional_info, ast.account_data
            FROM account_stock ast
            JOIN orders o ON ast.order_id = o.id
            WHERE o.order_id = ? AND ast.is_sold = 1
            ORDER BY ast.id
        ");
        $stmt->execute([$order_id]);
        $accounts = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'order' => $order,
            'accounts' => $accounts
        ]);
        
    } catch (Exception $e) {
        error_log("Order details error: " . $e->getMessage());
        echo json_encode(['error' => 'Internal server error']);
    }
}

// Output buffering'i temizle ve gönder
ob_end_flush();
?> 
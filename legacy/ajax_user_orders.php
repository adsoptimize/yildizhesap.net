<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'Auth.php';
require_once 'OrderManager.php';
require_once 'TicketManager.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// CSRF kontrolü
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'CSRF token hatası']);
        exit;
    }
}

// Session kontrolü
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

try {
    $orderManager = new OrderManager($pdo);
    $ticketManager = new TicketManager($pdo);
    
    // Parametreleri al
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(50, intval($_GET['limit'] ?? 10))); // Min 1, Max 50
    $offset = ($page - 1) * $limit;
    
    // Siparişleri getir
    $orders = $orderManager->getUserOrders($currentUser['id'], $limit, $offset);
    $totalOrders = $orderManager->getUserOrderCount($currentUser['id']);
    $totalPages = ceil($totalOrders / $limit);
    
    // Her sipariş için ticket bilgilerini getir
    $ordersWithTickets = [];
    foreach ($orders as $order) {
        $ticketInfo = $ticketManager->getOrderTicketInfo($currentUser['id'], $order['order_id']);
        $order['ticket_info'] = $ticketInfo;
        $ordersWithTickets[] = $order;
    }
    
    // HTML oluştur
    $ordersHtml = '';
    foreach ($ordersWithTickets as $order) {
        $ticketInfo = $order['ticket_info'];
        $hasTicket = !empty($ticketInfo);
        $adminReplies = $hasTicket ? $ticketInfo['admin_replies_count'] : 0;
        
        // Durum metni
        $statusText = '';
        switch($order['status']) {
            case 'completed': $statusText = 'Tamamlandı'; break;
            case 'processing': $statusText = 'İşleniyor'; break;
            case 'pending': $statusText = 'Bekliyor'; break;
            case 'cancelled': $statusText = 'İptal Edildi'; break;
            default: $statusText = 'Bilinmiyor';
        }
        
        $ordersHtml .= '<tr>
            <td>
                <span class="order-id">' . htmlspecialchars($order['order_id']) . '</span>
            </td>
            <td>
                <span class="product-name">' . htmlspecialchars($order['product_name']) . '</span>
            </td>
            <td>
                <span class="category-text">' . htmlspecialchars($order['category']) . '</span>
            </td>
            <td>
                <span class="quantity">' . number_format($order['quantity']) . '</span>
            </td>
            <td>
                <span class="total-price">' . formatPrice($order['total_price']) . '</span>
            </td>
            <td>
                <span class="order-date">' . date('d.m.Y H:i', strtotime($order['order_date'])) . '</span>
            </td>
            <td>
                <span class="status-badge status-' . $order['status'] . '">' . $statusText . '</span>
            </td>
            <td>
                <div class="action-buttons">
                    <button class="btn-view" 
                        data-order-id="' . $order['order_id'] . '" 
                        data-product-name="' . htmlspecialchars($order['product_name']) . '" 
                        data-quantity="' . $order['quantity'] . '" 
                        data-total-price="' . $order['total_price'] . '" 
                        onclick="viewOrderDetailsFromButton(this)" 
                        title="Detayları Görüntüle">
                        <i class="fas fa-eye"></i>
                    </button>';
        
        if ($order['delivery_status'] === 'delivered') {
            $ordersHtml .= '<button class="btn-download" onclick="downloadOrder(\'' . $order['order_id'] . '\')" title="İndir">
                        <i class="fas fa-download"></i>
                    </button>';
        }
        
        $ordersHtml .= '<button class="btn-support ' . ($hasTicket ? 'has-ticket' : '') . '" 
                        onclick="contactSupport(\'' . $order['order_id'] . '\')" 
                        data-order-id="' . $order['order_id'] . '"
                        data-has-ticket="' . ($hasTicket ? 'true' : 'false') . '"
                        data-ticket-id="' . ($hasTicket ? $ticketInfo['ticket_id'] : '') . '"
                        title="' . ($hasTicket ? 'Destek Sohbeti' : 'Destek Talebi Oluştur') . '">
                        <i class="fas fa-' . ($hasTicket ? 'comments' : 'headset') . '"></i>';
        
        if ($adminReplies > 0) {
            $ordersHtml .= '<span class="reply-count">' . $adminReplies . '</span>';
        }
        
        $ordersHtml .= '</button>
                </div>
            </td>
        </tr>';
    }
    
    echo json_encode([
        'success' => true,
        'html' => $ordersHtml,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_orders' => $totalOrders,
            'per_page' => $limit,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ]
    ]);
    
} catch (Exception $e) {
    error_log("AJAX User Orders Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Siparişler yüklenirken hata oluştu']);
}
?>

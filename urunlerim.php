<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'Auth.php';
require_once 'OrderManager.php';
require_once 'TicketManager.php';

$page_title = "Ürünlerim";

// Cryptomus'tan gelen istekleri kontrol et
$isFromCryptomus = false;
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$currentPage = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($referer, 'cryptomus') !== false || strpos($referer, 'cryptomus.com') !== false || 
    strpos($currentPage, 'payment-cancel.php') !== false || strpos($currentPage, 'payment-success.php') !== false) {
    $isFromCryptomus = true;
}

// Kullanıcı kontrolü
$auth = new Auth();

if ($isFromCryptomus) {
    // Cryptomus'tan geliyorsa veya ödeme sayfalarındaysa, session kontrolünü atla
    // Ama kullanıcı bilgisini order_id'den almaya çalış
    $order_id = $_GET['order_id'] ?? '';
    if ($order_id) {
        $stmt = $pdo->prepare("SELECT user_id FROM crypto_payments WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $payment = $stmt->fetch();
        
        if ($payment) {
            $stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name FROM users WHERE id = ?");
            $stmt->execute([$payment['user_id']]);
            $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    
    // Eğer kullanıcı bilgisi alınamadıysa, normal session kontrolü yap
    if (!$currentUser) {
        $sessionToken = $_COOKIE['session_token'] ?? null;
        if (!$sessionToken) {
            header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
        $sessionResult = $auth->validateSession($sessionToken);
        if (!$sessionResult['valid']) {
            header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
        $currentUser = $sessionResult['user'];
    }
} else {
    // Normal istek için session kontrolü yap
    $sessionToken = $_COOKIE['session_token'] ?? null;
    if (!$sessionToken) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    $sessionResult = $auth->validateSession($sessionToken);
    if (!$sessionResult['valid']) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    $currentUser = $sessionResult['user'];
}

// OrderManager örneği oluştur
$orderManager = new OrderManager($pdo);
$ticketManager = new TicketManager($pdo);

// Sipariş istatistiklerini getir
$orderStats = $orderManager->getUserOrderStats($currentUser['id']);

// İlk sayfa için siparişleri getir (pagination için)
$initialLimit = 10;
$userOrders = $orderManager->getUserOrders($currentUser['id'], $initialLimit, 0);
$totalOrders = $orderManager->getUserOrderCount($currentUser['id']);
$totalPages = ceil($totalOrders / $initialLimit);

// İlk sayfa için ticket bilgilerini getir
$orderTickets = [];
foreach($userOrders as $order) {
    $ticketInfo = $ticketManager->getOrderTicketInfo($currentUser['id'], $order['order_id']);
    $orderTickets[$order['order_id']] = $ticketInfo;
}

// CSRF token oluştur
$csrfToken = generateCSRFToken();

include 'header.php';
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1><i class="fas fa-shopping-bag"></i> Ürünlerim</h1>
        <p>Sipariş geçmişinizi ve detaylarını buradan görüntüleyebilirsiniz.</p>
    </div>
</section>

<!-- Orders Section -->
<section class="orders-section">
    <div class="container">
        <?php if ($totalOrders === 0): ?>
        <div class="no-orders">
            <i class="fas fa-shopping-bag"></i>
            <h3>Henüz Sipariş Vermemişsiniz</h3>
            <p>İlk siparişinizi vermek için hesaplarımızı inceleyin.</p>
            <a href="hesaplar.php" class="btn-primary">Hesapları İncele</a>
        </div>
        <?php else: ?>
        <div class="orders-table-container">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Sipariş No</th>
                        <th>Ürün Adı</th>
                        <th>Kategori</th>
                        <th>Adet</th>
                        <th>Teslim Edilen</th>
                        <th>Toplam</th>
                        <th>Tarih</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody id="orders-table-body">
                    <?php foreach ($userOrders as $order): ?>
                    <tr>
                        <td>
                            <span class="order-id"><?php echo htmlspecialchars($order['order_id']); ?></span>
                        </td>
                        <td>
                            <span class="product-name"><?php echo htmlspecialchars($order['product_name']); ?></span>
                        </td>
                        <td>
                            <span class="category-text"><?php echo htmlspecialchars($order['category']); ?></span>
                        </td>
                        <td>
                            <span class="quantity"><?php echo number_format($order['quantity']); ?></span>
                        </td>
                        <td>
                            <span class="delivered-accounts"><?php echo ($order['account_count'] ?? 0) . ' / ' . $order['quantity']; ?></span>
                        </td>
                        <td>
                            <span class="total-price"><?php echo formatPrice($order['total_price']); ?></span>
                        </td>
                        <td>
                            <span class="order-date"><?php echo date('d.m.Y H:i', strtotime($order['order_date'])); ?></span>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php 
                                switch($order['status']) {
                                    case 'completed': echo 'Tamamlandı'; break;
                                    case 'processing': echo 'İşleniyor'; break;
                                    case 'pending': echo 'Bekliyor'; break;
                                    case 'cancelled': echo 'İptal Edildi'; break;
                                    default: echo 'Bilinmiyor';
                                }
                                ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-view" 
                                    data-order-id="<?php echo $order['order_id']; ?>" 
                                    data-product-name="<?php echo htmlspecialchars($order['product_name']); ?>" 
                                    data-quantity="<?php echo $order['quantity']; ?>" 
                                    data-total-price="<?php echo $order['total_price']; ?>" 
                                    onclick="viewOrderDetailsFromButton(this)" 
                                    title="Detayları Görüntüle">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($order['delivery_status'] === 'delivered'): ?>
                                <button class="btn-download" onclick="downloadOrder('<?php echo $order['order_id']; ?>')" title="İndir">
                                    <i class="fas fa-download"></i>
                                </button>
                                <?php endif; ?>
                                <?php 
                                $ticketInfo = $orderTickets[$order['order_id']];
                                $hasTicket = !empty($ticketInfo);
                                $adminReplies = $hasTicket ? $ticketInfo['admin_replies_count'] : 0;
                                ?>
                                <button class="btn-support <?php echo $hasTicket ? 'has-ticket' : ''; ?>" 
                                    onclick="contactSupport('<?php echo $order['order_id']; ?>')" 
                                    data-order-id="<?php echo $order['order_id']; ?>"
                                    data-has-ticket="<?php echo $hasTicket ? 'true' : 'false'; ?>"
                                    data-ticket-id="<?php echo $hasTicket ? $ticketInfo['ticket_id'] : ''; ?>"
                                    title="<?php echo $hasTicket ? 'Destek Sohbeti' : 'Destek Talebi Oluştur'; ?>">
                                    <i class="fas fa-<?php echo $hasTicket ? 'comments' : 'headset'; ?>"></i>
                                    <?php if ($adminReplies > 0): ?>
                                    <span class="reply-count"><?php echo $adminReplies; ?></span>
                                    <?php endif; ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                <span id="pagination-info">Sayfa 1 / <?php echo $totalPages; ?> (Toplam <?php echo $totalOrders; ?> sipariş)</span>
            </div>
            <div class="pagination-controls">
                <button id="prevPageBtn" class="pagination-btn" disabled onclick="loadPage('prev')">
                    <i class="fas fa-chevron-left"></i> Önceki
                </button>
                <span id="pageNumbers" class="page-numbers">
                    <!-- Dinamik olarak doldurulacak -->
                </span>
                <button id="nextPageBtn" class="pagination-btn" onclick="loadPage('next')">
                    Sonraki <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="pagination-loader" id="paginationLoader" style="display: none;">
                <i class="fas fa-spinner fa-spin"></i> Yükleniyor...
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Sipariş Özeti -->
        <div class="order-summary">
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="summary-info">
                        <h4>Toplam Sipariş</h4>
                        <span class="summary-value"><?php echo $orderStats['total_orders']; ?></span>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="summary-info">
                        <h4>Toplam Harcama</h4>
                        <span class="summary-value"><?php echo formatPrice($orderStats['total_spent'] ?? 0); ?></span>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="summary-info">
                        <h4>Tamamlanan</h4>
                        <span class="summary-value"><?php echo $orderStats['completed_orders']; ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Sipariş Detay Modal -->
<div id="orderDetailsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-list-alt"></i> Sipariş Detayları</h3>
            <span class="close-modal" onclick="closeOrderModal()">&times;</span>
        </div>
        
        <div class="modal-body">
            <div class="order-info">
                <div class="info-grid">
                    <div class="info-item">
                        <label>Sipariş No:</label>
                        <span id="modal-order-id">-</span>
                    </div>
                    <div class="info-item">
                        <label>Ürün Adı:</label>
                        <span id="modal-product-name">-</span>
                    </div>
                    <div class="info-item">
                        <label>Toplam Adet:</label>
                        <span id="modal-quantity">-</span>
                    </div>
                    <div class="info-item">
                        <label>Teslim Edilen:</label>
                        <span id="modal-delivered-accounts">-</span>
                    </div>
                    <div class="info-item">
                        <label>Toplam Tutar:</label>
                        <span id="modal-total-price">-</span>
                    </div>
                </div>
            </div>
            
            <div class="accounts-section">
                <div class="section-header">
                    <h4><i class="fas fa-users"></i> Hesap Listesi</h4>
                    <div class="bulk-actions">
                        <button class="btn-copy-all" onclick="copyAllAccounts()">
                            <i class="fas fa-copy"></i> Tümünü Kopyala
                        </button>
                        <button class="btn-download-all" onclick="downloadAllAccounts()">
                            <i class="fas fa-download"></i> Excel İndir
                        </button>
                        <button class="btn-download-txt" onclick="downloadTxtAccounts()">
                            <i class="fas fa-file-alt"></i> TXT İndir
                        </button>
                    </div>
                </div>
                
                <div class="accounts-container" id="accounts-container">
                    <!-- Dinamik olarak doldurulacak -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Destek Modal -->
<div id="supportModal" class="modal">
    <div class="modal-content support-modal">
        <div class="modal-header">
            <h3 id="support-modal-title"><i class="fas fa-headset"></i> Destek Talebi Oluştur</h3>
            <span class="close-modal" onclick="closeSupportModal()">&times;</span>
        </div>
        
        <div class="modal-body">
            <!-- Sipariş Bilgileri -->
            <div class="form-group">
                <label for="order_info">Sipariş Bilgileri:</label>
                <div class="order-info-display" id="order_info_display">
                    <!-- Dinamik olarak doldurulacak -->
                </div>
            </div>
            
            <!-- Mevcut Ticket Sohbeti -->
            <div id="ticket-chat-section" style="display: none;">
                <div class="chat-header">
                    <h4 id="ticket-subject">Ticket Başlığı</h4>
                    <span id="ticket-status" class="status-badge">Açık</span>
                </div>
                
                <div class="chat-messages" id="chat-messages">
                    <!-- Mesajlar buraya yüklenecek -->
                </div>
                
                <!-- Yanıt Formu -->
                <div id="reply-form-section">
                    <form id="replyForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="ticket_id" id="reply_ticket_id">
                        
                        <div class="form-group">
                            <label for="reply_message">Yanıtınız:</label>
                            <textarea id="reply_message" name="message" rows="4" maxlength="5000" required></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn-cancel" onclick="closeSupportModal()">İptal</button>
                            <button type="submit" class="btn-submit">Yanıt Gönder</button>
                        </div>
                    </form>
                </div>
                
                <div id="no-reply-message" style="display: none;">
                    <p class="info-message">Destek ekibimizden yanıt gelmeden yeni mesaj gönderemezsiniz.</p>
                </div>
            </div>
            
            <!-- Yeni Ticket Formu -->
            <div id="new-ticket-section">
                <form id="supportForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="order_id" id="support_order_id">
                    
                    <div class="form-group">
                        <label for="subject">Konu Başlığı: <span class="required">*</span></label>
                        <input type="text" id="subject" name="subject" maxlength="255" required>
                        <small>En az 5, en fazla 255 karakter</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="priority">Öncelik:</label>
                        <select id="priority" name="priority">
                            <option value="low">Düşük</option>
                            <option value="medium" selected>Orta</option>
                            <option value="high">Yüksek</option>
                            <option value="urgent">Acil</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Mesajınız: <span class="required">*</span></label>
                        <textarea id="message" name="message" rows="6" maxlength="5000" required></textarea>
                        <small>En az 10, en fazla 5000 karakter</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="closeSupportModal()">İptal</button>
                        <button type="submit" class="btn-submit">Destek Talebi Oluştur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Orders Section Styles */
.orders-section {
    padding: 40px 0;
    min-height: 60vh;
}

.no-orders {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 80px 20px;
    color: var(--gray);
}

.no-orders i {
    font-size: 4rem;
    color: var(--accent);
    margin-bottom: 20px;
}

.no-orders h3 {
    font-size: 1.5rem;
    color: var(--light);
    margin-bottom: 15px;
}

.no-orders p {
    font-size: 1rem;
    margin-bottom: 30px;
    opacity: 0.8;
    max-width: 400px;
}

/* Tablo Stilleri */
.orders-table-container {
    background: var(--card-bg);
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid var(--card-border);
    box-shadow: var(--inner-shadow);
    margin-bottom: 30px;
}

.orders-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}

.orders-table thead {
    background: linear-gradient(135deg, var(--primary), var(--accent));
    color: white;
}

.orders-table th {
    padding: 15px 12px;
    text-align: left;
    font-weight: 600;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
}

.orders-table tbody tr {
    transition: var(--transition);
    border-bottom: 1px solid var(--card-border);
}

.orders-table tbody tr:hover {
    background: rgba(108, 99, 255, 0.05);
}

.orders-table td {
    padding: 15px 12px;
    vertical-align: middle;
    color: var(--light);
}

/* Badge Stilleri */
.order-id {
    font-family: 'Courier New', monospace;
    font-weight: 700;
    color: var(--accent);
    background: rgba(108, 99, 255, 0.1);
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.8rem;
}

.product-name {
    font-weight: 600;
    color: var(--light);
}

.category-text {
    color: var(--light);
    font-weight: 500;
    font-size: 0.9rem;
}

.quantity {
    font-weight: 700;
    color: var(--accent);
    font-size: 1rem;
}

.total-price {
    font-weight: 700;
    color: var(--primary);
    font-family: 'Courier New', monospace;
    font-size: 1.1rem;
}

.order-date {
    color: var(--gray);
    font-size: 0.85rem;
}

.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-completed {
    background: rgba(34, 197, 94, 0.2);
    color: #22c55e;
    border: 1px solid #22c55e;
}

.status-processing {
    background: rgba(59, 130, 246, 0.2);
    color: #3b82f6;
    border: 1px solid #3b82f6;
}

.status-pending {
    background: rgba(245, 158, 11, 0.2);
    color: #f59e0b;
    border: 1px solid #f59e0b;
}

.status-cancelled {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
    border: 1px solid #ef4444;
}


/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.btn-view, .btn-download, .btn-support, .btn-support-disabled {
    background: none;
    border: 1px solid var(--card-border);
    color: var(--gray);
    padding: 8px 10px;
    border-radius: 8px;
    cursor: pointer;
    transition: var(--transition);
    font-size: 0.9rem;
}

.btn-view:hover {
    background: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
    border-color: #3b82f6;
}

.btn-download:hover {
    background: rgba(34, 197, 94, 0.1);
    color: #22c55e;
    border-color: #22c55e;
}

.btn-support:hover {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
    border-color: #f59e0b;
}

.btn-support-disabled {
    opacity: 0.5;
    cursor: not-allowed;
    color: #22c55e;
    border-color: #22c55e;
    background: rgba(34, 197, 94, 0.1);
}

.btn-support-disabled:hover {
    opacity: 0.5;
    cursor: not-allowed;
    color: #22c55e;
    border-color: #22c55e;
    background: rgba(34, 197, 94, 0.1);
}

.btn-support.has-ticket {
    background: rgba(34, 197, 94, 0.1);
    color: #22c55e;
    border-color: #22c55e;
    position: relative;
}

.btn-support.has-ticket:hover {
    background: rgba(34, 197, 94, 0.2);
    color: #16a34a;
    border-color: #16a34a;
}

.reply-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #ef4444;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 10px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid var(--card-bg);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* Sipariş Özet Kartları */
.order-summary {
    margin-top: 40px;
}

.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.summary-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 16px;
    padding: 25px;
    display: flex;
    align-items: center;
    gap: 20px;
    transition: var(--transition);
    box-shadow: var(--inner-shadow);
}

.summary-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(108, 99, 255, 0.15);
}

.summary-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--primary), var(--accent));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.summary-info h4 {
    font-size: 0.9rem;
    color: var(--gray);
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.summary-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--light);
    font-family: 'Courier New', monospace;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .orders-table {
        font-size: 0.8rem;
    }
    
    .orders-table th,
    .orders-table td {
        padding: 10px 8px;
    }
}

@media (max-width: 768px) {
    .orders-table-container {
        overflow-x: auto;
    }
    
    .orders-table {
        min-width: 800px;
    }
    
    .summary-cards {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 5px;
    }
    
    .btn-view, .btn-download, .btn-support {
        padding: 6px 8px;
        font-size: 0.8rem;
    }
}

/* Modal Stilleri */
.modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
    animation: fadeIn 0.3s ease;
}

.modal-content {
    background: var(--card-bg);
    margin: 2% auto;
    padding: 0;
    border: 1px solid var(--card-border);
    border-radius: 16px;
    width: 95%;
    max-width: 1400px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    animation: slideIn 0.3s ease;
}

.modal-header {
    background: linear-gradient(135deg, var(--primary), var(--accent));
    color: white;
    padding: 20px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--card-border);
}

.modal-header h3 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.close-modal {
    color: white;
    font-size: 32px;
    font-weight: bold;
    cursor: pointer;
    transition: var(--transition);
    line-height: 1;
    padding: 5px;
    border-radius: 50%;
}

.close-modal:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: rotate(90deg);
}

.modal-body {
    padding: 25px;
    max-height: 70vh;
    overflow-y: auto;
}

/* Sipariş Bilgileri */
.order-info {
    background: rgba(108, 99, 255, 0.05);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
    border: 1px solid rgba(108, 99, 255, 0.1);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.info-item label {
    font-weight: 600;
    color: var(--gray);
    font-size: 0.9rem;
}

.info-item span {
    font-weight: 700;
    color: var(--light);
    font-size: 1rem;
}

/* Hesaplar Bölümü */
.accounts-section {
    margin-top: 25px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--card-border);
}

.section-header h4 {
    margin: 0;
    color: var(--light);
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.bulk-actions {
    display: flex;
    gap: 10px;
}

.btn-copy-all, .btn-download-all, .btn-download-txt {
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-copy-all {
    background: linear-gradient(45deg, #3b82f6, #1d4ed8);
    color: white;
}

.btn-copy-all:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3);
}

.btn-download-all {
    background: linear-gradient(45deg, #22c55e, #16a34a);
    color: white;
}

.btn-download-all:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(34, 197, 94, 0.3);
}

.btn-download-txt {
    background: linear-gradient(45deg, #f59e0b, #d97706);
    color: white;
}

.btn-download-txt:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(245, 158, 11, 0.3);
}

/* Hesap Kartları */
.accounts-container {
    display: grid;
    gap: 1rem;
}

.account-card {
    background: var(--dark);
    border-radius: 8px;
    padding: 1rem;
    border: 1px solid var(--card-border);
    transition: background-color 0.2s;
}

.account-card:hover {
    background: rgba(255, 255, 255, 0.05);
}

.account-row {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.account-number {
    color: var(--primary-color);
    font-weight: 600;
    font-size: 0.9rem;
    min-width: 80px;
}

.account-info {
    flex: 1;
    color: var(--light);
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 4px;
    transition: background-color 0.2s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.account-info:hover {
    background: rgba(255, 255, 255, 0.1);
}

.account-date {
    color: var(--gray);
    font-size: 0.8rem;
    min-width: 100px;
    text-align: right;
}

.copy-icon {
    color: #3b82f6;
    font-size: 0.8rem;
    opacity: 0.7;
    transition: opacity 0.2s;
}

.account-info:hover .copy-icon {
    opacity: 1;
}

.empty-accounts {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--gray);
}

.empty-accounts i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-accounts h3 {
    margin: 0 0 0.5rem 0;
    color: var(--light);
}

.empty-accounts p {
    margin: 0;
    font-size: 0.9rem;
}
    white-space: nowrap;
}

.accounts-table tbody tr {
    transition: var(--transition);
    border-bottom: 1px solid var(--card-border);
}

.accounts-table tbody tr:hover {
    background: rgba(108, 99, 255, 0.08);
}

.accounts-table td {
    padding: 10px 8px;
    vertical-align: middle;
    color: var(--light);
    font-size: 0.8rem;
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Giriş Yap Badge */
.login-badge {
    background: linear-gradient(45deg, #22c55e, #16a34a);
    color: white;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Kod Al Butonu */
.code-btn {
    background: linear-gradient(45deg, #3b82f6, #1d4ed8);
    color: white;
    padding: 4px 8px;
    border: none;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    text-transform: uppercase;
}

.code-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 3px 10px rgba(59, 130, 246, 0.3);
}

/* Kopyalanabilir Alanlar */
.copyable-field {
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 6px;
    transition: var(--transition);
    position: relative;
    background: rgba(108, 99, 255, 0.1);
    border: 1px solid rgba(108, 99, 255, 0.2);
}

.copyable-field:hover {
    background: rgba(108, 99, 255, 0.2);
    transform: scale(1.02);
}

.copy-icon {
    position: absolute;
    right: 5px;
    top: 50%;
    transform: translateY(-50%);
    opacity: 0;
    transition: var(--transition);
    color: var(--accent);
    font-size: 0.7rem;
}

.copyable-field:hover .copy-icon {
    opacity: 1;
}

/* Animasyonlar */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-50px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Responsive Modal */
@media (max-width: 768px) {
    .modal-content {
        width: 98%;
        margin: 1% auto;
        max-height: 95vh;
    }
    
    .modal-header {
        padding: 15px 20px;
    }
    
    .modal-body {
        padding: 20px 15px;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .section-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
    
    .bulk-actions {
        width: 100%;
        justify-content: space-between;
    }
    
    .accounts-table-container {
        overflow-x: auto;
    }
    
    .accounts-table {
        min-width: 700px;
    }
}

/* Destek Modal Stilleri */
.support-modal {
    max-width: 600px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: var(--light);
    font-size: 0.9rem;
}

.required {
    color: #ef4444;
    font-weight: 700;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--card-border);
    border-radius: 8px;
    background: var(--dark);
    color: var(--light);
    font-size: 0.9rem;
    transition: var(--transition);
    font-family: inherit;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 2px rgba(108, 99, 255, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: var(--gray);
    font-size: 0.8rem;
}

.order-info-display {
    background: rgba(108, 99, 255, 0.1);
    border: 1px solid rgba(108, 99, 255, 0.2);
    border-radius: 8px;
    padding: 12px;
    font-size: 0.9rem;
    color: var(--light);
}

.order-info-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
}

.order-info-item:last-child {
    margin-bottom: 0;
}

.order-info-label {
    font-weight: 600;
    color: var(--gray);
}

.order-info-value {
    color: var(--light);
}

.form-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid var(--card-border);
}

.btn-cancel,
.btn-submit {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    min-width: 120px;
}

.btn-cancel {
    background: var(--card-bg);
    color: var(--gray);
    border: 1px solid var(--card-border);
}

.btn-cancel:hover {
    background: var(--dark);
    color: var(--light);
}

.btn-submit {
    background: linear-gradient(45deg, var(--primary), var(--accent));
    color: white;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(108, 99, 255, 0.3);
}

.btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* Loading state */
.form-loading {
    position: relative;
    overflow: hidden;
}

.form-loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

/* Chat Stilleri */
.chat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid var(--card-border);
    margin-bottom: 20px;
}

.chat-header h4 {
    margin: 0;
    color: var(--light);
    font-size: 1.1rem;
}

.chat-messages {
    max-height: 400px;
    overflow-y: auto;
    padding: 10px 0;
    margin-bottom: 20px;
    border: 1px solid var(--card-border);
    border-radius: 8px;
    background: var(--dark);
}

.message-item {
    margin-bottom: 15px;
    padding: 12px 15px;
}

.message-item.admin {
    background: rgba(34, 197, 94, 0.1);
    border-left: 4px solid #22c55e;
    margin-left: 0;
    margin-right: 20px;
}

.message-item.user {
    background: rgba(108, 99, 255, 0.1);
    border-left: 4px solid var(--accent);
    margin-left: 20px;
    margin-right: 0;
}

.message-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    font-size: 0.8rem;
    opacity: 0.8;
}

.message-author {
    font-weight: 600;
    color: var(--light);
}

.message-date {
    color: var(--gray);
}

.message-content {
    color: var(--light);
    line-height: 1.5;
    word-wrap: break-word;
}

.info-message {
    text-align: center;
    color: var(--gray);
    font-style: italic;
    padding: 20px;
    background: rgba(245, 158, 11, 0.1);
    border-radius: 8px;
    border: 1px solid rgba(245, 158, 11, 0.2);
}

.chat-loading {
    text-align: center;
    padding: 40px;
    color: var(--gray);
}

    .chat-loading i {
        font-size: 2rem;
        margin-bottom: 10px;
        color: var(--accent);
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    /* Pagination Stilleri */
    .pagination-container {
        margin-top: 30px;
        padding: 20px;
        background: var(--card-bg);
        border-radius: 12px;
        border: 1px solid var(--card-border);
        text-align: center;
    }
    
    .pagination-info {
        margin-bottom: 15px;
        color: var(--gray);
        font-size: 0.9rem;
    }
    
    .pagination-controls {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .pagination-btn {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        color: var(--light);
        padding: 8px 16px;
        border-radius: 8px;
        cursor: pointer;
        transition: var(--transition);
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .pagination-btn:hover:not(:disabled) {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
        transform: translateY(-1px);
    }
    
    .pagination-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        color: var(--gray);
    }
    
    .page-numbers {
        display: flex;
        align-items: center;
        gap: 5px;
        flex-wrap: wrap;
    }
    
    .page-number {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        color: var(--light);
        padding: 6px 12px;
        border-radius: 6px;
        cursor: pointer;
        transition: var(--transition);
        font-size: 0.9rem;
        min-width: 35px;
        text-align: center;
    }
    
    .page-number:hover {
        background: var(--accent);
        border-color: var(--accent);
        color: white;
    }
    
    .page-number.active {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
        font-weight: 600;
    }
    
    .page-ellipsis {
        color: var(--gray);
        padding: 6px 8px;
        font-size: 0.9rem;
    }
    
    .pagination-loader {
        margin-top: 15px;
        color: var(--accent);
        font-size: 0.9rem;
    }
    
    .pagination-loader i {
        margin-right: 8px;
        color: var(--accent);
    }

/* Responsive */
@media (max-width: 768px) {
    .support-modal {
        width: 95%;
        max-width: none;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn-cancel,
    .btn-submit {
        width: 100%;
    }
    
    .chat-messages {
        max-height: 300px;
    }
    
    .message-item.admin {
        margin-right: 10px;
    }
    
    .message-item.user {
        margin-left: 10px;
    }
    
    .pagination-container {
        margin-top: 20px;
        padding: 15px;
    }
    
    .pagination-controls {
        flex-direction: column;
        gap: 15px;
    }
    
    .page-numbers {
        justify-content: center;
    }
    
    .pagination-btn {
        width: 100%;
        max-width: 200px;
        justify-content: center;
    }
}
</style>
<script>
// Currency formatting function for JavaScript
function formatCurrency(amount) {
    // Get currency from PHP - we'll use a simple approach for now
    // You can enhance this by passing the currency from PHP to JavaScript
    const currency = '<?php echo getDefaultCurrency(); ?>';
    const symbols = {
        'TRY': '₺',
        'USD': '$'
    };
    const symbol = symbols[currency] || '$';
    return symbol + parseFloat(amount).toFixed(2);
}

// Data attribute'den veri alıp modal aç
function viewOrderDetailsFromButton(button) {
    const orderId = button.getAttribute('data-order-id');
    const productName = button.getAttribute('data-product-name');
    const quantity = button.getAttribute('data-quantity');
    const total = button.getAttribute('data-total-price');
    
    // AJAX ile hesapları getir
    fetchOrderAccounts(orderId, productName, quantity, total);
}

// AJAX ile sipariş hesaplarını getir
function fetchOrderAccounts(orderId, productName, quantity, total) {
    try {
        showNotification('Hesaplar yükleniyor...', 'info');
        
        fetch(`get_order_accounts.php?order_id=${encodeURIComponent(orderId)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    viewOrderDetails(orderId, productName, quantity, total, data.accounts);
                } else {
                    showNotification(data.message || 'Hesaplar yüklenirken hata oluştu', 'error');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showNotification('Hesaplar yüklenirken hata oluştu', 'error');
            });
    } catch (error) {
        console.error('fetchOrderAccounts error:', error);
        showNotification('Bir hata oluştu', 'error');
    }
}

// Global değişken
let currentOrderId = '';

// Sipariş detaylarını görüntüle
function viewOrderDetails(orderId, productName, quantity, total, accounts) {
    currentOrderId = orderId;
    try {
        // Modal bilgilerini ayarla
        const modalOrderId = document.getElementById('modal-order-id');
        const modalProductName = document.getElementById('modal-product-name');
        const modalQuantity = document.getElementById('modal-quantity');
        const modalDeliveredAccounts = document.getElementById('modal-delivered-accounts');
        const modalTotalPrice = document.getElementById('modal-total-price');
        
        if (modalOrderId) modalOrderId.textContent = orderId;
        if (modalProductName) modalProductName.textContent = productName;
        if (modalQuantity) modalQuantity.textContent = quantity;
        if (modalDeliveredAccounts) modalDeliveredAccounts.textContent = `${accounts ? accounts.length : 0} / ${quantity}`;
        if (modalTotalPrice) modalTotalPrice.textContent = formatCurrency(parseFloat(total));

        // Hesapları kartlara ekle
        const accountsContainer = document.getElementById('accounts-container');
        if (accountsContainer) {
            accountsContainer.innerHTML = '';
            
            if (accounts && accounts.length > 0) {
                accounts.forEach((account, index) => {
                    const createdDate = account.created_at ? new Date(account.created_at).toLocaleDateString('tr-TR') : '19/01/2025';
                    
                    const accountCard = document.createElement('div');
                    accountCard.className = 'account-card';
                    
                    // Hesap bilgilerini tek satırda birleştir
                    let accountInfo = `${account.username || ''}:${account.password || ''}`;
                    if (account.email && account.email !== 'N/A') accountInfo += `:${account.email}`;
                    if (account.additional_info && account.additional_info !== 'N/A') accountInfo += `:${account.additional_info}`;
                    if (account.account_data && account.account_data !== 'N/A') accountInfo += `:${account.account_data}`;
                    
                    accountCard.innerHTML = `
                        <div class="account-row">
                            <span class="account-number">HESAP${index + 1}</span>
                            <span class="account-info copyable">${accountInfo} <i class="fas fa-copy copy-icon"></i></span>
                            <span class="account-date">${createdDate}</span>
                        </div>
                    `;
                    accountsContainer.appendChild(accountCard);
                });
            } else {
                // Eğer hesap yoksa boş mesaj göster
                const emptyMessage = document.createElement('div');
                emptyMessage.className = 'empty-accounts';
                emptyMessage.innerHTML = `
                    <i class="fas fa-user-slash"></i>
                    <h3>Hesap Bulunamadı</h3>
                    <p>Bu sipariş için henüz hesap bulunamadı.</p>
                `;
                accountsContainer.appendChild(emptyMessage);
            }
        }

        // Modalı göster
        const modal = document.getElementById('orderDetailsModal');
        if (modal) {
            modal.style.display = 'block';
        }
        
    } catch (error) {
        console.error('viewOrderDetails error:', error);
        showNotification('Modal açılırken hata oluştu', 'error');
    }
}

// Modalı kapat
function closeOrderModal() {
    document.getElementById('orderDetailsModal').style.display = 'none';
}

// Modal dışına tıklandığında kapat
window.onclick = function(event) {
    const modal = document.getElementById('orderDetailsModal');
    if (event.target === modal) {
        closeOrderModal();
    }
}

// Sipariş dosyalarını indir
function downloadOrder(orderId) {
    try {
        showNotification(`Sipariş ${orderId} dosyaları hazırlanıyor...`, 'info');
        
        // Excel dosyasını indir
        const downloadUrl = `download_order.php?order_id=${encodeURIComponent(orderId)}`;
        
        // Yeni bir link oluştur ve tıklat
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.style.display = 'none';
        
        if (document.body) {
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            showNotification('Excel dosyası indiriliyor...', 'success');
        } else {
            // Fallback - yeni pencerede aç
            window.open(downloadUrl, '_blank');
            showNotification('Dosya yeni sekmede açılıyor...', 'info');
        }
    } catch (error) {
        console.error('Download error:', error);
        showNotification('Dosya indirme hatası', 'error');
    }
}

// Destek modalını aç
function contactSupport(orderId) {
    try {
        // Destek butonunu bul
        const supportButton = document.querySelector(`button.btn-support[data-order-id="${orderId}"]`);
        if (!supportButton) {
            showNotification('Destek butonu bulunamadı', 'error');
            return;
        }
        
        // Sipariş bilgilerini al
        const orderRow = supportButton.closest('tr');
        if (!orderRow || !orderRow.cells || orderRow.cells.length < 7) {
            showNotification('Sipariş bilgileri alınamadı', 'error');
            return;
        }
        
        const productName = orderRow.cells[1] ? orderRow.cells[1].textContent.trim() : 'Bilinmiyor';
        const category = orderRow.cells[2] ? orderRow.cells[2].textContent.trim() : 'Bilinmiyor';
        const quantity = orderRow.cells[3] ? orderRow.cells[3].textContent.trim() : 'Bilinmiyor';
        const total = orderRow.cells[4] ? orderRow.cells[4].textContent.trim() : 'Bilinmiyor';
        const orderDate = orderRow.cells[5] ? orderRow.cells[5].textContent.trim() : 'Bilinmiyor';
        const status = orderRow.cells[6] ? orderRow.cells[6].textContent.trim() : 'Bilinmiyor';
        
        // Sipariş bilgilerini doldur
        const orderInfoDisplay = document.getElementById('order_info_display');
        if (orderInfoDisplay) {
            orderInfoDisplay.innerHTML = `
                <div class="order-info-item">
                    <span class="order-info-label">Sipariş No:</span>
                    <span class="order-info-value">${orderId}</span>
                </div>
                <div class="order-info-item">
                    <span class="order-info-label">Ürün:</span>
                    <span class="order-info-value">${productName}</span>
                </div>
                <div class="order-info-item">
                    <span class="order-info-label">Kategori:</span>
                    <span class="order-info-value">${category}</span>
                </div>
                <div class="order-info-item">
                    <span class="order-info-label">Adet:</span>
                    <span class="order-info-value">${quantity}</span>
                </div>
                <div class="order-info-item">
                    <span class="order-info-label">Toplam:</span>
                    <span class="order-info-value">${total}</span>
                </div>
                <div class="order-info-item">
                    <span class="order-info-label">Tarih:</span>
                    <span class="order-info-value">${orderDate}</span>
                </div>
                <div class="order-info-item">
                    <span class="order-info-label">Durum:</span>
                    <span class="order-info-value">${status}</span>
                </div>
            `;
        }
        
        // Data attribute'dan ticket bilgisini al
        const hasTicket = supportButton.getAttribute('data-has-ticket') === 'true';
        const ticketId = supportButton.getAttribute('data-ticket-id');
        
        console.log('Ticket kontrolü:', { orderId, hasTicket, ticketId }); // Debug için
        
        if (hasTicket && ticketId) {
            // Mevcut ticket var - sohbet yükle
            console.log('Mevcut ticket bulundu, chat yükleniyor:', ticketId);
            loadTicketChat(orderId);
        } else {
            // Yeni ticket formu göster
            console.log('Ticket yok, yeni ticket formu gösteriliyor');
            showNewTicketForm(orderId);
        }
        
        // Modalı göster
        const supportModal = document.getElementById('supportModal');
        if (supportModal) {
            supportModal.style.display = 'block';
        }
        
    } catch (error) {
        console.error('contactSupport error:', error);
        showNotification('Destek formu açılırken hata oluştu', 'error');
    }
}

// Mevcut ticket sohbetini yükle
function loadTicketChat(orderId) {
    const modalTitle = document.getElementById('support-modal-title');
    const chatSection = document.getElementById('ticket-chat-section');
    const newTicketSection = document.getElementById('new-ticket-section');
    const supportOrderIdField = document.getElementById('support_order_id');
    
    console.log('Ticket chat yükleniyor, orderId:', orderId); // Debug
    
    if (modalTitle) modalTitle.innerHTML = '<i class="fas fa-comments"></i> Destek Sohbeti';
    if (chatSection) chatSection.style.display = 'block';
    if (newTicketSection) newTicketSection.style.display = 'none';
    
    // Order ID'yi set et (reply için gerekebilir)
    if (supportOrderIdField) {
        supportOrderIdField.value = orderId;
    }
    
    // Loading mesajı
    const chatMessages = document.getElementById('chat-messages');
    if (chatMessages) {
        chatMessages.innerHTML = '<div class="chat-loading"><i class="fas fa-spinner"></i><br>Mesajlar yükleniyor...</div>';
    }
    
    // AJAX ile ticket detaylarını getir
    fetch(`get_ticket_chat.php?order_id=${encodeURIComponent(orderId)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayTicketChat(data.ticket, data.messages, data.canReply);
            } else {
                showNotification(data.message || 'Ticket yüklenirken hata oluştu', 'error');
                closeSupportModal();
            }
        })
        .catch(error => {
            console.error('Ticket chat error:', error);
            showNotification('Sohbet yüklenirken hata oluştu', 'error');
        });
}

// Yeni ticket formunu göster
function showNewTicketForm(orderId) {
    const modalTitle = document.getElementById('support-modal-title');
    const chatSection = document.getElementById('ticket-chat-section');
    const newTicketSection = document.getElementById('new-ticket-section');
    const supportOrderIdField = document.getElementById('support_order_id');
    
    console.log('Yeni ticket formu gösteriliyor, orderId:', orderId); // Debug
    
    if (modalTitle) modalTitle.innerHTML = '<i class="fas fa-headset"></i> Destek Talebi Oluştur';
    if (chatSection) chatSection.style.display = 'none';
    if (newTicketSection) newTicketSection.style.display = 'block';
    
    // Formu temizle ve order_id'yi set et
    const supportForm = document.getElementById('supportForm');
    if (supportForm) {
        supportForm.reset();
    }
    
    if (supportOrderIdField) {
        supportOrderIdField.value = orderId;
        console.log('support_order_id set edildi:', orderId); // Debug
    }
}

// Ticket sohbetini gürüntüle
function displayTicketChat(ticket, messages, canReply) {
    // Ticket bilgilerini güncelle
    const ticketSubject = document.getElementById('ticket-subject');
    const ticketStatus = document.getElementById('ticket-status');
    const replyTicketId = document.getElementById('reply_ticket_id');
    
    if (ticketSubject) ticketSubject.textContent = ticket.subject;
    if (ticketStatus) {
        ticketStatus.textContent = getStatusText(ticket.status);
        ticketStatus.className = `status-badge status-${ticket.status}`;
    }
    if (replyTicketId) replyTicketId.value = ticket.ticket_id;
    
    // Mesajları görüntüle
    const chatMessages = document.getElementById('chat-messages');
    if (chatMessages) {
        if (messages.length === 0) {
            chatMessages.innerHTML = '<div class="info-message">Henüz mesaj yok.</div>';
        } else {
            let messagesHtml = '';
            messages.forEach(message => {
                const messageClass = message.is_admin_reply ? 'admin' : 'user';
                const authorName = message.is_admin_reply ? 'Destek Ekibi' : 'Siz';
                const messageDate = new Date(message.created_at).toLocaleString('tr-TR');
                
                messagesHtml += `
                    <div class="message-item ${messageClass}">
                        <div class="message-header">
                            <span class="message-author">${authorName}</span>
                            <span class="message-date">${messageDate}</span>
                        </div>
                        <div class="message-content">${message.message.replace(/\n/g, '<br>')}</div>
                    </div>
                `;
            });
            chatMessages.innerHTML = messagesHtml;
            
            // En alta kaydır
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }
    
    // Yanıt formunu göster/gizle
    const replyFormSection = document.getElementById('reply-form-section');
    const noReplyMessage = document.getElementById('no-reply-message');
    
    if (canReply) {
        if (replyFormSection) replyFormSection.style.display = 'block';
        if (noReplyMessage) noReplyMessage.style.display = 'none';
    } else {
        if (replyFormSection) replyFormSection.style.display = 'none';
        if (noReplyMessage) noReplyMessage.style.display = 'block';
    }
}

// Durum metnini getir
function getStatusText(status) {
    switch(status) {
        case 'open': return 'Açık';
        case 'in_progress': return 'İşleniyor';
        case 'waiting_customer': return 'Müşteri Bekliyor';
        case 'resolved': return 'Çözüldü';
        case 'closed': return 'Kapandı';
        default: return 'Bilinmiyor';
    }
}

// Destek modalını kapat
function closeSupportModal() {
    const supportModal = document.getElementById('supportModal');
    if (supportModal) {
        supportModal.style.display = 'none';
    }
    
    const supportForm = document.getElementById('supportForm');
    if (supportForm) {
        supportForm.reset();
    }
    
    const replyForm = document.getElementById('replyForm');
    if (replyForm) {
        replyForm.reset();
    }
}

// 2FA sayfasını aç
function open2FA(secret) {
    if (secret && secret !== '') {
        window.open(`2fa.php?secret=${encodeURIComponent(secret)}`, '_blank');
        showNotification('2FA sayfası yeni sekmede açıldı!', 'info');
    } else {
        showNotification('2FA secret bulunamadı!', 'error');
    }
}

// Tüm hesapları kopyala
function copyAllAccounts() {
    const accounts = getCurrentAccounts();
    if (!accounts || accounts.length === 0) {
        showNotification('Kopyalanacak hesap bulunamadı', 'warning');
        return;
    }
    
    let allAccountsText = '';
    accounts.forEach((account, index) => {
        allAccountsText += `HESAP${index + 1} | ${account.username}:${account.password}`;
        if (account.email && account.email !== 'N/A') allAccountsText += `:${account.email}`;
        if (account.additional_info && account.additional_info !== 'N/A') allAccountsText += `:${account.additional_info}`;
        if (account.account_data && account.account_data !== 'N/A') allAccountsText += `:${account.account_data}`;
        allAccountsText += '\n';
    });
    
    navigator.clipboard.writeText(allAccountsText).then(() => {
        showNotification('Tüm hesap bilgileri kopyalandı!', 'success');
    }).catch(err => {
        console.error('Kopyalama hatası:', err);
        showNotification('Kopyalama başarısız', 'error');
    });
}

// Mevcut hesapları al
function getCurrentAccounts() {
    const accountsContainer = document.getElementById('accounts-container');
    if (!accountsContainer) return [];
    
    const accountCards = accountsContainer.querySelectorAll('.account-card');
    const accounts = [];
    
    accountCards.forEach(card => {
        const accountInfo = card.querySelector('.account-info')?.textContent.trim() || '';
        const cleanInfo = accountInfo.replace(' copy-icon', '').trim();
        
        // Hesap bilgilerini parçala
        const parts = cleanInfo.split(':');
        const username = parts[0] || '';
        const password = parts[1] || '';
        const email = parts[2] || '';
        const additionalInfo = parts[3] || '';
        const accountData = parts[4] || '';
        
        accounts.push({
            username: username,
            password: password,
            email: email,
            additional_info: additionalInfo,
            account_data: accountData
        });
    });
    
    return accounts;
}

// Excel olarak indir
function downloadAllAccounts() {
    showNotification('Excel dosyası hazırlanıyor...', 'info');
    
    const accounts = getCurrentAccounts();
    if (!accounts || accounts.length === 0) {
        showNotification('İndirilecek hesap bulunamadı', 'warning');
        return;
    }
    
    let csvContent = 'data:text/csv;charset=utf-8,Hesap No,Hesap Bilgileri,Oluşturulma Tarihi\n';
    
    accounts.forEach((account, index) => {
        let accountInfo = `${account.username}:${account.password}`;
        if (account.email) accountInfo += `:${account.email}`;
        if (account.additional_info) accountInfo += `:${account.additional_info}`;
        if (account.account_data) accountInfo += `:${account.account_data}`;
        
        csvContent += `HESAP${index + 1},"${accountInfo}",${new Date().toLocaleDateString('tr-TR')}\n`;
    });
    
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `hesaplar_${new Date().getTime()}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showNotification('Excel dosyası indirildi!', 'success');
}

// TXT olarak indir
function downloadTxtAccounts() {
    showNotification('TXT dosyası hazırlanıyor...', 'info');
    
    const accounts = getCurrentAccounts();
    if (!accounts || accounts.length === 0) {
        showNotification('İndirilecek hesap bulunamadı', 'warning');
        return;
    }
    
    let txtContent = '=== YILDIZ HESAP - HESAP BİLGİLERİ ===\n';
    txtContent += `Sipariş Kodu: ${currentOrderId || 'N/A'}\n`;
    txtContent += `Hesap Sayısı: ${accounts.length}\n`;
    txtContent += `Oluşturulma Tarihi: ${new Date().toLocaleDateString('tr-TR')}\n`;
    txtContent += '=====================================\n\n';
    
    accounts.forEach((account, index) => {
        let accountInfo = `${account.username}:${account.password}`;
        if (account.email) accountInfo += `:${account.email}`;
        if (account.additional_info) accountInfo += `:${account.additional_info}`;
        if (account.account_data) accountInfo += `:${account.account_data}`;
        
        txtContent += `HESAP${index + 1} | ${accountInfo}\n`;
    });
    
    txtContent += '\n=== ÖNEMLİ NOTLAR ===\n';
    txtContent += '1. Bu bilgileri güvenli bir yerde saklayın\n';
    txtContent += '2. Şifreleri kimseyle paylaşmayın\n';
    txtContent += '3. 2FA aktifse, 2FA kodunu da kullanın\n';
    txtContent += '4. Sorun yaşarsanız destek ekibiyle iletişime geçin\n';
    txtContent += '====================\n';
    
    const blob = new Blob([txtContent], { type: 'text/plain;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', `hesaplar_${new Date().getTime()}.txt`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    
    showNotification('TXT dosyası indirildi!', 'success');
}

// DOM tam yüklendiğinde çalış
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeEvents);
} else {
    initializeEvents();
}

// Event listener'ları başlat
function initializeEvents() {
    // Modal içindeki kopyalanabilir alanlara event listener ekle
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('copyable') || e.target.closest('.copyable')) {
            const field = e.target.classList.contains('copyable') ? e.target : e.target.closest('.copyable');
            const textToCopy = field.textContent.replace(' N/A', '').replace(' copy-icon', '').trim();
            
            navigator.clipboard.writeText(textToCopy).then(() => {
                showNotification('Kopyalandı!', 'success');
            }).catch(err => {
                console.error('Kopyalama hatası:', err);
                showNotification('Kopyalama başarısız', 'error');
            });
        }
    });
    
    // Destek formu submit handler
    const supportForm = document.getElementById('supportForm');
    if (supportForm) {
        supportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('.btn-submit');
            
            // Form validasyonu
            const subject = formData.get('subject').trim();
            const message = formData.get('message').trim();
            
            if (subject.length < 5) {
                showNotification('Konu başlığı en az 5 karakter olmalı', 'error');
                return;
            }
            
            if (message.length < 10) {
                showNotification('Mesaj en az 10 karakter olmalı', 'error');
                return;
            }
            
            // Loading durumu
            submitBtn.disabled = true;
            submitBtn.textContent = 'Gönderiliyor...';
            this.classList.add('form-loading');
            
            // AJAX ile gönder
            fetch('create_ticket.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeSupportModal();
                    // Sayfayı yenile ki buton durumu güncellensin
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Support form error:', error);
                showNotification('Destek talebi gönderilirken hata oluştu', 'error');
            })
            .finally(() => {
                // Loading durumunu kaldır
                submitBtn.disabled = false;
                submitBtn.textContent = 'Destek Talebi Oluştur';
                this.classList.remove('form-loading');
            });
        });
    }
    
    // Reply formu submit handler
    const replyForm = document.getElementById('replyForm');
    if (replyForm) {
        replyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('.btn-submit');
            const messageField = this.querySelector('#reply_message');
            
            // Form validasyonu
            const message = formData.get('message').trim();
            
            if (message.length < 10) {
                showNotification('Mesaj en az 10 karakter olmalı', 'error');
                return;
            }
            
            // Loading durumu
            submitBtn.disabled = true;
            submitBtn.textContent = 'Gönderiliyor...';
            this.classList.add('form-loading');
            
            // AJAX ile gönder
            fetch('reply_ticket.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    messageField.value = ''; // Mesaj alanını temizle
                    // Sohbeti yeniden yükle
                    const orderId = document.getElementById('support_order_id').value;
                    if (orderId) {
                        setTimeout(() => loadTicketChat(orderId), 500);
                    }
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Reply form error:', error);
                showNotification('Yanıt gönderilirken hata oluştu', 'error');
            })
            .finally(() => {
                // Loading durumunu kaldır
                submitBtn.disabled = false;
                submitBtn.textContent = 'Yanıt Gönder';
                this.classList.remove('form-loading');
            });
        });
    }
    
    // Modal dışına tıklandığında kapat (hem sipariş hem destek için)
    window.addEventListener('click', function(event) {
        const orderModal = document.getElementById('orderDetailsModal');
        const supportModal = document.getElementById('supportModal');
        
        if (event.target === orderModal) {
            closeOrderModal();
        }
        
        if (event.target === supportModal) {
            closeSupportModal();
        }
    });
}

// Global error handler
window.addEventListener('error', function(e) {
    console.error('Global error:', e.error);
});

// Simple notification system
function showNotification(message, type = 'info') {
    // Güvenlik kontrolü - document var mı?
    if (typeof document === 'undefined') {
        console.log(`Notification: ${type.toUpperCase()} - ${message}`);
        return;
    }
    
    try {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        // Add notification styles if not exists
        if (!document.querySelector('#notification-styles')) {
            const style = document.createElement('style');
            style.id = 'notification-styles';
            style.textContent = `
                .notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 12px 20px;
                    border-radius: 8px;
                    color: white;
                    font-weight: 600;
                    z-index: 10000;
                    animation: slideIn 0.3s ease;
                }
                .notification.success { background: #22c55e; }
                .notification.info { background: #3b82f6; }
                .notification.warning { background: #f59e0b; }
                .notification.error { background: #ef4444; }
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
        }
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification && notification.parentNode) {
                notification.remove();
            }
        }, 3000);
        
    } catch (error) {
        console.log(`Notification Error: ${error.message}`);
        console.log(`Message: ${type.toUpperCase()} - ${message}`);
    }
}
let currentPage = 1;
const totalPages = <?php echo $totalPages; ?>;
const csrfToken = '<?php echo $csrfToken; ?>';

function loadPage(direction) {
    const loader = document.getElementById('paginationLoader');
    const nextPageBtn = document.getElementById('nextPageBtn');
    const prevPageBtn = document.getElementById('prevPageBtn');
    const pageInfo = document.getElementById('pagination-info');
    const ordersTableBody = document.getElementById('orders-table-body');

    let targetPage = direction === 'next' ? currentPage + 1 : currentPage - 1;
    if (targetPage < 1 || targetPage > totalPages) return;
    
    loader.style.display = 'block';
    nextPageBtn.disabled = true;
    prevPageBtn.disabled = true;

    fetch(`ajax_user_orders.php?page=${targetPage}&limit=10`, {
        method: 'GET',
        headers: { 'X-CSRF-TOKEN': csrfToken }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            ordersTableBody.innerHTML = data.html;
            currentPage = targetPage;
            pageInfo.textContent = `Sayfa ${currentPage} / ${totalPages} (Toplam ${data.pagination.total_orders} sipariş)`;
            nextPageBtn.disabled = !data.pagination.has_next;
            prevPageBtn.disabled = !data.pagination.has_prev;
        } else {
            showNotification(data.message || 'Siparişler yüklenemedi', 'error');
        }
    })
    .catch(error => {
        console.error('Pagination error:', error);
        showNotification('Siparişler yüklenirken hata oluştu', 'error');
    })
    .finally(() => {
        loader.style.display = 'none';
    });
}
</script>

<?php include 'footer.php'; ?>

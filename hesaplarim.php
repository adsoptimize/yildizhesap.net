<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'Auth.php';

// Cryptomus'tan gelen istekleri kontrol et
$isFromCryptomus = false;
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$currentPage = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($referer, 'cryptomus') !== false || strpos($referer, 'cryptomus.com') !== false || 
    strpos($currentPage, 'payment-cancel.php') !== false || strpos($currentPage, 'payment-success.php') !== false) {
    $isFromCryptomus = true;
}

// Kullanıcı giriş kontrolü
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
        if (!$auth->isLoggedIn()) {
            header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
        $currentUser = $auth->getCurrentUser();
    }
} else {
    // Normal istek için session kontrolü yap
if (!$auth->isLoggedIn()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
    $currentUser = $auth->getCurrentUser();
}

// İndirme isteği kontrolü
if (isset($_GET['download']) && $_GET['download'] === 'true') {
    $orderId = $_GET['order_id'] ?? '';
    
    if (empty($orderId)) {
        die('Sipariş ID gerekli');
    }
    
    // Siparişin bu kullanıcıya ait olduğunu kontrol et
    $stmt = $pdo->prepare("SELECT o.*, u.first_name, u.last_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.order_id = ? AND o.user_id = ? AND o.status = 'completed'");
    $stmt->execute([$orderId, $currentUser['id']]);
    $order = $stmt->fetch();
    
    if (!$order) {
        die('Sipariş bulunamadı veya tamamlanmamış');
    }
    
    // Hesap bilgilerini al
    $stmt = $pdo->prepare("SELECT ast.* FROM account_stock ast JOIN orders o ON ast.order_id = o.id WHERE o.order_id = ? AND ast.is_sold = 1 ORDER BY ast.id");
    $stmt->execute([$orderId]);
    $accounts = $stmt->fetchAll();
    
    if (empty($accounts)) {
        die('Bu sipariş için hesap bilgisi bulunamadı');
    }
    
    // TXT dosyası oluştur
    $filename = "hesaplar_{$orderId}_{$currentUser['username']}.txt";
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo "=== YILDIZ HESAP - HESAP BİLGİLERİ ===\n";
    echo "Sipariş Kodu: {$order['order_id']}\n";
    echo "Müşteri: {$order['first_name']} {$order['last_name']}\n";
    echo "Ürün: {$order['product_name']}\n";
    echo "Sipariş Tarihi: " . date('d.m.Y H:i', strtotime($order['created_at'])) . "\n";
    echo "Hesap Sayısı: " . count($accounts) . "\n";
    echo "=====================================\n";
    
    foreach ($accounts as $index => $account) {
        $accountLine = "HESAP" . ($index + 1);
        
        // Basit format: username:password
        $accountLine .= " | {$account['username']}:{$account['password']}";
        
        // Eğer ek bilgiler varsa ekle
        $additionalInfo = [];
        
        if (!empty($account['email'])) {
            $additionalInfo[] = $account['email'];
        }
        
        if (!empty($account['email_password'])) {
            $additionalInfo[] = $account['email_password'];
        }
        
        if (!empty($account['totp_secret'])) {
            $additionalInfo[] = $account['totp_secret'];
        }
        
        if (!empty($account['account_created_date'])) {
            $additionalInfo[] = $account['account_created_date'];
        }
        
        // Ek bilgileri ekle
        if (!empty($additionalInfo)) {
            $accountLine .= ":" . implode(":", $additionalInfo);
        }
        
        echo $accountLine . "\n";
    }
    
    echo "=== ÖNEMLİ NOTLAR ===\n";
    echo "1. Bu bilgileri güvenli bir yerde saklayın\n";
    echo "2. Şifreleri kimseyle paylaşmayın\n";
    echo "3. 2FA aktifse, 2FA kodunu da kullanın\n";
    echo "4. Sorun yaşarsanız destek ekibiyle iletişime geçin\n";
    echo "====================\n";
    
    exit;
}

// AJAX isteği kontrolü
if (isset($_GET['ajax']) && $_GET['ajax'] === 'true') {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
    $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';
    
    $offset = ($page - 1) * $perPage;
    
    // SQL sorgusu oluştur
    $whereConditions = ["o.user_id = ?"];
    $params = [$currentUser['id']];
    
    if ($status) {
        $whereConditions[] = "o.status = ?";
        $params[] = $status;
    }
    
    if ($category) {
        $whereConditions[] = "o.product_name LIKE ?";
        $params[] = "%$category%";
    }
    
    if ($search) {
        $whereConditions[] = "(o.product_name LIKE ? OR o.order_id LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($dateFrom) {
        $whereConditions[] = "DATE(o.created_at) >= ?";
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $whereConditions[] = "DATE(o.created_at) <= ?";
        $params[] = $dateTo;
    }
    
    $whereClause = implode(" AND ", $whereConditions);
    
    // Siparişleri al
    $sql = "
        SELECT o.*, cp.order_id as payment_order_id, cp.status as payment_status,
               COUNT(CASE WHEN ast.is_sold = 1 THEN ast.id END) as account_count
        FROM orders o 
        LEFT JOIN crypto_payments cp ON o.order_id COLLATE utf8mb4_unicode_ci = cp.order_id COLLATE utf8mb4_unicode_ci
        LEFT JOIN account_stock ast ON o.id = ast.order_id
        WHERE $whereClause
        GROUP BY o.id
        ORDER BY o.created_at DESC 
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $perPage;
    $params[] = $offset;
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("AJAX query error: " . $e->getMessage());
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
    
    // Toplam sayıyı al
    $countSql = "
        SELECT COUNT(DISTINCT o.id) 
        FROM orders o 
        LEFT JOIN crypto_payments cp ON o.order_id COLLATE utf8mb4_unicode_ci = cp.order_id COLLATE utf8mb4_unicode_ci
        WHERE $whereClause
    ";
    
    try {
        $countParams = array_slice($params, 0, -2);
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($countParams);
        $totalOrders = $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("AJAX count query error: " . $e->getMessage());
        echo json_encode(['error' => 'Count query error: ' . $e->getMessage()]);
        exit;
    }
    
    $totalPages = ceil($totalOrders / $perPage);
    
    // Kategorileri al
    try {
        $stmt = $pdo->prepare("SELECT DISTINCT category FROM orders WHERE user_id = ? ORDER BY category");
        $stmt->execute([$currentUser['id']]);
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        error_log("AJAX categories query error: " . $e->getMessage());
        $categories = [];
    }
    
    echo json_encode([
        'orders' => $orders,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_orders' => $totalOrders,
            'per_page' => $perPage
        ],
        'filters' => [
            'categories' => $categories,
            'current_status' => $status,
            'current_category' => $category,
            'current_search' => $search,
            'current_date_from' => $dateFrom,
            'current_date_to' => $dateTo
        ]
    ]);
    exit;
}

// Sayfalama
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$offset = ($page - 1) * $perPage;

    // Kullanıcının siparişlerini al
    $stmt = $pdo->prepare("
        SELECT o.*, cp.order_id as payment_order_id, cp.status as payment_status,
               COUNT(CASE WHEN ast.is_sold = 1 THEN ast.id END) as account_count
        FROM orders o 
        LEFT JOIN crypto_payments cp ON o.order_id COLLATE utf8mb4_unicode_ci = cp.order_id COLLATE utf8mb4_unicode_ci
        LEFT JOIN account_stock ast ON o.id = ast.order_id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC 
        LIMIT ? OFFSET ?
    ");
try {
$stmt->execute([$currentUser['id'], $perPage, $offset]);
$orders = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Main query error: " . $e->getMessage());
    $orders = [];
    $error = $e->getMessage();
}

// Toplam sipariş sayısını al
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
try {
$stmt->execute([$currentUser['id']]);
$totalOrders = $stmt->fetchColumn();
} catch (Exception $e) {
    error_log("Count query error: " . $e->getMessage());
    $totalOrders = 0;
}

$totalPages = ceil($totalOrders / $perPage);

// Kategorileri al
$stmt = $pdo->prepare("SELECT DISTINCT category FROM orders WHERE user_id = ? ORDER BY category");
try {
    $stmt->execute([$currentUser['id']]);
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    error_log("Categories query error: " . $e->getMessage());
    $categories = [];
}

include 'header.php';
?>

<style>
.accounts-container {
    max-width: 1400px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.accounts-header {
    background: var(--card-bg);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    text-align: center;
}

.accounts-title {
    color: var(--text-primary);
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.accounts-subtitle {
    color: var(--text-secondary);
    font-size: 1.1rem;
}

/* Filtreler */
.filters-section {
    background: var(--card-bg);
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.filters-title {
    color: var(--text-primary);
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-label {
    color: var(--text-secondary);
    font-size: 0.9rem;
    font-weight: 500;
}

.filter-input, .filter-select {
    padding: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    background: rgba(0, 0, 0, 0.2);
    color: var(--text-primary);
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.filter-input:focus, .filter-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(var(--primary-rgb), 0.2);
}

.filter-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-btn-primary {
    background: var(--primary);
    color: white;
}

.filter-btn-primary:hover {
    background: var(--accent);
    transform: translateY(-1px);
}

.filter-btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-primary);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.filter-btn-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* İstatistikler */
.stats-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 0.5rem;
}

.stat-label {
    color: var(--text-secondary);
    font-size: 0.9rem;
    font-weight: 500;
}

/* Hesap kartları */
.account-card {
    background: var(--card-bg);
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
}

.account-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.account-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    flex-wrap: wrap;
    gap: 1rem;
}

.account-title {
    color: var(--text-primary);
    font-size: 1.2rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.account-status {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-completed {
    background: rgba(40, 167, 69, 0.2);
    color: #28a745;
}

.status-pending {
    background: rgba(255, 193, 7, 0.2);
    color: #ffc107;
}

.status-processing {
    background: rgba(0, 123, 255, 0.2);
    color: #007bff;
}

.status-cancelled {
    background: rgba(220, 53, 69, 0.2);
    color: #dc3545;
}

.account-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.detail-label {
    color: var(--text-secondary);
    font-size: 0.9rem;
    font-weight: 500;
}

.detail-value {
    color: var(--text-primary);
    font-weight: 600;
}

.account-data {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
}

.account-data-title {
    color: var(--text-primary);
    font-weight: 600;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.account-data-content {
    color: var(--text-secondary);
    font-family: monospace;
    font-size: 0.9rem;
    line-height: 1.6;
    white-space: pre-wrap;
    word-break: break-all;
}

/* Sayfalama */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 2rem;
    flex-wrap: wrap;
}

.pagination a, .pagination span {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.pagination a {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-primary);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.pagination a:hover {
    background: var(--primary);
    color: white;
    transform: translateY(-1px);
}

.pagination .current {
    background: var(--primary);
    color: white;
}

.pagination .disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Loading */
.loading {
    text-align: center;
    padding: 2rem;
    color: var(--text-secondary);
}

.loading i {
    font-size: 2rem;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Boş durum */
.empty-accounts {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-secondary);
}

.empty-accounts i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.buy-accounts-btn {
    display: inline-block;
    margin-top: 1rem;
    padding: 0.75rem 1.5rem;
    background: var(--primary);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.buy-accounts-btn:hover {
    background: var(--accent);
    text-decoration: none;
    color: white;
    transform: translateY(-2px);
}

.account-actions {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.download-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.download-btn:hover {
    background: linear-gradient(135deg, #218838, #1e7e34);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .accounts-container {
        margin: 1rem auto;
        padding: 0 0.5rem;
    }
    
    .accounts-header {
        padding: 1.5rem;
    }
    
    .accounts-title {
        font-size: 1.5rem;
    }
    
    .filters-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-buttons {
        flex-direction: column;
    }
    
    .stats-section {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .account-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .account-details {
        grid-template-columns: 1fr;
    }
    
    .pagination {
        flex-wrap: wrap;
    }
    
    .pagination a, .pagination span {
        padding: 0.4rem 0.8rem;
        font-size: 0.8rem;
    }
}

@media (max-width: 480px) {
    .stats-section {
        grid-template-columns: 1fr;
    }
    
    .stat-number {
        font-size: 1.5rem;
    }
    
    .account-card {
        padding: 1rem;
    }
    
    .account-title {
        font-size: 1rem;
    }
}
</style>

<div class="accounts-container">
    <div class="accounts-header">
        <h1 class="accounts-title">
            <i class="fas fa-user-shield"></i>
            Hesaplarım
        </h1>
        <p class="accounts-subtitle">
            Satın aldığınız sosyal medya hesaplarınızı buradan görüntüleyebilirsiniz
        </p>
    </div>

    <!-- İstatistikler -->
    <div class="stats-section">
        <div class="stat-card">
            <div class="stat-number" id="total-orders"><?= $totalOrders ?></div>
            <div class="stat-label">Toplam Sipariş</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="completed-orders">0</div>
            <div class="stat-label">Tamamlanan</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="pending-orders">0</div>
            <div class="stat-label">Bekleyen</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="total-accounts">0</div>
            <div class="stat-label">Toplam Hesap</div>
        </div>
    </div>

    <!-- Filtreler -->
    <div class="filters-section">
        <div class="filters-title">
            <i class="fas fa-filter"></i>
            Filtreler ve Arama
        </div>
        
        <div class="filters-grid">
            <div class="filter-group">
                <label class="filter-label">Arama</label>
                <input type="text" class="filter-input" id="search-input" placeholder="Ürün adı veya sipariş kodu...">
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Durum</label>
                <select class="filter-select" id="status-filter">
                    <option value="">Tümü</option>
                    <option value="pending">Beklemede</option>
                    <option value="processing">İşleniyor</option>
                    <option value="completed">Tamamlandı</option>
                    <option value="cancelled">İptal Edildi</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Kategori</label>
                <select class="filter-select" id="category-filter">
                    <option value="">Tümü</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Başlangıç Tarihi</label>
                <input type="date" class="filter-input" id="date-from">
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Bitiş Tarihi</label>
                <input type="date" class="filter-input" id="date-to">
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Sayfa Başına</label>
                <select class="filter-select" id="per-page">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>
        
        <div class="filter-buttons">
            <button class="filter-btn filter-btn-primary" id="apply-filters">
                <i class="fas fa-search"></i>
                Filtrele
            </button>
            <button class="filter-btn filter-btn-secondary" id="clear-filters">
                <i class="fas fa-times"></i>
                Temizle
            </button>
        </div>
    </div>

    <!-- Sonuçlar -->
    <div id="orders-container">
        <div class="loading">
            <i class="fas fa-spinner"></i>
            <p>Yükleniyor...</p>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let currentFilters = {};

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', function() {
    loadOrders();
    updateStats();
    
    // Filtre olayları
    document.getElementById('apply-filters').addEventListener('click', function() {
        currentPage = 1;
        loadOrders();
    });
    
    document.getElementById('clear-filters').addEventListener('click', function() {
        clearFilters();
        currentPage = 1;
        loadOrders();
    });
    
    // Enter tuşu ile arama
    document.getElementById('search-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            currentPage = 1;
            loadOrders();
        }
    });
    
    // Filtre değişikliklerinde otomatik arama
    ['status-filter', 'category-filter', 'per-page'].forEach(id => {
        document.getElementById(id).addEventListener('change', function() {
            currentPage = 1;
            loadOrders();
        });
    });
});

// Siparişleri yükle
function loadOrders() {
    const container = document.getElementById('orders-container');
    container.innerHTML = '<div class="loading"><i class="fas fa-spinner"></i><p>Yükleniyor...</p></div>';
    
    const filters = getFilters();
    const params = new URLSearchParams({
        ajax: 'true',
        page: currentPage,
        ...filters
    });
    
    fetch(`hesaplarim.php?${params}&_t=${Date.now()}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Server error:', data.error);
                container.innerHTML = '<div class="empty-accounts"><i class="fas fa-exclamation-triangle"></i><h3>Hata Oluştu</h3><p>Sunucu hatası: ' + data.error + '</p></div>';
            } else {
                displayOrders(data);
                updateStats();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = '<div class="empty-accounts"><i class="fas fa-exclamation-triangle"></i><h3>Hata Oluştu</h3><p>Siparişler yüklenirken bir hata oluştu.</p></div>';
        });
}

// Filtreleri al
function getFilters() {
    return {
        search: document.getElementById('search-input').value,
        status: document.getElementById('status-filter').value,
        category: document.getElementById('category-filter').value,
        date_from: document.getElementById('date-from').value,
        date_to: document.getElementById('date-to').value,
        per_page: document.getElementById('per-page').value
    };
}

// Filtreleri temizle
function clearFilters() {
    document.getElementById('search-input').value = '';
    document.getElementById('status-filter').value = '';
    document.getElementById('category-filter').value = '';
    document.getElementById('date-from').value = '';
    document.getElementById('date-to').value = '';
    document.getElementById('per-page').value = '10';
}

// Siparişleri göster
function displayOrders(data) {
    const container = document.getElementById('orders-container');
    
    if (data.orders.length === 0) {
        container.innerHTML = `
        <div class="account-card">
            <div class="empty-accounts">
                <i class="fas fa-user-slash"></i>
                    <h3>Sipariş Bulunamadı</h3>
                    <p>Arama kriterlerinize uygun sipariş bulunamadı.</p>
                    <button class="buy-accounts-btn" onclick="clearFilters(); loadOrders();">
                        <i class="fas fa-refresh"></i>
                        Filtreleri Temizle
                    </button>
                </div>
            </div>
        `;
        return;
    }
    
    let html = '';
    
    data.orders.forEach(order => {
        const statusClass = `status-${order.status}`;
        const statusText = getStatusText(order.status);
        const paymentStatus = getPaymentStatusText(order.payment_status);
        const accountCount = order.account_count || 0;
        
        html += `
            <div class="account-card">
                <div class="account-header">
                    <h3 class="account-title">
                        <i class="fas fa-user"></i>
                        ${escapeHtml(order.product_name)}
                    </h3>
                    <span class="account-status ${statusClass}">
                        ${statusText}
                    </span>
                </div>
                
                <div class="account-details">
                    <div class="detail-item">
                        <span class="detail-label">Sipariş Kodu</span>
                        <span class="detail-value">${escapeHtml(order.order_id)}</span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Sipariş Tarihi</span>
                        <span class="detail-value">${formatDate(order.created_at)}</span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Fiyat</span>
                        <span class="detail-value">${formatPrice(order.total_price)}</span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Kategori</span>
                        <span class="detail-value">${escapeHtml(order.category)}</span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Adet</span>
                        <span class="detail-value">${order.quantity}</span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Teslim Edilen</span>
                        <span class="detail-value">${accountCount} / ${order.quantity}</span>
                    </div>
                    
                    ${order.payment_order_id ? `
                        <div class="detail-item">
                            <span class="detail-label">Ödeme Durumu</span>
                            <span class="detail-value">${paymentStatus}</span>
                        </div>
                    ` : ''}
                </div>
                
                ${order.account_data ? `
                    <div class="account-data">
                        <div class="account-data-title">
                            <i class="fas fa-key"></i>
                            Hesap Bilgileri
                        </div>
                        <div class="account-data-content">${escapeHtml(order.account_data)}</div>
                    </div>
                ` : ''}
                
                <div class="account-actions">
                    ${order.status === 'completed' && accountCount > 0 ? `
                        <button class="download-btn" onclick="downloadAccounts('${order.order_id}')">
                            <i class="fas fa-download"></i>
                            Hesapları İndir (TXT)
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
    });
    
    // Sayfalama
    if (data.pagination.total_pages > 1) {
        html += generatePagination(data.pagination);
    }
    
    container.innerHTML = html;
}

// Sayfalama oluştur
function generatePagination(pagination) {
    const { current_page, total_pages } = pagination;
    let html = '<div class="pagination">';
    
    // Önceki sayfa
    if (current_page > 1) {
        html += `<a href="#" onclick="goToPage(${current_page - 1}); return false;">
                        <i class="fas fa-chevron-left"></i> Önceki
        </a>`;
    } else {
        html += `<span class="disabled">
                        <i class="fas fa-chevron-left"></i> Önceki
        </span>`;
    }
    
    // Sayfa numaraları
    const start = Math.max(1, current_page - 2);
    const end = Math.min(total_pages, current_page + 2);
    
    for (let i = start; i <= end; i++) {
        if (i === current_page) {
            html += `<span class="current">${i}</span>`;
        } else {
            html += `<a href="#" onclick="goToPage(${i}); return false;">${i}</a>`;
        }
    }
    
    // Sonraki sayfa
    if (current_page < total_pages) {
        html += `<a href="#" onclick="goToPage(${current_page + 1}); return false;">
                        Sonraki <i class="fas fa-chevron-right"></i>
        </a>`;
    } else {
        html += `<span class="disabled">
                        Sonraki <i class="fas fa-chevron-right"></i>
        </span>`;
    }
    
    html += '</div>';
    return html;
}

// Sayfa değiştir
function goToPage(page) {
    currentPage = page;
    loadOrders();
}

// İstatistikleri güncelle
function updateStats() {
    const filters = getFilters();
    const params = new URLSearchParams({
        ajax: 'true',
        page: 1,
        per_page: 1000, // Tüm siparişleri al
        ...filters
    });
    
    fetch(`hesaplarim.php?${params}&_t=${Date.now()}`)
        .then(response => response.json())
        .then(data => {
            const orders = data.orders;
            const totalOrders = orders.length;
            const completedOrders = orders.filter(o => o.status === 'completed').length;
            const pendingOrders = orders.filter(o => o.status === 'pending' || o.status === 'processing').length;
            const totalAccounts = orders.reduce((sum, o) => sum + (parseInt(o.account_count) || 0), 0);
            
            document.getElementById('total-orders').textContent = totalOrders;
            document.getElementById('completed-orders').textContent = completedOrders;
            document.getElementById('pending-orders').textContent = pendingOrders;
            document.getElementById('total-accounts').textContent = totalAccounts;
        })
        .catch(error => {
            console.error('Stats update error:', error);
        });
}

// Yardımcı fonksiyonlar
function getStatusText(status) {
    const statusMap = {
        'pending': 'Beklemede',
        'processing': 'İşleniyor',
        'completed': 'Tamamlandı',
        'cancelled': 'İptal Edildi'
    };
    return statusMap[status] || status;
}

function getPaymentStatusText(status) {
    if (status === 'paid') {
        return '<i class="fas fa-check-circle" style="color: #28a745;"></i> Ödendi';
    } else if (status === 'pending') {
        return '<i class="fas fa-clock" style="color: #ffc107;"></i> Beklemede';
    } else {
        return '<i class="fas fa-times-circle" style="color: #dc3545;"></i> Ödenmedi';
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('tr-TR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatPrice(price) {
    return new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY'
    }).format(price);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Hesap bilgilerini indir
function downloadAccounts(orderId) {
    const url = `hesaplarim.php?download=true&order_id=${orderId}`;
    window.open(url, '_blank');
}

// Otomatik yenileme (her 30 saniyede bir)
setInterval(() => {
    loadOrders();
}, 30000);
</script>

<?php include 'footer.php'; ?> 
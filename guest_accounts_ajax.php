<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'AccountManager.php';

// JSON response header
header('Content-Type: application/json');

// Cache control headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

try {
    $pdo = getPDO();
    $accountManager = new AccountManager();
    
    // GET request - Hesapları listele
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $page = intval($_GET['page'] ?? 1);
        $sort = $_GET['sort'] ?? 'smart';
        $category = $_GET['category'] ?? '';
        $priceRange = $_GET['price_range'] ?? '';
        $features = $_GET['features'] ?? '';
        $search = $_GET['search'] ?? '';
        
        // Filtreleri oluştur
        $filters = [];
        
        if ($category) {
            $filters['category_id'] = intval($category);
        }
        
        if ($priceRange) {
            $priceParts = explode('-', $priceRange);
            if (count($priceParts) === 2) {
                $filters['min_price'] = floatval($priceParts[0]);
                $filters['max_price'] = floatval($priceParts[1]);
            } elseif ($priceRange === '5000+') {
                $filters['min_price'] = 5000;
            }
        }
        
        if ($features) {
            $filters['features'] = $features;
        }
        
        if ($search) {
            $filters['search'] = $search;
        }
        
        // Hesapları getir
        $accounts = $accountManager->getAccounts($filters, $page, ITEMS_PER_PAGE);
        $totalAccounts = $accountManager->getTotalAccounts($filters);
        
        // HTML oluştur
        $html = '';
        foreach ($accounts as $account) {
            $html .= generateGuestAccountCardHTML($account);
        }
        
        // Pagination bilgileri
        $pagination = [
            'current_page' => $page,
            'total_pages' => ceil($totalAccounts / ITEMS_PER_PAGE),
            'total_items' => $totalAccounts,
            'items_per_page' => ITEMS_PER_PAGE
        ];
        
        echo json_encode([
            'success' => true,
            'html' => $html,
            'pagination' => $pagination
        ]);
    }
    
    // POST request - Hesap detaylarını getir
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_accounts') {
        $orderId = $_POST['order_id'] ?? '';
        
        if (empty($orderId)) {
            echo json_encode([
                'success' => false,
                'message' => 'Sipariş ID gerekli'
            ]);
            exit;
        }
        
        // Siparişin ziyaretçi siparişi olduğunu kontrol et
        $stmt = $pdo->prepare("
            SELECT id FROM orders 
            WHERE order_id = ? AND user_id IS NULL
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            echo json_encode([
                'success' => false,
                'message' => 'Sipariş bulunamadı'
            ]);
            exit;
        }
        
        // Hesap bilgilerini getir
        $stmt = $pdo->prepare("
            SELECT ast.username, ast.password, ast.email, ast.additional_info, ast.account_data, ast.created_at
            FROM account_stock ast
            JOIN orders o ON ast.order_id = o.id
            WHERE o.order_id = ? AND ast.is_sold = 1
            ORDER BY ast.id ASC
        ");
        $stmt->execute([$orderId]);
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($accounts)) {
            echo json_encode([
                'success' => true,
                'html' => '<div class="no-accounts">Henüz hesap teslim edilmemiş.</div>'
            ]);
            exit;
        }
        
        // HTML oluştur
        $html = '';
        foreach ($accounts as $index => $account) {
            $createdDate = date('d.m.Y H:i', strtotime($account['created_at']));
            
            // Hesap bilgilerini birleştir
            $accountInfo = $account['username'] . ':' . $account['password'];
            if (!empty($account['email']) && $account['email'] !== 'N/A') {
                $accountInfo .= ':' . $account['email'];
            }
            if (!empty($account['additional_info']) && $account['additional_info'] !== 'N/A') {
                $accountInfo .= ':' . $account['additional_info'];
            }
            if (!empty($account['account_data']) && $account['account_data'] !== 'N/A') {
                $accountInfo .= ':' . $account['account_data'];
            }
            
            $html .= '
            <div class="account-card">
                <div class="account-row">
                    <span class="account-number">HESAP' . ($index + 1) . '</span>
                    <span class="account-info copyable">' . htmlspecialchars($accountInfo) . ' <i class="fas fa-copy copy-icon"></i></span>
                    <span class="account-date">' . $createdDate . '</span>
                </div>
            </div>';
        }
        
        echo json_encode([
            'success' => true,
            'html' => $html
        ]);
    }
    
    else {
        echo json_encode([
            'success' => false,
            'message' => 'Geçersiz istek'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Guest accounts AJAX error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu'
    ]);
}

/**
 * Ziyaretçi hesap kartı HTML'i oluştur
 */
function generateGuestAccountCardHTML($account) {
    $features = [];
    if ($account['is_verified']) $features[] = 'Doğrulanmış';
    if ($account['is_premium']) $features[] = 'Premium';
    if ($account['is_featured']) $features[] = 'Öne Çıkan';
    if ($account['instant_delivery']) $features[] = 'Anında Teslimat';
    
    $featuresHtml = '';
    if (!empty($features)) {
        $featuresHtml = '<div class="account-features">';
        foreach ($features as $feature) {
            $featuresHtml .= '<span class="feature-tag">' . htmlspecialchars($feature) . '</span>';
        }
        $featuresHtml .= '</div>';
    }
    
    return '
    <div class="account-card" data-account-id="' . $account['id'] . '">
        <div class="account-header">
            <h3>' . htmlspecialchars($account['name']) . '</h3>
            <span class="account-category">' . htmlspecialchars($account['category_name']) . '</span>
        </div>
        
        <div class="account-description">
            <p>' . htmlspecialchars($account['description']) . '</p>
        </div>
        
        ' . $featuresHtml . '
        
        <div class="account-details">
            <div class="detail-item">
                <span class="label">Fiyat:</span>
                <span class="price">' . formatPrice($account['price']) . '</span>
            </div>
            <div class="detail-item">
                <span class="label">Stok:</span>
                <span class="stock">' . number_format($account['stock_quantity'] ?? 0) . ' adet</span>
            </div>
        </div>
        
        <div class="account-actions">
            <button class="btn-purchase" onclick="openGuestPurchaseModal(' . $account['id'] . ', {
                name: \'' . addslashes($account['name']) . '\',
                category: \'' . addslashes($account['category_name']) . '\',
                price: \'' . formatPrice($account['price']) . '\'
            })">
                <i class="fas fa-shopping-cart"></i>
                Satın Al
            </button>
        </div>
    </div>';
}
?> 
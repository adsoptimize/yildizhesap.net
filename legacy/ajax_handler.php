<?php
// ajax_handler.php - AJAX isteklerini işleyen dosya

require_once 'config.php';
require_once 'functions.php';

// Content-Type ayarla
header('Content-Type: application/json; charset=utf-8');

// Sadece POST isteklerini kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse(['success' => false, 'message' => 'Sadece POST istekleri kabul edilir.'], 405);
}

// CSRF token kontrolü
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    sendJSONResponse(['success' => false, 'message' => 'Güvenlik hatası. Sayfayı yenileyin.'], 403);
}

// Action parametresini kontrol et
$action = isset($_POST['action']) ? sanitizeInput($_POST['action']) : '';

if (empty($action)) {
    sendJSONResponse(['success' => false, 'message' => 'Geçersiz işlem.'], 400);
}

$accountManager = new AccountManager();

switch ($action) {
    case 'get_accounts':
        handleGetAccounts($accountManager);
        break;
        
    case 'get_account_detail':
        handleGetAccountDetail($accountManager);
        break;
        
    case 'search_accounts':
        handleSearchAccounts($accountManager);
        break;
        
    case 'increment_view':
        handleIncrementView($accountManager);
        break;
        
    default:
        sendJSONResponse(['success' => false, 'message' => 'Geçersiz işlem.'], 400);
}

// Hesapları getir
function handleGetAccounts($accountManager) {
    try {
        // Sayfa numarasını al ve doğrula
        $page = isset($_POST['page']) ? sanitizeNumber($_POST['page'], 1) : 1;
        if ($page === false) {
            $page = 1;
        }
        
        // Filtreleri al ve temizle
        $filters = [];
        
        // Arama terimi
        if (isset($_POST['search']) && !empty(trim($_POST['search']))) {
            $filters['search'] = sanitizeInput($_POST['search']);
        }
        
        // Kategori ID
        if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
            $filters['category_id'] = sanitizeNumber($_POST['category_id'], 1);
        }
        
        
        // Fiyat aralığı
        if (isset($_POST['price_range']) && !empty($_POST['price_range'])) {
            $priceRange = sanitizeInput($_POST['price_range']);
            if ($priceRange === '5000+') {
                $filters['min_price'] = 5000;
            } else {
                $rangeParts = explode('-', $priceRange);
                if (count($rangeParts) == 2) {
                    $filters['min_price'] = (int)$rangeParts[0];
                    $filters['max_price'] = (int)$rangeParts[1];
                }
            }
        }
        
        // Özellik filtresi
        if (isset($_POST['feature']) && !empty($_POST['feature'])) {
            $feature = sanitizeInput($_POST['feature']);
            switch ($feature) {
                case 'verified':
                    $filters['verified'] = 1;
                    break;
                case 'premium':
                    $filters['premium'] = 1;
                    break;
                case 'featured':
                    $filters['featured'] = 1;
                    break;
                case 'instant':
                    $filters['instant'] = 1;
                    break;
            }
        }
        
        // Tarih aralığı
        if (isset($_POST['date_range']) && !empty($_POST['date_range'])) {
            $dateRange = sanitizeInput($_POST['date_range']);
            if (in_array($dateRange, ['today', 'week', 'month', 'all'])) {
                if ($dateRange !== 'all') {
                    $filters['date_range'] = $dateRange;
                }
            }
        }
        
        // Sıralama
        if (isset($_POST['sort']) && !empty($_POST['sort'])) {
            $sort = sanitizeInput($_POST['sort']);
            if (in_array($sort, ['smart', 'date', 'price-low', 'price-high', 'popular', 'rating'])) {
                $filters['sort'] = $sort;
            }
        }
        
        // Hesapları getir
        $accounts = $accountManager->getAccounts($filters, $page, ITEMS_PER_PAGE);
        $totalAccounts = $accountManager->getTotalAccounts($filters);
        $totalPages = ceil($totalAccounts / ITEMS_PER_PAGE);
        
        // HTML oluştur
        $html = '';
        if (!empty($accounts)) {
            foreach ($accounts as $account) {
                $html .= generateAccountHTML($account);
            }
        }
        
        sendJSONResponse([
            'success' => true,
            'html' => $html,
            'total_accounts' => $totalAccounts,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_more' => $page < $totalPages
        ]);
        
    } catch (Exception $e) {
        error_log("handleGetAccounts error: " . $e->getMessage());
        sendJSONResponse(['success' => false, 'message' => 'Hesaplar yüklenirken hata oluştu.'], 500);
    }
}

// Hesap detayını getir
function handleGetAccountDetail($accountManager) {
    try {
        $accountId = isset($_POST['account_id']) ? sanitizeNumber($_POST['account_id'], 1) : false;
        
        if ($accountId === false) {
            sendJSONResponse(['success' => false, 'message' => 'Geçersiz hesap ID.'], 400);
        }
        
        $account = $accountManager->getAccountById($accountId);
        
        if (!$account) {
            sendJSONResponse(['success' => false, 'message' => 'Hesap bulunamadı.'], 404);
        }
        
        // Hesap özelliklerini getir
        $features = $accountManager->getAccountFeatures($accountId);
        
        // Görüntülenme sayısını artır
        $accountManager->incrementViews($accountId);
        
        // HTML oluştur
        $html = generateAccountDetailHTML($account, $features);
        
        sendJSONResponse([
            'success' => true,
            'html' => $html,
            'account' => $account
        ]);
        
    } catch (Exception $e) {
        error_log("handleGetAccountDetail error: " . $e->getMessage());
        sendJSONResponse(['success' => false, 'message' => 'Hesap detayı yüklenirken hata oluştu.'], 500);
    }
}

// Hesap arama
function handleSearchAccounts($accountManager) {
    try {
        $search = isset($_POST['search']) ? sanitizeInput($_POST['search']) : '';
        
        if (strlen($search) < 2) {
            sendJSONResponse(['success' => false, 'message' => 'En az 2 karakter girmelisiniz.'], 400);
        }
        
        $filters = ['search' => $search];
        $accounts = $accountManager->getAccounts($filters, 1, 20); // İlk 20 sonucu göster
        
        $html = '';
        if (!empty($accounts)) {
            foreach ($accounts as $account) {
                $html .= generateAccountHTML($account);
            }
        } else {
            $html = '<div class="no-results"><p>Arama kriterlerinize uygun hesap bulunamadı.</p></div>';
        }
        
        sendJSONResponse([
            'success' => true,
            'html' => $html,
            'count' => count($accounts)
        ]);
        
    } catch (Exception $e) {
        error_log("handleSearchAccounts error: " . $e->getMessage());
        sendJSONResponse(['success' => false, 'message' => 'Arama yapılırken hata oluştu.'], 500);
    }
}

// Görüntülenme sayısını artır
function handleIncrementView($accountManager) {
    try {
        $accountId = isset($_POST['account_id']) ? sanitizeNumber($_POST['account_id'], 1) : false;
        
        if ($accountId === false) {
            sendJSONResponse(['success' => false, 'message' => 'Geçersiz hesap ID.'], 400);
        }
        
        $result = $accountManager->incrementViews($accountId);
        
        sendJSONResponse([
            'success' => $result,
            'message' => $result ? 'Görüntülenme sayısı güncellendi.' : 'Güncelleme başarısız.'
        ]);
        
    } catch (Exception $e) {
        error_log("handleIncrementView error: " . $e->getMessage());
        sendJSONResponse(['success' => false, 'message' => 'İşlem başarısız.'], 500);
    }
}

function generateAccountHTML($account) {
    $stockStatus = getStockStatus($account['stock_quantity']);
    $platformIcon = getPlatformIcon($account['platform']);
    $price = formatPrice($account['price']);
    $urgentClass = $stockStatus['status'] === 'low' ? 'urgent' : '';
    
    $outOfStockClass = $stockStatus['status'] === 'out' ? 'out-of-stock' : '';
    
    $html = '
    <div class="account-card ' . $outOfStockClass . '">';
    
    if ($stockStatus['status'] === 'out') {
        // Stokta yoksa tıklanamaz div
        $html .= '
        <div class="card-link disabled">
            <div class="card-media">
                <i class="' . $platformIcon . '"></i>
            </div>
            <div class="card-content">
                <h3 class="card-title">' . htmlspecialchars($account['title']) . '</h3>
                <div class="card-pricing">
                    <span class="current-price">' . $price . '</span>';
                    
        if ($account['old_price']) {
            $html .= '<span class="old-price">' . formatPrice($account['old_price']) . '</span>';
        }
        
        $html .= '</div>
                <div class="stock-status ' . $urgentClass . ' out">
                    <i class="fas fa-box"></i> ' . $stockStatus['text'] . '
                </div>
                <div class="card-badges">';
                
        if ($account['is_verified']) {
            $html .= '<span class="badge verified">Doğrulanmış</span>';
        }
        
        $locationParts = explode(',', $account['location'] ?? 'Premium,Hesap');
        foreach($locationParts as $badge) {
            $badge = trim($badge);
            if($badge) {
                $html .= '<span class="badge platform">' . htmlspecialchars($badge) . '</span>';
            }
        }
        
        $html .= '</div>
                <div class="out-of-stock-overlay">
                    <i class="fas fa-ban"></i>
                    <span>Stok Tükendi</span>
                </div>
            </div>
        </div>';
    } else {
        // Stokta varsa normal link - SEO-friendly URL kullan
        $productUrl = URLHelper::getProductUrl($account['id']);
        $html .= '
        <a href="' . $productUrl . '" class="card-link">
            <div class="card-media">
                <i class="' . $platformIcon . '"></i>
            </div>
            <div class="card-content">
                <h3 class="card-title">' . htmlspecialchars($account['title']) . '</h3>
                <div class="card-pricing">
                    <span class="current-price">' . $price . '</span>';
                    
        if ($account['old_price']) {
            $html .= '<span class="old-price">' . formatPrice($account['old_price']) . '</span>';
        }
        
        $html .= '</div>
                <div class="stock-status ' . $urgentClass . '">
                    <i class="fas fa-box"></i> ' . $stockStatus['text'] . '
                </div>
                <div class="card-badges">';
                
        if ($account['is_verified']) {
            $html .= '<span class="badge verified">Doğrulanmış</span>';
        }
        
        $locationParts = explode(',', $account['location'] ?? 'Premium,Hesap');
        foreach($locationParts as $badge) {
            $badge = trim($badge);
            if($badge) {
                $html .= '<span class="badge platform">' . htmlspecialchars($badge) . '</span>';
            }
        }
        
        $html .= '</div>
            </div>
        </a>';
    }
    
    $html .= '
    </div>';
    
    return $html;
}

// Hesap detay HTML'i oluştur
function generateAccountDetailHTML($account, $features) {
    $stockStatus = getStockStatus($account['stock_quantity']);
    $platformIcon = getPlatformIcon($account['platform']);
    $price = formatPrice($account['price']);
    $oldPrice = $account['old_price'] ? '<span class="old-price">' . formatPrice($account['old_price']) . '</span>' : '';
    $verified = $account['is_verified'] ? '<span class="badge verified"><i class="fas fa-check-circle"></i> Doğrulanmış</span>' : '';
    $stockBadge = '<span class="badge stock-available"><i class="fas fa-box"></i> ' . $stockStatus['text'] . '</span>';
    
    // Özellikler listesi
    $featuresList = '';
    $defaultFeatures = [
        'Tam doğrulanmış hesap',
        'Yüksek güvenlik',
        'Anında teslimat',
        '7/24 destek',
        $account['warranty_days'] . ' gün garanti'
    ];
    
    foreach ($defaultFeatures as $feature) {
        $featuresList .= '<li><i class="fas fa-check"></i> ' . $feature . '</li>';
    }
    
    // Teknik özellikler
    $specs = '';
    $defaultSpecs = [];
    
    foreach ($features as $feature) {
        $defaultSpecs[$feature['feature_name']] = $feature['feature_value'];
    }
    
    foreach ($defaultSpecs as $label => $value) {
        $specs .= '
        <div class="spec-item">
            <span class="spec-label">' . $label . ':</span>
            <span class="spec-value">' . $value . '</span>
        </div>';
    }
    
    return '
    <div class="account-detail-card" id="account-' . $account['id'] . '">
        <div class="detail-header">
            <div class="detail-avatar">
                <i class="' . $platformIcon . '"></i>
            </div>
            <div class="detail-title">
                <h2>' . htmlspecialchars($account['title']) . '</h2>
                <p>' . htmlspecialchars($account['account_type']) . '</p>
                <div class="detail-badges">
                    ' . $verified . '
                    ' . $stockBadge . '
                </div>
            </div>
        </div>

        <div class="detail-body">
            <!-- Price & Stock Info -->
            <div class="price-section">
                <div class="price-info">
                    <span class="current-price">' . $price . '</span>
                    ' . $oldPrice . '
                </div>
                <div class="stock-info">
                    <i class="fas fa-boxes"></i>
                    <span>' . $account['stock_quantity'] . ' adet stokta</span>
                </div>
            </div>

            <!-- Description -->
            <div class="description-section">
                <h3><i class="fas fa-info-circle"></i> Açıklama</h3>
                <p>' . nl2br(htmlspecialchars($account['description'])) . '</p>
            </div>

            <!-- Features -->
            <div class="features-section">
                <h3><i class="fas fa-star"></i> Özellikler</h3>
                <ul class="features-list">
                    ' . $featuresList . '
                </ul>
            </div>

            <!-- Specifications -->
            <div class="specs-section">
                <h3><i class="fas fa-cog"></i> Teknik Özellikler</h3>
                <div class="specs-grid">
                    ' . $specs . '
                </div>
            </div>

            <!-- Purchase Section -->
            <div class="purchase-section">
                <div class="quantity-selector">
                    <label>Adet:</label>
                    <div class="quantity-controls">
                        <button class="qty-btn minus">-</button>
                        <input type="number" value="1" min="1" max="' . $account['stock_quantity'] . '" class="qty-input">
                        <button class="qty-btn plus">+</button>
                    </div>
                </div>

                <div class="total-price">
                    <span>Toplam: <strong>' . $price . '</strong></span>
                </div>

                <div class="purchase-buttons">
                    <button class="btn-buy-now" data-account-id="' . $account['id'] . '">
                        <i class="fas fa-shopping-cart"></i>
                        Hemen Satın Al
                    </button>
                    <button class="btn-add-cart" data-account-id="' . $account['id'] . '">
                        <i class="fas fa-plus"></i>
                        Sepete Ekle
                    </button>
                </div>

                <div class="security-info">
                    <div class="security-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>256-bit SSL güvenliği</span>
                    </div>
                    <div class="security-item">
                        <i class="fas fa-clock"></i>
                        <span>Anında teslimat</span>
                    </div>
                    <div class="security-item">
                        <i class="fas fa-undo"></i>
                        <span>' . $account['warranty_days'] . ' gün garanti</span>
                    </div>
                </div>
            </div>
        </div>
    </div>';
}
?>
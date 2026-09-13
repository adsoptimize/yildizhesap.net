<?php
// functions.php - Hesap işlemleri için fonksiyonlar

require_once 'config.php';

class AccountManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Kategorileri getir (tüm kategoriler)
    public function getCategories($limit = null, $offset = 0) {
        try {
            $sql = "SELECT * FROM categories ORDER BY name ASC";
            if ($limit !== null) {
                $sql .= " LIMIT :limit OFFSET :offset";
            }
            
            $stmt = $this->db->prepare($sql);
            
            if ($limit !== null) {
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("getCategories error: " . $e->getMessage());
            return [];
        }
    }
    
    // Toplam kategori sayısını getir
    public function getTotalCategories() {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM categories");
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("getTotalCategories error: " . $e->getMessage());
            return 0;
        }
    }
    
    // Hesapları filtreli şekilde getir
    public function getAccounts($filters = [], $page = 1, $limit = ITEMS_PER_PAGE) {
        try {
            $offset = ($page - 1) * $limit;
            $whereConditions = ["a.status = 'active'"];
            $params = [];
            
            // Filtreleme koşulları
            if (!empty($filters['search'])) {
                $whereConditions[] = "(a.title LIKE :search1 OR a.description LIKE :search2 OR a.platform LIKE :search3 OR a.account_type LIKE :search4)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[':search1'] = $searchTerm;
                $params[':search2'] = $searchTerm;
                $params[':search3'] = $searchTerm;
                $params[':search4'] = $searchTerm;
            }
            
            if (!empty($filters['category_id'])) {
                $whereConditions[] = "a.category_id = :category_id";
                $params[':category_id'] = $filters['category_id'];
            }
            
            if (!empty($filters['min_price'])) {
                $whereConditions[] = "a.price >= :min_price";
                $params[':min_price'] = $filters['min_price'];
            }
            
            if (!empty($filters['max_price'])) {
                $whereConditions[] = "a.price <= :max_price";
                $params[':max_price'] = $filters['max_price'];
            }
            
            
            if (isset($filters['verified']) && $filters['verified'] == 1) {
                $whereConditions[] = "a.is_verified = 1";
            }
            
            if (isset($filters['premium']) && $filters['premium'] == 1) {
                $whereConditions[] = "a.is_premium = 1";
            }
            
            if (isset($filters['featured']) && $filters['featured'] == 1) {
                $whereConditions[] = "a.is_featured = 1";
            }
            
            if (isset($filters['instant']) && $filters['instant'] == 1) {
                $whereConditions[] = "a.delivery_type = 'instant'";
            }
            
            if (!empty($filters['date_range'])) {
                switch ($filters['date_range']) {
                    case 'today':
                        $whereConditions[] = "DATE(a.created_at) = CURDATE()";
                        break;
                    case 'week':
                        $whereConditions[] = "a.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                        break;
                    case 'month':
                        $whereConditions[] = "a.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                        break;
                }
            }
            
            // Sıralama - Stoğu olan hesaplar önce gelecek şekilde
            $orderBy = "COALESCE(stock_counts.available_stock, 0) DESC, a.created_at DESC";
            if (!empty($filters['sort'])) {
                switch ($filters['sort']) {
                    case 'price-low':
                        $orderBy = "COALESCE(stock_counts.available_stock, 0) DESC, a.price ASC";
                        break;
                    case 'price-high':
                        $orderBy = "COALESCE(stock_counts.available_stock, 0) DESC, a.price DESC";
                        break;
                    case 'popular':
                        $orderBy = "COALESCE(stock_counts.available_stock, 0) DESC, a.sales_count DESC, a.views DESC";
                        break;
                    case 'rating':
                        $orderBy = "COALESCE(stock_counts.available_stock, 0) DESC, a.rating DESC";
                        break;
                    case 'smart':
                        $orderBy = "COALESCE(stock_counts.available_stock, 0) DESC, a.is_featured DESC, a.is_premium DESC, a.rating DESC, a.created_at DESC";
                        break;
                    case 'date':
                        $orderBy = "COALESCE(stock_counts.available_stock, 0) DESC, a.created_at DESC";
                        break;
                }
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            $sql = "SELECT a.*, c.name as category_name, c.slug as category_slug, c.icon as category_icon,
                           COALESCE(stock_counts.available_stock, 0) as stock_quantity
                    FROM accounts a 
                    LEFT JOIN categories c ON a.category_id = c.id 
                    LEFT JOIN (
                        SELECT account_id, 
                               COUNT(*) as total_stock,
                               COUNT(CASE WHEN is_sold = 0 THEN 1 END) as available_stock,
                               COUNT(CASE WHEN is_sold = 1 THEN 1 END) as sold_stock
                        FROM account_stock 
                        GROUP BY account_id
                    ) stock_counts ON a.id = stock_counts.account_id
                    WHERE {$whereClause} 
                    ORDER BY {$orderBy} 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            
            // Parametreleri bağla
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("getAccounts error: " . $e->getMessage());
            return [];
        }
    }
    
    // Toplam hesap sayısını getir
    public function getTotalAccounts($filters = []) {
        try {
            $whereConditions = ["a.status = 'active'"];
            $params = [];
            
            // Aynı filtreleme koşulları
            if (!empty($filters['search'])) {
                $whereConditions[] = "(a.title LIKE :search1 OR a.description LIKE :search2 OR a.platform LIKE :search3 OR a.account_type LIKE :search4)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[':search1'] = $searchTerm;
                $params[':search2'] = $searchTerm;
                $params[':search3'] = $searchTerm;
                $params[':search4'] = $searchTerm;
            }
            
            if (!empty($filters['category_id'])) {
                $whereConditions[] = "a.category_id = :category_id";
                $params[':category_id'] = $filters['category_id'];
            }
            
            if (!empty($filters['min_price'])) {
                $whereConditions[] = "a.price >= :min_price";
                $params[':min_price'] = $filters['min_price'];
            }
            
            if (!empty($filters['max_price'])) {
                $whereConditions[] = "a.price <= :max_price";
                $params[':max_price'] = $filters['max_price'];
            }
            
            
            if (isset($filters['verified']) && $filters['verified'] == 1) {
                $whereConditions[] = "a.is_verified = 1";
            }
            
            if (isset($filters['premium']) && $filters['premium'] == 1) {
                $whereConditions[] = "a.is_premium = 1";
            }
            
            if (isset($filters['featured']) && $filters['featured'] == 1) {
                $whereConditions[] = "a.is_featured = 1";
            }
            
            if (isset($filters['instant']) && $filters['instant'] == 1) {
                $whereConditions[] = "a.delivery_type = 'instant'";
            }
            
            if (!empty($filters['date_range'])) {
                switch ($filters['date_range']) {
                    case 'today':
                        $whereConditions[] = "DATE(a.created_at) = CURDATE()";
                        break;
                    case 'week':
                        $whereConditions[] = "a.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                        break;
                    case 'month':
                        $whereConditions[] = "a.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                        break;
                }
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            $sql = "SELECT COUNT(*) as total 
                    FROM accounts a 
                    LEFT JOIN categories c ON a.category_id = c.id 
                    WHERE {$whereClause}";
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'];
            
        } catch (PDOException $e) {
            error_log("getTotalAccounts error: " . $e->getMessage());
            return 0;
        }
    }
    
    // Tek hesap detayını getir
    public function getAccountById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, c.name as category_name, c.slug as category_slug, c.icon as category_icon,
                       COALESCE(stock_counts.available_stock, 0) as stock_quantity
                FROM accounts a 
                LEFT JOIN categories c ON a.category_id = c.id 
                LEFT JOIN (
                    SELECT account_id, 
                           COUNT(*) as total_stock,
                           COUNT(CASE WHEN is_sold = 0 THEN 1 END) as available_stock,
                           COUNT(CASE WHEN is_sold = 1 THEN 1 END) as sold_stock
                    FROM account_stock 
                    GROUP BY account_id
                ) stock_counts ON a.id = stock_counts.account_id
                WHERE a.id = :id AND a.status = 'active'
            ");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("getAccountById error: " . $e->getMessage());
            return false;
        }
    }
    
    // Hesap özelliklerini getir
    public function getAccountFeatures($accountId) {
        try {
            $stmt = $this->db->prepare("
                SELECT feature_name, feature_value 
                FROM account_features 
                WHERE account_id = :account_id
            ");
            $stmt->bindValue(':account_id', $accountId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("getAccountFeatures error: " . $e->getMessage());
            return [];
        }
    }
    
    // Hesap görüntülenme sayısını artır
    public function incrementViews($accountId) {
        try {
            $stmt = $this->db->prepare("UPDATE accounts SET views = views + 1 WHERE id = :id");
            $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("incrementViews error: " . $e->getMessage());
            return false;
        }
    }
    
    // Platform istatistiklerini getir
    public function getPlatformStats() {
        try {
            $stmt = $this->db->prepare("
                SELECT platform, COUNT(*) as count 
                FROM accounts 
                WHERE status = 'active' 
                GROUP BY platform 
                ORDER BY count DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("getPlatformStats error: " . $e->getMessage());
            return [];
        }
    }
    
    // FAQ'ları getir (tüm FAQ'lar)
    public function getFaqs($limit = null, $offset = 0) {
        try {
            $sql = "SELECT * FROM faqs WHERE is_active = 1 ORDER BY order_index ASC, created_at ASC";
            if ($limit !== null) {
                $sql .= " LIMIT :limit OFFSET :offset";
            }
            
            $stmt = $this->db->prepare($sql);
            
            if ($limit !== null) {
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("getFaqs error: " . $e->getMessage());
            return [];
        }
    }
    
    // Toplam FAQ sayısını getir
    public function getTotalFaqs() {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM faqs WHERE is_active = 1");
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("getTotalFaqs error: " . $e->getMessage());
            return 0;
        }
    }
    
    // Ana sayfa için sınırlı FAQ getir
    public function getHomeFaqs($limit = 6) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM faqs 
                WHERE is_active = 1 
                ORDER BY order_index ASC, created_at ASC 
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("getHomeFaqs error: " . $e->getMessage());
            return [];
        }
    }
    
    // En son eklenen 6 hesabı getir
    public function getLatestPremiumAccounts($limit = 6) {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, c.name as category_name, c.slug as category_slug, c.icon as category_icon
                FROM accounts a 
                LEFT JOIN categories c ON a.category_id = c.id 
                WHERE a.status = 'active'
                ORDER BY a.created_at DESC 
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("getLatestPremiumAccounts error: " . $e->getMessage());
            return [];
        }
    }
}

// Utility fonksiyonlar

/**
 * Para birimi sembolünü al
 */
function getCurrencySymbol($currency = null) {
    if ($currency === null) {
        // Veritabanından varsayılan para birimini al
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM crypto_settings WHERE setting_key = 'default_currency'");
            $stmt->execute();
            $currency = $stmt->fetchColumn() ?: 'USD';
        } catch (Exception $e) {
            $currency = 'USD';
        }
    }
    
    $symbols = [
        'TRY' => '₺',
        'USD' => '$',
        'EUR' => '€'
    ];
    
    return $symbols[$currency] ?? '$';
}

/**
 * Para birimi adını al
 */
function getCurrencyName($currency = null) {
    if ($currency === null) {
        // Veritabanından varsayılan para birimini al
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM crypto_settings WHERE setting_key = 'default_currency'");
            $stmt->execute();
            $currency = $stmt->fetchColumn() ?: 'USD';
        } catch (Exception $e) {
            $currency = 'USD';
        }
    }
    
    $names = [
        'TRY' => 'Türk Lirası',
        'USD' => 'Amerikan Doları',
        'EUR' => 'Euro'
    ];
    
    return $names[$currency] ?? 'Amerikan Doları';
}

/**
 * Fiyatı formatla
 */
function formatPrice($price, $currency = null) {
    $symbol = getCurrencySymbol($currency);
    return $symbol . number_format($price, 2);
}

/**
 * Varsayılan para birimini al
 */
function getDefaultCurrency() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM crypto_settings WHERE setting_key = 'default_currency'");
        $stmt->execute();
        return $stmt->fetchColumn() ?: 'USD';
    } catch (Exception $e) {
        return 'USD';
    }
}

// Tarih formatlama
function formatDate($date) {
    $dateObj = new DateTime($date);
    return $dateObj->format('d.m.Y H:i');
}

// Stok durumu kontrolü
function getStockStatus($quantity) {
    if ($quantity > 10) {
        return ['status' => 'available', 'text' => 'Stokta Var', 'class' => 'stock-available'];
    } elseif ($quantity > 0) {
        return ['status' => 'low', 'text' => 'Az Stok', 'class' => 'stock-low'];
    } else {
        return ['status' => 'out', 'text' => 'Stokta Yok', 'class' => 'stock-out'];
    }
}

// Platform ikonu getir
function getPlatformIcon($platform) {
    $icons = [
        'Facebook' => 'fab fa-facebook-f',
        'Instagram' => 'fab fa-instagram',
        'Twitter' => 'fab fa-twitter',
        'YouTube' => 'fab fa-youtube',
        'TikTok' => 'fab fa-tiktok',
        'Gmail' => 'fas fa-envelope'
    ];
    
    return isset($icons[$platform]) ? $icons[$platform] : 'fas fa-user';
}

// Rating yıldızları
function getRatingStars($rating) {
    $stars = '';
    $fullStars = floor($rating);
    $hasHalfStar = ($rating - $fullStars) >= 0.5;
    
    for ($i = 0; $i < $fullStars; $i++) {
        $stars .= '<i class="fas fa-star"></i>';
    }
    
    if ($hasHalfStar) {
        $stars .= '<i class="fas fa-star-half-alt"></i>';
    }
    
    $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
    for ($i = 0; $i < $emptyStars; $i++) {
        $stars .= '<i class="far fa-star"></i>';
    }
    
    return $stars;
}

// URL slug oluştur
function createSlug($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// Admin yetki kontrolü
function require_admin() {
    require_once dirname(__FILE__) . '/Auth.php';
    
    $auth = new Auth();
    $currentUser = null;
    $isLoggedIn = false;
    
    $sessionToken = $_COOKIE['session_token'] ?? null;
    if ($sessionToken) {
        $sessionResult = $auth->validateSession($sessionToken);
        if ($sessionResult['valid']) {
            $currentUser = $sessionResult['user'];
            $isLoggedIn = true;
        }
    }
    
    // Giriş yapmamışsa login sayfasına yönlendir
    if (!$isLoggedIn) {
        header('Location: ../login.php');
        exit;
    }
    
    // Admin değilse ana sayfaya yönlendir
    if (!isset($currentUser['is_admin']) || $currentUser['is_admin'] != 1) {
        header('Location: ../index.php');
        exit;
    }
    
    return $currentUser;
}

?>

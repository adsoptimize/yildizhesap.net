<?php
// Admin authentication
require_once __DIR__ . '/../StockManager.php';

require_once 'auth_header.php';
 

// Stok yönetimi başlat
$stockManager = new StockManager();

// AJAX işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    try {
        switch($action) {
         case 'add_bulk_stock':
    $accountId = intval($_POST['account_id']);
    $stockData = $_POST['stock_data'] ?? '';
    
    if ($accountId <= 0 || empty($stockData)) {
        throw new Exception('Hesap ID ve stok verisi gereklidir');
    }
    
    // Debug: Log gelen veriyi
    file_put_contents(__DIR__ . '/debug_log.txt', "=== BULK STOCK DEBUG ===\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/debug_log.txt', "Stock Data Length: " . strlen($stockData) . "\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/debug_log.txt', "Stock Data Preview: " . substr($stockData, 0, 200) . "...\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/debug_log.txt', "Contains separator: " . (strpos($stockData, '|||DOSYA_AYIRICI|||') !== false ? 'YES' : 'NO') . "\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/debug_log.txt', "Separator position: " . strpos($stockData, '|||DOSYA_AYIRICI|||') . "\n", FILE_APPEND);
    
    // Özel ayırıcı ile TXT dosyalarını ayır, her dosya içeriği tek hesap
    if (strpos($stockData, '|||DOSYA_AYIRICI|||') !== false) {
        // TXT dosyalarından gelen veri - özel ayırıcı ile ayrılmış
        // Her dosya içeriği tek bir hesap olarak işlenir
        $accounts = array_filter(array_map('trim', explode('|||DOSYA_AYIRICI|||', $stockData)));
        file_put_contents(__DIR__ . '/debug_log.txt', "Using separator split, accounts count: " . count($accounts) . "\n", FILE_APPEND);
        
        // Her dosya içeriğini tek hesap olarak kaydet
        $inserted = 0;
        foreach ($accounts as $fileContent) {
            if (!empty($fileContent)) {
                $accountInfo = trim($fileContent);
                $stmt = $pdo->prepare("
                    INSERT INTO account_stock (account_id, username, password) 
                    VALUES (?, ?, '')
                ");
                $stmt->execute([$accountId, $accountInfo]);
                $inserted++;
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => "{$inserted} adet stok eklendi"
        ]);
        exit;
    } else {
        // Normal metin girişi - satır satır işle
        $accounts = array_filter(array_map('trim', explode("\n", $stockData)));
        file_put_contents(__DIR__ . '/debug_log.txt', "Using line split, accounts count: " . count($accounts) . "\n", FILE_APPEND);
        
        $inserted = 0;
        
        foreach ($accounts as $accountContent) {
            if (!empty($accountContent)) {
                // Her bloğu (TXT dosyası içeriği veya satır) tek bir hesap bilgisi olarak kaydet
                $accountInfo = trim($accountContent);
                
                $stmt = $pdo->prepare("
                    INSERT INTO account_stock (account_id, username, password) 
                    VALUES (?, ?, '')
                ");
                $stmt->execute([$accountId, $accountInfo]);
                $inserted++;
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => "{$inserted} adet stok eklendi"
        ]);
        exit;
    }
    case 'add_single_stock':
                $accountId = intval($_POST['account_id']);
                $accountData = $_POST['account_data'] ?? '';
                
                $result = $stockManager->addSingleStock($accountId, $accountData);
                echo json_encode($result);
                exit;
                
            case 'get_stock_list':
                $accountId = intval($_POST['account_id']);
                $page = intval($_POST['page'] ?? 1);
                
                $result = $stockManager->getStockList($accountId, $page);
                echo json_encode($result);
                exit;
                
            case 'update_stock':
                $stockId = intval($_POST['stock_id']);
                $accountData = $_POST['account_data'] ?? '';
                
                $result = $stockManager->updateStock($stockId, $accountData);
                echo json_encode($result);
                exit;
                
            case 'delete_stock':
                $stockId = intval($_POST['stock_id']);
                
                $result = $stockManager->deleteStock($stockId);
                echo json_encode($result);
                exit;
                
            case 'clean_empty_stock':
                $accountId = intval($_POST['account_id'] ?? 0);
                
                $result = $stockManager->cleanEmptyStock($accountId ?: null);
                echo json_encode($result);
                exit;
                
            case 'get_stock_counts':
                $accountId = intval($_POST['account_id']);
                if ($accountId <= 0) {
                    throw new Exception('Geçersiz hesap ID');
                }
                
                $stmt = $pdo->prepare("
                    SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN is_sold = 0 THEN 1 ELSE 0 END) as available,
                        SUM(CASE WHEN is_sold = 1 THEN 1 ELSE 0 END) as sold
                    FROM account_stock 
                    WHERE account_id = ?
                ");
                $stmt->execute([$accountId]);
                $counts = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$counts) {
                    $counts = ['total' => 0, 'available' => 0, 'sold' => 0];
                }
                
                echo json_encode(['success' => true, 'counts' => $counts]);
                exit;
                
            case 'get_stock_list':
                $accountId = intval($_POST['account_id']);
                $page = max(1, intval($_POST['page'] ?? 1));
                $limit = 20;
                $offset = ($page - 1) * $limit;
                
                if ($accountId <= 0) {
                    throw new Exception('Geçersiz hesap ID');
                }
                
                // Toplam sayı
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM account_stock WHERE account_id = ?");
                $countStmt->execute([$accountId]);
                $totalStocks = $countStmt->fetchColumn();
                $totalPages = ceil($totalStocks / $limit);
                
                // Stok listesi
                $stmt = $pdo->prepare("
                    SELECT s.*, u.username as buyer_username,
                           CONCAT(s.username, ':', s.password) as account_data
                    FROM account_stock s
                    LEFT JOIN users u ON s.sold_to_user_id = u.id
                    WHERE s.account_id = ?
                    ORDER BY s.created_at DESC
                    LIMIT ? OFFSET ?
                ");
                $stmt->execute([$accountId, $limit, $offset]);
                $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true, 
                    'stocks' => $stocks,
                    'pages' => $totalPages,
                    'current_page' => $page
                ]);
                exit;
                
            case 'add_bulk_stock':
                $accountId = intval($_POST['account_id']);
                $stockData = $_POST['stock_data'] ?? '';
                
                if ($accountId <= 0 || empty($stockData)) {
                    throw new Exception('Hesap ID ve stok verisi gereklidir');
                }
                
                // Debug: Log gelen veriyi
                file_put_contents(__DIR__ . '/debug_log.txt', "=== BULK STOCK DEBUG ===\n", FILE_APPEND);
                file_put_contents(__DIR__ . '/debug_log.txt', "Stock Data Length: " . strlen($stockData) . "\n", FILE_APPEND);
                file_put_contents(__DIR__ . '/debug_log.txt', "Stock Data Preview: " . substr($stockData, 0, 200) . "...\n", FILE_APPEND);
                file_put_contents(__DIR__ . '/debug_log.txt', "Contains separator: " . (strpos($stockData, '|||DOSYA_AYIRICI|||') !== false ? 'YES' : 'NO') . "\n", FILE_APPEND);
                file_put_contents(__DIR__ . '/debug_log.txt', "Separator position: " . strpos($stockData, '|||DOSYA_AYIRICI|||') . "\n", FILE_APPEND);
                
                // Özel ayırıcı ile TXT dosyalarını ayır, her dosya içeriği tek hesap
                if (strpos($stockData, '|||DOSYA_AYIRICI|||') !== false) {
                    // TXT dosyalarından gelen veri - özel ayırıcı ile ayrılmış
                    // Her dosya içeriği tek bir hesap olarak işlenir
                    $accounts = array_filter(array_map('trim', explode('|||DOSYA_AYIRICI|||', $stockData)));
                    file_put_contents(__DIR__ . '/debug_log.txt', "Using separator split, accounts count: " . count($accounts) . "\n", FILE_APPEND);
                    
                    // Her dosya içeriğini tek hesap olarak kaydet
                    $inserted = 0;
                    foreach ($accounts as $fileContent) {
                        if (!empty($fileContent)) {
                            $accountInfo = trim($fileContent);
                            $stmt = $pdo->prepare("
                                INSERT INTO account_stock (account_id, username, password) 
                                VALUES (?, ?, '')
                            ");
                            $stmt->execute([$accountId, $accountInfo]);
                            $inserted++;
                        }
                    }
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => "{$inserted} adet stok eklendi"
                    ]);
                    exit;
                } else {
                    // Normal metin girişi - satır satır işle
                    $accounts = array_filter(array_map('trim', explode("\n", $stockData)));
                    file_put_contents(__DIR__ . '/debug_log.txt', "Using line split, accounts count: " . count($accounts) . "\n", FILE_APPEND);
                    
                    $inserted = 0;
                    
                    foreach ($accounts as $accountContent) {
                        if (!empty($accountContent)) {
                            // Her satırı ayrı hesap olarak kaydet
                            $accountInfo = trim($accountContent);
                            
                            $stmt = $pdo->prepare("
                                INSERT INTO account_stock (account_id, username, password) 
                                VALUES (?, ?, '')
                            ");
                            $stmt->execute([$accountId, $accountInfo]);
                            $inserted++;
                        }
                    }
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => "{$inserted} adet stok eklendi"
                    ]);
                    exit;
                }
                
            case 'delete_stock':
                $stockId = intval($_POST['stock_id']);
                
                if ($stockId <= 0) {
                    throw new Exception('Geçersiz stok ID');
                }
                
                $stmt = $pdo->prepare("DELETE FROM account_stock WHERE id = ? AND is_sold = 0");
                $stmt->execute([$stockId]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Stok silindi']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Stok silinemedi veya zaten satılmış']);
                }
                exit;
                
            case 'add_single_stock':
                $accountId = intval($_POST['account_id']);
                $accountData = $_POST['account_data'] ?? '';
                
                if ($accountId <= 0 || empty($accountData)) {
                    throw new Exception('Hesap ID ve hesap bilgisi gereklidir');
                }
                
                $accountInfo = trim($accountData);
                
                if (!empty($accountInfo)) {
                    $stmt = $pdo->prepare("
                        INSERT INTO account_stock (account_id, username, password) 
                        VALUES (?, ?, '')
                    ");
                    $stmt->execute([$accountId, $accountInfo]);
                    echo json_encode(['success' => true, 'message' => 'Stok eklendi']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Hesap bilgisi gereklidir']);
                }
                exit;
                
            case 'update_stock':
                $stockId = intval($_POST['stock_id']);
                $accountData = $_POST['account_data'] ?? '';
                
                if ($stockId <= 0 || empty($accountData)) {
                    throw new Exception('Stok ID ve hesap bilgisi gereklidir');
                }
                
                $accountInfo = trim($accountData);
                
                if (!empty($accountInfo)) {
                    $stmt = $pdo->prepare("
                        UPDATE account_stock 
                        SET username = ?, password = '' 
                        WHERE id = ? AND is_sold = 0
                    ");
                    $stmt->execute([$accountInfo, $stockId]);
                    
                    if ($stmt->rowCount() > 0) {
                        echo json_encode(['success' => true, 'message' => 'Stok güncellendi']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Stok güncellenemedi veya zaten satılmış']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Hesap bilgisi gereklidir']);
                }
                exit;
                
            case 'add_account':
                $title = sanitizeInput($_POST['title']);
                $description = sanitizeInput($_POST['description']);
                $price = floatval($_POST['price']);
                $platform = sanitizeInput($_POST['platform']);
                $category = sanitizeInput($_POST['category'] ?? '');
                $account_type = sanitizeInput($_POST['account_type'] ?? '');
                $status = sanitizeInput($_POST['status']);
                
                // Yeni alanları al
                $old_price = !empty($_POST['old_price']) ? floatval($_POST['old_price']) : null;
                $features = sanitizeInput($_POST['features'] ?? '');
                $technical_info = sanitizeInput($_POST['technical_info'] ?? '');
                $is_verified = isset($_POST['is_verified']) ? 1 : 0;
                $is_premium = isset($_POST['is_premium']) ? 1 : 0;
                $is_secure = isset($_POST['is_secure']) ? 1 : 0;
                $instant_delivery = isset($_POST['instant_delivery']) ? 1 : 0;
                $support_24_7 = isset($_POST['support_24_7']) ? 1 : 0;
                $guarantee_30_days = isset($_POST['guarantee_30_days']) ? 1 : 0;
                $warranty_days = !empty($_POST['warranty_days']) ? intval($_POST['warranty_days']) : 30;
                $delivery_type = sanitizeInput($_POST['delivery_type'] ?? 'instant');
                $location = sanitizeInput($_POST['location'] ?? '');
                
                if (empty($title) || empty($description) || $price <= 0) {
                    throw new Exception('Tüm alanları doldurun ve geçerli bir fiyat girin.');
                }
                
                // Kategori ID'sini bul veya yeni kategori oluştur
                $category_id = 1; // Default
                if (!empty($category)) {
                    // Önce kategori var mı kontrol et
                    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
                    $stmt->execute([$category]);
                    $existing_category = $stmt->fetch();
                    
                    if ($existing_category) {
                        $category_id = $existing_category['id'];
                    } else {
                        // Yeni kategori oluştur
                        $stmt = $pdo->prepare("INSERT INTO categories (name, status) VALUES (?, 'active')");
                        $stmt->execute([$category]);
                        $category_id = $pdo->lastInsertId();
                    }
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO accounts (category_id, title, description, price, old_price, platform, account_type, features, technical_info, is_verified, is_premium, is_secure, instant_delivery, support_24_7, guarantee_30_days, warranty_days, delivery_type, location, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$category_id, $title, $description, $price, $old_price, $platform, $account_type, $features, $technical_info, $is_verified, $is_premium, $is_secure, $instant_delivery, $support_24_7, $guarantee_30_days, $warranty_days, $delivery_type, $location, $status]);
                
                echo json_encode(['success' => true, 'message' => 'Hesap başarıyla eklendi']);
                exit;
                
            case 'update_account':
                $id = intval($_POST['id']);
                $title = sanitizeInput($_POST['title']);
                $description = sanitizeInput($_POST['description']);
                $price = floatval($_POST['price']);
                $platform = sanitizeInput($_POST['platform']);
                $category = sanitizeInput($_POST['category'] ?? '');
                $account_type = sanitizeInput($_POST['account_type'] ?? '');
                $status = sanitizeInput($_POST['status']);
                
                // Yeni alanları al
                $old_price = !empty($_POST['old_price']) ? floatval($_POST['old_price']) : null;
                $features = sanitizeInput($_POST['features'] ?? '');
                $technical_info = sanitizeInput($_POST['technical_info'] ?? '');
                $is_verified = isset($_POST['is_verified']) ? 1 : 0;
                $is_premium = isset($_POST['is_premium']) ? 1 : 0;
                $is_secure = isset($_POST['is_secure']) ? 1 : 0;
                $instant_delivery = isset($_POST['instant_delivery']) ? 1 : 0;
                $support_24_7 = isset($_POST['support_24_7']) ? 1 : 0;
                $guarantee_30_days = isset($_POST['guarantee_30_days']) ? 1 : 0;
                $warranty_days = !empty($_POST['warranty_days']) ? intval($_POST['warranty_days']) : 30;
                $delivery_type = sanitizeInput($_POST['delivery_type'] ?? 'instant');
                $location = sanitizeInput($_POST['location'] ?? '');
                
                if ($id <= 0 || empty($title) || empty($description) || $price <= 0) {
                    throw new Exception('Geçersiz veri. Tüm alanları kontrol edin.');
                }
                
                // Kategori ID'sini bul veya yeni kategori oluştur
                $category_id = 1; // Default
                if (!empty($category)) {
                    // Önce kategori var mı kontrol et
                    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
                    $stmt->execute([$category]);
                    $existing_category = $stmt->fetch();
                    
                    if ($existing_category) {
                        $category_id = $existing_category['id'];
                    } else {
                        // Yeni kategori oluştur
                        $stmt = $pdo->prepare("INSERT INTO categories (name, status) VALUES (?, 'active')");
                        $stmt->execute([$category]);
                        $category_id = $pdo->lastInsertId();
                    }
                }
                
                $stmt = $pdo->prepare("
                    UPDATE accounts 
                    SET category_id=?, title=?, description=?, price=?, old_price=?, platform=?, account_type=?, features=?, technical_info=?, is_verified=?, is_premium=?, is_secure=?, instant_delivery=?, support_24_7=?, guarantee_30_days=?, warranty_days=?, delivery_type=?, location=?, status=?, updated_at=NOW()
                    WHERE id=?
                ");
                $stmt->execute([$category_id, $title, $description, $price, $old_price, $platform, $account_type, $features, $technical_info, $is_verified, $is_premium, $is_secure, $instant_delivery, $support_24_7, $guarantee_30_days, $warranty_days, $delivery_type, $location, $status, $id]);
                
                echo json_encode(['success' => true, 'message' => 'Hesap başarıyla güncellendi']);
                exit;
                
            case 'delete_account':
                $id = intval($_POST['id']);
                if ($id <= 0) {
                    throw new Exception('Geçersiz hesap ID');
                }
                
                // Önce siparişlerde kullanılıp kullanılmadığını kontrol et
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE product_name = (SELECT title FROM accounts WHERE id = ?)");
                $stmt->execute([$id]);
                $orderCount = $stmt->fetchColumn();
                
                if ($orderCount > 0) {
                    echo json_encode(['success' => false, 'message' => 'Bu hesap siparişlerde kullanıldığı için silinemez. Durumu pasif yapabilirsiniz.']);
                    exit;
                }
                
                $stmt = $pdo->prepare("DELETE FROM accounts WHERE id = ?");
                $stmt->execute([$id]);
                
                echo json_encode(['success' => true, 'message' => 'Hesap başarıyla silindi']);
                exit;
                
            case 'get_account':
                $id = intval($_POST['id']);
                if ($id <= 0) {
                    throw new Exception('Geçersiz hesap ID');
                }
                
                $stmt = $pdo->prepare("
                    SELECT a.*, c.name as category_name 
                    FROM accounts a 
                    LEFT JOIN categories c ON a.category_id = c.id 
                    WHERE a.id = ?
                ");
                $stmt->execute([$id]);
                $account = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$account) {
                    throw new Exception('Hesap bulunamadı');
                }
                
                echo json_encode(['success' => true, 'account' => $account]);
                exit;
                
            default:
                throw new Exception('Geçersiz işlem');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Filtreleme parametreleri
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';
$stock_filter = $_GET['stock_filter'] ?? ''; // Yeni stok filtresi
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Sorgu oluşturma
$whereConditions = ['1=1'];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(title LIKE ? OR description LIKE ? OR platform LIKE ? OR account_type LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($category)) {
    $whereConditions[] = "platform = ?";
    $params[] = $category;
}

if (!empty($status)) {
    $whereConditions[] = "status = ?";
    $params[] = $status;
}

// Stok filtresi koşulu
if (!empty($stock_filter)) {
    if ($stock_filter === 'in_stock') {
        // Stoğu olanları göster
        $whereConditions[] = "EXISTS (SELECT 1 FROM account_stock WHERE account_id = a.id AND is_sold = 0)";
    } elseif ($stock_filter === 'out_of_stock') {
        // Stoğu olmayanları göster
        $whereConditions[] = "NOT EXISTS (SELECT 1 FROM account_stock WHERE account_id = a.id AND is_sold = 0)";
    }
}

$whereClause = implode(' AND ', $whereConditions);

// Toplam sayı - accounts tablosuna alias ekle
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM accounts a WHERE $whereClause");
$countStmt->execute($params);
$totalAccounts = $countStmt->fetchColumn();
$totalPages = ceil($totalAccounts / $limit);

// Hesapları getir - gerçek stok verilerini dahil et
$stmt = $pdo->prepare("
    SELECT a.*, 
           a.sales_count as total_sales,
           COALESCE(stock_counts.total_stock, 0) as real_total_stock,
           COALESCE(stock_counts.available_stock, 0) as real_available_stock,
           COALESCE(stock_counts.sold_stock, 0) as real_sold_stock
    FROM accounts a 
    LEFT JOIN (
        SELECT 
            account_id,
            COUNT(*) as total_stock,
            SUM(CASE WHEN is_sold = 0 THEN 1 ELSE 0 END) as available_stock,
            SUM(CASE WHEN is_sold = 1 THEN 1 ELSE 0 END) as sold_stock
        FROM account_stock
        GROUP BY account_id
    ) stock_counts ON a.id = stock_counts.account_id
    WHERE $whereClause 
    ORDER BY a.created_at DESC 
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kategorileri getir
// Kategorileri çek
$categoriesStmt = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

// Platform listesi için ayrı sorgu
$platformsStmt = $pdo->query("SELECT DISTINCT platform FROM accounts WHERE platform IS NOT NULL AND platform != '' ORDER BY platform");
$platforms = $platformsStmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Hesap Yönetimi';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --accent: #f093fb;
            --success: #4ade80;
            --warning: #facc15;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #1e293b;
            --light: #f8fafc;
            --border: #e2e8f0;
            --shadow: rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            box-shadow: 4px 0 20px var(--shadow);
            transition: all 0.3s ease;
            position: relative;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 2rem;
            border-bottom: 1px solid var(--border);
            text-align: center;
        }

        .sidebar-logo {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
        }

        .sidebar-subtitle {
            color: #64748b;
            font-size: 0.875rem;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0.25rem 1rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            color: #64748b;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-link:hover, .nav-link.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .main-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1.5rem 2rem;
            border-radius: 20px;
            box-shadow: 0 8px 32px var(--shadow);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }

        /* Content Card */
        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 8px 32px var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        /* Filters */
        .filters {
            display: grid;
            grid-template-columns: 1fr auto auto auto auto auto;
            gap: 1rem;
            margin-bottom: 2rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
        }

        .form-control {
            padding: 0.75rem 1rem;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
            border-radius: 12px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .table th {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .table th:first-child {
            border-top-left-radius: 12px;
        }

        .table th:last-child {
            border-top-right-radius: 12px;
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            font-size: 0.875rem;
        }

        .table tbody tr:hover {
            background: #f8fafc;
        }

        /* Status badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: rgba(74, 222, 128, 0.1);
            color: var(--success);
        }

        .status-inactive {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a, .pagination span {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            color: #64748b;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: var(--primary);
            color: white;
        }

        .pagination .current {
            background: var(--primary);
            color: white;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
        }

        .close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #64748b;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: var(--danger);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-row.full {
            grid-template-columns: 1fr;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        /* Toast notifications */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            z-index: 10000;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .toast.success {
            background: var(--success);
        }

        .toast.error {
            background: var(--danger);
        }

        .btn-info {
            background: var(--info);
            color: white;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
            }
            
            .filters {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="main-header">
                <h1 class="page-title"><?= $pageTitle ?></h1>
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                        <div style="font-size: 0.875rem; color: #64748b;">Admin</div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="content-card">
                <!-- Filters and Add Button -->
                <div class="filters">
                    <div class="form-group">
                        <input type="text" class="form-control" id="searchInput" placeholder="Hesap ara..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="form-group">
                        <select class="form-control" id="categoryFilter">
                            <option value="">Tüm Kategoriler</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['name']) ?>" <?= $category === $cat['name'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <select class="form-control" id="statusFilter">
                            <option value="">Tüm Durumlar</option>
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Aktif</option>
                            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Pasif</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <select class="form-control" id="stockFilter">
                            <option value="">Tüm Stok Durumları</option>
                            <option value="in_stock" <?= $stock_filter === 'in_stock' ? 'selected' : '' ?>>Stoğu Olanlar</option>
                            <option value="out_of_stock" <?= $stock_filter === 'out_of_stock' ? 'selected' : '' ?>>Stoğu Olmayanlar</option>
                        </select>
                    </div>
                    <button class="btn btn-primary" onclick="filterAccounts()">
                        <i class="fas fa-search"></i>
                        Filtrele
                    </button>
                    <br><br> 
                    <button class="btn btn-success" onclick="openAddModal()">
                        <i class="fas fa-plus"></i>
                        Yeni Hesap Ekle
                    </button>
                </div>

                <!-- Accounts Table -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Başlık</th>
                                <th>Platform</th>
                                <th>Fiyat</th>
                                <th>Stok</th>
                                <th>Satış</th>
                                <th>Durum</th>
                                <th>Oluşturulma</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($accounts as $account): ?>
                                <tr>
                                    <td><?= $account['id'] ?></td>
                                    <td>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($account['title']) ?></div>
                                        <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">
                                            <?= htmlspecialchars(substr($account['description'], 0, 50)) ?>...
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($account['platform']) ?></td>
                                    <td><?= formatPrice($account['price']) ?></td>
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                            <div style="font-weight: 600; color: <?= $account['real_available_stock'] > 0 ? '#059669' : '#ef4444' ?>;">
                                                <?= $account['real_available_stock'] ?> Mevcut
                                            </div>
                                            <div style="font-size: 0.75rem; color: #6b7280;">
                                                Stok: <?= $account['real_total_stock'] ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= $account['total_sales'] ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $account['status'] ?>">
                                            <?= $account['status'] === 'active' ? 'Aktif' : 'Pasif' ?>
                                        </span>
                                    </td>
                                    <td><?= date('d.m.Y H:i', strtotime($account['created_at'])) ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                            <button class="btn btn-info btn-sm" onclick="openStockModal(<?= $account['id'] ?>, '<?= htmlspecialchars($account['title']) ?>')" title="Stok Yönetimi">
                                                <i class="fas fa-boxes"></i>
                                            </button>
                                            <button class="btn btn-warning btn-sm" onclick="editAccount(<?= $account['id'] ?>)" title="Düzenle">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteAccount(<?= $account['id'] ?>)" title="Sil">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($accounts)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 2rem; color: #64748b;">
                                        Henüz hesap bulunamadı.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&status=<?= urlencode($status) ?>&stock_filter=<?= urlencode($stock_filter) ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="current"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&status=<?= urlencode($status) ?>&stock_filter=<?= urlencode($stock_filter) ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&status=<?= urlencode($status) ?>&stock_filter=<?= urlencode($stock_filter) ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add/Edit Account Modal -->
    <div id="accountModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Yeni Hesap Ekle</h3>
                <button type="button" class="close" onclick="closeModal()">&times;</button>
            </div>
            <form id="accountForm">
                <input type="hidden" id="accountId" name="id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="title">Başlık *</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="category">Kategori *</label>
                        <select class="form-control" id="category" name="category" required>
                            <option value="">Kategori Seçin</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['name']) ?>">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Fiyat (<?= getCurrencySymbol() ?>) *</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" required>
                    </div>
                    <div class="form-group">
                        <label for="platform">Platform *</label>
                        <input type="text" class="form-control" id="platform" name="platform" required>
                    </div>
                </div>
                
                <div class="form-row full">
                    <div class="form-group">
                        <label for="description">Açıklama *</label>
                        <textarea class="form-control" id="description" name="description" required></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="old_price">Eski Fiyat (<?= getCurrencySymbol() ?>)</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="old_price" name="old_price" placeholder="İndirim göstermek için eski fiyatı girin">
                    </div>
                    <div class="form-group">
                        <label for="account_type">Hesap Türü</label>
                        <input type="text" class="form-control" id="account_type" name="account_type" placeholder="Örn: Business, Personal">
                    </div>
                </div>
                
                <div class="form-row full">
                    <div class="form-group">
                        <label for="features">Özellikler</label>
                        <textarea class="form-control" id="features" name="features" rows="3" placeholder="Her satıra bir özellik yazın\nÖrn: Doğrulanmış\nGüvenli\nAnında Teslimat"></textarea>
                    </div>
                </div>
                
                <div class="form-row full">
                    <div class="form-group">
                        <label for="technical_info">Teknik Bilgiler</label>
                        <textarea class="form-control" id="technical_info" name="technical_info" rows="3" placeholder="Teknik detayları girin\nÖrn: Platform: Facebook\nTür: Business\nDurum: Aktif"></textarea>
                    </div>
                </div>
                
                <!-- Özellik Seçenekleri -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Özellik Seçenekleri</label>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; margin-top: 0.5rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" id="is_verified" name="is_verified" value="1">
                                <span>Doğrulanmış</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" id="is_premium" name="is_premium" value="1">
                                <span>Premium</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" id="is_secure" name="is_secure" value="1">
                                <span>Güvenli</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" id="instant_delivery" name="instant_delivery" value="1" checked>
                                <span>Anında Teslimat</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" id="support_24_7" name="support_24_7" value="1" checked>
                                <span>7/24 Destek</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" id="guarantee_30_days" name="guarantee_30_days" value="1" checked>
                                <span>30 Gün Garanti</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="warranty_days">Garanti Süresi (Gün)</label>
                        <input type="number" min="0" class="form-control" id="warranty_days" name="warranty_days" value="30">
                    </div>
                    <div class="form-group">
                        <label for="delivery_type">Teslimat Türü</label>
                        <select class="form-control" id="delivery_type" name="delivery_type">
                            <option value="instant">Anında</option>
                            <option value="manual">Manuel</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Durum</label>
                        <select class="form-control" id="status" name="status">
                            <option value="active">Aktif</option>
                            <option value="inactive">Pasif</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="location">Konum</label>
                        <input type="text" class="form-control" id="location" name="location" placeholder="Örn: Türkiye, ABD">
                    </div>
                </div>
                
                <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="closeModal()" style="background: #6b7280; color: white;">
                        İptal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stock Management Modal -->
    <div id="stockModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h3 class="modal-title" id="stockModalTitle">Stok Yönetimi</h3>
                <button type="button" class="close" onclick="closeStockModal()">&times;</button>
            </div>
            
            <div id="stockModalBody">
                <!-- Stock Stats -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem;">
                    <div style="background: #f0f9ff; padding: 1rem; border-radius: 8px; text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 600; color: #0284c7;" id="totalStock">0</div>
                        <div style="font-size: 0.875rem; color: #64748b;">Toplam Stok</div>
                    </div>
                    <div style="background: #f0fdf4; padding: 1rem; border-radius: 8px; text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 600; color: #059669;" id="availableStock">0</div>
                        <div style="font-size: 0.875rem; color: #64748b;">Mevcut Stok</div>
                    </div>
                    <div style="background: #fef2f2; padding: 1rem; border-radius: 8px; text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 600; color: #dc2626;" id="soldStock">0</div>
                        <div style="font-size: 0.875rem; color: #64748b;">Satılan Stok</div>
                    </div>
                </div>
                
                <!-- Stock Actions -->
                <div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
                    <button class="btn btn-success" onclick="showBulkAddForm()">
                        <i class="fas fa-plus-circle"></i>
                        Toplu Stok Ekle
                    </button>
                    <button class="btn btn-primary" onclick="showSingleAddForm()">
                        <i class="fas fa-plus"></i>
                        Tek Hesap Ekle
                    </button>
                    <button class="btn btn-warning" onclick="cleanEmptyStock()">
                        <i class="fas fa-broom"></i>
                        Boş Stokları Temizle
                    </button>
                    <button class="btn btn-info" onclick="refreshStockList()">
                        <i class="fas fa-sync"></i>
                        Yenile
                    </button>
                </div>
                
                <!-- Bulk Add Form -->
                <div id="bulkAddForm" style="display: none; background: #f8fafc; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                <h4>Toplu Stok Ekleme</h4>
                <p style="color: #64748b; margin-bottom: 1rem;">Her satıra bir hesap bilgisi yazın veya txt dosyaları yükleyin. <strong>İstediğiniz format kullanabilirsiniz:</strong><br>
                • username:password (şifre ile)<br>
                • sadece kullanıcı adı (şifre boş kalacak)<br>
                • <strong>TXT Dosyaları:</strong> Her txt dosyası 1 hesap anlamına gelir (içerik önemli değil)</p>
                
                <!-- Metin Girişi -->
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Metin Girişi:</label>
                    <textarea id="bulkStockData" placeholder="Örnek 1: email1@example.com:password1&#10;Örnek 2: email2@example.com&#10;Örnek 3: username123:mypassword&#10;Örnek 4: sadece_kullanici_adi" 
                              style="width: 100%; height: 150px; padding: 1rem; border: 1px solid #d1d5db; border-radius: 8px; font-family: monospace;"></textarea>
                </div>
                
                <!-- TXT Dosya Yükleme -->
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; margin-bottom: 0.5rem; display: block;">TXT Dosyaları Yükle:</label>
                    <p style="color: #64748b; font-size: 0.875rem; margin-bottom: 0.5rem;">Her txt dosyası 1 hesap anlamına gelir. Dosya içeriği önemli değil.</p>
                    <input type="file" id="txtFiles" multiple accept=".txt" 
                           style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; background: white;">
                    <div id="selectedFiles" style="margin-top: 0.5rem; font-size: 0.875rem; color: #64748b;"></div>
                </div>
                
                    <div style="margin-top: 1rem; display: flex; gap: 1rem;">
                        <button class="btn btn-success" onclick="addBulkStock()">
                            <i class="fas fa-save"></i>
                            Stokları Ekle
                        </button>
                        <button class="btn btn-secondary" onclick="hideBulkAddForm()">
                            İptal
                        </button>
                    </div>
                </div>
                
                <!-- Single Add Form -->
                <div id="singleAddForm" style="display: none; background: #f8fafc; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                    <h4>Tek Hesap Ekleme</h4>
                    <p style="color: #64748b; margin-bottom: 1rem; font-size: 0.875rem;">İstediğiniz formatı kullanabilirsiniz: <code>username:password</code> veya sadece <code>username</code></p>
                    <input type="text" id="singleAccountData" placeholder="email@example.com:password veya sadece email@example.com" 
                           style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; margin-bottom: 1rem;">
                    <div style="display: flex; gap: 1rem;">
                        <button class="btn btn-success" onclick="addSingleStock()">
                            <i class="fas fa-save"></i>
                            Hesap Ekle
                        </button>
                        <button class="btn btn-secondary" onclick="hideSingleAddForm()">
                            İptal
                        </button>
                    </div>
                </div>
                
                <!-- Stock List -->
                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px;">
                    <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                        <h4 style="margin: 0;">Stok Listesi</h4>
                        <div id="stockPagination"></div>
                    </div>
                    <div id="stockList" style="max-height: 400px; overflow-y: auto;">
                        <!-- Stock items will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Currency formatting function for JavaScript
        function formatCurrency(amount) {
            // Get currency from PHP - we'll use a simple approach for now
            // You can enhance this by passing the currency from PHP to JavaScript
            const currency = '<?php echo getDefaultCurrency(); ?>';
            const symbols = {
                'TRY': '₺',
                'USD': '$',
                'EUR': '€'
            };
            const symbol = symbols[currency] || '$';
            return symbol + parseFloat(amount).toFixed(2);
        }

        let currentEditId = null;

        // Modal functions
        function openAddModal() {
            currentEditId = null;
            document.getElementById('modalTitle').textContent = 'Yeni Hesap Ekle';
            document.getElementById('accountForm').reset();
            document.getElementById('accountId').value = '';
            document.getElementById('accountModal').style.display = 'block';
        }

        function editAccount(id) {
            currentEditId = id;
            document.getElementById('modalTitle').textContent = 'Hesap Düzenle';
            
            // Get account data
            fetch('accounts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_account&id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const account = data.account;
                    document.getElementById('accountId').value = account.id;
                    document.getElementById('title').value = account.title;
                    document.getElementById('category').value = account.category_name || '';
                    document.getElementById('description').value = account.description;
                    document.getElementById('price').value = account.price;
                    document.getElementById('old_price').value = account.old_price || '';
                    document.getElementById('platform').value = account.platform;
                    document.getElementById('account_type').value = account.account_type || '';
                    document.getElementById('features').value = account.features || '';
                    document.getElementById('technical_info').value = account.technical_info || '';
                    document.getElementById('is_verified').checked = account.is_verified == 1;
                    document.getElementById('is_premium').checked = account.is_premium == 1;
                    document.getElementById('is_secure').checked = account.is_secure == 1;
                    document.getElementById('instant_delivery').checked = account.instant_delivery == 1;
                    document.getElementById('support_24_7').checked = account.support_24_7 == 1;
                    document.getElementById('guarantee_30_days').checked = account.guarantee_30_days == 1;
                    document.getElementById('warranty_days').value = account.warranty_days || 30;
                    document.getElementById('delivery_type').value = account.delivery_type || 'instant';
                    document.getElementById('location').value = account.location || '';
                    document.getElementById('status').value = account.status;
                    
                    document.getElementById('accountModal').style.display = 'block';
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Bir hata oluştu', 'error');
            });
        }

        function closeModal() {
            document.getElementById('accountModal').style.display = 'none';
        }

        function deleteAccount(id) {
            if (confirm('Bu hesabı silmek istediğinizden emin misiniz?')) {
                fetch('accounts.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete_account&id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    showToast(data.message, data.success ? 'success' : 'error');
                    if (data.success) {
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    }
                })
                .catch(error => {
                    showToast('Bir hata oluştu', 'error');
                });
            }
        }

        // Form submit
        document.getElementById('accountForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const action = currentEditId ? 'update_account' : 'add_account';
            formData.append('action', action);
            
            fetch('accounts.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    closeModal();
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                }
            })
            .catch(error => {
                showToast('Bir hata oluştu', 'error');
            });
        });

        // Filter function
        function filterAccounts() {
            const search = document.getElementById('searchInput').value;
            const category = document.getElementById('categoryFilter').value;
            const status = document.getElementById('statusFilter').value;
            const stockFilter = document.getElementById('stockFilter').value;
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (category) params.append('category', category);
            if (status) params.append('status', status);
            if (stockFilter) params.append('stock_filter', stockFilter);
            
            window.location.href = 'accounts.php?' + params.toString();
        }

        // Enter key search
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                filterAccounts();
            }
        });

        // Stock Management Functions
        let currentStockAccountId = null;
        let currentStockPage = 1;

        function openStockModal(accountId, accountTitle) {
            currentStockAccountId = accountId;
            currentStockPage = 1;
            document.getElementById('stockModalTitle').textContent = `Stok Yönetimi - ${accountTitle}`;
            document.getElementById('stockModal').style.display = 'block';
            
            loadStockCounts();
            loadStockList();
        }

        function closeStockModal() {
            document.getElementById('stockModal').style.display = 'none';
            currentStockAccountId = null;
            
            // Hide forms
            hideBulkAddForm();
            hideSingleAddForm();
        }

        function loadStockCounts() {
            if (!currentStockAccountId) return;
            
            fetch('accounts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_stock_counts&account_id=${currentStockAccountId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('totalStock').textContent = data.counts.total || 0;
                    document.getElementById('availableStock').textContent = data.counts.available || 0;
                    document.getElementById('soldStock').textContent = data.counts.sold || 0;
                }
            })
            .catch(error => console.error('Error loading stock counts:', error));
        }

        function loadStockList(page = 1) {
            if (!currentStockAccountId) return;
            
            currentStockPage = page;
            
            fetch('accounts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_stock_list&account_id=${currentStockAccountId}&page=${page}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderStockList(data.stocks);
                    renderStockPagination(data.pages, page);
                } else {
                    document.getElementById('stockList').innerHTML = '<div style="padding: 2rem; text-align: center; color: #64748b;">Stok yüklenirken hata oluştu</div>';
                }
            })
            .catch(error => {
                console.error('Error loading stock list:', error);
                document.getElementById('stockList').innerHTML = '<div style="padding: 2rem; text-align: center; color: #ef4444;">Bağlantı hatası</div>';
            });
        }

        function renderStockList(stocks) {
            const container = document.getElementById('stockList');
            
            if (!stocks || stocks.length === 0) {
                container.innerHTML = '<div style="padding: 2rem; text-align: center; color: #64748b;">Henüz stok eklenmemiş</div>';
                return;
            }
            
            const stocksHtml = stocks.map(stock => {
                const statusClass = stock.is_sold ? 'danger' : 'success';
                const statusText = stock.is_sold ? 'Satıldı' : 'Mevcut';
                const soldInfo = stock.is_sold ? `<div style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">${stock.username || 'Bilinmeyen'} - ${new Date(stock.sold_at).toLocaleDateString('tr-TR')}</div>` : '';
                
                return `
                    <div style="padding: 1rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                        <div style="flex: 1;">
                            <div style="font-family: monospace; font-weight: 500;">${stock.account_data}</div>
                            ${soldInfo}
                        </div>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <span class="status-badge status-${statusClass}">${statusText}</span>
                            ${!stock.is_sold ? `
                                <button class="btn btn-warning btn-sm" onclick="editStock(${stock.id}, '${stock.account_data}')" title="Düzenle">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="deleteStock(${stock.id})" title="Sil">
                                    <i class="fas fa-trash"></i>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `;
            }).join('');
            
            container.innerHTML = stocksHtml;
        }

        function renderStockPagination(totalPages, currentPage) {
            const container = document.getElementById('stockPagination');
            
            if (totalPages <= 1) {
                container.innerHTML = '';
                return;
            }
            
            let paginationHtml = '';
            
            if (currentPage > 1) {
                paginationHtml += `<button class="btn btn-sm" onclick="loadStockList(${currentPage - 1})"><</button>`;
            }
            
            for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
                const isActive = i === currentPage;
                paginationHtml += `<button class="btn btn-sm ${isActive ? 'btn-primary' : ''}" onclick="loadStockList(${i})">${i}</button>`;
            }
            
            if (currentPage < totalPages) {
                paginationHtml += `<button class="btn btn-sm" onclick="loadStockList(${currentPage + 1})">></button>`;
            }
            
            container.innerHTML = `<div style="display: flex; gap: 0.25rem;">${paginationHtml}</div>`;
        }

        function showBulkAddForm() {
            hideSingleAddForm();
            document.getElementById('bulkAddForm').style.display = 'block';
            document.getElementById('bulkStockData').focus();
        }

        function hideBulkAddForm() {
            document.getElementById('bulkAddForm').style.display = 'none';
            document.getElementById('bulkStockData').value = '';
            document.getElementById('txtFiles').value = '';
            document.getElementById('selectedFiles').innerHTML = '';
        }

        function showSingleAddForm() {
            hideBulkAddForm();
            document.getElementById('singleAddForm').style.display = 'block';
            document.getElementById('singleAccountData').focus();
        }

        function hideSingleAddForm() {
            document.getElementById('singleAddForm').style.display = 'none';
            document.getElementById('singleAccountData').value = '';
        }

     function addBulkStock() {
    const stockData = document.getElementById('bulkStockData').value.trim();
    const txtFiles = document.getElementById('txtFiles').files;
    
    // Metin verisi ve dosya kontrolü
    if (!stockData && txtFiles.length === 0) {
        showToast('Lütfen stok verilerini girin veya txt dosyaları seçin', 'error');
        return;
    }
    
    let finalStockData = stockData;
    
    // TXT dosyalarını işle
    if (txtFiles.length > 0) {
        const filePromises = Array.from(txtFiles).map(file => {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const content = e.target.result.trim();
                    // Her dosyanın tüm içeriğini tek bir hesap bilgisi olarak kullan
                    resolve(content);
                };
                reader.onerror = reject;
                reader.readAsText(file);
            });
        });
        
        // Tüm dosyaları oku
        Promise.all(filePromises).then(fileContents => {
            // Her dosya içeriği (tüm satırlarıyla birlikte) ayrı bir hesap
            const validContents = fileContents.filter(content => content.trim() !== '');
            
            // Her dosyanın tüm içeriğini tek bir hesap olarak ekle
            // Dosyalar arasında özel ayırıcı kullan: |||DOSYA_AYIRICI|||
            if (finalStockData) {
                // Metin girişi varsa, önce metin girişini ekle, sonra dosyaları özel ayırıcı ile
                finalStockData = finalStockData + '|||DOSYA_AYIRICI|||' + validContents.join('|||DOSYA_AYIRICI|||');
            } else {
                // Sadece dosyalar varsa özel ayırıcı kullan
                finalStockData = validContents.join('|||DOSYA_AYIRICI|||');
            }
            
            // Dosya okuma tamamlandıktan sonra isteği gönder
            sendBulkStockRequest(finalStockData);
        }).catch(error => {
            showToast('Dosya okuma hatası: ' + error.message, 'error');
        });
        
        return; // Promise ile asenkron işlem yapıldığı için burada return
    }
    
    // Dosya yoksa direkt isteği gönder
    sendBulkStockRequest(finalStockData);
}
        
        function sendBulkStockRequest(stockData) {
            fetch('accounts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add_bulk_stock&account_id=${currentStockAccountId}&stock_data=${encodeURIComponent(stockData)}`
            })
            .then(response => response.json())
            .then(data => {
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    hideBulkAddForm();
                    loadStockCounts();
                    loadStockList();
                }
            })
            .catch(error => {
                showToast('Bir hata oluştu', 'error');
                console.error('Error adding bulk stock:', error);
            });
        }

        function addSingleStock() {
            const accountData = document.getElementById('singleAccountData').value.trim();
            
            if (!accountData) {
                showToast('Lütfen hesap bilgisini girin', 'error');
                return;
            }
            
            fetch('accounts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add_single_stock&account_id=${currentStockAccountId}&account_data=${encodeURIComponent(accountData)}`
            })
            .then(response => response.json())
            .then(data => {
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    hideSingleAddForm();
                    loadStockCounts();
                    loadStockList();
                }
            })
            .catch(error => {
                showToast('Bir hata oluştu', 'error');
                console.error('Error adding single stock:', error);
            });
        }

        function editStock(stockId, currentData) {
            const newData = prompt('Yeni hesap bilgisini girin:', currentData);
            
            if (newData === null || newData.trim() === '') {
                return;
            }
            
            fetch('accounts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_stock&stock_id=${stockId}&account_data=${encodeURIComponent(newData.trim())}`
            })
            .then(response => response.json())
            .then(data => {
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    loadStockList(currentStockPage);
                }
            })
            .catch(error => {
                showToast('Bir hata oluştu', 'error');
                console.error('Error updating stock:', error);
            });
        }

        function deleteStock(stockId) {
            if (!confirm('Bu stoku silmek istediğinizden emin misiniz?')) {
                return;
            }
            
            fetch('accounts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete_stock&stock_id=${stockId}`
            })
            .then(response => response.json())
            .then(data => {
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    loadStockCounts();
                    loadStockList(currentStockPage);
                }
            })
            .catch(error => {
                showToast('Bir hata oluştu', 'error');
                console.error('Error deleting stock:', error);
            });
        }

        function cleanEmptyStock() {
            if (!confirm('Boş stokları temizlemek istediğinizden emin misiniz?')) {
                return;
            }
            
            fetch('accounts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=clean_empty_stock&account_id=${currentStockAccountId}`
            })
            .then(response => response.json())
            .then(data => {
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    loadStockCounts();
                    loadStockList(currentStockPage);
                }
            })
            .catch(error => {
                showToast('Bir hata oluştu', 'error');
                console.error('Error cleaning empty stock:', error);
            });
        }

        function refreshStockList() {
            loadStockCounts();
            loadStockList(currentStockPage);
            showToast('Stok listesi yenilendi', 'success');
        }

        // Dosya seçimi gösterimi
        document.addEventListener('DOMContentLoaded', function() {
            const txtFilesInput = document.getElementById('txtFiles');
            if (txtFilesInput) {
                txtFilesInput.addEventListener('change', function() {
                    const selectedFilesDiv = document.getElementById('selectedFiles');
                    const files = this.files;
                    
                    if (files.length > 0) {
                        const fileNames = Array.from(files).map(file => file.name).join(', ');
                        selectedFilesDiv.innerHTML = `<strong>${files.length} dosya seçildi:</strong> ${fileNames}`;
                    } else {
                        selectedFilesDiv.innerHTML = '';
                    }
                });
            }
        });

        // Toast notification
        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            const accountModal = document.getElementById('accountModal');
            const stockModal = document.getElementById('stockModal');
            
            if (e.target === accountModal) {
                closeModal();
            }
            if (e.target === stockModal) {
                closeStockModal();
            }
        });
    </script>
</body>
</html>

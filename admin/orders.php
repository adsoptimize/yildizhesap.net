<?php
// Admin authentication
require_once 'auth_header.php';

// AJAX işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    try {
        switch($action) {
            case 'update_status':
                $orderId = intval($_POST['order_id']);
                $status = sanitizeInput($_POST['status']);
                
                if ($orderId <= 0) {
                    throw new Exception('Geçersiz sipariş ID');
                }
                
                $validStatuses = ['pending', 'processing', 'completed', 'cancelled'];
                if (!in_array($status, $validStatuses)) {
                    throw new Exception('Geçersiz durum');
                }
                
                $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$status, $orderId]);
                
                echo json_encode(['success' => true, 'message' => 'Sipariş durumu güncellendi']);
                exit;
                
            case 'update_delivery_status':
                $orderId = intval($_POST['order_id']);
                $deliveryStatus = sanitizeInput($_POST['delivery_status']);
                
                if ($orderId <= 0) {
                    throw new Exception('Geçersiz sipariş ID');
                }
                
                $validDeliveryStatuses = ['pending', 'partial', 'delivered', 'failed'];
                if (!in_array($deliveryStatus, $validDeliveryStatuses)) {
                    throw new Exception('Geçersiz teslimat durumu');
                }
                
                $stmt = $pdo->prepare("UPDATE orders SET delivery_status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$deliveryStatus, $orderId]);
                
                echo json_encode(['success' => true, 'message' => 'Teslimat durumu güncellendi']);
                exit;
                
            case 'get_order_details':
                $orderId = intval($_POST['order_id']);
                
                if ($orderId <= 0) {
                    throw new Exception('Geçersiz sipariş ID');
                }
                
                $stmt = $pdo->prepare("
                    SELECT o.*, u.first_name, u.last_name, u.email, u.phone
                    FROM orders o
                    JOIN users u ON o.user_id COLLATE latin1_swedish_ci = u.id COLLATE latin1_swedish_ci
                    WHERE o.id = ?
                ");
                $stmt->execute([$orderId]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$order) {
                    throw new Exception('Sipariş bulunamadı');
                }
                
                // Teslim edilen hesapları getir - hem account_stock hem de order_accounts tablolarından
                $accountsStmt = $pdo->prepare("
                    SELECT oa.username, oa.password, oa.account_created_date as sold_at, a.title as account_title
                    FROM order_accounts oa
                    LEFT JOIN account_stock ast ON ast.order_id = oa.order_id
                    LEFT JOIN accounts a ON ast.account_id COLLATE latin1_swedish_ci = a.id COLLATE latin1_swedish_ci
                    WHERE oa.order_id = ?
                    ORDER BY oa.account_created_date DESC
                ");
                $accountsStmt->execute([$orderId]);
                $deliveredAccounts = $accountsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                $order['delivered_accounts'] = $deliveredAccounts;
                
                echo json_encode(['success' => true, 'order' => $order]);
                exit;
                
            case 'delete_order':
                $orderId = intval($_POST['order_id']);
                
                if ($orderId <= 0) {
                    throw new Exception('Geçersiz sipariş ID');
                }
                
                // Sadece pending veya cancelled siparişler silinebilir
                $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
                $stmt->execute([$orderId]);
                $order = $stmt->fetch();
                
                if (!$order) {
                    throw new Exception('Sipariş bulunamadı');
                }
                
                if (!in_array($order['status'], ['pending', 'cancelled'])) {
                    throw new Exception('Sadece bekleyen veya iptal edilmiş siparişler silinebilir');
                }
                
                $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
                $stmt->execute([$orderId]);
                
                echo json_encode(['success' => true, 'message' => 'Sipariş silindi']);
                exit;
                
            case 'auto_deliver_accounts':
                try {
                    error_log("=== AUTO DELIVER ACCOUNTS START ===");
                    error_log("Auto deliver accounts called with POST data: " . print_r($_POST, true));
                    
                    $orderId = intval($_POST['order_id']);
                    
                    if ($orderId <= 0) {
                        throw new Exception('Geçersiz sipariş ID');
                    }
                    
                    // Sipariş bilgilerini al
                    $stmt = $pdo->prepare("
                        SELECT o.*, u.first_name, u.last_name, u.email 
                        FROM orders o 
                        JOIN users u ON o.user_id COLLATE latin1_swedish_ci = u.id COLLATE latin1_swedish_ci 
                        WHERE o.id = ?
                    ");
                    $stmt->execute([$orderId]);
                    $order = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$order) {
                        throw new Exception('Sipariş bulunamadı');
                    }
                    
                    if ($order['status'] === 'cancelled') {
                        throw new Exception('İptal edilmiş siparişe hesap teslim edilemez');
                    }
                    
                    // Siparişin zaten teslim edilip edilmediğini kontrol et
                    if ($order['delivery_status'] === 'delivered') {
                        throw new Exception('Bu sipariş zaten teslim edilmiş');
                    }
                    
                    // Bu siparişe daha önce hesap teslim edilip edilmediğini kontrol et
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as delivered_count 
                        FROM account_stock 
                        WHERE order_id = ? AND is_sold = 1
                    ");
                    $stmt->execute([$orderId]);
                    $deliveredCount = $stmt->fetchColumn();
                    
                    if ($deliveredCount > 0) {
                        throw new Exception("Bu siparişe zaten $deliveredCount hesap teslim edilmiş. Tekrar teslimat yapılamaz.");
                    }
                    
                    // Kategori/platform bilgisini al
                    $category = $order['category'];
                    $quantity = intval($order['quantity']);
                    
                    // Debug: Sipariş bilgilerini logla
                    error_log("Auto Delivery Debug - Order ID: $orderId, Category: $category, Quantity: $quantity");
                    
                    // Debug: Toplam stok sayısını kontrol et
                    $totalStockStmt = $pdo->prepare("
                        SELECT COUNT(*) as total_stock
                        FROM account_stock ast
                        JOIN accounts a ON ast.account_id = a.id
                        WHERE LOWER(a.platform) = LOWER(?) AND ast.is_sold = 0
                    ");
                    $totalStockStmt->execute([$category]);
                    $totalStock = $totalStockStmt->fetchColumn();
                    error_log("Auto Delivery Debug - Total available stock for category '$category': $totalStock");
                    
                    // Mevcut stoktan hesapları al - SADECE GEREKEN MİKTAR KADAR
                    $stmt = $pdo->prepare("
                        SELECT ast.id, ast.account_data, ast.account_id
                        FROM account_stock ast
                        JOIN accounts a ON ast.account_id = a.id
                        WHERE LOWER(a.platform) = LOWER(?) AND ast.is_sold = 0
                        LIMIT ?
                    ");
                    $stmt->execute([$category, $quantity]);
                    $availableAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Debug: Bulunan hesap sayısını logla
                    error_log("Auto Delivery Debug - Found accounts: " . count($availableAccounts) . ", Required: $quantity");
                    
                    // Debug: Bulunan hesapların ID'lerini logla
                    $accountIds = array_column($availableAccounts, 'id');
                    error_log("Auto Delivery Debug - Account IDs to be processed: " . implode(', ', $accountIds));
                    
                    if (count($availableAccounts) < $quantity) {
                        throw new Exception("Yeterli hesap bulunamadı. Gereken: $quantity, Mevcut: " . count($availableAccounts));
                    }
                    
                    // Transaction başlat
                    $pdo->beginTransaction();
                    
                    try {
                        // Hesapları siparişe ata ve teslim et
                        $deliveredCount = 0;
                        $deliveredAccounts = [];
                        
                        foreach ($availableAccounts as $account) {
                            // Debug: Her hesap işlenirken ID'yi logla
                            error_log("Auto Delivery Debug - Processing account ID: " . $account['id']);
                            
                            // Gerekli miktar kadar işlem yapıldıysa döngüyü sonlandır
                            if ($deliveredCount >= $quantity) {
                                error_log("Auto Delivery Debug - Required quantity reached ($quantity), stopping delivery");
                                break;
                            }
                            
                            // account_stock'u güncelle
                            $stmt = $pdo->prepare("
                                UPDATE account_stock 
                                SET is_sold = 1, order_id = ?, sold_at = NOW() 
                                WHERE id = ? AND is_sold = 0
                            ");
                            $result = $stmt->execute([$orderId, $account['id']]);
                            
                            if ($stmt->rowCount() == 0) {
                                throw new Exception("Hesap zaten satılmış: ID " . $account['id']);
                            }
                            
                            // account_data'dan hesap bilgilerini parse et (format: username:password)
                            // Deprecated uyarısını çözmek için null kontrolü ekle
                            $accountData = isset($account['account_data']) ? trim($account['account_data']) : '';
                            $accountParts = explode(':', $accountData, 2);
                            $username = $accountParts[0] ?? '';
                            $password = $accountParts[1] ?? '';
                            
                            // order_accounts tablosuna hesap bilgilerini ekle
                            $stmt = $pdo->prepare("
                                INSERT INTO order_accounts (order_id, username, password, account_created_date)
                                VALUES (?, ?, ?, CURDATE())
                            ");
                            $stmt->execute([
                                $orderId,
                                $username,
                                $password
                            ]);
                            
                            $deliveredAccounts[] = [
                                'username' => $username,
                                'password' => $password
                            ];
                            
                            $deliveredCount++;
                            error_log("Auto Delivery Debug - Account ID " . $account['id'] . " delivered. Total delivered so far: $deliveredCount");
                        }
                        
                        // Debug: Final count kontrolü
                        error_log("Auto Delivery Debug - Loop completed. Final delivered count: $deliveredCount, Expected: $quantity");
                        
                        // Teslim edilen hesap sayısını kontrol et
                        if ($deliveredCount !== $quantity) {
                            throw new Exception("Teslimat hatası: Beklenen $quantity hesap, teslim edilen $deliveredCount hesap");
                        }
                        
                        // Sipariş durumunu güncelle ve teslim edilen hesap bilgilerini kaydet
                        $deliveredAccountsJson = json_encode($deliveredAccounts);
                        $stmt = $pdo->prepare("
                            UPDATE orders 
                            SET status = 'completed', delivery_status = 'delivered', delivered_accounts = ?, updated_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([$deliveredAccountsJson, $orderId]);
                        
                        // Debug log
                        error_log("Order $orderId updated with delivered accounts: " . $deliveredAccountsJson);
                        
                        // Transaction'ı commit et
                        $pdo->commit();
                        
                        error_log("Auto Delivery Success - Order ID: $orderId, Delivered: $deliveredCount");
                        error_log("=== AUTO DELIVER ACCOUNTS END ===");
                        
                        echo json_encode([
                            'success' => true, 
                            'message' => "$deliveredCount hesap başarıyla teslim edildi",
                            'delivered_count' => $deliveredCount
                        ]);
                        exit;
                        
                    } catch (Exception $e) {
                        // Transaction'ı rollback et
                        $pdo->rollBack();
                        throw $e;
                    }
                    
                } catch (Exception $e) {
                    error_log("Auto Delivery Error - Order ID: $orderId, Error: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    exit;
                }
                
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
$status = $_GET['status'] ?? '';
$deliveryStatus = $_GET['delivery_status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

// Sorgu oluşturma
$whereConditions = ['1=1'];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(o.order_id LIKE ? OR o.product_name LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($status)) {
    $whereConditions[] = "o.status = ?";
    $params[] = $status;
}

if (!empty($deliveryStatus)) {
    $whereConditions[] = "o.delivery_status = ?";
    $params[] = $deliveryStatus;
}

if (!empty($dateFrom)) {
    $whereConditions[] = "DATE(o.order_date) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $whereConditions[] = "DATE(o.order_date) <= ?";
    $params[] = $dateTo;
}

$whereClause = implode(' AND ', $whereConditions);

// Toplam sayı
$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM orders o
    JOIN users u ON o.user_id COLLATE latin1_swedish_ci = u.id COLLATE latin1_swedish_ci
    WHERE $whereClause
");
$countStmt->execute($params);
$totalOrders = $countStmt->fetchColumn();
$totalPages = ceil($totalOrders / $limit);

// Siparişleri getir
$stmt = $pdo->prepare("
    SELECT o.*, u.first_name, u.last_name, u.email, u.phone
    FROM orders o
    JOIN users u ON o.user_id COLLATE latin1_swedish_ci = u.id COLLATE latin1_swedish_ci
    WHERE $whereClause
    ORDER BY o.order_date DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// İstatistikler
$statsStmt = $pdo->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_price) as total_revenue,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
        COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_orders,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders
    FROM orders
");
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$pageTitle = 'Sipariş Yönetimi';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
    

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px var(--shadow);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .stat-card.total .stat-value { color: var(--primary); }
        .stat-card.revenue .stat-value { color: var(--success); }
        .stat-card.pending .stat-value { color: var(--warning); }
        .stat-card.processing .stat-value { color: var(--info); }
        .stat-card.completed .stat-value { color: var(--success); }
        .stat-card.cancelled .stat-value { color: var(--danger); }

        /* Content Card */
        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 8px 32px var(--shadow);
            overflow: hidden;
        }

        /* Filters */
        .filters {
            padding: 2rem;
            border-bottom: 1px solid var(--border);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

    

        .form-group label {
            font-weight: 600;
            color: var(--dark);
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

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            min-height: 44px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
            min-height: 32px;
        }

        .btn-success { background: var(--success); color: white; }
        .btn-warning { background: var(--warning); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-info { background: var(--info); color: white; }
        .btn-secondary { background: #6b7280; color: white; }

        /* Table */
        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .table th {
            background: #f8fafc;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .table tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        /* Status badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-pending {
            background: rgba(251, 191, 36, 0.1);
            color: #f59e0b;
        }

        .status-processing {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .status-completed {
            background: rgba(74, 222, 128, 0.1);
            color: var(--success);
        }

        .status-cancelled {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .delivery-pending {
            background: rgba(156, 163, 175, 0.1);
            color: #6b7280;
        }

        .delivery-partial {
            background: rgba(251, 191, 36, 0.1);
            color: #f59e0b;
        }

        .delivery-delivered {
            background: rgba(74, 222, 128, 0.1);
            color: var(--success);
        }

        .delivery-failed {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 2rem 0;
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

    
    </style>

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

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-value"><?= number_format($stats['total_orders']) ?></div>
                    <div class="stat-label">Toplam Sipariş</div>
                </div>
                <div class="stat-card revenue">
                    <div class="stat-value"><?= formatPrice($stats['total_revenue']) ?></div>
                    <div class="stat-label">Toplam Gelir</div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-value"><?= number_format($stats['pending_orders']) ?></div>
                    <div class="stat-label">Bekleyen</div>
                </div>
                <div class="stat-card processing">
                    <div class="stat-value"><?= number_format($stats['processing_orders']) ?></div>
                    <div class="stat-label">İşlemde</div>
                </div>
                <div class="stat-card completed">
                    <div class="stat-value"><?= number_format($stats['completed_orders']) ?></div>
                    <div class="stat-label">Tamamlanan</div>
                </div>
                <div class="stat-card cancelled">
                    <div class="stat-value"><?= number_format($stats['cancelled_orders']) ?></div>
                    <div class="stat-label">İptal Edilen</div>
                </div>
            </div>

            <!-- Content -->
            <div class="content-card">
                <!-- Filters -->
                <div class="filters">
                    <div class="form-group">
                        <label>Arama</label>
                        <input type="text" class="form-control" id="searchInput" placeholder="Sipariş ID, ürün, müşteri ara..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="form-group">
                        <label>Durum</label>
                        <select class="form-control" id="statusFilter">
                            <option value="">Tüm Durumlar</option>
                            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Bekleyen</option>
                            <option value="processing" <?= $status === 'processing' ? 'selected' : '' ?>>İşlemde</option>
                            <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Tamamlanan</option>
                            <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>İptal Edilen</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Teslimat Durumu</label>
                        <select class="form-control" id="deliveryStatusFilter">
                            <option value="">Tüm Teslimat Durumları</option>
                            <option value="pending" <?= $deliveryStatus === 'pending' ? 'selected' : '' ?>>Bekliyor</option>
                            <option value="partial" <?= $deliveryStatus === 'partial' ? 'selected' : '' ?>>Kısmi</option>
                            <option value="delivered" <?= $deliveryStatus === 'delivered' ? 'selected' : '' ?>>Teslim Edildi</option>
                            <option value="failed" <?= $deliveryStatus === 'failed' ? 'selected' : '' ?>>Başarısız</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Başlangıç Tarihi</label>
                        <input type="date" class="form-control" id="dateFromFilter" value="<?= htmlspecialchars($dateFrom) ?>">
                    </div>
                    <div class="form-group">
                        <label>Bitiş Tarihi</label>
                        <input type="date" class="form-control" id="dateToFilter" value="<?= htmlspecialchars($dateTo) ?>">
                    </div>
                    <br>
                    <div class="form-group">
                        <button class="btn btn-primary" onclick="filterOrders()">
                            <i class="fas fa-search"></i>
                            Filtrele
                        </button>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Sipariş ID</th>
                                <th>Müşteri</th>
                                <th>Ürün</th>
                                <th>Miktar</th>
                                <th>Tutar</th>
                                <th>Durum</th>
                                <th>Teslimat</th>
                                <th>Tarih</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600; color: var(--primary);">#<?= htmlspecialchars($order['order_id']) ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></div>
                                        <div style="font-size: 0.75rem; color: #64748b;"><?= htmlspecialchars($order['email']) ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 500;"><?= htmlspecialchars($order['product_name']) ?></div>
                                        <div style="font-size: 0.75rem; color: #64748b;"><?= htmlspecialchars($order['category']) ?></div>
                                    </td>
                                    <td><?= $order['quantity'] ?></td>
                                    <td><?= formatPrice($order['total_price']) ?></td>
                                    <td>
                                        <select class="status-badge status-<?= $order['status'] ?> form-control" style="border: none; background: transparent; font-size: 0.75rem; padding: 0.25rem;" onchange="updateStatus(<?= $order['id'] ?>, this.value, 'status')">
                                            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Bekleyen</option>
                                            <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>İşlemde</option>
                                            <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Tamamlanan</option>
                                            <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>İptal Edilen</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="status-badge delivery-<?= $order['delivery_status'] ?> form-control" style="border: none; background: transparent; font-size: 0.75rem; padding: 0.25rem;" onchange="updateStatus(<?= $order['id'] ?>, this.value, 'delivery_status')">
                                            <option value="pending" <?= $order['delivery_status'] === 'pending' ? 'selected' : '' ?>>Bekliyor</option>
                                            <option value="partial" <?= $order['delivery_status'] === 'partial' ? 'selected' : '' ?>>Kısmi</option>
                                            <option value="delivered" <?= $order['delivery_status'] === 'delivered' ? 'selected' : '' ?>>Teslim Edildi</option>
                                            <option value="failed" <?= $order['delivery_status'] === 'failed' ? 'selected' : '' ?>>Başarısız</option>
                                        </select>
                                    </td>
                                    <td><?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                            <button class="btn btn-info btn-sm" onclick="viewOrderDetails(<?= $order['id'] ?>)" title="Detaylar">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-success btn-sm" onclick="viewDeliveredAccounts(<?= $order['id'] ?>)" title="Teslim Edilen Hesaplar">
                                                <i class="fas fa-user-check"></i>
                                            </button>
                                            <?php 
                                            // Siparişe daha önce hesap teslim edilip edilmediğini kontrol et
                                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM account_stock WHERE order_id = ? AND is_sold = 1");
                                            $stmt->execute([$order['id']]);
                                            $hasDeliveredAccounts = $stmt->fetchColumn() > 0;
                                            
                                            // Debug bilgisi
                                            $debugInfo = "Order ID: {$order['id']}, Status: {$order['status']}, Delivery Status: {$order['delivery_status']}, Has Delivered: " . ($hasDeliveredAccounts ? 'Yes' : 'No');
                                            
                                            // Daha esnek koşullar - sadece iptal edilmiş siparişler için buton gizlensin
                                            if ($order['status'] !== 'cancelled'): 
                                            ?>
                                                <button class="btn btn-warning btn-sm" onclick="autoDeliverAccounts(<?= $order['id'] ?>)" title="Otomatik Teslimat">
                                                    <i class="fas fa-rocket"></i>
                                                </button>
                                            <?php else: ?>
                                                <!-- Debug: Buton neden görünmüyor -->
                                                <span style="font-size: 10px; color: #999;" title="<?= htmlspecialchars($debugInfo) ?>">
                                                    İptal Edilmiş
                                                </span>
                                            <?php endif; ?>
                                            <?php if (in_array($order['status'], ['pending', 'cancelled'])): ?>
                                                <button class="btn btn-danger btn-sm" onclick="deleteOrder(<?= $order['id'] ?>)" title="Sil">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 2rem; color: #64748b;">
                                        Henüz sipariş bulunamadı.
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
                            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&delivery_status=<?= urlencode($deliveryStatus) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="current"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&delivery_status=<?= urlencode($deliveryStatus) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&delivery_status=<?= urlencode($deliveryStatus) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Sipariş Detayları</h3>
                <button type="button" class="close" onclick="closeModal()">&times;</button>
            </div>
            <div id="orderDetails">
                <!-- Order details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Delivered Accounts Modal -->
    <div id="accountsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="accountsModalTitle">Teslim Edilen Hesaplar</h3>
                <button type="button" class="close" onclick="closeAccountsModal()">&times;</button>
            </div>
            <div id="accountsDetails">
                <!-- Delivered accounts will be loaded here -->
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

        // Filter function
        function filterOrders() {
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            const deliveryStatus = document.getElementById('deliveryStatusFilter').value;
            const dateFrom = document.getElementById('dateFromFilter').value;
            const dateTo = document.getElementById('dateToFilter').value;
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (status) params.append('status', status);
            if (deliveryStatus) params.append('delivery_status', deliveryStatus);
            if (dateFrom) params.append('date_from', dateFrom);
            if (dateTo) params.append('date_to', dateTo);
            
            window.location.href = 'orders.php?' + params.toString();
        }

        // Enter key search
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                filterOrders();
            }
        });

        // Update status
        function updateStatus(orderId, newStatus, type) {
            const action = type === 'status' ? 'update_status' : 'update_delivery_status';
            const field = type === 'status' ? 'status' : 'delivery_status';
            
            fetch('orders.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${action}&order_id=${orderId}&${field}=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }
            })
            .catch(error => {
                showToast('Bir hata oluştu', 'error');
                console.error('Error:', error);
            });
        }

        // View order details
        function viewOrderDetails(orderId) {
            fetch('orders.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_order_details&order_id=${orderId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const order = data.order;
                    document.getElementById('modalTitle').textContent = `Sipariş Detayları - #${order.order_id}`;
                    
                    const detailsHtml = `
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                            <div>
                                <h4 style="margin-bottom: 1rem; color: var(--dark);">Müşteri Bilgileri</h4>
                                <div style="background: #f8fafc; padding: 1rem; border-radius: 8px;">
                                    <p><strong>Ad Soyad:</strong> ${order.first_name} ${order.last_name}</p>
                                    <p><strong>E-posta:</strong> ${order.email}</p>
                                    <p><strong>Telefon:</strong> ${order.phone || 'Belirtilmemiş'}</p>
                                </div>
                            </div>
                            <div>
                                <h4 style="margin-bottom: 1rem; color: var(--dark);">Sipariş Bilgileri</h4>
                                <div style="background: #f8fafc; padding: 1rem; border-radius: 8px;">
                                    <p><strong>Sipariş ID:</strong> #${order.order_id}</p>
                                    <p><strong>Ürün:</strong> ${order.product_name}</p>
                                    <p><strong>Kategori:</strong> ${order.category}</p>
                                    <p><strong>Miktar:</strong> ${order.quantity}</p>
                                    <p><strong>Birim Fiyat:</strong> ${formatCurrency(parseFloat(order.unit_price))}</p>
                                    <p><strong>Toplam:</strong> ${formatCurrency(parseFloat(order.total_price))}</p>
                                </div>
                            </div>
                        </div>
                        <div style="margin-top: 2rem;">
                            <h4 style="margin-bottom: 1rem; color: var(--dark);">Durum Bilgileri</h4>
                            <div style="background: #f8fafc; padding: 1rem; border-radius: 8px;">
                                <p><strong>Sipariş Durumu:</strong> 
                                    <span class="status-badge status-${order.status}" style="margin-left: 0.5rem;">
                                        ${getStatusText(order.status)}
                                    </span>
                                </p>
                                <p><strong>Teslimat Durumu:</strong> 
                                    <span class="status-badge delivery-${order.delivery_status}" style="margin-left: 0.5rem;">
                                        ${getDeliveryStatusText(order.delivery_status)}
                                    </span>
                                </p>
                                <p><strong>Sipariş Tarihi:</strong> ${new Date(order.order_date).toLocaleString('tr-TR')}</p>
                                <p><strong>Son Güncelleme:</strong> ${new Date(order.updated_at).toLocaleString('tr-TR')}</p>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('orderDetails').innerHTML = detailsHtml;
                    document.getElementById('orderModal').style.display = 'block';
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Bir hata oluştu', 'error');
                console.error('Error:', error);
            });
        }

        // Delete order
        function deleteOrder(orderId) {
            if (confirm('Bu siparişi silmek istediğinizden emin misiniz?')) {
                fetch('orders.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete_order&order_id=${orderId}`
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

        // Auto deliver accounts
        function autoDeliverAccounts(orderId) {
            console.log('autoDeliverAccounts called with orderId:', orderId);
            
            if (!confirm('Bu siparişe otomatik hesap teslimatı yapmak istediğinizden emin misiniz?')) {
                return;
            }
            
            // Butonu devre dışı bırak
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;
            
            fetch('orders.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=auto_deliver_accounts&order_id=${orderId}`
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    showToast(data.message, 'success');
                    // Butonu gizle ve sayfayı yenile
                    button.style.display = 'none';
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showToast(data.message, 'error');
                    // Butonu geri aktif et
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            })
            .catch(error => {
                showToast('Bir hata oluştu', 'error');
                console.error('Error:', error);
                // Butonu geri aktif et
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }

        // View delivered accounts
        function viewDeliveredAccounts(orderId) {
            fetch('orders.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_order_details&order_id=${orderId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const order = data.order;
                    document.getElementById('accountsModalTitle').textContent = `Teslim Edilen Hesaplar - Sipariş #${order.order_id}`;
                    
                    let accountsHtml = '';
                    if (order.delivered_accounts && order.delivered_accounts.length > 0) {
                        accountsHtml = `
                            <div style="margin-bottom: 1rem; padding: 1rem; background: #f0f9ff; border-radius: 8px; border-left: 4px solid var(--info);">
                                <strong>Sipariş Bilgileri:</strong><br>
                                Ürün: ${order.product_name}<br>
                                Miktar: ${order.quantity}<br>
                                Teslim Edilen: ${order.delivered_accounts.length} hesap
                            </div>
                            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                                <div style="background: #f9fafb; padding: 1rem; border-bottom: 1px solid #e5e7eb; font-weight: 600;">
                                    Teslim Edilen Hesap Listesi
                                </div>
                        `;
                        
                        order.delivered_accounts.forEach((account, index) => {
                            accountsHtml += `
                                <div style="padding: 1rem; border-bottom: 1px solid #f3f4f6; display: flex; justify-content: space-between; align-items: center;">
                                    <div style="flex: 1;">
                                        <div style="font-family: monospace; font-weight: 600; color: var(--dark); margin-bottom: 0.25rem;">
                                            ${account.username}:${account.password}
                                        </div>
                                        <div style="font-size: 0.75rem; color: #6b7280;">
                                            Teslim Tarihi: ${new Date(account.sold_at).toLocaleString('tr-TR')}
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <button class="btn btn-info btn-sm" onclick="copyToClipboard('${account.username}:${account.password}')" title="Kopyala">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <span class="status-badge" style="background: rgba(74, 222, 128, 0.1); color: var(--success);">
                                            Teslim Edildi
                                        </span>
                                    </div>
                                </div>
                            `;
                        });
                        
                        accountsHtml += `
                            </div>
                            <div style="margin-top: 1rem; padding: 1rem; background: #f0fdf4; border-radius: 8px; border-left: 4px solid var(--success);">
                                <i class="fas fa-check-circle" style="color: var(--success); margin-right: 0.5rem;"></i>
                                <strong>Toplam ${order.delivered_accounts.length} hesap başarıyla teslim edildi.</strong>
                            </div>
                        `;
                    } else {
                        accountsHtml = `
                            <div style="text-align: center; padding: 3rem; color: #6b7280;">
                                <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                                <h4>Henüz Hesap Teslim Edilmedi</h4>
                                <p>Bu siparişe ait henüz teslim edilen hesap bulunmuyor.</p>
                            </div>
                        `;
                    }
                    
                    document.getElementById('accountsDetails').innerHTML = accountsHtml;
                    document.getElementById('accountsModal').style.display = 'block';
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Bir hata oluştu', 'error');
                console.error('Error:', error);
            });
        }

        // Copy to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                showToast('Hesap bilgileri kopyalandı', 'success');
            }).catch(() => {
                showToast('Kopyalama başarısız', 'error');
            });
        }

        // Close modals
        function closeModal() {
            document.getElementById('orderModal').style.display = 'none';
        }

        function closeAccountsModal() {
            document.getElementById('accountsModal').style.display = 'none';
        }

        // Helper functions
        function getStatusText(status) {
            const statusMap = {
                'pending': 'Bekleyen',
                'processing': 'İşlemde', 
                'completed': 'Tamamlanan',
                'cancelled': 'İptal Edilen'
            };
            return statusMap[status] || status;
        }

        function getDeliveryStatusText(status) {
            const statusMap = {
                'pending': 'Bekliyor',
                'partial': 'Kısmi',
                'delivered': 'Teslim Edildi',
                'failed': 'Başarısız'
            };
            return statusMap[status] || status;
        }

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

        // Close modal on outside click
        window.onclick = function(event) {
            const orderModal = document.getElementById('orderModal');
            const accountsModal = document.getElementById('accountsModal');
            
            if (event.target == orderModal) {
                closeModal();
            }
            
            if (event.target == accountsModal) {
                closeAccountsModal();
            }
        }
    </script>
</body>
</html>

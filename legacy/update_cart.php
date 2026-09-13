<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'Auth.php';

header('Content-Type: application/json');

try {
    // Kullanıcı giriş kontrolü (ziyaretçiler de sepet işlemleri yapabilir)
    $auth = new Auth();
    // Ziyaretçiler de sepet işlemleri yapabilir, giriş kontrolü kaldırıldı

    // POST verilerini al
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST; // Fallback to regular POST
    }
    
    if (!isset($input['action'])) {
        throw new Exception('İşlem türü belirtilmedi.');
    }
    
    $action = $input['action'];
    
    // Sepet session kontrolü
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    switch ($action) {
        case 'update':
            // Miktar güncelleme
            if (!isset($input['account_id']) || !isset($input['quantity'])) {
                throw new Exception('Gerekli parametreler eksik.');
            }
            
            $accountId = (int)$input['account_id'];
            $quantity = (int)$input['quantity'];
            
            if ($accountId <= 0 || $quantity <= 0) {
                throw new Exception('Geçersiz parametre değerleri.');
            }
            
            // Hesap bilgilerini kontrol et
            $stmt = $pdo->prepare("
                SELECT COALESCE(stock_counts.available_stock, 0) as stock_quantity
                FROM accounts a
                LEFT JOIN (
                    SELECT account_id, 
                           COUNT(CASE WHEN is_sold = 0 THEN 1 END) as available_stock
                    FROM account_stock 
                    GROUP BY account_id
                ) stock_counts ON a.id = stock_counts.account_id
                WHERE a.id = ? AND a.status = 'active'
            ");
            $stmt->execute([$accountId]);
            $account = $stmt->fetch();
            
            if (!$account) {
                throw new Exception('Hesap bulunamadı veya aktif değil.');
            }
            
            if ($quantity > $account['stock_quantity']) {
                throw new Exception('Yetersiz stok. Maksimum: ' . $account['stock_quantity']);
            }
            
            // Sepetteki miktarı güncelle
            $_SESSION['cart'][$accountId] = $quantity;
            
            echo json_encode([
                'success' => true,
                'message' => 'Sepet güncellendi!',
                'cart_count' => array_sum($_SESSION['cart'])
            ]);
            break;
            
        case 'remove':
            // Ürün çıkarma
            if (!isset($input['account_id'])) {
                throw new Exception('Ürün ID gerekli.');
            }
            
            $accountId = (int)$input['account_id'];
            
            if (isset($_SESSION['cart'][$accountId])) {
                unset($_SESSION['cart'][$accountId]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Ürün sepetten çıkarıldı!',
                'cart_count' => array_sum($_SESSION['cart'])
            ]);
            break;
            
        case 'clear':
            // Sepeti temizle
            $_SESSION['cart'] = [];
            
            echo json_encode([
                'success' => true,
                'message' => 'Sepet temizlendi!',
                'cart_count' => 0
            ]);
            break;
            
        case 'get_count':
            // Sepetteki toplam ürün sayısını al
            $cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
            
            echo json_encode([
                'success' => true,
                'cart_count' => $cartCount
            ]);
            break;
            
        default:
            throw new Exception('Geçersiz işlem türü.');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

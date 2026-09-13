<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'Auth.php';

header('Content-Type: application/json');

try {
    // Debug bilgileri
    error_log("ADD_TO_CART DEBUG: Session ID: " . session_id());
    error_log("ADD_TO_CART DEBUG: Session token cookie: " . (isset($_COOKIE['session_token']) ? $_COOKIE['session_token'] : 'YOK'));
    error_log("ADD_TO_CART DEBUG: Session data: " . print_r($_SESSION, true));
    
    // Kullanıcı giriş kontrolü (ziyaretçiler de sepete ekleyebilir)
    $auth = new Auth();
    $isLoggedIn = $auth->isLoggedIn();
    error_log("ADD_TO_CART DEBUG: isLoggedIn result: " . ($isLoggedIn ? 'true' : 'false'));
    
    // Ziyaretçiler de sepete ürün ekleyebilir, giriş kontrolü kaldırıldı

    // POST verilerini al
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST; // Fallback to regular POST
    }
    
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
        SELECT a.*, COALESCE(stock_counts.available_stock, 0) as stock_quantity
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
    
    if ($account['stock_quantity'] < $quantity) {
        throw new Exception('Yetersiz stok. Mevcut stok: ' . $account['stock_quantity']);
    }
    
    // Sepete ekle (session'da sakla)
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Eğer aynı ürün varsa miktarını artır, yoksa yeni ekle
    if (isset($_SESSION['cart'][$accountId])) {
        $currentQuantity = $_SESSION['cart'][$accountId];
        $newQuantity = $currentQuantity + $quantity;
        
        // Stok kontrolü - eğer aştıysa maksimum miktara ayarla
        if ($newQuantity > $account['stock_quantity']) {
            $newQuantity = $account['stock_quantity'];
            // Sadece gerçekten farklı bir miktar varsa uyarı ver
            if ($currentQuantity < $account['stock_quantity']) {
                // Maksimuma ayarlandı mesajı (uyarı değil)
                $adjustedMessage = 'Ürün sepete eklendi! Miktar maksimum stok miktarına ayarlandı.';
            } else {
                // Zaten maksimumda
                throw new Exception('Bu üründen zaten maksimum miktarda sepetinizde bulunuyor.');
            }
        }
        
        $_SESSION['cart'][$accountId] = $newQuantity;
    } else {
        $_SESSION['cart'][$accountId] = $quantity;
    }
    
    // Sepetteki toplam ürün sayısını hesapla
    $totalItems = array_sum($_SESSION['cart']);
    
    // Mesaj belirleme
    $successMessage = isset($adjustedMessage) ? $adjustedMessage : 'Ürün sepete eklendi!';
    
    echo json_encode([
        'success' => true,
        'message' => $successMessage,
        'cart_count' => $totalItems,
        'account_title' => $account['title'],
        'quantity' => $quantity,
        'final_quantity' => $_SESSION['cart'][$accountId] // Gerçek sepetteki miktar
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

<?php
require_once '../config.php';
require_once '../functions.php';
require_once '../Auth.php';

// Kullanıcı giriş kontrolü
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$currentUser = $auth->getCurrentUser();
$user_id = $currentUser['id'];

// Sepetteki ürünleri al
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: ../checkout.php?error=empty_cart');
    exit;
}

// Toplam tutarı hesapla
$totalAmount = 0;
$cartItems = [];

foreach ($_SESSION['cart'] as $accountId => $quantity) {
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE id = ? AND status = 'active'");
    $stmt->execute([$accountId]);
    $account = $stmt->fetch();
    
    if ($account && $account['stock_quantity'] >= $quantity) {
        $itemTotal = $account['price'] * $quantity;
        $totalAmount += $itemTotal;
        
        $cartItems[] = [
            'account_id' => $accountId,
            'quantity' => $quantity,
            'price' => $account['price'],
            'title' => $account['title']
        ];
    }
}

if (empty($cartItems)) {
    header('Location: ../checkout.php?error=invalid_items');
    exit;
}

// Cryptomus API bilgileri (admin panelinden alınacak)
$stmt = $pdo->prepare("SELECT setting_value FROM crypto_settings WHERE setting_key = 'cryptomus_payment_key'");
$stmt->execute();
$APIKEY = $stmt->fetchColumn() ?: 'Fpw0XJtQV4xNMGZk4elsbeoXbhNbD4FF2n91CsjfBWeSmHZUPJ0qLmqp71xK9SGLEkHG0P9KBjp1SBmJspoIXgL252OLXRT4J6wLXTlBEZhAF7XKFvbsREe0PEg6DeDm';

$stmt = $pdo->prepare("SELECT setting_value FROM crypto_settings WHERE setting_key = 'cryptomus_merchant_uuid'");
$stmt->execute();
$merchant = $stmt->fetchColumn() ?: '04a2aa40-d91e-48c1-8c2d-3c9cf50e8284';

$callback_url = SITE_URL . 'cryptomus_webhook.php';

// Benzersiz order ID oluştur
$order_id = 'order_' . time() . '_' . $user_id . '_' . uniqid();

// Ödeme verileri
$data = [
    'amount' => (string)$totalAmount,
    'currency' => 'USD',
    'order_id' => $order_id,
    'url_callback' => $callback_url,
    'url_success' => SITE_URL . 'payment_success.php?order_id=' . $order_id,
    'url_return' => SITE_URL . 'payment_cancel.php?order_id=' . $order_id
];

// JSON formatına çevir
$data = json_encode($data);

// İmza oluştur (MD5 + Base64)
$sign = md5(base64_encode($data) . $APIKEY);

// cURL ile API isteği
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.cryptomus.com/v1/payment');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

// Başlıkları ayarla
$headers = [];
$headers[] = 'merchant: ' . $merchant;
$headers[] = 'sign: ' . $sign;
$headers[] = 'Content-Type: application/json';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// İsteği gönder ve sonucu al
$result = curl_exec($ch);

// Hata kontrolü
if (curl_errno($ch)) {
    error_log('Cryptomus cURL Error: ' . curl_error($ch));
    header('Location: ../checkout.php?error=payment_error');
    exit;
}
curl_close($ch);

// Sonucu işle
$response = json_decode($result, true);

// Ödeme URL'sini kontrol et ve yönlendir
if (isset($response['result']['url'])) {
    $payment_url = $response['result']['url'];
    $payment_uuid = $response['result']['uuid'];
    
    // Ödeme kaydını veritabanına ekle
    try {
        $pdo->beginTransaction();
        
        // Ana ödeme kaydı
        $stmt = $pdo->prepare("INSERT INTO crypto_payments (user_id, order_id, amount, currency, status, cryptomus_uuid, payment_url, created_at) VALUES (?, ?, ?, 'USD', 'pending', ?, ?, NOW())");
        $stmt->execute([$user_id, $order_id, $totalAmount, $payment_uuid, $payment_url]);
        $payment_id = $pdo->lastInsertId();
        
        // Ödeme öğelerini kaydet
        $stmt = $pdo->prepare("INSERT INTO payment_items (payment_id, account_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
        foreach ($cartItems as $item) {
            $stmt->execute([
                $payment_id,
                $item['account_id'],
                $item['quantity'],
                $item['price'],
                $item['price'] * $item['quantity']
            ]);
        }
        
        // Sepeti temizle
        unset($_SESSION['cart']);
        
        $pdo->commit();
        
        // Kullanıcıyı ödeme sayfasına yönlendir
        header("Location: " . $payment_url);
        exit();
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log('Database Error: ' . $e->getMessage());
        header('Location: ../checkout.php?error=database_error');
        exit;
    }
} else {
    // Hata durumunda detaylı bilgi göster
    error_log('Cryptomus API Error: ' . json_encode($response));
    header('Location: ../checkout.php?error=api_error');
    exit;
}
?>
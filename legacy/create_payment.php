<?php
// Output buffering başlat
ob_start();

require_once 'config.php';
require_once 'functions.php';
require_once 'Auth.php';
require_once 'ShopierClient.php';

// Kullanıcı giriş kontrolü
$auth = new Auth();
$isGuest = false;

if ($auth->isLoggedIn()) {
    $currentUser = $auth->getCurrentUser();
    $user_id = $currentUser['id'];
} else {
    // Ziyaretçi kontrolü
    $guest_name = $_POST['guest_name'] ?? '';
    $guest_email = $_POST['guest_email'] ?? '';
    $guest_phone = $_POST['guest_phone'] ?? '';
    
    // Debug bilgileri
    error_log('POST Data: ' . json_encode($_POST));
    error_log('Guest Name: ' . $guest_name);
    error_log('Guest Email: ' . $guest_email);
    error_log('Guest Phone: ' . $guest_phone);
    
    if (empty($guest_name) || empty($guest_email)) {
        error_log('Guest info missing - redirecting');
        header('Location: checkout.php?error=guest_info_required');
        exit;
    }
    
    $isGuest = true;
    $user_id = null;
    $currentUser = [
        'id' => null,
        'username' => 'Ziyaretçi',
        'email' => $guest_email,
        'first_name' => $guest_name,
        'last_name' => '',
        'phone' => $guest_phone
    ];
}

// Ödeme yöntemini kontrol et
$paymentMethod = $_POST['payment_method'] ?? '';
if (empty($paymentMethod)) {
    header('Location: checkout.php?error=payment_method_required');
    exit;
}

// Sepetteki ürünleri al
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: checkout.php?error=empty_cart');
    exit;
}

// Toplam tutarı hesapla
$totalAmount = 0;
$cartItems = [];

foreach ($_SESSION['cart'] as $accountId => $quantity) {
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
    header('Location: checkout.php?error=invalid_items');
    exit;
}

// Benzersiz order ID oluştur
if ($isGuest) {
    $order_id = 'guest_order_' . time() . '_' . uniqid();
} else {
    $order_id = 'order_' . time() . '_' . $user_id . '_' . uniqid();
}

// Varsayılan para birimini al
$defaultCurrency = getDefaultCurrency();

// Ödeme yöntemine göre işlem yap
if ($paymentMethod === 'cryptomus') {
    // Cryptomus ödeme işlemi
    $result = processCryptomusPayment($totalAmount, $order_id, $defaultCurrency, $cartItems, $user_id, $isGuest, $currentUser);
} elseif ($paymentMethod === 'shopier') {
    // Shopier ödeme işlemi
    $result = processShopierPayment($totalAmount, $order_id, $defaultCurrency, $cartItems, $user_id, $currentUser, $isGuest);
} else {
    header('Location: checkout.php?error=invalid_payment_method');
    exit;
}

// Sonucu işle
if ($result['success']) {
    $payment_url = $result['payment_url'];
    $payment_uuid = $result['payment_uuid'] ?? '';
        
        // Sepeti temizle
        unset($_SESSION['cart']);
        
    // JavaScript ile yönlendirme
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Ödeme Sayfasına Yönlendiriliyor...</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    text-align: center;
                    padding: 50px;
                    background: #f5f5f5;
                }
                .loader {
                    border: 4px solid #f3f3f3;
                    border-top: 4px solid #3498db;
                    border-radius: 50%;
                    width: 50px;
                    height: 50px;
                    animation: spin 1s linear infinite;
                    margin: 20px auto;
                }
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
        </head>
        <body>
            <h2>Ödeme Sayfasına Yönlendiriliyor...</h2>
            <div class="loader"></div>
        <p>Lütfen bekleyin, ödeme sayfasına yönlendiriliyorsunuz.</p>
            
            <script>
                // 3 saniye sonra yönlendir
                setTimeout(function() {
                    window.location.href = '<?= $payment_url ?>';
                }, 3000);
                
                // Hemen de yönlendirmeyi dene
                window.location.href = '<?= $payment_url ?>';
            </script>
            
            <p><small>Otomatik yönlendirme çalışmazsa <a href="<?= $payment_url ?>">buraya tıklayın</a></small></p>
        </body>
        </html>
        <?php
        exit();
} else {
    // Hata durumunda detaylı bilgi
    error_log('Payment Error: ' . json_encode($result));
    header('Location: checkout.php?error=api_error&message=' . urlencode($result['message']));
    exit;
}

/**
 * Cryptomus ödeme işlemi
 */
function processCryptomusPayment($totalAmount, $order_id, $defaultCurrency, $cartItems, $user_id, $isGuest = false, $currentUser = null) {
    // Cryptomus ayarlarını al
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM crypto_settings WHERE setting_key IN ('cryptomus_merchant_uuid', 'cryptomus_payment_key', 'cryptomus_test_mode')");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $merchant = $settings['cryptomus_merchant_uuid'] ?? '';
    $apiKey = $settings['cryptomus_payment_key'] ?? '';
    $testMode = ($settings['cryptomus_test_mode'] ?? '0') === '1';
    
    if (empty($merchant) || empty($apiKey)) {
        error_log('Cryptomus API settings missing: merchant=' . $merchant . ', apiKey=' . ($apiKey ? 'SET' : 'NOT_SET'));
        return ['success' => false, 'message' => 'Cryptomus API ayarları eksik. Lütfen yönetici ile iletişime geçin.'];
    }
    
    $callback_url = SITE_URL . 'cryptomus_webhook.php';
    
    // Ödeme verileri
    $data = [
        'amount' => (string)$totalAmount,
        'currency' => $defaultCurrency,
        'order_id' => $order_id,
        'url_callback' => $callback_url,
                        'url_success' => SITE_URL . 'payment-success.php?order_id=' . $order_id,
                'url_return' => SITE_URL . 'payment-cancel.php?order_id=' . $order_id
    ];
    
    // JSON formatına çevir
    $data = json_encode($data);
    
    // İmza oluştur (MD5 + Base64)
    $sign = md5(base64_encode($data) . $apiKey);
    
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
        return ['success' => false, 'message' => 'Ödeme sistemi hatası'];
    }
    curl_close($ch);
    
    // Sonucu işle
    $response = json_decode($result, true);
    
    // Debug için response'u logla
    error_log('Cryptomus API Response: ' . json_encode($response));
    error_log('Cryptomus API Request Data: ' . $data);
    error_log('Cryptomus API Headers: ' . json_encode($headers));
    
    // Ödeme URL'sini kontrol et
    if (isset($response['result']['url'])) {
        $payment_url = $response['result']['url'];
        $payment_uuid = $response['result']['uuid'];
        
        // Ödeme kaydını veritabanına ekle
        try {
            $pdo->beginTransaction();
            
            // Ana ödeme kaydı
            if ($isGuest) {
                $stmt = $pdo->prepare("INSERT INTO crypto_payments (user_id, order_id, amount, currency, status, cryptomus_uuid, payment_url, payment_method, is_guest_order, email, customer_name, phone, created_at) VALUES (NULL, ?, ?, ?, 'pending', ?, ?, 'cryptomus', 1, ?, ?, ?, NOW())");
                $stmt->execute([$order_id, $totalAmount, $defaultCurrency, $payment_uuid, $payment_url, $currentUser['email'], $currentUser['first_name'], $currentUser['phone'] ?? '']);
            } else {
                $stmt = $pdo->prepare("INSERT INTO crypto_payments (user_id, order_id, amount, currency, status, cryptomus_uuid, payment_url, payment_method, created_at) VALUES (?, ?, ?, ?, 'pending', ?, ?, 'cryptomus', NOW())");
                $stmt->execute([$user_id, $order_id, $totalAmount, $defaultCurrency, $payment_uuid, $payment_url]);
            }
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
            
            $pdo->commit();
            
            return ['success' => true, 'payment_url' => $payment_url, 'payment_uuid' => $payment_uuid];
        
    } catch (Exception $e) {
            if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        error_log('Database Error: ' . $e->getMessage());
        error_log('Database Error Details: ' . $e->getTraceAsString());
        return ['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()];
    }
} else {
    // Hata durumunda detaylı bilgi göster
    error_log('Cryptomus API Error: ' . json_encode($response));
    
    $errorMessage = 'Bilinmeyen hata';
    if (isset($response['message'])) {
        $errorMessage = $response['message'];
    } elseif (isset($response['error'])) {
        $errorMessage = $response['error'];
    }
    
        return ['success' => false, 'message' => $errorMessage];
    }
}

/**
 * Shopier ödeme işlemi
 */
function processShopierPayment($totalAmount, $order_id, $defaultCurrency, $cartItems, $user_id, $currentUser, $isGuest = false) {
    // Shopier ayarlarını al
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM crypto_settings WHERE setting_key IN ('shopier_username', 'shopier_key', 'shopier_test_mode')");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $username = $settings['shopier_username'] ?? '';
    $key = $settings['shopier_key'] ?? '';
    $testMode = ($settings['shopier_test_mode'] ?? '0') === '1';
    
    if (empty($username) || empty($key)) {
        return ['success' => false, 'message' => 'Shopier API ayarları eksik'];
    }
    
    try {
        // Shopier client'ı oluştur
        $shopier = new ShopierClient($username, $key, $testMode);
        
        // Müşteri adını belirle
        $customerName = '';
        if ($isGuest) {
            $customerName = $currentUser['first_name'] ?? $currentUser['name'] ?? 'Müşteri';
        } else {
            $customerName = $currentUser['username'] ?? $currentUser['first_name'] ?? 'Müşteri';
        }
        
        // Ödeme verileri
        $orderData = [
            'amount' => $totalAmount,
            'currency' => $defaultCurrency,
            'order_id' => $order_id,
            'callback_url' => SITE_URL . 'shopier_webhook.php',
            'success_url' => SITE_URL . 'payment-success.php?order_id=' . $order_id,
            'fail_url' => SITE_URL . 'payment-cancel.php?order_id=' . $order_id,
            'customer_name' => $customerName,
            'customer_email' => $currentUser['email'],
            'customer_phone' => $currentUser['phone'] ?? '',
            'description' => 'Hesap satın alma - ' . count($cartItems) . ' adet'
        ];
        
        // Shopier ödeme oluştur
        $response = $shopier->createPayment($orderData);
        
        if (isset($response['success']) && $response['success']) {
            $payment_url = $response['payment_url'] ?? '';
            $payment_data = $response['payment_data'] ?? [];
            
            // Ödeme kaydını veritabanına ekle
            $pdo->beginTransaction();
            
            // Ana ödeme kaydı
            if ($isGuest) {
                $stmt = $pdo->prepare("INSERT INTO crypto_payments (user_id, order_id, amount, currency, status, shopier_payment_id, payment_url, payment_method, is_guest_order, email, customer_name, phone, created_at) VALUES (NULL, ?, ?, ?, 'pending', ?, ?, 'shopier', 1, ?, ?, ?, NOW())");
                $stmt->execute([$order_id, $totalAmount, $defaultCurrency, $order_id, $payment_url, $currentUser['email'], $customerName, $currentUser['phone'] ?? '']);
            } else {
                $stmt = $pdo->prepare("INSERT INTO crypto_payments (user_id, order_id, amount, currency, status, shopier_payment_id, payment_url, payment_method, email, customer_name, created_at) VALUES (?, ?, ?, ?, 'pending', ?, ?, 'shopier', ?, ?, NOW())");
                $stmt->execute([$user_id, $order_id, $totalAmount, $defaultCurrency, $order_id, $payment_url, $currentUser['email'], $customerName]);
            }
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
            
            $pdo->commit();
            
            // Shopier ödeme formu sayfasına yönlendir
            return ['success' => true, 'payment_url' => SITE_URL . 'shopier_payment_form.php?order_id=' . $order_id, 'payment_uuid' => $payment_id];
            
        } else {
            $errorMessage = $response['message'] ?? 'Shopier ödeme hatası';
            return ['success' => false, 'message' => $errorMessage];
        }
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        error_log('Shopier Error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Shopier ödeme hatası: ' . $e->getMessage()];
    }
}

// Output buffering'i temizle ve gönder
ob_end_flush();
?>
<?php
require_once 'config.php';
require_once 'ShopierClient.php';

// Order ID'yi al
$order_id = $_GET['order_id'] ?? '';

if (empty($order_id)) {
    die('Order ID gerekli!');
}

// Ödeme bilgilerini al
$stmt = $pdo->prepare("SELECT * FROM crypto_payments WHERE order_id = ?");
$stmt->execute([$order_id]);
$payment = $stmt->fetch();

if (!$payment) {
    die('Ödeme bulunamadı!');
}

// Shopier ayarlarını al
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM crypto_settings WHERE setting_key IN ('shopier_username', 'shopier_key')");
$stmt->execute();
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$username = $settings['shopier_username'] ?? '';
$key = $settings['shopier_key'] ?? '';

if (empty($username) || empty($key)) {
    die('Shopier ayarları eksik!');
}

// Shopier ödeme oluştur
$shopierClient = new ShopierClient($username, $key);

$paymentData = [
    'amount' => $payment['amount'],
    'currency' => $payment['currency'],
    'order_id' => $payment['order_id'],
    'customer_name' => $payment['customer_name'] ?? 'Müşteri',
    'customer_email' => $payment['email'] ?? 'musteri@example.com',
    'customer_phone' => $payment['phone'] ?? '05448412171',
    'description' => 'Hesap Satın Alma'
];

$paymentResult = $shopierClient->createPayment($paymentData);

if (!$paymentResult || !isset($paymentResult['success']) || !$paymentResult['success']) {
    $errorMessage = 'Ödeme oluşturulurken hata oluştu.';
    if (isset($paymentResult['error'])) {
        $errorMessage .= ' Hata: ' . $paymentResult['error'];
        if (isset($paymentResult['error_code'])) {
            $errorMessage .= ' (Kod: ' . $paymentResult['error_code'] . ')';
        }
    }
    error_log('SHOPIER PAYMENT ERROR: ' . $errorMessage);
    die($errorMessage);
}

$paymentArgs = $paymentResult['payment_data'];
$paymentUrl = $paymentResult['payment_url'];

// Payment verilerini logla
error_log('Shopier Payment Created - Order ID: ' . $payment['order_id'] . ', Amount: ' . $payment['amount'] . ' ' . $payment['currency']);

// Debug: Form parametrelerini logla
error_log('Shopier Form Parameters: ' . json_encode($paymentArgs));
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopier Ödeme</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .payment-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        .payment-title {
            color: #333;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        .payment-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
        }
        .payment-amount {
            font-size: 2rem;
            font-weight: bold;
            color: #28a745;
            margin: 1rem 0;
        }
        .btn-pay {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1rem;
        }
        .btn-pay:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        .loading {
            display: none;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <h1 class="payment-title">Shopier Güvenli Ödeme</h1>
        
        <div class="payment-info">
            <p><strong>Sipariş No:</strong> <?= htmlspecialchars($payment['order_id']) ?></p>
            <p><strong>Müşteri:</strong> <?= htmlspecialchars($payment['customer_name']) ?></p>
            <p><strong>E-posta:</strong> <?= htmlspecialchars($payment['email']) ?></p>
        </div>
        
        <div class="payment-amount">
            <?= number_format($payment['amount'], 2) ?> <?= $payment['currency'] ?>
        </div>
        
        <p>Shopier güvenli ödeme sayfasına yönlendiriliyorsunuz...</p>
        
        <form id="shopierForm" action="<?= htmlspecialchars($paymentUrl) ?>" method="post">
            <?php foreach ($paymentArgs as $key => $value): ?>
                <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
            <?php endforeach; ?>
        </form>
        
        <div class="loading">
            <p>Lütfen bekleyin...</p>
        </div>
        
        <script>
        // Sayfa yüklendiğinde otomatik olarak formu gönder
        window.onload = function() {
            document.getElementById('shopierForm').submit();
        };
        </script>
    </div>
</body>
</html>
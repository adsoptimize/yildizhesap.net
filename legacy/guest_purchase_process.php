<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'AccountManager.php';
require_once 'GuestSecurityManager.php';

// JSON response header
header('Content-Type: application/json');

// CSRF kontrolü
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    echo json_encode([
        'success' => false,
        'message' => 'Güvenlik hatası. Lütfen tekrar deneyin.'
    ]);
    exit;
}

// POST verilerini al
$accountId = intval($_POST['account_id'] ?? 0);
$email = trim($_POST['email'] ?? '');
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$paymentMethod = $_POST['payment_method'] ?? 'crypto';
$agreeTerms = isset($_POST['agree_terms']);

// Validasyon
if (!$accountId || !$email || !$name || !$agreeTerms) {
    echo json_encode([
        'success' => false,
        'message' => 'Lütfen tüm gerekli alanları doldurun.'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Geçerli bir e-posta adresi girin.'
    ]);
    exit;
}

try {
    $pdo = getPDO();
    
    // Güvenlik kontrolü
    $securityManager = new GuestSecurityManager($pdo);
    
    // Ziyaretçi satın alma özelliği aktif mi?
    if (!$securityManager->isGuestPurchaseEnabled()) {
        echo json_encode([
            'success' => false,
            'message' => 'Ziyaretçi satın alma özelliği şu anda aktif değil.'
        ]);
        exit;
    }
    
    // Güvenlik kontrolü
    $securityCheck = $securityManager->checkPurchaseAllowed($email);
    if (!$securityCheck['allowed']) {
        $securityManager->logSecurityEvent('purchase_blocked', [
            'email' => $email,
            'reason' => $securityCheck['message']
        ]);
        
        echo json_encode([
            'success' => false,
            'message' => $securityCheck['message']
        ]);
        exit;
    }
    
    // Hesabın mevcut olup olmadığını kontrol et
    $stmt = $pdo->prepare("
        SELECT a.*, c.name as category_name 
        FROM accounts a 
        LEFT JOIN categories c ON a.category_id = c.id 
        WHERE a.id = ? AND a.is_available = 1
    ");
    $stmt->execute([$accountId]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$account) {
        echo json_encode([
            'success' => false,
            'message' => 'Seçilen hesap mevcut değil veya satışta değil.'
        ]);
        exit;
    }
    
    // Stok kontrolü
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as available_count 
        FROM account_stock 
        WHERE account_id = ? AND is_sold = 0
    ");
    $stmt->execute([$accountId]);
    $stockInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($stockInfo['available_count'] < 1) {
        echo json_encode([
            'success' => false,
            'message' => 'Bu hesap için stok bulunmuyor.'
        ]);
        exit;
    }
    
    // Sipariş ID oluştur
    $orderId = 'order_' . time() . '_' . $accountId . '_' . rand(1, 9999);
    
    // Sipariş oluştur
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            order_id, product_name, category, quantity, unit_price, total_price, 
            status, delivery_status, email, customer_name, phone, payment_method, 
            created_at, is_guest_order
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1)
    ");
    
    $stmt->execute([
        $orderId,
        $account['name'],
        $account['category_name'],
        1,
        $account['price'],
        $account['price'],
        'pending',
        'pending',
        $email,
        $name,
        $phone,
        $paymentMethod
    ]);
    
    $orderDbId = $pdo->lastInsertId();
    
    // E-posta gönder
    sendGuestOrderEmail($email, $name, $orderId, $account);
    
    // Başarılı yanıt
    $response = [
        'success' => true,
        'message' => 'Siparişiniz başarıyla oluşturuldu!',
        'order_id' => $orderId
    ];
    
    // Ödeme yöntemine göre yönlendirme
    if ($paymentMethod === 'crypto') {
        // Kripto ödeme sayfasına yönlendir
        $response['payment_url'] = "create_payment.php?order_id=" . urlencode($orderId);
    } else {
        // Banka havalesi bilgileri
        $response['payment_url'] = "bank_payment.php?order_id=" . urlencode($orderId);
    }
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Guest purchase error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.'
    ]);
}

/**
 * Ziyaretçi sipariş e-postası gönder
 */
function sendGuestOrderEmail($email, $name, $orderId, $account) {
    $subject = "Sipariş Onayı - " . $orderId;
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #3b82f6; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f8f9fa; padding: 20px; border-radius: 0 0 8px 8px; }
            .order-info { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; }
            .btn { display: inline-block; padding: 12px 24px; background: #22c55e; color: white; text-decoration: none; border-radius: 6px; margin: 10px 5px; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>🎉 Siparişiniz Alındı!</h2>
            </div>
            <div class='content'>
                <p>Merhaba <strong>{$name}</strong>,</p>
                
                <p>Siparişiniz başarıyla alındı. Aşağıda sipariş detaylarını bulabilirsiniz:</p>
                
                <div class='order-info'>
                    <h3>Sipariş Bilgileri</h3>
                    <p><strong>Sipariş Kodu:</strong> {$orderId}</p>
                    <p><strong>Ürün:</strong> {$account['name']}</p>
                    <p><strong>Kategori:</strong> {$account['category_name']}</p>
                    <p><strong>Fiyat:</strong> " . formatPrice($account['price']) . "</p>
                    <p><strong>Tarih:</strong> " . date('d.m.Y H:i') . "</p>
                </div>
                
                <p><strong>Önemli:</strong> Siparişinizi takip etmek için aşağıdaki linki kullanabilirsiniz:</p>
                
                <div style='text-align: center; margin: 20px 0;'>
                    <a href='" . getSiteUrl() . "guest_order_track.php?order_id=" . urlencode($orderId) . "' class='btn'>
                        📋 Siparişimi Takip Et
                    </a>
                </div>
                
                <p><strong>Not:</strong> Ödeme tamamlandıktan sonra hesap bilgileriniz otomatik olarak size teslim edilecektir.</p>
                
                <div class='footer'>
                    <p>Bu e-posta otomatik olarak gönderilmiştir. Lütfen yanıtlamayınız.</p>
                    <p>Destek için: " . getSiteSettings('contact_email') . "</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // E-posta gönder
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . getSiteSettings('site_name') . " <" . getSiteSettings('contact_email') . ">\r\n";
    
    mail($email, $subject, $message, $headers);
}

/**
 * Site URL'sini al
 */
function getSiteUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['REQUEST_URI']);
    return $protocol . '://' . $host . $path . '/';
}
?> 
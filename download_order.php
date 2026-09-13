<?php
require_once 'config.php';
require_once 'Auth.php';
require_once 'OrderManager.php';

// Kullanıcı kontrolü
$auth = new Auth();
$sessionToken = $_COOKIE['session_token'] ?? null;

if (!$sessionToken) {
    http_response_code(401);
    die('Oturum bulunamadı');
}

$sessionResult = $auth->validateSession($sessionToken);
if (!$sessionResult['valid']) {
    http_response_code(401);
    die('Geçersiz oturum');
}

$currentUser = $sessionResult['user'];

// Sipariş ID'sini al
$orderId = $_GET['order_id'] ?? '';

if (empty($orderId)) {
    http_response_code(400);
    die('Sipariş ID gerekli');
}

try {
    $orderManager = new OrderManager($pdo);
    
    // Siparişin kullanıcıya ait olduğunu kontrol et
    $orderDetails = $orderManager->getOrderDetails($orderId, $currentUser['id']);
    
    if (!$orderDetails) {
        http_response_code(404);
        die('Sipariş bulunamadı');
    }
    
    // Sadece teslim edilmiş siparişler indirilebilir
    if ($orderDetails['delivery_status'] !== 'delivered') {
        http_response_code(403);
        die('Sadece teslim edilmiş siparişler indirilebilir');
    }
    
    // Sipariş hesaplarını getir
    $accounts = $orderManager->getOrderAccounts($orderId);
    
    if (empty($accounts)) {
        http_response_code(404);
        die('Bu sipariş için hesap bulunamadı');
    }
    
    // CSV dosyası oluştur
    $filename = "siparis_{$orderId}_" . date('Y-m-d_H-i-s') . ".csv";
    
    // Headers ayarla
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // CSV çıktısı oluştur
    $output = fopen('php://output', 'w');
    
    // BOM ekle (Excel'de Türkçe karakterler için)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Başlık bilgileri
    fputcsv($output, ['Sipariş ID: ' . $orderId], ';');
    fputcsv($output, ['Ürün: ' . $orderDetails['product_name']], ';');
    fputcsv($output, ['Kategori: ' . $orderDetails['category']], ';');
    fputcsv($output, ['Toplam Adet: ' . $orderDetails['quantity']], ';');
    fputcsv($output, ['Sipariş Tarihi: ' . date('d.m.Y H:i', strtotime($orderDetails['order_date']))], ';');
    fputcsv($output, ['İndirme Tarihi: ' . date('d.m.Y H:i')], ';');
    fputcsv($output, [''], ';'); // Boş satır
    
    // Tablo başlıkları
    fputcsv($output, [
        'Sıra No',
        'Kullanıcı Adı',
        'Şifre',
        'E-posta',
        'E-posta Şifresi',
        '2FA Secret',
        'Hesap Oluşturulma Tarihi'
    ], ';');
    
    // Hesap verilerini ekle
    $counter = 1;
    foreach ($accounts as $account) {
        fputcsv($output, [
            $counter,
            $account['username'] ?? '',
            $account['password'] ?? '',
            $account['email'] ?? '',
            $account['email_password'] ?? '',
            $account['totp_secret'] ?? '',
            $account['created_at'] ? date('d.m.Y', strtotime($account['created_at'])) : ''
        ], ';');
        $counter++;
    }
    
    // Alt bilgi
    fputcsv($output, [''], ';'); // Boş satır
    fputcsv($output, ['Not: Bu dosya güvenlik amacıyla şifrelenmesi önerilir.'], ';');
    fputcsv($output, ['Destek: Herhangi bir sorun için destek ekibimizle iletişime geçin.'], ';');
    
    fclose($output);
    
    // Log kaydet (opsiyonel)
    error_log("Order download: User {$currentUser['id']} downloaded order {$orderId} with " . count($accounts) . " accounts");
    
} catch (Exception $e) {
    error_log("download_order.php Error: " . $e->getMessage());
    http_response_code(500);
    die('Dosya oluşturulurken hata oluştu');
}
?>

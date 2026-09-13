<?php
/**
 * SEO-Friendly URL Router
 * Bu dosya .htaccess tarafından gelen SEO-friendly URL'leri işler
 * ve doğru sayfaya yönlendirir
 */

// Prevent caching during development/testing
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once 'config.php';

// URL'i al
$requestedUrl = isset($_GET['url']) ? $_GET['url'] : '';
$requestedUrl = rtrim($requestedUrl, '/');

// Eğer boş ise ana sayfaya yönlendir
if (empty($requestedUrl)) {
    require_once __DIR__ . '/index.php';
    exit;
}

// Static sayfa mappingleri
$staticPages = [
    'siparis-takip' => 'guest_order_track.php',
    'tum-hesaplar' => 'hesaplar.php',
    'sikca-sorulan-sorular' => 'sss.php',
    'hizmetler' => 'hizmetler.php',
    'iletisim' => 'iletisim.php',
    'kvkk' => 'kvvk.php',
    'giris-yap' => 'login.php',
    'kayit-ol' => 'register.php',
    'gizlilik-politikasi' => 'privacy.php'
];

// Static sayfa kontrolü
if (array_key_exists($requestedUrl, $staticPages)) {
    require_once __DIR__ . '/' . $staticPages[$requestedUrl];
    exit;
}

// Kategori sayfası kontrolü
try {
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE seo_slug = ? AND is_active = 1");
    $stmt->execute([$requestedUrl]);
    $category = $stmt->fetch();
    
    if ($category) {
        // Kategori bulundu, hesaplar.php'ye yönlendir
        $_GET['category'] = $category['id'];
        require_once __DIR__ . '/hesaplar.php';
        exit;
    }
    
    // Ürün detay sayfası kontrolü
    $stmt = $pdo->prepare("SELECT id FROM accounts WHERE seo_slug = ? AND status = 'active'");
    $stmt->execute([$requestedUrl]);
    $product = $stmt->fetch();
    
    if ($product) {
        // Ürün bulundu, view.php'ye yönlendir
        $_GET['id'] = $product['id'];
        require_once __DIR__ . '/view.php';
        exit;
    }
    
    // Hiçbir şey bulunamadı, 404 sayfasına yönlendir
    header("HTTP/1.0 404 Not Found");
    echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>404 - Sayfa Bulunamadı</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .error-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 500px;
        }
        h1 {
            font-size: 72px;
            margin: 0;
            color: #667eea;
        }
        h2 {
            font-size: 24px;
            margin: 20px 0;
            color: #333;
        }
        p {
            color: #666;
            margin: 20px 0;
        }
        a {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 20px;
            transition: background 0.3s;
        }
        a:hover {
            background: #764ba2;
        }
    </style>
</head>
<body>
    <div class='error-container'>
        <h1>404</h1>
        <h2>Sayfa Bulunamadı</h2>
        <p>Aradığınız sayfa mevcut değil veya taşınmış olabilir.</p>
        <p>URL: <strong>" . htmlspecialchars($requestedUrl) . "</strong></p>
        <a href='/'>Ana Sayfaya Dön</a>
    </div>
</body>
</html>";
    
} catch (PDOException $e) {
    error_log("Router error: " . $e->getMessage());
    header("HTTP/1.0 500 Internal Server Error");
    echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>500 - Sunucu Hatası</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .error-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 500px;
        }
        h1 {
            font-size: 72px;
            margin: 0;
            color: #f5576c;
        }
        h2 {
            font-size: 24px;
            margin: 20px 0;
            color: #333;
        }
        p {
            color: #666;
            margin: 20px 0;
        }
        a {
            display: inline-block;
            background: #f5576c;
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 20px;
            transition: background 0.3s;
        }
        a:hover {
            background: #f093fb;
        }
    </style>
</head>
<body>
    <div class='error-container'>
        <h1>500</h1>
        <h2>Sunucu Hatası</h2>
        <p>Bir hata oluştu. Lütfen daha sonra tekrar deneyin.</p>
        <a href='/'>Ana Sayfaya Dön</a>
    </div>
</body>
</html>";
}
?>

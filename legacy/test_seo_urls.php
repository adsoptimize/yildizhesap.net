<?php
/**
 * Quick Test - SEO URLs
 */

// Prevent search engine indexing
header('X-Robots-Tag: noindex, nofollow', true);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta name='robots' content='noindex, nofollow'>
    <title>SEO URL Test</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .test-box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        a { color: #007bff; text-decoration: none; padding: 8px 16px; background: #e7f3ff; border-radius: 4px; display: inline-block; margin: 5px; }
        a:hover { background: #007bff; color: white; }
    </style>
</head>
<body>
    <h1>🧪 SEO URL Test Page</h1>
    
    <div class="test-box">
        <h2>Kategori URL'leri (Yeni Format)</h2>
        <a href="/business-manager" target="_blank">Business Manager</a>
        <a href="/facebook-hesaplari" target="_blank">Facebook Hesapları</a>
        <a href="/instagram-hesaplari" target="_blank">Instagram Hesapları</a>
        <a href="/old-accounts" target="_blank">Old Accounts</a>
    </div>
    
    <div class="test-box">
        <h2>Ürün URL'leri (Yeni Format)</h2>
        <a href="/gunluk-harcama-250-dolar-limitli-tekli-kisisel-reklam-hesabi" target="_blank">ID 40 - Günlük Harcama BM</a>
        <a href="/kimlik-onayli-facebook-hesaplari" target="_blank">ID 42 - Kimlik Onaylı FB</a>
        <a href="/business-manager-hesaplari" target="_blank">ID 39 - Business Manager</a>
        <a href="/facebook-marketplace-hesaplari" target="_blank">ID 41 - Marketplace</a>
    </div>
    
    <div class="test-box">
        <h2>Static Sayfalar (Yeni Format)</h2>
        <a href="/giris-yap" target="_blank">Giriş Yap</a>
        <a href="/kayit-ol" target="_blank">Kayıt Ol</a>
        <a href="/sikca-sorulan-sorular" target="_blank">SSS</a>
        <a href="/iletisim" target="_blank">İletişim</a>
    </div>
    
    <div class="test-box">
        <h2>301 Redirect Test (Eski → Yeni)</h2>
        <a href="/view.php?id=40" target="_blank">view.php?id=40 (Otomatik yönlenecek)</a>
        <a href="/hesaplar.php?category=13" target="_blank">hesaplar.php?category=13 (Otomatik yönlenecek)</a>
        <a href="/login.php" target="_blank">login.php (Otomatik yönlenecek)</a>
    </div>
    
    <div class="test-box">
        <h2>🔧 Cache Temizleme</h2>
        <p>Eğer URL'ler hala çalışmıyorsa:</p>
        <ol>
            <li><a href="/opcache_reset.php?key=<?= md5('yildizhesap_reset_2026') ?>" target="_blank">OPcache Temizle</a></li>
            <li>Tarayıcı Cache: CTRL+SHIFT+DEL</li>
            <li>Test için Incognito/Private pencere kullanın</li>
        </ol>
    </div>
    
    <div class="test-box">
        <h2>✅ Beklenen Sonuç</h2>
        <ul>
            <li class="success">✓ Tüm yeni URL'ler direkt açılmalı</li>
            <li class="success">✓ Eski URL'ler yeni URL'lere 301 redirect yapmalı</li>
            <li class="success">✓ Anasayfadaki kategori kartları yeni URL'leri kullanmalı</li>
            <li class="success">✓ Ürün detay sayfaları açılmalı</li>
        </ul>
    </div>
    
    <div class="test-box">
        <a href="/" style="background: #28a745; color: white;">🏠 Ana Sayfaya Dön</a>
        <a href="/check_seo_slugs.php" style="background: #17a2b8; color: white;">🔍 Database Check</a>
    </div>
</body>
</html>

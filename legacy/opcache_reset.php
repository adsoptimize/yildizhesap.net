<?php
/**
 * OPcache Reset Tool
 * PHP bytecode cache'ini temizler
 */

// Prevent search engine indexing
header('X-Robots-Tag: noindex, nofollow', true);

// Güvenlik için IP kontrolü (isteğe bağlı)
$allowed_ips = ['127.0.0.1', '::1']; // Kendi IP'nizi ekleyin
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '';

// Basit bir güvenlik anahtarı
$secret_key = md5('yildizhesap_reset_2026');
$provided_key = $_GET['key'] ?? '';

if ($provided_key !== $secret_key) {
    die('Unauthorized access!');
}

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta name='robots' content='noindex, nofollow'>
    <title>OPcache Reset</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { color: #28a745; padding: 15px; background: #d4edda; border-radius: 5px; margin: 10px 0; }
        .error { color: #dc3545; padding: 15px; background: #f8d7da; border-radius: 5px; margin: 10px 0; }
        .info { color: #0056b3; padding: 15px; background: #d1ecf1; border-radius: 5px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🗑️ OPcache Reset Tool</h1>";

// OPcache durumunu kontrol et
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();
    
    if ($status !== false) {
        echo "<div class='info'>";
        echo "<strong>OPcache Aktif</strong><br>";
        echo "Cached Scripts: " . number_format($status['opcache_statistics']['num_cached_scripts']) . "<br>";
        echo "Hits: " . number_format($status['opcache_statistics']['hits']) . "<br>";
        echo "Misses: " . number_format($status['opcache_statistics']['misses']) . "<br>";
        echo "Memory Usage: " . round($status['memory_usage']['used_memory'] / 1024 / 1024, 2) . " MB / " . round($status['memory_usage']['free_memory'] / 1024 / 1024, 2) . " MB<br>";
        echo "</div>";
        
        // Cache'i temizle
        if (opcache_reset()) {
            echo "<div class='success'>";
            echo "✅ <strong>OPcache başarıyla temizlendi!</strong><br>";
            echo "Tüm PHP dosyaları yeniden derlenecek.";
            echo "</div>";
            
            // Yeni durum
            $new_status = opcache_get_status();
            echo "<div class='info'>";
            echo "<strong>Yeni Durum:</strong><br>";
            echo "Cached Scripts: " . number_format($new_status['opcache_statistics']['num_cached_scripts']) . "<br>";
            echo "</div>";
        } else {
            echo "<div class='error'>";
            echo "❌ OPcache temizlenemedi. Sunucu yöneticisiyle iletişime geçin.";
            echo "</div>";
        }
    } else {
        echo "<div class='error'>OPcache devre dışı veya erişilemiyor.</div>";
    }
} else {
    echo "<div class='error'>OPcache bu sunucuda yüklü değil.</div>";
}

echo "
        <h3>Diğer Cache İşlemleri:</h3>
        <ul>
            <li><strong>Tarayıcı Cache:</strong> CTRL+SHIFT+DEL veya Gizli Pencere kullanın</li>
            <li><strong>Apache:</strong> .htaccess değişiklikleri otomatik yüklenir</li>
            <li><strong>Session:</strong> Çıkış yapıp yeniden giriş yapın</li>
        </ul>
        
        <h3>SEO URL Migration Sonrası:</h3>
        <ol>
            <li>Bu sayfayı çalıştırarak OPcache'i temizleyin ✅</li>
            <li>Tarayıcı cache'ini temizleyin (CTRL+SHIFT+DEL)</li>
            <li>Test URL'leri gizli pencerede açın</li>
            <li>Migration başarılıysa setup_seo_urls.php dosyasını silin</li>
        </ol>
        
        <div style='margin-top: 20px;'>
            <a href='/' class='btn'>Ana Sayfaya Dön</a>
            <a href='?key=" . urlencode($secret_key) . "' class='btn' style='background: #dc3545;'>Tekrar Temizle</a>
        </div>
        
        <div style='margin-top: 30px; padding: 15px; background: #fff3cd; border-radius: 5px; color: #856404;'>
            <strong>⚠️ Güvenlik Uyarısı:</strong> Bu dosyayı kullandıktan sonra silmeniz önerilir.
        </div>
    </div>
</body>
</html>";
?>

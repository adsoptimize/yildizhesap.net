<?php
/**
 * Static Sitemap.xml Updater
 * Generates a static sitemap.xml file with new SEO-friendly URLs
 * Run this script once to update the old sitemap.xml
 */

require_once 'config.php';
require_once 'URLHelper.php';

// Get all sitemap URLs from URLHelper
$urls = URLHelper::getSitemapUrls();

// Build XML content
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as $url) {
    $xml .= '  <url>' . "\n";
    $xml .= '    <loc>' . htmlspecialchars($url['loc']) . '</loc>' . "\n";
    
    if (isset($url['lastmod'])) {
        $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . "\n";
    }
    
    $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
    $xml .= '    <priority>' . $url['priority'] . '</priority>' . "\n";
    $xml .= '  </url>' . "\n";
}

$xml .= '</urlset>';

// Write to sitemap.xml
$result = file_put_contents(__DIR__ . '/sitemap.xml', $xml);

if ($result !== false) {
    echo "✅ sitemap.xml başarıyla güncellendi!\n\n";
    echo "📊 Toplam URL sayısı: " . count($urls) . "\n\n";
    echo "🔗 URL örnekleri:\n";
    
    $sampleCount = 0;
    foreach ($urls as $url) {
        if ($sampleCount >= 10) break;
        echo "  - " . $url['loc'] . "\n";
        $sampleCount++;
    }
    
    echo "\n✨ Yeni sitemap.xml artık SEO-friendly URL'leri içeriyor!\n";
    echo "🌐 Google Search Console'a gönderin: https://yildizhesap.net/sitemap.xml\n\n";
    echo "⚠️  Bu dosyayı çalıştırdıktan sonra sunucudan silebilirsiniz.\n";
} else {
    echo "❌ HATA: sitemap.xml yazılamadı. Dosya izinlerini kontrol edin.\n";
}
?>

<?php
/**
 * XML Sitemap Generator
 * SEO-friendly URL'ler için Google sitemap oluşturur
 */

require_once 'config.php';
require_once 'URLHelper.php';

// XML header
header('Content-Type: application/xml; charset=utf-8');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// URLHelper'dan sitemap URL'lerini al
$urls = URLHelper::getSitemapUrls();

foreach ($urls as $url) {
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($url['loc']) . '</loc>' . "\n";
    
    if (isset($url['lastmod'])) {
        echo '    <lastmod>' . $url['lastmod'] . '</lastmod>' . "\n";
    }
    
    echo '    <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
    echo '    <priority>' . $url['priority'] . '</priority>' . "\n";
    echo '  </url>' . "\n";
}

echo '</urlset>';
?>

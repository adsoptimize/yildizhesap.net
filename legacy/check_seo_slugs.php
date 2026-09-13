<?php
/**
 * Quick Database Check - SEO Slugs
 * Veritabanında slug'ların olup olmadığını kontrol eder
 */

// Prevent search engine indexing
header('X-Robots-Tag: noindex, nofollow', true);

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='robots' content='noindex, nofollow'>
    <title>SEO Slug Check</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #dcdcaa; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; background: #252526; }
        th, td { border: 1px solid #3e3e42; padding: 8px; text-align: left; }
        th { background: #2d2d30; color: #569cd6; }
        .code { background: #1e1e1e; padding: 10px; border-left: 3px solid #007acc; margin: 10px 0; }
    </style>
</head>
<body>";

echo "<h1>🔍 SEO Slug Database Check</h1>";
echo "<hr>";

try {
    // 1. Check if seo_slug columns exist
    echo "<h2>1. Column Existence Check</h2>";
    
    $categoriesCheck = $pdo->query("SHOW COLUMNS FROM categories LIKE 'seo_slug'");
    $accountsCheck = $pdo->query("SHOW COLUMNS FROM accounts LIKE 'seo_slug'");
    
    if ($categoriesCheck->rowCount() > 0) {
        echo "<div class='success'>✓ categories.seo_slug column EXISTS</div>";
    } else {
        echo "<div class='error'>✗ categories.seo_slug column MISSING - Run migration!</div>";
    }
    
    if ($accountsCheck->rowCount() > 0) {
        echo "<div class='success'>✓ accounts.seo_slug column EXISTS</div>";
    } else {
        echo "<div class='error'>✗ accounts.seo_slug column MISSING - Run migration!</div>";
    }
    
    // 2. Check specific product slug
    echo "<h2>2. Product ID 40 Check</h2>";
    echo "<div class='code'>Expected: gunluk-harcama-250-dolar-limitli-tekli-kisisel-reklam-hesabi</div>";
    
    $stmt = $pdo->query("SELECT id, title, seo_slug, status FROM accounts WHERE id = 40");
    $product = $stmt->fetch();
    
    if ($product) {
        echo "<table>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>ID</td><td>{$product['id']}</td></tr>";
        echo "<tr><td>Title</td><td>" . htmlspecialchars($product['title']) . "</td></tr>";
        echo "<tr><td>SEO Slug</td><td class='" . ($product['seo_slug'] ? 'success' : 'error') . "'>" . 
             ($product['seo_slug'] ? htmlspecialchars($product['seo_slug']) : 'NULL/EMPTY') . "</td></tr>";
        echo "<tr><td>Status</td><td>{$product['status']}</td></tr>";
        echo "</table>";
        
        if (!$product['seo_slug']) {
            echo "<div class='error'>⚠️ SEO slug is EMPTY! Migration not completed.</div>";
        } elseif ($product['seo_slug'] !== 'gunluk-harcama-250-dolar-limitli-tekli-kisisel-reklam-hesabi') {
            echo "<div class='warning'>⚠️ SEO slug MISMATCH!</div>";
            echo "<div class='code'>Database: " . htmlspecialchars($product['seo_slug']) . "</div>";
            echo "<div class='code'>Expected: gunluk-harcama-250-dolar-limitli-tekli-kisisel-reklam-hesabi</div>";
        } else {
            echo "<div class='success'>✓ SEO slug is CORRECT!</div>";
        }
    } else {
        echo "<div class='error'>✗ Product ID 40 NOT FOUND in database!</div>";
    }
    
    // 3. Check all products with slugs
    echo "<h2>3. All Products with SEO Slugs</h2>";
    
    $stmt = $pdo->query("SELECT id, title, seo_slug FROM accounts WHERE status = 'active' ORDER BY id");
    $products = $stmt->fetchAll();
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Title</th><th>SEO Slug</th><th>Status</th></tr>";
    
    $withSlug = 0;
    $withoutSlug = 0;
    
    foreach ($products as $p) {
        $hasSlug = !empty($p['seo_slug']);
        if ($hasSlug) $withSlug++; else $withoutSlug++;
        
        $statusClass = $hasSlug ? 'success' : 'error';
        $statusIcon = $hasSlug ? '✓' : '✗';
        
        echo "<tr>";
        echo "<td>{$p['id']}</td>";
        echo "<td>" . htmlspecialchars(substr($p['title'], 0, 50)) . "...</td>";
        echo "<td class='$statusClass'>" . ($p['seo_slug'] ?: 'EMPTY') . "</td>";
        echo "<td class='$statusClass'>$statusIcon</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "<div class='code'>Products with slug: $withSlug | Products without slug: $withoutSlug</div>";
    
    // 4. Test URL construction
    echo "<h2>4. URL Construction Test</h2>";
    
    if ($product && $product['seo_slug']) {
        $testUrl = SITE_URL . $product['seo_slug'];
        echo "<div class='success'>Generated URL: <a href='$testUrl' target='_blank' style='color: #4ec9b0;'>$testUrl</a></div>";
        echo "<div class='code'>Click to test if URL works</div>";
    }
    
    // 5. Router Test
    echo "<h2>5. Router.php Test</h2>";
    echo "<div class='code'>";
    echo "Testing if router can find product with slug...<br>";
    
    $testSlug = 'gunluk-harcama-250-dolar-limitli-tekli-kisisel-reklam-hesabi';
    $stmt = $pdo->prepare("SELECT id, title FROM accounts WHERE seo_slug = ? AND status = 'active'");
    $stmt->execute([$testSlug]);
    $found = $stmt->fetch();
    
    if ($found) {
        echo "<span class='success'>✓ Router query would find: ID {$found['id']} - " . htmlspecialchars($found['title']) . "</span>";
    } else {
        echo "<span class='error'>✗ Router query returns NULL - Product not found!</span>";
    }
    echo "</div>";
    
    // 6. Recommendations
    echo "<h2>6. Recommendations</h2>";
    
    if ($withoutSlug > 0) {
        echo "<div class='error'>⚠️ ACTION REQUIRED: Run migration script</div>";
        echo "<div class='code'>Visit: " . SITE_URL . "setup_seo_urls.php</div>";
    }
    
    if ($product && !$product['seo_slug']) {
        echo "<div class='error'>⚠️ Product ID 40 needs slug assignment</div>";
    }
    
    echo "<div class='success'>✓ After migration, clear OPcache</div>";
    echo "<div class='code'>Visit: " . SITE_URL . "opcache_reset.php?key=YOUR_KEY</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<hr>";
echo "<div style='margin-top: 20px;'>";
echo "<a href='setup_seo_urls.php' style='color: #4ec9b0; text-decoration: none;'>→ Run Migration Script</a> | ";
echo "<a href='/' style='color: #4ec9b0; text-decoration: none;'>→ Go Home</a>";
echo "</div>";

echo "</body></html>";
?>

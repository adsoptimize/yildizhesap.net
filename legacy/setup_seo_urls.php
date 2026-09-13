<?php
/**
 * SEO URL Migration Script
 * Bu script accounts ve categories tablolarına slug kolonları ekler
 * ve mevcut veriler için SEO-friendly slug'lar oluşturur
 */

// Prevent search engine indexing
header('X-Robots-Tag: noindex, nofollow', true);

require_once 'config.php';

echo "<h1>SEO URL Migration Script</h1>";
echo "<meta name='robots' content='noindex, nofollow'>";
echo "<hr>";

try {
    // 1. Categories tablosuna seo_slug kolonu ekle
    echo "<h3>1. Categories tablosuna seo_slug kolonu ekleniyor...</h3>";
    
    $checkCategoryColumn = $pdo->query("SHOW COLUMNS FROM categories LIKE 'seo_slug'");
    if ($checkCategoryColumn->rowCount() == 0) {
        $pdo->exec("ALTER TABLE categories ADD COLUMN seo_slug VARCHAR(255) DEFAULT NULL AFTER slug");
        $pdo->exec("ALTER TABLE categories ADD UNIQUE KEY unique_seo_slug (seo_slug)");
        echo "✓ seo_slug kolonu categories tablosuna eklendi<br>";
    } else {
        echo "⚠ seo_slug kolonu zaten mevcut<br>";
    }

    // 2. Accounts tablosuna seo_slug kolonu ekle
    echo "<h3>2. Accounts tablosuna seo_slug kolonu ekleniyor...</h3>";
    
    $checkAccountColumn = $pdo->query("SHOW COLUMNS FROM accounts LIKE 'seo_slug'");
    if ($checkAccountColumn->rowCount() == 0) {
        $pdo->exec("ALTER TABLE accounts ADD COLUMN seo_slug VARCHAR(500) DEFAULT NULL AFTER title");
        $pdo->exec("ALTER TABLE accounts ADD UNIQUE KEY unique_seo_slug (seo_slug)");
        echo "✓ seo_slug kolonu accounts tablosuna eklendi<br>";
    } else {
        echo "⚠ seo_slug kolonu zaten mevcut<br>";
    }

    // 3. Category SEO slug mapping - Manuel tanımlanmış sluglar
    echo "<h3>3. Category SEO slugları güncelleniyor...</h3>";
    
    $categorySlugMapping = [
        7 => 'ana-hesaplar-dogrulanmis',
        8 => 'direncli-hesaplar',
        12 => 'old-accounts',
        13 => 'business-manager',
        14 => 'facebook-marketplace',
        15 => 'facebook-hesaplari',
        16 => 'instagram-hesaplari',
        20 => 'onayli-twitter-hesaplari',
        21 => 'tiktok-hesaplari',
        22 => 'mail-gmail-outlook-hotmail-hesaplari',
        27 => 'telegram-hesaplari'
    ];
    
    $stmt = $pdo->prepare("UPDATE categories SET seo_slug = ? WHERE id = ?");
    foreach ($categorySlugMapping as $catId => $seoSlug) {
        $stmt->execute([$seoSlug, $catId]);
        echo "✓ Category ID $catId -> $seoSlug<br>";
    }

    // 4. Product SEO slug mapping - Manuel tanımlanmış sluglar
    echo "<h3>4. Product SEO slugları güncelleniyor...</h3>";
    
    $productSlugMapping = [
        12 => 'rastgele-kimlik-dogrulanmis-super-eski-facebook-hesabi-2015',
        13 => 'rastgele-kimlik-dogrulanmis-eski-facebook-hesabi-2010',
        14 => 'rastgele-kimlik-dogrulanmis-super-eski-facebook-hesabi-2010-2020',
        15 => 'rastgele-kimlik-dogrulanmis-eski-facebook-hesabi-2010-2023',
        16 => 'vietnam-kimligi-dogrulanmis-eski-facebook-hesabi-2024',
        17 => 'vietnam-kimligi-dogrulanmis-eski-facebook-hesabi-2023-2024',
        18 => 'vietnamda-yeniden-acilan-eski-facebook-hesabi-2024',
        19 => 'vietnamese-reinstated-aged-fb-account-2024',
        20 => 'random-reinstated-aged-fb-account-2010-2023',
        21 => 'random-reinstated-super-aged-fb-account-2007-2015',
        26 => 'poland-old-fb-account-2008-2021',
        27 => 'taiwan-old-fb-account',
        28 => 'uk-old-fb-account',
        29 => 'usa-old-fb-account-2024',
        30 => 'random-country-fb-account-can-create-sub-profile',
        31 => 'india-aged-fb-account',
        35 => '250-dolar-limitli-tekli-business-manager-hesaplari',
        36 => 'kisittan-donmus-business-manager-hesaplari',
        37 => 'dogrulanmis-business-manager-hesaplari-3lu',
        38 => 'eski-business-manager-hesaplari',
        39 => 'business-manager-hesaplari',
        40 => 'gunluk-harcama-250-dolar-limitli-tekli-kisisel-reklam-hesabi',
        41 => 'facebook-marketplace-hesaplari',
        42 => 'kimlik-onayli-facebook-hesaplari',
        43 => 'kimlik-onayli-facebook-hesaplari-guclendirilmis',
        44 => 'facebook-hesaplari-turk',
        45 => 'facebook-hesaplari-yabanci',
        46 => 'instagram-hesaplari-10000-takipcili',
        47 => 'instagram-hesaplari-5000-takipcili',
        49 => '20-adet-gonderili-instagram-hesaplari',
        50 => 'tanitim-onayli-instagram-hesaplari',
        60 => 'dogrulanmis-telegram-hesap'
    ];
    
    $stmt = $pdo->prepare("UPDATE accounts SET seo_slug = ? WHERE id = ?");
    foreach ($productSlugMapping as $productId => $seoSlug) {
        $stmt->execute([$seoSlug, $productId]);
        echo "✓ Product ID $productId -> $seoSlug<br>";
    }

    // 5. Diğer ürünler için otomatik slug oluştur
    echo "<h3>5. Slug olmayan ürünler için otomatik slug oluşturuluyor...</h3>";
    
    $accountsWithoutSlug = $pdo->query("SELECT id, title FROM accounts WHERE seo_slug IS NULL OR seo_slug = ''");
    
    function generateSlug($text) {
        // Türkçe karakterleri dönüştür
        $turkish = ['Ç', 'Ş', 'Ğ', 'Ü', 'İ', 'Ö', 'ç', 'ş', 'ğ', 'ü', 'ö', 'ı'];
        $english = ['C', 'S', 'G', 'U', 'I', 'O', 'c', 's', 'g', 'u', 'o', 'i'];
        $text = str_replace($turkish, $english, $text);
        
        // Küçük harfe çevir
        $text = strtolower($text);
        
        // HTML etiketlerini kaldır
        $text = strip_tags($text);
        
        // Özel karakterleri temizle
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        
        // Boşlukları tire ile değiştir
        $text = preg_replace('/[\s-]+/', '-', $text);
        
        // Başta ve sonda tire varsa kaldır
        $text = trim($text, '-');
        
        return $text;
    }
    
    $stmt = $pdo->prepare("UPDATE accounts SET seo_slug = ? WHERE id = ?");
    while ($row = $accountsWithoutSlug->fetch()) {
        $autoSlug = generateSlug($row['title']);
        
        // Slug benzersiz olmalı, eğer varsa sonuna ID ekle
        $checkSlug = $pdo->prepare("SELECT id FROM accounts WHERE seo_slug = ? AND id != ?");
        $checkSlug->execute([$autoSlug, $row['id']]);
        
        if ($checkSlug->rowCount() > 0) {
            $autoSlug .= '-' . $row['id'];
        }
        
        $stmt->execute([$autoSlug, $row['id']]);
        echo "✓ Product ID {$row['id']} -> $autoSlug (auto-generated)<br>";
    }

    echo "<hr>";
    echo "<h2 style='color: green;'>✓ Migration başarıyla tamamlandı!</h2>";
    echo "<p>Artık SEO-friendly URL'ler kullanıma hazır.</p>";
    echo "<p><a href='index.php'>Ana Sayfaya Dön</a></p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Hata oluştu!</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

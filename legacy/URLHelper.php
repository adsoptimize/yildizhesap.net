<?php
/**
 * URL Helper Functions
 * SEO-friendly URL'ler için yardımcı fonksiyonlar
 */

class URLHelper {
    private static $pdo;
    
    /**
     * Initialize with database connection
     */
    public static function init($pdo) {
        self::$pdo = $pdo;
    }
    
    /**
     * Kategori URL'i oluştur
     * @param int $categoryId Kategori ID
     * @param string $fallback Varsayılan slug (veritabanından slug bulunamazsa)
     * @return string SEO-friendly kategori URL'i
     */
    public static function getCategoryUrl($categoryId, $fallback = null) {
        if (!self::$pdo) {
            return "hesaplar.php?category=$categoryId";
        }
        
        try {
            $stmt = self::$pdo->prepare("SELECT seo_slug FROM categories WHERE id = ?");
            $stmt->execute([$categoryId]);
            $result = $stmt->fetch();
            
            if ($result && !empty($result['seo_slug'])) {
                return SITE_URL . $result['seo_slug'];
            }
            
            // Fallback kullan
            if ($fallback) {
                return SITE_URL . $fallback;
            }
            
            // Hiçbir şey yoksa eski format
            return SITE_URL . "hesaplar.php?category=$categoryId";
            
        } catch (PDOException $e) {
            error_log("URLHelper::getCategoryUrl error: " . $e->getMessage());
            return "hesaplar.php?category=$categoryId";
        }
    }
    
    /**
     * Ürün detay URL'i oluştur
     * @param int $productId Ürün ID
     * @param string $fallback Varsayılan slug (veritabanından slug bulunamazsa)
     * @return string SEO-friendly ürün URL'i
     */
    public static function getProductUrl($productId, $fallback = null) {
        if (!self::$pdo) {
            return "view.php?id=$productId";
        }
        
        try {
            $stmt = self::$pdo->prepare("SELECT seo_slug FROM accounts WHERE id = ?");
            $stmt->execute([$productId]);
            $result = $stmt->fetch();
            
            if ($result && !empty($result['seo_slug'])) {
                return SITE_URL . $result['seo_slug'];
            }
            
            // Fallback kullan
            if ($fallback) {
                return SITE_URL . $fallback;
            }
            
            // Hiçbir şey yoksa eski format
            return SITE_URL . "view.php?id=$productId";
            
        } catch (PDOException $e) {
            error_log("URLHelper::getProductUrl error: " . $e->getMessage());
            return "view.php?id=$productId";
        }
    }
    
    /**
     * Static sayfa URL'i oluştur
     * @param string $page Sayfa adı
     * @return string SEO-friendly sayfa URL'i
     */
    public static function getPageUrl($page) {
        $urlMapping = [
            'home' => '',
            'index' => '',
            'accounts' => 'tum-hesaplar',
            'hesaplar' => 'tum-hesaplar',
            'sss' => 'sikca-sorulan-sorular',
            'faq' => 'sikca-sorulan-sorular',
            'hizmetler' => 'hizmetler',
            'services' => 'hizmetler',
            'iletisim' => 'iletisim',
            'contact' => 'iletisim',
            'kvvk' => 'kvkk',
            'kvkk' => 'kvkk',
            'login' => 'giris-yap',
            'giris' => 'giris-yap',
            'register' => 'kayit-ol',
            'kayit' => 'kayit-ol',
            'privacy' => 'gizlilik-politikasi',
            'gizlilik' => 'gizlilik-politikasi',
            'guest_order_track' => 'siparis-takip',
            'siparis-takip' => 'siparis-takip'
        ];
        
        $page = strtolower(str_replace('.php', '', $page));
        
        if (isset($urlMapping[$page])) {
            return SITE_URL . $urlMapping[$page];
        }
        
        return SITE_URL . $page;
    }
    
    /**
     * Tüm kategorileri slug ile beraber getir
     * @return array Kategoriler dizisi
     */
    public static function getAllCategoriesWithSlugs() {
        if (!self::$pdo) {
            return [];
        }
        
        try {
            $stmt = self::$pdo->query("SELECT id, name, seo_slug FROM categories WHERE is_active = 1 ORDER BY name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("URLHelper::getAllCategoriesWithSlugs error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Sitemap için tüm URL'leri getir
     * @return array Sitemap URL'leri
     */
    public static function getSitemapUrls() {
        if (!self::$pdo) {
            return [];
        }
        
        $urls = [];
        
        try {
            // Ana sayfa
            $urls[] = [
                'loc' => SITE_URL,
                'priority' => '1.0',
                'changefreq' => 'daily'
            ];
            
            // Static sayfalar
            $staticPages = [
                'tum-hesaplar' => ['priority' => '0.9', 'changefreq' => 'daily'],
                'sikca-sorulan-sorular' => ['priority' => '0.7', 'changefreq' => 'weekly'],
                'hizmetler' => ['priority' => '0.8', 'changefreq' => 'weekly'],
                'iletisim' => ['priority' => '0.7', 'changefreq' => 'monthly'],
                'giris-yap' => ['priority' => '0.5', 'changefreq' => 'monthly'],
                'kayit-ol' => ['priority' => '0.5', 'changefreq' => 'monthly']
            ];
            
            foreach ($staticPages as $page => $meta) {
                $urls[] = [
                    'loc' => SITE_URL . $page,
                    'priority' => $meta['priority'],
                    'changefreq' => $meta['changefreq']
                ];
            }
            
            // Kategoriler
            $stmt = self::$pdo->query("SELECT seo_slug, created_at FROM categories WHERE is_active = 1 AND seo_slug IS NOT NULL");
            while ($row = $stmt->fetch()) {
                $urls[] = [
                    'loc' => SITE_URL . $row['seo_slug'],
                    'priority' => '0.8',
                    'changefreq' => 'weekly',
                    'lastmod' => date('Y-m-d', strtotime($row['created_at']))
                ];
            }
            
            // Ürünler
            $stmt = self::$pdo->query("SELECT seo_slug, updated_at FROM accounts WHERE status = 'active' AND seo_slug IS NOT NULL");
            while ($row = $stmt->fetch()) {
                $urls[] = [
                    'loc' => SITE_URL . $row['seo_slug'],
                    'priority' => '0.7',
                    'changefreq' => 'daily',
                    'lastmod' => date('Y-m-d', strtotime($row['updated_at']))
                ];
            }
            
            return $urls;
            
        } catch (PDOException $e) {
            error_log("URLHelper::getSitemapUrls error: " . $e->getMessage());
            return [];
        }
    }
}

// Initialize URLHelper with database connection
if (isset($pdo)) {
    URLHelper::init($pdo);
}
?>

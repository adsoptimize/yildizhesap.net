<?php

class PopupAd {
    private $pdo;
    private $siteSettings;
    
    public function __construct($pdo, $siteSettings) {
        $this->pdo = $pdo;
        $this->siteSettings = $siteSettings;
        $this->ensureTableExists();
    }
    
    /**
     * Popup tablosunun varlığını kontrol eder ve gerekirse oluşturur
     */
    private function ensureTableExists() {
        try {
            // Önce tabloyu oluştur
            $sql = "
                CREATE TABLE IF NOT EXISTS popup_ad_views (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL,
                    user_id INT NULL,
                    view_date DATE NOT NULL,
                    view_count INT DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_view (ip_address, user_id, view_date)
                )
            ";
            $this->pdo->exec($sql);
            
            // content_hash kolonunun varlığını kontrol et
            $stmt = $this->pdo->query("SHOW COLUMNS FROM popup_ad_views LIKE 'content_hash'");
            $columnExists = $stmt->fetch();
            
            // visitor_id kolonunun varlığını kontrol et
            $stmt2 = $this->pdo->query("SHOW COLUMNS FROM popup_ad_views LIKE 'visitor_id'");
            $visitorColumnExists = $stmt2->fetch();
            
            if (!$columnExists) {
                // content_hash kolonunu ekle
                $this->pdo->exec("ALTER TABLE popup_ad_views ADD COLUMN content_hash VARCHAR(64) DEFAULT ''");
                
                // Mevcut kayıtlara varsayılan hash değeri ata
                $defaultHash = hash('sha256', 'legacy_content');
                $stmt = $this->pdo->prepare("UPDATE popup_ad_views SET content_hash = ? WHERE content_hash = ''");
                $stmt->execute([$defaultHash]);
                
                // Eski unique index'i kaldır
                try {
                    $this->pdo->exec("ALTER TABLE popup_ad_views DROP INDEX unique_view");
                } catch (Exception $e) {
                    // Index yoksa devam et
                }
                
                // Yeni unique index ekle (visitor_id bazlı)
                try {
                    // Kayıtlı kullanıcılar için user_id
                    $this->pdo->exec("ALTER TABLE popup_ad_views ADD UNIQUE KEY unique_view_users (user_id, view_date, content_hash)");
                    // Ziyaretçiler için visitor_id
                    $this->pdo->exec("ALTER TABLE popup_ad_views ADD UNIQUE KEY unique_view_visitors (visitor_id, view_date, content_hash)");
                } catch (Exception $e) {
                    // Index eklenemiyor, basit unique key kullan
                    error_log('Visitor-based unique index eklenemedi, basit unique key kullanılıyor: ' . $e->getMessage());
                    try {
                        $this->pdo->exec("ALTER TABLE popup_ad_views ADD UNIQUE KEY unique_view (ip_address, user_id, view_date, content_hash)");
                    } catch (Exception $e2) {
                        error_log('Unique key eklenemedi: ' . $e2->getMessage());
                    }
                }
                
                $this->pdo->exec("ALTER TABLE popup_ad_views ADD INDEX idx_content_hash (content_hash)");
                
                error_log('PopupAd: content_hash kolonu eklendi ve mevcut veriler güncellendi');
            }
            
            if (!$visitorColumnExists) {
                // visitor_id kolonunu ekle
                $this->pdo->exec("ALTER TABLE popup_ad_views ADD COLUMN visitor_id VARCHAR(128) NULL");
                $this->pdo->exec("ALTER TABLE popup_ad_views ADD INDEX idx_visitor_id (visitor_id)");
                
                error_log('PopupAd: visitor_id kolonu eklendi');
            }
            
        } catch (Exception $e) {
            error_log('PopupAd table creation/update error: ' . $e->getMessage());
        }
    }
    
    /**
     * İçerik hash'ini hesaplar
     */
    private function getContentHash() {
        $content = $this->siteSettings->get('popup_ad_content');
        $frequency = $this->siteSettings->get('popup_ad_frequency');
        $target = $this->siteSettings->get('popup_ad_target');
        
        // İçerik, frekans ve hedef kitle değişikliği durumunda yeni hash
        return hash('sha256', $content . '|' . $frequency . '|' . $target);
    }
    
    /**
     * Ziyaretçi için unique ID oluştur
     */
    private function getVisitorId($userId) {
        if ($userId !== null) {
            return $userId; // Kayıtlı kullanıcı için user_id kullan
        }
        
        // Ziyaretçiler için cookie bazlı unique ID
        $cookieName = 'popup_visitor_id';
        
        if (isset($_COOKIE[$cookieName]) && !empty($_COOKIE[$cookieName])) {
            return $_COOKIE[$cookieName];
        }
        
        // Yeni visitor ID oluştur
        $visitorId = 'visitor_' . bin2hex(random_bytes(16)) . '_' . time();
        
        // Cookie'yi 30 gün için set et
        setcookie($cookieName, $visitorId, time() + (30 * 24 * 60 * 60), '/', '', false, true);
        
        return $visitorId;
    }
    
    /**
     * Popup reklamın gösterilip gösterilmeyeceğini kontrol eder
     */
    public function shouldShowPopup($userId = null) {
        // Popup reklam aktif mi?
        if (!$this->siteSettings->get('popup_ad_enabled')) {
            return false;
        }
        
        $target = $this->siteSettings->get('popup_ad_target');
        $frequency = (int)$this->siteSettings->get('popup_ad_frequency', 1);
        
        // Hedef kitle kontrolü
        if ($target === 'visitors' && $userId !== null) {
            return false; // Sadece ziyaretçilere göster, ama kullanıcı giriş yapmış
        }
        
        if ($target === 'users' && $userId === null) {
            return false; // Sadece kullanıcılara göster, ama ziyaretçi
        }
        
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $today = date('Y-m-d');
        $contentHash = $this->getContentHash();
        
        // Ziyaretçiler için unique identifier oluştur
        $visitorId = $this->getVisitorId($userId);
        
        // Bugünkü gösterim sayısını kontrol et (visitor_id ile)
        try {
            if ($userId === null) {
                // Ziyaretçiler için visitor_id kullan
                $stmt = $this->pdo->prepare("
                    SELECT view_count 
                    FROM popup_ad_views 
                    WHERE visitor_id = ? AND user_id IS NULL AND view_date = ? AND content_hash = ?
                ");
                $stmt->execute([$visitorId, $today, $contentHash]);
            } else {
                // Kayıtlı kullanıcılar için user_id kullan
                $stmt = $this->pdo->prepare("
                    SELECT view_count 
                    FROM popup_ad_views 
                    WHERE user_id = ? AND view_date = ? AND content_hash = ?
                ");
                $stmt->execute([$userId, $today, $contentHash]);
            }
            
            $result = $stmt->fetch();
            $currentViews = $result ? (int)$result['view_count'] : 0;
            
            error_log("PopupAd Debug - IP: $ipAddress, UserID: " . ($userId ?? 'NULL') . ", VisitorID: $visitorId, Today: $today, Current Views: $currentViews, Frequency: $frequency");
            
            return $currentViews < $frequency;
            
        } catch (Exception $e) {
            // Eğer content_hash kolonu yoksa, eski sistemi kullan
            error_log('PopupAd shouldShowPopup error: ' . $e->getMessage());
            
            try {
                if ($userId === null) {
                    // Ziyaretçiler için IP kullan (fallback)
                    $stmt = $this->pdo->prepare("
                        SELECT view_count 
                        FROM popup_ad_views 
                        WHERE ip_address = ? AND user_id IS NULL AND view_date = ?
                    ");
                    $stmt->execute([$ipAddress, $today]);
                } else {
                    // Kayıtlı kullanıcılar için
                    $stmt = $this->pdo->prepare("
                        SELECT view_count 
                        FROM popup_ad_views 
                        WHERE user_id = ? AND view_date = ?
                    ");
                    $stmt->execute([$userId, $today]);
                }
                
                $result = $stmt->fetch();
                $currentViews = $result ? (int)$result['view_count'] : 0;
                
                error_log("PopupAd Fallback Debug - IP: $ipAddress, UserID: " . ($userId ?? 'NULL') . ", VisitorID: $visitorId, Current Views: $currentViews, Frequency: $frequency");
                
                return $currentViews < $frequency;
                
            } catch (Exception $e2) {
                error_log('PopupAd fallback error: ' . $e2->getMessage());
                return true; // Hata durumunda popup'u göster
            }
        }
    }
    
    /**
     * Popup gösterimini kaydet
     */
    public function recordPopupView($userId = null) {
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $today = date('Y-m-d');
        $contentHash = $this->getContentHash();
        $visitorId = $this->getVisitorId($userId);
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO popup_ad_views (ip_address, user_id, view_date, view_count, content_hash, visitor_id)
                VALUES (?, ?, ?, 1, ?, ?)
                ON DUPLICATE KEY UPDATE 
                view_count = view_count + 1,
                updated_at = CURRENT_TIMESTAMP
            ");
            
            $result = $stmt->execute([$ipAddress, $userId, $today, $contentHash, $visitorId]);
            
            if ($result) {
                error_log("PopupAd Record - IP: $ipAddress, UserID: " . ($userId ?? 'NULL') . ", VisitorID: $visitorId, Today: $today, Hash: $contentHash");
            }
            
            return $result;
            
        } catch (Exception $e) {
            // Eğer content_hash kolonu yoksa, eski sistemi kullan
            error_log('PopupAd recordPopupView error: ' . $e->getMessage());
            
            try {
                // visitor_id kolonunu da ekleyebilmeyi dene
                try {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO popup_ad_views (ip_address, user_id, view_date, view_count, visitor_id)
                        VALUES (?, ?, ?, 1, ?)
                        ON DUPLICATE KEY UPDATE 
                        view_count = view_count + 1,
                        updated_at = CURRENT_TIMESTAMP
                    ");
                    
                    $result = $stmt->execute([$ipAddress, $userId, $today, $visitorId]);
                    
                    if ($result) {
                        error_log("PopupAd Record Fallback with VisitorID - IP: $ipAddress, UserID: " . ($userId ?? 'NULL') . ", VisitorID: $visitorId, Today: $today");
                    }
                    
                    return $result;
                    
                } catch (Exception $e3) {
                    // visitor_id yoksa eski yöntemle
                    $stmt = $this->pdo->prepare("
                        INSERT INTO popup_ad_views (ip_address, user_id, view_date, view_count)
                        VALUES (?, ?, ?, 1)
                        ON DUPLICATE KEY UPDATE 
                        view_count = view_count + 1,
                        updated_at = CURRENT_TIMESTAMP
                    ");
                    
                    $result = $stmt->execute([$ipAddress, $userId, $today]);
                    
                    if ($result) {
                        error_log("PopupAd Record Fallback (no VisitorID) - IP: $ipAddress, UserID: " . ($userId ?? 'NULL') . ", Today: $today");
                    }
                    
                    return $result;
                }
                
            } catch (Exception $e2) {
                error_log('PopupAd fallback record error: ' . $e2->getMessage());
                return false;
            }
        }
    }
    
    /**
     * Popup reklam içeriğini güvenli şekilde al
     */
    public function getPopupContent() {
        $content = $this->siteSettings->get('popup_ad_content');
        
        // XSS koruması - sadece güvenli HTML taglerini izin ver
        $allowedTags = '<p><br><strong><b><em><i><u><a><img><div><span><h1><h2><h3><h4><h5><h6><ul><ol><li>';
        $content = strip_tags($content, $allowedTags);
        
        // Zararlı script taglerini temizle
        $content = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $content);
        $content = preg_replace('/on\w+="[^"]*"/i', '', $content); // onclick, onload vs. temizle
        $content = preg_replace('/javascript:/i', '', $content);
        
        return $content;
    }
    
    /**
     * İçerik değişikliğinde tüm kullanıcıların görme durumunu sıfırla
     * Bu fonksiyon popup içeriği değiştiğinde çağrılır
     */
    public function resetAllViews() {
        try {
            $currentHash = $this->getContentHash();
            
            // TÜM kayıtları sil - İçerik değiştiğinde herkes yeniden görmeli
            try {
                // Önce tüm kayıtları sil
                $stmt = $this->pdo->prepare("DELETE FROM popup_ad_views");
                $result = $stmt->execute();
                
                if ($result) {
                    $deletedRows = $stmt->rowCount();
                    error_log("PopupAd Reset: $deletedRows kayıt temizlendi - İçerik değiştiği için tüm kullanıcılar sıfırlandı");
                    return $deletedRows;
                }
                
            } catch (Exception $e) {
                error_log('PopupAd reset error: ' . $e->getMessage());
                
                // Alternatif yöntem - sadece bugünkü kayıtları sil
                try {
                    $today = date('Y-m-d');
                    $stmt = $this->pdo->prepare("DELETE FROM popup_ad_views WHERE view_date = ?");
                    $result = $stmt->execute([$today]);
                    
                    if ($result) {
                        $deletedRows = $stmt->rowCount();
                        error_log("PopupAd Reset Fallback: $deletedRows bugünkü kayıt temizlendi");
                        return $deletedRows;
                    }
                    
                } catch (Exception $e2) {
                    error_log('PopupAd reset fallback error: ' . $e2->getMessage());
                    return false;
                }
            }
            
            return 0;
        } catch (Exception $e) {
            error_log('PopupAd reset error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Eski kayıtları temizle (30 günden eski)
     */
    public function cleanupOldViews($days = 30) {
        try {
            $cutoffDate = date('Y-m-d', strtotime("-$days days"));
            
            $stmt = $this->pdo->prepare("
                DELETE FROM popup_ad_views 
                WHERE view_date < ?
            ");
            
            $result = $stmt->execute([$cutoffDate]);
            
            if ($result) {
                $deletedRows = $stmt->rowCount();
                error_log("PopupAd: $deletedRows eski kayıt temizlendi ($days günden eski)");
                return $deletedRows;
            }
            
            return 0;
        } catch (Exception $e) {
            error_log('PopupAd cleanup error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Popup istatistiklerini al
     */
    public function getStats() {
        try {
            $contentHash = $this->getContentHash();
            $today = date('Y-m-d');
            
            // content_hash kolonu varsa hash bazında istatistik al
            try {
                // Bugünkü gösterimler
                $stmt = $this->pdo->prepare("
                    SELECT 
                        COUNT(*) as total_viewers,
                        SUM(view_count) as total_views,
                        COUNT(CASE WHEN user_id IS NULL THEN 1 END) as visitor_viewers,
                        COUNT(CASE WHEN user_id IS NOT NULL THEN 1 END) as user_viewers
                    FROM popup_ad_views 
                    WHERE view_date = ? AND content_hash = ?
                ");
                $stmt->execute([$today, $contentHash]);
                $todayStats = $stmt->fetch();
                
                // Tüm zamanlar
                $stmt = $this->pdo->prepare("
                    SELECT 
                        COUNT(*) as total_viewers,
                        SUM(view_count) as total_views
                    FROM popup_ad_views 
                    WHERE content_hash = ?
                ");
                $stmt->execute([$contentHash]);
                $allTimeStats = $stmt->fetch();
                
                return [
                    'today' => $todayStats,
                    'all_time' => $allTimeStats,
                    'content_hash' => $contentHash
                ];
                
            } catch (Exception $e) {
                // Eğer content_hash kolonu yoksa, tüm kayıtları al
                error_log('PopupAd stats fallback: ' . $e->getMessage());
                
                // Bugünkü gösterimler
                $stmt = $this->pdo->prepare("
                    SELECT 
                        COUNT(*) as total_viewers,
                        SUM(view_count) as total_views,
                        COUNT(CASE WHEN user_id IS NULL THEN 1 END) as visitor_viewers,
                        COUNT(CASE WHEN user_id IS NOT NULL THEN 1 END) as user_viewers
                    FROM popup_ad_views 
                    WHERE view_date = ?
                ");
                $stmt->execute([$today]);
                $todayStats = $stmt->fetch();
                
                // Tüm zamanlar
                $stmt = $this->pdo->prepare("
                    SELECT 
                        COUNT(*) as total_viewers,
                        SUM(view_count) as total_views
                    FROM popup_ad_views
                ");
                $stmt->execute();
                $allTimeStats = $stmt->fetch();
                
                return [
                    'today' => $todayStats,
                    'all_time' => $allTimeStats,
                    'content_hash' => 'legacy'
                ];
            }
            
        } catch (Exception $e) {
            error_log('PopupAd stats error: ' . $e->getMessage());
            return null;
        }
    }
}

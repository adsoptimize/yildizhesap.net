<?php
class SiteSettings {
    private $pdo;
    private static $instance = null;
    private $settings = [];
    private $loaded = false;
    
    private function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
        $this->loadSettings();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadSettings() {
        if ($this->loaded) {
            return;
        }
        
        try {
            $stmt = $this->pdo->query("SELECT setting_key, setting_value FROM site_settings");
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($result as $row) {
                $this->settings[$row['setting_key']] = $row['setting_value'];
            }
            
            $this->loaded = true;
        } catch (Exception $e) {
            error_log("Site settings load error: " . $e->getMessage());
            $this->settings = $this->getDefaultSettings();
        }
    }
    
    public function get($key, $default = '') {
        return $this->settings[$key] ?? $default;
    }
    
    public function getJsonSetting($key, $default = []) {
        $value = $this->get($key, '');
        if (empty($value)) {
            return $default;
        }
        
        $decoded = json_decode($value, true);
        return $decoded !== null ? $decoded : $default;
    }
    
    public function set($key, $value) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO site_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
            ");
            $stmt->execute([$key, $value, $value]);
            
            // Cache'i güncelle
            $this->settings[$key] = $value;
            
            return true;
        } catch (Exception $e) {
            error_log("Site settings save error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAll() {
        return $this->settings;
    }
    
    public function getAllByCategory($category) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT setting_key, setting_value 
                FROM site_settings 
                WHERE category = ? 
                ORDER BY order_index
            ");
            $stmt->execute([$category]);
            
            $result = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $result[$row['setting_key']] = $row['setting_value'];
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Site settings category error: " . $e->getMessage());
            return [];
        }
    }
    
    private function getDefaultSettings() {
        return [
            'site_title' => 'BusinessHesap',
            'ban_page_title' => 'Erişim Engellendi',
            'ban_page_message' => 'IP adresiniz sistem yöneticisi tarafından engellenmiştir.',
            'ban_page_contact' => 'Destek için info@example.com adresine yazabilirsiniz.',
            'site_logo' => '',
            'hero_title' => 'Premium Sosyal Medya Hesapları',
            'hero_subtitle' => 'En Kaliteli Sosyal Medya Hesapları',
            'hero_description' => 'BusinessHesap güvencesiyle doğrulanmış, yüksek limitli ve güvenli hesapları keşfedin.',
            'hero_button_text' => 'Hemen Satın Al',
            'hero_button_url' => 'hesaplar.php',
            'hero_stats' => '[{"number":"60.620+","label":"Satılan Hesap"},{"number":"1.300+","label":"Stokta Hesap"},{"number":"9.281+","label":"Mutlu Müşteri"},{"number":"99.8%","label":"Memnuniyet Oranı"}]',
            'footer_description' => 'Güvenilir ve kaliteli sosyal medya hesapları.',
            'footer_copyright' => '© 2024 BusinessHesap. Tüm hakları saklıdır.',
            'contact_email' => 'info@businesshesap.com',
            'contact_phone' => '+90 555 000 00 00',
            'theme_primary_color' => '#6c63ff',
            'theme_accent_color' => '#42e2b8',
            'max_accounts_per_ip' => '1',
            'ip_limit_days' => '365',
            'ip_limit_enabled' => '1',
            'welcome_message_title' => 'Hoşgeldin!',
            'welcome_message_content' => 'Hesabın başarıyla oluşturuldu ve otomatik giriş yapıldı. Şimdi premium hesapları inceleyebilirsin!',
            'welcome_message_enabled' => '1'
        ];
    }
    
    public function refreshCache() {
        $this->loaded = false;
        $this->settings = [];
        $this->loadSettings();
    }
}
?>

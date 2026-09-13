<?php
require_once 'config.php';
require_once 'Auth.php';

class Admin {
    private $pdo;
    private $auth;
    
    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
        $this->auth = new Auth();
        
        if (!$this->auth->isLoggedIn() || !$this->isAdmin()) {
            header('Location: login.php');
            exit;
        }
    }

    public function isAdmin() {
        $user = $this->auth->getCurrentUser();
        return $user && $user['is_admin'] == 1;
    }

    public function getSiteSettings($category = 'general') {
        $stmt = $this->pdo->prepare("SELECT * FROM site_settings WHERE category = ? ORDER BY order_index");
        $stmt->execute([$category]);
        return $stmt->fetchAll();
    }

    public function updateSetting($key, $value) {
        $stmt = $this->pdo->prepare("UPDATE site_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
        return $stmt->execute([$value, $key]);
    }
}
?>

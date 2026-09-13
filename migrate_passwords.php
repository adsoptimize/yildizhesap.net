<?php
/**
 * Password Migration Script
 * Mevcut password_hash formatındaki şifreleri SHA256 + salt formatına dönüştürür
 * 
 * NOT: Bu script sadece bir kez çalıştırılmalıdır!
 */

require_once 'config.php';

echo "<h2>Password Migration Script</h2>";
echo "<p><strong>UYARI:</strong> Bu script mevcut şifreleri SHA256 formatına dönüştürür.</p>";

// Güvenlik kontrolü
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    echo "<p style='color: red;'>Bu script çalıştırılmadan önce onay gereklidir.</p>";
    echo "<p><a href='migrate_passwords.php?confirm=yes&action=analyze' style='background: blue; color: white; padding: 10px; text-decoration: none;'>Analiz Et</a></p>";
    echo "<p><a href='migrate_passwords.php?confirm=yes&action=migrate' style='background: red; color: white; padding: 10px; text-decoration: none;'>Migration Başlat</a></p>";
    exit;
}

$action = $_GET['action'] ?? 'analyze';

try {
    if ($action === 'analyze') {
        // Mevcut durumu analiz et
        echo "<h3>Analiz Sonuçları</h3>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $totalUsers = $stmt->fetch()['total'];
        echo "<p>Toplam kullanıcı sayısı: <strong>$totalUsers</strong></p>";
        
        // Yeni format (SHA256) kontrolü
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE password_hash LIKE '%:%' AND LENGTH(password_hash) > 64");
        $newFormatCount = $stmt->fetch()['count'];
        echo "<p>SHA256 formatında: <strong>$newFormatCount</strong></p>";
        
        // Eski format (password_hash) kontrolü  
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE password_hash NOT LIKE '%:%' OR LENGTH(password_hash) <= 64");
        $oldFormatCount = $stmt->fetch()['count'];
        echo "<p>Eski formatında: <strong>$oldFormatCount</strong></p>";
        
        if ($oldFormatCount > 0) {
            echo "<h4>Migration Gerekli</h4>";
            echo "<p style='color: orange;'>$oldFormatCount kullanıcının şifresi migration gerektirir.</p>";
            echo "<p><strong>NOT:</strong> Migration sırasında eski şifreler geçici olarak işlenemez hale gelecektir.</p>";
            echo "<p>Migration sadece kullanıcılar giriş yaptığında otomatik olarak gerçekleşir.</p>";
        } else {
            echo "<p style='color: green;'>Tüm şifreler güncel formatta!</p>";
        }
        
    } elseif ($action === 'migrate') {
        echo "<h3>Migration Başlatılıyor...</h3>";
        
        // Eski formattaki kullanıcıları bul
        $stmt = $pdo->query("
            SELECT id, username, email, password_hash 
            FROM users 
            WHERE password_hash NOT LIKE '%:%' OR LENGTH(password_hash) <= 64
        ");
        $oldUsers = $stmt->fetchAll();
        
        echo "<p>Migration edilecek kullanıcı sayısı: <strong>" . count($oldUsers) . "</strong></p>";
        
        if (count($oldUsers) > 0) {
            echo "<h4>Migration Stratejisi</h4>";
            echo "<p style='color: orange;'>Bu kullanıcıların şifreleri bir sonraki giriş sırasında otomatik olarak güncellenecektir.</p>";
            echo "<p>Şu an için herhangi bir işlem yapılmayacak, çünkü plaintext şifreler mevcut değil.</p>";
            
            // Migration flag'i ekle
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'needs_password_migration'");
                $columnExists = $stmt->fetch();
                
                if (!$columnExists) {
                    $pdo->exec("ALTER TABLE users ADD COLUMN needs_password_migration TINYINT(1) DEFAULT 0");
                    echo "<p style='color: green;'>Migration flag kolonu eklendi.</p>";
                }
                
                // Eski formattaki kullanıcıları işaretle
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET needs_password_migration = 1 
                    WHERE password_hash NOT LIKE '%:%' OR LENGTH(password_hash) <= 64
                ");
                $result = $stmt->execute();
                
                if ($result) {
                    $affectedRows = $stmt->rowCount();
                    echo "<p style='color: green;'>$affectedRows kullanıcı migration için işaretlendi.</p>";
                }
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>Migration flag hatası: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: green;'>Migration gerekli değil - tüm şifreler güncel!</p>";
        }
        
    } else {
        echo "<p style='color: red;'>Geçersiz işlem!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Hata: " . $e->getMessage() . "</p>";
}

echo "<br><br>";
echo "<a href='migrate_passwords.php' style='background: gray; color: white; padding: 10px; text-decoration: none;'>Başa Dön</a>";
echo " <a href='admin/users.php' style='background: green; color: white; padding: 10px; text-decoration: none;'>Kullanıcılar Sayfası</a>";
echo " <a href='debug_popup.php' style='background: blue; color: white; padding: 10px; text-decoration: none;'>Debug Sayfası</a>";
?>

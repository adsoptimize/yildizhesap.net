<?php
require_once 'config.php';

echo "<h1>Hesap Yönetim Sistemi Kurulumu</h1>";

try {
    // 1. Accounts tablosunu oluştur
    echo "<h2>1. Accounts tablosu oluşturuluyor...</h2>";
    
    $sql = file_get_contents('accounts_table.sql');
    $pdo->exec($sql);
    
    echo "✅ Accounts tablosu başarıyla oluşturuldu.<br>";
    
    // 2. Orders tablosunu kontrol et ve güncelle
    echo "<h2>2. Orders tablosu kontrol ediliyor...</h2>";
    
    $sql = file_get_contents('orders_tables.sql');
    $pdo->exec($sql);
    
    echo "✅ Orders tablosu başarıyla güncellendi.<br>";
    
    // 3. Crypto payments tablosunu kontrol et
    echo "<h2>3. Crypto payments tablosu kontrol ediliyor...</h2>";
    
    $sql = file_get_contents('crypto_payments_tables.sql');
    $pdo->exec($sql);
    
    echo "✅ Crypto payments tablosu başarıyla güncellendi.<br>";
    
    // 3.5. Order accounts tablosunu oluştur
    echo "<h2>3.5. Order accounts tablosu oluşturuluyor...</h2>";
    
    $sql = file_get_contents('order_accounts_table.sql');
    $pdo->exec($sql);
    
    echo "✅ Order accounts tablosu başarıyla oluşturuldu.<br>";
    
    // 4. Site settings tablosunu kontrol et
    echo "<h2>4. Site settings tablosu kontrol ediliyor...</h2>";
    
    $stmt = $pdo->prepare("
        CREATE TABLE IF NOT EXISTS `site_settings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `setting_key` varchar(100) NOT NULL,
            `setting_value` text DEFAULT NULL,
            `is_encrypted` tinyint(1) DEFAULT 0,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `setting_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $stmt->execute();
    
    echo "✅ Site settings tablosu başarıyla oluşturuldu.<br>";
    
    // 5. Telegram ayarlarını ekle
    echo "<h2>5. Telegram ayarları ekleniyor...</h2>";
    
    $telegramSettings = [
        'telegram_enabled' => '0',
        'telegram_bot_token' => '',
        'telegram_chat_id' => ''
    ];
    
    foreach ($telegramSettings as $key => $value) {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO site_settings (setting_key, setting_value, is_encrypted) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$key, $value, $key === 'telegram_bot_token' ? 1 : 0]);
    }
    
    echo "✅ Telegram ayarları başarıyla eklendi.<br>";
    
    // 6. Test verilerini kontrol et
    echo "<h2>6. Test verileri kontrol ediliyor...</h2>";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE status = 'active'");
    $stmt->execute();
    $accountCount = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM account_stock");
    $stmt->execute();
    $stockCount = $stmt->fetchColumn();
    
    if ($accountCount == 0) {
        echo "⚠️ Accounts tablosunda aktif ürün bulunamadı.<br>";
    } else {
        echo "✅ Accounts tablosunda $accountCount adet aktif ürün bulundu.<br>";
    }
    
    if ($stockCount == 0) {
        echo "⚠️ Account_stock tablosunda hesap bulunamadı.<br>";
    } else {
        echo "✅ Account_stock tablosunda $stockCount adet hesap bulundu.<br>";
    }
    
    // 7. Stok durumunu göster
    echo "<h2>7. Stok durumu:</h2>";
    
    $stmt = $pdo->prepare("
        SELECT 
            a.title,
            a.platform,
            COUNT(ast.id) as total,
            SUM(CASE WHEN ast.is_sold = 0 THEN 1 ELSE 0 END) as available,
            SUM(CASE WHEN ast.is_sold = 1 THEN 1 ELSE 0 END) as sold
        FROM accounts a
        LEFT JOIN account_stock ast ON a.id = ast.account_id
        WHERE a.status = 'active'
        GROUP BY a.id, a.title, a.platform
        ORDER BY available DESC
    ");
    $stmt->execute();
    $stockStatus = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Ürün</th><th>Platform</th><th>Toplam</th><th>Mevcut</th><th>Satılan</th></tr>";
    
    foreach ($stockStatus as $stock) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($stock['title']) . "</td>";
        echo "<td>" . htmlspecialchars($stock['platform']) . "</td>";
        echo "<td>" . $stock['total'] . "</td>";
        echo "<td style='color: green;'>" . $stock['available'] . "</td>";
        echo "<td style='color: red;'>" . $stock['sold'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 8. Sistem kontrolü
    echo "<h2>8. Sistem kontrolü:</h2>";
    
    $checks = [
        'AutoAccountAssignment sınıfı' => class_exists('AutoAccountAssignment'),
        'Accounts tablosu' => $pdo->query("SHOW TABLES LIKE 'accounts'")->rowCount() > 0,
        'Orders tablosu' => $pdo->query("SHOW TABLES LIKE 'orders'")->rowCount() > 0,
        'Crypto_payments tablosu' => $pdo->query("SHOW TABLES LIKE 'crypto_payments'")->rowCount() > 0,
        'Order_accounts tablosu' => $pdo->query("SHOW TABLES LIKE 'order_accounts'")->rowCount() > 0,
        'Site_settings tablosu' => $pdo->query("SHOW TABLES LIKE 'site_settings'")->rowCount() > 0
    ];
    
    foreach ($checks as $check => $result) {
        $status = $result ? '✅' : '❌';
        echo "$status $check<br>";
    }
    
    echo "<h2>🎉 Kurulum tamamlandı!</h2>";
    echo "<p>Sistem başarıyla kuruldu. Şimdi şu işlemleri yapabilirsiniz:</p>";
    echo "<ul>";
    echo "<li><a href='hesaplarim.php'>Hesaplarım sayfasını test edin</a></li>";
    echo "<li><a href='admin/order_management.php'>Admin panelinden sipariş yönetimini test edin</a></li>";
    echo "<li><a href='auto_account_assignment.php'>Otomatik hesap atama sistemini test edin</a></li>";
    echo "</ul>";
    
    echo "<p><strong>Güvenlik:</strong> Bu dosyayı kurulum tamamlandıktan sonra silmeyi unutmayın!</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Hata oluştu:</h2>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Lütfen hata mesajını kontrol edin ve tekrar deneyin.</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background: #f5f5f5;
}

h1 {
    color: #333;
    text-align: center;
    margin-bottom: 30px;
}

h2 {
    color: #666;
    border-bottom: 2px solid #ddd;
    padding-bottom: 10px;
    margin-top: 30px;
}

table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

th {
    background: #f8f9fa;
    padding: 12px;
    font-weight: bold;
    text-align: left;
}

td {
    padding: 12px;
    border-top: 1px solid #eee;
}

ul {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

li {
    margin: 10px 0;
}

a {
    color: #007bff;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style> 
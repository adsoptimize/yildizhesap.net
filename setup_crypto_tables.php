<?php
require_once 'config.php';

echo "<h2>Cryptomus Tabloları Oluşturuluyor...</h2>";

try {
    // Ana ödeme tablosu
    $sql = "CREATE TABLE IF NOT EXISTS `crypto_payments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `order_id` varchar(255) NOT NULL,
        `amount` decimal(10,2) NOT NULL,
        `currency` varchar(10) DEFAULT 'USD',
        `status` enum('pending','paid','failed','cancelled','expired') DEFAULT 'pending',
        `cryptomus_uuid` varchar(255) DEFAULT NULL,
        `payment_url` text DEFAULT NULL,
        `payment_amount` decimal(10,2) DEFAULT NULL,
        `network` varchar(50) DEFAULT NULL,
        `to_currency` varchar(10) DEFAULT NULL,
        `address` varchar(255) DEFAULT NULL,
        `txid` varchar(255) DEFAULT NULL,
        `expired_at` datetime DEFAULT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `order_id` (`order_id`),
        KEY `user_id` (`user_id`),
        KEY `status` (`status`),
        KEY `cryptomus_uuid` (`cryptomus_uuid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ crypto_payments tablosu oluşturuldu<br>";
    
    // Ödeme öğeleri tablosu
    $sql = "CREATE TABLE IF NOT EXISTS `payment_items` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `payment_id` int(11) NOT NULL,
        `account_id` int(11) NOT NULL,
        `quantity` int(11) NOT NULL,
        `unit_price` decimal(10,2) NOT NULL,
        `total_price` decimal(10,2) NOT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `payment_id` (`payment_id`),
        KEY `account_id` (`account_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ payment_items tablosu oluşturuldu<br>";
    
    // Cryptomus ayarları tablosu
    $sql = "CREATE TABLE IF NOT EXISTS `crypto_settings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `setting_key` varchar(100) NOT NULL,
        `setting_value` text DEFAULT NULL,
        `is_encrypted` tinyint(1) DEFAULT 0,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ crypto_settings tablosu oluşturuldu<br>";
    
    // Ödeme logları tablosu
    $sql = "CREATE TABLE IF NOT EXISTS `payment_logs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `amount` decimal(10,2) NOT NULL,
        `status` varchar(50) NOT NULL,
        `payment_uuid` varchar(255) DEFAULT NULL,
        `order_id` varchar(255) DEFAULT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `payment_uuid` (`payment_uuid`),
        KEY `order_id` (`order_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ payment_logs tablosu oluşturuldu<br>";
    
    // Webhook logları tablosu
    $sql = "CREATE TABLE IF NOT EXISTS `crypto_webhook_logs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order_id` varchar(255) DEFAULT NULL,
        `cryptomus_uuid` varchar(255) DEFAULT NULL,
        `webhook_data` text DEFAULT NULL,
        `signature` varchar(255) DEFAULT NULL,
        `status` enum('valid','error','processed') DEFAULT 'valid',
        `ip_address` varchar(45) DEFAULT NULL,
        `user_agent` text DEFAULT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `processed_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `order_id` (`order_id`),
        KEY `cryptomus_uuid` (`cryptomus_uuid`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ crypto_webhook_logs tablosu oluşturuldu<br>";
    
    // Varsayılan ayarları ekle
    $settings = [
        ['cryptomus_enabled', '1', 0],
        ['cryptomus_merchant_uuid', '04a2aa40-d91e-48c1-8c2d-3c9cf50e8284', 0],
        ['cryptomus_payment_key', 'Fpw0XJtQV4xNMGZk4elsbeoXbhNbD4FF2n91CsjfBWeSmHZUPJ0qLmqp71xK9SGLEkHG0P9KBjp1SBmJspoIXgL252OLXRT4J6wLXTlBEZhAF7XKFvbsREe0PEg6DeDm', 1],
        ['cryptomus_payout_key', '', 1],
        ['cryptomus_test_mode', '1', 0],
        ['cryptomus_webhook_secret', '', 1],
        ['default_currency', 'USD', 0],
        ['supported_networks', 'BTC,ETH,TRON,LTC', 0]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO crypto_settings (setting_key, setting_value, is_encrypted) VALUES (?, ?, ?)");
    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }
    echo "✅ Varsayılan ayarlar eklendi<br>";
    
    echo "<h3>🎉 Tüm tablolar başarıyla oluşturuldu!</h3>";
    echo "<p><a href='checkout.php'>Checkout sayfasına dön</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Hata: " . $e->getMessage() . "</h3>";
}
?> 
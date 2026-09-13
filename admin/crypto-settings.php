<?php
// Admin authentication
require_once 'auth_header.php';

// Mevcut ayarları çekme
$settings = [];
$settingsStmt = $pdo->query("SELECT * FROM crypto_settings");
while ($row = $settingsStmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF kontrolü
    if (!isset($_POST['_csrf']) || $_POST['_csrf'] !== $_SESSION['csrf_token']) {
        die('CSRF token hatası!');
    }

    // Ayarları güncelleme
    $updateStmt = $pdo->prepare("UPDATE crypto_settings SET setting_value = ? WHERE setting_key = ?");
    
    foreach ($_POST as $key => $value) {
        if ($key === '_csrf') continue;
        $updateStmt->execute([$value, $key]);
    }
    
    $_SESSION['success_message'] = 'Ayarlar başarıyla güncellendi!';
    header('Location: crypto-settings.php');
    exit;
}

// Yeni CSRF token oluştur
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$pageTitle = 'Ödeme Ayarları';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --accent: #f093fb;
            --success: #4ade80;
            --danger: #ef4444;
            --dark: #1e293b;
            --light: #f8fafc;
            --border: #e2e8f0;
            --shadow: rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            box-shadow: 4px 0 20px var(--shadow);
        }
        
        .sidebar-header {
            padding: 2rem;
            border-bottom: 1px solid var(--border);
            text-align: center;
        }

        .sidebar-logo {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0.25rem 1rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            color: #64748b;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-link:hover, .nav-link.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .main-header {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
        }
        
        .form-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 32px var(--shadow);
            margin-bottom: 2rem;
        }

        .form-section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #4b5563;
        }

        .form-input,
        .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .form-description {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.5rem;
        }

        .form-actions {
            text-align: right;
        }

        .btn-save {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-save:hover {
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            color: white;
            text-align: center;
        }
        
        .alert.success {
            background: var(--success);
        }
        
        .btn-test {
            background: linear-gradient(135deg, #10b981, #14b8a6);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-right: 1rem;
        }
        
        .btn-test:hover {
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
            transform: translateY(-2px);
        }
        
        .test-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .test-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: static;
            }
            
            .main-content {
                padding: 1rem;
            }
        }
    </style>
    <script>
        function testConnection() {
            const btn = document.querySelector('.btn-test');
            const result = document.getElementById('test-result');
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Test Ediliyor...';
            btn.disabled = true;
            
            fetch('test_crypto_connection.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    merchant_uuid: document.getElementById('cryptomus_merchant_uuid').value,
                    payment_key: document.getElementById('cryptomus_payment_key').value
                })
            })
            .then(response => response.json())
            .then(data => {
                result.style.display = 'block';
                if (data.success) {
                    result.className = 'test-success';
                    result.innerHTML = '<i class="fas fa-check"></i> Bağlantı başarılı! API anahtarları geçerli.';
                } else {
                    result.className = 'test-error';
                    result.innerHTML = '<i class="fas fa-times"></i> Bağlantı hatası: ' + data.message;
                }
            })
            .catch(error => {
                result.style.display = 'block';
                result.className = 'test-error';
                result.innerHTML = '<i class="fas fa-times"></i> Test sırasında hata oluştu.';
            })
            .finally(() => {
                btn.innerHTML = 'API Bağlantısını Test Et';
                btn.disabled = false;
            });
        }
    </script>
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="main-header">
                <h1 class="page-title">Cryptomus Ödeme Ayarları</h1>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert success">
                <?= $_SESSION['success_message'] ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="_csrf" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="form-card">
                    <h2 class="form-section-title">API Bilgileri</h2>
                    <div class="form-group">
                        <label for="cryptomus_merchant_uuid" class="form-label">Merchant UUID</label>
                        <input type="text" id="cryptomus_merchant_uuid" name="cryptomus_merchant_uuid" class="form-input" value="<?= htmlspecialchars($settings['cryptomus_merchant_uuid'] ?? '') ?>">
                        <p class="form-description">Cryptomus hesabınızdaki Merchant UUID.</p>
                    </div>
                    <div class="form-group">
                        <label for="cryptomus_payment_key" class="form-label">Payment API Key</label>
                        <input type="password" id="cryptomus_payment_key" name="cryptomus_payment_key" class="form-input" value="<?= htmlspecialchars($settings['cryptomus_payment_key'] ?? '') ?>">
                        <p class="form-description">Cryptomus ödeme API anahtarınız.</p>
                    </div>
                    <div class="form-group">
                        <label for="cryptomus_payout_key" class="form-label">Payout API Key</label>
                        <input type="password" id="cryptomus_payout_key" name="cryptomus_payout_key" class="form-input" value="<?= htmlspecialchars($settings['cryptomus_payout_key'] ?? '') ?>">
                        <p class="form-description">Cryptomus para çekme API anahtarınız.</p>
                    </div>
                </div>

                <div class="form-card">
                    <h2 class="form-section-title">Genel Ayarlar</h2>
                    <div class="form-group">
                        <label for="cryptomus_enabled" class="form-label">Cryptomus Ödemeleri</label>
                        <select id="cryptomus_enabled" name="cryptomus_enabled" class="form-select">
                            <option value="1" <?= ($settings['cryptomus_enabled'] ?? 0) == 1 ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= ($settings['cryptomus_enabled'] ?? 0) == 0 ? 'selected' : '' ?>>Pasif</option>
                        </select>
                        <p class="form-description">Cryptomus ödeme yöntemini sitede aktif et.</p>
                    </div>
                    <div class="form-group">
                        <label for="cryptomus_test_mode" class="form-label">Test Modu</label>
                        <select id="cryptomus_test_mode" name="cryptomus_test_mode" class="form-select">
                            <option value="1" <?= ($settings['cryptomus_test_mode'] ?? 0) == 1 ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= ($settings['cryptomus_test_mode'] ?? 0) == 0 ? 'selected' : '' ?>>Pasif</option>
                        </select>
                        <p class="form-description">Test modu aktifken gerçek ödeme yapılmaz.</p>
                    </div>
                    <div class="form-group">
                        <label for="default_currency" class="form-label">Varsayılan Para Birimi</label>
                        <select id="default_currency" name="default_currency" class="form-select">
                            <option value="USD" <?= ($settings['default_currency'] ?? 'USD') == 'USD' ? 'selected' : '' ?>>Amerikan Doları ($)</option>
                            <option value="TRY" <?= ($settings['default_currency'] ?? 'USD') == 'TRY' ? 'selected' : '' ?>>Türk Lirası (₺)</option>
                        </select>
                        <p class="form-description">Sitede kullanılacak ana para birimi. Bu seçim tüm fiyatları etkileyecektir.</p>
                    </div>
                    <div class="form-group">
                        <label for="supported_networks" class="form-label">Desteklenen Ağlar</label>
                        <input type="text" id="supported_networks" name="supported_networks" class="form-input" value="<?= htmlspecialchars($settings['supported_networks'] ?? 'BTC,ETH,TRON,LTC') ?>">
                        <p class="form-description">Kabul edilen kripto ağları (virgülle ayırın).</p>
                    </div>
                </div>

                <div class="form-card">
                    <h2 class="form-section-title">Webhook Ayarları</h2>
                    <div class="form-group">
                        <label for="cryptomus_webhook_secret" class="form-label">Webhook Secret Key</label>
                        <input type="password" id="cryptomus_webhook_secret" name="cryptomus_webhook_secret" class="form-input" value="<?= htmlspecialchars($settings['cryptomus_webhook_secret'] ?? '') ?>">
                        <p class="form-description">Webhook doğrulaması için güvenlik anahtarı.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Webhook URL'leri</label>
                        <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; margin-top: 0.5rem;">
                            <p><strong>Ödeme Callback URL:</strong><br>
                            <code style="color: #667eea;"><?= 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] ?>/cryptomus_callback.php</code></p>
                            <p style="margin-top: 0.5rem;"><strong>Return URL:</strong><br>
                            <code style="color: #667eea;"><?= 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] ?>/payment_success.php</code></p>
                            <p class="form-description" style="margin-top: 0.5rem;">Bu URL'leri Cryptomus panelinde webhook ayarlarına ekleyin.</p>
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <h2 class="form-section-title">Test ve Durum</h2>
                    <div class="form-group">
                        <button type="button" class="btn-test" onclick="testConnection()">API Bağlantısını Test Et</button>
                        <p class="form-description">Cryptomus API ayarlarınızı test edin.</p>
                        <div id="test-result" style="margin-top: 1rem; padding: 1rem; border-radius: 8px; display: none;"></div>
                    </div>
                </div>

                <!-- Shopier Ayarları -->
                <div class="form-card">
                    <h2 class="form-section-title">Shopier Ayarları</h2>
                    <div class="form-group">
                        <label for="shopier_enabled" class="form-label">Shopier Ödemeleri</label>
                        <select id="shopier_enabled" name="shopier_enabled" class="form-select">
                            <option value="1" <?= ($settings['shopier_enabled'] ?? 0) == 1 ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= ($settings['shopier_enabled'] ?? 0) == 0 ? 'selected' : '' ?>>Pasif</option>
                        </select>
                        <p class="form-description">Shopier ödeme yöntemini sitede aktif et.</p>
                    </div>
                    <div class="form-group">
                        <label for="shopier_username" class="form-label">Shopier Username</label>
                        <input type="text" id="shopier_username" name="shopier_username" class="form-input" value="<?= htmlspecialchars($settings['shopier_username'] ?? '') ?>">
                        <p class="form-description">Shopier hesabınızdan aldığınız kullanıcı adı.</p>
                    </div>
                    <div class="form-group">
                        <label for="shopier_key" class="form-label">Shopier Key</label>
                        <input type="password" id="shopier_key" name="shopier_key" class="form-input" value="<?= htmlspecialchars($settings['shopier_key'] ?? '') ?>">
                        <p class="form-description">Shopier hesabınızdan aldığınız gizli anahtar.</p>
                    </div>
                    <div class="form-group">
                        <label for="shopier_test_mode" class="form-label">Test Modu</label>
                        <select id="shopier_test_mode" name="shopier_test_mode" class="form-select">
                            <option value="1" <?= ($settings['shopier_test_mode'] ?? 0) == 1 ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= ($settings['shopier_test_mode'] ?? 0) == 0 ? 'selected' : '' ?>>Pasif</option>
                        </select>
                        <p class="form-description">Test modu aktifken gerçek ödeme yapılmaz.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Shopier Webhook URL</label>
                        <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; margin-top: 0.5rem;">
                            <p><strong>Webhook URL:</strong><br>
                            <code style="color: #667eea;"><?= 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] ?>/shopier_webhook.php</code></p>
                            <p class="form-description" style="margin-top: 0.5rem;">Bu URL'yi Shopier panelinde webhook ayarlarına ekleyin.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="button" class="btn-test" onclick="testShopierConnection()">Shopier API Bağlantısını Test Et</button>
                        <p class="form-description">Shopier API ayarlarınızı test edin.</p>
                        <div id="shopier-test-result" style="margin-top: 1rem; padding: 1rem; border-radius: 8px; display: none;"></div>
                    </div>
                </div>

                <!-- Döviz Kuru Ayarları -->
                <div class="form-card">
                    <h2 class="form-section-title">Döviz Kuru Ayarları</h2>
                    <div class="form-group">
                        <label for="usd_to_try_rate" class="form-label">USD/TRY Kuru</label>
                        <input type="number" step="0.01" id="usd_to_try_rate" name="usd_to_try_rate" class="form-input" value="<?= htmlspecialchars($settings['usd_to_try_rate'] ?? '30.00') ?>">
                        <p class="form-description">1 USD = ? TRY (Shopier ödemeleri için kullanılır)</p>
                    </div>
                    <div class="form-group">
                        <label for="eur_to_try_rate" class="form-label">EUR/TRY Kuru</label>
                        <input type="number" step="0.01" id="eur_to_try_rate" name="eur_to_try_rate" class="form-input" value="<?= htmlspecialchars($settings['eur_to_try_rate'] ?? '33.00') ?>">
                        <p class="form-description">1 EUR = ? TRY (Shopier ödemeleri için kullanılır)</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Döviz Kuru Bilgisi</label>
                        <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; margin-top: 0.5rem;">
                            <p style="color: #667eea; margin: 0;">💡 <strong>Bilgi:</strong> Shopier sadece TRY (Türk Lirası) ödemelerini kabul eder. USD ve EUR fiyatları bu kurlar kullanılarak otomatik olarak TRY'ye çevrilir.</p>
                        </div>
                    </div>
                </div>

                <!-- Telegram Bildirim Ayarları -->
                <div class="form-card">
                    <h2 class="form-section-title">Telegram Bildirim Ayarları</h2>
                    <div class="form-group">
                        <label for="telegram_enabled" class="form-label">Telegram Bildirimleri</label>
                        <select id="telegram_enabled" name="telegram_enabled" class="form-select">
                            <option value="1" <?= ($settings['telegram_enabled'] ?? 0) == 1 ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= ($settings['telegram_enabled'] ?? 0) == 0 ? 'selected' : '' ?>>Pasif</option>
                        </select>
                        <p class="form-description">Stok yetersizliği ve ödeme bildirimlerini Telegram üzerinden al.</p>
                    </div>
                    <div class="form-group">
                        <label for="telegram_bot_token" class="form-label">Telegram Bot Token</label>
                        <input type="password" id="telegram_bot_token" name="telegram_bot_token" class="form-input" value="<?= htmlspecialchars($settings['telegram_bot_token'] ?? '') ?>">
                        <p class="form-description">Telegram bot token'ınızı buraya girin. Bot oluşturmak için @BotFather ile konuşun.</p>
                    </div>
                    <div class="form-group">
                        <label for="telegram_chat_id" class="form-label">Telegram Chat ID</label>
                        <input type="text" id="telegram_chat_id" name="telegram_chat_id" class="form-input" value="<?= htmlspecialchars($settings['telegram_chat_id'] ?? '') ?>">
                        <p class="form-description">Bildirimlerin gönderileceği chat ID (grup veya kanal). Örnek: -1001234567890</p>
                    </div>
                    <div class="form-group">
                        <button type="button" class="btn-test" onclick="testTelegramConnection()">Telegram Bağlantısını Test Et</button>
                        <p class="form-description">Telegram bot ayarlarınızı test edin.</p>
                        <div id="telegram-test-result" style="margin-top: 1rem; padding: 1rem; border-radius: 8px; display: none;"></div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-save">Ayarları Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function testConnection() {
            const testResult = document.getElementById('test-result');
            testResult.style.display = 'block';
            testResult.innerHTML = '<div style="color: #667eea;">API bağlantısı test ediliyor...</div>';
            
            // AJAX ile test isteği gönder
            fetch('test_crypto_connection.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'test_cryptomus'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    testResult.innerHTML = '<div style="color: #28a745;">✅ ' + data.message + '</div>';
                } else {
                    testResult.innerHTML = '<div style="color: #dc3545;">❌ ' + data.message + '</div>';
                }
            })
            .catch(error => {
                testResult.innerHTML = '<div style="color: #dc3545;">❌ Bağlantı hatası: ' + error.message + '</div>';
            });
        }
        
        function testShopierConnection() {
            const testResult = document.getElementById('shopier-test-result');
            testResult.style.display = 'block';
            testResult.innerHTML = '<div style="color: #667eea;">Shopier API bağlantısı test ediliyor...</div>';
            
            // AJAX ile test isteği gönder
            fetch('test_crypto_connection.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'test_shopier'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    testResult.innerHTML = '<div style="color: #28a745;">✅ ' + data.message + '</div>';
                } else {
                    testResult.innerHTML = '<div style="color: #dc3545;">❌ ' + data.message + '</div>';
                }
            })
            .catch(error => {
                testResult.innerHTML = '<div style="color: #dc3545;">❌ Bağlantı hatası: ' + error.message + '</div>';
            });
        }
        
        function testTelegramConnection() {
            const testResult = document.getElementById('telegram-test-result');
            testResult.style.display = 'block';
            testResult.innerHTML = '<div style="color: #667eea;">Telegram bağlantısı test ediliyor...</div>';
            
            // AJAX ile test isteği gönder
            fetch('test_crypto_connection.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'test_telegram'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    testResult.innerHTML = '<div style="color: #28a745;">✅ ' + data.message + '</div>';
                } else {
                    testResult.innerHTML = '<div style="color: #dc3545;">❌ ' + data.message + '</div>';
                }
            })
            .catch(error => {
                testResult.innerHTML = '<div style="color: #dc3545;">❌ Bağlantı hatası: ' + error.message + '</div>';
            });
        }
    </script>
</body>
</html>

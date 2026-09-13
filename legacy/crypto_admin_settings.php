<?php
/**
 * Cryptomus Admin Ayarları
 * NOT: Bu dosya gerçek uygulamada güvenli admin paneline taşınmalıdır!
 */

require_once 'config.php';
require_once 'functions.php';
require_once 'CryptomusPaymentManager.php';

$error = '';
$success = '';

// Bu dosyayı sadece admin IP'lerden erişilebilir yapmak için:
$allowedIPs = ['127.0.0.1', '::1', 'localhost'];
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (!in_array($clientIP, $allowedIPs)) {
    die('Bu sayfaya erişim yetkiniz yok.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $paymentManager = new CryptomusPaymentManager();
        
        // Ayarları güncelle
        $settings = [
            'cryptomus_merchant_uuid' => sanitizeInput($_POST['merchant_uuid'] ?? ''),
            'cryptomus_payment_key' => sanitizeInput($_POST['payment_key'] ?? ''),
            'cryptomus_payout_key' => sanitizeInput($_POST['payout_key'] ?? ''),
            'cryptomus_webhook_secret' => sanitizeInput($_POST['webhook_secret'] ?? ''),
            'cryptomus_enabled' => isset($_POST['enabled']) ? '1' : '0',
            'cryptomus_test_mode' => isset($_POST['test_mode']) ? '1' : '0',
            'default_currency' => sanitizeInput($_POST['default_currency'] ?? 'USD'),
            'supported_networks' => sanitizeInput($_POST['supported_networks'] ?? 'BTC,ETH,TRON,LTC')
        ];
        
        foreach ($settings as $key => $value) {
            $isEncrypted = in_array($key, ['cryptomus_merchant_uuid', 'cryptomus_payment_key', 'cryptomus_payout_key', 'cryptomus_webhook_secret']);
            $paymentManager->updateSetting($key, $value, $isEncrypted);
        }
        
        $success = 'Ayarlar başarıyla güncellendi!';
        
    } catch (Exception $e) {
        $error = 'Hata: ' . $e->getMessage();
    }
}

// Mevcut ayarları al
try {
    $stmt = $pdo->prepare("SELECT setting_key, setting_value, is_encrypted FROM crypto_settings");
    $stmt->execute();
    $rows = $stmt->fetchAll();
    
    $currentSettings = [];
    foreach ($rows as $row) {
        $currentSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    $error = 'Ayarlar yüklenemedi: ' . $e->getMessage();
    $currentSettings = [];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cryptomus API Ayarları</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .content {
            padding: 2rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        input[type="text"], input[type="password"], textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        input[type="checkbox"] {
            width: auto;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .test-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-cog"></i> Cryptomus API Ayarları</h1>
            <p>Kripto para ödeme sistemi yapılandırması</p>
        </div>
        
        <div class="content">
            <div class="warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Uyarı:</strong> Bu sayfa sadece geliştirme amaçlıdır. Gerçek uygulamada bu ayarlar güvenli bir admin paneline taşınmalıdır!
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="merchant_uuid">
                        <i class="fas fa-key"></i> Merchant UUID
                    </label>
                    <input type="text" id="merchant_uuid" name="merchant_uuid" 
                           value="<?= htmlspecialchars($currentSettings['cryptomus_merchant_uuid'] ?? '') ?>"
                           placeholder="Cryptomus merchant UUID'nizi girin">
                </div>
                
                <div class="form-group">
                    <label for="payment_key">
                        <i class="fas fa-lock"></i> Payment API Key
                    </label>
                    <textarea id="payment_key" name="payment_key" rows="3" 
                              placeholder="Cryptomus payment API key'inizi girin"><?= htmlspecialchars($currentSettings['cryptomus_payment_key'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="payout_key">
                        <i class="fas fa-money-bill-wave"></i> Payout API Key (Opsiyonel)
                    </label>
                    <textarea id="payout_key" name="payout_key" rows="3" 
                              placeholder="Cryptomus payout API key'inizi girin"><?= htmlspecialchars($currentSettings['cryptomus_payout_key'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="webhook_secret">
                        <i class="fas fa-shield-alt"></i> Webhook Secret (Opsiyonel)
                    </label>
                    <input type="text" id="webhook_secret" name="webhook_secret" 
                           value="<?= htmlspecialchars($currentSettings['cryptomus_webhook_secret'] ?? '') ?>"
                           placeholder="Webhook doğrulama için secret key">
                </div>
                
                <div class="form-group">
                    <label for="default_currency">
                        <i class="fas fa-dollar-sign"></i> Varsayılan Para Birimi
                    </label>
                    <input type="text" id="default_currency" name="default_currency" 
                           value="<?= htmlspecialchars($currentSettings['default_currency'] ?? 'USD') ?>"
                           placeholder="USD, EUR, TRY vb.">
                </div>
                
                <div class="form-group">
                    <label for="supported_networks">
                        <i class="fas fa-network-wired"></i> Desteklenen Ağlar
                    </label>
                    <input type="text" id="supported_networks" name="supported_networks" 
                           value="<?= htmlspecialchars($currentSettings['supported_networks'] ?? 'BTC,ETH,TRON,LTC') ?>"
                           placeholder="BTC,ETH,TRON,LTC (virgülle ayırın)">
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="enabled" name="enabled" 
                               <?= ($currentSettings['cryptomus_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <label for="enabled">Cryptomus ödemelerini aktif et</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="test_mode" name="test_mode" 
                               <?= ($currentSettings['cryptomus_test_mode'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <label for="test_mode">Test modu (geliştirme için)</label>
                    </div>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Ayarları Kaydet
                </button>
            </form>
            
            <div class="test-section">
                <h3><i class="fas fa-flask"></i> Test Bilgileri</h3>
                <p><strong>Webhook URL:</strong> <?= SITE_URL ?>cryptomus_webhook.php</p>
                <p><strong>Return URL:</strong> <?= SITE_URL ?>payment_return.php</p>
                <p><strong>Checkout Sayfası:</strong> <a href="checkout.php" target="_blank"><?= SITE_URL ?>checkout.php</a></p>
                
                <br>
                <p><strong>Not:</strong> API bilgilerini Cryptomus dashboard'unuzdan alabilirsiniz. Test modunda gerçek ödeme yapılmaz.</p>
            </div>
        </div>
    </div>
</body>
</html>

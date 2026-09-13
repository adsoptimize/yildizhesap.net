<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';
require_once 'IPBanManager.php';
require_once 'SiteSettings.php';

// IP adresini al
$ipAddress = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

// Eğer virgülle ayrılmış IP'ler varsa ilkini al
if (strpos($ipAddress, ',') !== false) {
    $ipAddress = trim(explode(',', $ipAddress)[0]);
}

$ipBanManager = new IPBanManager($pdo);
$siteSettings = SiteSettings::getInstance();

// Ban bilgisini al
$banInfo = $ipBanManager->getBanInfo($ipAddress);

// UTF-8 encoding garantisi için ban bilgilerini dönüştür
if ($banInfo) {
    $banInfo['reason'] = mb_convert_encoding($banInfo['reason'], 'UTF-8', 'auto');
    if (isset($banInfo['banned_by_username'])) {
        $banInfo['banned_by_username'] = mb_convert_encoding($banInfo['banned_by_username'], 'UTF-8', 'auto');
    }
}

// Eğer ban yoksa ana sayfaya yönlendir
if (!$banInfo) {
    header('Location: index.php');
    exit;
}

// Site ayarlarını al
$banPageTitle = $siteSettings->get('ban_page_title', 'Erişim Engellendi');
$banPageMessage = $siteSettings->get('ban_page_message', 'IP adresiniz sistem yöneticisi tarafından engellenmiştir.');
$banPageContact = $siteSettings->get('ban_page_contact', 'Destek için info@example.com adresine yazabilirsiniz.');

// UTF-8 encoding garantisi için
$banPageTitle = mb_convert_encoding($banPageTitle, 'UTF-8', 'auto');
$banPageMessage = mb_convert_encoding($banPageMessage, 'UTF-8', 'auto');
$banPageContact = mb_convert_encoding($banPageContact, 'UTF-8', 'auto');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($banPageTitle) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --danger: #ef4444;
            --dark: #1e293b;
            --light: #f8fafc;
            --border: #e2e8f0;
            --shadow: rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .ban-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 500px;
            width: 90%;
            margin: 2rem;
        }

        .ban-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--danger), #dc2626);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 2rem;
            color: white;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .ban-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .ban-message {
            font-size: 1.1rem;
            color: #64748b;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .ban-details {
            background: rgba(239, 68, 68, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }

        .ban-details h4 {
            color: var(--danger);
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .ban-detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(239, 68, 68, 0.1);
        }

        .ban-detail-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .ban-detail-label {
            font-weight: 600;
            color: var(--dark);
        }

        .ban-detail-value {
            color: #64748b;
        }

        .countdown {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: white;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 600;
            margin-bottom: 2rem;
        }

        .contact-info {
            font-size: 0.9rem;
            color: #64748b;
            line-height: 1.5;
        }

        .contact-info a {
            color: var(--danger);
            text-decoration: none;
        }

        .contact-info a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .ban-container {
                padding: 2rem;
                margin: 1rem;
            }

            .ban-title {
                font-size: 1.5rem;
            }

            .ban-message {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="ban-container">
        <div class="ban-icon">
            <i class="fas fa-ban"></i>
        </div>
        
        <h1 class="ban-title"><?= htmlspecialchars($banPageTitle) ?></h1>
        
        <p class="ban-message"><?= htmlspecialchars($banPageMessage) ?></p>
        
        <div class="ban-details">
            <h4><i class="fas fa-info-circle"></i> Ban Detayları</h4>
            
            <div class="ban-detail-item">
                <span class="ban-detail-label">IP Adresiniz:</span>
                <span class="ban-detail-value"><?= htmlspecialchars($ipAddress) ?></span>
            </div>
            
            <div class="ban-detail-item">
                <span class="ban-detail-label">Ban Sebebi:</span>
                <span class="ban-detail-value"><?= htmlspecialchars($banInfo['reason']) ?></span>
            </div>
            
            <div class="ban-detail-item">
                <span class="ban-detail-label">Ban Tarihi:</span>
                <span class="ban-detail-value"><?= date('d.m.Y H:i', strtotime($banInfo['created_at'])) ?></span>
            </div>
            
            <div class="ban-detail-item">
                <span class="ban-detail-label">Ban Türü:</span>
                <span class="ban-detail-value">
                    <?= $banInfo['ban_type'] === 'permanent' ? 'Kalıcı' : 'Geçici' ?>
                </span>
            </div>
            
            <?php if ($banInfo['ban_type'] === 'temporary' && $banInfo['banned_until']): ?>
            <div class="ban-detail-item">
                <span class="ban-detail-label">Ban Bitiş Tarihi:</span>
                <span class="ban-detail-value"><?= date('d.m.Y H:i', strtotime($banInfo['banned_until'])) ?></span>
            </div>
            <?php endif; ?>
            
        </div>
        
        <?php if ($banInfo['ban_type'] === 'temporary' && $banInfo['banned_until']): ?>
        <div class="countdown" id="countdown">
            <i class="fas fa-clock"></i>
            <span id="countdown-text">Ban süresi hesaplanıyor...</span>
        </div>
        
        <script>
            function updateCountdown() {
                const banEndTime = new Date('<?= date('c', strtotime($banInfo['banned_until'])) ?>').getTime();
                const now = new Date().getTime();
                const timeLeft = banEndTime - now;
                
                if (timeLeft > 0) {
                    const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                    
                    let countdownText = '';
                    if (days > 0) countdownText += days + ' gün ';
                    if (hours > 0) countdownText += hours + ' saat ';
                    if (minutes > 0) countdownText += minutes + ' dakika ';
                    countdownText += seconds + ' saniye kaldı';
                    
                    document.getElementById('countdown-text').textContent = countdownText;
                } else {
                    document.getElementById('countdown-text').textContent = 'Ban süresi doldu, sayfa yenileniyor...';
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                }
            }
            
            updateCountdown();
            setInterval(updateCountdown, 1000);
        </script>
        <?php endif; ?>
        
        <div class="contact-info">
            <?= nl2br(htmlspecialchars($banPageContact)) ?>
        </div>
    </div>
</body>
</html>

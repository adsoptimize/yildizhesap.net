<?php
$page_title = "YildizHesap | 2FA Kod Üretici";
$secret = $_GET['secret'] ?? '';

// Base32 decode fonksiyonu (global)
function base32_decode($secret) {
    $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $base32charsFlipped = array_flip(str_split($base32chars));
    
    $secret = strtoupper($secret);
    $secret = str_replace([' ', '-', '_'], '', $secret);
    $secret = str_replace('=', '', $secret);
    
    $binaryString = '';
    $secretLength = strlen($secret);
    
    for ($i = 0; $i < $secretLength; $i += 8) {
        $x = '';
        for ($j = 0; $j < 8; $j++) {
            if ($i + $j < $secretLength) {
                $char = $secret[$i + $j];
                if (isset($base32charsFlipped[$char])) {
                    $x .= str_pad(base_convert($base32charsFlipped[$char], 10, 2), 5, '0', STR_PAD_LEFT);
                }
            }
        }
        
        $eightBits = str_split($x, 8);
        foreach ($eightBits as $bit) {
            if (strlen($bit) == 8) {
                $binaryString .= chr(base_convert($bit, 2, 10));
            }
        }
    }
    
    return $binaryString;
}

// AJAX isteği kontrolü (sadece kod üretimi için)
    if (isset($_GET['ajax']) && $_GET['ajax'] === 'true') {
        header('Content-Type: application/json');
        
        if (empty($secret)) {
            echo json_encode(['error' => 'Secret parameter required']);
            exit;
        }
        
        // Debug bilgisi ekle
        $debug = isset($_GET['debug']) && $_GET['debug'] === 'true';
        
        // Gerçek TOTP kodu üret
        $currentCode = generateTOTP($secret);
        $nextCode = generateTOTP($secret, 30); // 30 saniye sonrası için
        
        $response = [
            'current_code' => $currentCode,
            'next_code' => $nextCode,
            'time_left' => 30 - (time() % 30)
        ];
        
        if ($debug) {
            $response['debug'] = [
                'secret' => $secret,
                'timestamp' => time(),
                'time_window' => floor(time() / 30),
                'binary_secret_length' => strlen(base32_decode($secret)),
                'current_code_raw' => generateTOTP($secret),
                'next_code_raw' => generateTOTP($secret, 30)
            ];
        }
        
        echo json_encode($response);
        exit;
    }

// Gerçek TOTP kodu üret fonksiyonu
function generateTOTP($secret, $timeOffset = 0) {
    // Secret'ı decode et
    $binarySecret = base32_decode($secret);
    
    // Zaman penceresi hesapla
    $time = floor((time() + $timeOffset) / 30);
    
    // HMAC-SHA1 hesapla
    $time = pack('N*', 0) . pack('N*', $time);
    $hash = hash_hmac('sha1', $time, $binarySecret, true);
    
    // Offset hesapla
    $offset = ord($hash[19]) & 0xf;
    
    // 4 byte'lık kodu al
    $code = (
        ((ord($hash[$offset]) & 0x7f) << 24) |
        ((ord($hash[$offset + 1]) & 0xff) << 16) |
        ((ord($hash[$offset + 2]) & 0xff) << 8) |
        (ord($hash[$offset + 3]) & 0xff)
    ) % 1000000;
    
    return str_pad($code, 6, '0', STR_PAD_LEFT);
}

// QR kod URL'i oluştur
function generateQRUrl($secret, $label = 'YILDIZ HESAP', $issuer = 'YILDIZ HESAP') {
    $url = 'otpauth://totp/' . urlencode($issuer) . ':' . urlencode($label) . '?secret=' . urlencode($secret) . '&issuer=' . urlencode($issuer) . '&algorithm=SHA1&digits=6&period=30';
    
    // Alternatif QR kod API'leri
    $qrApis = [
        'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' . urlencode($url),
        'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($url),
        'https://qr.tec-it.com/API/QRCode?data=' . urlencode($url) . '&size=200'
    ];
    
    return $qrApis[0]; // İlk API'yi döndür
}

// Secret yoksa form göster
if (empty($secret)) {
    ?>
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $page_title; ?></title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            :root {
                --primary: #6c63ff;
                --accent: #ff6b6b;
                --dark: #1a1a1a;
                --light: #ffffff;
                --gray: #a0a0a0;
                --card-bg: #2d2d2d;
                --card-border: #404040;
                --success: #4caf50;
                --transition: all 0.3s ease;
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
                background: linear-gradient(135deg, var(--dark) 0%, #2d2d2d 100%);
                color: var(--light);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }

            .container {
                background: var(--card-bg);
                border-radius: 20px;
                padding: 40px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                border: 1px solid var(--card-border);
                max-width: 500px;
                width: 100%;
                text-align: center;
            }

            .header {
                margin-bottom: 30px;
            }

            .header h1 {
                font-size: 2rem;
                font-weight: 700;
                background: linear-gradient(135deg, var(--primary), var(--accent));
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
                margin-bottom: 10px;
            }

            .header p {
                color: var(--gray);
                font-size: 1rem;
            }

            .form-group {
                margin-bottom: 20px;
                text-align: left;
            }

            .form-label {
                display: block;
                margin-bottom: 8px;
                color: var(--light);
                font-weight: 600;
            }

            .form-input {
                width: 100%;
                padding: 12px 16px;
                border: 1px solid var(--card-border);
                border-radius: 10px;
                background: rgba(255, 255, 255, 0.1);
                color: var(--light);
                font-size: 1rem;
                transition: var(--transition);
            }

            .form-input:focus {
                outline: none;
                border-color: var(--primary);
                box-shadow: 0 0 0 2px rgba(108, 99, 255, 0.2);
            }

            .submit-btn {
                background: linear-gradient(45deg, var(--primary), var(--accent));
                color: white;
                border: none;
                padding: 12px 24px;
                border-radius: 10px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: var(--transition);
                width: 100%;
            }

            .submit-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(108, 99, 255, 0.3);
            }

            .example-section {
                margin-top: 30px;
                padding: 20px;
                background: rgba(255, 255, 255, 0.05);
                border-radius: 12px;
                border: 1px solid rgba(255, 255, 255, 0.1);
            }

            .example-title {
                font-size: 1.1rem;
                font-weight: 600;
                margin-bottom: 15px;
                color: var(--light);
            }

            .example-url {
                font-family: 'Courier New', monospace;
                font-size: 0.9rem;
                color: var(--gray);
                word-break: break-all;
                background: rgba(0, 0, 0, 0.3);
                padding: 10px;
                border-radius: 8px;
                margin-bottom: 10px;
            }

            .example-btn {
                background: rgba(108, 99, 255, 0.2);
                color: var(--primary);
                border: 1px solid var(--primary);
                padding: 8px 16px;
                border-radius: 6px;
                font-size: 0.9rem;
                cursor: pointer;
                transition: var(--transition);
                text-decoration: none;
                display: inline-block;
            }

            .example-btn:hover {
                background: var(--primary);
                color: white;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1><i class="fas fa-key"></i> YildizHesap | 2FA OTP Generator</h1>
                <p>2 adımlı doğrulama aktif hesaplarınızda; Secret key ile 2FA otp kodlarınızı üretin</p>
            </div>

            <form method="GET" action="">
                <div class="form-group">
                    <label class="form-label">Secret Key</label>
                    <input type="text" name="secret" class="form-input" placeholder="Örnek: JBSWY3DPEHPK3PXP" required>
                </div>
                <button type="submit" class="submit-btn">
                    <i class="fas fa-cog"></i> Kodları Üret
                </button>
            </form>

            <div class="example-section">
                <div class="example-title">Örnek Kullanım</div>
                <div class="example-url">https://yildizhesap.net/2fa.php?secret=JBSWY3DPEHPK3PXP</div>
                <a href="?secret=JBSWY3DPEHPK3PXP" class="example-btn">
                    <i class="fas fa-play"></i> Örnek Secret ile Dene
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Gerçek TOTP kodlarını üret
$currentCode = generateTOTP($secret);
$nextCode = generateTOTP($secret, 30);
$qrUrl = generateQRUrl($secret);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #6c63ff;
            --accent: #ff6b6b;
            --dark: #1a1a1a;
            --light: #ffffff;
            --gray: #a0a0a0;
            --card-bg: #2d2d2d;
            --card-border: #404040;
            --success: #4caf50;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, var(--dark) 0%, #2d2d2d 100%);
            color: var(--light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--card-border);
            max-width: 600px;
            width: 100%;
            text-align: center;
        }

        .header {
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .header p {
            color: var(--gray);
            font-size: 1rem;
        }

        .secret-info {
            background: rgba(108, 99, 255, 0.1);
            border: 1px solid rgba(108, 99, 255, 0.2);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .secret-label {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .secret-value {
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            color: var(--light);
            background: rgba(255, 255, 255, 0.1);
            padding: 10px;
            border-radius: 8px;
            word-break: break-all;
            cursor: pointer;
            transition: var(--transition);
        }

        .secret-value:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .qr-section {
            margin-bottom: 30px;
        }

        .qr-code {
            background: white;
            border-radius: 12px;
            padding: 15px;
            display: inline-block;
            margin-bottom: 15px;
        }

        .qr-code img {
            width: 150px;
            height: 150px;
        }

        .qr-info {
            font-size: 0.9rem;
            color: var(--gray);
        }

        .codes-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .code-card {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 16px;
            padding: 25px;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .code-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: rotate(45deg);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            50% { transform: translateX(0) translateY(0) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }

        .code-label {
            font-size: 0.8rem;
            opacity: 0.8;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .code-value {
            font-family: 'Courier New', monospace;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
        }

        .code-timer {
            font-size: 0.8rem;
            opacity: 0.8;
            position: relative;
            z-index: 2;
        }

        .timer-bar {
            width: 100%;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            overflow: hidden;
            margin-top: 10px;
        }

        .timer-progress {
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 2px;
            transition: width 1s linear;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--success), #45a049);
            color: white;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: var(--light);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 10000;
            animation: slideIn 0.3s ease;
        }

        .notification.success {
            background: #4caf50;
        }

        .notification.info {
            background: #2196f3;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
            }
            
            .codes-section {
                grid-template-columns: 1fr;
            }
            
            .code-value {
                font-size: 1.5rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-key"></i> YildizHesap | 2FA Otp Code Generator</h1>
            <p>2FA secreti girin ve Gerçek zamanlı TOTP kodları üretin.</p>
        </div>

        <div class="secret-info">
            <div class="secret-label">Secret Key</div>
            <div class="secret-value" onclick="copyToClipboard(this.textContent)">
                <?php echo htmlspecialchars($secret); ?>
            </div>
            <button class="btn btn-secondary" onclick="copyToClipboard('<?php echo htmlspecialchars($secret); ?>')">
                <i class="fas fa-copy"></i> Kopyala
            </button>
        </div>

        <div class="qr-section">
            <div class="qr-code">
                <img src="<?php echo $qrUrl; ?>" alt="QR Code" onerror="this.style.display='none'; document.getElementById('qr-fallback').style.display='block';">
                <div id="qr-fallback" style="display: none; padding: 20px; background: #f5f5f5; border-radius: 8px; color: #333;">
                    <i class="fas fa-exclamation-triangle"></i> QR kod yüklenemedi<br>
                    <small>Manuel olarak secret key'i 2FA uygulamanıza ekleyin</small>
                </div>
            </div>
            <div class="qr-info">
                <i class="fas fa-qrcode"></i> QR kodu telefonunuza tarayarak 2FA uygulamanıza ekleyin
            </div>
        </div>

        <div class="codes-section">
            <div class="code-card">
                <div class="code-label">Şu Anki Kod</div>
                <div class="code-value" id="current-code"><?php echo $currentCode; ?></div>
                <div class="code-timer">
                    <span id="current-timer">30</span> saniye geçerli
                </div>
                <div class="timer-bar">
                    <div class="timer-progress" id="current-progress" style="width: 100%;"></div>
                </div>
            </div>

            <div class="code-card">
                <div class="code-label">Sonraki Kod</div>
                <div class="code-value" id="next-code"><?php echo $nextCode; ?></div>
                <div class="code-timer">
                    <span id="next-timer">30</span> saniye sonra
                </div>
                <div class="timer-bar">
                    <div class="timer-progress" id="next-progress" style="width: 0%;"></div>
                </div>
            </div>
        </div>

        <div class="action-buttons">
            <button class="btn btn-primary" onclick="copyCurrentCode()">
                <i class="fas fa-copy"></i> Şu Anki Kodu Kopyala
            </button>
            <button class="btn btn-secondary" onclick="refreshCodes()">
                <i class="fas fa-sync-alt"></i> Yenile
            </button>
            <a href="/2fa.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Yeni Secret
            </a>
        </div>
    </div>

    <script>
        const secret = '<?php echo addslashes($secret); ?>';
        let currentCode = '<?php echo $currentCode; ?>';
        let nextCode = '<?php echo $nextCode; ?>';
        let timeLeft = 30 - (Math.floor(Date.now() / 1000) % 30);

        // Timer güncelleme
        function updateTimer() {
            const now = Math.floor(Date.now() / 1000);
            timeLeft = 30 - (now % 30);
            
            // Progress bar güncelleme
            const progress = (timeLeft / 30) * 100;
            document.getElementById('current-progress').style.width = progress + '%';
            document.getElementById('next-progress').style.width = (100 - progress) + '%';
            
            // Timer metinleri
            document.getElementById('current-timer').textContent = timeLeft;
            document.getElementById('next-timer').textContent = timeLeft;
            
            // 30 saniye dolduğunda kodları yenile
            if (timeLeft === 30) {
                refreshCodes();
            }
        }

        // Kodları yenile
        function refreshCodes() {
            fetch(`?ajax=true&secret=${encodeURIComponent(secret)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        showNotification(data.error, 'error');
                        return;
                    }
                    
                    currentCode = data.current_code;
                    nextCode = data.next_code;
                    
                    document.getElementById('current-code').textContent = currentCode;
                    document.getElementById('next-code').textContent = nextCode;
                    
                    showNotification('Kodlar yenilendi!', 'success');
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Kodlar yenilenirken hata oluştu', 'error');
                });
        }

        // Şu anki kodu kopyala
        function copyCurrentCode() {
            copyToClipboard(currentCode);
        }

        // Metni kopyala
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                showNotification('Kopyalandı!', 'success');
            }).catch(() => {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showNotification('Kopyalandı!', 'success');
            });
        }

        // Bildirim göster
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // QR kod yükleme hatası durumunda alternatif API'leri dene
        function loadQRCode() {
            const qrImage = document.querySelector('.qr-code img');
            const qrFallback = document.getElementById('qr-fallback');
            
            const otpauthUrl = 'otpauth://totp/<?php echo urlencode('YILDIZ HESAP'); ?>:<?php echo urlencode('YILDIZ HESAP'); ?>?secret=<?php echo urlencode($secret); ?>&issuer=<?php echo urlencode('YILDIZ HESAP'); ?>&algorithm=SHA1&digits=6&period=30';
            
            const qrApis = [
                'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' + encodeURIComponent(otpauthUrl),
                'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(otpauthUrl),
                'https://qr.tec-it.com/API/QRCode?data=' + encodeURIComponent(otpauthUrl) + '&size=200'
            ];
            
            let currentApiIndex = 0;
            let loadTimeout;
            
            function tryNextApi() {
                clearTimeout(loadTimeout);
                
                if (currentApiIndex < qrApis.length) {
                    qrImage.src = qrApis[currentApiIndex];
                    currentApiIndex++;
                    
                    // 5 saniye timeout
                    loadTimeout = setTimeout(() => {
                        tryNextApi();
                    }, 5000);
                } else {
                    // Tüm API'ler başarısız oldu
                    qrImage.style.display = 'none';
                    qrFallback.style.display = 'block';
                }
            }
            
            qrImage.onerror = tryNextApi;
            qrImage.onload = function() {
                clearTimeout(loadTimeout);
                qrFallback.style.display = 'none';
                qrImage.style.display = 'block';
            };
            
            // İlk API'yi dene
            tryNextApi();
        }

        // Timer başlat
        setInterval(updateTimer, 1000);
        updateTimer(); // İlk çalıştırma
        
        // QR kod yükleme sistemini başlat
        loadQRCode();
    </script>
</body>
</html>

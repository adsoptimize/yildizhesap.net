<?php
header("Content-Security-Policy: script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://www.google-analytics.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://cdn.ckeditor.com;");

// Kullanıcı oturum kontrolü
require_once(dirname(__FILE__) . '/config.php');
require_once(dirname(__FILE__) . '/Auth.php');
require_once(dirname(__FILE__) . '/IPBanManager.php');
require_once(dirname(__FILE__) . '/includes/PopupAd.php');
require_once(dirname(__FILE__) . '/DatabaseSessionManager.php');

// IP ban kontrolü
$ipAddress = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
if (strpos($ipAddress, ',') !== false) {
    $ipAddress = trim(explode(',', $ipAddress)[0]);
}

$ipBanManager = new IPBanManager($pdo);
$banInfo = $ipBanManager->isIPBanned($ipAddress);

if ($banInfo) {
    header('Location: banned.php');
    exit;
}

// Cryptomus'tan gelen istekleri kontrol et
$isFromCryptomus = false;
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$currentPage = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($referer, 'cryptomus') !== false || strpos($referer, 'cryptomus.com') !== false || 
    strpos($currentPage, 'payment-cancel.php') !== false || strpos($currentPage, 'payment-success.php') !== false) {
    $isFromCryptomus = true;
}

// Database Session Manager kullan (SIFIR TOLERANS güvenlik)
$sessionManager = new DatabaseSessionManager($pdo);
$currentUser = null;
$isLoggedIn = false;

if ($isFromCryptomus) {
    // Cryptomus'tan geliyorsa veya ödeme sayfalarındaysa, session kontrolünü atla
    // Ama kullanıcı bilgisini order_id'den almaya çalış
    $order_id = $_GET['order_id'] ?? '';
    if ($order_id) {
        $stmt = $pdo->prepare("SELECT user_id, is_guest_order, email, customer_name, phone FROM crypto_payments WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $payment = $stmt->fetch();
        
        if ($payment) {
            if ($payment['is_guest_order']) {
                // Ziyaretçi ödemesi
                $currentUser = [
                    'id' => null,
                    'username' => 'Ziyaretçi',
                    'email' => $payment['email'],
                    'first_name' => $payment['customer_name'],
                    'last_name' => '',
                    'phone' => $payment['phone']
                ];
                $isLoggedIn = true;
            } else {
                // Kayıtlı kullanıcı ödemesi
                $stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name FROM users WHERE id = ?");
                $stmt->execute([$payment['user_id']]);
                $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
                $isLoggedIn = true;
            }
        }
    }
} else {
    // Normal session kontrolü
    $sessionToken = $_COOKIE['session_token'] ?? null;
    if ($sessionToken) {
        $sessionResult = $sessionManager->validateSession($sessionToken);
        if ($sessionResult['valid']) {
            $currentUser = $sessionResult['user'];
            $isLoggedIn = true;
        } else {
            // Geçersiz session - cookie'yi temizle
            setcookie('session_token', '', time() - 1, '/', '', false, true);
            
            // Şüpheli aktivite logla
            error_log("SECURITY: Invalid session token attempted from IP: $ipAddress. Reason: {$sessionResult['reason']}");
            
            // Eğer güvenlik ihlali varsa (IP/UA değişimi) uyarı sayfası göster
            if (in_array($sessionResult['reason'], ['IP address changed', 'Device fingerprint changed'])) {
                http_response_code(403);
                die(getSecurityViolationPage());
            }
        }
    }
}

// Güvenlik ihlali sayfası fonksiyonu
function getSecurityViolationPage() {
    return '<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Güvenlik İhlali Tespit Edildi</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 50px auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); text-align: center; }
        .error-icon { font-size: 64px; color: #dc3545; margin-bottom: 20px; }
        h1 { color: #dc3545; margin-bottom: 20px; }
        p { color: #6c757d; line-height: 1.6; margin-bottom: 15px; }
        .alert { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #dc3545; }
        .btn { display: inline-block; padding: 12px 24px; background: #6c63ff; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .btn:hover { background: #5a52d5; }
        .security-info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin-top: 20px; font-size: 14px; color: #1565c0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-icon">🛡️</div>
        <h1>Güvenlik İhlali Tespit Edildi</h1>
        
        <div class="alert">
            <strong>⚠️ CRİTİK GÜVENLİK UYARISI</strong><br>
            Oturumunuz başka bir cihaz, tarayıcı veya IP adresinden erişilmeye çalışıldığı tespit edilmiştir.
        </div>
        
        <p><strong>Tespit edilen değişiklikler:</strong></p>
        <ul style="text-align: left; color: #495057;">
            <li>🌐 IP adresi değişimi (VPN/Proxy değişimi)</li>
            <li>🖥️ Cihaz parmak izi değişimi</li>
            <li>🌏 Tarayıcı özelliklerinde farklılık</li>
            <li>⚠️ Şüpheli session manipülasyonu</li>
        </ul>
        
        <p>Güvenliğiniz için oturumunuz <strong>derhal sonlandırılmıştır</strong>.</p>
        
        <div class="security-info">
            <strong>🔒 Bu güvenlik önlemi şunları korur:</strong><br>
            • Session hijacking (oturum çalma) saldırıları<br>
            • Çerez hırsızlığı<br>
            • Yetkisiz hesap erişimi<br>
            • VPN/Proxy üzerinden saldırılar<br>
            • Kimlik avı saldırıları
        </div>
        
        <a href="/login.php" class="btn">🔐 Güvenli Giriş Yap</a>
        
        <p style="margin-top: 30px; font-size: 12px; color: #999;">
            Bu olay güvenlik günlüğüne kaydedilmiştir.<br>
            VPN kullanıyorsanız, güvenlik için tekrar giriş yapmanız gerekir.
        </p>
    </div>
</body>
</html>';
}

// Popup reklam kontrolü
$popupAd = new PopupAd($pdo, $siteSettings);
$showPopup = $popupAd->shouldShowPopup($currentUser['id'] ?? null);
$popupContent = $showPopup ? $popupAd->getPopupContent() : '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="kt@kaantanis.com" />

<!-- Dynamic Meta Tags -->
<?php
// Default meta tags if not set by the page
if (!isset($meta_title)) {
    $meta_title = "Facebook Reklam Hesabı Satın Al | Business Manager & Reklam Hesapları";
}
if (!isset($meta_description)) {
    $meta_description = "Facebook reklam hesabı satın al, Business Manager hesap, doğrulanmış reklam hesapları ve kimlik onaylı Facebook hesapları en uygun fiyatlarla!";
}
if (!isset($meta_keywords)) {
    $meta_keywords = "facebook reklam hesabı satın al, business manager hesap, doğrulanmış business manager, facebook reklam hesabı, kimlik onaylı facebook hesabı, instagram hesap satın al, tiktok hesap, twitter hesap, discord hesap, telegram hesap, gmail hesap, outlook hesap, hesap satış platformu";
}
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
?>

<!-- Title -->
<title><?php echo htmlspecialchars($meta_title); ?></title>

<!-- Description -->
<meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>"/>

<!-- AI-Specific Meta Tags -->
<?php if (isset($meta_ai_intent)): ?>
<meta name="ai-intent" content="<?php echo htmlspecialchars($meta_ai_intent); ?>"/>
<?php endif; ?>

<?php if (isset($meta_ai_intent_2)): ?>
<meta name="ai-intent" content="<?php echo htmlspecialchars($meta_ai_intent_2); ?>"/>
<?php endif; ?>

<?php if (isset($meta_ai_subtopics)): ?>
<meta name="ai-subtopics" content="<?php echo htmlspecialchars($meta_ai_subtopics); ?>"/>
<?php endif; ?>

<?php if (isset($meta_audience)): ?>
<meta name="audience" content="<?php echo htmlspecialchars($meta_audience); ?>"/>
<?php endif; ?>

<?php if (isset($meta_category)): ?>
<meta name="category" content="<?php echo htmlspecialchars($meta_category); ?>"/>
<?php endif; ?>

<?php if (isset($meta_content_tone)): ?>
<meta name="content-tone" content="<?php echo htmlspecialchars($meta_content_tone); ?>"/>
<?php endif; ?>

<?php if (isset($meta_reader_interest)): ?>
<meta name="reader-interest" content="<?php echo htmlspecialchars($meta_reader_interest); ?>"/>
<?php endif; ?>

<?php if (isset($meta_summary)): ?>
<meta name="summary" content="<?php echo htmlspecialchars($meta_summary); ?>"/>
<?php endif; ?>

<?php if (isset($meta_topic_tags)): ?>
<meta name="topic-tags" content="<?php echo htmlspecialchars($meta_topic_tags); ?>"/>
<?php endif; ?>

<?php if (isset($meta_visual_content)): ?>
<meta name="visual-content" content="<?php echo htmlspecialchars($meta_visual_content); ?>"/>
<?php endif; ?>

<!-- Keywords -->
<meta name="keywords" content="<?php echo htmlspecialchars($meta_keywords); ?>">

<!-- DNS Prefetch -->
<meta http-equiv='x-dns-prefetch-control' content='on'>
<link rel='dns-prefetch' href='//cdnjs.cloudflare.com' />
<link rel='dns-prefetch' href='//ajax.googleapis.com' />
<link rel='dns-prefetch' href='//fonts.googleapis.com' />
<link rel='dns-prefetch' href='//fonts.gstatic.com' />
<link rel='dns-prefetch' href='//s.gravatar.com' />
<link rel='dns-prefetch' href='//www.google-analytics.com' />

<!-- Compatibility -->
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<!-- Open Graph Meta Tags -->
<meta property="og:title" content="<?php echo htmlspecialchars($meta_title); ?>">
<meta property="og:url" content="<?php echo htmlspecialchars($current_url); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($meta_description); ?>">
<meta property="og:image" content="https://www.yildizhesap.net/storage/site_logo/logo.png?ver=1">
<meta property="og:type" content="website">
<meta property="og:site_name" content="yildizhesap.net - Hesap Satış Platformu">
<meta property="og:locale" content="tr_TR">

<!-- Twitter Card Meta Tags -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:domain" content="yildizhesap.net">
<meta name="twitter:title" content="<?php echo htmlspecialchars($meta_title); ?>">
<meta name="twitter:url" content="<?php echo htmlspecialchars($current_url); ?>">
<meta name="twitter:description" content="<?php echo htmlspecialchars($meta_description); ?>">
<meta name="twitter:image" content="https://www.yildizhesap.net/storage/uploads/favicon.png">

<!-- Schema.org Meta Tags -->
<meta itemprop="name" content="<?php echo htmlspecialchars($meta_title); ?>">
<meta itemprop="url" content="<?php echo htmlspecialchars($current_url); ?>">
<meta itemprop="description" content="<?php echo htmlspecialchars($meta_description); ?>">
<meta itemprop="image" content="https://www.yildizhesap.net/storage/uploads/favicon.png">
 
    <base href="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/'; ?>">
    <?php if (isset($csrfToken)): ?>
    <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
    <?php endif; ?>
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php safe_attr(SessionSecurity::generateCSRFToken()); ?>">
    
    <!-- CSP Nonce -->
    <?php $cspNonce = XSSProtection::generateCSPNonce(); ?>
    <meta name="csp-nonce" content="<?php safe_attr($cspNonce); ?>">
    
    <!-- Favicon and App Icons -->
    <link rel="icon" type="image/x-icon" href="<?php safe_url($siteSettings->get('favicon_ico', '/favicon.ico')); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php safe_url($siteSettings->get('favicon_png_16', '/assets/icons/favicon-16x16.png')); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php safe_url($siteSettings->get('favicon_png_32', '/assets/icons/favicon-32x32.png')); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php safe_url($siteSettings->get('apple_touch_icon', '/assets/icons/apple-touch-icon.png')); ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php safe_url($siteSettings->get('android_chrome_192', '/assets/icons/android-chrome-192x192.png')); ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?php safe_url($siteSettings->get('android_chrome_512', '/assets/icons/android-chrome-512x512.png')); ?>">
    <link rel="manifest" href="<?php safe_url($siteSettings->get('manifest_json', '/site.webmanifest')); ?>">
    
    <!-- Theme and Browser UI -->
    <meta name="theme-color" content="<?php safe_attr($siteSettings->get('site_theme_color', '#6c63ff')); ?>">
    <meta name="msapplication-TileColor" content="<?php safe_attr($siteSettings->get('site_theme_color', '#6c63ff')); ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?php safe_attr($siteSettings->get('site_title', 'BusinessHesap')); ?>">
    
    <!-- Preconnect for external resources (early connection) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    
    <!-- Preload critical CSS (local stylesheet) -->
    <link rel="preload" href="style.css?v=mobile-fix-2025" as="style">
    
    <!-- Critical CSS loaded synchronously -->
    <link rel="stylesheet" href="style.css?v=mobile-fix-2025">
    
    <!-- Google Fonts - with font-display: swap to eliminate render delay -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
    
    <!-- Bootstrap - defer non-critical CSS with media=print + onload trick -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"></noscript>
    
    <!-- Font Awesome - defer non-critical icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
    

    
    <?php 
    // AdSense kodu ekle
    $adsenseCode = $siteSettings->get('adsense_code');
    if (!empty($adsenseCode)) {
        echo $adsenseCode;
    }
    ?>
    
    <!-- FontAwesome 7 Pro - Lazy loaded for non-critical styles -->
    <!-- Removed 28 render-blocking FA7 CSS files for PageSpeed optimization -->
    <!-- FA6 from cdnjs already provides all needed icons (fas, fab) -->
    
    <!-- Dynamic Theme Colors -->
    <style>
        :root {
            --primary: <?php echo htmlspecialchars($siteSettings->get('theme_primary_color', '#6c63ff')); ?>;
            --secondary: <?php echo htmlspecialchars($siteSettings->get('theme_secondary_color', '#ff6584')); ?>;
            --accent: <?php echo htmlspecialchars($siteSettings->get('theme_accent_color', '#42e2b8')); ?>;
            --tertiary: <?php echo htmlspecialchars($siteSettings->get('theme_tertiary_color', '#ff416c')); ?>;
            --link-color: <?php echo htmlspecialchars($siteSettings->get('theme_link_color', '#42e2b8')); ?>;
            --theme-color: <?php echo htmlspecialchars($siteSettings->get('site_theme_color', '#6c63ff')); ?>;
            
            /* Türetilmiş renkler */
            --primary-rgb: <?php 
                $primary = $siteSettings->get('theme_primary_color', '#6c63ff');
                $rgb = sscanf($primary, "#%02x%02x%02x");
                echo implode(', ', $rgb);
            ?>;
            --secondary-rgb: <?php 
                $secondary = $siteSettings->get('theme_secondary_color', '#ff6584');
                $rgb = sscanf($secondary, "#%02x%02x%02x");
                echo implode(', ', $rgb);
            ?>;
            --accent-rgb: <?php 
                $accent = $siteSettings->get('theme_accent_color', '#42e2b8');
                $rgb = sscanf($accent, "#%02x%02x%02x");
                echo implode(', ', $rgb);
            ?>;
            --tertiary-rgb: <?php 
                $tertiary = $siteSettings->get('theme_tertiary_color', '#ff416c');
                $rgb = sscanf($tertiary, "#%02x%02x%02x");
                echo implode(', ', $rgb);
            ?>;
            --link-color-rgb: <?php 
                $linkColor = $siteSettings->get('theme_link_color', '#42e2b8');
                $rgb = sscanf($linkColor, "#%02x%02x%02x");
                echo implode(', ', $rgb);
            ?>;
            
            /* Alpha varyasyonları */
            --primary-10: rgba(var(--primary-rgb), 0.1);
            --primary-20: rgba(var(--primary-rgb), 0.2);
            --primary-30: rgba(var(--primary-rgb), 0.3);
            --secondary-10: rgba(var(--secondary-rgb), 0.1);
            --secondary-20: rgba(var(--secondary-rgb), 0.2);
            --secondary-30: rgba(var(--secondary-rgb), 0.3);
            --accent-10: rgba(var(--accent-rgb), 0.1);
            --accent-20: rgba(var(--accent-rgb), 0.2);
            --accent-30: rgba(var(--accent-rgb), 0.3);
            --tertiary-10: rgba(var(--tertiary-rgb), 0.1);
            --tertiary-20: rgba(var(--tertiary-rgb), 0.2);
            --tertiary-30: rgba(var(--tertiary-rgb), 0.3);
            --link-color-10: rgba(var(--link-color-rgb), 0.1);
            --link-color-20: rgba(var(--link-color-rgb), 0.2);
            --link-color-30: rgba(var(--link-color-rgb), 0.3);
            
            /* Gradient'lar */
            --gradient-primary: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            --gradient-secondary: linear-gradient(135deg, var(--secondary) 0%, var(--tertiary) 100%);
            --gradient-tertiary: linear-gradient(135deg, var(--tertiary) 0%, var(--accent) 100%);
            --gradient-bg: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--secondary-rgb), 0.08) 100%);
            
            /* Bootstrap override - prevent deferred BS from resetting colors */
            --bs-body-color: var(--light);
            --bs-heading-color: var(--light);
            --bs-body-bg: var(--darker);
        }
        
        /* Butonlar */
        .btn-primary {
            background: var(--gradient-primary) !important;
            border: none !important;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%) !important;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: var(--gradient-secondary) !important;
            color: white !important;
            border: none !important;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, var(--tertiary) 0%, var(--secondary) 100%) !important;
            transform: translateY(-2px);
        }
        
        /* Logo efektleri - Dinamik tema renkleri */
        .logo-icon {
            background: var(--gradient-primary) !important;
            box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.3) !important;
        }
        
        .logo:hover .logo-icon {
            box-shadow: 0 8px 20px rgba(var(--primary-rgb), 0.4) !important;
        }
        
        /* Logo text dinamik renkler */
        .logo-text .logo-title::before {
            background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.3) 0%, rgba(var(--accent-rgb), 0.25) 100%) !important;
            border-color: rgba(var(--primary-rgb), 0.4) !important;
            box-shadow: 0 2px 8px rgba(var(--primary-rgb), 0.15) !important;
        }
        
        .logo:hover .logo-text .logo-title::before {
            background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.4) 0%, rgba(var(--accent-rgb), 0.35) 100%) !important;
            border-color: rgba(var(--primary-rgb), 0.6) !important;
            box-shadow: 0 4px 16px rgba(var(--primary-rgb), 0.25) !important;
        }
        
        /* Header navigasyon */
        nav ul li a.active,
        nav ul li a:hover {
            color: var(--accent) !important;
        }
        
        /* User dropdown */
        .user-avatar .avatar-circle {
            background: var(--gradient-primary);
        }
        
        /* Kartlar */
        .card-hover:hover {
            box-shadow: 0 8px 32px rgba(var(--primary-rgb), 0.2) !important;
        }
        
        /* Filtreler */
        .filter-select:focus {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 2px var(--primary-20) !important;
        }
        
        /* Footer linkler */
        footer a {
            color: var(--link-color) !important;
            transition: all 0.3s ease;
        }
        
        footer a:hover {
            color: var(--primary) !important;
            text-decoration: underline;
        }
        
        /* Footer bottom linkler (KVKK, Gizlilik) */
        .footer-bottom a {
            color: var(--link-color) !important;
            font-weight: 500;
        }
        
        .footer-bottom a:hover {
            color: var(--primary) !important;
            text-decoration: underline;
        }
        
        /* Genel link stilleri */
        a {
            color: var(--link-color);
            transition: all 0.3s ease;
        }
        
        a:hover {
            color: var(--primary);
        }
        
        /* Social icons */
        .social-icons a {
            background: var(--primary-20) !important;
            border: 1px solid var(--primary-30) !important;
        }
        
        .social-icons a:hover {
            background: var(--gradient-primary) !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.3) !important;
        }
    </style>
    
    <!-- Global Security JavaScript -->
    <script nonce="<?php echo safe_attr($cspNonce); ?>" src="<?php echo safe_url('/js/security.js'); ?>" defer></script>
    
    <!-- Logo Title Fix + Mobile Header Fix -->
    <style>
        /* Logo title span styling (replaces h1) - ensure all h1 styles apply */
        .logo-text .logo-title {
            display: block !important;
            font-family: Montserrat, sans-serif !important;
            text-transform: none !important; /* Override uppercase from .logo-text span */
        }
        
        /* Additional logo fixes */
        .logo {
            overflow: visible;
            max-width: none;
        }
        
        .logo-text {
            overflow: visible;
            min-width: 0;
        }
        
        /* Mobile Header Fixes */
        @media (max-width: 992px) {
            .header-container {
                padding: 10px 0;
                flex-wrap: nowrap;
                overflow-x: visible;
            }
            
            .header-actions {
                gap: 8px;
                flex-shrink: 0;
            }
            
            .header-actions .btn {
                padding: 8px 14px;
                font-size: 13px;
                gap: 6px;
                box-shadow: none;
                white-space: nowrap;
            }
            
            .header-actions .btn i {
                font-size: 14px;
            }
            
            .logo {
                gap: 8px;
                flex-shrink: 1;
                min-width: 0;
            }
            
            .logo-text .logo-title {
                white-space: nowrap;
            }
            
            .order-track-icon-container,
            .cart-icon-container {
                margin-right: 0.3rem;
            }
            
            .order-track-icon,
            .cart-icon {
                width: 36px;
                height: 36px;
                border-radius: 10px;
            }
        }
        
        @media (max-width: 768px) {
            .header-container {
                padding: 8px 0;
            }
            
            .header-actions {
                gap: 6px;
            }
            
            .header-actions .btn {
                padding: 10px;
                font-size: 16px;
                border-radius: 50%;
                width: 42px;
                height: 42px;
                min-width: 42px;
                gap: 0;
            }
            
            /* Hide button text, only show icons on small screens */
            .header-actions .btn .btn-text {
                display: none;
            }
            
            .header-actions .btn i {
                margin: 0;
            }
            
            .logo {
                gap: 6px;
            }
            
            .logo-text .logo-title {
                font-size: 16px !important;
                padding: 5px 10px !important;
            }
            
            .logo-text span {
                font-size: 8px !important;
                padding-left: 10px !important;
            }
            
            .logo-icon.simple {
                width: 38px !important;
                height: 38px !important;
            }
            
            .logo-main-icon {
                font-size: 18px !important;
            }
            
            .mobile-menu-btn {
                width: 40px;
                height: 40px;
                font-size: 22px;
            }
            
            .order-track-icon,
            .cart-icon {
                width: 34px;
                height: 34px;
            }
            
            .order-track-icon i,
            .cart-icon i {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 480px) {
            .header-actions {
                gap: 4px;
            }
            
            .header-actions .btn {
                padding: 8px;
                font-size: 14px;
                width: 38px;
                height: 38px;
                min-width: 38px;
            }
            
            .logo-text .logo-title {
                font-size: 13px !important;
                padding: 4px 8px !important;
            }
            
            .logo-text span {
                display: none;
            }
            
            .logo-icon.simple {
                width: 32px !important;
                height: 32px !important;
            }
            
            .logo-main-icon {
                font-size: 14px !important;
            }
            
            .order-track-icon,
            .cart-icon {
                width: 32px;
                height: 32px;
            }
            
            .mobile-menu-btn {
                width: 36px;
                height: 36px;
                font-size: 20px;
            }
            
            .hero {
                padding: 60px 0 40px;
            }
            
            .hero h1 {
                font-size: 1.5rem !important;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                margin-top: 30px;
            }
            
            .stat-card {
                padding: 15px;
            }
            
            .stat-number {
                font-size: 1.8rem;
            }
            
            .stat-label {
                font-size: 0.85rem;
            }
        }
    </style>
    
    <?php 
    // Yapısal veri (Structured Data) desteği
    if (isset($structuredData) && !empty($structuredData)): 
    ?>
    <script type="application/ld+json">
    <?php echo $structuredData; ?>
    </script>
    <?php endif; ?>
    
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-VWV6X9KDMQ"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-VWV6X9KDMQ');
    </script>

    <meta name="google-site-verification" content="WVj1pVmSDcoMnItIOZAeXRUa_aeFswj72PSNJB-Q0uQ" />
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container header-container">
            <a href="index.php" class="logo">
                <?php 
                $logoType = $siteSettings->get('logo_type', 'text'); // 'text' veya 'image'
                $logoUrl = $siteSettings->get('site_logo', '');
                $logoWidth = $siteSettings->get('logo_width', '50');
                $logoHeight = $siteSettings->get('logo_height', '50');
                $logoIcon = $siteSettings->get('logo_icon', 'fas fa-crown');
                $logoSubtext = $siteSettings->get('logo_subtext', 'PREMIUM HESAP PAZARI');
                
                if ($logoType === 'image' && !empty($logoUrl)): 
                ?>
                    <img src="<?php echo htmlspecialchars($logoUrl); ?>" 
                         alt="<?php echo htmlspecialchars($siteSettings->get('site_title', 'BusinessHesap')); ?>" 
                         class="logo-image" 
                         style="width: <?php echo intval($logoWidth); ?>px; height: <?php echo intval($logoHeight); ?>px;">
                <?php else: ?>
                    <div class="logo-icon simple">
                        <!-- Sadece ana ikon -->
                        <i class="<?php echo htmlspecialchars($logoIcon); ?> logo-main-icon"></i>
                    </div>
                    <div class="logo-text">
                        <span class="logo-title"><?php echo htmlspecialchars($siteSettings->get('site_title', 'BusinessHesap')); ?></span>
                        <span><?php echo htmlspecialchars($logoSubtext); ?></span>
                    </div>
                <?php endif; ?>
            </a>

            <nav>
                <ul>
                    <?php 
                    $headerMenu = $siteSettings->getJsonSetting('header_menu', [
                        ['name' => 'Anasayfa', 'url' => 'index.php', 'icon' => 'fas fa-home'],
                        ['name' => 'Hizmetler', 'url' => 'hizmetler.php', 'icon' => 'fas fa-cogs'],
                        ['name' => 'Hesaplar', 'url' => 'hesaplar.php', 'icon' => 'fas fa-shopping-cart'],
                        ['name' => 'Hesap Satın Al', 'url' => 'guest_purchase.php', 'icon' => 'fas fa-shopping-bag'],
                        ['name' => 'Sipariş Takip', 'url' => 'guest_order_track.php', 'icon' => 'fas fa-search'],
                        ['name' => 'SSS', 'url' => 'sss.php', 'icon' => 'fas fa-question-circle'],
                        ['name' => 'İletişim', 'url' => 'iletisim.php', 'icon' => 'fas fa-phone-alt']
                    ]);
                    
                    foreach ($headerMenu as $menuItem): 
                        $isActive = (basename($_SERVER['PHP_SELF']) == basename($menuItem['url']));
                        $activeClass = $isActive ? 'class="active"' : '';
                        $icon = isset($menuItem['icon']) ? '<i class="' . htmlspecialchars($menuItem['icon']) . '"></i> ' : '';
                    ?>
                        <li><a href="<?php echo htmlspecialchars($menuItem['url']); ?>" <?php echo $activeClass; ?>><?php echo $icon . htmlspecialchars($menuItem['name']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            
            <!-- Sepet Simgesi -->
            <?php if ($isLoggedIn): ?>
                <div class="cart-icon-container">
                    <a href="cart.php" class="cart-icon" id="cartIcon" aria-label="Sepeti görüntüle">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count" id="cartCount">
                            <?php 
                            $cartCount = 0;
                            if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                                $cartCount = array_sum($_SESSION['cart']);
                            }
                            echo $cartCount > 0 ? $cartCount : '0';
                            ?>
                        </span>
                    </a>
                </div>
            <?php endif; ?>
            
            <!-- Ziyaretçi Sipariş Takip Butonu -->
            <?php if (!$isLoggedIn || ($isLoggedIn && (!isset($currentUser['id']) || $currentUser['id'] === null))): ?>
                <div class="order-track-icon-container">
                    <a href="guest_order_track.php" class="order-track-icon" title="Sipariş Takip" aria-label="Sipariş takip sayfasına git">
                        <i class="fas fa-eye"></i>
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="header-actions">
                <?php if ($isLoggedIn): ?>
                    <?php if (isset($currentUser['id']) && $currentUser['id'] !== null): ?>
                        <!-- Kayıtlı Kullanıcı Giriş Yapmış -->
                        <div class="user-menu">
                            <div class="user-avatar" onclick="toggleUserDropdown()">
                                <div class="avatar-circle">
                                    <i class="fas fa-user"></i>
                                </div>
                                <span class="username"><?php echo htmlspecialchars($currentUser['first_name']); ?></span>
                                <i class="fas fa-chevron-down dropdown-arrow"></i>
                            </div>
                            
                            <div class="user-dropdown" id="userDropdown">
                                <div class="dropdown-header">
                                    <div class="user-info">
                                        <div class="avatar-circle">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="user-details">
                                            <span class="name"><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></span>
                                            <span class="email"><?php echo htmlspecialchars($currentUser['email']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="dropdown-menu">
                                    <a href="urunlerim.php" class="dropdown-item">
                                        <i class="fas fa-shopping-bag"></i>
                                        <span>Ürünlerim</span>
                                    </a>
                                    <a href="ayarlar.php" class="dropdown-item">
                                        <i class="fas fa-cog"></i>
                                        <span>Ayarlar</span>
                                    </a>
                                    <?php if (isset($currentUser['is_admin']) && $currentUser['is_admin'] == 1): ?>
                                    <div class="dropdown-divider"></div>
                                    <a href="<?php echo ADMIN_PANEL_PATH; ?>/index.php" class="dropdown-item admin-item">
                                        <i class="fas fa-shield-alt"></i>
                                        <span>Admin Panel</span>
                                    </a>
                                    <?php endif; ?>
                                    <div class="dropdown-divider"></div>
                                    <a href="cikis.php" class="dropdown-item logout">
                                        <i class="fas fa-sign-out-alt"></i>
                                        <span>Çıkış Yap</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Ziyaretçi Kullanıcı -->
                        <div class="guest-user">
                            <div class="guest-avatar">
                                <i class="fas fa-user-clock"></i>
                            </div>
                            <span class="guest-text">Ziyaretçi</span>
                            <a href="login.php" class="btn btn-primary btn-sm">Giriş Yap</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Kullanıcı Giriş Yapmamış -->
                    <a href="login.php" class="btn btn-secondary" aria-label="Giriş yap"><i class="fas fa-sign-in-alt"></i> <span class="btn-text">Giriş Yap</span></a>
                    <a href="register.php" class="btn btn-primary" aria-label="Kayıt ol"><i class="fas fa-user-plus"></i> <span class="btn-text">Kayıt Ol</span></a>
                <?php endif; ?>
                
                <button class="mobile-menu-btn" aria-label="Menüyü aç">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <script>
    // User dropdown toggle
    function toggleUserDropdown() {
        console.log('🔽 Dropdown toggle clicked!');
        const dropdown = document.getElementById('userDropdown');
        const arrow = document.querySelector('.dropdown-arrow');
        
        console.log('📋 Dropdown element:', dropdown);
        console.log('🔽 Arrow element:', arrow);
        
        if (dropdown && dropdown.classList.contains('show')) {
            dropdown.classList.remove('show');
            if (arrow) arrow.style.transform = 'rotate(0deg)';
            console.log('❌ Dropdown closed');
        } else if (dropdown) {
            dropdown.classList.add('show');
            if (arrow) arrow.style.transform = 'rotate(180deg)';
            console.log('✅ Dropdown opened');
        } else {
            console.error('⚠️ Dropdown element not found!');
        }
    }

    // Click outside to close dropdown
    document.addEventListener('click', function(event) {
        const userMenu = document.querySelector('.user-menu');
        const dropdown = document.getElementById('userDropdown');
        const arrow = document.querySelector('.dropdown-arrow');
        
        if (userMenu && !userMenu.contains(event.target)) {
            if (dropdown) {
                dropdown.classList.remove('show');
            }
            if (arrow) {
                arrow.style.transform = 'rotate(0deg)';
            }
        }
    });
    </script>
    
    <style>
    /* Ziyaretçi Kullanıcı Stilleri */
    .guest-user {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 25px;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .guest-avatar {
        width: 32px;
        height: 32px;
        background: rgba(255, 193, 7, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffc107;
    }
    
    .guest-text {
        color: var(--text-primary);
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .btn-sm {
        padding: 0.25rem 0.75rem;
        font-size: 0.8rem;
    }
    
    /* Sepet Simgesi Stilleri */
    .cart-icon-container {
        margin-right: 1rem;
        position: relative;
    }
    
    .cart-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 45px;
        height: 45px;
        background: var(--gradient-primary);
        color: white;
        border-radius: 12px;
        text-decoration: none;
        transition: all 0.3s ease;
        position: relative;
        box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.3);
    }
    
    .cart-icon:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(var(--primary-rgb), 0.4);
        color: white;
        text-decoration: none;
    }
    
    .cart-icon i {
        font-size: 1.2rem;
    }
    
    .cart-count {
        position: absolute;
        top: -8px;
        right: -8px;
        background: var(--tertiary);
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(255, 65, 108, 0.4);
        animation: pulse 2s infinite;
    }
    
    .cart-count:empty,
    .cart-count[data-count="0"] {
        display: none;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    /* Ziyaretçi Sipariş Takip Butonu Stilleri */
    .order-track-icon-container {
        margin-right: 1rem;
        position: relative;
    }
    
    .order-track-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 45px;
        height: 45px;
        background: var(--gradient-secondary);
        color: white;
        border-radius: 12px;
        text-decoration: none;
        transition: all 0.3s ease;
        position: relative;
        box-shadow: 0 4px 12px rgba(var(--secondary-rgb), 0.3);
    }
    
    .order-track-icon:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(var(--secondary-rgb), 0.4);
        color: white;
        text-decoration: none;
    }
    
    .order-track-icon i {
        font-size: 1.2rem;
    }
    
    /* Mobile responsive */
    @media (max-width: 768px) {
        .cart-icon-container {
            margin-right: 0.5rem;
        }
        
        .cart-icon {
            width: 40px;
            height: 40px;
        }
        
        .cart-icon i {
            font-size: 1rem;
        }
        
        .cart-count {
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            top: -6px;
            right: -6px;
        }
        
        .order-track-icon-container {
            margin-right: 0.5rem;
        }
        
        .order-track-icon {
            width: 40px;
            height: 40px;
        }
        
        .order-track-icon i {
            font-size: 1rem;
        }
    }
    </style>
    
    <?php if ($showPopup && !empty($popupContent)): ?>
    <!-- Popup Reklam Modal -->
    <div id="popupAdModal" class="popup-ad-modal">
        <div class="popup-ad-overlay" onclick="closePopupAd()"></div>
        <div class="popup-ad-content">
            <button class="popup-ad-close" onclick="closePopupAd()" aria-label="Reklamı kapat">
                <i class="fas fa-times"></i>
            </button>
            <div class="popup-ad-body">
                <?php echo $popupContent; ?>
            </div>
        </div>
    </div>
    
    <style>
    .popup-ad-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 999999;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: popupFadeIn 0.3s ease-out;
    }
    
    .popup-ad-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(5px);
    }
    
    .popup-ad-content {
        position: relative;
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        border: 1px solid rgba(108, 99, 255, 0.3);
        border-radius: 16px;
        max-width: 90vw;
        max-height: 80vh;
        overflow: auto;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5),
                    0 0 40px rgba(108, 99, 255, 0.1);
        animation: popupSlideUp 0.3s ease-out;
    }
    
    .popup-ad-close {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(255, 255, 255, 0.1);
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        color: #fff;
        font-size: 18px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
        z-index: 1;
    }
    
    .popup-ad-close:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: scale(1.1);
    }
    
    .popup-ad-body {
        padding: 30px;
        color: #fff;
        text-align: center;
    }
    
    .popup-ad-body h1, .popup-ad-body h2, .popup-ad-body h3 {
        color: #42e2b8;
        margin-bottom: 15px;
    }
    
    .popup-ad-body p {
        margin-bottom: 15px;
        line-height: 1.6;
    }
    
    .popup-ad-body a {
        color: #42e2b8;
        text-decoration: none;
        transition: color 0.3s ease;
    }
    
    .popup-ad-body a:hover {
        color: #6c63ff;
    }
    
    .popup-ad-body img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        margin: 15px 0;
    }
    
    @keyframes popupFadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }
    
    @keyframes popupSlideUp {
        from {
            transform: translateY(50px) scale(0.9);
            opacity: 0;
        }
        to {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
    }
    
    @media (max-width: 768px) {
        .popup-ad-content {
            margin: 20px;
            max-width: calc(100vw - 40px);
        }
        
        .popup-ad-body {
            padding: 20px;
        }
    }
    </style>
    
    <script>
    // Popup gösterildiği anda görüntüleme sayısını kaydet
    recordPopupView();
    
    function closePopupAd() {
        const modal = document.getElementById('popupAdModal');
        if (modal) {
            modal.style.animation = 'popupFadeOut 0.3s ease-out';
            setTimeout(() => {
                modal.remove();
            }, 300);
        }
    }
    
    function recordPopupView() {
        fetch('ajax_popup_view.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'record_view'
            })
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Popup görüntüleme kaydedildi');
            }
        })
        .catch(error => {
            console.error('Popup kayıt hatası:', error);
        });
    }
    
    // Auto close after 15 seconds (reduced from 30)
    setTimeout(() => {
        closePopupAd();
    }, 15000);
    
    // Prevent right click on popup content
    document.getElementById('popupAdModal').addEventListener('contextmenu', function(e) {
        e.preventDefault();
    });
    
    // Add fade out animation style
    const style = document.createElement('style');
    style.textContent = `
        @keyframes popupFadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    `;
    document.head.appendChild(style);
    </script>
    <?php endif; ?>

<!-- 
    ACCESSIBILITY NOTE: 
    Pages should wrap their main content in a <main> element for better accessibility.
    Example: <main role="main"> ... page content ... </main>
-->

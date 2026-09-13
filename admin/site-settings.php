<?php
// Admin authentication
require_once 'auth_header.php';

$message = '';
$error = '';

// Site ayarlarını al
function getSiteSettings($category = null) {
    global $pdo;
    if ($category) {
        $stmt = $pdo->prepare("SELECT * FROM site_settings WHERE category = ? ORDER BY order_index");
        $stmt->execute([$category]);
    } else {
        $stmt = $pdo->query("SELECT * FROM site_settings ORDER BY category, order_index");
    }
    return $stmt->fetchAll();
}

// SiteSettings sınıfını kullan
$siteSettingsInstance = SiteSettings::getInstance();

// Ayar güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Geçersiz güvenlik token\'i';
    } else {
        try {
            $updateCount = 0;
            
            foreach ($_POST as $key => $value) {
                if ($key !== 'csrf_token' && !empty($key)) {
                    // JSON formatında olanları kontrol et (sadece gerçek JSON alanları)
                    $jsonFields = ['header_menu', 'footer_social_icons', 'footer_quick_links', 'hero_stats'];
                    if (in_array($key, $jsonFields)) {
                        // JSON doğrulaması
                        if (!empty($value)) {
                            $decoded = json_decode($value, true);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                throw new Exception("$key için geçersiz JSON format");
                            }
                        }
                    }
                    
                    if ($siteSettingsInstance->set($key, $value)) {
                        $updateCount++;
                    }
                }
            }
            
            // Cache'i yenile
            $siteSettingsInstance->refreshCache();
            
            $message = "Site ayarları başarıyla güncellendi! ($updateCount ayar kaydedildi)";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Kategorilere göre ayarları al
$headerSettings = getSiteSettings('header');
$heroSettings = getSiteSettings('hero');
$footerSettings = getSiteSettings('footer');
$generalSettings = getSiteSettings('general');
$themeSettings = getSiteSettings('theme');

// Ayarları SiteSettings instance'dan çek
$currentSettings = $siteSettingsInstance->getAll();

$pageTitle = 'Site Ayarları';
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
            --warning: #facc15;
            --danger: #ef4444;
            --info: #3b82f6;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar (same as dashboard) */
        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            box-shadow: 4px 0 20px var(--shadow);
            transition: all 0.3s ease;
            position: relative;
            z-index: 1000;
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
            margin-bottom: 0.5rem;
        }

        .sidebar-subtitle {
            color: #64748b;
            font-size: 0.875rem;
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

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .main-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1.5rem 2rem;
            border-radius: 20px;
            box-shadow: 0 8px 32px var(--shadow);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .save-btn {
            background: linear-gradient(135deg, var(--success), #16a34a);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(34, 197, 94, 0.3);
        }

        /* Settings Grid */
        .settings-grid {
            display: grid;
            gap: 2rem;
        }

        .settings-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 8px 32px var(--shadow);
            overflow: hidden;
        }

        .section-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }

        .section-icon.header { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .section-icon.hero { background: linear-gradient(135deg, #10b981, #047857); }
        .section-icon.footer { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .section-icon.general { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .section-icon.theme { background: linear-gradient(135deg, #ef4444, #dc2626); }

        .section-body {
            padding: 2rem;
        }

        .form-grid {
            display: grid;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
        }

        .form-input, .form-textarea, .form-select {
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus, .form-textarea:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .json-editor {
            min-height: 150px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .form-help {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }

        .color-input {
            width: 60px;
            height: 60px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .color-group {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .color-info {
            flex: 1;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 2px solid rgba(34, 197, 94, 0.2);
            color: #166534;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid rgba(239, 68, 68, 0.2);
            color: #991b1b;
        }

        /* JSON format button */
        .json-input-container {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .json-format-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: var(--info);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 0.5rem;
            cursor: pointer;
            font-size: 0.75rem;
            z-index: 10;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .json-format-btn:hover {
            background: var(--primary);
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        .json-input-container .json-editor {
            padding-right: 3rem !important;
        }
        
        /* Theme Presets Styles */
        .theme-presets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .theme-preset {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .theme-preset:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
            border-color: var(--primary);
        }
        
        .theme-preset.active {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(var(--primary), 0.1) 0%, rgba(var(--secondary), 0.1) 100%);
            box-shadow: 0 8px 32px rgba(var(--primary), 0.2);
        }
        
        .preset-colors {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1rem;
            justify-content: center;
        }
        
        .color-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .theme-preset:hover .color-circle {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .preset-info {
            text-align: center;
        }
        
        .preset-info h4 {
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .preset-info p {
            color: #6b7280;
            font-size: 0.875rem;
            margin: 0;
        }
        
        .section-description {
            color: #6b7280;
            font-size: 0.95rem;
            margin-top: 0.5rem;
            font-style: italic;
        }
        
        /* Apply button for presets */
        .preset-apply-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: var(--success);
            color: white;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            transform: scale(0);
            transition: all 0.3s ease;
        }
        
        .theme-preset:hover .preset-apply-btn {
            opacity: 1;
            transform: scale(1);
        }
        
        .preset-apply-btn:hover {
            background: var(--primary);
            transform: scale(1.1);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .main-content {
                padding: 1rem;
            }
            
            .main-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .theme-presets-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="main-header">
                <h1 class="page-title">Site Ayarları</h1>
                <button type="submit" form="settingsForm" class="save-btn">
                    <i class="fas fa-save"></i>
                    Değişiklikleri Kaydet
                </button>
            </div>

            <!-- Alert Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Settings Form -->
            <form id="settingsForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="settings-grid">
                    <!-- Header Settings -->
                    <div class="settings-section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <div class="section-icon header">
                                    <i class="fas fa-window-maximize"></i>
                                </div>
                                Header Ayarları
                            </h2>
                        </div>
                        <div class="section-body">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Site Başlığı</label>
                                    <input type="text" name="site_title" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['site_title'] ?? 'BusinessHesap') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Logo Tipi</label>
                                    <select name="logo_type" class="form-select">
                                        <option value="text" <?= ($currentSettings['logo_type'] ?? 'text') === 'text' ? 'selected' : '' ?>>Metin + İkon</option>
                                        <option value="image" <?= ($currentSettings['logo_type'] ?? 'text') === 'image' ? 'selected' : '' ?>>Sadece Resim</option>
                                    </select>
                                    <div class="form-help">Logo görünüm tipini seçin</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Logo Resmi (URL)</label>
                                    <input type="text" name="site_logo" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['site_logo'] ?? '') ?>">
                                    <div class="form-help">Logo resmi seçtiyseniz görsel URL'sini girin</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Logo Genişliği (px)</label>
                                    <input type="number" name="logo_width" class="form-input" min="20" max="200"
                                           value="<?= htmlspecialchars($currentSettings['logo_width'] ?? '50') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Logo Yüksekliği (px)</label>
                                    <input type="number" name="logo_height" class="form-input" min="20" max="200"
                                           value="<?= htmlspecialchars($currentSettings['logo_height'] ?? '50') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Logo İkonu (FontAwesome)</label>
                                    <input type="text" name="logo_icon" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['logo_icon'] ?? 'fas fa-crown') ?>">
                                    <div class="form-help">Örnek: fas fa-crown, fas fa-star, fab fa-facebook</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Logo Alt Yazısı</label>
                                    <input type="text" name="logo_subtext" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['logo_subtext'] ?? 'PREMIUM HESAP PAZARI') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Header Menüsü (JSON)</label>
                                    <div class="json-input-container">
                                        <textarea name="header_menu" class="form-textarea json-editor"><?= htmlspecialchars($currentSettings['header_menu'] ?? '[{"name":"Anasayfa","url":"index.php","icon":"fas fa-home"},{"name":"Hizmetler","url":"hizmetler.php","icon":"fas fa-cogs"},{"name":"Hesaplar","url":"hesaplar.php","icon":"fas fa-shopping-cart"},{"name":"SSS","url":"sss.php","icon":"fas fa-question-circle"},{"name":"İletişim","url":"iletisim.php","icon":"fas fa-phone-alt"}]') ?></textarea>
                                        <button type="button" class="json-format-btn" onclick="formatJSON(this)" title="JSON'ı düzenle">
                                            <i class="fas fa-code"></i>
                                        </button>
                                    </div>
                                    <div class="form-help">Format: [{"name":"Menu Adı","url":"sayfa.php","icon":"fas fa-home"}]</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hero Settings -->
                    <div class="settings-section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <div class="section-icon hero">
                                    <i class="fas fa-image"></i>
                                </div>
                                Hero Section Ayarları
                            </h2>
                        </div>
                        <div class="section-body">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Ana Başlık</label>
                                    <input type="text" name="hero_title" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['hero_title'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Alt Başlık</label>
                                    <input type="text" name="hero_subtitle" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['hero_subtitle'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Açıklama</label>
                                    <textarea name="hero_description" class="form-textarea"><?= htmlspecialchars($currentSettings['hero_description'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Arka Plan Görseli (URL)</label>
                                    <input type="text" name="hero_background" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['hero_background'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Buton Metni</label>
                                    <input type="text" name="hero_button_text" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['hero_button_text'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Buton Linki</label>
                                    <input type="text" name="hero_button_url" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['hero_button_url'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">İstatistikler (JSON)</label>
                                    <div class="json-input-container">
                                        <textarea name="hero_stats" class="form-textarea json-editor"><?= htmlspecialchars($currentSettings['hero_stats'] ?? '[{"number":"1000+","label":"Memnun Müşteri"},{"number":"5000+","label":"Satılan Hesap"},{"number":"24/7","label":"Destek Hizmeti"},{"number":"99%","label":"Memnuniyet"}]') ?></textarea>
                                        <button type="button" class="json-format-btn" onclick="formatJSON(this)" title="JSON'ı düzenle">
                                            <i class="fas fa-code"></i>
                                        </button>
                                    </div>
                                    <div class="form-help">Format: [{"number":"10000+","label":"Memnun Müşteri"}]</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Settings -->
                    <div class="settings-section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <div class="section-icon footer">
                                    <i class="fas fa-align-left"></i>
                                </div>
                                Footer Ayarları
                            </h2>
                        </div>
                        <div class="section-body">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">İş Başlığı (Sol Sütun)</label>
                                    <input type="text" name="footer_business_title" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['footer_business_title'] ?? 'BusinessHesap') ?>">
                                    <div class="form-help">Footer'ın sol sütunundaki başlık</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">İş Açıklaması (Sol Sütun)</label>
                                    <textarea name="footer_business_description" class="form-textarea"><?= htmlspecialchars($currentSettings['footer_business_description'] ?? 'Kaliteli sosyal medya hesapları ile işinizi büyütmeniz için buradayız. Güvenli alışverişin premium adresi.') ?></textarea>
                                    <div class="form-help">Footer'ın sol sütunundaki açıklama metni</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Sosyal Medya İkonları (JSON)</label>
                                    <div class="json-input-container">
                                        <textarea name="footer_social_icons" class="form-textarea json-editor"><?= htmlspecialchars($currentSettings['footer_social_icons'] ?? '[{"platform":"Facebook","url":"https://facebook.com","icon":"fab fa-facebook-f"},{"platform":"Twitter","url":"https://twitter.com","icon":"fab fa-twitter"},{"platform":"Instagram","url":"https://instagram.com","icon":"fab fa-instagram"}]') ?></textarea>
                                        <button type="button" class="json-format-btn" onclick="formatJSON(this)" title="JSON'ı düzenle">
                                            <i class="fas fa-code"></i>
                                        </button>
                                    </div>
                                    <div class="form-help">Format: [{"platform":"Facebook","url":"https://facebook.com","icon":"fab fa-facebook-f"}]</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Hızlı Erişim Başlığı</label>
                                    <input type="text" name="footer_quick_links_title" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['footer_quick_links_title'] ?? 'Hızlı Erişim') ?>">
                                    <div class="form-help">Orta sütundaki hızlı erişim başlığı</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Hızlı Erişim Linkleri (JSON)</label>
                                    <div class="json-input-container">
                                        <textarea name="footer_quick_links" class="form-textarea json-editor"><?= htmlspecialchars($currentSettings['footer_quick_links'] ?? '[{"name":"Anasayfa","url":"index.php","icon":"fas fa-chevron-right"},{"name":"Hizmetler","url":"hizmetler.php","icon":"fas fa-chevron-right"},{"name":"SSS","url":"sss.php","icon":"fas fa-chevron-right"},{"name":"İletişim","url":"iletisim.php","icon":"fas fa-chevron-right"}]') ?></textarea>
                                        <button type="button" class="json-format-btn" onclick="formatJSON(this)" title="JSON'ı düzenle">
                                            <i class="fas fa-code"></i>
                                        </button>
                                    </div>
                                    <div class="form-help">Format: [{"name":"Anasayfa","url":"index.php","icon":"fas fa-chevron-right"}]</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Telif Hakkı</label>
                                    <input type="text" name="footer_copyright" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['footer_copyright'] ?? '© 2025 BusinessHesap.com - Tüm Hakları Saklıdır.') ?>">
                                    <div class="form-help">Footer alt kısmındaki telif hakkı metni</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- General Settings -->
                    <div class="settings-section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <div class="section-icon general">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                Genel Ayarlar
                            </h2>
                        </div>
                        <div class="section-body">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">İletişim E-postası</label>
                                    <input type="email" name="contact_email" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['contact_email'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">İletişim Telefonu</label>
                                    <input type="text" name="contact_phone" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['contact_phone'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">WhatsApp Numarası</label>
                                    <input type="text" name="contact_whatsapp" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['contact_whatsapp'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Telegram Kanalı</label>
                                    <input type="text" name="contact_telegram" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['contact_telegram'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Adres</label>
                                    <input type="text" name="contact_address" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['contact_address'] ?? 'İstanbul, Türkiye') ?>">
                                    <div class="form-help">Footer ve iletişim sayfasında görünecek adres</div>
                                </div>
                                
                                <!-- Favicon Settings -->
                                <div class="form-group">
                                    <label class="form-label">ICO Favicon (16x16, 32x32)</label>
                                    <input type="text" name="favicon_ico" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['favicon_ico'] ?? '/favicon.ico') ?>">
                                    <div class="form-help">ICO formatında favicon dosyası</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">PNG Favicon 16x16</label>
                                    <input type="text" name="favicon_png_16" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['favicon_png_16'] ?? '/assets/icons/favicon-16x16.png') ?>">
                                    <div class="form-help">16x16 boyutunda PNG favicon</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">PNG Favicon 32x32</label>
                                    <input type="text" name="favicon_png_32" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['favicon_png_32'] ?? '/assets/icons/favicon-32x32.png') ?>">
                                    <div class="form-help">32x32 boyutunda PNG favicon</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Apple Touch Icon (180x180)</label>
                                    <input type="text" name="apple_touch_icon" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['apple_touch_icon'] ?? '/assets/icons/apple-touch-icon.png') ?>">
                                    <div class="form-help">iOS Safari için touch icon</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Android Chrome Icon 192x192</label>
                                    <input type="text" name="android_chrome_192" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['android_chrome_192'] ?? '/assets/icons/android-chrome-192x192.png') ?>">
                                    <div class="form-help">Android Chrome için 192x192 icon</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Android Chrome Icon 512x512</label>
                                    <input type="text" name="android_chrome_512" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['android_chrome_512'] ?? '/assets/icons/android-chrome-512x512.png') ?>">
                                    <div class="form-help">Android Chrome için 512x512 icon</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Web App Manifest</label>
                                    <input type="text" name="manifest_json" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['manifest_json'] ?? '/site.webmanifest') ?>">
                                    <div class="form-help">PWA manifest dosyası</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Site Tema Rengi</label>
                                    <div class="color-group">
                                        <input type="color" name="site_theme_color" class="color-input" 
                                               value="<?= htmlspecialchars($currentSettings['site_theme_color'] ?? '#6c63ff') ?>">
                                        <div class="color-info">
                                            <div style="font-weight: 600;">Tarayıcı UI Rengi</div>
                                            <div style="color: #6b7280; font-size: 0.875rem;">Tarayıcı arayüzü ve adres çubuğu rengi</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Account Registration Limits -->
                                <div class="form-group">
                                    <label class="form-label">IP Başına Maksimum Hesap Sayısı</label>
                                    <input type="number" name="max_accounts_per_ip" class="form-input" 
                                           min="1" max="100" 
                                           value="<?= htmlspecialchars($currentSettings['max_accounts_per_ip'] ?? '1') ?>">
                                    <div class="form-help">Belirtilen zaman aralığında aynı IP'den kaç hesap açılabileceğini belirler</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Zaman Aralığı (Gün)</label>
                                    <input type="number" name="ip_limit_days" class="form-input" 
                                           min="1" max="3650" 
                                           value="<?= htmlspecialchars($currentSettings['ip_limit_days'] ?? '365') ?>">
                                    <div class="form-help">Kaç gün içinde limit uygulanacağını belirler (1-3650 gün arası)</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">IP Limit Kontrolü</label>
                                    <select name="ip_limit_enabled" class="form-select">
                                        <option value="1" <?= ($currentSettings['ip_limit_enabled'] ?? '1') === '1' ? 'selected' : '' ?>>Aktif</option>
                                        <option value="0" <?= ($currentSettings['ip_limit_enabled'] ?? '1') === '0' ? 'selected' : '' ?>>Pasif</option>
                                    </select>
                                    <div class="form-help">IP başına zaman tabanlı hesap limitini etkinleştirir/devre dışı bırakır</div>
                                </div>
                                
                                <div class="form-group">
                                    <div style="background: rgba(59, 130, 246, 0.1); padding: 1rem; border-radius: 8px; border-left: 4px solid #3b82f6;">
                                        <h4 style="margin: 0 0 0.5rem 0; color: #1e40af; font-size: 0.9rem; font-weight: 600;">
                                            <i class="fas fa-info-circle"></i> Örnek Kullanım
                                        </h4>
                                        <p style="margin: 0; font-size: 0.8rem; color: #1e40af; line-height: 1.4;">
                                            <strong>1 hesap - 365 gün:</strong> Aynı IP'den 365 gün içinde sadece 1 hesap açılabilir<br>
                                            <strong>3 hesap - 30 gün:</strong> Aynı IP'den 30 gün içinde maksimum 3 hesap açılabilir<br>
                                            <strong>5 hesap - 1 gün:</strong> Aynı IP'den günlük maksimum 5 hesap açılabilir
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Welcome Message Settings -->
                                <div class="form-group">
                                    <label class="form-label">Hoşgeldin Mesajı Başlığı</label>
                                    <input type="text" name="welcome_message_title" class="form-input" 
                                           value="<?= htmlspecialchars($currentSettings['welcome_message_title'] ?? 'Hoşgeldin!') ?>">
                                    <div class="form-help">Kayıt sonrası gösterilecek hoşgeldin mesajının başlığı</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Hoşgeldin Mesajı İçeriği</label>
                                    <textarea name="welcome_message_content" class="form-textarea" rows="3"><?= htmlspecialchars($currentSettings['welcome_message_content'] ?? 'Hesabın başarıyla oluşturuldu ve otomatik giriş yapıldı. Şimdi premium hesapları inceleyebilirsin!') ?></textarea>
                                    <div class="form-help">Kayıt sonrası gösterilecek hoşgeldin mesajının içeriği</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Hoşgeldin Mesajı Gösterimi</label>
                                    <select name="welcome_message_enabled" class="form-select">
                                        <option value="1" <?= ($currentSettings['welcome_message_enabled'] ?? '1') === '1' ? 'selected' : '' ?>>Aktif</option>
                                        <option value="0" <?= ($currentSettings['welcome_message_enabled'] ?? '1') === '0' ? 'selected' : '' ?>>Pasif</option>
                                    </select>
                                    <div class="form-help">Kayıt sonrası hoşgeldin mesajını göster/gizle</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Theme Presets -->
                    <div class="settings-section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <div class="section-icon theme">
                                    <i class="fas fa-swatchbook"></i>
                                </div>
                                Hazır Tema Paletleri
                            </h2>
                            <p class="section-description">Tek tıkla profesyonel renk kombinasyonlarını uygulayın</p>
                        </div>
                        <div class="section-body">
                            <div class="theme-presets-grid">
                                <!-- Ocean Depths Theme -->
                                <div class="theme-preset" data-preset="ocean">
                                    <div class="preset-colors">
                                        <div class="color-circle" style="background: #0F4C75;"></div>
                                        <div class="color-circle" style="background: #3282B8;"></div>
                                        <div class="color-circle" style="background: #BBE1FA;"></div>
                                        <div class="color-circle" style="background: #1B262C;"></div>
                                    </div>
                                    <div class="preset-info">
                                        <h4>🌊 Ocean Depths</h4>
                                        <p>Derin ve güvenilir profesyonellik</p>
                                    </div>
                                </div>
                                
                                <!-- Vibrant Sunset Theme -->
                                <div class="theme-preset" data-preset="sunset">
                                    <div class="preset-colors">
                                        <div class="color-circle" style="background: #FF6B35;"></div>
                                        <div class="color-circle" style="background: #F7931E;"></div>
                                        <div class="color-circle" style="background: #FFD23F;"></div>
                                        <div class="color-circle" style="background: #06BCC1;"></div>
                                    </div>
                                    <div class="preset-info">
                                        <h4>🌅 Vibrant Sunset</h4>
                                        <p>Enerji dolu ve ilham verici</p>
                                    </div>
                                </div>
                                
                                <!-- Midnight Elite Theme -->
                                <div class="theme-preset" data-preset="midnight">
                                    <div class="preset-colors">
                                        <div class="color-circle" style="background: #2C3E50;"></div>
                                        <div class="color-circle" style="background: #34495E;"></div>
                                        <div class="color-circle" style="background: #E74C3C;"></div>
                                        <div class="color-circle" style="background: #1ABC9C;"></div>
                                    </div>
                                    <div class="preset-info">
                                        <h4>🌙 Midnight Elite</h4>
                                        <p>Sofistike ve güçlü karizma</p>
                                    </div>
                                </div>
                                
                                <!-- Royal Luxury Theme -->
                                <div class="theme-preset" data-preset="royal">
                                    <div class="preset-colors">
                                        <div class="color-circle" style="background: #8E44AD;"></div>
                                        <div class="color-circle" style="background: #9B59B6;"></div>
                                        <div class="color-circle" style="background: #E91E63;"></div>
                                        <div class="color-circle" style="background: #F39C12;"></div>
                                    </div>
                                    <div class="preset-info">
                                        <h4>👑 Royal Luxury</h4>
                                        <p>Asil ve prestijli elegans</p>
                                    </div>
                                </div>
                                
                                <!-- Emerald Forest Theme -->
                                <div class="theme-preset" data-preset="forest">
                                    <div class="preset-colors">
                                        <div class="color-circle" style="background: #27AE60;"></div>
                                        <div class="color-circle" style="background: #2ECC71;"></div>
                                        <div class="color-circle" style="background: #F1C40F;"></div>
                                        <div class="color-circle" style="background: #E67E22;"></div>
                                    </div>
                                    <div class="preset-info">
                                        <h4>🌲 Emerald Forest</h4>
                                        <p>Doğal güç ve büyüme</p>
                                    </div>
                                </div>
                                
                                <!-- Cosmic Galaxy Theme -->
                                <div class="theme-preset" data-preset="galaxy">
                                    <div class="preset-colors">
                                        <div class="color-circle" style="background: #667EEA;"></div>
                                        <div class="color-circle" style="background: #764BA2;"></div>
                                        <div class="color-circle" style="background: #F093FB;"></div>
                                        <div class="color-circle" style="background: #4FACFE;"></div>
                                    </div>
                                    <div class="preset-info">
                                        <h4>🌌 Cosmic Galaxy</h4>
                                        <p>Sınırsız hayal gücü</p>
                                    </div>
                                </div>
                                
                                <!-- Crimson Power Theme -->
                                <div class="theme-preset" data-preset="crimson">
                                    <div class="preset-colors">
                                        <div class="color-circle" style="background: #DC143C;"></div>
                                        <div class="color-circle" style="background: #B22222;"></div>
                                        <div class="color-circle" style="background: #FF6347;"></div>
                                        <div class="color-circle" style="background: #FFD700;"></div>
                                    </div>
                                    <div class="preset-info">
                                        <h4>🔥 Crimson Power</h4>
                                        <p>Cesur ve etkileyici güç</p>
                                    </div>
                                </div>
                                
                                <!-- Azure Sky Theme -->
                                <div class="theme-preset" data-preset="azure">
                                    <div class="preset-colors">
                                        <div class="color-circle" style="background: #007BFF;"></div>
                                        <div class="color-circle" style="background: #0056B3;"></div>
                                        <div class="color-circle" style="background: #17A2B8;"></div>
                                        <div class="color-circle" style="background: #28A745;"></div>
                                    </div>
                                    <div class="preset-info">
                                        <h4>☁️ Azure Sky</h4>
                                        <p>Temiz ve güvenilir başarı</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Theme Settings -->
                    <div class="settings-section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <div class="section-icon theme">
                                    <i class="fas fa-palette"></i>
                                </div>
                                Özel Tema Renkleri
                            </h2>
                            <p class="section-description">Kendi renk kombinasyonunuzu oluşturun</p>
                        </div>
                        <div class="section-body">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Ana Renk</label>
                                    <div class="color-group">
                                        <input type="color" name="theme_primary_color" class="color-input" 
                                               value="<?= htmlspecialchars($currentSettings['theme_primary_color'] ?? '#6c63ff') ?>">
                                        <div class="color-info">
                                            <div style="font-weight: 600;">Primary Color</div>
                                            <div style="color: #6b7280; font-size: 0.875rem;">Ana tema rengi</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">İkincil Renk</label>
                                    <div class="color-group">
                                        <input type="color" name="theme_secondary_color" class="color-input" 
                                               value="<?= htmlspecialchars($currentSettings['theme_secondary_color'] ?? '#ff6584') ?>">
                                        <div class="color-info">
                                            <div style="font-weight: 600;">Secondary Color</div>
                                            <div style="color: #6b7280; font-size: 0.875rem;">Gradient ve ikincil öğeler için</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Vurgu Rengi</label>
                                    <div class="color-group">
                                        <input type="color" name="theme_accent_color" class="color-input" 
                                               value="<?= htmlspecialchars($currentSettings['theme_accent_color'] ?? '#42e2b8') ?>">
                                        <div class="color-info">
                                            <div style="font-weight: 600;">Accent Color</div>
                                            <div style="color: #6b7280; font-size: 0.875rem;">Vurgu ve butonlar için</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Üçüncül Renk</label>
                                    <div class="color-group">
                                        <input type="color" name="theme_tertiary_color" class="color-input" 
                                               value="<?= htmlspecialchars($currentSettings['theme_tertiary_color'] ?? '#ff416c') ?>">
                                        <div class="color-info">
                                            <div style="font-weight: 600;">Tertiary Color</div>
                                            <div style="color: #6b7280; font-size: 0.875rem;">Secondary butonlar ve özel efektler için</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Link Rengi</label>
                                    <div class="color-group">
                                        <input type="color" name="theme_link_color" class="color-input" 
                                               value="<?= htmlspecialchars($currentSettings['theme_link_color'] ?? '#42e2b8') ?>">
                                        <div class="color-info">
                                            <div style="font-weight: 600;">Link Color</div>
                                            <div style="color: #6b7280; font-size: 0.875rem;">Tüm linkler için kullanılır</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // JSON validator
        document.querySelectorAll('.json-editor').forEach(textarea => {
            textarea.addEventListener('blur', function() {
                try {
                    if (this.value.trim()) {
                        const parsed = JSON.parse(this.value);
                        this.style.borderColor = '#10b981';
                        this.style.backgroundColor = '#f0fdf4';
                    } else {
                        this.style.borderColor = '#d1d5db';
                        this.style.backgroundColor = 'white';
                    }
                } catch (e) {
                    this.style.borderColor = '#ef4444';
                    this.style.backgroundColor = '#fef2f2';
                    // Daha kullanıcı dostu hata mesajı
                    const errorMsg = 'JSON formatı hatalı. Lütfen kontrol edin:\n' + e.message;
                    console.error('JSON Parse Error:', e);
                    
                    // Toast notification yerine console log
                    setTimeout(() => {
                        if (confirm(errorMsg + '\n\nDefault değeri yüklemek ister misiniz?')) {
                            // Default değeri yükle
                            const fieldName = this.name;
                            if (fieldName === 'footer_social_icons') {
                                this.value = '[{"platform":"Facebook","url":"https://facebook.com","icon":"fab fa-facebook-f"},{"platform":"Twitter","url":"https://twitter.com","icon":"fab fa-twitter"}]';
                            } else if (fieldName === 'footer_quick_links') {
                                this.value = '[{"name":"Anasayfa","url":"index.php","icon":"fas fa-chevron-right"},{"name":"Hizmetler","url":"hizmetler.php","icon":"fas fa-chevron-right"}]';
                            } else if (fieldName === 'header_menu') {
                                this.value = '[{"name":"Anasayfa","url":"index.php","icon":"fas fa-home"},{"name":"Hizmetler","url":"hizmetler.php","icon":"fas fa-cogs"}]';
                            } else if (fieldName === 'hero_stats') {
                                this.value = '[{"number":"1000+","label":"Memnun Müşteri"},{"number":"5000+","label":"Satılan Hesap"}]';
                            } else {
                                this.value = '[]';
                            }
                            this.style.borderColor = '#10b981';
                            this.style.backgroundColor = '#f0fdf4';
                        }
                    }, 100);
                }
            });
            
            // Format JSON on focus out
            textarea.addEventListener('input', function() {
                this.style.borderColor = '#d1d5db';
                this.style.backgroundColor = 'white';
            });
        });

        // Auto-save warning
        let hasChanges = false;
        document.querySelectorAll('input, textarea').forEach(input => {
            input.addEventListener('change', () => {
                hasChanges = true;
            });
        });

        window.addEventListener('beforeunload', (e) => {
            if (hasChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        document.getElementById('settingsForm').addEventListener('submit', () => {
            hasChanges = false;
        });

        // JSON formatter function
        function formatJSON(button) {
            const textarea = button.parentElement.querySelector('textarea');
            try {
                const parsed = JSON.parse(textarea.value);
                textarea.value = JSON.stringify(parsed, null, 2);
                textarea.style.borderColor = '#10b981';
                textarea.style.backgroundColor = '#f0fdf4';
            } catch (e) {
                alert('JSON formatı hatalı: ' + e.message);
            }
        }
        
        // Theme Presets Functionality - Professional & Stunning Color Palettes
        const themePresets = {
            ocean: {
                primary: '#0F4C75',     // Deep Ocean Blue
                secondary: '#3282B8',   // Medium Ocean Blue
                tertiary: '#BBE1FA',    // Light Ocean Blue
                accent: '#1B262C',      // Deep Navy
                link: '#3282B8'        // Ocean Blue Links
            },
            sunset: {
                primary: '#FF6B35',     // Vibrant Orange
                secondary: '#F7931E',   // Golden Orange
                tertiary: '#FFD23F',    // Sunset Yellow
                accent: '#06BCC1',      // Turquoise Accent
                link: '#F7931E'        // Golden Links
            },
            midnight: {
                primary: '#2C3E50',     // Midnight Blue
                secondary: '#34495E',   // Steel Blue
                tertiary: '#E74C3C',    // Ruby Red
                accent: '#1ABC9C',      // Emerald Green
                link: '#1ABC9C'        // Emerald Links
            },
            royal: {
                primary: '#8E44AD',     // Royal Purple
                secondary: '#9B59B6',   // Amethyst
                tertiary: '#E91E63',    // Pink Accent
                accent: '#F39C12',      // Golden Accent
                link: '#F39C12'        // Golden Links
            },
            forest: {
                primary: '#27AE60',     // Forest Green
                secondary: '#2ECC71',   // Emerald
                tertiary: '#F1C40F',    // Sunlight Yellow
                accent: '#E67E22',      // Carrot Orange
                link: '#E67E22'        // Orange Links
            },
            galaxy: {
                primary: '#667EEA',     // Galaxy Purple
                secondary: '#764BA2',   // Deep Purple
                tertiary: '#F093FB',    // Pink Nebula
                accent: '#4FACFE',      // Space Blue
                link: '#4FACFE'        // Space Blue Links
            },
            crimson: {
                primary: '#DC143C',     // Crimson Red
                secondary: '#B22222',   // Fire Brick
                tertiary: '#FF6347',    // Tomato
                accent: '#FFD700',      // Gold
                link: '#FFD700'        // Gold Links
            },
            azure: {
                primary: '#007BFF',     // Azure Blue
                secondary: '#0056B3',   // Deep Azure
                tertiary: '#17A2B8',    // Info Blue
                accent: '#28A745',      // Success Green
                link: '#28A745'        // Green Links
            }
        };
        
        // Theme preset click handlers
        document.querySelectorAll('.theme-preset').forEach(preset => {
            preset.addEventListener('click', function() {
                const presetName = this.dataset.preset;
                const colors = themePresets[presetName];
                
                if (colors) {
                    // Update color inputs
                    document.querySelector('input[name="theme_primary_color"]').value = colors.primary;
                    document.querySelector('input[name="theme_secondary_color"]').value = colors.secondary;
                    document.querySelector('input[name="theme_tertiary_color"]').value = colors.tertiary;
                    document.querySelector('input[name="theme_accent_color"]').value = colors.accent;
                    document.querySelector('input[name="theme_link_color"]').value = colors.link;
                    
                    // Mark as active
                    document.querySelectorAll('.theme-preset').forEach(p => p.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Get theme name from h4 text, remove emoji
                    const themeName = this.querySelector('h4').textContent.replace(/[^\w\s]/gi, '').trim();
                    
                    // Show success message
                    showToast('✨ ' + themeName + ' teması seçildi! Kaydetmeyi unutmayın.', 'success');
                    
                    // Mark as changed
                    hasChanges = true;
                }
            });
        });
        
        // Toast notification function
        function showToast(message, type = 'info') {
            // Remove existing toast
            const existingToast = document.querySelector('.toast');
            if (existingToast) {
                existingToast.remove();
            }
            
            // Create toast
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <div class="toast-content">
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="toast-close">×</button>
                </div>
            `;
            
            // Add styles
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#10b981' : '#3b82f6'};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                animation: slideInRight 0.3s ease;
                max-width: 400px;
            `;
            
            // Add animation styles
            if (!document.querySelector('#toast-styles')) {
                const style = document.createElement('style');
                style.id = 'toast-styles';
                style.textContent = `
                    @keyframes slideInRight {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                    .toast-content {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 1rem;
                    }
                    .toast-close {
                        background: none;
                        border: none;
                        color: white;
                        font-size: 1.2rem;
                        cursor: pointer;
                        padding: 0;
                        width: 20px;
                        height: 20px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                `;
                document.head.appendChild(style);
            }
            
            document.body.appendChild(toast);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>

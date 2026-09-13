<?php

require_once 'auth_header.php';
// IP Ban kontrolü - En başta olmalı
require_once 'check-ip-ban.php';

require_once '../functions.php';
// require_admin(); // auth_header.php zaten admin kontrolü yapıyor

// XSS koruması için güvenli HTML temizleme fonksiyonu
function sanitizePopupContent($html) {
    // İzin verilen HTML etiketleri (sadece güvenli olanlar)
    $allowedTags = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li', 'a', 'img', 'div', 'span', 'blockquote'
    ];
    
    // İzin verilen özellikler (sadece güvenli olanlar)
    $allowedAttributes = [
        'href', 'src', 'alt', 'title', 'class', 'id', 'target', 'rel'
    ];
    
    // Tehlikeli etiketleri ve özellikleri temizle
    $html = strip_tags($html, '<' . implode('><', $allowedTags) . '>');
    
    // JavaScript ve diğer tehlikeli içerikleri temizle
    $html = preg_replace('/on\w+\s*=\s*["\'][^"\']*/i', '', $html); // onclick, onload vs.
    $html = preg_replace('/javascript:/i', '', $html);
    $html = preg_replace('/vbscript:/i', '', $html);
    $html = preg_replace('/data:/i', '', $html);
    $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
    $html = preg_replace('/<iframe[^>]*>.*?<\/iframe>/is', '', $html);
    $html = preg_replace('/<object[^>]*>.*?<\/object>/is', '', $html);
    $html = preg_replace('/<embed[^>]*>.*?<\/embed>/is', '', $html);
    $html = preg_replace('/<form[^>]*>.*?<\/form>/is', '', $html);
    
    return trim($html);
}

$adsenseCode = $siteSettings->get('adsense_code');
$popupAdEnabled = $siteSettings->get('popup_ad_enabled');
$popupAdContent = $siteSettings->get('popup_ad_content');
$popupAdFrequency = $siteSettings->get('popup_ad_frequency');
$popupAdTarget = $siteSettings->get('popup_ad_target');

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF koruması
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Güvenlik hatası! Lütfen sayfayı yenileyip tekrar deneyin.';
        $messageType = 'danger';
    } else {
        $adsenseCode = $_POST['adsense_code'] ?? '';
        $popupAdEnabled = isset($_POST['popup_ad_enabled']) ? '1' : '0';
        $rawPopupContent = $_POST['popup_ad_content'] ?? '';
        $popupAdFrequency = (int)($_POST['popup_ad_frequency'] ?? '1');
        $popupAdTarget = $_POST['popup_ad_target'] ?? 'both';
        
        // Popup içeriğini güvenli hale getir
        $popupAdContent = sanitizePopupContent($rawPopupContent);
        
        // Ek güvenlik kontrolleri
        if ($popupAdFrequency < 1 || $popupAdFrequency > 10) {
            $popupAdFrequency = 1;
        }
        
        if (!in_array($popupAdTarget, ['visitors', 'users', 'both'])) {
            $popupAdTarget = 'both';
        }
        
        $siteSettings->set('adsense_code', $adsenseCode);
        $siteSettings->set('popup_ad_enabled', $popupAdEnabled);
        $siteSettings->set('popup_ad_content', $popupAdContent);
        $siteSettings->set('popup_ad_frequency', $popupAdFrequency);
        $siteSettings->set('popup_ad_target', $popupAdTarget);
        
        // Popup ayarları değiştiğinde tüm kullanıcıların görme durumunu sıfırla
        try {
            require_once '../includes/PopupAd.php';
            $popupAd = new PopupAd($pdo, $siteSettings);
            $resetCount = $popupAd->resetAllViews();
            
            // Eski kayıtları da temizle (30 günden eski)
            $cleanupCount = $popupAd->cleanupOldViews(30);
            
            if ($resetCount > 0) {
                $message = "Ayarlar güncellendi! $resetCount kullanıcı kaydı sıfırlandı - Popup tüm ziyaretçilere yeniden gösterilecek.";
            } else {
                $message = 'Ayarlar başarıyla güncellendi. Popup sistemi aktif!';
            }
            
            if ($cleanupCount > 0) {
                $message .= " (Ayrıca $cleanupCount eski kayıt temizlendi)";
            }
            
        } catch (Exception $e) {
            error_log('Popup reset error: ' . $e->getMessage());
            $message = 'Ayarlar güncellendi ancak popup sıfırlama sırasında bir hata oluştu.';
        }
        
        $messageType = 'success';
    }
}

// CSRF token oluştur
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Popup istatistiklerini al
$popupStats = null;
if ($popupAdEnabled) {
    try {
        require_once '../includes/PopupAd.php';
        $popupAd = new PopupAd($pdo, $siteSettings);
        $popupStats = $popupAd->getStats();
    } catch (Exception $e) {
        error_log('Popup stats error: ' . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- CKEditor CDN -->
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.0/classic/ckeditor.js"></script>
    
    <title>Reklam Yönetimi</title>
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
        
        /* Site ayarlarından tema renkleri yükle */
        <?php if (isset($siteSettings)): ?>
        :root {
            --primary: <?php echo htmlspecialchars($siteSettings->get('theme_primary_color', '#667eea')); ?>;
            --secondary: <?php echo htmlspecialchars($siteSettings->get('theme_secondary_color', '#764ba2')); ?>;
            --accent: <?php echo htmlspecialchars($siteSettings->get('theme_accent_color', '#f093fb')); ?>;
            --tertiary: <?php echo htmlspecialchars($siteSettings->get('theme_tertiary_color', '#ff416c')); ?>;
        }
        <?php endif; ?>

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


        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .main-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px var(--shadow);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #64748b;
            font-size: 1.125rem;
        }
        
        .content-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px var(--shadow);
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .section-title i {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-control::placeholder {
            color: #94a3b8;
        }
        
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 12px;
        }
        
        .checkbox-wrapper input[type="checkbox"] {
            width: 1.25rem;
            height: 1.25rem;
            margin: 0;
        }
        
        .checkbox-wrapper label {
            margin: 0;
            font-weight: 600;
        }
        
        .inline-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: rgba(74, 222, 128, 0.1);
            border: 2px solid rgba(74, 222, 128, 0.3);
            color: #065f46;
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid rgba(239, 68, 68, 0.3);
            color: #991b1b;
        }
        
        /* CKEditor overrides */
        .ck-editor__editable {
            border-radius: 12px !important;
            border: 2px solid var(--border) !important;
            min-height: 200px !important;
        }
        
        .ck-editor__editable:focus {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
        }
        
        .ck.ck-editor {
            border-radius: 12px !important;
        }
        
        .ck.ck-toolbar {
            border-radius: 12px 12px 0 0 !important;
        }
        
        .help-text {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Popup İstatistik Stilleri */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 16px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(102, 126, 234, 0.2);
            border-color: var(--primary);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 600;
        }
        
        .stats-summary {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 1rem;
            padding: 1.5rem;
            background: rgba(248, 250, 252, 0.8);
            border-radius: 12px;
            border: 1px solid var(--border);
        }
        
        .summary-item {
            text-align: center;
            color: #64748b;
        }
        
        .summary-item strong {
            color: var(--dark);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .main-content {
                padding: 1rem;
            }
            
            .inline-fields {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="admin-container">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="main-header">
            <h1 class="page-title"><i class="fas fa-ad"></i> Reklam Yönetimi</h1>
            <p class="page-subtitle">Site reklamlarını ve popup ayarlarını yönetin</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <!-- AdSense Bölümü -->
            <div class="content-section">
                <h2 class="section-title"><i class="fab fa-google"></i> Google AdSense Kodları</h2>
                <div class="form-group">
                    <label for="adsense_code" class="form-label">AdSense Head Kodu</label>
                    <textarea name="adsense_code" id="adsense_code" class="form-control" rows="6" placeholder="Google AdSense tarafından verilen kodu buraya yapıştırın..."><?= htmlspecialchars($adsenseCode) ?></textarea>
                    <div class="help-text">
                        <i class="fas fa-info-circle"></i> AdSense kontrol panelinden aldığınız kodu doğrudan buraya yapıştırabilirsiniz.
                    </div>
                </div>
            </div>
            
            <!-- Popup Reklam Bölümü -->
            <div class="content-section">
                <h2 class="section-title"><i class="fas fa-window-restore"></i> Popup Reklam Ayarları</h2>
                
                <div class="form-group">
                    <div class="checkbox-wrapper">
                        <input type="checkbox" name="popup_ad_enabled" id="popup_ad_enabled" <?php if ($popupAdEnabled) echo 'checked'; ?>>
                        <label for="popup_ad_enabled" class="form-label">Popup Reklam Sistemini Aktif Et</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="popup_ad_content" class="form-label">Popup İçeriği</label>
                    <textarea name="popup_ad_content" id="popup_ad_content" class="form-control" rows="8" placeholder="Popup içeriğinizi yazın..."><?= htmlspecialchars($popupAdContent) ?></textarea>
                    <div class="help-text">
                        <i class="fas fa-shield-alt"></i> <strong>Güvenlik:</strong> Otomatik XSS koruması aktif. İzin verilen etiketler: p, br, strong, em, h1-h6, ul, ol, li, a, img, div, span, blockquote
                    </div>
                    <div class="help-text" style="margin-top: 0.25rem;">
                        <i class="fas fa-ban"></i> <strong>Engellenen:</strong> script, iframe, form, onclick/onload gibi JavaScript event'lar
                    </div>
                </div>
                
                <div class="inline-fields">
                    <div class="form-group">
                        <label for="popup_ad_frequency" class="form-label">Günlük Gösterim Sayısı</label>
                        <input type="number" name="popup_ad_frequency" id="popup_ad_frequency" class="form-control" value="<?php echo $popupAdFrequency; ?>" min="1" max="10">
                        <div class="help-text">Aynı kişiye günde kaç kez gösterileceği</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="popup_ad_target" class="form-label">Hedef Kitle</label>
                        <select name="popup_ad_target" id="popup_ad_target" class="form-control">
                            <option value="visitors" <?php if ($popupAdTarget == 'visitors') echo 'selected'; ?>>Sadece Ziyaretçiler</option>
                            <option value="users" <?php if ($popupAdTarget == 'users') echo 'selected'; ?>>Sadece Kayıtlı Kullanıcılar</option>
                            <option value="both" <?php if ($popupAdTarget == 'both') echo 'selected'; ?>>Tüm Ziyaretçiler</option>
                        </select>
                        <div class="help-text">Popup reklamın kime gösterileceği</div>
                    </div>
                </div>
            </div>
            
            <!-- Popup İstatistikleri -->
            <?php if ($popupAdEnabled && $popupStats): ?>
            <div class="content-section">
                <h2 class="section-title"><i class="fas fa-chart-bar"></i> Popup Reklam İstatistikleri</h2>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?= $popupStats['today']['total_views'] ?? 0 ?></div>
                            <div class="stat-label">Bugünkü Gösterim</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?= $popupStats['today']['total_viewers'] ?? 0 ?></div>
                            <div class="stat-label">Bugünkü Ziyaretçi</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-secret"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?= $popupStats['today']['visitor_viewers'] ?? 0 ?></div>
                            <div class="stat-label">Anonim Ziyaretçi</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?= $popupStats['today']['user_viewers'] ?? 0 ?></div>
                            <div class="stat-label">Kayıtlı Kullanıcı</div>
                        </div>
                    </div>
                </div>
                
                <div class="stats-summary">
                    <div class="summary-item">
                        <strong>Toplam Gösterim:</strong> <?= $popupStats['all_time']['total_views'] ?? 0 ?>
                    </div>
                    <div class="summary-item">
                        <strong>Toplam Ziyaretçi:</strong> <?= $popupStats['all_time']['total_viewers'] ?? 0 ?>
                    </div>
                    <div class="summary-item">
                        <strong>İçerik Güncelleme:</strong> <?= date('d.m.Y H:i') ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i> Ayarları Güncelle
            </button>
        </form>
    </div>
</div>

<script>
// CKEditor global değişkeni
let ckEditorInstance = null;

// CKEditor'ü başlat
function initCKEditor() {
    if (ckEditorInstance) {
        return Promise.resolve(ckEditorInstance);
    }
    
    return ClassicEditor
        .create(document.querySelector('#popup_ad_content'), {
            toolbar: {
                items: [
                    'heading', '|',
                    'bold', 'italic', 'link', '|',
                    'bulletedList', 'numberedList', '|',
                    'outdent', 'indent', '|',
                    'blockQuote', '|',
                    'undo', 'redo'
                ]
            },
            language: 'tr',
            placeholder: 'Popup içeriğinizi yazın... (Otomatik güvenlik filtresi aktif)',
            
            // Güvenlik için sınırlı HTML özellikleri
            htmlSupport: {
                allow: [
                    {
                        name: /^(p|br|strong|b|em|i|u|h[1-6]|ul|ol|li|a|img|div|span|blockquote)$/,
                        attributes: {
                            class: true,
                            id: true,
                            href: true,
                            src: true,
                            alt: true,
                            title: true,
                            target: true,
                            rel: true
                        }
                    }
                ],
                disallow: [
                    {
                        name: /^(script|iframe|form|object|embed)$/
                    },
                    {
                        attributes: /^on\w+/ // onclick, onload vs. engellemek için
                    }
                ]
            }
        })
        .then(editor => {
            ckEditorInstance = editor;
            console.log('CKEditor başarıyla başlatıldı (Güvenli mod):', editor);
            
            // Form submit olduğunda CKEditor verisini textarea'ya aktar
            document.querySelector('form').addEventListener('submit', function(e) {
                const textareaElement = document.querySelector('#popup_ad_content');
                textareaElement.value = editor.getData();
            });
            
            return editor;
        })
        .catch(error => {
            console.error('CKEditor başlatılamadı:', error);
            throw error;
        });
}

// Sayfa yüklenince CKEditor'ü başlat
document.addEventListener('DOMContentLoaded', function() {
    initCKEditor();
});

// Sayfa kapatılırken CKEditor'ü temizle
window.addEventListener('beforeunload', function() {
    if (ckEditorInstance) {
        ckEditorInstance.destroy()
            .then(() => {
                ckEditorInstance = null;
                console.log('CKEditor temizlendi');
            })
            .catch(error => {
                console.error('CKEditor temizlenirken hata:', error);
            });
    }
});
</script>

</body>
</html>

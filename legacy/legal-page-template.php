<?php
// UTF-8 encoding ayarı
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Yasal Sayfa Template
require_once 'config.php';
require_once 'StructuredDataHelper.php';

// Sayfa tipini URL'den al (örn: terms.php -> terms)
// Router.php'den geliyorsa GET parametresini kullan
$pageType = isset($_GET['page_type']) ? $_GET['page_type'] : basename($_SERVER['SCRIPT_NAME'], '.php');

// Sayfa tipleri ve varsayılan bilgileri
$pageTypes = [
    'kvvk' => [
        'title' => 'KVKK Aydınlatma Metni',
        'description' => 'Kişisel verilerinizin korunması, işlenme amaçları, veri güvenliği ve yasal haklarınız hakkında detaylı bilgilendirme.',
        'default_content' => '<h2>KVKK Aydınlatma Metni</h2><p>Bu sayfa henüz yapılandırılmamış.</p>',
        'slug' => 'kvvk',
        'meta' => [
            'keywords' => 'kvkk aydınlatma metni, kişisel verilerin korunması, yildizhesap veri gizlilik ve güvenliği, kvkk kullanıcı hakları',
            'ai_intent' => 'kişisel verilerinin nasıl işlendiğini ve kvkk kapsamındaki haklarını öğrenmek isteyen kullanıcılar için bilgilendirici içerik',
            'ai_subtopics' => 'kvkk aydınlatma metni, kişisel veri işleme, veri güvenliği, kullanıcı hakları',
            'audience' => 'yildizhesap kullanıcıları, site ziyaretçileri, hesap satın alan müşteriler',
            'category' => 'hukuki bilgilendirme, kvkk ve gizlilik politikası',
            'content_tone' => 'resmi, bilgilendirici, şeffaf',
            'reader_interest' => 'kişisel verilerin güvenliği, kullanıcı gizliliği, yasal haklar hakkında bilgi edinmek',
            'summary' => 'yildizhesap kullanıcılarına ait kişisel verilerin hangi amaçlarla işlendiğini, nasıl korunduğunu ve kvkk kapsamındaki hakları açıklayan resmi bilgilendirme metni',
            'topic_tags' => 'kvkk, kişisel veriler, gizlilik politikası, veri güvenliği',
            'visual_content' => 'kvkk ve gizlilik ikonları'
        ]
    ],
    'privacy' => [
        'title' => 'Gizlilik Politikası',
        'description' => 'Kişisel bilgilerinizin nasıl toplandığı, kullanıldığı, korunduğu ve hangi durumlarda paylaşıldığı hakkında detaylı bilgiler.',
        'default_content' => '<h2>Gizlilik Politikası</h2><p>Bu sayfa henüz yapılandırılmamış.</p>',
        'slug' => 'gizlilik-politikasi',
        'meta' => [
            'keywords' => 'gizlilik politikası, kişisel verilerin korunması, yildizhesap gizlilik, kullanıcı verileri, veri güvenliği',
            'ai_intent' => 'kişisel verilerinin gizliliği ve site tarafından nasıl korunduğu hakkında şeffaf bilgi almak isteyen kullanıcılar için açıklamalar',
            'ai_subtopics' => 'gizlilik politikası, kişisel veri güvenliği, çerez kullanımı',
            'audience' => 'yildizhesap kullanıcıları, site ziyaretçileri, hesap satın alan müşteriler',
            'category' => 'gizlilik politikası, kullanıcı verileri ve güvenlik',
            'content_tone' => 'resmi, bilgilendirici, şeffaf',
            'reader_interest' => 'kişisel bilgilerin korunması, online gizlilik, güvenli alışveriş',
            'summary' => 'yildizhesap gizlilik politikası, kullanıcı bilgilerinin toplanması ve kullanılması süreçlerini açıklar',
            'topic_tags' => 'gizlilik politikası, kullanıcı verileri ve veri güvenliği, çerez kullanımı',
            'visual_content' => 'gizlilik ve güvenlik ikonları'
        ]
    ],
    'terms' => [
        'title' => 'Kullanım Şartları',
        'description' => 'Hizmetlerimizi kullanırken uymanız gereken şartlar',
        'default_content' => '<h2>Kullanım Şartları</h2><p>Bu sayfa henüz yapılandırılmamış.</p>',
        'slug' => 'kullanim-sartlari'
    ],
    'cookies' => [
        'title' => 'Çerez Politikası',
        'description' => 'Web sitemizde kullanılan çerezler hakkında bilgiler',
        'default_content' => '<h2>Çerez Politikası</h2><p>Bu sayfa henüz yapılandırılmamış.</p>',
        'slug' => 'cerez-politikasi'
    ],
    'refund' => [
        'title' => 'İade ve İptal Politikası',
        'description' => 'İade ve iptal işlemleri hakkında detaylı bilgiler',
        'default_content' => '<h2>İade ve İptal Politikası</h2><p>Bu sayfa henüz yapılandırılmamış.</p>',
        'slug' => 'iade-politikasi'
    ],
    'about' => [
        'title' => 'Hakkımızda',
        'description' => 'Şirketimiz ve hizmetlerimiz hakkında bilgiler',
        'default_content' => '<h2>Hakkımızda</h2><p>Bu sayfa henüz yapılandırılmamış.</p>',
        'slug' => 'hakkimizda'
    ]
];

// Geçerli sayfa tipini kontrol et
if (!isset($pageTypes[$pageType])) {
    header('HTTP/1.0 404 Not Found');
    exit('Sayfa bulunamadı');
}

$pageInfo = $pageTypes[$pageType];

// Sayfa verilerini al
try {
    $stmt = $pdo->prepare("SELECT * FROM legal_pages WHERE page_type = ? AND is_active = 1");
    $stmt->execute([$pageType]);
    $pageData = $stmt->fetch();
} catch (Exception $e) {
    error_log("Legal page error: " . $e->getMessage());
}

if (!isset($pageData) || !$pageData) {
    // Varsayılan içerik
    $pageData = [
        'title' => $pageInfo['title'],
        'content' => $pageInfo['default_content'],
        'meta_description' => $pageInfo['title'],
        'updated_at' => date('Y-m-d H:i:s')
    ];
}

// Sayfa başlığını ayarla
$page_title = $pageData['title'];

// Meta Tags for SEO and AI
$meta_title = $pageData['title'];
$meta_description = isset($pageData['meta_description']) ? $pageData['meta_description'] : $pageInfo['description'];

// Set meta tags from pageInfo if available
if (isset($pageInfo['meta'])) {
    $meta_keywords = $pageInfo['meta']['keywords'];
    $meta_ai_intent = $pageInfo['meta']['ai_intent'];
    $meta_ai_subtopics = $pageInfo['meta']['ai_subtopics'];
    $meta_audience = $pageInfo['meta']['audience'];
    $meta_category = $pageInfo['meta']['category'];
    $meta_content_tone = $pageInfo['meta']['content_tone'];
    $meta_reader_interest = $pageInfo['meta']['reader_interest'];
    $meta_summary = $pageInfo['meta']['summary'];
    $meta_topic_tags = $pageInfo['meta']['topic_tags'];
    $meta_visual_content = $pageInfo['meta']['visual_content'];
}

// Yapısal veri oluştur
$structuredDataHelper = new StructuredDataHelper();
$pageSlug = isset($pageInfo['slug']) ? $pageInfo['slug'] : $pageType;
$structuredData = $structuredDataHelper->getLegalPageStructuredData($pageSlug, $pageData['title']);

// Header dahil et
include 'header.php';
?>

<!-- Ana İçerik -->
<main class="container" style="margin-top: 120px; margin-bottom: 60px;">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="content-card legal-content-wrapper rounded-4 shadow-lg p-5">
                <!-- Sayfa Başlığı -->
                <div class="text-center mb-5">
                    <h1 class="display-5 fw-bold mb-3" style="background: var(--gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                        <?= htmlspecialchars($pageData['title']) ?>
                    </h1>
                    <p class="lead text-muted">
                        <?= htmlspecialchars($pageInfo['description']) ?>
                    </p>
                    <hr class="my-4" style="border-color: var(--primary); opacity: 0.3;">
                </div>

                <!-- İçerik -->
                <div class="legal-content">
                    <?= $pageData['content'] ?>
                </div>

                <!-- Son Güncelleme -->
                <div class="mt-5 pt-4 border-top text-center">
                    <small class="text-muted">
                        <i class="fas fa-clock me-2"></i>
                        Son güncelleme: <?= date('d.m.Y H:i', strtotime($pageData['updated_at'] ?? 'now')) ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
/* Yasal sayfalar için özel stiller */
.legal-content-wrapper {
    background: var(--bg-secondary) !important;
    color: var(--text-primary) !important;
    transition: all 0.3s ease;
}

.legal-content {
    font-size: 1rem;
    line-height: 1.8;
    color: var(--text-primary);
}

.legal-content h2 {
    color: var(--text-primary);
    font-size: 1.75rem;
    font-weight: 700;
    margin: 2.5rem 0 1.5rem 0;
    text-align: center;
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.legal-content h3 {
    color: var(--text-primary);
    font-size: 1.375rem;
    font-weight: 600;
    margin: 2rem 0 1rem 0;
    padding-left: 1rem;
    border-left: 4px solid var(--primary);
}

.legal-content h4 {
    color: var(--text-primary);
    font-size: 1.125rem;
    font-weight: 600;
    margin: 1.5rem 0 0.75rem 0;
}

.legal-content p {
    margin-bottom: 1.25rem;
    text-align: justify;
}

.legal-content ul {
    margin: 1rem 0 1.5rem 2rem;
    padding-left: 0;
}

.legal-content li {
    margin-bottom: 0.5rem;
    position: relative;
}

.legal-content li::marker {
    color: var(--primary);
}

.legal-content strong {
    color: var(--text-primary);
    font-weight: 600;
}

.legal-content em {
    color: var(--text-secondary);
    font-style: italic;
}

/* Dark tema uyumluluğu */
[data-bs-theme="dark"] .legal-content-wrapper {
    background: var(--bs-dark) !important;
    border: 1px solid var(--bs-border-color) !important;
}

[data-bs-theme="dark"] .legal-content {
    color: var(--bs-body-color) !important;
}

[data-bs-theme="dark"] .legal-content h2,
[data-bs-theme="dark"] .legal-content h3,
[data-bs-theme="dark"] .legal-content h4,
[data-bs-theme="dark"] .legal-content strong {
    color: var(--bs-body-color) !important;
}

[data-bs-theme="dark"] .legal-content em {
    color: var(--bs-secondary-color) !important;
}

/* Dark tema için özel metin renkleri */
[data-bs-theme="dark"] .text-muted {
    color: #d1d5db !important;
}

[data-bs-theme="dark"] .lead.text-muted {
    color: #e5e7eb !important;
}

[data-bs-theme="dark"] small.text-muted {
    color: #d1d5db !important;
}

/* Hover efektleri dark tema için */
[data-bs-theme="dark"] small.text-muted:hover {
    color: #f3f4f6 !important;
}

/* Responsive */
@media (max-width: 768px) {
    .legal-content h2 {
        font-size: 1.5rem;
    }
    
    .legal-content h3 {
        font-size: 1.25rem;
    }
    
    .legal-content ul {
        margin-left: 1rem;
    }
}
</style>

<?php
// Footer dahil et
include 'footer.php';
?>

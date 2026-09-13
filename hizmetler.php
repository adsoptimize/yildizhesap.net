<?php 
require_once 'config.php';
require_once 'functions.php';
require_once 'StructuredDataHelper.php';

$page_title = "Hizmetlerimiz";

// Meta Tags for SEO and AI
$meta_title = "Premium Sosyal Medya Hesap Satın Alarak İşlerinizi Büyütün";
$meta_description = "Doğrulanmış Facebook, Instagram, Twitter ve diğer premium hesaplarla işlerinizi büyütün. Güvenli ödeme ve hızlı teslimat fırsatını kaçırmayın!";
$meta_keywords = "premium sosyal medya hesapları, facebook instagram twitter tiktok doğrulanmış hesaplar, marketplace hesapları";
$meta_ai_intent = "premium sosyal medya hesapları, doğrulanmış hesaplar, işinizi büyütmek, güvenli teslimat, yüksek kalite";
$meta_ai_subtopics = "facebook hesapları, instagram hesapları, twitter hesapları, tiktok hesapları, telegram hesapları, mail hesapları, old accounts, businnes manager, marketplace hesapları";
$meta_audience = "işletmeler, dijital pazarlamacılar, sosyal medya yöneticileri, güvenli hesap arayan kullanıcılar";
$meta_category = "sosyal medya, premium hesaplar, dijital hizmetler, hesap satışları";
$meta_content_tone = "güvenilir, profesyonel";
$meta_reader_interest = "işlerini büyütmek isteyen girişimciler, premium hesap satın almak isteyenler";
$meta_summary = "doğrulanmış facebook instagram twitter tiktok ve diğer premium hesaplarla işlerinizi büyütebilirsiniz";
$meta_topic_tags = "premium hesaplar, sosyal medya hesapları, facebook instagram twitter tiktok, marketplace hesapları";
$meta_visual_content = "premium hesap satışı görselleri";

// Pagination ayarları
$itemsPerPage = 88;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Account Manager sınıfını başlat
$accountManager = new AccountManager();

// Kategorileri çek
try {
    $categories = $accountManager->getCategories($itemsPerPage, $offset);
    $totalCategories = $accountManager->getTotalCategories();
    $totalPages = ceil($totalCategories / $itemsPerPage);
} catch (Exception $e) {
    error_log("Categories fetch error: " . $e->getMessage());
    $categories = [];
    $totalCategories = 0;
    $totalPages = 0;
}

// Yapısal veri oluştur
$structuredDataHelper = new StructuredDataHelper();
$structuredData = $structuredDataHelper->getServicesStructuredData($categories);

include 'header.php'; 
?>

    <!-- Page Header -->
    <section class="page-header compact">
        <div class="container">
            <h1>Hizmetlerimiz</h1>
            <p>Premium sosyal medya hesapları ve dijital pazarlama araçları ile işinizi büyütün.</p>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services-section">
        <div class="container">
            <?php if (!empty($categories)): ?>
                <div class="services-grid">
                    <?php foreach ($categories as $category): ?>
                        <div class="service-card category-<?php echo htmlspecialchars($category['slug']); ?>">
                            <div class="service-header">
                                <div class="service-icon">
                                    <i class="<?php echo htmlspecialchars($category['icon']); ?>"></i>
                                </div>
                                <div class="service-info">
                                    <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                                    <p>Premium <?php echo htmlspecialchars($category['name']); ?> hizmetleri</p>
                                </div>
                            </div>
                            <div class="service-body">
                                <ul class="service-features">
                                    <li><i class="fas fa-check-circle"></i> Doğrulanmış hesaplar</li>
                                    <li><i class="fas fa-check-circle"></i> Yüksek kalite garantisi</li>
                                    <li><i class="fas fa-check-circle"></i> Anında teslimat</li>
                                    <li><i class="fas fa-check-circle"></i> 7/24 destek</li>
                                </ul>
                                <div class="service-action">
                                    <a href="<?php echo URLHelper::getCategoryUrl($category['id']); ?>" class="btn-primary">
                                        <i class="fas fa-shopping-cart"></i> Hesapları Görüntüle
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-services">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Henüz hizmet bulunmuyor</h3>
                    <p>Yakında yeni hizmetler eklenecek.</p>
                </div>
            <?php endif; ?>
            
           
        </div>
    </section>

<?php include 'footer.php'; ?>

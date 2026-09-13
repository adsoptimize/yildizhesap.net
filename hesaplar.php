<?php
// hesaplar.php - Ana hesaplar sayfası
require_once 'config.php';
require_once 'functions.php';
require_once 'StructuredDataHelper.php';

$page_title = "Hesaplar";

// Account Manager sınıfını başlat
$accountManager = new AccountManager();

// URL'den kategori parametresi al (slug veya ID olabilir)
$categoryParam = isset($_GET['category']) ? trim($_GET['category']) : null;
$selectedCategory = null;
$currentCategorySlug = 'tum-hesaplar'; // varsayılan
$currentCategoryData = null;

// Router tarafından gönderilen URI'yi kontrol et
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$requestUri = trim(parse_url($requestUri, PHP_URL_PATH), '/');

if ($categoryParam) {
    // Eğer sayısal ise ID olarak kabul et
    if (is_numeric($categoryParam)) {
        $selectedCategory = intval($categoryParam);
        
        // ID'den seo_slug'ı bul
        try {
            $stmt = Database::getInstance()->getConnection()->prepare("SELECT id, name, seo_slug, description FROM categories WHERE id = ? AND is_active = 1");
            $stmt->execute([$selectedCategory]);
            $currentCategoryData = $stmt->fetch();
            if ($currentCategoryData && !empty($currentCategoryData['seo_slug'])) {
                $currentCategorySlug = $currentCategoryData['seo_slug'];
            }
        } catch (PDOException $e) {
            error_log("Category ID lookup error: " . $e->getMessage());
        }
    } else {
        // Slug ise kategori ID'sini bul
        try {
            $stmt = Database::getInstance()->getConnection()->prepare("SELECT id, name, seo_slug, description FROM categories WHERE (slug = ? OR seo_slug = ?) AND is_active = 1");
            $stmt->execute([$categoryParam, $categoryParam]);
            $currentCategoryData = $stmt->fetch();
            if ($currentCategoryData) {
                $selectedCategory = $currentCategoryData['id'];
                if (!empty($currentCategoryData['seo_slug'])) {
                    $currentCategorySlug = $currentCategoryData['seo_slug'];
                }
            }
        } catch (PDOException $e) {
            error_log("Category slug lookup error: " . $e->getMessage());
        }
    }
} elseif (!empty($requestUri) && $requestUri !== 'hesaplar.php' && $requestUri !== 'tum-hesaplar') {
    // Router üzerinden gelen slug'ı kontrol et
    try {
        $stmt = Database::getInstance()->getConnection()->prepare("SELECT id, name, seo_slug, description FROM categories WHERE seo_slug = ? AND is_active = 1");
        $stmt->execute([$requestUri]);
        $currentCategoryData = $stmt->fetch();
        if ($currentCategoryData) {
            $selectedCategory = $currentCategoryData['id'];
            $currentCategorySlug = $currentCategoryData['seo_slug'];
        }
    } catch (PDOException $e) {
        error_log("Category URI lookup error: " . $e->getMessage());
    }
}

// Kategorileri getir
$categories = $accountManager->getCategories();

// İlk yükleme için filtreleri ayarla
$initialFilters = [];
if ($selectedCategory) {
    $initialFilters['category_id'] = $selectedCategory;
}

// İlk yükleme için hesapları getir
$initialAccounts = $accountManager->getAccounts($initialFilters, 1, ITEMS_PER_PAGE);
$totalAccounts = $accountManager->getTotalAccounts($initialFilters);

// Meta Tags for SEO and AI - Default hesaplar.php
$meta_title = "Facebook Premium Hesap Satın Al | Doğrulanmış ve Güvenli";
$meta_description = "Facebook premium hesap satın alın! Doğrulanmış, yüksek kaliteli ve hızlı teslimat garantili hesaplarla güvenli alışveriş yapın!";
$meta_keywords = "facebook premium hesap satın al, doğrulanmış facebook hesapları, kimlik onaylı facebook hesapları, marketplace hesapları, güvenli hesap";
$meta_ai_intent = "premium facebook hesap satın alma, yüksek kalite ve güvenli teslimat hizmeti";
$meta_ai_subtopics = "premium facebook hesapları, doğrulanmış hesaplar, kimlik onaylı hesaplar, marketplace hesapları, Türkiye ve global hesaplar";
$meta_audience = "sosyal medya kullanıcıları, dijital pazarlamacılar, işletmeler, güvenli hesap arayanlar";
$meta_category = "sosyal medya, dijital hizmetler, premium hesaplar";
$meta_content_tone = "profesyonel, premium";
$meta_reader_interest = "kaliteli facebook hesapları, güvenli ödeme seçenekleri, premium hesap çeşitleri";
$meta_summary = "doğrulanmış ve premium facebook hesaplarını güvenli ödeme ile hızlı bir şekilde satın alabilirsiniz";
$meta_topic_tags = "facebook premium hesap, doğrulanmış hesaplar, kimlik onaylı hesaplar, marketplace hesapları, yüksek kaliteli hesaplar";
$meta_visual_content = "facebook premium hesap görselleri, teslimat simgeleri, hesap türlerini gösteren ikonlar";

// Override meta tags based on category
if ($selectedCategory == 7) {
    // hesaplar.php?category=7
    $meta_title = "Doğrulanmış Facebook Hesapları | Premium Seçenekler";
    $meta_description = "Doğrulanmış eski Facebook ve sosyal medya hesaplarını güvenli ödeme yöntemleri, hızlı teslimat ve premium seçeneklerle hemen satın alın.";
    $meta_keywords = "facebook premium hesap, doğrulanmış hesaplar, eski facebook hesapları, global sosyal medya hesapları";
    $meta_ai_intent = "facebook hesap satın al, premium hesaplar, doğrulanmış hesaplar, eski sosyal medya hesapları";
    $meta_ai_subtopics = "facebook hesapları, global facebook hesapları, kimlik doğrulanmış hesaplar, premium sosyal medya hesap seçenekleri";
    $meta_content_tone = "güvenilir, profesyonel, hızlı, premium";
    $meta_reader_interest = "kaliteli facebook hesapları, eski sosyal medya hesapları, premium seçenekler";
    $meta_summary = "doğrulanmış ve premium facebook hesapları satışı";
    $meta_topic_tags = "facebook hesap satın al, premium hesaplar, doğrulanmış hesaplar, global sosyal medya hesapları";
} elseif ($selectedCategory == 8) {
    // hesaplar.php?category=8
    $meta_title = "Doğrulanmış Facebook Premium Hesap Satın Al | Hızlı Teslim";
    $meta_description = "Eski tarihli Facebook hesaplarını en uygun premium seçenekler ile güvenli ödeme ve anlık teslimat garantisiyle hemen satın alın.";
    $meta_keywords = "facebook premium hesap, doğrulanmış hesaplar, eski facebook hesapları, kimlik doğrulanmış hesaplar";
    $meta_ai_intent = "facebook hesap satın al, premium hesaplar, doğrulanmış hesaplar, eski sosyal medya hesaplarının satışı";
    $meta_ai_subtopics = "premium facebook hesapları, eski hesaplar, kimlik doğrulanmış hesaplar";
    $meta_content_tone = "güvenilir, profesyonel";
    $meta_reader_interest = "kaliteli facebook hesapları, premium seçenekler";
    $meta_summary = "doğrulanmış ve premium facebook hesaplarını güvenli ödeme ve anlık teslimat ile satın alabilirsiniz";
    $meta_topic_tags = "facebook hesap satın al, premium hesaplar, doğrulanmış hesaplar, eski hesapların satışı";
} elseif ($selectedCategory == 12) {
    // hesaplar.php?category=12
    $meta_title = "Premium Facebook Eski Hesapları Uygun Fiyata Satın Alın!";
    $meta_description = "Doğrulanmış eski Facebook premium hesaplarını kolayca seçin ve güvenli ödeme ile hızlı teslimat garantisiyle hemen satın alın.";
    $meta_keywords = "facebook premium hesap satışı, doğrulanmış hesaplar uygun fiyatlı, eski facebook hesapları, kimlik doğrulanmış hesap satışı";
    $meta_ai_intent = "facebook hesap satın al, premium hesaplar, doğrulanmış eski hesaplar";
    $meta_ai_subtopics = "premium facebook hesapları, kimlik doğrulanmış facebook hesap seçenekleri";
    $meta_reader_interest = "yüksek kaliteli facebook hesapları, premium seçenekler";
    $meta_summary = "doğrulanmış ve premium facebook hesaplarını güvenli ödeme ve hızlı teslimatla satın alabilirsiniz";
    $meta_topic_tags = "facebook hesap satın al, premium hesaplar, doğrulanmış hesaplar";
    $meta_visual_content = "facebook hesap görselleri, teslimat simgeleri, hesap türlerini gösteren ikonlar, premium seçenekler";
} elseif ($selectedCategory == 13) {
    // hesaplar.php?category=13
    $meta_title = "Eski ve Doğrulanmış Business Manager Hesapları | Limitli";
    $meta_description = "Reklam geçmişi bulunan, günlük harcama limitli ve doğrulanmış Business Manager hesaplarını güvenli ödeme ve hızlı teslimat avantajıyla edinin.";
    $meta_keywords = "business manager satın al, doğrulanmış bm hesabı, günlük limitli reklam hesabı, eski business manager";
    $meta_ai_intent = "doğrulanmış ve reklam kullanımına hazır business manager hesapları arayanlar için uygun seçenekler";
    $meta_ai_subtopics = "business manager hesapları, günlük limitli bm, eski reklam hesapları, doğrulanmış işletme yöneticisi";
    $meta_audience = "dijital reklamcılar, ajanslar, e-ticaret markaları, performans pazarlamacıları";
    $meta_category = "facebook reklam altyapısı, business manager hesapları";
    $meta_content_tone = "teknik, profesyonel";
    $meta_reader_interest = "hazır reklam altyapısı, stabil business manager, güvenli reklam hesabı";
    $meta_summary = "günlük limitli ve doğrulanmış business manager hesapları";
    $meta_topic_tags = "business manager, reklam hesabı, doğrulanmış bm, eski hesap";
    $meta_visual_content = "business manager, reklam limit ikonları";
} elseif ($selectedCategory == 14) {
    // hesaplar.php?category=14
    $meta_title = "Facebook Marketplace Hesapları | Satışa Açık ve Aktif";
    $meta_description = "Reklam vermeye hazır, Marketplace erişimi açık Facebook hesaplarını hızlı teslimat ve güvenli ödeme seçenekleriyle hemen satın alın.";
    $meta_keywords = "facebook marketplace hesap satın al, satışa açık facebook hesabı, ilan verme aktif hesaplar";
    $meta_ai_intent = "facebook marketplace üzerinden ürün satışı yapmak isteyen kullanıcıları bilgilendirme";
    $meta_ai_subtopics = "facebook marketplace hesapları, satışa açık hesaplar, ilan verme aktif hesaplar, marketplace erişimi";
    $meta_audience = "e-ticaret satıcıları, bireysel satış yapanlar, dropshipping kullanıcıları, sosyal ticaret girişimcileri";
    $meta_category = "facebook marketplace, sosyal ticaret, hesap satışı";
    $meta_content_tone = "satış odaklı, profesyonel";
    $meta_reader_interest = "hızlı satış yapmak isteyenler, marketplace üzerinden ürün listelemek isteyenler";
    $meta_summary = "satışa hazır facebook marketplace hesapları";
    $meta_topic_tags = "facebook marketplace, satış hesapları, ilan verme";
    $meta_visual_content = "marketplace satış ikonları";
} elseif ($selectedCategory == 15) {
    // hesaplar.php?category=15
    $meta_title = "Facebook Hesapları Satın Al | Güvenli ve Hızlı Teslim";
    $meta_description = "Farklı kullanım amaçlarına ve reklama uygun Facebook hesaplarını güvenli ödeme, hızlı teslimat ve net hesap bilgileriyle kolayca satın alın.";
    $meta_keywords = "facebook hesap satın al, eski facebook hesapları satışı, doğrulanmış eski facebook hesabı uygun fiyat, güvenli facebook hesabı satın al";
    $meta_ai_intent = "facebook hesapları satın almak isteyenler için uygun seçenekler";
    $meta_ai_subtopics = "facebook hesapları, eski facebook hesapları, doğrulanmış facebook profilleri";
    $meta_audience = "bireysel kullanıcılar, dijital girişimciler, sosyal medya ile ilgilenenler, e-ticaret ve dijital pazarlama yöneticileri";
    $meta_category = "facebook hesapları, sosyal medya hesapları";
    $meta_content_tone = "açıklayıcı, profesyonel";
    $meta_reader_interest = "facebook hesabı edinmek isteyen kullanıcılar, dijital pazarlama ve sosyal medya pazarlama yöneticileri";
    $meta_summary = "reklam ve farklı ihtiyaçlara uygun facebook hesap satışı";
    $meta_topic_tags = "facebook hesapları, sosyal medya hesabı satın al, eski facebook hesapları satışı";
    $meta_visual_content = "facebook hesabı ikonları";
} elseif ($selectedCategory == 16) {
    // hesaplar.php?category=16
    $meta_title = "Instagram Hesapları Satın Al | Güvenli ve Hızlı Teslim";
    $meta_description = "Farklı kullanım senaryolarına uygun Instagram hesaplarını şeffaf bilgiler, güvenli ödeme ve hızlı teslimat altyapısıyla satın alın.";
    $meta_keywords = "instagram hesap satışı, eski instagram hesapları, instagram profili satın al, güvenli instagram hesabı uygun fiyatlı";
    $meta_ai_intent = "güvenli ve hızlı şekilde, uygun fiyatlı ve farklı kullanım senaryolarına uygun eski instagram hesabı satışı";
    $meta_ai_subtopics = "instagram hesapları, eski instagram profilleri, sosyal medya hesap satışı";
    $meta_audience = "bireysel kullanıcılar, içerik üreticileri, dijital girişimciler, dijital pazarlama uzmanları";
    $meta_category = "instagram hesapları, sosyal medya hesapları";
    $meta_content_tone = "satış odaklı, profesyonel";
    $meta_reader_interest = "instagram hesabı edinmek isteyen kullanıcılar";
    $meta_summary = "çeşitli ihtiyaçlara uygun instagram hesap seçenekleri";
    $meta_topic_tags = "instagram hesap satışı, sosyal medya hesap yönetimi, instagram hesapları satın al";
    $meta_visual_content = "instagram ikonu";
} elseif ($selectedCategory == 20) {
    // hesaplar.php?category=20
    $meta_title = "X (Twitter) Hesapları Satın Al | Aktif Profiller Uygun Fiyat";
    $meta_description = "Farklı kullanım amaçlarına uygun X (Twitter) hesaplarını güvenli ödeme altyapısı ve hızlı erişim avantajıyla hemen satın alın.";
    $meta_keywords = "twitter x hesap yönetimi, eski twitter hesapları satışı, x hesabı satın al, sosyal medya profili satışı";
    $meta_ai_intent = "x twitter hesapları hakkında bilgi ve güvenilir şekilde hesap temin seçenekleri";
    $meta_ai_subtopics = "twitter hesapları satışı, x profilleri satın al, sosyal medya hesap satışı";
    $meta_audience = "bireysel kullanıcılar, dijital pazarlama uzmanları, içerik üreticileri ve editörler, sosyal medya yöneticileri";
    $meta_category = "twitter x hesap yönetimi, sosyal medya hesapları";
    $meta_content_tone = "profesyonel, bilgilendirici";
    $meta_reader_interest = "twitter hesabı edinmek isteyen kullanıcılar, etkileşim ve erişim odaklı çözümler";
    $meta_summary = "çeşitli kullanım senaryolarına uygun twitter x hesap seçenekleri";
    $meta_topic_tags = "twitter, x, sosyal medya, twitter hesapları";
    $meta_visual_content = "twitter x ikonları";
} elseif ($selectedCategory == 21) {
    // hesaplar.php?category=21
    $meta_title = "TikTok Hesapları Satın Al | Aktif Profiller Uygun Fiyat";
    $meta_description = "Farklı kullanım amaçlarına uygun TikTok hesaplarını sade yapı, güvenli ödeme altyapısı ve hızlı erişim avantajıyla hemen satın alın.";
    $meta_keywords = "tiktok hesap satışı, eski tiktok hesapları satın al, video içerik hesabı uygun fiyatlı, sosyal medya profili satın al";
    $meta_ai_intent = "tiktok hesap satın almak isteyen kullanıcılar için uygun seçenekler";
    $meta_ai_subtopics = "tiktok hesapları, etkileşim temelli profil satışı";
    $meta_audience = "içerik üreticileri, sosyal medya yöneticileri, bireysel kullanıcılar, dijital pazarlama uzmanları";
    $meta_category = "tiktok hesap yönetimi, sosyal medya hesapları yönetimi, tiktok ve sosyal medya reklamları";
    $meta_content_tone = "profesyonel, bilgilendirici";
    $meta_reader_interest = "tiktok üzerinden içerik üretmek ve erişim sağlamak isteyen kullanıcılar";
    $meta_summary = "içerik üretimi ve paylaşım odaklı tiktok hesap satışı";
    $meta_topic_tags = "tiktok hesap yönetimi, video içerik hesapları satışı, sosyal medya hesap yönetimi, tiktok hesapları uygun fiyatlı";
    $meta_visual_content = "tiktok ikonları";
} elseif ($selectedCategory == 22) {
    // hesaplar.php?category=22
    $meta_title = "Gmail, Outlook ve Hotmail Mail Hesapları | Güvenli ve Ucuz";
    $meta_description = "Gmail, Outlook ve Hotmail hesaplarını farklı kullanım senaryolarına uygun yapı, sorunsuz erişim ve hızlı kullanım avantajıyla satın alın.";
    $meta_keywords = "gmail mail hesabı satın al, outlook mail satın al, hotmail hesabı satın al, e-posta hesapları en ucuz";
    $meta_ai_intent = "mail hesapları satışı, dijital işlemler için hazır e-posta çözümleri";
    $meta_ai_subtopics = "gmail hesapları, outlook mail, hotmail e-posta, e-posta hesapları satın al";
    $meta_audience = "bireysel kullanıcılar, dijital hizmet kullanıcıları, online platform yöneticileri, dijital pazarlama uzmanları";
    $meta_category = "e-posta hizmetleri, dijital hesap yönetimi";
    $meta_content_tone = "profesyonel, bilgilendirici";
    $meta_reader_interest = "farklı platformlar için mail hesabı kullanmak isteyen kullanıcılar";
    $meta_summary = "gmail, outlook ve hotmail tabanlı mail hesap seçenekleri";
    $meta_topic_tags = "gmail, outlook, hotmail, mail hesapları, e-posta satışı";
    $meta_visual_content = "mail ikonları, e-posta simgeleri";
} elseif ($selectedCategory == 27) {
    // hesaplar.php?category=27
    $meta_title = "Telegram Hesapları Satın Al | Hazır ve Kullanıma Uygun";
    $meta_description = "Telegram hesaplarını farklı kullanım amaçlarına uygun yapı, hızlı erişim ve sorunsuz kullanım avantajlarıyla uygun fiyata kolayca edinin.";
    $meta_keywords = "telegram hesapları uygun fiyatlı, telegram profil satın al, telegram kullanıcı hesabı uygun fiyatlı";
    $meta_ai_intent = "telegram hesapları satışı, dijital işlemler için hazır telegram çözümleri";
    $meta_ai_subtopics = "telegram hesap yönetimi, telegram kullanıcı profilleri uygun fiyatlı";
    $meta_audience = "bireysel kullanıcılar, dijital hizmet kullanıcıları, topluluk yöneticileri, dijital pazarlama uzmanları";
    $meta_category = "sosyal medya platformları, dijital hesap yönetimi, telegram hesap satışı";
    $meta_content_tone = "profesyonel, bilgilendirici";
    $meta_reader_interest = "telegram üzerinde aktif kullanım için hesap arayan kullanıcılar";
    $meta_summary = "telegram platformu için hazır hesap seçenekleri ve uygun fiyatlar";
    $meta_topic_tags = "telegram hesap yönetimi, dijital hesap satışı";
    $meta_visual_content = "telegram ikonları";
}

// Yapısal veri (Structured Data) oluştur
$structuredDataHelper = new StructuredDataHelper();

// Kategori meta bilgilerini al
$categoryMetaData = StructuredDataHelper::getCategoryMetaData($currentCategorySlug, $currentCategoryData['name'] ?? 'Premium Hesaplar');

// Tüm hesapları al (yapısal veri için)
$allAccountsForSchema = $accountManager->getAccounts($initialFilters, 1, 1000); // Max 1000 hesap

// Yapısal veriyi oluştur
$structuredData = $structuredDataHelper->getCategoryStructuredData(
    $currentCategorySlug,
    $categoryMetaData['name'],
    $categoryMetaData['description'],
    $allAccountsForSchema
);

// CSRF token oluştur
$csrfToken = generateCSRFToken();

// Sayfa başlığını güncelle
$page_title = $categoryMetaData['name'];

include 'header.php';
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <div class="header-content">
            <h1><?php echo htmlspecialchars($categoryMetaData['name']); ?></h1>
            <p><?php echo htmlspecialchars($categoryMetaData['description']); ?></p>
        </div>
    </div>
</section>

<!-- Main Content -->
<section class="accounts-section">
    <div class="container">
        
        <!-- Filters & Search Card -->
        <div class="filters-card">
            <div class="filters-header">
                <h3><i class="fas fa-filter"></i> Filtreler</h3>
            </div>
            <div class="filters-content">
                <div class="filter-group">
                    <div class="filter-item">
                        <label for="sort">Sıralama:</label>
                        <select id="sort" name="sort" class="filter-select">
                            <option value="smart">Akıllı Sıralama</option>
                            <option value="date">Yeni Eklenenler</option>
                            <option value="price-low">Fiyat (Düşük-Yüksek)</option>
                            <option value="price-high">Fiyat (Yüksek-Düşük)</option>
                            <option value="popular">En Popüler</option>
                            <option value="rating">En Yüksek Puan</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label for="categories">Kategori:</label>
                        <select id="categories" name="categories" class="filter-select">
                        <option value="">Tüm Kategoriler</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" data-seo-slug="<?= htmlspecialchars($category['seo_slug'] ?? $category['slug']) ?>" <?php echo ($selectedCategory == $category['id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label for="priceRange">Fiyat:</label>
                        <select id="priceRange" name="priceRange" class="filter-select">
                            <option value="">Tüm Fiyatlar</option>
                            <option value="0-100">0<?= getCurrencySymbol() ?> - 100<?= getCurrencySymbol() ?></option>
                            <option value="100-500">100<?= getCurrencySymbol() ?> - 500<?= getCurrencySymbol() ?></option>
                            <option value="500-1000">500<?= getCurrencySymbol() ?> - 1000<?= getCurrencySymbol() ?></option>
                            <option value="1000-5000">1000<?= getCurrencySymbol() ?> - 5000<?= getCurrencySymbol() ?></option>
                            <option value="5000+">5000<?= getCurrencySymbol() ?>+</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label for="features">Özellikler:</label>
                        <select id="features" name="features" class="filter-select">
                            <option value="">Tüm Özellikler</option>
                            <option value="verified">Doğrulanmış</option>
                            <option value="premium">Premium</option>
                            <option value="featured">Öne Çıkan</option>
                            <option value="instant">Anında Teslimat</option>
                        </select>
                    </div>
                </div>
                
                <!-- Search Box inside filters -->
                <div class="search-section">
                    <label for="accountSearch">Arama:</label>
                    <div class="search-box-compact">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Hesap ara..." id="accountSearch">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Accounts Grid -->
        <div class="accounts-grid" id="accountsList">
                    <?php if (!empty($initialAccounts)): ?>
                        <?php foreach ($initialAccounts as $account): ?>
                            <?php
                            $stockStatus = getStockStatus($account['stock_quantity']);
                            $platformIcon = getPlatformIcon($account['platform']);
                            $price = formatPrice($account['price']);
                            $rating = $account['rating'] > 0 ? '<i class="fas fa-star rating-star"></i>' . $account['rating'] : '';
                            $verified = $account['is_verified'] ? '<i class="fas fa-check-circle verified-icon"></i>' : '';
                            $urgentClass = $stockStatus['status'] === 'low' ? 'urgent' : '';
                            $activeClass = $account === reset($initialAccounts) ? 'active' : '';
                            ?>
                            
                            <div class="account-card <?php echo $stockStatus['status'] === 'out' ? 'out-of-stock' : ''; ?>">
                                <?php if ($stockStatus['status'] === 'out'): ?>
                                    <div class="card-link disabled">
                                        <div class="card-media">
                                            <i class="<?php echo $platformIcon; ?>"></i>
                                        </div>
                                        <div class="card-content">
                                            <h3 class="card-title"><?php echo htmlspecialchars($account['title']); ?></h3>
                                            <div class="card-pricing">
                                                <span class="current-price"><?php echo $price; ?></span>
                                                <?php if ($account['old_price']): ?>
                                                    <span class="old-price"><?php echo formatPrice($account['old_price']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="stock-status <?php echo $urgentClass; ?> <?php echo $stockStatus['status'] === 'out' ? 'out' : ''; ?>">
                                                <i class="fas fa-box"></i> <?php echo $stockStatus['text']; ?>
                                            </div>
                                            <div class="card-badges">
                                                <?php if ($account['is_verified']): ?>
                                                    <span class="badge verified">Doğrulanmış</span>
                                                <?php endif; ?>
                                                <?php 
                                                $locationParts = explode(',', $account['location'] ?? 'Premium,Hesap');
                                                foreach($locationParts as $badge): 
                                                    $badge = trim($badge);
                                                    if($badge): ?>
                                                    <span class="badge platform"><?php echo htmlspecialchars($badge); ?></span>
                                                <?php endif; endforeach; ?>
                                            </div>
                                            <div class="out-of-stock-overlay">
                                                <i class="fas fa-ban"></i>
                                                <span>Stok Tükendi</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <a href="<?php echo URLHelper::getProductUrl($account['id']); ?>" class="card-link">
                                        <div class="card-media">
                                            <i class="<?php echo $platformIcon; ?>"></i>
                                        </div>
                                        <div class="card-content">
                                            <h3 class="card-title"><?php echo htmlspecialchars($account['title']); ?></h3>
                                            <div class="card-pricing">
                                                <span class="current-price"><?php echo $price; ?></span>
                                                <?php if ($account['old_price']): ?>
                                                    <span class="old-price"><?php echo formatPrice($account['old_price']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="stock-status <?php echo $urgentClass; ?> <?php echo $stockStatus['status'] === 'out' ? 'out' : ''; ?>">
                                                <i class="fas fa-box"></i> <?php echo $stockStatus['text']; ?>
                                            </div>
                                            <div class="card-badges">
                                                <?php if ($account['is_verified']): ?>
                                                    <span class="badge verified">Doğrulanmış</span>
                                                <?php endif; ?>
                                                <?php 
                                                $locationParts = explode(',', $account['location'] ?? 'Premium,Hesap');
                                                foreach($locationParts as $badge): 
                                                    $badge = trim($badge);
                                                    if($badge): ?>
                                                    <span class="badge platform"><?php echo htmlspecialchars($badge); ?></span>
                                                <?php endif; endforeach; ?>
                                            </div>
                                        </div>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

        <!-- Load More Section -->
        <div class="load-more-section" id="loadMoreSection">
            <button class="btn-load-more" id="loadMoreBtn" onclick="loadMoreAccounts()" style="<?php echo ceil($totalAccounts / ITEMS_PER_PAGE) > 1 ? '' : 'display: none;'; ?>">
                <i class="fas fa-plus"></i>
                <span>Daha Fazla Hesap Yükle</span>
            </button>
            
            <div class="loading-indicator" id="loadingIndicator" style="display: none;">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Yükleniyor...</span>
            </div>
            
            <div class="no-more-results" id="noMoreResults" style="display: none;">
                <i class="fas fa-check-circle"></i>
                <span>Tüm hesaplar yüklendi</span>
                <p>Başka hesap bulunmuyor.</p>
            </div>
        </div>
    </div>
</section>

<!-- Hidden Form for CSRF -->
<form id="hiddenForm" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
</form>

<script>
// Global değişkenler
let currentPage = 1;
let isLoading = false;
let hasMorePages = <?php echo ceil($totalAccounts / ITEMS_PER_PAGE) > 1 ? 'true' : 'false'; ?>;
let currentFilters = {};
let debounceTimer = null;

// Sayfa yüklendiğinde çalışacak fonksiyonlar
document.addEventListener('DOMContentLoaded', function() {
    initializeFilters();
    initializeSearch();
    initializeAccountSelection();
    initializePurchaseButtons();
    updateLoadMoreButton();
});

// Filtreleri başlat
function initializeFilters() {
    // Tüm select filtrelerini dinle
    const filterSelects = document.querySelectorAll('.filter-select');
    console.log('Found filter selects:', filterSelects.length);
    
    filterSelects.forEach(select => {
        console.log('Adding event listener to:', select.id);
        select.addEventListener('change', function(e) {
            console.log('Filter changed:', e.target.id, '=', e.target.value);
            debounceFilterChange();
        });
    });
}

// Arama fonksiyonunu başlat
function initializeSearch() {
    const searchInput = document.getElementById('accountSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                applyFilters();
            }, 300);
        });
    }
}

// Kart etkileşimlerini başlat
function initializeAccountSelection() {
    // Artık gerekli değil - kartlar direkt view.php'ye yönlendiriyor
}

// Load more button durumunu güncelle
function updateLoadMoreButton() {
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    const noMoreResults = document.getElementById('noMoreResults');
    
    if (hasMorePages) {
        loadMoreBtn.style.display = 'flex';
        noMoreResults.style.display = 'none';
    } else {
        loadMoreBtn.style.display = 'none';
        // Eğer en az bir sayfa yüklendiyse "tüm hesaplar yüklendi" mesajını göster
        if (currentPage > 1) {
            noMoreResults.style.display = 'flex';
        }
    }
}

// Satın alma butonlarını başlat
function initializePurchaseButtons() {
    // Artık HTML'de onclick ile hallediliyor
    console.log('Purchase buttons ready');
}

// Debounced filter change
function debounceFilterChange() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        applyFilters();
    }, 300);
}

// Filtreleri uygula
function applyFilters() {
    if (isLoading) return;
    
    currentPage = 1;
    currentFilters = getFilters();
    
    console.log('Current filters:', currentFilters);
    
    // URL'yi güncelle (kategori seçimi için)
    updateURL();
    
    showLoading();
    
    fetch('ajax_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: buildFormData({
            action: 'get_accounts',
            page: currentPage,
            ...currentFilters
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            document.getElementById('accountsList').innerHTML = data.html;
            
            hasMorePages = data.has_more;
            updateLoadMoreButton();
            
            // Eğer sonuç yoksa mesaj göster
            if (!data.html || data.html.trim() === '') {
                showNoResults();
            }
        } else {
            showError(data.message || 'Hesaplar yüklenirken hata oluştu.');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Filter error:', error);
        showError('Bağlantı hatası oluştu.');
    });
}

// Mevcut filtreleri al
function getFilters() {
    const filters = {};
    
    // Arama terimi
    const searchInput = document.getElementById('accountSearch');
    if (searchInput && searchInput.value.trim()) {
        filters.search = searchInput.value.trim();
    }
    
    // Filtreler
    const sortSelect = document.getElementById('sort');
    if (sortSelect && sortSelect.value) {
        filters.sort = sortSelect.value;
    }
    
    const categorySelect = document.getElementById('categories');
    if (categorySelect && categorySelect.value) {
        filters.category_id = categorySelect.value;
    }
    
    
    const priceRangeSelect = document.getElementById('priceRange');
    if (priceRangeSelect && priceRangeSelect.value) {
        filters.price_range = priceRangeSelect.value;
    }
    
    const featureSelect = document.getElementById('features');
    if (featureSelect && featureSelect.value) {
        filters.feature = featureSelect.value;
    }
    
    console.log('getFilters result:', filters);
    
    return filters;
}

// URL'yi güncelle (SEO uyumlu)
function updateURL() {
    const categorySelect = document.getElementById('categories');
    if (categorySelect && categorySelect.value) {
        // Kategori seçilmişse, SEO slug'ını al
        const selectedOption = categorySelect.options[categorySelect.selectedIndex];
        const categorySlug = selectedOption.getAttribute('data-seo-slug');
        
        if (categorySlug) {
            const newUrl = '/' + categorySlug;
            window.history.pushState({}, '', newUrl);
        }
    } else {
        // Kategori seçilmemişse ana hesaplar sayfasına dön
        window.history.pushState({}, '', '/tum-hesaplar');
    }
}

// Daha fazla hesap yükle
function loadMoreAccounts() {
    if (isLoading || !hasMorePages) {
        console.log('Load more blocked - isLoading:', isLoading, 'hasMorePages:', hasMorePages);
        return;
    }
    
    currentPage++;
    isLoading = true;
    
    console.log('Loading page:', currentPage, 'with filters:', currentFilters);
    showLoadingIndicator();
    
    fetch('ajax_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: buildFormData({
            action: 'get_accounts',
            page: currentPage,
            ...currentFilters
        })
    })
    .then(response => response.json())
    .then(data => {
        isLoading = false;
        hideLoadingIndicator();
        
        console.log('Load more response:', data);
        
        if (data.success && data.html && data.html.trim()) {
            document.getElementById('accountsList').insertAdjacentHTML('beforeend', data.html);
            hasMorePages = data.has_more;
            updateLoadMoreButton();
            console.log('Added more accounts. Has more pages:', hasMorePages);
        } else {
            hasMorePages = false;
            updateLoadMoreButton();
            console.log('No more accounts to load or empty response');
        }
    })
    .catch(error => {
        isLoading = false;
        hideLoadingIndicator();
        console.error('Load more error:', error);
    });
}

// Hesap seç
function selectAccount(accountItem) {
    // Diğer hesaplardan active class'ını kaldır
    document.querySelectorAll('.account-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Seçilen hesaba active class ekle
    accountItem.classList.add('active');
    
    // Hesap ID'sini al
    const accountId = accountItem.getAttribute('data-account');
    
    // Hesap detayını yükle
    loadAccountDetail(accountId);
    
    // Görüntülenme sayısını artır
    incrementAccountView(accountId);
}

// Hesap detayını yükle
function loadAccountDetail(accountId) {
    fetch('ajax_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: buildFormData({
            action: 'get_account_detail',
            account_id: accountId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('accountDetails').innerHTML = data.html;
        } else {
            showError(data.message || 'Hesap detayı yüklenirken hata oluştu.');
        }
    })
    .catch(error => {
        console.error('Account detail error:', error);
        showError('Hesap detayı yüklenirken hata oluştu.');
    });
}

// Görüntülenme sayısını artır
function incrementAccountView(accountId) {
    fetch('ajax_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: buildFormData({
            action: 'increment_view',
            account_id: accountId
        })
    })
    .catch(error => {
        console.error('Increment view error:', error);
    });
}

// Bookmark toggle
function toggleBookmark(bookmarkBtn) {
    const icon = bookmarkBtn.querySelector('i');
    if (icon.classList.contains('far')) {
        icon.classList.remove('far');
        icon.classList.add('fas');
        bookmarkBtn.classList.add('active');
        showToast('Hesap favorilere eklendi');
    } else {
        icon.classList.remove('fas');
        icon.classList.add('far');
        bookmarkBtn.classList.remove('active');
        showToast('Hesap favorilerden çıkarıldı');
    }
}

// Quantity değiştir
function handleQuantityChange(btn) {
    const isPlus = btn.classList.contains('plus');
    const input = btn.parentNode.querySelector('.qty-input');
    const currentValue = parseInt(input.value);
    const max = parseInt(input.getAttribute('max'));
    const min = parseInt(input.getAttribute('min'));
    
    if (isPlus && currentValue < max) {
        input.value = currentValue + 1;
    } else if (!isPlus && currentValue > min) {
        input.value = currentValue - 1;
    }
    
    updateTotalPrice(input);
}

// Quantity input değişikliği
function handleQuantityInputChange(input) {
    const max = parseInt(input.getAttribute('max'));
    const min = parseInt(input.getAttribute('min'));
    let value = parseInt(input.value);
    
    if (isNaN(value) || value < min) value = min;
    if (value > max) value = max;
    
    input.value = value;
    updateTotalPrice(input);
}

// Toplam fiyatı güncelle
function updateTotalPrice(input) {
    const quantity = parseInt(input.value);
    const detailCard = input.closest('.account-detail-card');
    const priceElement = detailCard.querySelector('.current-price');
            const priceText = priceElement.textContent.replace(/[₺$€]/g, '').replace(/\./g, '');
    const unitPrice = parseInt(priceText);
    const total = quantity * unitPrice;
    
    const totalPriceElement = input.closest('.purchase-section').querySelector('.total-price strong');
            totalPriceElement.textContent = total.toLocaleString('tr-TR') + '<?= getCurrencySymbol() ?>';
}

// Satın alma işlemi
function handlePurchase(btn) {
    const accountId = btn.getAttribute('data-account-id');
    const quantityInput = btn.closest('.purchase-section').querySelector('.qty-input');
    const quantity = parseInt(quantityInput.value);
    
    // Loading state
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> İşleniyor...';
    btn.disabled = true;
    
    // Simulate purchase process
    setTimeout(() => {
        showToast('Satın alma işlemi başlatıldı! Ödeme sayfasına yönlendiriliyorsunuz...');
        btn.innerHTML = originalText;
        btn.disabled = false;
        
        // Gerçek uygulamada burada ödeme sayfasına yönlendirme yapılacak
        // window.location.href = 'checkout.php?account=' + accountId + '&qty=' + quantity;
    }, 2000);
}

// Sepete ekle
function handleAddToCart(btn) {
    const accountId = btn.getAttribute('data-account-id');
    const quantityInput = btn.closest('.purchase-section').querySelector('.qty-input');
    const quantity = parseInt(quantityInput.value);
    
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i> Sepete Eklendi';
    btn.style.background = 'rgba(16, 185, 129, 0.2)';
    btn.style.color = '#10b981';
    btn.style.borderColor = '#10b981';
    
    showToast('Ürün sepete eklendi');
    
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.style.background = '';
        btn.style.color = '';
        btn.style.borderColor = '';
    }, 3000);
}

// Form data builder
function buildFormData(data) {
    const csrfToken = document.querySelector('input[name="csrf_token"]').value;
    const formData = new URLSearchParams();
    
    formData.append('csrf_token', csrfToken);
    
    for (const key in data) {
        if (Array.isArray(data[key])) {
            data[key].forEach(value => {
                formData.append(key + '[]', value);
            });
        } else {
            formData.append(key, data[key]);
        }
    }
    
    return formData;
}

// Loading göster
function showLoading() {
    document.getElementById('accountsList').innerHTML = `
        <div class="loading-accounts">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Hesaplar yükleniyor...</p>
        </div>
    `;
}

// Loading gizle
function hideLoading() {
    const loadingEl = document.querySelector('.loading-accounts');
    if (loadingEl) {
        loadingEl.remove();
    }
}

// Loading indicator göster
function showLoadingIndicator() {
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    const loadingIndicator = document.getElementById('loadingIndicator');
    
    loadMoreBtn.style.display = 'none';
    loadingIndicator.style.display = 'flex';
}

// Loading indicator gizle
function hideLoadingIndicator() {
    const loadingIndicator = document.getElementById('loadingIndicator');
    loadingIndicator.style.display = 'none';
}

// Sonuç bulunamadı mesajı
function showNoResults() {
    document.getElementById('accountsList').innerHTML = `
        <div class="no-results">
            <i class="fas fa-search"></i>
            <h3>Sonuç bulunamadı</h3>
            <p>Arama kriterlerinize uygun hesap bulunamadı. Filtreleri değiştirerek tekrar deneyin.</p>
        </div>
    `;
}


// Hata mesajı göster
function showError(message) {
    showToast(message, 'error');
}

// Toast mesajı göster
function showToast(message, type = 'success') {
    // Mevcut toast'ları kaldır
    const existingToasts = document.querySelectorAll('.toast');
    existingToasts.forEach(toast => toast.remove());
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    // Toast'ı göster
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    // Toast'ı gizle
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 4000);
}
</script>

<style>
/* Toast Styles */
.account-badges {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-top: 8px;
}

.account-badge {
    display: inline-block;
    padding: 3px 8px;
    background: rgba(108, 99, 255, 0.1);
    border: 1px solid rgba(108, 99, 255, 0.2);
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    color: var(--primary);
    white-space: nowrap;
}
.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 12px;
    padding: 15px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: var(--light);
    font-weight: 500;
    box-shadow: var(--shadow);
    backdrop-filter: blur(10px);
    z-index: 10000;
    transform: translateX(100%);
    opacity: 0;
    transition: all 0.3s ease;
}

.toast.show {
    transform: translateX(0);
    opacity: 1;
}

.toast-success {
    border-left: 4px solid #10b981;
}

.toast-success i {
    color: #10b981;
}

.toast-error {
    border-left: 4px solid #ef4444;
}

.toast-error i {
    color: #ef4444;
}

/* Loading Styles */
.loading-accounts {
    text-align: center;
    padding: 60px 20px;
    color: var(--gray);
}

.loading-accounts i {
    font-size: 2rem;
    color: var(--primary);
    margin-bottom: 15px;
}

.loading-accounts p {
    font-size: 1.1rem;
}

/* No Results Styles */
.no-results {
    text-align: center;
    padding: 80px 40px;
    color: var(--gray);
}

.no-results i {
    font-size: 4rem;
    color: var(--primary);
    margin-bottom: 25px;
    opacity: 0.5;
}

.no-results h3 {
    font-size: 1.5rem;
    color: var(--light);
    margin-bottom: 15px;
}

.no-results p {
    font-size: 1rem;
    line-height: 1.6;
}

/* Filter Active States */
.filter-link-btn.active {
    background: rgba(255, 101, 132, 0.2);
    border-color: var(--secondary);
    color: var(--secondary);
}

.bookmark-btn.active i {
    color: var(--accent);
}

/* Loading Indicator */
.loading-indicator {
    text-align: center;
    padding: 20px;
    color: var(--gray);
    border-top: 1px solid var(--card-border);
    margin-top: 20px;
}

.loading-indicator i {
    margin-right: 10px;
    color: var(--primary);
}

/* Account Detail Animations */
.account-detail-card {
    animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Hover Effects */
.account-item:hover .account-avatar {
    transform: scale(1.05);
}

.account-item:hover .bookmark-btn {
    opacity: 1;
}

.bookmark-btn {
    opacity: 0.6;
    transition: var(--transition);
}

.bookmark-btn:hover {
    transform: scale(1.1);
}

/* Purchase Section Enhancements */
.purchase-buttons .btn-buy-now:disabled,
.purchase-buttons .btn-add-cart:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

.qty-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

/* Accessibility Improvements */
.account-item:focus-within {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
}

.filter-btn:focus,
.qty-btn:focus,
.btn-buy-now:focus,
.btn-add-cart:focus {
    outline: 2px solid var(--accent);
    outline-offset: 2px;
}

/* Search Highlight */
.search-highlight {
    background: rgba(255, 255, 0, 0.3);
    padding: 2px 4px;
    border-radius: 3px;
}

/* Stock Status Colors */
.stock-status {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.stock-status.urgent {
    color: #f59e0b;
    background: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.2);
}

.stock-status:not(.urgent):not(.out) {
    color: #10b981;
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.stock-status.out {
    color: #ef4444;
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.2);
}

/* Price Animations */
.account-price {
    font-weight: 700;
    color: var(--accent);
    font-size: 16px;
    transition: var(--transition);
}

.account-item:hover .account-price {
    transform: scale(1.05);
}

/* Rating Stars */
.rating-star {
    color: #fbbf24;
    margin-right: 4px;
}

/* Verified Icon */
.verified-icon {
    color: #10b981;
    margin-left: 4px;
}

/* Responsive Improvements */
@media (max-width: 1200px) {
    .accounts-layout {
        grid-template-columns: 380px 1fr;
        gap: 30px;
    }
}

@media (max-width: 992px) {
    .accounts-layout {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .accounts-sidebar {
        position: relative;
        top: 0;
        max-height: 500px;
        overflow-y: auto;
    }
    
    .filters-bar {
        flex-direction: column;
        gap: 15px;
    }
    
    .filters-left {
        width: 100%;
        justify-content: space-between;
        flex-wrap: wrap;
    }
    
    .filters-right {
        width: 100%;
        justify-content: space-between;
    }
    
    .filter-dropdown {
        flex: 1;
        min-width: 150px;
    }
}

@media (max-width: 768px) {
    .accounts-section {
        padding: 30px 0;
    }

    
    .accounts-sidebar {
        padding: 20px;
        max-height: 400px;
    }
    
    .account-details .detail-body {
        padding: 20px;
    }
    
    .detail-header {
        padding: 20px;
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .price-section {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .specs-grid {
        grid-template-columns: 1fr;
    }
    
    .features-list {
        grid-template-columns: 1fr;
    }
    
    .purchase-buttons {
        flex-direction: column;
        gap: 10px;
    }
    
    .security-info {
        flex-direction: column;
        gap: 15px;
    }
    
    .security-item {
        flex-direction: row;
        justify-content: center;
    }
    
    .toast {
        right: 10px;
        left: 10px;
        top: 10px;
    }
}

@media (max-width: 576px) {
    .filters-left {
        flex-direction: column;
        gap: 10px;
    }
    
    .filter-dropdown,
    .filter-link {
        width: 100%;
    }
    
    .filter-btn {
        width: 100%;
        justify-content: space-between;
    }
    
    .account-item {
        padding: 15px;
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
    
    .account-info {
        order: 2;
    }
    
    .account-actions {
        order: 3;
        display: flex;
        justify-content: space-between;
        width: 100%;
    }
    
    .bookmark-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        opacity: 1;
    }
    
    .current-price {
        font-size: 1.5rem;
    }
    
    .quantity-selector {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
    
    .quantity-controls {
        align-self: center;
    }
}

/* Print Styles */
@media print {
    .filters-bar,
    .search-container,
    .bookmark-btn,
    .purchase-section,
    .toast {
        display: none !important;
    }
    
    .accounts-layout {
        display: block;
    }
    
    .account-details {
        page-break-inside: avoid;
    }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
    .account-item {
        border: 2px solid var(--light);
    }
    
    .account-item.active {
        border-color: var(--accent);
        background: rgba(66, 226, 184, 0.2);
    }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    .account-detail-card {
        animation: none;
    }
    
    .toast {
        transition: none;
    }
    
    * {
        transition-duration: 0.01ms !important;
    }
}

/* Dark Mode Enhancements */
@media (prefers-color-scheme: dark) {
    .search-highlight {
        background: rgba(255, 255, 0, 0.2);
    }
}
.account-item {
    position: relative;
}
.account-badges-wrapper {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 2;
    display: flex;
    flex-direction: column;
    gap: 6px;
    align-items: flex-end;
    pointer-events: none;
}
.account-badges {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.account-badge {
    background: rgba(108, 99, 255, 0.1);
    border: 1px solid rgba(108, 99, 255, 0.2);
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    color: var(--primary);
    padding: 3px 8px;
    margin-bottom: 0;
    white-space: nowrap;
}

    /* Accounts Left Section */
    .accounts-left-section {
        display: flex;
        flex-direction: column;
    }
    
    /* Search Container - Fixed at top */
    .search-container {
        padding: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        z-index: 1;
        background-color: var(--card-bg, white);
        margin-bottom: 10px;
        border-radius: 8px;
    }

    .accounts-sidebar {
        height: 140vh;
        max-height: 1200px;
        min-height: 800px;
        overflow-y: auto;
        overflow-x: hidden;
        scrollbar-width: thin;
        scrollbar-color: var(--primary) transparent;
    }

.accounts-sidebar::-webkit-scrollbar {
    width: 6px;
}

.accounts-sidebar::-webkit-scrollbar-track {
    background: transparent;
}

.accounts-sidebar::-webkit-scrollbar-thumb {
    background: var(--primary);
    border-radius: 3px;
}

.accounts-sidebar::-webkit-scrollbar-thumb:hover {
    background: var(--accent);
}

.accounts-list {
    padding-bottom: 20px;
}

/* Page Header Modern Design */
.page-header {
    background: linear-gradient(135deg, 
        rgba(var(--primary-rgb), 0.95) 0%, 
        rgba(var(--accent-rgb), 0.90) 100%),
        radial-gradient(circle at 30% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
    position: relative;
    padding: 60px 0 50px 0;
    overflow: hidden;
}

.page-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="20" cy="20" r="1" fill="%23ffffff" opacity="0.05"/><circle cx="80" cy="40" r="1.5" fill="%23ffffff" opacity="0.03"/><circle cx="40" cy="80" r="1" fill="%23ffffff" opacity="0.04"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
    pointer-events: none;
}

.header-content {
    text-align: center;
    position: relative;
    z-index: 2;
    max-width: 800px;
    margin: 0 auto;
}

.page-header h1 {
    font-size: 2.5rem;
    font-weight: 800;
    color: white;
    margin-bottom: 16px;
    text-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
    letter-spacing: -0.02em;
    line-height: 1.1;
}

.page-header p {
    font-size: 1.1rem;
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.6;
    font-weight: 400;
    text-shadow: 0 1px 10px rgba(0, 0, 0, 0.1);
    margin: 0;
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-header {
        padding: 40px 0 35px 0;
    }
    
    .page-header h1 {
        font-size: 2rem;
        margin-bottom: 12px;
    }
    
    .page-header p {
        font-size: 1rem;
        padding: 0 20px;
    }
}

@media (max-width: 480px) {
    .page-header h1 {
        font-size: 1.75rem;
    }
    
    .page-header p {
        font-size: 0.95rem;
    }
}

/* Modern Filter Bar Styles */
.filters-bar {
    background: var(--card-bg, white);
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--card-border, #e5e7eb);
}

.filters-center {
    display: flex;
    justify-content: center;
    width: 100%;
}

.filter-group {
    display: flex;
    gap: 24px;
    align-items: center;
    flex-wrap: wrap;
    justify-content: center;
}

.filter-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 140px;
}

.filter-item label {
    font-size: 12px;
    font-weight: 600;
    color: var(--gray, #6b7280);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-select {
    padding: 8px 12px;
    border: 1px solid var(--card-border, #d1d5db);
    border-radius: 8px;
    background: var(--card-bg, white);
    color: var(--light, #1f2937);
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
    cursor: pointer;
    outline: none;
}

.filter-select:hover {
    border-color: var(--primary, #6366f1);
}

.filter-select:focus {
    border-color: var(--primary, #6366f1);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.filter-select option {
    padding: 8px;
    background: var(--card-bg, white);
    color: var(--light, #1f2937);
}

/* Responsive Filter Design */
@media (max-width: 1024px) {
    .filter-group {
        gap: 16px;
    }
    
    .filter-item {
        min-width: 120px;
    }
}

@media (max-width: 768px) {
    .filters-bar {
        padding: 12px 16px;
    }
    
    .filter-group {
        gap: 12px;
        justify-content: flex-start;
    }
    
    .filter-item {
        min-width: 100px;
        flex: 1;
    }
    
    .filter-select {
        font-size: 13px;
        padding: 6px 10px;
    }
}

@media (max-width: 480px) {
    .filter-group {
        flex-direction: column;
        align-items: stretch;
        gap: 16px;
    }
    
    .filter-item {
        min-width: unset;
        width: 100%;
    }
}
/* Theme-Compatible Card Design */
.search-container {
    max-width: 600px;
    margin: 0 auto 30px auto;
    padding: 0 20px;
}

.search-box {
    position: relative;
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 18px;
    padding: 15px 20px 15px 55px;
    box-shadow: var(--inner-shadow);
    backdrop-filter: blur(10px);
    transition: var(--transition);
}

.search-box:focus-within {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.15);
}

.search-box i {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--accent);
    font-size: 1.2rem;
}

.search-box input {
    width: 100%;
    border: none;
    outline: none;
    background: transparent;
    color: var(--light);
    font-size: 1rem;
    font-weight: 500;
}

.search-box input::placeholder {
    color: var(--gray);
}

.accounts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
    padding: 0 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.account-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 16px;
    overflow: hidden;
    transition: var(--transition);
    box-shadow: var(--inner-shadow);
    backdrop-filter: blur(10px);
    position: relative;
    height: 280px;
    display: flex;
    flex-direction: column;
}

.account-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow);
    border-color: var(--primary);
}

.card-link {
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.card-media {
    position: relative;
    padding: 20px 16px 12px;
    text-align: center;
    background: linear-gradient(135deg, 
        rgba(108, 99, 255, 0.15) 0%, 
        rgba(66, 226, 184, 0.10) 100%);
    border-bottom: 1px solid var(--card-border);
    flex-shrink: 0;
}

.card-media i {
    font-size: 2rem;
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    display: block;
    transition: var(--transition);
}

.account-card:hover .card-media i {
    transform: scale(1.1) rotate(5deg);
}

.card-content {
    padding: 16px;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.card-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 8px;
}

.badge {
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 3px;
    white-space: nowrap;
    backdrop-filter: blur(10px);
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
}

.badge.verified {
    background: linear-gradient(135deg, rgba(66, 226, 184, 0.9), rgba(16, 185, 129, 0.9));
    color: white;
    border: 1px solid rgba(66, 226, 184, 0.5);
}

.badge.platform {
    background: linear-gradient(135deg, rgba(108, 99, 255, 0.9), rgba(86, 79, 216, 0.9));
    color: white;
    border: 1px solid rgba(108, 99, 255, 0.5);
}

.card-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--light);
    margin-bottom: 12px;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    font-family: 'Montserrat', sans-serif;
}

.card-pricing {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
}

.current-price {
    font-size: 1.4rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    font-family: 'Montserrat', sans-serif;
}

.old-price {
    font-size: 0.9rem;
    color: var(--gray);
    text-decoration: line-through;
    opacity: 0.7;
}

.stock-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 600;
    background: linear-gradient(135deg, rgba(66, 226, 184, 0.2), rgba(16, 185, 129, 0.2));
    color: var(--accent);
    border: 1px solid rgba(66, 226, 184, 0.3);
    backdrop-filter: blur(10px);
}

.stock-status.urgent {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(217, 119, 6, 0.2));
    color: #f59e0b;
    border: 1px solid rgba(245, 158, 11, 0.3);
}

.stock-status.out {
    background: linear-gradient(135deg, rgba(255, 101, 132, 0.2), rgba(239, 68, 68, 0.2));
    color: var(--secondary);
    border: 1px solid rgba(255, 101, 132, 0.3);
}


.loading-indicator {
    text-align: center;
    padding: 40px 20px;
    color: var(--gray);
    max-width: 1400px;
    margin: 20px auto 0;
}

.loading-indicator i {
    margin-right: 10px;
    color: var(--primary);
    font-size: 1.2rem;
}

/* No Results */
.no-results {
    text-align: center;
    padding: 80px 40px;
    color: var(--gray);
    max-width: 600px;
    margin: 0 auto;
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 20px;
    backdrop-filter: blur(10px);
    box-shadow: var(--inner-shadow);
}

.no-results i {
    font-size: 4rem;
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    margin-bottom: 25px;
    opacity: 0.8;
}

.no-results h3 {
    font-size: 1.5rem;
    color: var(--light);
    margin-bottom: 15px;
    font-family: 'Montserrat', sans-serif;
}

.no-results p {
    font-size: 1rem;
    line-height: 1.6;
}

/* Responsive Design */
@media (max-width: 768px) {
    .accounts-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 14px;
        padding: 0 15px;
    }
    
    .account-card {
        height: 260px;
    }
    
    .card-media {
        padding: 16px 12px 10px;
    }
    
    .card-media i {
        font-size: 1.8rem;
    }
    
    .card-content {
        padding: 14px;
    }
    
    .card-title {
        font-size: 1rem;
        margin-bottom: 10px;
    }
    
    .current-price {
        font-size: 1.2rem;
    }
    
    .search-container {
        padding: 0 15px;
        margin-bottom: 25px;
    }
}

@media (max-width: 480px) {
    .accounts-grid {
        grid-template-columns: 1fr;
        padding: 0 10px;
        gap: 12px;
    }
    
    .account-card {
        height: 240px;
    }
    
    .card-media {
        padding: 14px 10px 8px;
    }
    
    .card-media i {
        font-size: 1.6rem;
    }
    
    .card-content {
        padding: 12px;
    }
    
    .card-title {
        font-size: 0.95rem;
    }
    
    .current-price {
        font-size: 1.1rem;
    }
}

/* Animation for new cards */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.account-card {
    animation: slideInUp 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

/* Loading Animation */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.6;
    }
}

.loading-accounts {
    text-align: center;
    padding: 60px 20px;
    color: var(--gray);
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 20px;
    backdrop-filter: blur(10px);
    max-width: 600px;
    margin: 0 auto;
}

.loading-accounts i {
    font-size: 2rem;
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    margin-bottom: 15px;
    animation: pulse 1.5s ease-in-out infinite;
}

.loading-accounts p {
    font-size: 1.1rem;
}

</style>

<?php
// Helper function for generating account detail HTML (server-side)
function generateAccountDetailHTML($account, $features) {
    $stockStatus = getStockStatus($account['stock_quantity']);
    $platformIcon = getPlatformIcon($account['platform']);
    $price = formatPrice($account['price']);
    $oldPrice = $account['old_price'] ? '<span class="old-price">' . formatPrice($account['old_price']) . '</span>' : '';
    $verified = $account['is_verified'] ? '<span class="badge verified"><i class="fas fa-check-circle"></i> Doğrulanmış</span>' : '';
    $stockBadge = '<span class="badge stock-available"><i class="fas fa-box"></i> ' . $stockStatus['text'] . '</span>';
    
    // Özellikler listesi
    $featuresList = '';
    $defaultFeatures = [
        'Tam doğrulanmış hesap',
        'Yüksek güvenlik',
        'Anında teslimat',
        '7/24 destek',
        $account['warranty_days'] . ' gün garanti'
    ];
    
    foreach ($defaultFeatures as $feature) {
        $featuresList .= '<li><i class="fas fa-check"></i> ' . $feature . '</li>';
    }
    
    // Teknik özellikler
    $specs = '';
    $defaultSpecs = [];
    
    foreach ($features as $feature) {
        $defaultSpecs[$feature['feature_name']] = $feature['feature_value'];
    }
    
    foreach ($defaultSpecs as $label => $value) {
        $specs .= '
        <div class="spec-item">
            <span class="spec-label">' . $label . ':</span>
            <span class="spec-value">' . $value . '</span>
        </div>';
    }
    
    return '
    <div class="account-detail-card" id="account-' . $account['id'] . '">
        <div class="detail-header">
            <div class="detail-avatar">
                <i class="' . $platformIcon . '"></i>
            </div>
            <div class="detail-title">
                <h2>' . htmlspecialchars($account['title']) . '</h2>
                <p>' . htmlspecialchars($account['account_type']) . '</p>
                <div class="detail-badges">
                    ' . $verified . '
                    ' . $stockBadge . '
                </div>
            </div>
        </div>

        <div class="detail-body">
            <div class="price-section">
                <div class="price-info">
                    <span class="current-price">' . $price . '</span>
                    ' . $oldPrice . '
                </div>
                <div class="stock-info">
                    <i class="fas fa-boxes"></i>
                    <span>' . $account['stock_quantity'] . ' adet stokta</span>
                </div>
            </div>

            <div class="description-section">
                <h3><i class="fas fa-info-circle"></i> Açıklama</h3>
                <p>' . nl2br(htmlspecialchars($account['description'])) . '</p>
            </div>

            <div class="features-section">
                <h3><i class="fas fa-star"></i> Özellikler</h3>
                <ul class="features-list">
                    ' . $featuresList . '
                </ul>
            </div>

            <div class="specs-section">
                <h3><i class="fas fa-cog"></i> Teknik Özellikler</h3>
                <div class="specs-grid">
                    ' . $specs . '
                </div>
            </div>

            <div class="purchase-section">
                <div class="quantity-selector">
                    <label>Adet:</label>
                    <div class="quantity-controls">
                        <button class="qty-btn minus">-</button>
                        <input type="number" value="1" min="1" max="' . $account['stock_quantity'] . '" class="qty-input">
                        <button class="qty-btn plus">+</button>
                    </div>
                </div>

                <div class="total-price">
                    <span>Toplam: <strong>' . $price . '</strong></span>
                </div>

                <div class="purchase-buttons">
                    <button class="btn-buy-now" data-account-id="' . $account['id'] . '">
                        <i class="fas fa-shopping-cart"></i>
                        Hemen Satın Al
                    </button>
                    <button class="btn-add-cart" data-account-id="' . $account['id'] . '">
                        <i class="fas fa-plus"></i>
                        Sepete Ekle
                    </button>
                </div>

                <div class="security-info">
                    <div class="security-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>256-bit SSL güvenliği</span>
                    </div>
                    <div class="security-item">
                        <i class="fas fa-clock"></i>
                        <span>Anında teslimat</span>
                    </div>
                    <div class="security-item">
                        <i class="fas fa-undo"></i>
                        <span>' . $account['warranty_days'] . ' gün garanti</span>
                    </div>
                </div>
            </div>
        </div>
    </div>';
}

include 'footer.php';
?>
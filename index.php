<?php 
require_once 'config.php';
require_once 'functions.php';

// Meta Tags for SEO and AI
$meta_title = "Facebook Reklam Hesabı Satın Al | Business Manager & Reklam Hesapları";
$meta_description = "Facebook reklam hesabı satın al, Business Manager hesap, doğrulanmış reklam hesapları ve kimlik onaylı Facebook hesapları en uygun fiyatlarla!";
$meta_keywords = "facebook reklam hesabı satın al, business manager hesap, doğrulanmış reklam hesapları, instagram hesap, tiktok hesap, gmail, outlook, güvenli alışveriş, anında teslimat";
$meta_ai_intent = "Facebook reklam hesabı satın al ile sosyal medya hesaplarını güvenli ve hızlı şekilde edinmek isteyen kullanıcıları bilgilendirme";
$meta_ai_intent_2 = "facebook reklam hesabı, instagram, tiktok ve diğer sosyal medya hesaplarını güvenli ve hızlı şekilde satın alma hizmeti";
$meta_ai_subtopics = "facebook reklam hesabı satın al, business manager hesapları, doğrulanmış reklam hesapları, instagram hesapları, tiktok hesapları, gmail ve outlook hesapları, premium sosyal medya hesapları";
$meta_audience = "bireysel kullanıcılar, sosyal medya yöneticileri, dijital pazarlama profesyonelleri, reklam verenler";
$meta_category = "sosyal medya hesap satışı, dijital pazarlama";
$meta_content_tone = "güven verici, profesyonel, satış odaklı, bilgilendirici";
$meta_reader_interest = "facebook reklam hesabı satın almak isteyen kullanıcılar, business manager ve reklam hesaplarını hızlı ve güvenli edinmek isteyenler, premium hesaplara erişim isteyenler";
$meta_summary = "facebook reklam hesabı satın al, business manager ve doğrulanmış reklam hesapları satışı";
$meta_topic_tags = "facebook reklam hesabı, business manager, sosyal medya, instagram, tiktok, gmail, outlook, premium hesap satışı";
$meta_visual_content = "facebook reklam hesabı görselleri, business manager";

// Site ayarlarını çek
$heroTitle = $siteSettings->get('hero_title', 'Premium Sosyal Medya Hesapları');
$heroSubtitle = $siteSettings->get('hero_subtitle', 'En Kaliteli Sosyal Medya Hesapları');
$heroDescription = $siteSettings->get('hero_description', 'BusinessHesap güvencesiyle doğrulanmış, yüksek limitli ve güvenli hesapları keşfedin.');
$heroButtonText = $siteSettings->get('hero_button_text', 'Hemen Satın Al');
$heroButtonUrl = $siteSettings->get('hero_button_url', 'tum-hesaplar');
$heroBackground = $siteSettings->get('hero_background', 'https://images.unsplash.com/photo-1551836022-d5d88e9218df?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80');
$heroStats = $siteSettings->getJsonSetting('hero_stats', [
    ['number' => '60.620+', 'label' => 'Satılan Hesap'],
    ['number' => '1.300+', 'label' => 'Stokta Hesap'],
    ['number' => '9.281+', 'label' => 'Mutlu Müşteri'],
    ['number' => '99.8%', 'label' => 'Memnuniyet Oranı']
]);

$page_title = "Anasayfa";

// Account Manager sınıfını başlat
$accountManager = new AccountManager();

// Ana sayfa için sınırlı FAQ'ları çek
try {
    $homeFaqs = $accountManager->getHomeFaqs(6);
} catch (Exception $e) {
    error_log("Home FAQs fetch error: " . $e->getMessage());
    $homeFaqs = [];
}
// Fetch categories for homepage
try {
    $categories = $accountManager->getCategories();
} catch (Exception $e) { 
    error_log("Categories fetch error: " . $e->getMessage());
    $categories = [];
}

include 'header.php'; 
?>

<main role="main">
    <?php 
    if (isset($_GET['welcome']) && $siteSettings->get('welcome_message_enabled', '1') === '1'): 
        $welcomeTitle = $siteSettings->get('welcome_message_title', 'Hoşgeldin!');
        $welcomeContent = $siteSettings->get('welcome_message_content', 'Hesabın başarıyla oluşturuldu ve otomatik giriş yapıldı. Şimdi premium hesapları inceleyebilirsin!');
    ?>
    <!-- Welcome Message -->
    <div class="welcome-banner">
        <div class="container">
            <div class="welcome-content">
                <i class="fas fa-party-horn"></i>
                <h3><?= htmlspecialchars($welcomeTitle) ?></h3>
                <p><?= htmlspecialchars($welcomeContent) ?></p>
                <button onclick="this.parentElement.parentElement.parentElement.style.display='none'" class="welcome-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
    
    <style>
    .welcome-banner {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1rem 0;
        position: relative;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    
    .welcome-content {
        display: flex;
        align-items: center;
        gap: 1rem;
        position: relative;
    }
    
    .welcome-content i.fa-party-horn {
        font-size: 2rem;
        color: #FFD700;
        animation: bounce 2s infinite;
    }
    
    .welcome-content h3 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
    }
    
    .welcome-content p {
        margin: 0;
        flex: 1;
        opacity: 0.9;
    }
    
    .welcome-close {
        background: rgba(255,255,255,0.2);
        border: none;
        color: white;
        padding: 0.5rem;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .welcome-close:hover {
        background: rgba(255,255,255,0.3);
        transform: scale(1.1);
    }
    
    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% {
            transform: translateY(0);
        }
        40% {
            transform: translateY(-10px);
        }
        60% {
            transform: translateY(-5px);
        }
    }
    
    @media (max-width: 768px) {
        .welcome-content {
            flex-direction: column;
            text-align: center;
            gap: 0.5rem;
        }
        
        .welcome-content h3 {
            font-size: 1.3rem;
        }
        
        .welcome-close {
            position: absolute;
            top: -10px;
            right: 0;
        }
    }
    </style>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1><?php echo htmlspecialchars($heroTitle); ?></h1>
                    <p><?php echo htmlspecialchars($heroDescription); ?></p>
                    
                    <div class="hero-buttons">
                        <a href="<?php echo htmlspecialchars($heroButtonUrl); ?>" class="btn btn-primary"><i class="fas fa-shopping-bag"></i> <?php echo htmlspecialchars($heroButtonText); ?></a>
                        <a href="#categories" class="btn btn-secondary" onclick="scrollToCategories()"><i class="fas fa-th-large"></i> Kategorileri Gör</a>
                    </div>
                    
                    <div class="stats-grid">
                        <?php foreach ($heroStats as $stat): ?>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo htmlspecialchars($stat['number']); ?></div>
                            <div class="stat-label"><?php echo htmlspecialchars($stat['label']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="hero-image">
                    <!-- Floating Elements -->
                    <div class="floating-element"></div>
                    <div class="floating-element"></div>
                    <div class="floating-element"></div>
                    
                    <!-- Main Image -->
                    <img src="<?php echo htmlspecialchars($heroBackground); ?>" alt="Facebook Reklam Hesabı Satın Al - Sosyal Medya Hesapları" loading="eager" fetchpriority="high" width="600" height="400">
                    
                    <!-- Shimmer Effect -->
                    <div class="shimmer"></div>
                    
                    <!-- Hover Particles -->
                    <div class="particle" style="top: 20%; left: 20%;"></div>
                    <div class="particle" style="top: 60%; left: 80%;"></div>
                    <div class="particle" style="top: 80%; left: 30%;"></div>
                    <div class="particle" style="top: 40%; left: 70%;"></div>
                    <div class="particle" style="top: 30%; left: 15%;"></div>
                    <div class="particle" style="top: 70%; left: 85%;"></div>
                </div>
            </div>
        </div>
    </section>

     

    <!-- Categories Section -->
    <section id="categories" class="section">
        <div class="container">
            <div class="section-title">
                <h2>Kategoriler</h2>
                <p>İhtiyacınıza uygun kategoriyi seçin ve hesapları keşfedin</p>
            </div>

            <div class="categories-grid">
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                        <div class="category-card" onclick="window.location.href='<?php echo URLHelper::getCategoryUrl($category['id']); ?>'">
                            <div class="category-image">
                                <?php if (!empty($category['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($category['image_url']); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" loading="lazy" width="60" height="60">
                                <?php else: ?>
                                    <div class="category-icon">
                                        <i class="<?php echo htmlspecialchars($category['icon'] ?? 'fas fa-folder'); ?>"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="category-content">
                                <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                                <p><?php echo htmlspecialchars($category['description'] ?? 'Bu kategoride çeşitli hesaplar bulabilirsiniz.'); ?></p>
                                <div class="category-footer">
                                    <span class="category-link">Hesapları Görüntüle <i class="fas fa-arrow-right"></i></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-categories">
                        <i class="fas fa-folder-open"></i>
                        <p>Kategoriler yükleniyor...</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="more-button">
                <a href="tum-hesaplar" class="btn btn-outline">
                    <i class="fas fa-th-large"></i> Tüm Hesapları Gör
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section features">
        <div class="container">
            <div class="section-title">
                <h2>Neden BusinessHesap?</h2>
                <p>Premium hesap deneyimini farklı kılan özelliklerimiz</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Tam Güvenlik</h3>
                    <p>Hesaplar özenle oluşturulur ve size özel olarak sunulur. Satılan bir hesabın bilgileri başka bir kullanıcıya asla satılmaz. Verileriniz 256-bit şifreleme ile korunur.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>Anında Teslimat</h3>
                    <p>Satın aldığınız hesapların bilgileri anında panelinize düşer. Beklemeden hesaplarınıza erişim sağlarsınız. 7/24 otomatik teslimat sistemi.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>7/24 Premium Destek</h3>
                    <p>Profesyonel destek ekibimiz 7 gün 24 saat yanınızda. Canlı destek, telefon ve e-posta ile tüm sorularınıza anında çözüm buluyoruz.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="section">
        <div class="container">
            <div class="section-title">
                <h2>Sıkça Sorulan Sorular</h2>
                <p>Aklınıza takılan soruların cevaplarını burada bulabilirsiniz</p>
            </div>
            
            <?php if (!empty($homeFaqs)): ?>
                <div class="faq-container">
                    <?php foreach ($homeFaqs as $index => $faq): ?>
                        <div class="faq-item" data-index="<?php echo $index; ?>">
                            <div class="faq-question" onclick="toggleHomeFaq(<?php echo $index; ?>)">
                                <?php echo htmlspecialchars($faq['question']); ?>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="faq-more">
                    <a href="sss.php" class="btn btn-outline">
                        <i class="fas fa-question-circle"></i> Tüm SSS'leri Gör
                    </a>
                </div>
            <?php else: ?>
                <div class="no-faqs-home">
                    <p>Sıkça sorulan sorular yükleniyor...</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

<script>
// Smooth scroll to categories section
function scrollToCategories() {
    document.getElementById('categories').scrollIntoView({
        behavior: 'smooth',
        block: 'start'
    });
}

// Hero Image Advanced Animations
document.addEventListener('DOMContentLoaded', function() {
    const heroImage = document.querySelector('.hero-image');
    const img = heroImage.querySelector('img');
    const particles = heroImage.querySelectorAll('.particle');
    
    // Mouse move parallax effect
    heroImage.addEventListener('mousemove', function(e) {
        const rect = heroImage.getBoundingClientRect();
        const x = (e.clientX - rect.left) / rect.width;
        const y = (e.clientY - rect.top) / rect.height;
        
        // 3D tilt effect
        const tiltX = (y - 0.5) * 10;
        const tiltY = (x - 0.5) * -10;
        
        img.style.transform = `
            scale(1.05) 
            rotateX(${tiltX}deg) 
            rotateY(${tiltY}deg)
            translateZ(20px)
        `;
        
        // Move floating elements
        const floatingElements = heroImage.querySelectorAll('.floating-element');
        floatingElements.forEach((el, index) => {
            const speed = 0.5 + (index * 0.2);
            const moveX = (x - 0.5) * 20 * speed;
            const moveY = (y - 0.5) * 20 * speed;
            el.style.transform = `translate(${moveX}px, ${moveY}px)`;
        });
    });
    
    // Reset on mouse leave
    heroImage.addEventListener('mouseleave', function() {
        img.style.transform = '';
        const floatingElements = heroImage.querySelectorAll('.floating-element');
        floatingElements.forEach(el => {
            el.style.transform = '';
        });
    });
    
    // Dynamic particle animation on hover
    heroImage.addEventListener('mouseenter', function() {
        particles.forEach((particle, index) => {
            setTimeout(() => {
                particle.style.animationDelay = `${index * 0.1}s`;
                particle.classList.add('active');
            }, index * 100);
        });
    });
    
    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.3,
        rootMargin: '0px 0px -100px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);
    
    observer.observe(heroImage);
    
    // Random sparkle effects
    setInterval(() => {
        createSparkle();
    }, 2000);
    
    function createSparkle() {
        const sparkle = document.createElement('div');
        sparkle.className = 'dynamic-sparkle';
        sparkle.innerHTML = ['✨', '💫', '⭐', '🌟'][Math.floor(Math.random() * 4)];
        sparkle.style.cssText = `
            position: absolute;
            font-size: ${Math.random() * 1.5 + 0.5}rem;
            top: ${Math.random() * 80 + 10}%;
            left: ${Math.random() * 80 + 10}%;
            pointer-events: none;
            animation: sparkleFloat 3s ease-out forwards;
            z-index: 10;
        `;
        
        heroImage.appendChild(sparkle);
        
        setTimeout(() => {
            sparkle.remove();
        }, 3000);
    }
});

function toggleHomeFaq(index) {
    const faqItem = document.querySelector(`.faq-item[data-index="${index}"]`);
    if (!faqItem) {
        console.error('FAQ item not found:', index);
        return;
    }
    
    const isActive = faqItem.classList.contains('active');
    
    // Tüm FAQ'ları kapat
    document.querySelectorAll('.faq-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Eğer tıklanan FAQ kapalıysa aç
    if (!isActive) {
        faqItem.classList.add('active');
    }
}

// Alternatif olarak event delegation kullan
document.addEventListener('DOMContentLoaded', function() {
    const faqContainer = document.querySelector('.faq-container');
    if (faqContainer) {
        faqContainer.addEventListener('click', function(e) {
            const faqQuestion = e.target.closest('.faq-question');
            if (faqQuestion) {
                const faqItem = faqQuestion.closest('.faq-item');
                const isActive = faqItem.classList.contains('active');
                
                // Tüm FAQ'ları kapat
                document.querySelectorAll('.faq-item').forEach(item => {
                    item.classList.remove('active');
                });
                
                // Eğer tıklanan FAQ kapalıysa aç
                if (!isActive) {
                    faqItem.classList.add('active');
                }
            }
        });
    }
});
</script>

<style>
/* FAQ More Button */
.faq-more {
    text-align: center;
    margin-top: 40px;
}

.faq-more .btn {
    padding: 14px 28px;
    font-size: 1rem;
    border-radius: 50px;
}

.no-faqs-home {
    text-align: center;
    padding: 40px 20px;
    color: var(--gray);
}

.no-faqs-home p {
    font-size: 1.1rem;
    margin: 0;
}

/* Categories Section */
.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.category-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    height: 240px;
    display: flex;
    flex-direction: column;
}

.category-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary), var(--accent));
    transform: scaleX(0);
    transition: var(--transition);
}

.category-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    border-color: rgba(108, 99, 255, 0.3);
}

.category-card:hover::before {
    transform: scaleX(1);
}

.category-image {
    height: 80px;
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, var(--primary), var(--accent));
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border-radius: 12px 12px 0 0;
}

.category-image img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    object-position: center;
    transition: var(--transition);
    border-radius: 50%;
    border: 2px solid rgba(255,255,255,0.2);
}

.category-card:hover .category-image img {
    transform: scale(1.05);
}

.category-icon {
    font-size: 2.5rem;
    color: white;
    opacity: 0.9;
}

.category-content {
    padding: 18px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.category-content h3 {
    color: var(--light);
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 10px 0;
    line-height: 1.3;
    word-wrap: break-word;
    overflow-wrap: break-word;
    hyphens: auto;
}

.category-content p {
    color: var(--gray);
    font-size: 0.85rem;
    line-height: 1.4;
    margin: 0 0 15px 0;
    flex: 1;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.category-footer {
    margin-top: auto;
}

.category-link {
    color: var(--primary);
    font-weight: 600;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: var(--transition);
}

.category-card:hover .category-link {
    color: var(--accent);
    transform: translateX(3px);
}

.no-categories {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    color: var(--gray);
}

.no-categories i {
    font-size: 3rem;
    margin-bottom: 20px;
    opacity: 0.5;
}

.no-categories p {
    font-size: 1.1rem;
    margin: 0;
}

.more-button {
    text-align: center;
    margin-top: 40px;
}

.more-button .btn {
    padding: 15px 35px;
    font-size: 1.1rem;
    border-radius: 50px;
    transition: var(--transition);
}

.more-button .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(108, 99, 255, 0.3);
}

/* Responsive for Categories */
@media (max-width: 992px) {
    .categories-grid {
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 15px;
    }
    
    .category-card {
        height: 240px;
    }
    
    .category-image {
        height: 70px;
    }
    
    .category-content {
        padding: 15px;
    }
}

@media (max-width: 768px) {
    .categories-grid {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
    }
    
    .category-card {
        height: 220px;
        border-radius: 10px;
    }
    
    .category-image {
        height: 60px;
    }
    
    .category-icon {
        font-size: 1.8rem;
    }
    
    .category-content {
        padding: 12px;
    }
    
    .category-content h3 {
        font-size: 1rem;
        margin: 0 0 8px 0;
    }
    
    .category-content p {
        font-size: 0.8rem;
        margin: 0 0 10px 0;
    }
    
    .category-link {
        font-size: 0.8rem;
    }
    
    .more-button .btn {
        padding: 12px 25px;
        font-size: 1rem;
    }
}

@media (max-width: 480px) {
    .categories-grid {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    
    .category-card {
        height: 200px;
    }
    
    .category-image {
        height: 50px;
    }
    
    .category-icon {
        font-size: 1.5rem;
    }
    
    .category-content {
        padding: 10px;
    }
    
    .category-content h3 {
        font-size: 0.85rem;
        margin: 0 0 6px 0;
        line-height: 1.2;
        max-height: 2.4em;
        overflow: hidden;
    }
    
    .category-content p {
        font-size: 0.75rem;
        margin: 0 0 8px 0;
        -webkit-line-clamp: 2;
        line-height: 1.3;
    }
    
    .category-link {
        font-size: 0.7rem;
        gap: 4px;
    }
}
</style>

    <!-- Facebook Reklam Hesabı Hakkında Detaylı Bölüm -->
    <section class="section fb-reklam-section">
        <div class="container">
            <div class="section-title">
                <h2>Facebook Reklam Hesabı Satın Al</h2>
                <p>Profesyonel Çözümler</p>
            </div>
            <div class="fb-reklam-content">
                <p>Facebook reklam hesabı satın almak isteyen işletmeler ve bireysel kullanıcılar için en güvenilir platform. Doğrulanmış, limitsiz ve anında kullanıma hazır facebook reklam hesapları ile dijital pazarlama kampanyalarınızı hemen başlatın.</p>
                
                <div class="fb-reklam-grid">
                    <div class="fb-reklam-card">
                        <h3><i class="fas fa-question-circle"></i> Neden Facebook Reklam Hesabı Satın Almalısınız?</h3>
                        <ul>
                            <li><i class="fas fa-check-circle"></i> Anında kampanya başlatma imkanı</li>
                            <li><i class="fas fa-check-circle"></i> Yüksek harcama limitleri</li>
                            <li><i class="fas fa-check-circle"></i> Doğrulanmış business manager hesapları</li>
                            <li><i class="fas fa-check-circle"></i> 7/24 destek garantisi</li>
                        </ul>
                    </div>
                    
                    <div class="fb-reklam-card">
                        <h3><i class="fas fa-shopping-cart"></i> Facebook Reklam Hesabı Nasıl Satın Alınır?</h3>
                        <ol>
                            <li><i class="fas fa-mouse-pointer"></i> Kategori seçimi yapın</li>
                            <li><i class="fas fa-credit-card"></i> Ödeme yöntemini belirleyin</li>
                            <li><i class="fas fa-bolt"></i> Anında hesap bilgilerinizi alın</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Schema Markup -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "FAQPage",
        "mainEntity": [{
            "@type": "Question",
            "name": "Facebook reklam hesabı satın almak güvenli mi?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Evet, tüm hesaplarımız doğrulanmış ve güvenlidir. Her hesap satışından önce titizlikle kontrol edilir ve alıcıya özel olarak sunulur. 7/24 destek garantisi ile güvenli alışveriş deneyimi sağlıyoruz."
            }
        }, {
            "@type": "Question",
            "name": "Facebook reklam hesabı ne kadar sürede teslim edilir?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Ödemeniz onaylandıktan sonra hesap bilgileriniz anında panelinize düşer. Otomatik teslimat sistemimiz sayesinde 7/24 kesintisiz hizmet sunuyoruz."
            }
        }, {
            "@type": "Question",
            "name": "Business Manager hesabı nedir?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Business Manager, Facebook reklam hesaplarınızı, sayfalarınızı ve ekip üyelerinizi tek bir yerden yönetmenizi sağlayan profesyonel bir araçtır. Doğrulanmış Business Manager hesapları ile reklam kampanyalarınızı profesyonelce yönetebilirsiniz."
            }
        }]
    }
    </script>

<style>
/* Facebook Reklam Hesabı Section */
.fb-reklam-section {
    padding: 80px 0;
}

.fb-reklam-content > p {
    color: var(--gray);
    font-size: 1.1rem;
    line-height: 1.8;
    text-align: center;
    max-width: 800px;
    margin: 0 auto 40px;
}

.fb-reklam-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 30px;
}

.fb-reklam-card {
    backdrop-filter: blur(10px);
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 20px;
    padding: 30px;
    transition: var(--transition);
}

.fb-reklam-card:hover {
    border-color: rgba(108, 99, 255, 0.3);
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
}

.fb-reklam-card h3 {
    color: var(--light);
    font-size: 1.3rem;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.fb-reklam-card h3 i {
    color: var(--accent);
}

.fb-reklam-card ul,
.fb-reklam-card ol {
    list-style: none;
    padding: 0;
}

.fb-reklam-card li {
    color: var(--gray);
    font-size: 1rem;
    padding: 10px 0;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.fb-reklam-card li:last-child {
    border-bottom: none;
}

.fb-reklam-card li i {
    color: var(--accent);
    font-size: 0.9rem;
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .fb-reklam-grid {
        grid-template-columns: 1fr;
    }
    
    .fb-reklam-card {
        padding: 20px;
    }
}
</style>

</main>

<?php include 'footer.php'; ?>

<?php 
require_once 'config.php';
require_once 'functions.php';
require_once 'StructuredDataHelper.php';

// Meta Tags for SEO and AI
$meta_title = "Bize Ulaşın | 7/24 Canlı Destek ve Hızlı Yardım";
$meta_description = "Facebook hesap satın alma ve diğer konularda herhangi bir sorunuz varsa bize hemen ulaşın. 7/24 canlı destekle hemen yardım alın.";
$meta_keywords = "facebook hesap satışı için iletişim, canlı destek, whatsapp destek, e-posta destek, telegram kanal, hesap yardımı, hızlı çözüm, müşteri memnuniyeti";
$meta_ai_intent = "sosyal medya hesap satışı için iletişim ve destek hizmeti, canlı destek, whatsapp destek";
$meta_ai_subtopics = "hesap satışı için canlı destek hizmeti, whatsapp destek, e-posta destek, 7/24 erişim";
$meta_audience = "sosyal medya eski hesap satın alan veya almak isteyenler, sosyal medya kullanıcıları, dijital pazarlamacılar, hesap alıcıları, hızlı destek isteyenler";
$meta_category = "iletişim, destek, sosyal medya hizmetleri, hesap satışı";
$meta_content_tone = "güvenilir, profesyonel";
$meta_reader_interest = "eski sosyal medya hesap satışı için 7/24 canlı destek, hızlı yanıt, whatsapp, facebook eski hesap satışında müşteri memnuniyeti, güvenli iletişim";
$meta_summary = "facebook premium hesap satışıyla ilgili herhangi bir sorun olduğuna bize ulaşabilirsiniz";
$meta_topic_tags = "iletişim, destek hizmeti, canlı destek, whatsapp destek, e-posta destek, telegram kanal, hızlı çözüm, müşteri memnuniyeti";
$meta_visual_content = "canlı destek ikonları, whatsapp simgesi";

// Site ayarlarından iletişim bilgilerini çek
$contactPhone = $siteSettings->get('contact_phone', '+90 555 000 00 00');
$contactEmail = $siteSettings->get('contact_email', 'info@businesshesap.com');
$contactWhatsapp = $siteSettings->get('contact_whatsapp', '+90 555 000 00 00');
$contactTelegram = $siteSettings->get('contact_telegram', '@businesshesap');
$contactAddress = $siteSettings->get('contact_address', 'İstanbul, Türkiye');

// Clean phone numbers for links
$cleanPhone = preg_replace('/[^0-9+]/', '', $contactPhone);
$cleanWhatsapp = preg_replace('/[^0-9+]/', '', $contactWhatsapp);
$cleanTelegram = str_replace('@', '', $contactTelegram);

$page_title = "İletişim";

// Yapısal veri oluştur
$structuredDataHelper = new StructuredDataHelper();
$structuredData = $structuredDataHelper->getContactStructuredData($contactEmail, $contactWhatsapp);

include 'header.php'; 
?>

    <!-- Page Header -->
    <section class="page-header compact">
        <div class="container">
            <h1>Bize Ulaşın</h1>
            <p>Herhangi bir sorunuz varsa, lütfen bizimle iletişime geçmekten çekinmeyin.</p>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <!-- Modern Contact Section -->
            <div class="modern-contact-section">
                <div class="contact-grid">
                    <div class="contact-main">
                        <div class="contact-icon-wrapper">
                            <div class="floating-icons">
                                <i class="fas fa-comments"></i>
                                <i class="fas fa-headset"></i>
                                <i class="fas fa-heart"></i>
                            </div>
                        </div>
                        <h3>Hala yardıma ihtiyacınız var mı?</h3>
                        <p>Profesyonel destek ekibimiz 7/24 yanınızda. Size en hızlı çözümü sunmak için buradayiz.</p>
                        
                        <div class="stats-mini">
                            <div class="stat-mini">
                                <span class="stat-number">5 dk</span>
                                <span class="stat-label">Ortalama Yanıt</span>
                            </div>
                            <div class="stat-mini">
                                <span class="stat-number">7/24</span>
                                <span class="stat-label">Canlı Destek</span>
                            </div>
                            <div class="stat-mini">
                                <span class="stat-number">98%</span>
                                <span class="stat-label">Memnuniyet</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="contact-options">
                        <a href="https://wa.me/<?php echo htmlspecialchars($cleanWhatsapp); ?>" class="contact-option whatsapp">
                            <div class="option-icon">
                                <i class="fab fa-whatsapp"></i>
                            </div>
                            <div class="option-content">
                                <h4>WhatsApp Destek</h4>
                                <p><?php echo htmlspecialchars($contactWhatsapp); ?> - Anında yardım alın.</p>
                                <span class="status online">Aktif</span>
                            </div>
                            <div class="option-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </a>
                        
                        <a href="mailto:<?php echo htmlspecialchars($contactEmail); ?>" class="contact-option email">
                            <div class="option-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="option-content">
                                <h4>E-posta Destek</h4>
                                <p><?php echo htmlspecialchars($contactEmail); ?> - Detaylı yardım alın.</p>
                                <span class="response-time">~30dk içinde</span>
                            </div>
                            <div class="option-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </a>
                        
                        <a href="https://t.me/<?php echo htmlspecialchars($cleanTelegram); ?>" class="contact-option telegram">
                            <div class="option-icon">
                                <i class="fab fa-telegram"></i>
                            </div>
                            <div class="option-content">
                                <h4>Telegram Kanal</h4>
                                <p><?php echo htmlspecialchars($contactTelegram); ?> - Güncellemeler ve duyurular</p>
                                <span class="members">2.1k üye</span>
                            </div>
                            <div class="option-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

<style>
/* Contact Section Styles */
.contact-section {
    padding: 50px 0;
}

/* Modern Contact Section */
.modern-contact-section {
    margin-top: 60px;
    padding: 0 20px;
}

.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    max-width: 1200px;
    margin: 0 auto;
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 24px;
    padding: 40px;
    backdrop-filter: blur(10px);
    box-shadow: var(--inner-shadow);
    position: relative;
    overflow: hidden;
}

.contact-grid::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle, rgba(108, 99, 255, 0.1) 0%, transparent 70%);
    z-index: -1;
}

.contact-main {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.contact-icon-wrapper {
    margin-bottom: 20px;
    position: relative;
    height: 80px;
}

.floating-icons {
    display: flex;
    gap: 15px;
    align-items: center;
}

.floating-icons i {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    animation: float 3s ease-in-out infinite;
    box-shadow: 0 8px 20px rgba(108, 99, 255, 0.3);
}

.floating-icons i:nth-child(2) {
    animation-delay: 0.5s;
    background: linear-gradient(135deg, var(--accent) 0%, var(--secondary) 100%);
}

.floating-icons i:nth-child(3) {
    animation-delay: 1s;
    background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.contact-main h3 {
    font-size: 1.8rem;
    color: var(--light);
    margin-bottom: 15px;
    font-weight: 700;
}

.contact-main p {
    color: var(--gray);
    font-size: 1.1rem;
    line-height: 1.6;
    margin-bottom: 30px;
}

.stats-mini {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.stat-mini {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 15px;
    background: rgba(108, 99, 255, 0.1);
    border-radius: 12px;
    border: 1px solid rgba(108, 99, 255, 0.2);
    flex: 1;
    min-width: 80px;
}

.stat-number {
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--accent);
    margin-bottom: 4px;
    font-family: 'Montserrat', sans-serif;
}

.stat-label {
    font-size: 0.75rem;
    color: var(--gray);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.contact-options {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.contact-option {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid var(--card-border);
    border-radius: 16px;
    cursor: pointer;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
}

.contact-option:visited {
    color: inherit;
    text-decoration: none;
}

.contact-option::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    transform: scaleY(0);
    transition: var(--transition);
}

.contact-option:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
    border-color: rgba(108, 99, 255, 0.3);
}

.contact-option:hover::before {
    transform: scaleY(1);
}

.option-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
    transition: var(--transition);
}

.whatsapp .option-icon {
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    color: white;
}

.email .option-icon {
    background: linear-gradient(135deg, #4285F4 0%, #1976D2 100%);
    color: white;
}

.telegram .option-icon {
    background: linear-gradient(135deg, #0088cc 0%, #005a99 100%);
    color: white;
}

.option-content {
    flex: 1;
}

.option-content h4 {
    font-size: 1.1rem;
    color: var(--light);
    margin: 0 0 4px 0;
    font-weight: 600;
}

.option-content p {
    color: var(--gray);
    margin: 0 0 6px 0;
    font-size: 0.9rem;
}

.status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status.online {
    background: rgba(34, 197, 94, 0.2);
    color: #22c55e;
}

.status.online::before {
    content: '';
    width: 6px;
    height: 6px;
    background: #22c55e;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.response-time {
    color: var(--accent);
    font-size: 0.8rem;
    font-weight: 500;
}

.members {
    color: var(--primary);
    font-size: 0.8rem;
    font-weight: 500;
}

.option-arrow {
    color: var(--gray);
    font-size: 0.9rem;
    transition: var(--transition);
}

.contact-option:hover .option-arrow {
    color: var(--accent);
    transform: translateX(4px);
}

/* Responsive */
@media (max-width: 968px) {
    .contact-grid {
        grid-template-columns: 1fr;
        gap: 30px;
        padding: 30px;
    }
    
    .contact-main {
        text-align: center;
    }
    
    .stats-mini {
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .modern-contact-section {
        padding: 0 15px;
    }
    
    .contact-grid {
        padding: 25px 20px;
        margin: 0 5px;
    }
    
    .contact-main h3 {
        font-size: 1.5rem;
    }
    
    .floating-icons {
        justify-content: center;
    }
    
    .floating-icons i {
        width: 45px;
        height: 45px;
        font-size: 1.1rem;
    }
    
    .stats-mini {
        gap: 10px;
    }
    
    .stat-mini {
        padding: 12px 8px;
        min-width: 70px;
    }
    
    .contact-option {
        padding: 16px;
    }
    
    .option-icon {
        width: 45px;
        height: 45px;
        font-size: 1.2rem;
    }
    
    .option-content h4 {
        font-size: 1rem;
    }
}
</style>

<?php include 'footer.php'; ?>

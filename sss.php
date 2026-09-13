<?php 
require_once 'config.php';
require_once 'functions.php';
require_once 'StructuredDataHelper.php';

$page_title = "Sıkça Sorulan Sorular";

// Meta Tags for SEO and AI
$meta_title = "Sıkça Sorulan Sorular";
$meta_description = "Facebook, Instagram, TikTok ve diğer sosyal medya hesap satışlarıyla ilgili sık sorulan soruların cevaplarını kolay şekilde öğrenin.";
$meta_keywords = "facebook hesap sss, instagram hesap sss, tiktok hesap sss, hesap teslim süresi, güvenli hesap, ödeme yöntemleri";
$meta_ai_intent = "hesap satışıyla ilgili sıkça sorulan sorular, facebook hesapları, instagram hesapları, tiktok hesapları, güvenli hesap bilgisi";
$meta_ai_subtopics = "hesap teslim süresi, hesap satışı riski, ödeme yöntemleri, para iadesi, toplu alım, destek hizmeti, hesap değişimi, hesap yaşı, hesap bilgileri";
$meta_audience = "sosyal medya kullanıcıları, dijital pazarlamacılar, işletmeler, güvenli hesap arayanlar";
$meta_category = "sosyal medya, hesap satışları, sıkça sorulan sorular, dijital hizmetler";
$meta_content_tone = "güvenilir, açıklayıcı, bilgilendirici";
$meta_reader_interest = "hesap güvenliği, hızlı teslimat, ödeme seçenekleri, toplu alım indirimleri, hesap değişimi";
$meta_summary = "facebook instagram tiktok ve diğer sosyal medya hesaplarıyla ilgili sıkça sorulan soruların yanıtlarını kolayca öğrenin";
$meta_topic_tags = "sıkça sorulan sorular, hesap teslim süresi, güvenli hesap, ödeme yöntemleri, toplu alım, destek hizmeti, hesap yaşı, hesap değişimi";
$meta_visual_content = "sıkça sorulan sorular ikonları, hesap güvenliği simgeleri";

// Pagination ayarları
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Account Manager sınıfını başlat
$accountManager = new AccountManager();

// FAQ'ları çek
try {
    $faqs = $accountManager->getFaqs($itemsPerPage, $offset);
    $totalFaqs = $accountManager->getTotalFaqs();
    $totalPages = ceil($totalFaqs / $itemsPerPage);
} catch (Exception $e) {
    error_log("FAQs fetch error: " . $e->getMessage());
    $faqs = [];
    $totalFaqs = 0;
    $totalPages = 0;
}

// Yapısal veri oluştur
$structuredDataHelper = new StructuredDataHelper();
$structuredData = $structuredDataHelper->getFAQStructuredData($faqs);

include 'header.php'; 
?>

    <!-- Page Header -->
    <section class="page-header compact">
        <div class="container">
            <h1>Sıkça Sorulan Sorular</h1>
            <p>Merak ettiğiniz tüm soruların cevaplarını burada bulabilirsiniz.</p>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <div class="container">
            <?php if (!empty($faqs)): ?>
                <div class="faq-container">
                    <?php foreach ($faqs as $index => $faq): ?>
                        <div class="faq-item" data-index="<?php echo $index; ?>">
                            <div class="faq-question" onclick="toggleFaq(<?php echo $index; ?>)">
                                <h3><?php echo htmlspecialchars($faq['question']); ?></h3>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-faqs">
                    <i class="fas fa-question-circle"></i>
                    <h3>Henüz soru bulunamadı</h3>
                    <p>Yakında sıkça sorulan sorular eklenecek.</p>
                </div>
            <?php endif; ?>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination">
                        <?php if ($currentPage > 1): ?>
                            <a href="?page=<?php echo $currentPage - 1; ?>" class="page-btn prev">
                                <i class="fas fa-chevron-left"></i> Önceki
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        
                        if ($startPage > 1) {
                            echo '<a href="?page=1" class="page-btn">1</a>';
                            if ($startPage > 2) {
                                echo '<span class="page-dots">...</span>';
                            }
                        }
                        
                        for ($i = $startPage; $i <= $endPage; $i++) {
                            $activeClass = ($i == $currentPage) ? 'active' : '';
                            echo '<a href="?page=' . $i . '" class="page-btn ' . $activeClass . '">' . $i . '</a>';
                        }
                        
                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo '<span class="page-dots">...</span>';
                            }
                            echo '<a href="?page=' . $totalPages . '" class="page-btn">' . $totalPages . '</a>';
                        }
                        ?>
                        
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?page=<?php echo $currentPage + 1; ?>" class="page-btn next">
                                Sonraki <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="pagination-info">
                        <span>Sayfa <?php echo $currentPage; ?> / <?php echo $totalPages; ?> (Toplam <?php echo $totalFaqs; ?> soru)</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

<script>
function toggleFaq(index) {
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
/* FAQ Section Styles */
.faq-section {
    padding: 50px 0;
}

.faq-container {
    max-width: 900px;
    margin: 0 auto;
}

.faq-item {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 16px;
    margin-bottom: 20px;
    overflow: hidden;
    transition: var(--transition);
    backdrop-filter: blur(10px);
    box-shadow: var(--inner-shadow);
}

.faq-item:hover {
    border-color: rgba(108, 99, 255, 0.3);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.faq-question {
    padding: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    transition: var(--transition);
}

.faq-question:hover {
    background: rgba(108, 99, 255, 0.05);
}

.faq-question h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--light);
    margin: 0;
    flex: 1;
    padding-right: 20px;
}

.faq-question i {
    font-size: 1.2rem;
    color: var(--accent);
    transition: var(--transition);
}

.faq-item.active .faq-question i {
    transform: rotate(180deg);
}

.faq-answer {
    padding: 0 25px;
    max-height: 0;
    overflow: hidden;
    transition: var(--transition);
}

.faq-item.active .faq-answer {
    padding: 0 25px 25px;
    max-height: 500px;
}

.faq-answer p {
    color: var(--gray);
    line-height: 1.7;
    margin: 0;
    font-size: 1rem;
}

/* No FAQs */
.no-faqs {
    text-align: center;
    padding: 60px 20px;
    color: var(--gray);
}

.no-faqs i {
    font-size: 4rem;
    color: var(--accent);
    margin-bottom: 20px;
}

.no-faqs h3 {
    font-size: 1.5rem;
    color: var(--light);
    margin-bottom: 10px;
}

.no-faqs p {
    font-size: 1rem;
    opacity: 0.8;
}

/* Responsive */
@media (max-width: 768px) {
    .faq-question {
        padding: 20px;
    }
    
    .faq-question h3 {
        font-size: 1rem;
    }
    
    .faq-item.active .faq-answer {
        padding: 0 20px 20px;
    }
}
</style>

<?php include 'footer.php'; ?>

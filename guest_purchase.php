<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'AccountManager.php';

$page_title = "Hesap Satın Al";

// Account Manager sınıfını başlat
$accountManager = new AccountManager();

// URL'den kategori parametresi al
$selectedCategory = isset($_GET['category']) ? intval($_GET['category']) : null;

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

// CSRF token oluştur
$csrfToken = generateCSRFToken();

include 'header.php';
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <div class="header-content">
            <h1><i class="fas fa-shopping-cart"></i> Hesap Satın Al</h1>
            <p>Üye olmadan hızlıca hesap satın alın. E-posta adresinizle siparişinizi takip edin.</p>
        </div>
    </div>
</section>

<!-- Guest Info Banner -->
<section class="guest-banner">
    <div class="container">
        <div class="banner-content">
            <div class="banner-icon">
                <i class="fas fa-user-clock"></i>
            </div>
            <div class="banner-text">
                <h3>Ziyaretçi Alışverişi</h3>
                <p>Üye olmadan hesap satın alabilirsiniz. Siparişinizi takip etmek için e-posta adresinizi girmeniz yeterli.</p>
            </div>
            <div class="banner-actions">
                <a href="register.php" class="btn-register">
                    <i class="fas fa-user-plus"></i>
                    Üye Ol
                </a>
                <a href="login.php" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Giriş Yap
                </a>
            </div>
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
                            <option value="<?= $category['id'] ?>" <?php echo ($selectedCategory == $category['id']) ? 'selected' : ''; ?>>
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
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Hesap ara..." class="search-input">
                        <button type="button" id="searchBtn" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accounts Grid -->
        <div class="accounts-grid" id="accountsGrid">
            <?php foreach ($initialAccounts as $account): ?>
                <?php echo generateAccountCardHTML($account); ?>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalAccounts > ITEMS_PER_PAGE): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                <span id="paginationInfo">Sayfa 1 / <?= ceil($totalAccounts / ITEMS_PER_PAGE) ?> (Toplam <?= $totalAccounts ?> hesap)</span>
            </div>
            <div class="pagination-controls">
                <button id="prevPageBtn" class="pagination-btn" disabled onclick="loadPage('prev')">
                    <i class="fas fa-chevron-left"></i> Önceki
                </button>
                <span id="pageNumbers" class="page-numbers">
                    <!-- Dinamik olarak doldurulacak -->
                </span>
                <button id="nextPageBtn" class="pagination-btn" onclick="loadPage('next')">
                    Sonraki <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Guest Purchase Modal -->
<div id="guestPurchaseModal" class="modal">
    <div class="modal-content guest-purchase-modal">
        <div class="modal-header">
            <h3><i class="fas fa-shopping-cart"></i> Hesap Satın Al</h3>
            <span class="close-modal" onclick="closeGuestPurchaseModal()">&times;</span>
        </div>
        
        <div class="modal-body">
            <form id="guestPurchaseForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="account_id" id="guestAccountId">
                
                <!-- Account Info -->
                <div class="account-info-section">
                    <h4>Seçilen Hesap</h4>
                    <div class="selected-account-info" id="selectedAccountInfo">
                        <!-- Dinamik olarak doldurulacak -->
                    </div>
                </div>
                
                <!-- Guest Information -->
                <div class="guest-info-section">
                    <h4>Kişisel Bilgiler</h4>
                    
                    <div class="form-group">
                        <label for="guestEmail">E-posta Adresi *</label>
                        <input type="email" id="guestEmail" name="email" required 
                               placeholder="ornek@email.com" class="form-input">
                        <small>Sipariş takibi için gerekli</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="guestName">Ad Soyad *</label>
                        <input type="text" id="guestName" name="name" required 
                               placeholder="Adınız Soyadınız" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="guestPhone">Telefon (Opsiyonel)</label>
                        <input type="tel" id="guestPhone" name="phone" 
                               placeholder="+90 5XX XXX XX XX" class="form-input">
                    </div>
                </div>
                
                <!-- Payment Method -->
                <div class="payment-section">
                    <h4>Ödeme Yöntemi</h4>
                    <div class="payment-methods">
                        <div class="payment-method">
                            <input type="radio" id="crypto" name="payment_method" value="crypto" checked>
                            <label for="crypto">
                                <i class="fas fa-bitcoin"></i>
                                Kripto Para (Bitcoin, Ethereum, USDT)
                            </label>
                        </div>
                        <div class="payment-method">
                            <input type="radio" id="bank" name="payment_method" value="bank">
                            <label for="bank">
                                <i class="fas fa-university"></i>
                                Banka Havalesi
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Terms -->
                <div class="terms-section">
                    <div class="form-group">
                        <input type="checkbox" id="agreeTerms" name="agree_terms" required>
                        <label for="agreeTerms">
                            <a href="terms.php" target="_blank">Kullanım Şartları</a> ve 
                            <a href="privacy.php" target="_blank">Gizlilik Politikası</a>'nı okudum ve kabul ediyorum *
                        </label>
                    </div>
                </div>
                
                <!-- Total -->
                <div class="total-section">
                    <div class="total-item">
                        <span>Hesap Fiyatı:</span>
                        <span id="accountPrice">₺0.00</span>
                    </div>
                    <div class="total-item">
                        <span>Komisyon:</span>
                        <span id="commission">₺0.00</span>
                    </div>
                    <div class="total-item total-final">
                        <span>Toplam:</span>
                        <span id="totalPrice">₺0.00</span>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeGuestPurchaseModal()">
                        İptal
                    </button>
                    <button type="submit" class="btn-purchase">
                        <i class="fas fa-credit-card"></i>
                        Satın Al
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Guest Banner */
.guest-banner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2rem 0;
    margin-bottom: 2rem;
}

.banner-content {
    display: flex;
    align-items: center;
    gap: 2rem;
    color: white;
}

.banner-icon {
    font-size: 3rem;
    opacity: 0.9;
}

.banner-text h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
}

.banner-text p {
    margin: 0;
    opacity: 0.9;
    font-size: 1rem;
}

.banner-actions {
    display: flex;
    gap: 1rem;
    margin-left: auto;
}

.btn-register, .btn-login {
    padding: 0.75rem 1.5rem;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 8px;
    color: white;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-register:hover, .btn-login:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-2px);
}

/* Guest Purchase Modal */
.guest-purchase-modal {
    max-width: 600px;
    width: 90%;
}

.account-info-section, .guest-info-section, .payment-section, .terms-section, .total-section {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--card-border);
}

.account-info-section h4, .guest-info-section h4, .payment-section h4 {
    color: var(--text-primary);
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

.selected-account-info {
    background: var(--card-bg);
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid var(--card-border);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
    font-weight: 500;
}

.form-input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--card-border);
    border-radius: 8px;
    background: var(--card-bg);
    color: var(--text-primary);
    font-size: 1rem;
    transition: border-color 0.3s;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-group small {
    color: var(--text-secondary);
    font-size: 0.85rem;
    margin-top: 0.25rem;
    display: block;
}

.payment-methods {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.payment-method {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border: 1px solid var(--card-border);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.payment-method:hover {
    background: var(--hover-bg);
    border-color: var(--primary-color);
}

.payment-method input[type="radio"] {
    margin: 0;
}

.payment-method label {
    margin: 0;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
}

.terms-section .form-group {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    margin-bottom: 0;
}

.terms-section input[type="checkbox"] {
    margin-top: 0.25rem;
}

.terms-section label {
    margin: 0;
    font-size: 0.9rem;
    line-height: 1.4;
}

.terms-section a {
    color: var(--primary-color);
    text-decoration: none;
}

.terms-section a:hover {
    text-decoration: underline;
}

.total-section {
    background: var(--card-bg);
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid var(--card-border);
}

.total-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
}

.total-item.total-final {
    border-top: 1px solid var(--card-border);
    margin-top: 0.5rem;
    padding-top: 1rem;
    font-weight: 600;
    font-size: 1.1rem;
    color: var(--primary-color);
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.btn-cancel {
    padding: 0.75rem 1.5rem;
    border: 1px solid var(--card-border);
    border-radius: 8px;
    background: transparent;
    color: var(--text-primary);
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-cancel:hover {
    background: var(--hover-bg);
}

.btn-purchase {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    background: linear-gradient(45deg, #22c55e, #16a34a);
    color: white;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-purchase:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(34, 197, 94, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .banner-content {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .banner-actions {
        margin-left: 0;
    }
    
    .guest-purchase-modal {
        width: 95%;
        margin: 1rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<script>
let currentPage = 1;
let totalPages = <?= ceil($totalAccounts / ITEMS_PER_PAGE) ?>;

// Guest purchase modal functions
function openGuestPurchaseModal(accountId, accountData) {
    document.getElementById('guestAccountId').value = accountId;
    
    // Fill account info
    const accountInfo = document.getElementById('selectedAccountInfo');
    accountInfo.innerHTML = `
        <div class="account-detail">
            <strong>${accountData.name}</strong>
            <span class="account-category">${accountData.category}</span>
        </div>
        <div class="account-price">
            <span class="price">${accountData.price}</span>
        </div>
    `;
    
    // Update prices
    document.getElementById('accountPrice').textContent = accountData.price;
    document.getElementById('commission').textContent = '₺0.00';
    document.getElementById('totalPrice').textContent = accountData.price;
    
    document.getElementById('guestPurchaseModal').style.display = 'block';
}

function closeGuestPurchaseModal() {
    document.getElementById('guestPurchaseModal').style.display = 'none';
    document.getElementById('guestPurchaseForm').reset();
}

// Handle form submission
document.getElementById('guestPurchaseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Show loading
    const submitBtn = this.querySelector('.btn-purchase');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> İşleniyor...';
    submitBtn.disabled = true;
    
    fetch('guest_purchase_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Sipariş başarıyla oluşturuldu!', 'success');
            closeGuestPurchaseModal();
            
            // Redirect to payment page or show order details
            if (data.payment_url) {
                window.location.href = data.payment_url;
            } else {
                window.location.href = `guest_order_track.php?order_id=${data.order_id}`;
            }
        } else {
            showNotification(data.message || 'Bir hata oluştu', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Bir hata oluştu', 'error');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('guestPurchaseModal');
    if (event.target === modal) {
        closeGuestPurchaseModal();
    }
}

// Load accounts with AJAX
function loadAccounts(page = 1, filters = {}) {
    const params = new URLSearchParams({
        page: page,
        ...filters
    });
    
    fetch(`guest_accounts_ajax.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('accountsGrid').innerHTML = data.html;
                updatePagination(data.pagination);
            } else {
                showNotification('Hesaplar yüklenirken hata oluştu', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Bir hata oluştu', 'error');
        });
}

// Update pagination
function updatePagination(pagination) {
    currentPage = pagination.current_page;
    totalPages = pagination.total_pages;
    
    document.getElementById('paginationInfo').textContent = 
        `Sayfa ${currentPage} / ${totalPages} (Toplam ${pagination.total_items} hesap)`;
    
    // Update page numbers
    let pageNumbers = '';
    const start = Math.max(1, currentPage - 2);
    const end = Math.min(totalPages, currentPage + 2);
    
    for (let i = start; i <= end; i++) {
        if (i === currentPage) {
            pageNumbers += `<span class="current">${i}</span>`;
        } else {
            pageNumbers += `<a href="#" onclick="loadPage(${i}); return false;">${i}</a>`;
        }
    }
    
    document.getElementById('pageNumbers').innerHTML = pageNumbers;
    
    // Update prev/next buttons
    document.getElementById('prevPageBtn').disabled = currentPage <= 1;
    document.getElementById('nextPageBtn').disabled = currentPage >= totalPages;
}

// Load specific page
function loadPage(page) {
    if (page === 'prev') page = currentPage - 1;
    if (page === 'next') page = currentPage + 1;
    
    if (page >= 1 && page <= totalPages) {
        const filters = getFilters();
        loadAccounts(page, filters);
    }
}

// Get current filters
function getFilters() {
    return {
        sort: document.getElementById('sort').value,
        category: document.getElementById('categories').value,
        price_range: document.getElementById('priceRange').value,
        features: document.getElementById('features').value,
        search: document.getElementById('searchInput').value
    };
}

// Apply filters
function applyFilters() {
    const filters = getFilters();
    loadAccounts(1, filters);
}

// Event listeners for filters
document.getElementById('sort').addEventListener('change', applyFilters);
document.getElementById('categories').addEventListener('change', applyFilters);
document.getElementById('priceRange').addEventListener('change', applyFilters);
document.getElementById('features').addEventListener('change', applyFilters);

// Search functionality
document.getElementById('searchBtn').addEventListener('click', applyFilters);
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        applyFilters();
    }
});

// Initialize pagination
if (totalPages > 1) {
    updatePagination({
        current_page: 1,
        total_pages: totalPages,
        total_items: <?= $totalAccounts ?>
    });
}
</script>

<?php include 'footer.php'; ?> 
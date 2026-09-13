<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'Auth.php';

$page_title = "Sepetim";

// Cryptomus'tan gelen istekleri kontrol et
$isFromCryptomus = false;
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$currentPage = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($referer, 'cryptomus') !== false || strpos($referer, 'cryptomus.com') !== false || 
    strpos($currentPage, 'payment-cancel.php') !== false || strpos($currentPage, 'payment-success.php') !== false) {
    $isFromCryptomus = true;
}

// Kullanıcı giriş kontrolü (ziyaretçiler de sepet sayfasını görebilir)
$auth = new Auth();
$currentUser = null;
$isGuest = false;

if ($isFromCryptomus) {
    // Cryptomus'tan geliyorsa veya ödeme sayfalarındaysa, session kontrolünü atla
    // Ama kullanıcı bilgisini order_id'den almaya çalış
    $order_id = $_GET['order_id'] ?? '';
    if ($order_id) {
        $stmt = $pdo->prepare("SELECT user_id FROM crypto_payments WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $payment = $stmt->fetch();
        
        if ($payment) {
            $stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name FROM users WHERE id = ?");
            $stmt->execute([$payment['user_id']]);
            $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    
    // Eğer kullanıcı bilgisi alınamadıysa, normal session kontrolü yap
    if (!$currentUser) {
        if ($auth->isLoggedIn()) {
            $currentUser = $auth->getCurrentUser();
        } else {
            $isGuest = true;
        }
    }
} else {
    // Normal istek için session kontrolü yap
    if ($auth->isLoggedIn()) {
        $currentUser = $auth->getCurrentUser();
    } else {
        $isGuest = true;
    }
}

// Sepet verilerini al
$cartItems = [];
$totalPrice = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $accountIds = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($accountIds) - 1) . '?';
    
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.platform, a.account_type, a.price, 
               COALESCE(stock_counts.available_stock, 0) as stock_quantity,
               a.delivery_type, a.description
        FROM accounts a
        LEFT JOIN (
            SELECT account_id, 
                   COUNT(CASE WHEN is_sold = 0 THEN 1 END) as available_stock
            FROM account_stock 
            GROUP BY account_id
        ) stock_counts ON a.id = stock_counts.account_id
        WHERE a.id IN ($placeholders) AND a.status = 'active'
    ");
    $stmt->execute($accountIds);
    $accounts = $stmt->fetchAll();
    
    foreach ($accounts as $account) {
        $quantity = $_SESSION['cart'][$account['id']];
        $subtotal = $account['price'] * $quantity;
        
        $cartItems[] = [
            'account' => $account,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
        
        $totalPrice += $subtotal;
    }
}

include 'header.php';
?>

<!-- Page Header -->
<section class="page-header compact">
    <div class="container">
        <h1><i class="fas fa-shopping-cart"></i> Sepetim</h1>
        <p>Seçtiklerinizi inceleyin ve satın alın</p>
    </div>
</section>

<!-- Cart Section -->
<section class="cart-section">
    <div class="container">
        <?php if (empty($cartItems)): ?>
            <!-- Empty Cart -->
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3>Sepetiniz Boş</h3>
                <p>Henüz sepetinize hiç ürün eklemediniz. Hemen alışverişe başlayın!</p>
                <a href="hesaplar.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i>
                    Alışverişe Başla
                </a>
            </div>
        <?php else: ?>
            <!-- Cart Items -->
            <div class="cart-content">
                <div class="cart-items">
                    <div class="cart-header">
                        <h3><i class="fas fa-list"></i> Sepetinizde <?= count($cartItems) ?> Farklı Ürün</h3>
                        <button onclick="clearCart()" class="btn btn-outline btn-sm">
                            <i class="fas fa-trash"></i>
                            Sepeti Temizle
                        </button>
                    </div>
                    
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item" data-id="<?= $item['account']['id'] ?>">
                            <div class="item-image">
                                <div class="no-image">
                                    <i class="fab fa-<?= strtolower($item['account']['platform']) ?>"></i>
                                </div>
                            </div>
                            
                            <div class="item-details">
                                <h4><?= htmlspecialchars($item['account']['title']) ?></h4>
                                <div class="item-meta">
                                    <span class="platform">
                                        <i class="fab fa-<?= strtolower($item['account']['platform']) ?>"></i>
                                        <?= htmlspecialchars($item['account']['platform']) ?>
                                    </span>
                                    <span class="type"><?= htmlspecialchars($item['account']['account_type']) ?></span>
                                    <span class="delivery">
                                        <i class="fas fa-<?= $item['account']['delivery_type'] === 'instant' ? 'bolt' : 'clock' ?>"></i>
                                        <?= $item['account']['delivery_type'] === 'instant' ? 'Anında' : 'Manuel' ?>
                                    </span>
                                </div>
                                <div class="item-price">
                                    <span class="unit-price"><?= formatPrice($item['account']['price']) ?> / adet</span>
                                </div>
                            </div>
                            
                            <div class="item-quantity">
                                <label>Adet</label>
                                <div class="qty-controls">
                                    <button onclick="updateQuantity(<?= $item['account']['id'] ?>, -1)">-</button>
                                    <input type="number" 
                                           value="<?= $item['quantity'] ?>" 
                                           min="1" 
                                           max="<?= $item['account']['stock_quantity'] ?>"
                                           onchange="setQuantity(<?= $item['account']['id'] ?>, this.value)">
                                    <button onclick="updateQuantity(<?= $item['account']['id'] ?>, 1)">+</button>
                                </div>
                                <small>Maksimum: <?= $item['account']['stock_quantity'] ?></small>
                            </div>
                            
                            <div class="item-total">
                                <div class="total-price"><?= formatPrice($item['subtotal']) ?></div>
                                <button onclick="removeItem(<?= $item['account']['id'] ?>)" class="btn-remove">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Cart Summary -->
                <div class="cart-summary">
                    <div class="summary-card">
                        <h3><i class="fas fa-calculator"></i> Sipariş Özeti</h3>
                        
                        <div class="summary-row">
                            <span>Ürün Sayısı:</span>
                            <span><?= array_sum(array_column($cartItems, 'quantity')) ?> adet</span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Ara Toplam:</span>
                            <span><?= formatPrice($totalPrice) ?></span>
                        </div>
                        
                        <div class="summary-divider"></div>
                        
                        <div class="summary-row total">
                            <span>Toplam:</span>
                            <span><?= formatPrice($totalPrice) ?></span>
                        </div>
                        
                        <div class="summary-actions">
                            <a href="checkout.php" class="btn btn-primary btn-checkout">
                                <i class="fas fa-credit-card"></i>
                                Ödemeye Geç
                            </a>
                            <a href="hesaplar.php" class="btn btn-outline">
                                <i class="fas fa-plus"></i>
                                Alışverişe Devam
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($cartItems)): ?>
            <!-- Recommended Products -->
            <div class="recommended-section">
                <div class="section-header">
                    <h3><i class="fas fa-heart"></i> Bunları da Beğenebilirsiniz</h3>
                    <p>Sepetinizdeki ürünlere uygun diğer hesaplarımız</p>
                </div>
                
                <div class="recommended-products">
                    <?php
                    // Sepetteki platformları al
                    $cartPlatforms = [];
                    foreach ($cartItems as $item) {
                        $cartPlatforms[] = $item['account']['platform'];
                    }
                    $cartPlatforms = array_unique($cartPlatforms);
                    
                    // Sepetteki ürün ID'lerini al
                    $cartIds = array_keys($_SESSION['cart']);
                    
                    // Benzer ürünleri öner (aynı platform, farklı ürünler)
                    $placeholdersCart = str_repeat('?,', count($cartIds) - 1) . '?';
                    $placeholdersPlatforms = str_repeat('?,', count($cartPlatforms) - 1) . '?';
                    
                    $recommendedStmt = $pdo->prepare("
                        SELECT id, title, platform, account_type, price, stock_quantity, 
                               delivery_type, description
                        FROM accounts 
                        WHERE status = 'active' 
                        AND id NOT IN ($placeholdersCart)
                        AND platform IN ($placeholdersPlatforms)
                        AND stock_quantity > 0
                        ORDER BY RAND()
                        LIMIT 4
                    ");
                    $recommendedStmt->execute(array_merge($cartIds, $cartPlatforms));
                    $recommendedProducts = $recommendedStmt->fetchAll();
                    
                    if (empty($recommendedProducts)) {
                        // Eğer aynı platformda ürün yoksa, rastgele aktif ürünler öner
                        $recommendedStmt = $pdo->prepare("
                            SELECT id, title, platform, account_type, price, stock_quantity, 
                                   delivery_type, description
                            FROM accounts 
                            WHERE status = 'active' 
                            AND id NOT IN ($placeholdersCart)
                            AND stock_quantity > 0
                            ORDER BY RAND()
                            LIMIT 4
                        ");
                        $recommendedStmt->execute($cartIds);
                        $recommendedProducts = $recommendedStmt->fetchAll();
                    }
                    ?>
                    
                    <?php if (!empty($recommendedProducts)): ?>
                        <div class="products-grid">
                            <?php foreach ($recommendedProducts as $product): ?>
                                <div class="product-card">
                                    <div class="product-image">
                                        <div class="platform-icon">
                                            <i class="fab fa-<?= strtolower($product['platform']) ?>"></i>
                                        </div>
                                        <?php if ($product['delivery_type'] === 'instant'): ?>
                                            <div class="instant-badge">
                                                <i class="fas fa-bolt"></i>
                                                Anında
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="product-info">
                                        <h4><?= htmlspecialchars($product['title']) ?></h4>
                                        <div class="product-meta">
                                            <span class="platform">
                                                <i class="fab fa-<?= strtolower($product['platform']) ?>"></i>
                                                <?= htmlspecialchars($product['platform']) ?>
                                            </span>
                                            <span class="type"><?= htmlspecialchars($product['account_type']) ?></span>
                                        </div>
                                        <div class="product-stock">
                                            <i class="fas fa-box"></i>
                                            <?= $product['stock_quantity'] ?> stokta
                                        </div>
                                    </div>
                                    
                                    <div class="product-actions">
                                        <div class="product-price">
                                            <?= formatPrice($product['price']) ?>
                                        </div>
                                        <div class="action-buttons">
                                            <a href="<?= URLHelper::getProductUrl($product['id']) ?>" class="btn-view">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button onclick="addToCartFromRecommended(<?= $product['id'] ?>)" class="btn-add">
                                                <i class="fas fa-cart-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-recommendations">
                            <i class="fas fa-search"></i>
                            <p>Şu anda önerebileceğimiz başka ürün bulunmuyor.</p>
                            <a href="hesaplar.php" class="btn btn-primary">
                                <i class="fas fa-shopping-bag"></i>
                                Tüm Ürünleri Gör
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
// Cart Management Functions
function updateQuantity(accountId, change) {
    const input = document.querySelector(`[data-id="${accountId}"] input[type="number"]`);
    const currentValue = parseInt(input.value);
    const maxValue = parseInt(input.max);
    let newValue = currentValue + change;
    
    if (newValue < 1) newValue = 1;
    if (newValue > maxValue) newValue = maxValue;
    
    input.value = newValue;
    setQuantity(accountId, newValue);
}

function setQuantity(accountId, quantity) {
    quantity = parseInt(quantity);
    if (quantity < 1) return;
    
    fetch('update_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            account_id: accountId,
            quantity: quantity,
            action: 'update'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Sayfayı yenile
            location.reload();
        } else {
            showToast(data.error || 'Bir hata oluştu', 'error');
        }
    })
    .catch(error => {
        showToast('Bağlantı hatası oluştu', 'error');
    });
}

function removeItem(accountId) {
    if (!confirm('Bu ürünü sepetinizden çıkarmak istediğinizden emin misiniz?')) {
        return;
    }
    
    fetch('update_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            account_id: accountId,
            action: 'remove'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Ürünü sayfadan kaldır
            const item = document.querySelector(`[data-id="${accountId}"]`);
            item.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                location.reload();
            }, 300);
            
            showToast('Ürün sepetten çıkarıldı');
        } else {
            showToast(data.error || 'Bir hata oluştu', 'error');
        }
    })
    .catch(error => {
        showToast('Bağlantı hatası oluştu', 'error');
    });
}

function clearCart() {
    if (!confirm('Sepetinizdeki tüm ürünleri silmek istediğinizden emin misiniz?')) {
        return;
    }
    
    fetch('update_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'clear'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
            showToast('Sepet temizlendi');
        } else {
            showToast(data.error || 'Bir hata oluştu', 'error');
        }
    })
    .catch(error => {
        showToast('Bağlantı hatası oluştu', 'error');
    });
}

function addToCartFromRecommended(accountId) {
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            account_id: accountId,
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Ürün sepete eklendi!');
            
            // Header'daki sepet sayacını güncelle
            if (data.cart_count) {
                const cartCountElement = document.getElementById('cartCount');
                if (cartCountElement) {
                    cartCountElement.textContent = data.cart_count;
                    if (data.cart_count > 0) {
                        cartCountElement.style.display = 'flex';
                    }
                }
            }
            
            // Sayfayı yenile (yeni ürün sepette görünsün)
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showToast(data.error || 'Bir hata oluştu', 'error');
        }
    })
    .catch(error => {
        showToast('Bağlantı hatası oluştu', 'error');
    });
}

function showToast(message, type = 'success') {
    // Mevcut toast'ları kaldır
    const existingToasts = document.querySelectorAll('.toast');
    existingToasts.forEach(toast => toast.remove());
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
    toast.innerHTML = `
        <i class="fas ${icon}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 4000);
}
</script>

<style>
/* Cart Section */
.cart-section {
    padding: 2rem 0 4rem;
    min-height: 70vh;
}

/* Empty Cart */
.empty-cart {
    text-align: center;
    padding: 4rem 2rem;
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    backdrop-filter: blur(10px);
}

.empty-cart-icon {
    font-size: 4rem;
    color: var(--primary);
    margin-bottom: 1.5rem;
    opacity: 0.7;
}

.empty-cart h3 {
    color: var(--text-primary);
    margin-bottom: 1rem;
}

.empty-cart p {
    color: var(--text-secondary);
    margin-bottom: 2rem;
}

/* Cart Content */
.cart-content {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 2rem;
    align-items: start;
}

/* Cart Items */
.cart-items {
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    backdrop-filter: blur(10px);
    overflow: hidden;
}

.cart-header {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.cart-header h3 {
    color: var(--text-primary);
    margin: 0;
}

/* Cart Item */
.cart-item {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    display: grid;
    grid-template-columns: 80px 1fr auto auto auto;
    gap: 1.5rem;
    align-items: center;
    transition: all 0.3s ease;
}

.cart-item:last-child {
    border-bottom: none;
}

.cart-item:hover {
    background: rgba(255, 255, 255, 0.02);
}

.item-image {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    overflow: hidden;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    width: 100%;
    height: 100%;
    background: var(--gradient-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: white;
}

.item-details h4 {
    color: var(--text-primary);
    margin-bottom: 0.5rem;
    font-size: 1.1rem;
}

.item-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.item-meta span {
    font-size: 0.85rem;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.item-price .unit-price {
    color: var(--accent);
    font-weight: 600;
}

/* Quantity Controls */
.item-quantity {
    text-align: center;
}

.item-quantity label {
    display: block;
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
}

.qty-controls {
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    overflow: hidden;
}

.qty-controls button {
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    color: var(--text-primary);
    cursor: pointer;
    transition: background 0.2s;
}

.qty-controls button:hover {
    background: rgba(255, 255, 255, 0.1);
}

.qty-controls input {
    width: 50px;
    height: 32px;
    border: none;
    background: transparent;
    color: var(--text-primary);
    text-align: center;
    font-size: 0.9rem;
}

.item-quantity small {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: var(--text-secondary);
}

/* Item Total */
.item-total {
    text-align: right;
}

.total-price {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--accent);
    margin-bottom: 0.5rem;
}

.btn-remove {
    background: transparent;
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #ef4444;
    padding: 0.5rem;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-remove:hover {
    background: rgba(239, 68, 68, 0.1);
    border-color: #ef4444;
}

/* Cart Summary */
.cart-summary {
    position: sticky;
    top: 2rem;
}

.summary-card {
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    backdrop-filter: blur(10px);
    padding: 2rem;
}

.summary-card h3 {
    color: var(--text-primary);
    margin-bottom: 1.5rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
    color: var(--text-secondary);
}

.summary-row.total {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--text-primary);
}

.summary-divider {
    height: 1px;
    background: rgba(255, 255, 255, 0.1);
    margin: 1.5rem 0;
}

.summary-actions {
    margin-top: 2rem;
}

.btn-checkout {
    width: 100%;
    padding: 1rem;
    font-size: 1.1rem;
    margin-bottom: 1rem;
}

.btn-outline {
    width: 100%;
    text-align: center;
}

/* Animations */
@keyframes slideOut {
    to {
        transform: translateX(-100%);
        opacity: 0;
    }
}

/* Toast Notifications */
.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    color: var(--text-primary);
    padding: 1rem 1.5rem;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transform: translateX(400px);
    opacity: 0;
    transition: all 0.3s ease;
    z-index: 9999;
    max-width: 300px;
}

.toast.show {
    transform: translateX(0);
    opacity: 1;
}

.toast.toast-success {
    border-left: 4px solid #28a745;
}

.toast.toast-error {
    border-left: 4px solid #dc3545;
}

.toast i {
    font-size: 1.2rem;
}

.toast.toast-success i {
    color: #28a745;
}

.toast.toast-error i {
    color: #dc3545;
}

/* Recommended Products */
.recommended-section {
    margin-top: 3rem;
    padding: 2rem 0;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.section-header {
    text-align: center;
    margin-bottom: 2rem;
}

.section-header h3 {
    color: var(--text-primary);
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.section-header h3 i {
    color: var(--tertiary);
}

.section-header p {
    color: var(--text-secondary);
    margin: 0;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.product-card {
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.product-card:hover {
    transform: translateY(-4px);
    border-color: var(--primary);
    box-shadow: 0 8px 32px rgba(var(--primary-rgb), 0.2);
}

.product-image {
    position: relative;
    text-align: center;
    margin-bottom: 1rem;
}

.platform-icon {
    width: 60px;
    height: 60px;
    background: var(--gradient-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.5rem;
    color: white;
}

.instant-badge {
    position: absolute;
    top: -8px;
    right: 50%;
    transform: translateX(50%);
    background: var(--tertiary);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.product-info h4 {
    color: var(--text-primary);
    font-size: 1rem;
    margin-bottom: 0.75rem;
    text-align: center;
}

.product-meta {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 0.75rem;
}

.product-meta span {
    font-size: 0.8rem;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.product-stock {
    text-align: center;
    font-size: 0.8rem;
    color: var(--accent);
    margin-bottom: 1rem;
}

.product-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.product-price {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--accent);
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-view,
.btn-add {
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn-view {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-primary);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-view:hover {
    background: var(--primary);
    color: white;
    text-decoration: none;
}

.btn-add {
    background: var(--gradient-primary);
    color: white;
    border: 1px solid transparent;
}

.btn-add:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.4);
}

.no-recommendations {
    text-align: center;
    padding: 3rem 2rem;
    color: var(--text-secondary);
}

.no-recommendations i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.7;
}

.no-recommendations p {
    margin-bottom: 1.5rem;
}

/* Responsive */
@media (max-width: 968px) {
    .cart-content {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .cart-item {
        grid-template-columns: 1fr;
        gap: 1rem;
        text-align: center;
    }
    
    .item-meta {
        justify-content: center;
    }
    
    .products-grid {
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1rem;
    }
    
    .product-card {
        padding: 1rem;
    }
}

@media (max-width: 768px) {
    .cart-section {
        padding: 1rem 0 2rem;
    }
    
    .cart-items,
    .summary-card {
        padding: 1rem;
    }
    
    .cart-item {
        padding: 1rem;
    }
    
    .item-image {
        width: 60px;
        height: 60px;
    }
}
</style>

<?php include 'footer.php'; ?>

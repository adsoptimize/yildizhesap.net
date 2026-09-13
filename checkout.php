<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'Auth.php';

// Cryptomus'tan gelen istekleri kontrol et
$isFromCryptomus = false;
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$currentPage = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($referer, 'cryptomus') !== false || strpos($referer, 'cryptomus.com') !== false || 
    strpos($currentPage, 'payment-cancel.php') !== false || strpos($currentPage, 'payment-success.php') !== false) {
    $isFromCryptomus = true;
}

// Kullanıcı giriş kontrolü
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
            $currentUser = [
                'id' => null,
                'username' => 'Ziyaretçi',
                'email' => '',
                'first_name' => '',
                'last_name' => ''
            ];
        }
    }
} else {
    // Normal istek için session kontrolü yap
    if ($auth->isLoggedIn()) {
        $currentUser = $auth->getCurrentUser();
    } else {
        // Ziyaretçi ise guest olarak işaretle
        $isGuest = true;
        $currentUser = [
            'id' => null,
            'username' => 'Ziyaretçi',
            'email' => '',
            'first_name' => '',
            'last_name' => ''
        ];
    }
}

// Sepet bilgilerini al
$cartItems = [];
$error = '';
$success = '';

// Sepetteki öğeleri getir
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $accountId => $quantity) {
        $stmt = $pdo->prepare("
            SELECT a.*, COALESCE(stock_counts.available_stock, 0) as stock_quantity
            FROM accounts a 
            LEFT JOIN (
                SELECT account_id, 
                       COUNT(CASE WHEN is_sold = 0 THEN 1 END) as available_stock
                FROM account_stock 
                GROUP BY account_id
            ) stock_counts ON a.id = stock_counts.account_id
            WHERE a.id = ? AND a.status = 'active'
        ");
        $stmt->execute([$accountId]);
        $account = $stmt->fetch();
        
        if ($account && $account['stock_quantity'] >= $quantity) {
            $cartItems[] = [
                'account' => $account,
                'quantity' => $quantity,
                'total' => $account['price'] * $quantity
            ];
        }
    }
}

// Toplam tutarı hesapla
$totalAmount = 0;
foreach ($cartItems as $item) {
    $totalAmount += $item['total'];
}

// Cryptomus ayarlarını kontrol et
$cryptomusEnabled = false;
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM crypto_settings WHERE setting_key = 'cryptomus_enabled'");
    $stmt->execute();
    $cryptomusEnabled = ($stmt->fetchColumn() === '1');
} catch (Exception $e) {
    error_log("Cryptomus settings check error: " . $e->getMessage());
}

include 'header.php';
?>

<style>
.checkout-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.checkout-card {
    background: var(--card-bg);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.section-title {
    color: var(--text-primary);
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.cart-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    margin-bottom: 1rem;
}

.item-image {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    background: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}

.item-details {
    flex: 1;
}

.item-title {
    color: var(--text-primary);
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.item-price {
    color: var(--accent);
    font-weight: 600;
}

.item-quantity {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.quantity-btn {
    width: 30px;
    height: 30px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-primary);
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    font-size: 0.8rem;
}

.quantity-btn:hover:not(:disabled) {
    background: var(--primary);
    border-color: var(--primary);
    color: white;
}

.quantity-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.quantity-display {
    min-width: 80px;
    text-align: center;
    font-weight: 500;
}

.stock-info {
    font-size: 0.8rem;
    color: var(--text-secondary);
    opacity: 0.8;
}

.total-summary {
    background: rgba(var(--primary-rgb), 0.1);
    border-radius: 10px;
    padding: 1.5rem;
    text-align: center;
}

.total-amount {
    font-size: 2rem;
    font-weight: bold;
    color: var(--accent);
}

.payment-section {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    padding: 1.5rem;
    margin-top: 1rem;
}

.payment-method {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    margin-bottom: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.payment-method:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
}

.payment-method.selected {
    background: rgba(var(--primary-rgb), 0.2);
    border: 2px solid var(--primary);
}

.payment-icon {
    font-size: 2rem;
    color: var(--accent);
}

.payment-info h4 {
    color: var(--text-primary);
    margin: 0 0 0.25rem 0;
}

.payment-info p {
    color: var(--text-secondary);
    margin: 0;
    font-size: 0.9rem;
}

.checkout-btn {
    background: linear-gradient(135deg, var(--primary), var(--accent));
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 10px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 1rem;
    width: 100%;
}

.checkout-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(var(--primary-rgb), 0.3);
}

.checkout-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* Ziyaretçi Bilgileri Stilleri */
.guest-info-section {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.guest-info-section h3 {
    color: var(--text-primary);
    font-size: 1.2rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.guest-info-section h3 i {
    color: var(--primary);
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    color: var(--text-primary);
    font-weight: 500;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    color: var(--text-primary);
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    background: rgba(255, 255, 255, 0.1);
    box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
}

.form-control::placeholder {
    color: var(--text-secondary);
}

/* Ödeme Yöntemleri Stilleri */
.payment-methods {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 2rem;
}

.payment-method {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.05);
    border: 2px solid transparent;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.payment-method:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(var(--primary-rgb), 0.3);
    transform: translateY(-2px);
}

.payment-method.selected {
    background: rgba(var(--primary-rgb), 0.1);
    border-color: var(--primary);
    box-shadow: 0 4px 15px rgba(var(--primary-rgb), 0.2);
}

.payment-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--primary), var(--accent));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.payment-info {
    flex: 1;
}

.payment-info h4 {
    margin: 0 0 0.5rem 0;
    color: var(--text-primary);
    font-size: 1.1rem;
    font-weight: 600;
}

.payment-info p {
    margin: 0;
    color: var(--text-secondary);
    font-size: 0.9rem;
    line-height: 1.4;
}

.payment-radio {
    position: relative;
    width: 24px;
    height: 24px;
    flex-shrink: 0;
}

.payment-radio input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.payment-radio label {
    position: absolute;
    top: 0;
    left: 0;
    width: 24px;
    height: 24px;
    border: 2px solid var(--border);
    border-radius: 50%;
    background: transparent;
    cursor: pointer;
    transition: all 0.3s ease;
}

.payment-radio input[type="radio"]:checked + label {
    border-color: var(--primary);
    background: var(--primary);
}

.payment-radio input[type="radio"]:checked + label::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 8px;
    height: 8px;
    background: white;
    border-radius: 50%;
}

.payment-info-box {
    margin-top: 1rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    font-size: 0.9rem;
    color: var(--text-secondary);
    border-left: 4px solid var(--primary);
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.alert-error {
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.3);
    color: #dc3545;
}

.alert-success {
    background: rgba(40, 167, 69, 0.1);
    border: 1px solid rgba(40, 167, 69, 0.3);
    color: #28a745;
}

.empty-cart {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-secondary);
}

.continue-shopping {
    display: inline-block;
    margin-top: 1rem;
    padding: 0.75rem 1.5rem;
    background: var(--primary);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.continue-shopping:hover {
    background: var(--accent);
    text-decoration: none;
    color: white;
}

@media (max-width: 768px) {
    .checkout-container {
        margin: 1rem auto;
        padding: 0 0.5rem;
    }
    
    .checkout-card {
        padding: 1.5rem;
    }
    
    .cart-item {
        flex-direction: column;
        text-align: center;
    }
    
    .total-amount {
        font-size: 1.5rem;
    }
    
    .checkout-btn {
        padding: 1.2rem 2rem;
        font-size: 1.2rem;
    }
}
</style>

<div class="checkout-container">
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?php 
            $errorType = $_GET['error'];
            $errorMessage = $_GET['message'] ?? '';
            
            switch ($errorType) {
                case 'api_error':
                    echo 'API Hatası: ' . htmlspecialchars($errorMessage);
                    break;
                case 'payment_error':
                    echo 'Ödeme işlemi sırasında bir hata oluştu. Lütfen tekrar deneyin.';
                    break;
                case 'empty_cart':
                    echo 'Sepetiniz boş. Lütfen satın almak istediğiniz hesapları seçin.';
                    break;
                case 'invalid_items':
                    echo 'Sepetinizde geçersiz ürünler bulunuyor. Lütfen tekrar seçin.';
                    break;
                case 'database_error':
                    echo 'Veritabanı hatası oluştu. Lütfen daha sonra tekrar deneyin.';
                    break;
                default:
                    echo 'Bir hata oluştu: ' . htmlspecialchars($errorMessage);
            }
            ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>
        <div class="checkout-card">
            <div class="empty-cart">
                <i class="fas fa-shopping-cart" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
                <h2>Sepetiniz Boş</h2>
                <p>Satın almak istediğiniz hesapları sepete ekleyin.</p>
                <a href="hesaplar.php" class="continue-shopping">
                    <i class="fas fa-arrow-left"></i> Alışverişe Devam Et
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Sepet Özeti -->
        <div class="checkout-card">
            <h2 class="section-title">
                <i class="fas fa-shopping-cart"></i>
                Sepet Özeti
            </h2>
            
            <?php foreach ($cartItems as $item): ?>
                <div class="cart-item">
                    <div class="item-image">
                        <?= strtoupper(substr($item['account']['platform'], 0, 2)) ?>
                    </div>
                    <div class="item-details">
                        <div class="item-title"><?= htmlspecialchars($item['account']['title']) ?></div>
                        <div class="item-price"><?= formatPrice($item['account']['price']) ?></div>
                        <div class="item-quantity">
                            <div class="quantity-controls">
                                <button type="button" class="quantity-btn" onclick="updateQuantity(<?= $item['account']['id'] ?>, -1, event)" <?= $item['quantity'] <= 1 ? 'disabled' : '' ?>>
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="quantity-display">Adet: <?= $item['quantity'] ?></span>
                                <button type="button" class="quantity-btn" onclick="updateQuantity(<?= $item['account']['id'] ?>, 1, event)" <?= $item['quantity'] >= $item['account']['stock_quantity'] ? 'disabled' : '' ?>>
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <div class="stock-info">Stok: <?= $item['account']['stock_quantity'] ?></div>
                        </div>
                    </div>
                    <div class="item-total">
                        <strong><?= formatPrice($item['total']) ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="total-summary">
                <div style="color: var(--text-secondary); margin-bottom: 0.5rem;">Toplam Tutar</div>
                <div class="total-amount"><?= formatPrice($totalAmount) ?></div>
            </div>
        </div>

        <!-- Ödeme Seçenekleri -->
        <div class="checkout-card">
            <h2 class="section-title">
                <i class="fas fa-credit-card"></i>
                Ödeme Yöntemi
            </h2>
            
            <div class="payment-section">
                <?php
                // Ödeme yöntemlerini kontrol et
                $cryptomusEnabled = false;
                $shopierEnabled = false;
                
                try {
                    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM crypto_settings WHERE setting_key IN ('cryptomus_enabled', 'shopier_enabled')");
                    $stmt->execute();
                    $paymentSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                    
                    $cryptomusEnabled = ($paymentSettings['cryptomus_enabled'] ?? '0') === '1';
                    $shopierEnabled = ($paymentSettings['shopier_enabled'] ?? '0') === '1';
                } catch (Exception $e) {
                    error_log("Payment settings check error: " . $e->getMessage());
                }
                
                if ($cryptomusEnabled || $shopierEnabled):
                ?>
                    <div class="payment-methods">
                        <?php if ($cryptomusEnabled): ?>
                            <div class="payment-method" data-method="cryptomus" onclick="selectPaymentMethod('cryptomus')">
                                <div class="payment-icon">
                                   <i class="fa-brands fa-bitcoin"></i>
                                </div>
                                <div class="payment-info">
                                    <h4>Kripto Para ile Ödeme</h4>
                                    <p>Bitcoin, Ethereum, USDT ve diğer kripto para birimleri ile güvenli ödeme</p>
                                </div>
                                <div class="payment-radio">
                                    <input type="radio" name="payment_method" value="cryptomus" id="cryptomus_radio">
                                    <label for="cryptomus_radio"></label>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($shopierEnabled): ?>
                            <div class="payment-method" data-method="shopier" onclick="selectPaymentMethod('shopier')">
                                <div class="payment-icon">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                                <div class="payment-info">
                                    <h4>Kredi Kartı ile Ödeme</h4>
                                    <p>Shopier güvenli ödeme sistemi ile kredi kartı, banka kartı ve havale seçenekleri</p>
                                </div>
                                <div class="payment-radio">
                                    <input type="radio" name="payment_method" value="shopier" id="shopier_radio">
                                    <label for="shopier_radio"></label>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST" action="create_payment.php" id="payment-form">
                        <input type="hidden" name="payment_method" id="selected_payment_method" value="">
                        
                        <?php if ($isGuest): ?>
                            <!-- Ziyaretçi Bilgileri -->
                            <div class="guest-info-section">
                                <h3><i class="fas fa-user"></i> Ziyaretçi Bilgileri</h3>
                                <div class="form-group">
                                    <label for="guest_name">Ad Soyad *</label>
                                    <input type="text" id="guest_name" name="guest_name" required 
                                           placeholder="Adınız ve soyadınız" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="guest_email">E-posta Adresi *</label>
                                    <input type="email" id="guest_email" name="guest_email" required 
                                           placeholder="ornek@email.com" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="guest_phone">Telefon Numarası</label>
                                    <input type="tel" id="guest_phone" name="guest_phone" 
                                           placeholder="+90 5XX XXX XX XX" class="form-control">
                                </div>
                            </div>
                        <?php endif; ?>
                        <button type="submit" class="checkout-btn" id="payment-btn" disabled>
                            <i class="fas fa-lock"></i>
                            Ödeme Yöntemi Seçin
                        </button>
                    </form>
                    
                    <div class="payment-info-box">
                        <i class="fas fa-info-circle"></i>
                        <strong>Bilgi:</strong> 
                        <span id="payment-info-text">Lütfen bir ödeme yöntemi seçin.</span>
                    </div>
                <?php else: ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        Ödeme sistemi şu anda aktif değil. Lütfen daha sonra tekrar deneyin veya admin ile iletişime geçin.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function updateQuantity(accountId, change, event) {
    // AJAX ile miktar güncelleme
    // Tıklanan butona göre quantity-display elementini bul
    const clickedButton = event.target.closest('.quantity-btn');
    const quantityControls = clickedButton.closest('.quantity-controls');
    const currentQuantitySpan = quantityControls.querySelector('.quantity-display');
    
    if (!currentQuantitySpan) {
        console.error('Quantity display element not found for account ID:', accountId);
        return;
    }
    
    const currentQuantity = parseInt(currentQuantitySpan.textContent.replace('Adet: ', ''));
    const newQuantity = currentQuantity + change;
    
    if (newQuantity < 1) {
        return; // Minimum 1 adet olmalı
    }
    
    fetch('update_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update',
            account_id: accountId,
            quantity: newQuantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Sayfayı yenile
            location.reload();
        } else {
            alert(data.error || 'Bir hata oluştu');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu');
    });
}

function selectPaymentMethod(method) {
    // Tüm ödeme yöntemlerinden seçili sınıfını kaldır
    document.querySelectorAll('.payment-method').forEach(el => {
        el.classList.remove('selected');
    });
    
    // Seçilen yönteme seçili sınıfını ekle
    const selectedMethod = document.querySelector(`[data-method="${method}"]`);
    if (selectedMethod) {
        selectedMethod.classList.add('selected');
    }
    
    // Radio button'u seç
    const radio = document.getElementById(method + '_radio');
    if (radio) {
        radio.checked = true;
    }
    
    // Hidden input'u güncelle
    const selectedPaymentMethodInput = document.getElementById('selected_payment_method');
    if (selectedPaymentMethodInput) {
        selectedPaymentMethodInput.value = method;
    }
    
    // Ödeme butonunu aktif et
    const paymentBtn = document.getElementById('payment-btn');
    const paymentInfoText = document.getElementById('payment-info-text');
    
    if (!paymentBtn || !paymentInfoText) {
        return; // Elementler bulunamadı
    }
    
    paymentBtn.disabled = false;
    
    if (method === 'cryptomus') {
        paymentBtn.innerHTML = '<i class="fa-brands fa-bitcoin"></i> Kripto Para ile Öde (<?= formatPrice($totalAmount) ?>)';
        paymentInfoText.textContent = 'Ödeme işlemi Cryptomus güvenli ödeme sistemi üzerinden gerçekleştirilir. Ödeme onaylandıktan sonra hesaplarınız otomatik olarak hesabınıza aktarılacaktır.';
    } else if (method === 'shopier') {
        paymentBtn.innerHTML = '<i class="fa-solid fa-credit-card"></i> Kredi Kartı ile Öde (<?= formatPrice($totalAmount) ?>)';
        paymentInfoText.textContent = 'Ödeme işlemi Shopier güvenli ödeme sistemi üzerinden gerçekleştirilir. Kredi kartı, banka kartı ve havale seçenekleri mevcuttur.';
    }
}

// Sayfa yüklendiğinde ilk ödeme yöntemini seç
document.addEventListener('DOMContentLoaded', function() {
    const firstPaymentMethod = document.querySelector('.payment-method');
    if (firstPaymentMethod) {
        const method = firstPaymentMethod.dataset.method;
        selectPaymentMethod(method);
    }
    
    // Ziyaretçi bilgileri form içinde, validation HTML'de yapılıyor
    <?php if ($isGuest): ?>
    // Debug için form submit'i logla
    const paymentForm = document.getElementById('payment-form');
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            console.log('Form submitting...');
            console.log('Guest name:', document.getElementById('guest_name').value);
            console.log('Guest email:', document.getElementById('guest_email').value);
            console.log('Guest phone:', document.getElementById('guest_phone').value);
            console.log('Payment method:', document.getElementById('selected_payment_method').value);
        });
    }
    <?php endif; ?>
});
</script>

<?php include 'footer.php'; ?>

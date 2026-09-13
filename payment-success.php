<?php
// Output buffering başlat
ob_start();

require_once 'config.php';
require_once 'functions.php';
require_once 'Auth.php';
require_once 'PaymentProcessor.php';

// Cryptomus'tan gelen istekleri kontrol et
$isFromCryptomus = false;
$referer = $_SERVER['HTTP_REFERER'] ?? '';
if (strpos($referer, 'cryptomus') !== false || strpos($referer, 'cryptomus.com') !== false) {
    $isFromCryptomus = true;
}

// Kullanıcı giriş kontrolü
$auth = new Auth();
$currentUser = null;
$isGuest = false;
$order_id = $_GET['order_id'] ?? '';

if ($isFromCryptomus) {
    // Cryptomus'tan geliyorsa session kontrolünü atla
    // Ziyaretçi ödemeleri için order_id'den bilgi al
    if ($order_id) {
        $stmt = $pdo->prepare("SELECT user_id, is_guest_order, email, customer_name, phone FROM crypto_payments WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $payment = $stmt->fetch();
        
        if ($payment) {
            if ($payment['is_guest_order']) {
                // Ziyaretçi ödemesi
                $isGuest = true;
                $currentUser = [
                    'id' => null,
                    'username' => 'Ziyaretçi',
                    'email' => $payment['email'],
                    'first_name' => $payment['customer_name'],
                    'last_name' => '',
                    'phone' => $payment['phone']
                ];
            } else {
                // Kayıtlı kullanıcı ödemesi
                $stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name FROM users WHERE id = ?");
                $stmt->execute([$payment['user_id']]);
                $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
    }
} else {
    // Normal istek için session kontrolü yap
    if (!$auth->isLoggedIn()) {
        // Ziyaretçi ise order_id'den bilgi almaya çalış
        if ($order_id) {
            $stmt = $pdo->prepare("SELECT user_id, is_guest_order, email, customer_name, phone FROM crypto_payments WHERE order_id = ?");
            $stmt->execute([$order_id]);
            $payment = $stmt->fetch();
            
            if ($payment && $payment['is_guest_order']) {
                // Ziyaretçi ödemesi
                $isGuest = true;
                $currentUser = [
                    'id' => null,
                    'username' => 'Ziyaretçi',
                    'email' => $payment['email'],
                    'first_name' => $payment['customer_name'],
                    'last_name' => '',
                    'phone' => $payment['phone']
                ];
            } else {
                // Kayıtlı kullanıcı ama giriş yapmamış
                header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
                exit;
            }
        } else {
            // Order_id yoksa giriş sayfasına yönlendir
            header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    } else {
        $currentUser = $auth->getCurrentUser();
    }
}

// PaymentProcessor'ı başlat
$paymentProcessor = new PaymentProcessor();

// Ödeme durumunu kontrol et ve hesapları ata
$paymentStatus = 'unknown';
$orderDetails = null;
$assignedAccounts = [];

if ($order_id) {
    // Önce sipariş durumunu kontrol et
    if ($isGuest) {
        // Ziyaretçi ödemesi için user_id kontrolü yapma
        $stmt = $pdo->prepare("SELECT status FROM crypto_payments WHERE order_id = ? AND is_guest_order = 1");
        $stmt->execute([$order_id]);
    } else {
        // Kayıtlı kullanıcı ödemesi
        if ($currentUser && isset($currentUser['id'])) {
            $stmt = $pdo->prepare("SELECT status FROM crypto_payments WHERE order_id = ? AND user_id = ?");
            $stmt->execute([$order_id, $currentUser['id']]);
        } else {
            // Kullanıcı bilgisi yoksa sadece order_id ile kontrol et
            $stmt = $pdo->prepare("SELECT status FROM crypto_payments WHERE order_id = ?");
            $stmt->execute([$order_id]);
        }
    }
    $payment = $stmt->fetch();
    $paymentStatus = $payment['status'] ?? 'unknown';
    
    // Sipariş detaylarını al
    $orderDetails = $paymentProcessor->getOrderStatus($order_id);
    
    // Eğer ödeme başarılıysa ve hesaplar henüz atanmamışsa, ata
    if ($paymentStatus === 'paid' && $orderDetails && $orderDetails['status'] !== 'completed') {
        $result = $paymentProcessor->processSuccessfulPayment($order_id);
        if ($result['success']) {
            $assignedAccounts = $result['assigned_accounts'] ?? [];
            // Sipariş durumunu yeniden al
            $orderDetails = $paymentProcessor->getOrderStatus($order_id);
        }
    }
    
    // Atanan hesapları al
    if ($orderDetails && $orderDetails['assigned_accounts_count'] > 0) {
        $stmt = $pdo->prepare("
            SELECT username, password, email, email_password, account_created_date 
            FROM order_accounts 
            WHERE order_id = ? AND is_active = 1
        ");
        $stmt->execute([$order_id]);
        $assignedAccounts = $stmt->fetchAll();
    }
}

include 'header.php';
?>

<style>
.success-container {
    max-width: 800px;
    margin: 3rem auto;
    padding: 2rem;
    text-align: center;
}

.success-card {
    background: var(--card-bg);
    border-radius: 15px;
    padding: 3rem 2rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.success-icon {
    font-size: 4rem;
    color: #28a745;
    margin-bottom: 1rem;
}

.success-title {
    color: var(--text-primary);
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.success-message {
    color: var(--text-secondary);
    font-size: 1.1rem;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.order-info {
    background: rgba(40, 167, 69, 0.1);
    border: 1px solid rgba(40, 167, 69, 0.3);
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    text-align: left;
}

.order-id {
    font-family: monospace;
    background: rgba(0, 0, 0, 0.1);
    padding: 0.5rem 1rem;
    border-radius: 5px;
    color: var(--text-primary);
    display: inline-block;
    margin: 0.5rem 0;
}

.accounts-section {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    text-align: left;
}

.account-item {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    border-left: 4px solid #28a745;
}

.account-item:last-child {
    margin-bottom: 0;
}

.account-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.account-title {
    font-weight: 600;
    color: var(--text-primary);
}

.account-date {
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.account-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.5rem;
    font-size: 0.9rem;
}

.account-detail {
    display: flex;
    justify-content: space-between;
    padding: 0.25rem 0;
}

.account-label {
    color: var(--text-secondary);
    font-weight: 500;
}

.account-value {
    color: var(--text-primary);
    font-family: monospace;
    background: rgba(0, 0, 0, 0.2);
    padding: 0.1rem 0.5rem;
    border-radius: 3px;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--accent);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(var(--primary-rgb), 0.3);
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-primary);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

.btn-copy {
    background: rgba(40, 167, 69, 0.2);
    color: #28a745;
    border: 1px solid rgba(40, 167, 69, 0.3);
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-copy:hover {
    background: rgba(40, 167, 69, 0.3);
}

@media (max-width: 768px) {
    .success-container {
        margin: 1rem auto;
        padding: 1rem;
    }
    
    .success-card {
        padding: 2rem 1rem;
    }
    
    .success-title {
        font-size: 1.5rem;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn {
        justify-content: center;
    }
    
    .account-details {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="success-container">
    <div class="success-card">
        <?php if ($paymentStatus === 'paid' && $orderDetails): ?>
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="success-title">Ödeme Başarılı!</h1>
            <p class="success-message">
                Ödemeniz başarıyla tamamlandı. Satın aldığınız hesaplar hesabınıza aktarıldı.
                Hesap bilgilerinizi aşağıda görüntüleyebilirsiniz.
            </p>
            
                <div class="order-info">
                    <strong>Sipariş Numarası:</strong><br>
                <span class="order-id"><?= htmlspecialchars($order_id) ?></span><br><br>
                
                <strong>Ürün:</strong> <?= htmlspecialchars($orderDetails['product_name']) ?><br>
                <strong>Miktar:</strong> <?= $orderDetails['quantity'] ?> adet<br>
                <strong>Toplam Tutar:</strong> <?= formatPrice($orderDetails['total_price']) ?><br>
                <strong>Durum:</strong> 
                <span style="color: #28a745; font-weight: 600;">
                    <?= $orderDetails['status'] === 'completed' ? 'Tamamlandı' : 'İşleniyor' ?>
                </span>
            </div>
            
            <?php if (!empty($assignedAccounts)): ?>
                <div class="accounts-section">
                    <h3 style="margin-bottom: 1rem; color: var(--text-primary);">
                        <i class="fas fa-key"></i> Hesap Bilgileriniz
                    </h3>
                    
                    <?php foreach ($assignedAccounts as $index => $account): ?>
                        <div class="account-item">
                            <div class="account-header">
                                <span class="account-title">Hesap #<?= $index + 1 ?></span>
                                <span class="account-date">
                                    <?= date('d.m.Y', strtotime($account['account_created_date'])) ?>
                                </span>
                            </div>
                            <div class="account-details">
                                <div class="account-detail">
                                    <span class="account-label">Kullanıcı Adı:</span>
                                    <span class="account-value" id="username-<?= $index ?>"><?= htmlspecialchars($account['username']) ?></span>
                                    <button class="btn-copy" onclick="copyToClipboard('username-<?= $index ?>')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <div class="account-detail">
                                    <span class="account-label">Şifre:</span>
                                    <span class="account-value" id="password-<?= $index ?>"><?= htmlspecialchars($account['password']) ?></span>
                                    <button class="btn-copy" onclick="copyToClipboard('password-<?= $index ?>')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <div class="account-detail">
                                    <span class="account-label">E-posta:</span>
                                    <span class="account-value" id="email-<?= $index ?>"><?= htmlspecialchars($account['email']) ?></span>
                                    <button class="btn-copy" onclick="copyToClipboard('email-<?= $index ?>')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <div class="account-detail">
                                    <span class="account-label">E-posta Şifresi:</span>
                                    <span class="account-value" id="email-password-<?= $index ?>"><?= htmlspecialchars($account['email_password']) ?></span>
                                    <button class="btn-copy" onclick="copyToClipboard('email-password-<?= $index ?>')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="accounts-section">
                    <p style="color: var(--text-secondary); margin: 0;">
                        <i class="fas fa-clock"></i> Hesaplarınız işleniyor, lütfen bekleyin...
                    </p>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="success-icon" style="color: #ffc107;">
                <i class="fas fa-clock"></i>
            </div>
            <h1 class="success-title">Ödeme İşleniyor</h1>
            <p class="success-message">
                Ödemeniz alındı ve işleniyor. Ödeme onaylandıktan sonra hesaplarınız 
                otomatik olarak hesabınıza aktarılacaktır. Bu işlem birkaç dakika sürebilir.
            </p>
            
            <?php if ($order_id): ?>
                <div class="order-info">
                    <strong>Sipariş Numarası:</strong><br>
                    <span class="order-id"><?= htmlspecialchars($order_id) ?></span>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="action-buttons">
            <a href="hesaplarim.php" class="btn btn-primary">
                <i class="fas fa-user"></i>
                Hesaplarım
            </a>
            <a href="hesaplar.php" class="btn btn-secondary">
                <i class="fas fa-shopping-cart"></i>
                Yeni Hesap Al
            </a>
        </div>
    </div>
</div>

<script>
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    const text = element.textContent;
    
    navigator.clipboard.writeText(text).then(function() {
        // Başarılı kopyalama animasyonu
        const button = element.nextElementSibling;
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i>';
        button.style.background = 'rgba(40, 167, 69, 0.4)';
        
        setTimeout(function() {
            button.innerHTML = originalHTML;
            button.style.background = 'rgba(40, 167, 69, 0.2)';
        }, 1000);
    }).catch(function(err) {
        console.error('Kopyalama başarısız:', err);
        alert('Kopyalama başarısız oldu. Lütfen manuel olarak kopyalayın.');
    });
}
</script>

<?php 
include 'footer.php'; 
// Output buffering'i temizle ve gönder
ob_end_flush();
?>
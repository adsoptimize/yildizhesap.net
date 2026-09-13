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

$order_id = $_GET['order_id'] ?? '';

// Eğer Cryptomus'tan geldiyse ve order_id varsa, kullanıcı bilgisini order'dan al
if ($isFromCryptomus && $order_id && !$currentUser) {
    $stmt = $pdo->prepare("SELECT user_id FROM crypto_payments WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $payment = $stmt->fetch();
    
    if ($payment) {
        $stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name FROM users WHERE id = ?");
        $stmt->execute([$payment['user_id']]);
        $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// PaymentProcessor'ı başlat
$paymentProcessor = new PaymentProcessor();

// Ödeme durumunu kontrol et ve iptal işlemini gerçekleştir
$paymentStatus = 'unknown';
$orderDetails = null;

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
    
    // Eğer ödeme iptal edildiyse ve henüz işlenmemişse, iptal işlemini gerçekleştir
    if (($paymentStatus === 'failed' || $paymentStatus === 'cancelled') && $orderDetails && $orderDetails['status'] !== 'cancelled') {
        $result = $paymentProcessor->processCancelledPayment($order_id);
        if ($result['success']) {
            // Sipariş durumunu yeniden al
            $orderDetails = $paymentProcessor->getOrderStatus($order_id);
        }
    }
}

include 'header.php';
?>

<style>
.cancel-container {
    max-width: 600px;
    margin: 3rem auto;
    padding: 2rem;
    text-align: center;
}

.cancel-card {
    background: var(--card-bg);
    border-radius: 15px;
    padding: 3rem 2rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.cancel-icon {
    font-size: 4rem;
    color: #dc3545;
    margin-bottom: 1rem;
}

.cancel-title {
    color: var(--text-primary);
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.cancel-message {
    color: var(--text-secondary);
    font-size: 1.1rem;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.order-info {
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.3);
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

@media (max-width: 768px) {
    .cancel-container {
        margin: 1rem auto;
        padding: 1rem;
    }
    
    .cancel-card {
        padding: 2rem 1rem;
    }
    
    .cancel-title {
        font-size: 1.5rem;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn {
        justify-content: center;
    }
}
</style>

<div class="cancel-container">
    <div class="cancel-card">
        <div class="cancel-icon">
            <i class="fas fa-times-circle"></i>
        </div>
        <h1 class="cancel-title">Ödeme İptal Edildi</h1>
        <p class="cancel-message">
            Ödeme işleminiz iptal edildi veya başarısız oldu. 
            Sepetinizdeki ürünler korunmuştur ve istediğiniz zaman tekrar ödeme yapabilirsiniz.
        </p>
        
        <?php if ($order_id && $orderDetails): ?>
            <div class="order-info">
                <strong>Sipariş Numarası:</strong><br>
                <span class="order-id"><?= htmlspecialchars($order_id) ?></span><br><br>
                
                <strong>Ürün:</strong> <?= htmlspecialchars($orderDetails['product_name']) ?><br>
                <strong>Miktar:</strong> <?= $orderDetails['quantity'] ?> adet<br>
                <strong>Toplam Tutar:</strong> <?= formatPrice($orderDetails['total_price']) ?><br>
                <strong>Durum:</strong> 
                <span style="color: #dc3545; font-weight: 600;">
                    <?= $orderDetails['status'] === 'cancelled' ? 'İptal Edildi' : 'Başarısız' ?>
                </span>
            </div>
        <?php elseif ($order_id): ?>
            <div class="order-info">
                <strong>Sipariş Numarası:</strong><br>
                <span class="order-id"><?= htmlspecialchars($order_id) ?></span>
            </div>
        <?php endif; ?>
        
        <div class="action-buttons">
            <a href="checkout.php" class="btn btn-primary">
                <i class="fas fa-credit-card"></i>
                Tekrar Ödeme Yap
            </a>
            <a href="hesaplar.php" class="btn btn-secondary">
                <i class="fas fa-shopping-cart"></i>
                Alışverişe Devam Et
            </a>
        </div>
    </div>
</div>

<?php 
include 'footer.php'; 
// Output buffering'i temizle ve gönder
ob_end_flush();
?>
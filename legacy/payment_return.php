<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'Auth.php';
require_once 'CryptomusPaymentManager.php';

// Kullanıcı giriş kontrolü
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = $auth->getCurrentUser();
$paymentManager = new CryptomusPaymentManager();

$orderId = $_GET['order_id'] ?? '';
$status = $_GET['status'] ?? 'pending';
$message = '';
$paymentInfo = null;

if ($orderId) {
    // Ödeme durumunu kontrol et
    $result = $paymentManager->checkPaymentStatus($orderId);
    
    if ($result['success']) {
        $status = $result['status'];
        $paymentInfo = $result;
        
        switch ($status) {
            case 'paid':
                $message = 'Ödemeniz başarıyla tamamlandı! Hesaplarınız hesabınıza aktarıldı.';
                break;
            case 'pending':
                $message = 'Ödemeniz işleniyor. Lütfen bekleyiniz...';
                break;
            case 'failed':
                $message = 'Ödeme işlemi başarısız oldu. Lütfen tekrar deneyin.';
                break;
            case 'expired':
                $message = 'Ödeme süresi doldu. Lütfen yeni bir ödeme başlatın.';
                break;
            case 'cancelled':
                $message = 'Ödeme işlemi iptal edildi.';
                break;
            default:
                $message = 'Ödeme durumu belirsiz. Destek ekibi ile iletişime geçin.';
        }
    } else {
        $message = 'Ödeme bilgileri alınamadı: ' . $result['error'];
        $status = 'error';
    }
    
    // Sepeti temizle
    if ($status === 'paid') {
        unset($_SESSION['cart']);
    }
} else {
    $message = 'Geçersiz ödeme bilgileri.';
    $status = 'error';
}

$page_title = 'Ödeme Sonucu';
include 'header.php';
?>

<style>
.payment-result-container {
    max-width: 600px;
    margin: 3rem auto;
    padding: 0 1rem;
}

.result-card {
    background: var(--card-bg);
    border-radius: 20px;
    padding: 3rem 2rem;
    text-align: center;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.result-icon {
    font-size: 4rem;
    margin-bottom: 1.5rem;
    display: block;
}

.result-icon.success {
    color: #28a745;
    animation: bounceIn 0.8s ease;
}

.result-icon.pending {
    color: #ffc107;
    animation: pulse 1.5s infinite;
}

.result-icon.error {
    color: #dc3545;
    animation: shake 0.8s ease;
}

.result-title {
    color: var(--text-primary);
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.result-message {
    color: var(--text-secondary);
    font-size: 1.1rem;
    line-height: 1.6;
    margin-bottom: 2rem;
}

.payment-details {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    text-align: left;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.detail-row:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.detail-label {
    color: var(--text-secondary);
}

.detail-value {
    color: var(--text-primary);
    font-weight: 600;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn {
    padding: 1rem 2rem;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--accent));
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(var(--primary-rgb), 0.3);
    color: white;
    text-decoration: none;
}

.btn-outline {
    background: transparent;
    border: 2px solid var(--primary);
    color: var(--primary);
}

.btn-outline:hover {
    background: var(--primary);
    color: white;
    text-decoration: none;
}

.auto-refresh {
    background: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.3);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 2rem;
    color: #ffc107;
    font-size: 0.9rem;
}

@keyframes bounceIn {
    0% { transform: scale(0.3); opacity: 0; }
    50% { transform: scale(1.05); }
    70% { transform: scale(0.9); }
    100% { transform: scale(1); opacity: 1; }
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
    20%, 40%, 60%, 80% { transform: translateX(5px); }
}

@media (max-width: 768px) {
    .payment-result-container {
        margin: 2rem auto;
        padding: 0 0.5rem;
    }
    
    .result-card {
        padding: 2rem 1.5rem;
    }
    
    .result-title {
        font-size: 1.5rem;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .btn {
        width: 100%;
        max-width: 250px;
        justify-content: center;
    }
}
</style>

<div class="payment-result-container">
    <div class="result-card">
        <?php if ($status === 'paid'): ?>
            <i class="fas fa-check-circle result-icon success"></i>
            <h1 class="result-title">Ödeme Başarılı!</h1>
        <?php elseif ($status === 'pending'): ?>
            <i class="fas fa-clock result-icon pending"></i>
            <h1 class="result-title">Ödeme İşleniyor</h1>
            <div class="auto-refresh">
                <i class="fas fa-sync-alt"></i>
                Sayfa otomatik olarak yenilenecek...
            </div>
        <?php elseif ($status === 'failed' || $status === 'expired' || $status === 'cancelled'): ?>
            <i class="fas fa-times-circle result-icon error"></i>
            <h1 class="result-title">Ödeme Başarısız</h1>
        <?php else: ?>
            <i class="fas fa-exclamation-triangle result-icon error"></i>
            <h1 class="result-title">Hata</h1>
        <?php endif; ?>
        
        <p class="result-message"><?= htmlspecialchars($message) ?></p>
        
        <?php if ($paymentInfo && $orderId): ?>
            <div class="payment-details">
                <div class="detail-row">
                    <span class="detail-label">Sipariş ID:</span>
                    <span class="detail-value"><?= htmlspecialchars($orderId) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Durum:</span>
                    <span class="detail-value">
                        <?php
                        $statusLabels = [
                            'paid' => 'Ödendi',
                            'pending' => 'Bekliyor',
                            'failed' => 'Başarısız',
                            'expired' => 'Süresi Doldu',
                            'cancelled' => 'İptal Edildi'
                        ];
                        echo $statusLabels[$status] ?? 'Bilinmeyen';
                        ?>
                    </span>
                </div>
                <?php if ($paymentInfo['txid']): ?>
                    <div class="detail-row">
                        <span class="detail-label">Transaction ID:</span>
                        <span class="detail-value" style="font-family: monospace; font-size: 0.9rem;">
                            <?= htmlspecialchars(substr($paymentInfo['txid'], 0, 20) . '...') ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="action-buttons">
            <?php if ($status === 'paid'): ?>
                <a href="urunlerim.php" class="btn btn-primary">
                    <i class="fas fa-box"></i>
                    Ürünlerimi Görüntüle
                </a>
                <a href="hesaplar.php" class="btn btn-outline">
                    <i class="fas fa-shopping-cart"></i>
                    Alışverişe Devam Et
                </a>
            <?php elseif ($status === 'pending'): ?>
                <button onclick="window.location.reload()" class="btn btn-primary">
                    <i class="fas fa-sync-alt"></i>
                    Durumu Yenile
                </button>
                <a href="urunlerim.php" class="btn btn-outline">
                    <i class="fas fa-history"></i>
                    Sipariş Geçmişi
                </a>
            <?php else: ?>
                <a href="checkout.php" class="btn btn-primary">
                    <i class="fas fa-redo"></i>
                    Tekrar Dene
                </a>
                <a href="hesaplar.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i>
                    Ana Sayfaya Dön
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($status === 'pending'): ?>
<script>
// Pending durumunda sayfa otomatik yenileme
setTimeout(function() {
    window.location.reload();
}, 10000); // 10 saniyede bir yenile
</script>
<?php endif; ?>

<?php include 'footer.php'; ?>

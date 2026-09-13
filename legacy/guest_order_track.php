<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'Auth.php';

// Kullanıcı giriş kontrolü
$auth = new Auth();
$currentUser = null;
$isGuest = false;

// Ziyaretçi sipariş takibi için özel kontrol
if (!$auth->isLoggedIn()) {
    // Ziyaretçi ise order_id'den bilgi almaya çalış
    $order_id = $_GET['order_id'] ?? '';
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
        }
    }
} else {
    $currentUser = $auth->getCurrentUser();
}

// POST işlemi kontrolü
$order_id = '';
$orderDetails = null;
$assignedAccounts = [];
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token kontrolü
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token'] ?? '') {
        $error_message = 'Güvenlik hatası. Lütfen sayfayı yenileyin.';
    } else {
        $order_id = trim($_POST['order_id'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (empty($order_id) || empty($email)) {
            $error_message = 'Sipariş numarası ve e-posta adresi gereklidir.';
        } else {
            // Sipariş ve email doğrulaması
            $stmt = $pdo->prepare("SELECT * FROM crypto_payments WHERE order_id = ? AND email = ?");
            $stmt->execute([$order_id, $email]);
            $payment = $stmt->fetch();
            
            if ($payment) {
                // Ödeme öğelerini al
                $stmt = $pdo->prepare("
                    SELECT pi.*, a.title, a.description 
                    FROM payment_items pi 
                    JOIN accounts a ON pi.account_id = a.id 
                    WHERE pi.payment_id = ?
                ");
                $stmt->execute([$payment['id']]);
                $orderDetails = $stmt->fetchAll();
                
                // Atanan hesapları al (eğer ödeme başarılıysa)
                if ($payment['status'] === 'paid' || $payment['status'] === 'completed') {
                    $stmt = $pdo->prepare("
                        SELECT username, password, email, email_password, account_created_date 
                        FROM order_accounts 
                        WHERE order_id = ? AND is_active = 1
                    ");
                    $stmt->execute([$order_id]);
                    $assignedAccounts = $stmt->fetchAll();
                }
                
                $success_message = 'Sipariş bilgileri başarıyla yüklendi.';
            } else {
                $error_message = 'Sipariş numarası veya e-posta adresi hatalı. Lütfen bilgilerinizi kontrol edin.';
            }
        }
    }
}

// CSRF token oluştur
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include 'header.php';
?>

<style>
.track-container {
    max-width: 800px;
    margin: 3rem auto;
    padding: 2rem;
}

.track-card {
    background: var(--card-bg);
    border-radius: 15px;
    padding: 3rem 2rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.track-title {
    color: var(--text-primary);
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 2rem;
    text-align: center;
}

.search-form {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    color: var(--text-primary);
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.form-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.05);
    color: var(--text-primary);
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(var(--primary-rgb), 0.2);
}

.btn-track {
    background: var(--gradient-primary);
    color: white;
    border: none;
    padding: 0.75rem 2rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
}

.btn-track:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(var(--primary-rgb), 0.3);
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border: 1px solid;
}

.alert-success {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
    border-color: rgba(40, 167, 69, 0.3);
}

.alert-error {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
    border-color: rgba(220, 53, 69, 0.3);
}

.order-status {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.status-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.status-pending {
    background: rgba(255, 193, 7, 0.2);
    color: #ffc107;
    border: 1px solid rgba(255, 193, 7, 0.3);
}

.status-paid {
    background: rgba(40, 167, 69, 0.2);
    color: #28a745;
    border: 1px solid rgba(40, 167, 69, 0.3);
}

.status-failed {
    background: rgba(220, 53, 69, 0.2);
    color: #dc3545;
    border: 1px solid rgba(220, 53, 69, 0.3);
}

.order-details {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: 600;
    color: var(--text-primary);
}

.detail-value {
    color: var(--text-secondary);
    font-family: monospace;
}

.accounts-section {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    padding: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.account-item {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.account-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.account-title {
    font-weight: 600;
    color: var(--text-primary);
}

.account-credentials {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-top: 1rem;
}

.credential-item {
    background: rgba(0, 0, 0, 0.2);
    padding: 0.75rem;
    border-radius: 5px;
}

.credential-label {
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin-bottom: 0.25rem;
}

.credential-value {
    font-family: monospace;
    color: var(--text-primary);
    word-break: break-all;
}

.copy-btn {
    background: var(--primary);
    color: white;
    border: none;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    cursor: pointer;
    margin-left: 0.5rem;
}

.copy-btn:hover {
    background: var(--accent);
}

.security-notice {
    background: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.3);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    color: #ffc107;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .track-container {
        margin: 1rem auto;
        padding: 1rem;
    }
    
    .track-card {
        padding: 2rem 1rem;
    }
    
    .track-title {
        font-size: 1.5rem;
    }
    
    .account-credentials {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="track-container">
    <div class="track-card">
        <h1 class="track-title">Sipariş Takip</h1>
        
        <!-- Güvenlik Uyarısı -->
        <div class="security-notice">
            <i class="fas fa-shield-alt"></i>
            <strong>Güvenlik:</strong> Sipariş bilgilerinizi görüntülemek için sipariş numaranız ve e-posta adresinizi girmeniz gerekmektedir.
        </div>
        
        <!-- Hata/Success Mesajları -->
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>
        
        <!-- Sipariş Arama Formu -->
        <div class="search-form">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                
                <div class="form-group">
                    <label class="form-label">Sipariş Numarası</label>
                    <input type="text" name="order_id" class="form-input" 
                           placeholder="Örnek: guest_order_1754256142_688fd30e21d9f" 
                           value="<?= htmlspecialchars($order_id) ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">E-posta Adresi</label>
                    <input type="email" name="email" class="form-input" 
                           placeholder="Siparişte kullandığınız e-posta adresi" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                
                <button type="submit" class="btn-track">
                    <i class="fas fa-search"></i> Sipariş Ara
                </button>
            </form>
        </div>
        
        <?php if ($order_id && $payment): ?>
            <!-- Sipariş Durumu -->
            <div class="order-status">
                <h3>Sipariş Durumu</h3>
                <div class="status-badge status-<?= $payment['status'] ?>">
                    <?php
                    $statusText = [
                        'pending' => 'Beklemede',
                        'paid' => 'Ödendi',
                        'completed' => 'Tamamlandı',
                        'failed' => 'Başarısız',
                        'cancelled' => 'İptal Edildi'
                    ];
                    echo $statusText[$payment['status']] ?? 'Bilinmiyor';
                    ?>
                </div>
            </div>
            
            <!-- Sipariş Detayları -->
            <div class="order-details">
                <h3>Sipariş Detayları</h3>
                
                <div class="detail-row">
                    <span class="detail-label">Sipariş Numarası:</span>
                    <span class="detail-value"><?= htmlspecialchars($payment['order_id']) ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Müşteri:</span>
                    <span class="detail-value"><?= htmlspecialchars($payment['customer_name'] ?? 'Ziyaretçi') ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">E-posta:</span>
                    <span class="detail-value"><?= htmlspecialchars($payment['email'] ?? '-') ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Telefon:</span>
                    <span class="detail-value"><?= htmlspecialchars($payment['phone'] ?? '-') ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Toplam Tutar:</span>
                    <span class="detail-value"><?= formatPrice($payment['amount']) ?> <?= $payment['currency'] ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Ödeme Yöntemi:</span>
                    <span class="detail-value"><?= ucfirst($payment['payment_method']) ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Sipariş Tarihi:</span>
                    <span class="detail-value"><?= date('d.m.Y H:i', strtotime($payment['created_at'])) ?></span>
                </div>
            </div>
            
            <!-- Sipariş Öğeleri -->
            <?php if ($orderDetails): ?>
                <div class="order-details">
                    <h3>Sipariş Öğeleri</h3>
                    <?php foreach ($orderDetails as $item): ?>
                        <div class="detail-row">
                            <span class="detail-label"><?= htmlspecialchars($item['title']) ?></span>
                            <span class="detail-value"><?= $item['quantity'] ?> adet - <?= formatPrice($item['total_price']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Atanan Hesaplar -->
            <?php if ($assignedAccounts): ?>
                <div class="accounts-section">
                    <h3>Atanan Hesaplar</h3>
                    <?php foreach ($assignedAccounts as $account): ?>
                        <div class="account-item">
                            <div class="account-header">
                                <span class="account-title">Hesap Bilgileri</span>
                                <small>Oluşturulma: <?= date('d.m.Y', strtotime($account['account_created_date'])) ?></small>
                            </div>
                            
                            <div class="account-credentials">
                                <div class="credential-item">
                                    <div class="credential-label">Kullanıcı Adı</div>
                                    <div class="credential-value">
                                        <?= htmlspecialchars($account['username']) ?>
                                        <button class="copy-btn" onclick="copyToClipboard('<?= htmlspecialchars($account['username']) ?>')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="credential-item">
                                    <div class="credential-label">Şifre</div>
                                    <div class="credential-value">
                                        <?= htmlspecialchars($account['password']) ?>
                                        <button class="copy-btn" onclick="copyToClipboard('<?= htmlspecialchars($account['password']) ?>')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <?php if ($account['email']): ?>
                                    <div class="credential-item">
                                        <div class="credential-label">E-posta</div>
                                        <div class="credential-value">
                                            <?= htmlspecialchars($account['email']) ?>
                                            <button class="copy-btn" onclick="copyToClipboard('<?= htmlspecialchars($account['email']) ?>')">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($account['email_password']): ?>
                                    <div class="credential-item">
                                        <div class="credential-label">E-posta Şifresi</div>
                                        <div class="credential-value">
                                            <?= htmlspecialchars($account['email_password']) ?>
                                            <button class="copy-btn" onclick="copyToClipboard('<?= htmlspecialchars($account['email_password']) ?>')">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($payment['status'] === 'paid' || $payment['status'] === 'completed'): ?>
                <div class="order-details">
                    <h3>Hesap Bilgileri</h3>
                    <p>Hesaplarınız henüz hazırlanıyor. Lütfen birkaç dakika bekleyin.</p>
                </div>
            <?php endif; ?>
            
        <?php endif; ?>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Başarılı kopyalama animasyonu
        const btn = event.target.closest('.copy-btn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.style.background = '#28a745';
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.style.background = '';
        }, 1000);
    }).catch(function(err) {
        console.error('Kopyalama başarısız:', err);
        alert('Kopyalama başarısız. Lütfen manuel olarak kopyalayın.');
    });
}
</script>

<?php include 'footer.php'; ?> 
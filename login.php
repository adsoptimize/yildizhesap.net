<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'Auth.php';

$page_title = "Giriş Yap";
$errors = [];
$success = [];

// POST işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailOrUsername = trim($_POST['email_username'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);
    
    if (empty($emailOrUsername)) {
        $errors[] = 'E-posta veya kullanıcı adı gereklidir.';
    }
    
    if (empty($password)) {
        $errors[] = 'Şifre gereklidir.';
    }
    
    if (empty($errors)) {
        $auth = new Auth();
        $result = $auth->login($emailOrUsername, $password, $rememberMe);
        
        if ($result['success']) {
            // Kullanıcı verisinin varlığını kontrol et
            if (!isset($result['user']) || !is_array($result['user'])) {
                $errors[] = 'Giriş verilerinde hata oluştu. Lütfen tekrar deneyin.';
            } else {
                // Kullanıcının aktif olup olmadığını kontrol et
                $isActive = isset($result['user']['is_active']) ? $result['user']['is_active'] : 1; // Varsayılan olarak aktif kabul et
                
                if (!$isActive) {
                require_once 'UserManager.php';
                $userManager = new UserManager($pdo);
                $deactivationInfo = $userManager->getUserDeactivationInfo($result['user']['id']);
                
                // Debug log
                error_log("DEACTIVATION DEBUG: User ID {$result['user']['id']} deactivation info: " . json_encode($deactivationInfo));
                
                if ($deactivationInfo) {
                    $reason = $deactivationInfo['deactivation_reason'] ?? 'Hesabınız pasif edilmiştir.';
                    $until = $deactivationInfo['deactivated_until'] ? date('d.m.Y H:i', strtotime($deactivationInfo['deactivated_until'])) : null;
                    
                    $errorMsg = "Hesabınız devre dışı bırakılmıştır.";
                    
                    if (!empty($reason)) {
                        $errorMsg .= "\n\nSebep: " . $reason;
                    }
                    
                    if ($until) {
                        $errorMsg .= "\n\nDurumunuz şu tarihe kadar geçerlidir: " . $until;
                    } else {
                        $errorMsg .= "\n\nBu durum kalıcıdır.";
                    }
                    
                    $errorMsg .= "\n\nEğer bunun bir hata olduğunu düşünüyorsanız, lütfen destek ekibi ile iletişime geçin.";
                    
                    $errors[] = $errorMsg;
                } else {
                    // Debug: Kullanıcı pasif ama deactivation info yok
                    error_log("DEACTIVATION DEBUG: User ID {$result['user']['id']} is inactive but no deactivation info found");
                    $errors[] = 'Hesabınız devre dışı bırakılmış. Destek ile iletişime geçin.';
                }
            } else {
                // Session cookie'si oluştur
                $cookieOptions = [
                    'expires' => $rememberMe ? time() + (30 * 24 * 60 * 60) : 0,
                    'path' => '/',
                    'domain' => '',
                    'secure' => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Strict'
                ];
                
                setcookie('session_token', $result['session_token'], $cookieOptions);
                
                // Başarılı giriş - yönlendirme
                $redirectTo = $_GET['redirect'] ?? 'index.php';
                header("Location: $redirectTo");
                exit;
            }
            }
        } else {
            $errors = $result['errors'];
        }
    }
}

include 'header.php';
?>

<!-- Page Header -->
<section class="page-header compact">
    <div class="container">
        <h1>Giriş Yap</h1>
        <p>Hesabınıza giriş yaparak premium hesaplarınıza erişin.</p>
    </div>
</section>

<!-- Login Section -->
<section class="auth-section">
    <div class="container">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <div class="auth-icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <h2>Hoş Geldiniz</h2>
                    <p>Hesabınıza giriş yapın</p>
                </div>
                
                <div class="auth-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-triangle"></i>
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <ul>
                                <?php foreach ($success as $message): ?>
                                    <li><?php echo htmlspecialchars($message); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="auth-form">
                        <div class="form-group">
                            <label for="email_username">E-posta veya Kullanıcı Adı</label>
                            <div class="input-group">
                                <i class="fas fa-user"></i>
                                <input 
                                    type="text" 
                                    id="email_username" 
                                    name="email_username" 
                                    value="<?php echo htmlspecialchars($_POST['email_username'] ?? ''); ?>"
                                    placeholder="E-posta adresiniz veya kullanıcı adınız"
                                    required
                                >
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Şifre</label>
                            <div class="input-group">
                                <i class="fas fa-lock"></i>
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    placeholder="Şifreniz"
                                    required
                                >
                                <span class="password-toggle" onclick="togglePassword()">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        
                        <div class="form-options">
                            <label class="checkbox-label">
                                <input type="checkbox" name="remember_me" <?php echo isset($_POST['remember_me']) ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                Beni hatırla (30 gün)
                            </label>
                            
                            <a href="forgot-password.php" class="forgot-link">Şifremi unuttum?</a>
                        </div>
                        
                        <button type="submit" class="btn-auth">
                            <i class="fas fa-sign-in-alt"></i>
                            Giriş Yap
                        </button>
                    </form>
                    
                    <div class="auth-footer">
                        <p>Hesabınız yok mu? <a href="register.php">Hemen kayıt olun</a></p>
                    </div>
                </div>
            </div>
            
            <!-- Login Benefits -->
            <div class="auth-benefits">
                <h3>Giriş Yaparak</h3>
                <div class="benefit-list">
                    <div class="benefit-item">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Hesap satın alabilirsiniz</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-history"></i>
                        <span>Satın alma geçmişinizi görüntüleyebilirsiniz</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-wallet"></i>
                        <span>Bakiye yönetimi yapabilirsiniz</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-headset"></i>
                        <span>Premium desteğe erişebilirsiniz</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.querySelector('.password-toggle i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Form validation
document.querySelector('.auth-form').addEventListener('submit', function(e) {
    const emailUsername = document.getElementById('email_username').value.trim();
    const password = document.getElementById('password').value;
    
    if (!emailUsername || !password) {
        e.preventDefault();
        showAlert('Lütfen tüm alanları doldurun.', 'error');
    }
});

function showAlert(message, type) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `<i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 'check-circle'}"></i> ${message}`;
    
    const form = document.querySelector('.auth-form');
    form.insertBefore(alert, form.firstChild);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}
</script>

<style>
/* Auth Section Styles */
.auth-section {
    padding: 50px 0;
    min-height: 80vh;
    display: flex;
    align-items: center;
}

.auth-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    max-width: 1000px;
    margin: 0 auto;
    align-items: center;
}

.auth-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 24px;
    padding: 40px;
    backdrop-filter: blur(10px);
    box-shadow: var(--inner-shadow);
    position: relative;
    overflow: hidden;
}

.auth-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle, rgba(108, 99, 255, 0.1) 0%, transparent 70%);
    z-index: -1;
}

.auth-header {
    text-align: center;
    margin-bottom: 30px;
}

.auth-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    box-shadow: 0 8px 20px rgba(108, 99, 255, 0.3);
}

.auth-icon i {
    font-size: 2rem;
    color: white;
}

.auth-header h2 {
    font-size: 1.8rem;
    color: var(--light);
    margin-bottom: 8px;
    font-weight: 700;
}

.auth-header p {
    color: var(--gray);
    font-size: 1rem;
}

.auth-form {
    margin-bottom: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    color: var(--light);
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 0.95rem;
}

.input-group {
    position: relative;
    display: flex;
    align-items: center;
}

.input-group i {
    position: absolute;
    left: 16px;
    color: var(--gray);
    font-size: 1rem;
    z-index: 2;
}

.input-group input {
    width: 100%;
    padding: 16px 16px 16px 50px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--card-border);
    border-radius: 12px;
    color: var(--light);
    font-size: 1rem;
    transition: var(--transition);
}

.input-group input:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.1);
}

.password-toggle {
    position: absolute;
    right: 16px;
    color: var(--gray);
    cursor: pointer;
    font-size: 1rem;
    z-index: 2;
    transition: var(--transition);
}

.password-toggle:hover {
    color: var(--accent);
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 15px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    color: var(--gray);
    font-size: 0.9rem;
}

.checkbox-label input {
    display: none;
}

.checkmark {
    width: 18px;
    height: 18px;
    border: 2px solid var(--card-border);
    border-radius: 4px;
    margin-right: 10px;
    position: relative;
    transition: var(--transition);
}

.checkbox-label input:checked + .checkmark {
    background: var(--accent);
    border-color: var(--accent);
}

.checkbox-label input:checked + .checkmark::after {
    content: '';
    position: absolute;
    left: 4px;
    top: 1px;
    width: 6px;
    height: 10px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.forgot-link {
    color: var(--accent);
    text-decoration: none;
    font-size: 0.9rem;
    transition: var(--transition);
}

.forgot-link:hover {
    color: var(--primary);
}

.btn-auth {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    border: none;
    border-radius: 12px;
    color: white;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-auth:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(108, 99, 255, 0.4);
}

.auth-footer {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid var(--card-border);
}

.auth-footer p {
    color: var(--gray);
    margin: 0;
}

.auth-footer a {
    color: var(--accent);
    text-decoration: none;
    font-weight: 600;
    transition: var(--transition);
}

.auth-footer a:hover {
    color: var(--primary);
}

/* Auth Benefits */
.auth-benefits {
    padding: 20px 0;
}

.auth-benefits h3 {
    color: var(--light);
    font-size: 1.5rem;
    margin-bottom: 25px;
    font-weight: 700;
}

.benefit-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.benefit-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 16px;
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid var(--card-border);
    border-radius: 12px;
    transition: var(--transition);
}

.benefit-item:hover {
    border-color: rgba(108, 99, 255, 0.3);
    transform: translateX(5px);
}

.benefit-item i {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
    flex-shrink: 0;
}

.benefit-item span {
    color: var(--gray);
    font-size: 0.95rem;
}

/* Alert Styles */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            white-space: pre-line;
        }

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #fca5a5;
}

.alert-success {
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #86efac;
}

.alert ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

.alert li {
    margin: 0;
}

/* Responsive */
@media (max-width: 968px) {
    .auth-container {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .auth-benefits {
        order: -1;
    }
}

@media (max-width: 768px) {
    .auth-section {
        padding: 30px 0;
    }
    
    .auth-card {
        padding: 30px 20px;
        margin: 0 10px;
    }
    
    .form-options {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<?php include 'footer.php'; ?>

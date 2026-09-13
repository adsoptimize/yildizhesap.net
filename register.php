<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'Auth.php';

$page_title = "Kayıt Ol";
$errors = [];
$success = [];

// POST işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? '',
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? '')
    ];

    $auth = new Auth();
    $result = $auth->register($data);

    if ($result['success']) {
        // Otomatik giriş yapıldıysa anasayfaya yönlendir
        if (isset($result['auto_login']) && $result['auto_login']) {
            header('Location: index.php?welcome=1');
            exit;
        }
        $success[] = $result['message'];
    } else {
        $errors = $result['errors'];
    }
}

include 'header.php';
?>

<!-- Page Header -->
<section class="page-header compact">
    <div class="container">
        <h1>Kayıt Ol</h1>
        <p>Premium hesabınıza erişim için lütfen kayıt olun.</p>
    </div>
</section>

<!-- Registration Section -->
<section class="auth-section">
    <div class="container">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <div class="auth-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h2>Yeni Hesap Oluşturun</h2>
                    <p>Bilgilerinizi girerek kayıt olun</p>
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
                            <label for="username">Kullanıcı Adı</label>
                            <div class="input-group">
                                <i class="fas fa-user"></i>
                                <input 
                                    type="text" 
                                    id="username" 
                                    name="username" 
                                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                    placeholder="Kullanıcı adınız"
                                    required
                                >
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">E-posta</label>
                            <div class="input-group">
                                <i class="fas fa-envelope"></i>
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                    placeholder="E-posta adresiniz"
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
                                <span class="password-toggle" onclick="togglePassword('password')">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password_confirm">Şifre (Tekrar)</label>
                            <div class="input-group">
                                <i class="fas fa-lock"></i>
                                <input 
                                    type="password" 
                                    id="password_confirm" 
                                    name="password_confirm" 
                                    placeholder="Şifrenizi tekrar girin"
                                    required
                                >
                                <span class="password-toggle" onclick="togglePassword('password_confirm')">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="first_name">Ad</label>
                            <div class="input-group">
                                <i class="fas fa-id-badge"></i>
                                <input 
                                    type="text" 
                                    id="first_name" 
                                    name="first_name" 
                                    value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                                    placeholder="Adınız"
                                    required
                                >
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="last_name">Soyad</label>
                            <div class="input-group">
                                <i class="fas fa-id-badge"></i>
                                <input 
                                    type="text" 
                                    id="last_name" 
                                    name="last_name" 
                                    value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                                    placeholder="Soyadınız"
                                    required
                                >
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="phone">Telefon</label>
                            <div class="input-group">
                                <i class="fas fa-phone"></i>
                                <input 
                                    type="text" 
                                    id="phone" 
                                    name="phone" 
                                    value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                    placeholder="Telefon numaranız (isteğe bağlı)"
                                >
                            </div>
                        </div>

                        <button type="submit" class="btn-auth">
                            <i class="fas fa-user-plus"></i>
                            Kayıt Ol
                        </button>
                    </form>

                    <div class="auth-footer">
                        <p>Zaten bir hesabınız var mı? <a href="login.php">Giriş yapın</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function togglePassword(field) {
    const passwordInput = document.getElementById(field);
    const toggleIcon = passwordInput.nextElementSibling.querySelector('i');

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
    max-width: 500px;
    margin: 0 auto;
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

/* Alert Styles */
.alert {
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
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
@media (max-width: 768px) {
    .auth-section {
        padding: 30px 0;
    }

    .auth-card {
        padding: 30px 20px;
        margin: 0 10px;
    }
}
</style>

<?php include 'footer.php'; ?>


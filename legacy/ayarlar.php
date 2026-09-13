<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'Auth.php';

$page_title = "Ayarlar";

// Kullanıcı kontrolü
$auth = new Auth();
$sessionToken = $_COOKIE['session_token'] ?? null;

if (!$sessionToken) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$sessionResult = $auth->validateSession($sessionToken);
if (!$sessionResult['valid']) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$currentUser = $sessionResult['user'];

include 'header.php';
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1><i class="fas fa-cog"></i> Ayarlar</h1>
        <p>Hesap bilgilerinizi ve ayarları buradan düzenleyebilirsiniz.</p>
    </div>
</section>

<!-- Settings Section -->
<section class="settings-section">
    <div class="container">
        <div class="settings-card">
            <h3>Profil Bilgileri</h3>
            <form method="POST" action="update_settings.php">
                <div class="form-group">
                    <label for="first_name">Ad</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($currentUser['first_name'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="last_name">Soyad</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($currentUser['last_name'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">E-posta</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>" required readonly>
                </div>

                <div class="form-group">
                    <label for="phone">Telefon</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Değişiklikleri Kaydet
                </button>
            </form>
        </div>
    </div>
</section>

<style>
.settings-section {
    padding: 40px 0;
    min-height: 60vh;
}

.settings-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 16px;
    padding: 30px;
    max-width: 600px;
    margin: 0 auto;
    box-shadow: var(--inner-shadow);
}

.settings-card h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 0.9rem;
    color: var(--gray);
    margin-bottom: 5px;
}

.form-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--card-border);
    border-radius: 8px;
    font-size: 0.9rem;
    color: var(--light);
    background: rgba(255, 255, 255, 0.05);
}

.btn-primary {
    margin-top: 10px;
    padding: 10px 20px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 600;
    transition: var(--transition);
}

.btn-primary:hover {
    background: rgba(108, 99, 255, 0.9);
}
</style>

<?php include 'footer.php'; ?>


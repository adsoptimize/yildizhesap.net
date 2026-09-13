<?php
// Aktif sayfa tespiti için current page bilgisini al
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
:root {
    --primary: #667eea;
    --secondary: #764ba2;
    --accent: #f093fb;
    --success: #4ade80;
    --warning: #facc15;
    --danger: #ef4444;
    --info: #3b82f6;
    --dark: #1e293b;
    --light: #f8fafc;
    --border: #e2e8f0;
    --shadow: rgba(0, 0, 0, 0.1);
}

/* Sidebar Styles */
.sidebar {
    width: 280px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    box-shadow: 4px 0 20px var(--shadow);
    transition: all 0.3s ease;
    position: relative;
    z-index: 1000;
}

.sidebar-header {
    padding: 2rem;
    border-bottom: 1px solid var(--border);
    text-align: center;
}

.sidebar-logo {
    font-size: 1.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    margin-bottom: 0.5rem;
}

.sidebar-subtitle {
    color: #64748b;
    font-size: 0.875rem;
}

.sidebar-nav {
    padding: 1rem 0;
}

.nav-item {
    margin: 0.25rem 1rem;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1rem;
    color: #64748b;
    text-decoration: none;
    border-radius: 12px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.nav-link:hover, .nav-link.active {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.nav-link i {
    width: 20px;
    text-align: center;
}

/* Responsive Sidebar */
@media (max-width: 1024px) {
    .sidebar {
        width: 240px;
    }
}

@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        position: static;
        height: auto;
    }
}
</style>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">Business Admin</div>
        <div class="sidebar-subtitle">Control Panel</div>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-item">
            <a href="index.php" class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
        </div>
        <div class="nav-item">
            <a href="site-settings.php" class="nav-link <?= $current_page === 'site-settings.php' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i>
                Site Ayarları
            </a>
        </div>
        <div class="nav-item">
            <a href="accounts.php" class="nav-link <?= $current_page === 'accounts.php' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                Hesap Yönetimi
            </a>
        </div>
        <div class="nav-item">
            <a href="categories.php" class="nav-link <?= $current_page === 'categories.php' ? 'active' : '' ?>">
                <i class="fas fa-tags"></i>
                Kategori Yönetimi
            </a>
        </div>
        <div class="nav-item">
            <a href="users.php" class="nav-link <?= $current_page === 'users.php' ? 'active' : '' ?>">
                <i class="fas fa-user-friends"></i>
                Kullanıcı Yönetimi
            </a>
        </div>
        <div class="nav-item">
            <a href="orders.php" class="nav-link <?= $current_page === 'orders.php' ? 'active' : '' ?>">
                <i class="fas fa-shopping-cart"></i>
                Sipariş Yönetimi
            </a>
        </div>
        <div class="nav-item">
            <a href="support.php" class="nav-link <?= $current_page === 'support.php' ? 'active' : '' ?>">
                <i class="fas fa-headset"></i>
                Destek Yönetimi
            </a>
        </div>
        <div class="nav-item">
            <a href="analytics.php" class="nav-link <?= $current_page === 'analytics.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i>
                Analitik & Raporlar
            </a>
        </div>
        <div class="nav-item">
            <a href="crypto-settings.php" class="nav-link <?= $current_page === 'crypto-settings.php' ? 'active' : '' ?>">
                <i class="fab fa-bitcoin"></i>
                Ödeme Ayarları
            </a>
        </div>
        <div class="nav-item">
            <a href="advertisements.php" class="nav-link <?= $current_page === 'advertisements.php' ? 'active' : '' ?>">
                <i class="fas fa-ad"></i>
                Reklam Yönetimi
            </a>
        </div>
        <div class="nav-item">
            <a href="ip-limits.php" class="nav-link <?= $current_page === 'ip-limits.php' ? 'active' : '' ?>">
                <i class="fas fa-network-wired"></i>
                IP Limit Yönetimi
            </a>
        </div>
        <div class="nav-item">
            <a href="legal-pages.php" class="nav-link <?= $current_page === 'legal-pages.php' ? 'active' : '' ?>">
                <i class="fas fa-shield-alt"></i>
                Yasal Sayfalar
            </a>
        </div>
        <div class="nav-item">
            <a href="session_manager.php" class="nav-link <?= $current_page === 'session_manager.php' ? 'active' : '' ?>">
                <i class="fas fa-user-shield"></i>
                Session Yönetimi
            </a>
        </div>
        <div class="nav-item">
            <a href="../index.php" class="nav-link">
                <i class="fas fa-external-link-alt"></i>
                Siteyi Görüntüle
            </a>
        </div>
        <div class="nav-item">
            <a href="../logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i>
                Çıkış Yap
            </a>
        </div>
    </nav>
</div>

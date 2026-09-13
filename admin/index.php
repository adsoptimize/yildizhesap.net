<?php
// IP Ban kontrolü - En başta olmalı
require_once 'check-ip-ban.php';

require_once '../config.php';
require_once '../functions.php';
require_once '../Auth.php';
require_once '../DatabaseSessionManager.php';

// Admin kontrolü - DatabaseSessionManager kullan
$sessionManager = new DatabaseSessionManager($pdo);
$currentUser = null;
$isLoggedIn = false;

$sessionToken = $_COOKIE['session_token'] ?? null;
if ($sessionToken) {
    $sessionResult = $sessionManager->validateSession($sessionToken);
    if ($sessionResult['valid']) {
        $currentUser = $sessionResult['user'];
        $isLoggedIn = true;
    }
}

if (!$isLoggedIn) {
    header('Location: ../login.php?redirect=admin/');
    exit;
}

if (!$currentUser || $currentUser['is_admin'] != 1) {
    die('Bu sayfaya erişim yetkiniz yok. Admin olmadığınız tespit edildi.');
}

$user = $currentUser; // Eski değişken adı ile uyumluluk

// Dashboard istatistikleri
try {
    $stmt = $pdo->query("SELECT * FROM dashboard_stats");
    $stats = $stmt->fetch();
} catch (Exception $e) {
    // View yoksa manuel hesapla
    $stats = [
        'total_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn(),
        'new_users_today' => $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
        'total_accounts' => $pdo->query("SELECT COUNT(*) FROM accounts WHERE status = 'active'")->fetchColumn(),
        'new_accounts_today' => $pdo->query("SELECT COUNT(*) FROM accounts WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
        'total_orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
        'orders_today' => $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
        'total_revenue' => $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders")->fetchColumn(),
        'revenue_today' => $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
        'successful_payments' => $pdo->query("SELECT COUNT(*) FROM crypto_payments WHERE status = 'paid'")->fetchColumn(),
        'pending_payments' => $pdo->query("SELECT COUNT(*) FROM crypto_payments WHERE status = 'pending'")->fetchColumn(),
    ];
}

// Gelir analizi için son 7 günün verileri
$revenueData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dailyRevenue = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE DATE(created_at) = ?");
    $dailyRevenue->execute([$date]);
    $revenueData[] = $dailyRevenue->fetchColumn();
}

// Son aktiviteler - karışık aktivite listesi
$recentActivities = [];

// Son siparişler
$recentOrders = $pdo->query("SELECT 'order' as type, o.id, o.product_name, o.total_price, o.created_at, u.username, u.email FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 3")->fetchAll();

// Son kullanıcı kayıtları
$recentUsers = $pdo->query("SELECT 'user' as type, id, username, email, created_at, first_name, last_name FROM users ORDER BY created_at DESC LIMIT 3")->fetchAll();

// Son eklenen hesaplar
$recentAccounts = $pdo->query("SELECT 'account' as type, id, title, platform, price, created_at FROM accounts ORDER BY created_at DESC LIMIT 2")->fetchAll();

// Son ödemeler
$recentPayments = $pdo->query("SELECT 'payment' as type, id, amount, status, created_at FROM crypto_payments WHERE status IN ('paid', 'pending') ORDER BY created_at DESC LIMIT 2")->fetchAll();

// Tüm aktiviteleri birleştir ve tarihe göre sırala
foreach ($recentOrders as $order) {
    $recentActivities[] = $order;
}
foreach ($recentUsers as $user) {
    $recentActivities[] = $user;
}
foreach ($recentAccounts as $account) {
    $recentActivities[] = $account;
}
foreach ($recentPayments as $payment) {
    $recentActivities[] = $payment;
}

// Tarihe göre sırala
usort($recentActivities, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// İlk 8 aktiviteyi al
$recentActivities = array_slice($recentActivities, 0, 8);

$pageTitle = 'Admin Dashboard';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css" rel="stylesheet">
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
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

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .main-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1.5rem 2rem;
            border-radius: 20px;
            box-shadow: 0 8px 32px var(--shadow);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 8px 32px var(--shadow);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.15);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-title {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.users { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .stat-icon.accounts { background: linear-gradient(135deg, #10b981, #047857); }
        .stat-icon.orders { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-icon.revenue { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .stat-change {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .stat-change.positive {
            color: var(--success);
        }

        .stat-change.negative {
            color: var(--danger);
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 8px 32px var(--shadow);
            overflow: hidden;
        }

        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 400px;
            padding: 1rem;
        }

        /* Activity List */
        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            background: #f8fafc;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .activity-subtitle {
            color: #64748b;
            font-size: 0.875rem;
        }

        .activity-time {
            color: #94a3b8;
            font-size: 0.75rem;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .action-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 16px var(--shadow);
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            text-decoration: none;
            color: inherit;
        }

        .action-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin: 0 auto 1rem;
        }

        .action-icon.settings { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
        .action-icon.accounts { background: linear-gradient(135deg, #10b981, #14b8a6); }
        .action-icon.users { background: linear-gradient(135deg, #f59e0b, #f97316); }
        .action-icon.analytics { background: linear-gradient(135deg, #ef4444, #f97316); }

        .action-title {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .action-subtitle {
            color: #64748b;
            font-size: 0.875rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 240px;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                position: static;
            }
            
            .main-content {
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="main-header">
                <h1 class="page-title">Dashboard</h1>
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                        <div style="color: #64748b; font-size: 0.875rem;">Administrator</div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Toplam Kullanıcılar</div>
                        <div class="stat-icon users">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($stats['total_users']) ?></div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        +<?= $stats['new_users_today'] ?> bugün
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Aktif Hesaplar</div>
                        <div class="stat-icon accounts">
                            <i class="fas fa-user-shield"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($stats['total_accounts']) ?></div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        +<?= $stats['new_accounts_today'] ?> bugün
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Toplam Siparişler</div>
                        <div class="stat-icon orders">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($stats['total_orders']) ?></div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        +<?= $stats['orders_today'] ?> bugün
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Toplam Gelir</div>
                        <div class="stat-icon revenue">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= formatPrice($stats['total_revenue']) ?></div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        +<?= formatPrice($stats['revenue_today']) ?> bugün
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="site-settings.php" class="action-card">
                    <div class="action-icon settings">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="action-title">Site Ayarları</div>
                    <div class="action-subtitle">Logo, menü, footer düzenle</div>
                </a>

                <a href="accounts.php" class="action-card">
                    <div class="action-icon accounts">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="action-title">Yeni Hesap Ekle</div>
                    <div class="action-subtitle">Satılacak hesap ekle</div>
                </a>

                <a href="users.php" class="action-card">
                    <div class="action-icon users">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <div class="action-title">Kullanıcıları Yönet</div>
                    <div class="action-subtitle">Kullanıcı listesi ve düzenle</div>
                </a>

                <a href="analytics.php" class="action-card">
                    <div class="action-icon analytics">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="action-title">Analitikler</div>
                    <div class="action-subtitle">Grafikler ve raporlar</div>
                </a>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Revenue Chart -->
                <div class="content-card">
                    <div class="card-header">
                        <h3 class="card-title">Gelir Analizi</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="content-card">
                    <div class="card-header">
                        <h3 class="card-title">Son Aktiviteler</h3>
                    </div>
                    <div class="card-body">
                        <?php foreach ($recentActivities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-avatar">
                                <?php 
                                switch($activity['type']) {
                                    case 'order':
                                        echo '<i class="fas fa-shopping-cart" style="font-size: 18px;"></i>';
                                        break;
                                    case 'user':
                                        echo strtoupper(substr($activity['username'] ?? 'U', 0, 1));
                                        break;
                                    case 'account':
                                        echo '<i class="fas fa-user-shield" style="font-size: 18px;"></i>';
                                        break;
                                    case 'payment':
                                        echo '<i class="fab fa-bitcoin" style="font-size: 18px;"></i>';
                                        break;
                                }
                                ?>
                            </div>
                            <div class="activity-content">
                                <?php 
                                switch($activity['type']) {
                                    case 'order':
                                        echo '<div class="activity-title">Yeni Sipariş</div>';
                                        echo '<div class="activity-subtitle">' . htmlspecialchars($activity['product_name']) . ' - ' . formatPrice($activity['total_price']) . '</div>';
                                        break;
                                    case 'user':
                                        echo '<div class="activity-title">Yeni Kullanıcı</div>';
                                        echo '<div class="activity-subtitle">' . htmlspecialchars($activity['username']) . ' kayıt oldu</div>';
                                        break;
                                    case 'account':
                                        echo '<div class="activity-title">Yeni Hesap</div>';
                                        echo '<div class="activity-subtitle">' . htmlspecialchars($activity['platform']) . ' - ' . htmlspecialchars($activity['title']) . '</div>';
                                        break;
                                    case 'payment':
                                        $statusText = $activity['status'] == 'paid' ? 'Başarılı' : 'Beklemede';
                                        echo '<div class="activity-title">Ödeme ' . $statusText . '</div>';
                                        echo '<div class="activity-subtitle">' . formatPrice($activity['amount']) . ' - ' . $statusText . '</div>';
                                        break;
                                }
                                ?>
                            </div>
                            <div class="activity-time">
                                <?= date('d.m H:i', strtotime($activity['created_at'])) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($recentActivities)): ?>
                        <div style="text-align: center; color: #64748b; padding: 2rem;">
                            <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <div>Henüz aktivite bulunmuyor</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Revenue Chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        
        // Son 7 günün gerçek gelir verileri
        const revenueData = [<?= implode(',', $revenueData) ?>];
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [
                    '<?= date('d.m', strtotime('-6 days')) ?>',
                    '<?= date('d.m', strtotime('-5 days')) ?>',
                    '<?= date('d.m', strtotime('-4 days')) ?>',
                    '<?= date('d.m', strtotime('-3 days')) ?>',
                    '<?= date('d.m', strtotime('-2 days')) ?>',
                    '<?= date('d.m', strtotime('-1 days')) ?>',
                    'Bugün'
                ],
                datasets: [{
                    label: 'Günlük Gelir (<?= getCurrencySymbol() ?>)',
                    data: revenueData,
                    borderColor: 'rgb(102, 126, 234)',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>

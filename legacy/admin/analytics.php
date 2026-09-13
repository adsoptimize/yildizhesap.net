<?php
// Admin authentication
require_once 'auth_header.php';

// Tarih aralığı belirleme (varsayılan son 30 gün)
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Temel istatistikler
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn(),
    'total_accounts' => $pdo->query("SELECT COUNT(*) FROM accounts WHERE status = 'active'")->fetchColumn(),
    'total_orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'total_revenue' => $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders")->fetchColumn(),
    'successful_payments' => $pdo->query("SELECT COUNT(*) FROM crypto_payments WHERE status = 'paid'")->fetchColumn(),
    'pending_payments' => $pdo->query("SELECT COUNT(*) FROM crypto_payments WHERE status = 'pending'")->fetchColumn(),
];

// Son 30 günün günlük gelir verileri
$dailyRevenueData = [];
$dailyOrderData = [];
$dailyUserData = [];

for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    
    // Günlük gelir
    $dailyRevenue = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE DATE(created_at) = ?");
    $dailyRevenue->execute([$date]);
    $dailyRevenueData[] = $dailyRevenue->fetchColumn();
    
    // Günlük siparişler
    $dailyOrders = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = ?");
    $dailyOrders->execute([$date]);
    $dailyOrderData[] = $dailyOrders->fetchColumn();
    
    // Günlük yeni kullanıcılar
    $dailyUsers = $pdo->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) = ?");
    $dailyUsers->execute([$date]);
    $dailyUserData[] = $dailyUsers->fetchColumn();
}

// Platform bazlı satış verileri - orders tablosundan category/platform bilgisini kullan
$platformSales = $pdo->query("
    SELECT o.category as platform, COUNT(*) as sales_count, SUM(o.total_price) as total_revenue
    FROM orders o
    WHERE o.status IN ('completed', 'processing') AND o.category IS NOT NULL AND o.category != ''
    GROUP BY o.category
    ORDER BY sales_count DESC
")->fetchAll();

// Eğer hiç platform verisi yoksa boş array kullan
if (empty($platformSales)) {
    $platformSales = [];
}

// Top 10 en çok satan hesaplar - product_name bazlı grup
$topAccounts = $pdo->query("
    SELECT o.product_name as title, o.category as platform, o.unit_price as price, 
           COUNT(*) as sales_count, SUM(o.total_price) as total_earned
    FROM orders o
    WHERE o.status IN ('completed', 'processing')
    GROUP BY o.product_name, o.category, o.unit_price
    ORDER BY sales_count DESC
    LIMIT 10
")->fetchAll();

// Aylık karşılaştırma
$currentMonth = date('Y-m');
$previousMonth = date('Y-m', strtotime('-1 month'));

// Array'leri başlat
$currentMonthStats = [];
$previousMonthStats = [];

// Bu ay istatistikleri
$currentMonthRevenue = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
$currentMonthRevenue->execute([$currentMonth]);
$currentMonthStats['revenue'] = $currentMonthRevenue->fetchColumn();

$currentMonthOrders = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
$currentMonthOrders->execute([$currentMonth]);
$currentMonthStats['orders'] = $currentMonthOrders->fetchColumn();

$currentMonthUsers = $pdo->prepare("SELECT COUNT(*) FROM users WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
$currentMonthUsers->execute([$currentMonth]);
$currentMonthStats['users'] = $currentMonthUsers->fetchColumn();

// Geçen ay istatistikleri
$previousMonthRevenue = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
$previousMonthRevenue->execute([$previousMonth]);
$previousMonthStats['revenue'] = $previousMonthRevenue->fetchColumn();

$previousMonthOrders = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
$previousMonthOrders->execute([$previousMonth]);
$previousMonthStats['orders'] = $previousMonthOrders->fetchColumn();

$previousMonthUsers = $pdo->prepare("SELECT COUNT(*) FROM users WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
$previousMonthUsers->execute([$previousMonth]);
$previousMonthStats['users'] = $previousMonthUsers->fetchColumn();

// Yüzdelik değişim hesaplama
function calculatePercentageChange($current, $previous) {
    if ($previous == 0) return $current > 0 ? 100 : 0;
    return round((($current - $previous) / $previous) * 100, 1);
}

$pageTitle = 'Analytics & Reports';
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

        .date-filter {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .date-input {
            padding: 0.5rem 1rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .date-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .filter-btn {
            padding: 0.5rem 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1.5rem;
            border-radius: 16px;
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
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .stat-card:hover {
            transform: translateY(-4px);
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

        .stat-value {
            font-size: 2rem;
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

        .stat-change.neutral {
            color: #64748b;
        }

        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 8px 32px var(--shadow);
            overflow: hidden;
        }

        .chart-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chart-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
        }

        .chart-container {
            position: relative;
            height: 400px;
            padding: 1.5rem;
        }

        .chart-container.small {
            height: 300px;
        }

        /* Tables */
        .table-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 8px 32px var(--shadow);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .table-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border);
        }

        .table-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 1rem 2rem;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
        }

        .data-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #64748b;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table tr:hover {
            background: #f8fafc;
        }

        .platform-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .platform-badge.instagram { background: #fce7f3; color: #be185d; }
        .platform-badge.facebook { background: #dbeafe; color: #1d4ed8; }
        .platform-badge.twitter { background: #dcfce7; color: #166534; }
        .platform-badge.tiktok { background: #fed7d7; color: #991b1b; }
        .platform-badge.youtube { background: #fef3c7; color: #92400e; }
        .platform-badge { background: #f1f5f9; color: #64748b; }

        /* Responsive */
        @media (max-width: 1200px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1024px) {
            .sidebar {
                width: 240px;
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

            .main-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
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
                <h1 class="page-title">Analitik & Raporlar</h1>
                <div class="date-filter">
                    <input type="date" class="date-input" value="<?= $startDate ?>" id="startDate">
                    <span>-</span>
                    <input type="date" class="date-input" value="<?= $endDate ?>" id="endDate">
                    <button class="filter-btn" onclick="updateDateRange()">
                        <i class="fas fa-filter"></i> Filtrele
                    </button>
                </div>
            </div>

            <!-- Monthly Comparison Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Bu Ay Gelir</div>
                    </div>
                    <div class="stat-value"><?= formatPrice($currentMonthStats['revenue']) ?></div>
                    <div class="stat-change <?= $currentMonthStats['revenue'] >= $previousMonthStats['revenue'] ? 'positive' : 'negative' ?>">
                        <i class="fas fa-arrow-<?= $currentMonthStats['revenue'] >= $previousMonthStats['revenue'] ? 'up' : 'down' ?>"></i>
                        <?= abs(calculatePercentageChange($currentMonthStats['revenue'], $previousMonthStats['revenue'])) ?>% geçen aya göre
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Bu Ay Siparişler</div>
                    </div>
                    <div class="stat-value"><?= number_format($currentMonthStats['orders']) ?></div>
                    <div class="stat-change <?= $currentMonthStats['orders'] >= $previousMonthStats['orders'] ? 'positive' : 'negative' ?>">
                        <i class="fas fa-arrow-<?= $currentMonthStats['orders'] >= $previousMonthStats['orders'] ? 'up' : 'down' ?>"></i>
                        <?= abs(calculatePercentageChange($currentMonthStats['orders'], $previousMonthStats['orders'])) ?>% geçen aya göre
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Bu Ay Yeni Kullanıcılar</div>
                    </div>
                    <div class="stat-value"><?= number_format($currentMonthStats['users']) ?></div>
                    <div class="stat-change <?= $currentMonthStats['users'] >= $previousMonthStats['users'] ? 'positive' : 'negative' ?>">
                        <i class="fas fa-arrow-<?= $currentMonthStats['users'] >= $previousMonthStats['users'] ? 'up' : 'down' ?>"></i>
                        <?= abs(calculatePercentageChange($currentMonthStats['users'], $previousMonthStats['users'])) ?>% geçen aya göre
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Ortalama Sipariş Değeri</div>
                    </div>
                    <div class="stat-value"><?= $currentMonthStats['orders'] > 0 ? formatPrice($currentMonthStats['revenue'] / $currentMonthStats['orders']) : formatPrice(0) ?></div>
                    <div class="stat-change neutral">
                        <i class="fas fa-calculator"></i>
                        Bu ay hesaplanan
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="charts-grid">
                <!-- Revenue Trend Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Son 30 Gün Gelir Trendi</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Platform Sales Pie Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Platform Bazlı Satışlar</h3>
                    </div>
                    <div class="chart-container small">
                        <canvas id="platformChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Orders and Users Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Sipariş ve Kullanıcı Trendi</h3>
                </div>
                <div class="chart-container">
                    <canvas id="ordersUsersChart"></canvas>
                </div>
            </div>

            <!-- Top Accounts Table -->
            <div class="table-card">
                <div class="table-header">
                    <h3 class="table-title">En Çok Satan Hesaplar</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Sıra</th>
                            <th>Hesap Adı</th>
                            <th>Platform</th>
                            <th>Birim Fiyat</th>
                            <th>Satış Sayısı</th>
                            <th>Toplam Kazanç</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topAccounts as $index => $account): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($account['title']) ?></td>
                            <td>
                                <span class="platform-badge <?= strtolower($account['platform']) ?>">
                                    <?= htmlspecialchars($account['platform']) ?>
                                </span>
                            </td>
                            <td><?= formatPrice($account['price']) ?></td>
                            <td><?= number_format($account['sales_count']) ?></td>
                            <td><?= formatPrice($account['total_earned']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($topAccounts)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #64748b; padding: 2rem;">
                                Henüz satış verisi bulunmuyor
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Platform Performance Table -->
            <div class="table-card">
                <div class="table-header">
                    <h3 class="table-title">Platform Performansı</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Platform</th>
                            <th>Toplam Satış</th>
                            <th>Toplam Gelir</th>
                            <th>Ortalama Fiyat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($platformSales as $platform): ?>
                        <tr>
                            <td>
                                <span class="platform-badge <?= strtolower($platform['platform']) ?>">
                                    <?= htmlspecialchars($platform['platform']) ?>
                                </span>
                            </td>
                            <td><?= number_format($platform['sales_count']) ?></td>
                            <td><?= formatPrice($platform['total_revenue']) ?></td>
                            <td><?= $platform['sales_count'] > 0 ? formatPrice($platform['total_revenue'] / $platform['sales_count']) : formatPrice(0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($platformSales)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #64748b; padding: 2rem;">
                                Platform verisi bulunmuyor
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Revenue Trend Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueData = [<?= implode(',', $dailyRevenueData) ?>];
        
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: [
                    <?php
                    for ($i = 29; $i >= 0; $i--) {
                        echo "'" . date('d.m', strtotime("-$i days")) . "',";
                    }
                    ?>
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

        // Platform Sales Pie Chart
        const platformCtx = document.getElementById('platformChart').getContext('2d');
        <?php if (!empty($platformSales)): ?>
        const platformChart = new Chart(platformCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php foreach ($platformSales as $p) echo "'" . htmlspecialchars($p['platform']) . "',"?>],
                datasets: [{
                    data: [<?php foreach ($platformSales as $p) echo $p['sales_count'] . ","?>],
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#4ade80',
                        '#facc15',
                        '#ef4444'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        <?php else: ?>
        // Platform verisi yok, boş mesaj göster
        platformCtx.font = '16px Inter';
        platformCtx.textAlign = 'center';
        platformCtx.fillStyle = '#64748b';
        platformCtx.fillText('Henüz platform verisi yok', platformCtx.canvas.width/2, platformCtx.canvas.height/2);
        <?php endif; ?>

        // Orders and Users Trend Chart
        const ordersUsersCtx = document.getElementById('ordersUsersChart').getContext('2d');
        const orderData = [<?= implode(',', $dailyOrderData) ?>];
        const userData = [<?= implode(',', $dailyUserData) ?>];
        
        const ordersUsersChart = new Chart(ordersUsersCtx, {
            type: 'bar',
            data: {
                labels: [
                    <?php
                    for ($i = 29; $i >= 0; $i--) {
                        echo "'" . date('d.m', strtotime("-$i days")) . "',";
                    }
                    ?>
                ],
                datasets: [{
                    label: 'Günlük Siparişler',
                    data: orderData,
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    yAxisID: 'y'
                }, {
                    label: 'Yeni Kullanıcılar',
                    data: userData,
                    backgroundColor: 'rgba(240, 147, 251, 0.8)',
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });

        // Date Range Filter
        function updateDateRange() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            if (startDate && endDate) {
                window.location.href = `analytics.php?start_date=${startDate}&end_date=${endDate}`;
            }
        }
    </script>
</body>
</html>

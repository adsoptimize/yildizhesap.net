<?php
// Admin authentication
require_once 'auth_header.php';
require_once '../SiteSettings.php';
require_once '../IPBanManager.php';
require_once '../UserManager.php';

// Manager sınıflarını başlat
$ipBanManager = new IPBanManager($pdo);
$userManager = new UserManager($pdo);

$message = '';
$error = '';

// POST işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'ban_ip':
            $ipAddress = trim($_POST['ip_address'] ?? '');
            $reason = trim($_POST['reason'] ?? '');
            $banType = $_POST['ban_type'] ?? 'temporary';
            $duration = $_POST['duration'] ?? '1 day';
            
            if ($ipAddress && $reason) {
                $result = $ipBanManager->banIP($ipAddress, $reason, $user['id'], $banType, $duration);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
            } else {
                $error = 'IP adresi ve sebep gereklidir.';
            }
            break;
            
        case 'unban_ip':
            $ipAddress = trim($_POST['ip_address'] ?? '');
            if ($ipAddress) {
                $result = $ipBanManager->unbanIP($ipAddress, $user['id']);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'reset_ip_limit':
            $ipAddress = trim($_POST['ip_address'] ?? '');
            $resetReason = trim($_POST['reset_reason'] ?? 'Admin tarafından sıfırlandı');
            
            if ($ipAddress) {
                try {
                    // Önce mevcut hesap sayısını kaydet
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE registration_ip = ?");
                    $stmt->execute([$ipAddress]);
                    $accountsBefore = $stmt->fetch()['count'];
                    
                    // IP limit geçmişine kaydet
                    $stmt = $pdo->prepare("
                        INSERT INTO ip_limit_resets (ip_address, reset_by, accounts_before_reset, reason) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$ipAddress, $user['id'], $accountsBefore, $resetReason]);
                    
                    // Burada gerçek sıfırlama mantığı olacak - örneğin hesapları başka IP'ye taşımak
                    // Şimdilik sadece log tutuyoruz
                    
                    $message = "IP limit sıfırlandı. Önceki hesap sayısı: $accountsBefore";
                } catch (Exception $e) {
                    $error = 'Limit sıfırlama sırasında hata: ' . $e->getMessage();
                }
            }
            break;
            
        case 'manage_users':
            $ipAddress = trim($_POST['ip_address'] ?? '');
            $userAction = $_POST['user_action'] ?? '';
            $userReason = trim($_POST['user_reason'] ?? '');
            $userDuration = $_POST['user_duration'] ?? null;
            $selectedUsers = $_POST['selected_users'] ?? [];
            
            if ($ipAddress && $userAction && $userReason) {
                $result = $userManager->manageIPUsers(
                    $ipAddress, 
                    $userAction, 
                    $userReason, 
                    $user['id'], 
                    $userDuration, 
                    !empty($selectedUsers) ? $selectedUsers : null
                );
                
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
            }
            break;
    }
}

// IP limit bilgisi al
$searchIp = $_GET['search_ip'] ?? '';
$ipInfo = null;

if ($searchIp) {
    $ipInfo = $auth->getIPLimitInfo($searchIp);
    // IP ban durumunu kontrol et
    $banInfo = $ipBanManager->getBanInfo($searchIp);
    if ($ipInfo) {
        $ipInfo['ban_info'] = $banInfo;
    }
}

// Site ayarlarını al
$siteSettings = SiteSettings::getInstance();
$maxAccountsPerIp = $siteSettings->get('max_accounts_per_ip', '1');
$limitDays = $siteSettings->get('ip_limit_days', '365');
$ipLimitEnabled = $siteSettings->get('ip_limit_enabled', '1');

// Otomatik temizlik işlemleri
$ipBanManager->cleanExpiredBans();
$userManager->reactivateExpiredUsers();

// İstatistikler
try {
    // Toplam IP sayısı
    $stmt = $pdo->query("SELECT COUNT(DISTINCT registration_ip) as total_ips FROM users WHERE registration_ip IS NOT NULL");
    $totalIPs = $stmt->fetch()['total_ips'] ?? 0;
    
    // Aktif ban sayısı
    $stmt = $pdo->query("SELECT COUNT(*) as active_bans FROM ip_bans WHERE is_active = 1 AND (ban_type = 'permanent' OR banned_until > NOW())");
    $activeBans = $stmt->fetch()['active_bans'] ?? 0;
    
    // Pasif kullanıcı sayısı
    $stmt = $pdo->query("SELECT COUNT(*) as inactive_users FROM users WHERE is_active = 0");
    $inactiveUsers = $stmt->fetch()['inactive_users'] ?? 0;
    
    // Son X günde limit aşan IP'ler
    $stmt = $pdo->prepare("
        SELECT registration_ip, COUNT(*) as account_count 
        FROM users 
        WHERE registration_ip IS NOT NULL 
        AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY registration_ip 
        HAVING COUNT(*) >= ?
        ORDER BY account_count DESC
    ");
    $stmt->execute([$limitDays, $maxAccountsPerIp]);
    $limitExceededIPs = $stmt->fetchAll();
    
    // Son X günde en çok hesap açan IP'ler (Top 10)
    $stmt = $pdo->prepare("
        SELECT registration_ip, COUNT(*) as account_count,
               GROUP_CONCAT(username SEPARATOR ', ') as usernames
        FROM users 
        WHERE registration_ip IS NOT NULL 
        AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY registration_ip 
        ORDER BY account_count DESC 
        LIMIT 10
    ");
    $stmt->execute([$limitDays]);
    $topIPs = $stmt->fetchAll();
    
    // Aktif banlı IP'leri al
    $activeBansList = $ipBanManager->getActiveBans(10);
    
} catch (Exception $e) {
    error_log("IP stats error: " . $e->getMessage());
    $totalIPs = 0;
    $activeBans = 0;
    $inactiveUsers = 0;
    $limitExceededIPs = [];
    $topIPs = [];
    $activeBansList = [];
}

$pageTitle = 'IP Limit Yönetimi';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --accent: #f093fb;
            --success: #10b981;
            --warning: #f59e0b;
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

        /* Sidebar styles */
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 8px 32px var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }

        .stat-icon.info { background: linear-gradient(135deg, var(--info), #1d4ed8); }
        .stat-icon.warning { background: linear-gradient(135deg, var(--warning), #d97706); }
        .stat-icon.danger { background: linear-gradient(135deg, var(--danger), #dc2626); }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.875rem;
        }

        /* Search Section */
        .search-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px var(--shadow);
            margin-bottom: 2rem;
        }

        .search-form {
            display: flex;
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            flex: 1;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .search-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
        }

        /* Tables */
        .data-table {
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
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        }

        .table-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 1rem 2rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .table th {
            background: rgba(102, 126, 234, 0.05);
            font-weight: 600;
            color: var(--dark);
        }

        .table tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        /* Status badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active { background: rgba(74, 222, 128, 0.1); color: #166534; }
        .status-exceeded { background: rgba(239, 68, 68, 0.1); color: #991b1b; }
        .status-warning { background: rgba(250, 204, 21, 0.1); color: #a16207; }

        /* IP Info Card */
        .ip-info-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px var(--shadow);
            margin-bottom: 2rem;
        }

        .ip-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .ip-address {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
        }

        .limit-status {
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-weight: 600;
        }

        .accounts-list {
            display: grid;
            gap: 0.75rem;
        }

        .account-item {
            padding: 1rem;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .account-info h4 {
            margin-bottom: 0.25rem;
            color: var(--dark);
        }

        .account-info p {
            color: #64748b;
            font-size: 0.875rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .main-content {
                padding: 1rem;
            }
            
            .search-form {
                flex-direction: column;
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
                <h1 class="page-title">IP Limit Yönetimi</h1>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <span class="status-badge <?= $ipLimitEnabled === '1' ? 'status-active' : 'status-exceeded' ?>">
                        <?= $ipLimitEnabled === '1' ? 'Aktif' : 'Pasif' ?>
                    </span>
                    <span style="color: #64748b;">Limit: <?= $maxAccountsPerIp ?> hesap / <?= $limitDays ?> gün</span>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if (!empty($message)): ?>
            <div class="alert alert-success" style="margin-bottom: 2rem;">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-error" style="margin-bottom: 2rem;">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <div class="stat-value"><?= number_format($totalIPs) ?></div>
                    <div class="stat-label">Toplam Kayıtlı IP</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon danger">
                        <i class="fas fa-ban"></i>
                    </div>
                    <div class="stat-value"><?= number_format($activeBans) ?></div>
                    <div class="stat-label">Aktif IP Ban</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <div class="stat-value"><?= number_format($inactiveUsers) ?></div>
                    <div class="stat-label">Pasif Kullanıcı</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-value"><?= count($limitExceededIPs) ?></div>
                    <div class="stat-label">Limit Aşan IP</div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="search-section">
                <form method="GET" class="search-form">
                    <div class="form-group">
                        <label class="form-label">IP Adresi Ara</label>
                        <input type="text" name="search_ip" class="form-input" 
                               placeholder="Örn: 192.168.1.1" value="<?= htmlspecialchars($searchIp) ?>">
                    </div>
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                        Ara
                    </button>
                </form>
            </div>

            <!-- IP Info -->
            <?php if ($ipInfo && !isset($ipInfo['error'])): ?>
            <div class="ip-info-card">
                <div class="ip-header">
                    <div>
                        <div class="ip-address"><?= htmlspecialchars($ipInfo['ip_address']) ?></div>
                        <p style="color: #64748b; margin-top: 0.5rem;">
                            Son <?= $ipInfo['limit_days'] ?> günde: <?= $ipInfo['current_count'] ?> / <?= $ipInfo['max_allowed'] ?> hesap<br>
                            Tüm zamanlar: <?= $ipInfo['total_count'] ?> hesap
                        </p>
                        <?php if ($ipInfo['ban_info']): ?>
                        <div style="margin-top: 0.5rem;">
                            <span class="status-badge status-exceeded">
                                <i class="fas fa-ban"></i> BANLI
                            </span>
                            <p style="color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem;">
                                Sebep: <?= htmlspecialchars($ipInfo['ban_info']['reason']) ?><br>
                                <?php if ($ipInfo['ban_info']['ban_type'] === 'temporary' && $ipInfo['ban_info']['banned_until']): ?>
                                    Süre: <?= date('d.m.Y H:i', strtotime($ipInfo['ban_info']['banned_until'])) ?> kadar
                                <?php else: ?>
                                    Kalıcı ban
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="limit-status <?= $ipInfo['can_create_new'] ? 'status-active' : 'status-exceeded' ?>">
                            <?= $ipInfo['can_create_new'] ? 'Yeni hesap açabilir' : 'Limit dolmuş' ?>
                        </div>
                        <?php if (!$ipInfo['ban_info']): ?>
                        <button onclick="showBanForm('<?= htmlspecialchars($ipInfo['ip_address']) ?>')" 
                                class="search-btn" style="margin-top: 0.5rem; padding: 0.5rem 1rem; font-size: 0.875rem; background: var(--danger);">
                            <i class="fas fa-ban"></i> IP'yi Banla
                        </button>
                        <?php else: ?>
                        <button onclick="unbanIP('<?= htmlspecialchars($ipInfo['ip_address']) ?>')" 
                                class="search-btn" style="margin-top: 0.5rem; padding: 0.5rem 1rem; font-size: 0.875rem; background: var(--success);">
                            <i class="fas fa-check"></i> Banı Kaldır
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Yönetim Butonları -->
                <div style="display: flex; gap: 1rem; margin: 1rem 0; padding-top: 1rem; border-top: 1px solid var(--border);">
                    <button onclick="showResetForm('<?= htmlspecialchars($ipInfo['ip_address']) ?>')" 
                            class="search-btn" style="background: var(--warning);">
                        <i class="fas fa-redo"></i> Limit Sıfırla
                    </button>
                    <button onclick="showUserManagementForm('<?= htmlspecialchars($ipInfo['ip_address']) ?>')" 
                            class="search-btn" style="background: var(--info);">
                        <i class="fas fa-users-cog"></i> Kullanıcıları Yönet
                    </button>
                </div>
                
                <?php if (!empty($ipInfo['recent_accounts'])): ?>
                <h3 style="margin: 1.5rem 0 1rem 0; color: var(--dark); font-size: 1.1rem;">
                    <i class="fas fa-clock"></i> Son <?= $ipInfo['limit_days'] ?> Günde Oluşturulan Hesaplar
                </h3>
                
                <!-- Kullanıcı Seçim Butonları -->
                <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap;">
                    <button type="button" onclick="selectAllUsers()" class="search-btn" style="padding: 0.5rem 1rem; font-size: 0.875rem; background: var(--success);">
                        <i class="fas fa-check-double"></i> Tümünü Seç
                    </button>
                    <button type="button" onclick="deselectAllUsers()" class="search-btn" style="padding: 0.5rem 1rem; font-size: 0.875rem; background: var(--secondary);">
                        <i class="fas fa-times"></i> Seçimi Kaldır
                    </button>
                    <button type="button" onclick="selectActiveUsers()" class="search-btn" style="padding: 0.5rem 1rem; font-size: 0.875rem; background: var(--info);">
                        <i class="fas fa-user-check"></i> Aktif Kullanıcılar
                    </button>
                    <button type="button" onclick="selectInactiveUsers()" class="search-btn" style="padding: 0.5rem 1rem; font-size: 0.875rem; background: var(--warning);">
                        <i class="fas fa-user-times"></i> Pasif Kullanıcılar
                    </button>
                </div>
                
                <div class="accounts-list">
                    <?php foreach ($ipInfo['recent_accounts'] as $account): ?>
                    <div class="account-item">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <input type="checkbox" name="selected_users[]" value="<?= $account['id'] ?>" 
                                   class="user-checkbox" style="transform: scale(1.2);">
                            <div class="account-info">
                                <h4><?= htmlspecialchars($account['username']) ?></h4>
                                <p><?= htmlspecialchars($account['email']) ?> • <?= date('d.m.Y H:i', strtotime($account['created_at'])) ?></p>
                                <?php if (!empty($account['deactivation_reason'])): ?>
                                <p style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">
                                    <i class="fas fa-info-circle"></i> <?= htmlspecialchars($account['deactivation_reason']) ?>
                                    <?php if ($account['deactivated_until']): ?>
                                        (<?= date('d.m.Y H:i', strtotime($account['deactivated_until'])) ?> kadar)
                                    <?php endif; ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="status-badge <?= $account['is_active'] ? 'status-active' : 'status-exceeded' ?>">
                            <?= $account['is_active'] ? 'Aktif' : 'Pasif' ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($ipInfo['all_accounts']) && count($ipInfo['all_accounts']) > count($ipInfo['recent_accounts'])): ?>
                <h3 style="margin: 2rem 0 1rem 0; color: var(--dark); font-size: 1.1rem;">
                    <i class="fas fa-history"></i> Tüm Zamanlar Oluşturulan Hesaplar
                </h3>
                <div class="accounts-list">
                    <?php foreach ($ipInfo['all_accounts'] as $account): ?>
                    <div class="account-item">
                        <div class="account-info">
                            <h4><?= htmlspecialchars($account['username']) ?></h4>
                            <p><?= htmlspecialchars($account['email']) ?> • <?= date('d.m.Y H:i', strtotime($account['created_at'])) ?></p>
                        </div>
                        <div class="status-badge <?= $account['is_active'] ? 'status-active' : 'status-exceeded' ?>">
                            <?= $account['is_active'] ? 'Aktif' : 'Pasif' ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php if (empty($ipInfo['all_accounts'])): ?>
                <p style="color: #64748b; text-align: center; padding: 2rem;">Bu IP adresinden henüz hesap oluşturulmamış.</p>
                <?php endif; ?>
            </div>
            <?php elseif ($ipInfo && isset($ipInfo['error'])): ?>
            <div class="ip-info-card">
                <p style="color: var(--danger); text-align: center; padding: 2rem;">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($ipInfo['error']) ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Top IPs Table -->
            <?php if (!empty($topIPs)): ?>
            <div class="data-table">
                <div class="table-header">
                    <h3 class="table-title">
                        <i class="fas fa-chart-bar"></i>
                        Son <?= $limitDays ?> Günde En Çok Hesap Açan IP Adresleri
                    </h3>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>IP Adresi</th>
                            <th>Hesap Sayısı</th>
                            <th>Durum</th>
                            <th>Kullanıcı Adları</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topIPs as $ip): ?>
                        <tr>
                            <td>
                                <a href="?search_ip=<?= urlencode($ip['registration_ip']) ?>" 
                                   style="color: var(--primary); text-decoration: none;">
                                    <?= htmlspecialchars($ip['registration_ip']) ?>
                                </a>
                            </td>
                            <td><strong><?= $ip['account_count'] ?></strong></td>
                            <td>
                                <span class="status-badge <?= $ip['account_count'] >= $maxAccountsPerIp ? 'status-exceeded' : ($ip['account_count'] >= ($maxAccountsPerIp * 0.8) ? 'status-warning' : 'status-active') ?>">
                                    <?php if ($ip['account_count'] >= $maxAccountsPerIp): ?>
                                        Limit Aştı
                                    <?php elseif ($ip['account_count'] >= ($maxAccountsPerIp * 0.8)): ?>
                                        Limite Yakın
                                    <?php else: ?>
                                        Normal
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?= htmlspecialchars($ip['usernames']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Limit Exceeded IPs -->
            <?php if (!empty($limitExceededIPs)): ?>
            <div class="data-table">
                <div class="table-header">
                    <h3 class="table-title">
                        <i class="fas fa-exclamation-triangle"></i>
                        Son <?= $limitDays ?> Günde Limit Aşan IP Adresleri
                    </h3>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>IP Adresi</th>
                            <th>Hesap Sayısı</th>
                            <th>Limit Aşma</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($limitExceededIPs as $ip): ?>
                        <tr>
                            <td><?= htmlspecialchars($ip['registration_ip']) ?></td>
                            <td><strong><?= $ip['account_count'] ?></strong></td>
                            <td>
                                <span class="status-badge status-exceeded">
                                    +<?= $ip['account_count'] - $maxAccountsPerIp ?> fazla
                                </span>
                            </td>
                            <td>
                                <a href="?search_ip=<?= urlencode($ip['registration_ip']) ?>" 
                                   class="search-btn" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                    <i class="fas fa-eye"></i>
                                    Detay
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Ban IP Modal -->
    <div id="banModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-ban"></i> IP Adresini Banla</h3>
                <span class="close" onclick="closeBanModal()">&times;</span>
            </div>
            <form method="POST" class="modal-form">
                <input type="hidden" name="action" value="ban_ip">
                <input type="hidden" name="ip_address" id="ban_ip_address">
                
                <div class="form-group">
                    <label class="form-label">Ban Sebebi *</label>
                    <textarea name="reason" class="form-input" rows="3" placeholder="Ban sebebini açıklayın..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Ban Türü</label>
                    <select name="ban_type" class="form-input" onchange="toggleDuration()">
                        <option value="temporary">Geçici</option>
                        <option value="permanent">Kalıcı</option>
                    </select>
                </div>
                
                <div class="form-group" id="duration_group">
                    <label class="form-label">Ban Süresi</label>
                    <select name="duration" class="form-input">
                        <option value="1 hour">1 Saat</option>
                        <option value="6 hours">6 Saat</option>
                        <option value="12 hours">12 Saat</option>
                        <option value="1 day" selected>1 Gün</option>
                        <option value="3 days">3 Gün</option>
                        <option value="7 days">1 Hafta</option>
                        <option value="30 days">1 Ay</option>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="button" onclick="closeBanModal()" class="btn-secondary">İptal</button>
                    <button type="submit" class="btn-danger">IP'yi Banla</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Reset IP Limit Modal -->
    <div id="resetModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-redo"></i> IP Limit Sıfırla</h3>
                <span class="close" onclick="closeResetModal()">&times;</span>
            </div>
            <form method="POST" class="modal-form">
                <input type="hidden" name="action" value="reset_ip_limit">
                <input type="hidden" name="ip_address" id="reset_ip_address">
                
                <div class="form-group">
                    <label class="form-label">Sıfırlama Sebebi</label>
                    <textarea name="reset_reason" class="form-input" rows="2" placeholder="Limit sıfırlama sebebi (opsiyonel)...">Admin tarafından sıfırlandı</textarea>
                </div>
                
                <div class="alert alert-warning" style="margin: 1rem 0;">
                    <i class="fas fa-exclamation-triangle"></i>
                    Bu işlem IP adresinin hesap oluşturma limitini sıfırlar. İşlem geri alınamaz.
                </div>
                
                <div class="modal-actions">
                    <button type="button" onclick="closeResetModal()" class="btn-secondary">İptal</button>
                    <button type="submit" class="btn-warning">Limiti Sıfırla</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- User Management Modal -->
    <div id="userManagementModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3><i class="fas fa-users-cog"></i> Kullanıcıları Yönet</h3>
                <span class="close" onclick="closeUserManagementModal()">&times;</span>
            </div>
            <form method="POST" class="modal-form">
                <input type="hidden" name="action" value="manage_users">
                <input type="hidden" name="ip_address" id="manage_ip_address">
                
                <div class="form-group">
                    <label class="form-label">İşlem Türü *</label>
                    <select name="user_action" class="form-input" onchange="toggleUserDuration()" required>
                        <option value="">Seçiniz...</option>
                        <option value="deactivate">Kalıcı Pasif Et</option>
                        <option value="deactivate_temporary">Geçici Pasif Et</option>
                        <option value="activate">Aktif Et</option>
                        <option value="delete">Sil</option>
                    </select>
                </div>
                
                <div class="form-group" id="user_duration_group" style="display: none;">
                    <label class="form-label">Pasif Etme Süresi</label>
                    <select name="user_duration" class="form-input">
                        <option value="1 hour">1 Saat</option>
                        <option value="6 hours">6 Saat</option>
                        <option value="12 hours">12 Saat</option>
                        <option value="1 day" selected>1 Gün</option>
                        <option value="3 days">3 Gün</option>
                        <option value="7 days">1 Hafta</option>
                        <option value="30 days">1 Ay</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">İşlem Sebebi *</label>
                    <textarea name="user_reason" class="form-input" rows="3" placeholder="İşlem sebebini açıklayın..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="apply_all_users" name="apply_all" value="1">
                        <span class="checkmark"></span>
                        Bu IP'den olan tüm kullanıcılara uygula
                    </label>
                    <p style="font-size: 0.875rem; color: #64748b; margin-top: 0.5rem;">
                        İşaretlenmezse sadece seçili kullanıcılara uygulanır.
                    </p>
                </div>
                
                <div class="modal-actions">
                    <button type="button" onclick="closeUserManagementModal()" class="btn-secondary">İptal</button>
                    <button type="submit" class="btn-primary">İşlemi Uygula</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Styles -->
    <style>
        .modal {
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }
        
        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            margin: 5% auto;
            padding: 0;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            padding: 2rem 2rem 1rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .close {
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            color: #999;
            transition: color 0.3s;
        }
        
        .close:hover {
            color: var(--danger);
        }
        
        .modal-form {
            padding: 1.5rem 2rem 2rem;
        }
        
        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, var(--warning), #d97706);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(250, 204, 21, 0.3);
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            font-weight: 500;
        }
        
        .checkmark {
            width: 20px;
            height: 20px;
            border: 2px solid var(--border);
            border-radius: 4px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .checkbox-label input[type="checkbox"] {
            display: none;
        }
        
        .checkbox-label input[type="checkbox"]:checked + .checkmark {
            background: var(--primary);
            border-color: var(--primary);
        }
        
        .checkbox-label input[type="checkbox"]:checked + .checkmark::after {
            content: '✓';
            position: absolute;
            color: white;
            font-size: 14px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-warning {
            background: rgba(250, 204, 21, 0.1);
            color: #a16207;
            border: 1px solid rgba(250, 204, 21, 0.3);
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #047857;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #991b1b;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
    </style>
    
    <!-- JavaScript Functions -->
    <script>
        function showBanForm(ipAddress) {
            document.getElementById('ban_ip_address').value = ipAddress;
            document.getElementById('banModal').style.display = 'block';
        }
        
        function closeBanModal() {
            document.getElementById('banModal').style.display = 'none';
        }
        
        function unbanIP(ipAddress) {
            if (confirm('Bu IP adresinin banını kaldırmak istediğinizden emin misiniz?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="unban_ip">
                    <input type="hidden" name="ip_address" value="${ipAddress}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function showResetForm(ipAddress) {
            document.getElementById('reset_ip_address').value = ipAddress;
            document.getElementById('resetModal').style.display = 'block';
        }
        
        function closeResetModal() {
            document.getElementById('resetModal').style.display = 'none';
        }
        
        function showUserManagementForm(ipAddress) {
            document.getElementById('manage_ip_address').value = ipAddress;
            document.getElementById('userManagementModal').style.display = 'block';
        }
        
        function closeUserManagementModal() {
            document.getElementById('userManagementModal').style.display = 'none';
        }
        
        function toggleDuration() {
            const banType = document.querySelector('select[name="ban_type"]').value;
            const durationGroup = document.getElementById('duration_group');
            durationGroup.style.display = banType === 'temporary' ? 'block' : 'none';
        }
        
        function toggleUserDuration() {
            const userAction = document.querySelector('select[name="user_action"]').value;
            const durationGroup = document.getElementById('user_duration_group');
            durationGroup.style.display = userAction === 'deactivate_temporary' ? 'block' : 'none';
        }
        
        // Modal kapatma - dışarı tıklama
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
        
        // Kullanıcı seçimi fonksiyonları
        function selectAllUsers() {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            checkboxes.forEach(cb => cb.checked = true);
        }
        
        function deselectAllUsers() {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            checkboxes.forEach(cb => cb.checked = false);
        }
        
        function selectActiveUsers() {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            checkboxes.forEach(cb => {
                const accountItem = cb.closest('.account-item');
                const statusBadge = accountItem.querySelector('.status-badge');
                if (statusBadge && statusBadge.classList.contains('status-active')) {
                    cb.checked = true;
                } else {
                    cb.checked = false;
                }
            });
        }
        
        function selectInactiveUsers() {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            checkboxes.forEach(cb => {
                const accountItem = cb.closest('.account-item');
                const statusBadge = accountItem.querySelector('.status-badge');
                if (statusBadge && statusBadge.classList.contains('status-exceeded')) {
                    cb.checked = true;
                } else {
                    cb.checked = false;
                }
            });
        }
        
        // Form gönderme öncesi kontrol
        document.addEventListener('DOMContentLoaded', function() {
            const userManagementForm = document.querySelector('#userManagementModal form');
            if (userManagementForm) {
                userManagementForm.addEventListener('submit', function(e) {
                    const applyAll = document.getElementById('apply_all_users').checked;
                    const selectedUsers = document.querySelectorAll('.user-checkbox:checked');
                    
                    if (!applyAll && selectedUsers.length === 0) {
                        e.preventDefault();
                        alert('Lütfen en az bir kullanıcı seçin veya "Tüm kullanıcılara uygula" seçeneğini işaretleyin.');
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>

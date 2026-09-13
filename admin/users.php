<?php
// Admin authentication
require_once 'auth_header.php';
require_once '../UserManager.php';

// CSRF Token oluştur
$csrfToken = generateCSRFToken();

// UserManager sınıfını başlat
$userManager = new UserManager($pdo);

// Sayfalama ve filtreleme parametreleri
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$search = sanitizeInput($_GET['search'] ?? '');
$status_filter = sanitizeInput($_GET['status'] ?? 'all');
$admin_filter = sanitizeInput($_GET['admin'] ?? 'all');

// Kullanıcı işlemleri (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('CSRF token doğrulaması başarısız.');
    }
    
    $action = sanitizeInput($_POST['action'] ?? '');
    $userId = sanitizeNumber($_POST['user_id'] ?? 0);
    
    try {
        switch ($action) {
            case 'add_user':
                // Yeni kullanıcı ekleme
                $username = sanitizeInput($_POST['username'] ?? '');
                $email = sanitizeInput($_POST['email'] ?? '');
                $firstName = sanitizeInput($_POST['first_name'] ?? '');
                $lastName = sanitizeInput($_POST['last_name'] ?? '');
                $password = $_POST['password'] ?? '';
                $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
                $balance = floatval($_POST['balance'] ?? 0);
                
                // Validasyon
                $errors = [];
                if (empty($username) || strlen($username) < 3) {
                    $errors[] = 'Kullanıcı adı en az 3 karakter olmalıdır.';
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Geçerli bir e-posta adresi girin.';
                }
                if (empty($password) || strlen($password) < 6) {
                    $errors[] = 'Şifre en az 6 karakter olmalıdır.';
                }
                if (empty($firstName)) {
                    $errors[] = 'Ad alanı gereklidir.';
                }
                if (empty($lastName)) {
                    $errors[] = 'Soyad alanı gereklidir.';
                }
                
                // E-posta ve kullanıcı adı kontrolü
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
                $checkStmt->execute([$email, $username]);
                if ($checkStmt->fetch()) {
                    $errors[] = 'Bu e-posta veya kullanıcı adı zaten kullanılıyor.';
                }
                
                if (empty($errors)) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, email, password_hash, first_name, last_name, balance, is_admin, is_active)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 1)
                    ");
                    $stmt->execute([$username, $email, $hashedPassword, $firstName, $lastName, $balance, $isAdmin]);
                    $message = "Kullanıcı başarıyla eklendi.";
                } else {
                    $error = implode(' ', $errors);
                }
                break;
                
            case 'deactivate_user':
                if ($userId && $userId != $user['id']) {
                    $reason = sanitizeInput($_POST['reason'] ?? '');
                    $statusType = sanitizeInput($_POST['status_type'] ?? 'permanent');
                    $duration = sanitizeInput($_POST['duration'] ?? null);
                    
                    if (empty($reason)) {
                        $error = 'Pasif etme sebebi gereklidir.';
                    } else {
                        $result = $userManager->deactivateUser($userId, $reason, $user['id'], $statusType, $duration);
                        if ($result['success']) {
                            $message = $result['message'];
                        } else {
                            $error = $result['message'];
                        }
                    }
                }
                break;
                
            case 'activate_user':
                if ($userId && $userId != $user['id']) {
                    $reason = sanitizeInput($_POST['reason'] ?? 'Admin tarafından yeniden aktif edildi');
                    $result = $userManager->reactivateUser($userId, $user['id'], $reason);
                    if ($result['success']) {
                        $message = $result['message'];
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
                
            case 'toggle_admin':
                if ($userId && $userId != $user['id']) {
                    $stmt = $pdo->prepare("UPDATE users SET is_admin = NOT is_admin WHERE id = ?");
                    $stmt->execute([$userId]);
                    $message = "Admin durumu güncellendi.";
                }
                break;
                
            case 'delete_user':
                if ($userId && $userId != $user['id']) {
                    $reason = sanitizeInput($_POST['reason'] ?? 'Admin tarafından silindi');
                    $result = $userManager->deleteUser($userId, $user['id'], $reason);
                    if ($result['success']) {
                        $message = $result['message'];
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
                
            case 'reset_password':
                if ($userId && $userId != $user['id']) {
                    $newPassword = bin2hex(random_bytes(4)); // 8 karakter rastgele şifre
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $stmt->execute([$hashedPassword, $userId]);
                    $message = "Şifre sıfırlandı. Yeni şifre: " . $newPassword;
                }
                break;
                
            case 'update_balance':
                if ($userId) {
                    $newBalance = floatval($_POST['new_balance'] ?? 0);
                    $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
                    $stmt->execute([$newBalance, $userId]);
                    $message = "Kullanıcı bakiyesi güncellendi.";
                }
                break;
        }
    } catch (Exception $e) {
        $error = "İşlem sırasında hata oluştu: " . $e->getMessage();
    }
}

// Kullanıcıları getir
try {
    $whereConditions = ["1=1"];
    $params = [];
    
    if (!empty($search)) {
        $whereConditions[] = "(username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    if ($status_filter !== 'all') {
        $whereConditions[] = "is_active = ?";
        $params[] = ($status_filter === 'active') ? 1 : 0;
    }
    
    if ($admin_filter !== 'all') {
        $whereConditions[] = "is_admin = ?";
        $params[] = ($admin_filter === 'admin') ? 1 : 0;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Toplam kullanıcı sayısı
    $countSql = "SELECT COUNT(*) FROM users WHERE $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalUsers = $countStmt->fetchColumn();
    $totalPages = ceil($totalUsers / $limit);
    
    // Kullanıcıları getir
    $sql = "SELECT id, username, email, first_name, last_name, balance, is_active, is_admin, 
                   login_attempts, last_login, created_at, deactivation_reason, 
                   deactivated_until, deactivated_by
            FROM users 
            WHERE $whereClause 
            ORDER BY created_at DESC 
            LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Kullanıcılar yüklenirken hata oluştu: " . $e->getMessage();
    $users = [];
    $totalUsers = 0;
    $totalPages = 0;
}

$pageTitle = 'Kullanıcı Yönetimi';
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

        /* Content Card */
        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 8px 32px var(--shadow);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
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

        /* Filters */
        .filters {
            display: grid;
            grid-template-columns: 1fr auto auto auto auto;
            gap: 1rem;
            margin-bottom: 1.5rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
        }

        .form-input, .form-select {
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
        }

        /* Table */
        .table-container {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .table thead {
            background: #f8fafc;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .table th {
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table td {
            color: #6b7280;
        }

        .table tbody tr:hover {
            background: #f9fafb;
        }

        /* Status badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a,
        .pagination span {
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .pagination a {
            color: #6b7280;
            background: white;
            border: 1px solid #e5e7eb;
        }

        .pagination a:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .pagination .current {
            background: var(--primary);
            color: white;
            border: 1px solid var(--primary);
        }

        /* Messages */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Actions dropdown */
        .actions {
            position: relative;
        }

        .actions-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            z-index: 100;
            min-width: 150px;
        }

        .actions:hover .actions-dropdown,
        .actions.active .actions-dropdown {
            display: block;
        }

        .actions-dropdown form {
            margin: 0;
        }

        .actions-dropdown button {
            width: 100%;
            padding: 0.75rem 1rem;
            border: none;
            background: none;
            text-align: left;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 0.875rem;
        }

        .actions-dropdown button:hover {
            background: #f3f4f6;
        }

        .actions-dropdown button:first-child {
            border-radius: 8px 8px 0 0;
        }

        .actions-dropdown button:last-child {
            border-radius: 0 0 8px 8px;
        }

        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 16px var(--shadow);
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: #1e293b;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 240px;
            }
            
            .filters {
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
                <h1 class="page-title">Kullanıcı Yönetimi</h1>
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

            <?php if (isset($message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($totalUsers) ?></div>
                    <div class="stat-label">Toplam Kullanıcı</div>
                </div>
                <div class="stat-card">
                    <?php
                    $activeUsers = 0;
                    foreach ($users as $u) {
                        if ($u['is_active']) $activeUsers++;
                    }
                    ?>
                    <div class="stat-value"><?= number_format($activeUsers) ?></div>
                    <div class="stat-label">Aktif Kullanıcı</div>
                </div>
                <div class="stat-card">
                    <?php
                    $adminUsers = 0;
                    foreach ($users as $u) {
                        if ($u['is_admin']) $adminUsers++;
                    }
                    ?>
                    <div class="stat-value"><?= number_format($adminUsers) ?></div>
                    <div class="stat-label">Admin Kullanıcı</div>
                </div>
                <div class="stat-card">
                    <?php
                    $totalBalance = 0;
                    foreach ($users as $u) {
                        $totalBalance += $u['balance'];
                    }
                    ?>
                    <div class="stat-value"><?= formatPrice($totalBalance) ?></div>
                    <div class="stat-label">Toplam Bakiye</div>
                </div>
            </div>

            <!-- Content Card -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">Kullanıcı Listesi</h3>
                    <button onclick="toggleAddUserForm()" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Yeni Kullanıcı Ekle
                    </button>
                </div>
                <div class="card-body">
                    <!-- Add User Form -->
                    <div id="addUserForm" style="display: none; margin-bottom: 2rem; padding: 1.5rem; background: #f8fafc; border-radius: 12px; border: 2px dashed #e2e8f0;">
                        <h4 style="margin-bottom: 1rem; color: #1e293b;">Yeni Kullanıcı Ekle</h4>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="action" value="add_user">
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div class="form-group">
                                    <label class="form-label">Kullanıcı Adı *</label>
                                    <input type="text" name="username" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">E-posta *</label>
                                    <input type="email" name="email" class="form-input" required>
                                </div>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div class="form-group">
                                    <label class="form-label">Ad *</label>
                                    <input type="text" name="first_name" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Soyad *</label>
                                    <input type="text" name="last_name" class="form-input" required>
                                </div>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr 120px; gap: 1rem; margin-bottom: 1rem;">
                                <div class="form-group">
                                    <label class="form-label">Şifre *</label>
                                    <input type="password" name="password" class="form-input" required minlength="6">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Başlangıç Bakiyesi</label>
                                    <input type="number" name="balance" class="form-input" step="0.01" min="0" value="0">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Admin</label>
                                    <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.75rem;">
                                        <input type="checkbox" name="is_admin" style="width: auto;">
                                        Admin Yap
                                    </label>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 1rem;">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i>
                                    Kullanıcı Ekle
                                </button>
                                <button type="button" onclick="toggleAddUserForm()" class="btn btn-warning">
                                    <i class="fas fa-times"></i>
                                    İptal
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Filters -->
                    <form method="GET" class="filters">
                        <div class="form-group">
                            <label class="form-label">Arama</label>
                            <input type="text" name="search" class="form-input" 
                                   placeholder="Kullanıcı adı, e-posta, ad/soyad..." 
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Durum</label>
                            <select name="status" class="form-select">
                                <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Tümü</option>
                                <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Aktif</option>
                                <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Pasif</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Yetki</label>
                            <select name="admin" class="form-select">
                                <option value="all" <?= $admin_filter === 'all' ? 'selected' : '' ?>>Tümü</option>
                                <option value="admin" <?= $admin_filter === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="user" <?= $admin_filter === 'user' ? 'selected' : '' ?>>Kullanıcı</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                                Filtrele
                            </button>
                        </div>
                        <div class="form-group">
                            <a href="users.php" class="btn btn-warning">
                                <i class="fas fa-times"></i>
                                Temizle
                            </a>
                        </div>
                    </form>

                    <!-- Table -->
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Kullanıcı Bilgileri</th>
                                    <th>E-posta</th>
                                    <th>Bakiye</th>
                                    <th>Durum</th>
                                    <th>Yetki</th>
                                    <th>Son Giriş</th>
                                    <th>Kayıt Tarihi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; color: #6b7280; padding: 2rem;">
                                            <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                            Kullanıcı bulunamadı
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td><strong><?= $u['id'] ?></strong></td>
                                            <td>
                                                <div style="font-weight: 600;"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></div>
                                                <div style="color: #6b7280; font-size: 0.875rem;">@<?= htmlspecialchars($u['username']) ?></div>
                                                <?php if ($u['login_attempts'] > 0): ?>
                                                    <div style="color: #ef4444; font-size: 0.75rem;">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        <?= $u['login_attempts'] ?> hatalı giriş
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($u['email']) ?></td>
                                            <td>
                                                <span style="font-weight: 600; color: <?= $u['balance'] > 0 ? '#059669' : '#6b7280' ?>;">
                                                    <?= formatPrice($u['balance']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($u['is_active']): ?>
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check"></i>
                                                        Aktif
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">
                                                        <i class="fas fa-times"></i>
                                                        Pasif
                                                    </span>
                                                    <?php if ($u['deactivation_reason']): ?>
                                                        <div style="font-size: 0.75rem; color: #ef4444; margin-top: 0.25rem; max-width: 200px;">
                                                            <i class="fas fa-info-circle"></i>
                                                            <?= htmlspecialchars($u['deactivation_reason']) ?>
                                                            <?php if ($u['deactivated_until']): ?>
                                                                <br><small><?= date('d.m.Y H:i', strtotime($u['deactivated_until'])) ?> kadar</small>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($u['is_admin']): ?>
                                                    <span class="badge badge-info">
                                                        <i class="fas fa-crown"></i>
                                                        Admin
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-user"></i>
                                                        Kullanıcı
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= $u['last_login'] ? date('d.m.Y H:i', strtotime($u['last_login'])) : 'Hiç' ?>
                                            </td>
                                            <td><?= date('d.m.Y H:i', strtotime($u['created_at'])) ?></td>
                                            <td>
                                                <?php if ($u['id'] != $user['id']): // Kendi hesabına işlem yapmasın ?>
                                                    <div class="actions">
                                                        <button class="btn btn-sm btn-primary">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </button>
                                                        <div class="actions-dropdown">
                                                            <?php if ($u['is_active']): ?>
                                                                <button type="button" onclick="showDeactivateModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>')">
                                                                    <i class="fas fa-user-times"></i>
                                                                    Pasifleştir
                                                                </button>
                                                            <?php else: ?>
                                                                <button type="button" onclick="showActivateModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>')">
                                                                    <i class="fas fa-user-check"></i>
                                                                    Aktifleştir
                                                                </button>
                                                            <?php endif; ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                                <input type="hidden" name="action" value="toggle_admin">
                                                                <button type="submit" onclick="return confirm('Admin durumunu değiştirmek istediğinizden emin misiniz?')">
                                                                    <i class="fas fa-crown"></i>
                                                                    <?= $u['is_admin'] ? 'Admin Kaldır' : 'Admin Yap' ?>
                                                                </button>
                                                            </form>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                                <input type="hidden" name="action" value="reset_password">
                                                                <button type="submit" onclick="return confirm('Kullanıcının şifresini sıfırlamak istediğinizden emin misiniz?')">
                                                                    <i class="fas fa-key"></i>
                                                                    Şifre Sıfırla
                                                                </button>
                                                            </form>
                                                            <form method="POST" style="display: inline;" onsubmit="return updateBalance(<?= $u['id'] ?>, <?= $u['balance'] ?>)">
                                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                                <input type="hidden" name="action" value="update_balance">
                                                                <input type="hidden" name="new_balance" id="balance_<?= $u['id'] ?>" value="<?= $u['balance'] ?>">
                                                                <button type="submit">
                                                                    <i class="fas fa-coins"></i>
                                                                    Bakiye Güncelle
                                                                </button>
                                                            </form>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                                <input type="hidden" name="action" value="delete_user">
                                                                <button type="submit" style="color: #ef4444;" onclick="return confirm('Kullanıcıyı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!')">
                                                                    <i class="fas fa-trash"></i>
                                                                    Sil
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span style="color: #6b7280; font-size: 0.875rem;">
                                                        <i class="fas fa-user-shield"></i>
                                                        Sen
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>&admin=<?= $admin_filter ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end = min($totalPages, $page + 2);
                            
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>&admin=<?= $admin_filter ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>&admin=<?= $admin_filter ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Deactivate User Modal -->
    <div id="deactivateModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-times"></i> Kullanıcıyı Pasifleştir</h3>
                <span class="close" onclick="closeModal('deactivateModal')">&times;</span>
            </div>
            <form method="POST" class="modal-form">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="deactivate_user">
                <input type="hidden" name="user_id" id="deactivate_user_id">
                
                <div class="form-group">
                    <label class="form-label">Kullanıcı</label>
                    <input type="text" id="deactivate_user_name" class="form-input" readonly>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Pasif Etme Türü</label>
                    <select name="status_type" class="form-input" onchange="toggleDeactivationDuration()">
                        <option value="permanent">Kalıcı</option>
                        <option value="temporary">Geçici</option>
                    </select>
                </div>
                
                <div class="form-group" id="deactivation_duration" style="display: none;">
                    <label class="form-label">Pasif Etme Süresi</label>
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
                
                <div class="form-group">
                    <label class="form-label">Pasif Etme Sebebi *</label>
                    <textarea name="reason" class="form-input" rows="4" placeholder="Kullanıcıyı neden pasif ediyorsunuz?" required></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" onclick="closeModal('deactivateModal')" class="btn btn-secondary">İptal</button>
                    <button type="submit" class="btn btn-danger">Pasifleştir</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Activate User Modal -->
    <div id="activateModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-check"></i> Kullanıcıyı Aktifleştir</h3>
                <span class="close" onclick="closeModal('activateModal')">&times;</span>
            </div>
            <form method="POST" class="modal-form">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="activate_user">
                <input type="hidden" name="user_id" id="activate_user_id">
                
                <div class="form-group">
                    <label class="form-label">Kullanıcı</label>
                    <input type="text" id="activate_user_name" class="form-input" readonly>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Aktifleştirme Sebebi</label>
                    <textarea name="reason" class="form-input" rows="3" placeholder="Kullanıcıyı neden aktifleştiriyorsunuz?">Admin tarafından yeniden aktif edildi</textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" onclick="closeModal('activateModal')" class="btn btn-secondary">İptal</button>
                    <button type="submit" class="btn btn-success">Aktifleştir</button>
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
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
    </style>

    <script>
        // Currency formatting function for JavaScript
        function formatCurrency(amount) {
            // Get currency from PHP - we'll use a simple approach for now
            // You can enhance this by passing the currency from PHP to JavaScript
            const currency = '<?php echo getDefaultCurrency(); ?>';
            const symbols = {
                'TRY': '₺',
                'USD': '$',
                'EUR': '€'
            };
            const symbol = symbols[currency] || '$';
            return symbol + parseFloat(amount).toFixed(2);
        }

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });

        // Toggle add user form
        function toggleAddUserForm() {
            const form = document.getElementById('addUserForm');
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
                form.scrollIntoView({ behavior: 'smooth' });
            } else {
                form.style.display = 'none';
            }
        }

        // Modal functions
        function showDeactivateModal(userId, userName) {
            document.getElementById('deactivate_user_id').value = userId;
            document.getElementById('deactivate_user_name').value = userName;
            document.getElementById('deactivateModal').style.display = 'block';
        }
        
        function showActivateModal(userId, userName) {
            document.getElementById('activate_user_id').value = userId;
            document.getElementById('activate_user_name').value = userName;
            document.getElementById('activateModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function toggleDeactivationDuration() {
            const statusType = document.querySelector('select[name="status_type"]').value;
            const durationDiv = document.getElementById('deactivation_duration');
            if (statusType === 'temporary') {
                durationDiv.style.display = 'block';
            } else {
                durationDiv.style.display = 'none';
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(function(modal) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Update balance function
        function updateBalance(userId, currentBalance) {
            const newBalance = prompt('Yeni bakiye miktarını girin:', currentBalance);
            if (newBalance === null) return false;
            
            const balance = parseFloat(newBalance);
            if (isNaN(balance) || balance < 0) {
                alert('Geçerli bir bakiye miktarı girin.');
                return false;
            }
            
            document.getElementById('balance_' + userId).value = balance;
            return confirm('Kullanıcının bakiyesini ' + formatCurrency(balance) + ' olarak güncellemek istediğinizden emin misiniz?');
        }

        // Dropdown toggle functionality
        document.addEventListener('click', function(e) {
            // Toggle dropdown when clicking the action button
            if (e.target.closest('.actions .btn')) {
                e.preventDefault();
                const actions = e.target.closest('.actions');
                actions.classList.toggle('active');
                
                // Close other dropdowns
                document.querySelectorAll('.actions.active').forEach(function(other) {
                    if (other !== actions) {
                        other.classList.remove('active');
                    }
                });
                return;
            }
            
            // Close dropdowns when clicking outside
            if (!e.target.closest('.actions')) {
                document.querySelectorAll('.actions.active').forEach(function(actions) {
                    actions.classList.remove('active');
                });
            }
            
            // Confirm dangerous actions
            if (e.target.closest('button[onclick*="confirm"]')) {
                const button = e.target.closest('button');
                const confirmText = button.getAttribute('onclick').match(/confirm\\('([^']+)'\\)/)[1];
                if (!confirm(confirmText)) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    </script>
</body>
</html>

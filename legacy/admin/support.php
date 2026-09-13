<?php
// Admin authentication
require_once 'auth_header.php';

// AJAX işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    try {
        switch($action) {
            case 'update_ticket_status':
                $ticketId = sanitizeInput($_POST['ticket_id']);
                $status = sanitizeInput($_POST['status']);
                
                if (empty($ticketId)) {
                    throw new Exception('Geçersiz ticket ID');
                }
                
                $validStatuses = ['open', 'in_progress', 'waiting_customer', 'resolved', 'closed'];
                if (!in_array($status, $validStatuses)) {
                    throw new Exception('Geçersiz durum');
                }
                
                $stmt = $pdo->prepare("UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE ticket_id = ?");
                $stmt->execute([$status, $ticketId]);
                
                echo json_encode(['success' => true, 'message' => 'Ticket durumu güncellendi']);
                exit;
                
            case 'get_ticket_details':
                $ticketId = sanitizeInput($_POST['ticket_id']);
                
                if (empty($ticketId)) {
                    throw new Exception('Geçersiz ticket ID');
                }
                
                // Ticket bilgilerini getir
                $stmt = $pdo->prepare("
                    SELECT st.*, u.first_name, u.last_name, u.email, 
                           o.order_id, o.product_name, o.total_price
                    FROM support_tickets st
                    JOIN users u ON st.user_id = u.id
                    JOIN orders o ON st.order_id = o.order_id
                    WHERE st.ticket_id = ?
                ");
                $stmt->execute([$ticketId]);
                $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$ticket) {
                    throw new Exception('Ticket bulunamadı');
                }
                
                // Ticket yanıtlarını getir
                $stmt = $pdo->prepare("
                    SELECT tr.*, 
                           CASE WHEN tr.is_admin_reply = 1 THEN 
                               (SELECT CONCAT(first_name, ' ', last_name) FROM users WHERE id = tr.admin_id)
                           ELSE 
                               (SELECT CONCAT(first_name, ' ', last_name) FROM users WHERE id = tr.user_id)
                           END as reply_author_name,
                           tr.is_admin_reply
                    FROM ticket_replies tr
                    WHERE tr.ticket_id = ?
                    ORDER BY tr.created_at ASC
                ");
                $stmt->execute([$ticketId]);
                $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $ticket['replies'] = $replies;
                
                echo json_encode(['success' => true, 'ticket' => $ticket]);
                exit;
                
            case 'reply_ticket':
                $ticketId = sanitizeInput($_POST['ticket_id']);
                $message = sanitizeInput($_POST['message']);
                
                if (empty($ticketId) || empty($message)) {
                    throw new Exception('Ticket ID ve mesaj gereklidir');
                }
                
                // Yanıt ekle
                $stmt = $pdo->prepare("
                    INSERT INTO ticket_replies (ticket_id, admin_id, message, is_admin_reply) 
                    VALUES (?, ?, ?, 1)
                ");
                $stmt->execute([$ticketId, $user['id'], $message]);
                
                // Ticket durumunu güncelle
                $stmt = $pdo->prepare("UPDATE support_tickets SET status = 'waiting_customer', updated_at = NOW(), last_reply_at = NOW(), last_reply_by = 'admin' WHERE ticket_id = ?");
                $stmt->execute([$ticketId]);
                
                echo json_encode(['success' => true, 'message' => 'Yanıt gönderildi']);
                exit;
                
            case 'delete_ticket':
                $ticketId = sanitizeInput($_POST['ticket_id']);
                
                if (empty($ticketId)) {
                    throw new Exception('Geçersiz ticket ID');
                }
                
                // Ticket ve yanıtlarını sil
                $stmt = $pdo->prepare("DELETE FROM support_tickets WHERE ticket_id = ?");
                $stmt->execute([$ticketId]);
                
                echo json_encode(['success' => true, 'message' => 'Ticket silindi']);
                exit;
                
            default:
                throw new Exception('Geçersiz işlem');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Filtreleme parametreleri
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';
$assignedTo = $_GET['assigned_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Sorgu oluşturma
$whereConditions = ['1=1'];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(st.subject LIKE ? OR st.message LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR o.order_id LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($status)) {
    $whereConditions[] = "st.status = ?";
    $params[] = $status;
}

if (!empty($priority)) {
    $whereConditions[] = "st.priority = ?";
    $params[] = $priority;
}

$whereClause = implode(' AND ', $whereConditions);

// Toplam sayı
$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM support_tickets st
    JOIN users u ON st.user_id = u.id
    JOIN orders o ON st.order_id = o.order_id
    WHERE $whereClause
");
$countStmt->execute($params);
$totalTickets = $countStmt->fetchColumn();
$totalPages = ceil($totalTickets / $limit);

// Tickets'ları getir
$stmt = $pdo->prepare("
    SELECT st.*, u.first_name, u.last_name, u.email,
           o.order_id, o.product_name,
           (SELECT COUNT(*) FROM ticket_replies tr WHERE tr.ticket_id = st.ticket_id) as reply_count
    FROM support_tickets st
    JOIN users u ON st.user_id = u.id
    JOIN orders o ON st.order_id = o.order_id
    WHERE $whereClause
    ORDER BY st.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Admin kullanıcıları getir
$adminStmt = $pdo->query("SELECT id, first_name, last_name FROM users WHERE is_admin = 1");
$admins = $adminStmt->fetchAll(PDO::FETCH_ASSOC);

// İstatistikler
$statsStmt = $pdo->query("
    SELECT 
        COUNT(*) as total_tickets,
        COUNT(CASE WHEN status = 'open' THEN 1 END) as open_tickets,
        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_tickets,
        COUNT(CASE WHEN status = 'waiting_customer' THEN 1 END) as waiting_tickets,
        COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_tickets,
        COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_tickets,
        0 as unassigned_tickets
    FROM support_tickets
");
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$pageTitle = 'Destek Yönetimi';
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
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
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
            margin-left: 280px;
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
            font-size: 1.875rem;
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
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px var(--shadow);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .stat-card.total .stat-value { color: var(--primary); }
        .stat-card.open .stat-value { color: var(--info); }
        .stat-card.progress .stat-value { color: var(--warning); }
        .stat-card.waiting .stat-value { color: var(--warning); }
        .stat-card.resolved .stat-value { color: var(--success); }
        .stat-card.closed .stat-value { color: #6b7280; }
        .stat-card.unassigned .stat-value { color: var(--danger); }

        /* Content Card */
        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 8px 32px var(--shadow);
            overflow: hidden;
        }

        /* Filters */
        .filters {
            padding: 2rem;
            border-bottom: 1px solid var(--border);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.875rem;
        }

        .form-control {
            padding: 0.75rem 1rem;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
            font-family: inherit;
            line-height: 1.5;
            width: 100%;
            box-sizing: border-box;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            min-height: 44px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
            min-height: 32px;
        }

        .btn-success { background: var(--success); color: white; }
        .btn-warning { background: var(--warning); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-info { background: var(--info); color: white; }
        .btn-secondary { background: #6b7280; color: white; }

        /* Table */
        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .table th {
            background: #f8fafc;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .table tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        /* Status badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-open {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .status-in_progress {
            background: rgba(251, 191, 36, 0.1);
            color: #f59e0b;
        }

        .status-waiting_customer {
            background: rgba(251, 191, 36, 0.1);
            color: #f59e0b;
        }

        .status-resolved {
            background: rgba(74, 222, 128, 0.1);
            color: var(--success);
        }

        .status-closed {
            background: rgba(107, 114, 128, 0.1);
            color: #6b7280;
        }

        .priority-low {
            background: rgba(74, 222, 128, 0.1);
            color: var(--success);
        }

        .priority-medium {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .priority-high {
            background: rgba(251, 191, 36, 0.1);
            color: #f59e0b;
        }

        .priority-urgent {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
        }

        .close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #64748b;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: var(--danger);
        }

        /* Chat messages */
        .chat-messages {
            max-height: 400px;
            overflow-y: auto;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .chat-messages::after {
            content: "";
            display: table;
            clear: both;
        }

        .message {
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 12px;
            max-width: 80%;
            clear: both;
        }

        .message.user {
            background: #e5e7eb;
            margin-right: auto;
            float: left;
            border-bottom-left-radius: 4px;
        }

        .message.admin {
            background: var(--primary);
            color: white;
            margin-left: auto;
            float: right;
            border-bottom-right-radius: 4px;
        }

        .message-author {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.5rem;
        }

        /* Toast notifications */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            z-index: 10000;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .toast.success {
            background: var(--success);
        }

        .toast.error {
            background: var(--danger);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .filters {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
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
                <h1 class="page-title"><?= $pageTitle ?></h1>
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                        <div style="font-size: 0.875rem; color: #64748b;">Admin</div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-value"><?= number_format($stats['total_tickets']) ?></div>
                    <div class="stat-label">Toplam Ticket</div>
                </div>
                <div class="stat-card open">
                    <div class="stat-value"><?= number_format($stats['open_tickets']) ?></div>
                    <div class="stat-label">Açık</div>
                </div>
                <div class="stat-card progress">
                    <div class="stat-value"><?= number_format($stats['in_progress_tickets']) ?></div>
                    <div class="stat-label">İşlemde</div>
                </div>
                <div class="stat-card waiting">
                    <div class="stat-value"><?= number_format($stats['waiting_tickets']) ?></div>
                    <div class="stat-label">Müşteri Bekliyor</div>
                </div>
                <div class="stat-card resolved">
                    <div class="stat-value"><?= number_format($stats['resolved_tickets']) ?></div>
                    <div class="stat-label">Çözüldü</div>
                </div>
                <div class="stat-card closed">
                    <div class="stat-value"><?= number_format($stats['closed_tickets']) ?></div>
                    <div class="stat-label">Kapatıldı</div>
                </div>
                <div class="stat-card unassigned">
                    <div class="stat-value"><?= number_format($stats['unassigned_tickets']) ?></div>
                    <div class="stat-label">Atanmamış</div>
                </div>
            </div>

            <!-- Content -->
            <div class="content-card">
                <!-- Filters -->
                <div class="filters">
                    <div class="form-group">
                        <label>Arama</label>
                        <input type="text" class="form-control" id="searchInput" placeholder="Ticket, kullanıcı, sipariş ara..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="form-group">
                        <label>Durum</label>
                        <select class="form-control" id="statusFilter">
                            <option value="">Tüm Durumlar</option>
                            <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Açık</option>
                            <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>İşlemde</option>
                            <option value="waiting_customer" <?= $status === 'waiting_customer' ? 'selected' : '' ?>>Müşteri Bekliyor</option>
                            <option value="resolved" <?= $status === 'resolved' ? 'selected' : '' ?>>Çözüldü</option>
                            <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Kapatıldı</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Öncelik</label>
                        <select class="form-control" id="priorityFilter">
                            <option value="">Tüm Öncelikler</option>
                            <option value="low" <?= $priority === 'low' ? 'selected' : '' ?>>Düşük</option>
                            <option value="medium" <?= $priority === 'medium' ? 'selected' : '' ?>>Orta</option>
                            <option value="high" <?= $priority === 'high' ? 'selected' : '' ?>>Yüksek</option>
                            <option value="urgent" <?= $priority === 'urgent' ? 'selected' : '' ?>>Acil</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button class="btn btn-primary" onclick="filterTickets()">
                            <i class="fas fa-search"></i>
                            Filtrele
                        </button>
                    </div>
                </div>

                <!-- Tickets Table -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Ticket #</th>
                                <th>Müşteri</th>
                                <th>Sipariş</th>
                                <th>Konu</th>
                                <th>Durum</th>
                                <th>Öncelik</th>
                                <th>Atanan</th>
                                <th>Yanıt</th>
                                <th>Tarih</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600; color: var(--primary);">#<?= htmlspecialchars($ticket['ticket_id']) ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']) ?></div>
                                        <div style="font-size: 0.75rem; color: #64748b;"><?= htmlspecialchars($ticket['email']) ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 500;">#<?= htmlspecialchars($ticket['order_id']) ?></div>
                                        <div style="font-size: 0.75rem; color: #64748b;"><?= htmlspecialchars($ticket['product_name']) ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 500;"><?= htmlspecialchars(substr($ticket['subject'], 0, 30)) ?>...</div>
                                    </td>
                                    <td>
                                        <select class="status-badge status-<?= $ticket['status'] ?> form-control" style="border: none; background: transparent; font-size: 0.75rem; padding: 0.25rem;" onchange="updateTicketStatus('<?= $ticket['ticket_id'] ?>', this.value)">
                                            <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Açık</option>
                                            <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>İşlemde</option>
                                            <option value="waiting_customer" <?= $ticket['status'] === 'waiting_customer' ? 'selected' : '' ?>>Müşteri Bekliyor</option>
                                            <option value="resolved" <?= $ticket['status'] === 'resolved' ? 'selected' : '' ?>>Çözüldü</option>
                                            <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Kapatıldı</option>
                                        </select>
                                    </td>
                                    <td>
                                        <span class="status-badge priority-<?= $ticket['priority'] ?>">
                                            <?= ucfirst($ticket['priority']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge" style="background: rgba(107, 114, 128, 0.1); color: #6b7280;">Yok</span>
                                    </td>
                                    <td>
                                        <span class="status-badge" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                                            <?= $ticket['reply_count'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('d.m.Y H:i', strtotime($ticket['created_at'])) ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                            <button class="btn btn-info btn-sm" onclick="viewTicketDetails('<?= $ticket['ticket_id'] ?>')" title="Detaylar">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteTicket('<?= $ticket['ticket_id'] ?>')" title="Sil">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($tickets)): ?>
                                <tr>
                                    <td colspan="10" style="text-align: center; padding: 2rem; color: #64748b;">
                                        Henüz destek talebi bulunamadı.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div style="display: flex; justify-content: center; gap: 0.5rem; margin: 2rem 0;">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&priority=<?= urlencode($priority) ?>" class="btn btn-secondary btn-sm">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="btn btn-primary btn-sm"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&priority=<?= urlencode($priority) ?>" class="btn btn-secondary btn-sm"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&priority=<?= urlencode($priority) ?>" class="btn btn-secondary btn-sm">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Ticket Details Modal -->
    <div id="ticketModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Ticket Detayları</h3>
                <button type="button" class="close" onclick="closeModal()">&times;</button>
            </div>
            <div id="ticketDetails">
                <!-- Ticket details will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        // Filter function
        function filterTickets() {
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            const priority = document.getElementById('priorityFilter').value;
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (status) params.append('status', status);
            if (priority) params.append('priority', priority);
            
            window.location.href = 'support.php?' + params.toString();
        }

        // Enter key search
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                filterTickets();
            }
        });

        // Update ticket status
        function updateTicketStatus(ticketId, newStatus) {
            fetch('support.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_ticket_status&ticket_id=${ticketId}&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }
            })
            .catch(error => {
                showToast('Bir hata oluştu', 'error');
                console.error('Error:', error);
            });
        }


        // View ticket details
        function viewTicketDetails(ticketId) {
            fetch('support.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_ticket_details&ticket_id=${ticketId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const ticket = data.ticket;
                    document.getElementById('modalTitle').textContent = `Ticket #${ticket.ticket_id} - ${ticket.subject}`;
                    
                    let repliesHtml = '';
                    if (ticket.replies && ticket.replies.length > 0) {
                        repliesHtml = ticket.replies.map(reply => `
                            <div class="message ${reply.is_admin_reply ? 'admin' : 'user'}">
                                <div class="message-author">
                                    ${reply.is_admin_reply ? 'Admin: ' : 'Müşteri: '}${reply.reply_author_name}
                                </div>
                                <div>${reply.message}</div>
                                <div class="message-time">${new Date(reply.created_at).toLocaleString('tr-TR')}</div>
                            </div>
                        `).join('');
                    } else {
                        repliesHtml = '<div style="text-align: center; color: #64748b; padding: 2rem;">Henüz yanıt yok</div>';
                    }
                    
                    const detailsHtml = `
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                            <div>
                                <h4 style="margin-bottom: 1rem; color: var(--dark);">Müşteri Bilgileri</h4>
                                <div style="background: #f8fafc; padding: 1rem; border-radius: 8px;">
                                    <p><strong>Ad Soyad:</strong> ${ticket.first_name} ${ticket.last_name}</p>
                                    <p><strong>E-posta:</strong> ${ticket.email}</p>
                                    <p><strong>Sipariş:</strong> #${ticket.order_id}</p>
                                    <p><strong>Ürün:</strong> ${ticket.product_name}</p>
                                </div>
                            </div>
                            <div>
                                <h4 style="margin-bottom: 1rem; color: var(--dark);">Ticket Bilgileri</h4>
                                <div style="background: #f8fafc; padding: 1rem; border-radius: 8px;">
                                    <p><strong>Durum:</strong> <span class="status-badge status-${ticket.status}">${getStatusText(ticket.status)}</span></p>
                                    <p><strong>Öncelik:</strong> <span class="status-badge priority-${ticket.priority}">${ticket.priority}</span></p>
                                    <p><strong>Atanan:</strong> Yok</p>
                                    <p><strong>Oluşturulma:</strong> ${new Date(ticket.created_at).toLocaleString('tr-TR')}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 2rem;">
                            <h4 style="margin-bottom: 1rem; color: var(--dark);">İlk Mesaj</h4>
                            <div style="background: #f8fafc; padding: 1rem; border-radius: 8px;">
                                ${ticket.message}
                            </div>
                        </div>

                        <div style="margin-bottom: 2rem;">
                            <h4 style="margin-bottom: 1rem; color: var(--dark);">Mesaj Geçmişi</h4>
                            <div class="chat-messages">
                                ${repliesHtml}
                            </div>
                        </div>

                        <div>
                            <h4 style="margin-bottom: 1rem; color: var(--dark);">Yanıt Gönder</h4>
                            <form onsubmit="replyTicket(event, '${ticket.ticket_id}')">
                                <textarea class="form-control" rows="6" placeholder="Yanıtınızı yazın..." required id="replyMessage" style="min-height: 120px; resize: vertical; width: 100%;"></textarea>
                                <div style="margin-top: 1rem; display: flex; gap: 1rem;">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i>
                                        Yanıt Gönder
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                                        İptal
                                    </button>
                                </div>
                            </form>
                        </div>
                    `;
                    
                    document.getElementById('ticketDetails').innerHTML = detailsHtml;
                    document.getElementById('ticketModal').style.display = 'block';
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Bir hata oluştu', 'error');
                console.error('Error:', error);
            });
        }

        // Reply to ticket
        function replyTicket(event, ticketId) {
            event.preventDefault();
            const message = document.getElementById('replyMessage').value;
            
            if (!message.trim()) {
                showToast('Lütfen bir mesaj yazın', 'error');
                return;
            }
            
            fetch('support.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=reply_ticket&ticket_id=${ticketId}&message=${encodeURIComponent(message)}`
            })
            .then(response => response.json())
            .then(data => {
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    closeModal();
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }
            })
            .catch(error => {
                showToast('Bir hata oluştu', 'error');
                console.error('Error:', error);
            });
        }

        // Delete ticket
        function deleteTicket(ticketId) {
            if (confirm('Bu destek talebini silmek istediğinizden emin misiniz?')) {
                fetch('support.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete_ticket&ticket_id=${ticketId}`
                })
                .then(response => response.json())
                .then(data => {
                    showToast(data.message, data.success ? 'success' : 'error');
                    if (data.success) {
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    }
                })
                .catch(error => {
                    showToast('Bir hata oluştu', 'error');
                });
            }
        }

        // Close modal
        function closeModal() {
            document.getElementById('ticketModal').style.display = 'none';
        }

        // Helper functions
        function getStatusText(status) {
            const statusMap = {
                'open': 'Açık',
                'in_progress': 'İşlemde',
                'waiting_customer': 'Müşteri Bekliyor',
                'resolved': 'Çözüldü',
                'closed': 'Kapatıldı'
            };
            return statusMap[status] || status;
        }

        // Toast notification
        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        }

        // Close modal on outside click
        window.onclick = function(event) {
            const modal = document.getElementById('ticketModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>

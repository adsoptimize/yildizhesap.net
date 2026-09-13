<?php
/**
 * Session Invalidation Tool
 * Belirli session token'ları manuel olarak geçersiz kılmak için
 */

require_once '../config.php';
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

$page_title = "Session Yönetimi";

$sessionManager = new DatabaseSessionManager($pdo);
$message = '';
$sessions = [];

// Session işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'kill_session' && !empty($_POST['session_token'])) {
        $sessionToken = $_POST['session_token'];
        
        if ($sessionManager->invalidateSession($sessionToken, 'admin', 'Manuel olarak sonlandırıldı')) {
            $message = "<div class='alert alert-success'>Session başarıyla sonlandırıldı: " . substr($sessionToken, 0, 16) . "...</div>";
        } else {
            $message = "<div class='alert alert-danger'>Session sonlandırılamadı!</div>";
        }
    }
    
    if ($action === 'kill_all_user' && !empty($_POST['user_id'])) {
        $userId = intval($_POST['user_id']);
        
        if ($sessionManager->invalidateAllUserSessions($userId)) {
            $message = "<div class='alert alert-success'>Kullanıcının tüm session'ları sonlandırıldı (User ID: $userId)</div>";
        } else {
            $message = "<div class='alert alert-danger'>Session'lar sonlandırılamadı!</div>";
        }
    }
    
    if ($action === 'clean_expired') {
        $cleanedCount = $sessionManager->cleanExpiredSessions();
        $message = "<div class='alert alert-info'>$cleanedCount adet eski session temizlendi</div>";
    }
}

// Aktif session'ları al
try {
    $stmt = $pdo->prepare("
        SELECT s.*, u.username, u.email, u.first_name, u.last_name
        FROM active_sessions s
        JOIN users u ON s.user_id = u.id 
        WHERE s.is_active = TRUE 
        ORDER BY s.last_activity DESC
        LIMIT 50
    ");
    $stmt->execute();
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = "<div class='alert alert-danger'>Hata: " . $e->getMessage() . "</div>";
}

// Güvenlik raporu al
$securityReport = $sessionManager->getSecurityReport(7);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; padding: 20px; }
        .container { max-width: 1200px; }
        .session-card { border-left: 4px solid #007bff; }
        .session-card.expired { border-left-color: #dc3545; }
        .session-card.warning { border-left-color: #ffc107; }
        .btn-kill { background: #dc3545; border-color: #dc3545; }
        .btn-kill:hover { background: #c82333; border-color: #c82333; }
        .security-stats { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-shield-alt"></i> Session Yönetim Paneli</h1>
                    <div>
                        <span class="badge bg-primary">Aktif Session: <?php echo count($sessions); ?></span>
                        <span class="badge bg-success">Online</span>
                    </div>
                </div>
                
                <?php echo $message; ?>
                
                <!-- Güvenlik Raporu -->
                <?php if ($securityReport): ?>
                <div class="card security-stats mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-chart-bar"></i> Son 7 Gün Güvenlik Raporu</h5>
                        <div class="row text-center">
                            <div class="col-md-2">
                                <h3><?php echo $securityReport['total_sessions']; ?></h3>
                                <small>Toplam Session</small>
                            </div>
                            <div class="col-md-2">
                                <h3><?php echo $securityReport['active_sessions']; ?></h3>
                                <small>Aktif Session</small>
                            </div>
                            <div class="col-md-2">
                                <h3><?php echo $securityReport['unique_users']; ?></h3>
                                <small>Benzersiz Kullanıcı</small>
                            </div>
                            <div class="col-md-2">
                                <h3><?php echo $securityReport['unique_ips']; ?></h3>
                                <small>Benzersiz IP</small>
                            </div>
                            <div class="col-md-4">
                                <h3 class="text-warning"><?php echo $securityReport['security_violations']; ?></h3>
                                <small>Güvenlik İhlali</small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Toplu İşlemler -->
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-tools"></i> Toplu İşlemler</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="clean_expired">
                                    <button type="submit" class="btn btn-info w-100" onclick="return confirm('Süresi dolan session\'ları temizlemek istediğinizden emin misiniz?')">
                                        <i class="fas fa-broom"></i> Eski Session'ları Temizle
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-4">
                                <form method="POST" class="d-inline">
                                    <div class="input-group">
                                        <input type="number" name="user_id" class="form-control" placeholder="User ID" required>
                                        <input type="hidden" name="action" value="kill_all_user">
                                        <button type="submit" class="btn btn-warning" onclick="return confirm('Bu kullanıcının tüm session\'larını sonlandırmak istediğinizden emin misiniz?')">
                                            <i class="fas fa-user-times"></i> Kullanıcı Session'larını Sonlandır
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-4">
                                <form method="POST">
                                    <div class="input-group">
                                        <input type="text" name="session_token" class="form-control" placeholder="Session Token" required>
                                        <input type="hidden" name="action" value="kill_session">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Bu session\'ı sonlandırmak istediğinizden emin misiniz?')">
                                            <i class="fas fa-skull-crossbones"></i> Session'ı Öldür
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Aktif Session'lar -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-users"></i> Aktif Session'lar</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($sessions)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Aktif session bulunamadı.
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($sessions as $session): 
                                    $sessionAge = time() - strtotime($session['created_at']);
                                    $lastActivity = time() - strtotime($session['last_activity']);
                                    $deviceInfo = json_decode($session['device_info'], true);
                                    
                                    $cardClass = 'session-card';
                                    if ($lastActivity > 1800) { // 30 dakika
                                        $cardClass .= ' warning';
                                    }
                                ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card <?php echo $cardClass; ?>">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="card-title">
                                                        <i class="fas fa-user"></i> 
                                                        <?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?>
                                                    </h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($session['email']); ?></small>
                                                </div>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="kill_session">
                                                    <input type="hidden" name="session_token" value="<?php echo htmlspecialchars($session['session_token']); ?>">
                                                    <button type="submit" class="btn btn-sm btn-kill text-white" 
                                                            title="Session'ı Sonlandır"
                                                            onclick="return confirm('Bu session\'ı sonlandırmak istediğinizden emin misiniz?')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            
                                            <hr>
                                            
                                            <div class="row small">
                                                <div class="col-6">
                                                    <strong>IP:</strong><br>
                                                    <code><?php echo htmlspecialchars($session['ip_address']); ?></code>
                                                </div>
                                                <div class="col-6">
                                                    <strong>Lokasyon:</strong><br>
                                                    <?php echo htmlspecialchars($session['login_location'] ?? 'Bilinmiyor'); ?>
                                                </div>
                                            </div>
                                            
                                            <div class="row small mt-2">
                                                <div class="col-6">
                                                    <strong>Tarayıcı:</strong><br>
                                                    <?php echo htmlspecialchars($deviceInfo['browser'] ?? 'Bilinmiyor'); ?>
                                                </div>
                                                <div class="col-6">
                                                    <strong>OS:</strong><br>
                                                    <?php echo htmlspecialchars($deviceInfo['os'] ?? 'Bilinmiyor'); ?>
                                                </div>
                                            </div>
                                            
                                            <div class="row small mt-2">
                                                <div class="col-6">
                                                    <strong>Oluşturulma:</strong><br>
                                                    <?php echo date('d.m.Y H:i', strtotime($session['created_at'])); ?>
                                                </div>
                                                <div class="col-6">
                                                    <strong>Son Aktivite:</strong><br>
                                                    <span class="<?php echo $lastActivity > 1800 ? 'text-warning' : 'text-success'; ?>">
                                                        <?php 
                                                        if ($lastActivity < 60) {
                                                            echo 'Az önce';
                                                        } elseif ($lastActivity < 3600) {
                                                            echo floor($lastActivity / 60) . ' dk önce';
                                                        } else {
                                                            echo floor($lastActivity / 3600) . ' saat önce';
                                                        }
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    <strong>Token:</strong> 
                                                    <code><?php echo substr($session['session_token'], 0, 16); ?>...</code>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto refresh her 30 saniyede
        setTimeout(function() {
            window.location.reload();
        }, 30000);
        
        // Son güncelleme zamanı göster
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('tr-TR');
            console.log('Son güncelleme: ' + timeStr);
        });
    </script>
</body>
</html>

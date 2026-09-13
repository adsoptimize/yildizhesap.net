<?php
require_once '../config.php';
require_once 'auth_header.php';
require_once 'check-ip-ban.php';

// CSRF token oluştur
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$messageType = '';

// Kategori işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token kontrolü
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Güvenlik hatası!';
        $messageType = 'error';
    } else {
        $db = Database::getInstance()->getConnection();
        
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    $name = trim($_POST['name']);
                    $slug = trim($_POST['slug']);
                    $description = trim($_POST['description']);
                    $icon = trim($_POST['icon']);
                    $image_url = trim($_POST['image_url']);
                    $is_active = isset($_POST['is_active']) ? 1 : 0;
                    
                    if (empty($name)) {
                        $message = 'Kategori adı boş olamaz!';
                        $messageType = 'error';
                    } else {
                        try {
                            // Slug otomatik oluştur
                            if (empty($slug)) {
                                $slug = strtolower(str_replace([' ', 'ç', 'ğ', 'ı', 'ö', 'ş', 'ü'], ['-', 'c', 'g', 'i', 'o', 's', 'u'], $name));
                                $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
                                $slug = preg_replace('/-+/', '-', $slug);
                                $slug = trim($slug, '-');
                            }
                            
                            $stmt = $db->prepare("INSERT INTO categories (name, slug, description, icon, image_url, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$name, $slug, $description, $icon, $image_url, $is_active]);
                            
                            $message = 'Kategori başarıyla eklendi!';
                            $messageType = 'success';
                        } catch (PDOException $e) {
                            if ($e->getCode() == 23000) {
                                $message = 'Bu kategori adı veya slug zaten mevcut!';
                            } else {
                                $message = 'Kategori eklenirken hata oluştu: ' . $e->getMessage();
                            }
                            $messageType = 'error';
                        }
                    }
                    break;
                    
                case 'edit':
                    $id = (int)$_POST['id'];
                    $name = trim($_POST['name']);
                    $slug = trim($_POST['slug']);
                    $description = trim($_POST['description']);
                    $icon = trim($_POST['icon']);
                    $image_url = trim($_POST['image_url']);
                    $is_active = isset($_POST['is_active']) ? 1 : 0;
                    
                    if (empty($name)) {
                        $message = 'Kategori adı boş olamaz!';
                        $messageType = 'error';
                    } else {
                        try {
                            // Slug otomatik oluştur
                            if (empty($slug)) {
                                $slug = strtolower(str_replace([' ', 'ç', 'ğ', 'ı', 'ö', 'ş', 'ü'], ['-', 'c', 'g', 'i', 'o', 's', 'u'], $name));
                                $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
                                $slug = preg_replace('/-+/', '-', $slug);
                                $slug = trim($slug, '-');
                            }
                            
                            $stmt = $db->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, icon = ?, image_url = ?, is_active = ? WHERE id = ?");
                            $stmt->execute([$name, $slug, $description, $icon, $image_url, $is_active, $id]);
                            
                            $message = 'Kategori başarıyla güncellendi!';
                            $messageType = 'success';
                        } catch (PDOException $e) {
                            if ($e->getCode() == 23000) {
                                $message = 'Bu kategori adı veya slug zaten mevcut!';
                            } else {
                                $message = 'Kategori güncellenirken hata oluştu: ' . $e->getMessage();
                            }
                            $messageType = 'error';
                        }
                    }
                    break;
                    
                case 'delete':
                    $id = (int)$_POST['id'];
                    
                    try {
                        // Önce bu kategoriye ait hesap var mı kontrol et
                        $stmt = $db->prepare("SELECT COUNT(*) FROM accounts WHERE category_id = ?");
                        $stmt->execute([$id]);
                        $accountCount = $stmt->fetchColumn();
                        
                        if ($accountCount > 0) {
                            $message = 'Bu kategoriye ait ' . $accountCount . ' hesap bulunuyor. Önce hesapları başka kategoriye taşıyın!';
                            $messageType = 'error';
                        } else {
                            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
                            $stmt->execute([$id]);
                            
                            $message = 'Kategori başarıyla silindi!';
                            $messageType = 'success';
                        }
                    } catch (PDOException $e) {
                        $message = 'Kategori silinirken hata oluştu: ' . $e->getMessage();
                        $messageType = 'error';
                    }
                    break;
            }
        }
    }
}

// Kategorileri getir
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT c.*, COUNT(a.id) as account_count, COALESCE(c.is_active, 1) as is_active FROM categories c LEFT JOIN accounts a ON c.id = a.category_id GROUP BY c.id ORDER BY c.name ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
    $message = 'Kategoriler yüklenirken hata oluştu: ' . $e->getMessage();
    $messageType = 'error';
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Yönetimi - Admin Panel</title>
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

        /* Content Card */
        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 8px 32px var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
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

        .btn-outline-primary {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline-primary:hover {
            background: var(--primary);
            color: white;
        }

        .btn-outline-danger {
            background: transparent;
            color: var(--danger);
            border: 2px solid var(--danger);
        }

        .btn-outline-danger:hover {
            background: var(--danger);
            color: white;
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
            border-radius: 12px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .table th {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .table th:first-child {
            border-top-left-radius: 12px;
        }

        .table th:last-child {
            border-top-right-radius: 12px;
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            font-size: 0.875rem;
        }

        .table tbody tr:hover {
            background: #f8fafc;
        }

        /* Status badges */
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .bg-success {
            background: rgba(74, 222, 128, 0.1) !important;
            color: var(--success) !important;
        }

        .bg-secondary {
            background: rgba(148, 163, 184, 0.1) !important;
            color: #64748b !important;
        }

        .bg-info {
            background: rgba(59, 130, 246, 0.1) !important;
            color: var(--info) !important;
        }

        .category-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 4px 12px var(--shadow);
        }

        /* Alert */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            border: none;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(74, 222, 128, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .alert-dismissible .btn-close {
            position: absolute;
            top: 0;
            right: 0;
            z-index: 2;
            padding: 1.25rem 1rem;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-dialog {
            max-width: 500px;
            width: 90%;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px var(--shadow);
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .btn-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            padding: 1rem 2rem 2rem;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        /* Form */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
        }

        .form-control {
            width: 100%;
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

        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
        }

        .form-check-label {
            font-weight: 500;
            color: #374151;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .main-content {
                padding: 1rem;
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
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="main-header">
                <h1 class="page-title"><i class="fas fa-tags" style="margin-right: 1rem;"></i>Kategori Yönetimi</h1>
                <button class="btn btn-primary" onclick="showModal('addCategoryModal')">
                    <i class="fas fa-plus"></i>Yeni Kategori
                </button>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?>" id="alertMessage">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" onclick="closeAlert()">×</button>
                </div>
            <?php endif; ?>
            
            <div class="content-card">
                <div class="table-responsive">
                    <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Resim</th>
                                        <th>Ad</th>
                                        <th>Slug</th>
                                        <th>Açıklama</th>
                                        <th>İkon</th>
                                        <th>Hesap Sayısı</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><?php echo $category['id']; ?></td>
                                            <td>
                                                <?php if (!empty($category['image_url'])): ?>
                                                    <img src="<?php echo htmlspecialchars($category['image_url']); ?>" 
                                                         alt="<?php echo htmlspecialchars($category['name']); ?>" 
                                                         class="category-image">
                                                <?php else: ?>
                                                    <div class="category-image bg-light d-flex align-items-center justify-content-center">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td><code><?php echo htmlspecialchars($category['slug']); ?></code></td>
                                            <td><?php echo htmlspecialchars(substr($category['description'] ?? '', 0, 50)) . (strlen($category['description'] ?? '') > 50 ? '...' : ''); ?></td>
                                            <td>
                                                <?php if (!empty($category['icon'])): ?>
                                                    <i class="<?php echo htmlspecialchars($category['icon']); ?>"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $category['account_count']; ?></span>
                                            </td>
                                            <td>
                                                <?php if (($category['is_active'] ?? 0) == 1): ?>
                                                    <span class="badge bg-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Pasif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary me-1" 
                                                        onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Kategori Ekleme Modal -->
    <div class="modal" id="addCategoryModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Yeni Kategori Ekle</h5>
                    <button type="button" class="btn-close" onclick="hideModal('addCategoryModal')">×</button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-group">
                            <label for="name" class="form-label">Kategori Adı *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="slug" class="form-label">Slug (Boş bırakılırsa otomatik oluşturulur)</label>
                            <input type="text" class="form-control" id="slug" name="slug">
                        </div>
                        
                        <div class="form-group">
                            <label for="description" class="form-label">Açıklama</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="icon" class="form-label">İkon (Font Awesome sınıfı)</label>
                            <input type="text" class="form-control" id="icon" name="icon" placeholder="fas fa-gamepad">
                        </div>
                        
                        <div class="form-group">
                            <label for="image_url" class="form-label">Resim URL'si</label>
                            <input type="url" class="form-control" id="image_url" name="image_url" placeholder="https://example.com/image.jpg">
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">Aktif</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger" onclick="hideModal('addCategoryModal')">İptal</button>
                        <button type="submit" class="btn btn-primary">Ekle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Kategori Düzenleme Modal -->
    <div class="modal" id="editCategoryModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Kategori Düzenle</h5>
                    <button type="button" class="btn-close" onclick="hideModal('editCategoryModal')">×</button>
                </div>
                <form method="POST" id="editCategoryForm">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="form-group">
                            <label for="edit_name" class="form-label">Kategori Adı *</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_slug" class="form-label">Slug</label>
                            <input type="text" class="form-control" id="edit_slug" name="slug">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_description" class="form-label">Açıklama</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_icon" class="form-label">İkon (Font Awesome sınıfı)</label>
                            <input type="text" class="form-control" id="edit_icon" name="icon" placeholder="fas fa-gamepad">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_image_url" class="form-label">Resim URL'si</label>
                            <input type="url" class="form-control" id="edit_image_url" name="image_url" placeholder="https://example.com/image.jpg">
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="edit_is_active" name="is_active">
                            <label class="form-check-label" for="edit_is_active">Aktif</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger" onclick="hideModal('editCategoryModal')">İptal</button>
                        <button type="submit" class="btn btn-primary">Güncelle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Silme Onay Modal -->
    <div class="modal" id="deleteCategoryModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Kategori Sil</h5>
                    <button type="button" class="btn-close" onclick="hideModal('deleteCategoryModal')">×</button>
                </div>
                <form method="POST" id="deleteCategoryForm">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_id">
                        
                        <p>Bu kategoriyi silmek istediğinizden emin misiniz?</p>
                        <p><strong id="delete_category_name"></strong></p>
                        <p class="text-danger"><small>Bu işlem geri alınamaz!</small></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger" onclick="hideModal('deleteCategoryModal')">İptal</button>
                        <button type="submit" class="btn btn-danger">Sil</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Modal functions
        function showModal(modalId) {
            document.getElementById(modalId).classList.add('show');
        }
        
        function hideModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
        
        // Alert functions
        function closeAlert() {
            const alert = document.getElementById('alertMessage');
            if (alert) {
                alert.style.display = 'none';
            }
        }
        
        // Auto hide alert after 5 seconds
        setTimeout(function() {
            closeAlert();
        }, 5000);
        
        function editCategory(category) {
            document.getElementById('edit_id').value = category.id;
            document.getElementById('edit_name').value = category.name;
            document.getElementById('edit_slug').value = category.slug;
            document.getElementById('edit_description').value = category.description || '';
            document.getElementById('edit_icon').value = category.icon || '';
            document.getElementById('edit_image_url').value = category.image_url || '';
            document.getElementById('edit_is_active').checked = category.is_active == 1;
            
            showModal('editCategoryModal');
        }
        
        function deleteCategory(id, name) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_category_name').textContent = name;
            
            showModal('deleteCategoryModal');
        }
    </script>
</body>
</html>
<?php
// Admin authentication
require_once 'auth_header.php';

$pageTitle = 'Yasal Sayfalar';

// POST işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'get_page':
                $pageType = $_POST['page_type'] ?? '';
                
                $stmt = $pdo->prepare("SELECT * FROM legal_pages WHERE page_type = ?");
                $stmt->execute([$pageType]);
                $page = $stmt->fetch();
                
                if ($page) {
                    echo json_encode(['success' => true, 'page' => $page]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Sayfa bulunamadı']);
                }
                exit;
                
            case 'save_page':
                $pageType = $_POST['page_type'] ?? '';
                $title = trim($_POST['title'] ?? '');
                $content = $_POST['content'] ?? '';
                $metaDescription = trim($_POST['meta_description'] ?? '');
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                
                if (empty($pageType) || empty($title) || empty($content)) {
                    throw new Exception('Tüm zorunlu alanları doldurun');
                }
                
                // Sayfa var mı kontrol et
                $stmt = $pdo->prepare("SELECT id FROM legal_pages WHERE page_type = ?");
                $stmt->execute([$pageType]);
                $existingPage = $stmt->fetch();
                
                if ($existingPage) {
                    // Güncelle
                    $stmt = $pdo->prepare("
                        UPDATE legal_pages 
                        SET title = ?, content = ?, meta_description = ?, is_active = ?
                        WHERE page_type = ?
                    ");
                    $stmt->execute([$title, $content, $metaDescription, $isActive, $pageType]);
                    $message = 'Sayfa başarıyla güncellendi';
                } else {
                    // Yeni ekle
                    $stmt = $pdo->prepare("
                        INSERT INTO legal_pages (page_type, title, content, meta_description, is_active)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$pageType, $title, $content, $metaDescription, $isActive]);
                    $message = 'Sayfa başarıyla eklendi';
                }
                
                echo json_encode(['success' => true, 'message' => $message]);
                exit;
                
            case 'delete_page':
                $pageType = $_POST['page_type'] ?? '';
                
                if (empty($pageType)) {
                    throw new Exception('Geçersiz sayfa tipi');
                }
                
                $stmt = $pdo->prepare("DELETE FROM legal_pages WHERE page_type = ?");
                $stmt->execute([$pageType]);
                
                echo json_encode(['success' => true, 'message' => 'Sayfa silindi']);
                exit;
                
            default:
                throw new Exception('Geçersiz işlem');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Mevcut sayfaları al
$stmt = $pdo->prepare("SELECT * FROM legal_pages ORDER BY page_type");
$stmt->execute();
$pages = $stmt->fetchAll();

// Sayfa tipleri
$pageTypes = [
    'kvvk' => 'KVKK Aydınlatma Metni',
    'privacy' => 'Gizlilik Politikası',
    'terms' => 'Kullanım Şartları',
    'cookies' => 'Çerez Politikası',
    'refund' => 'İade ve İptal Politikası',
    'about' => 'Hakkımızda'
];
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- CKEditor CDN -->
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.0/classic/ckeditor.js"></script>
    
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

        /* Sidebar (same as dashboard) */
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
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px var(--shadow);
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
            color: #64748b;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.125rem;
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

        /* Pages Grid */
        .pages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .page-card {
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .page-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--shadow);
            border-color: var(--primary);
        }

        .page-card.active {
            border-color: var(--success);
            background: rgba(74, 222, 128, 0.05);
        }

        .page-card.inactive {
            border-color: var(--danger);
            background: rgba(239, 68, 68, 0.05);
            opacity: 0.7;
        }

        .page-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .page-status.active {
            background: rgba(74, 222, 128, 0.1);
            color: var(--success);
        }

        .page-status.inactive {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .page-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .page-card p {
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .page-actions {
            display: flex;
            gap: 0.5rem;
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

        /* Form */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
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

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: auto;
        }

        /* Toast */
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

        /* CKEditor overrides */
        .ck-editor__editable {
            border-radius: 12px !important;
            border: 2px solid var(--border) !important;
            min-height: 300px !important;
        }

        .ck-editor__editable:focus {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
        }

        .ck.ck-editor {
            border-radius: 12px !important;
        }

        .ck.ck-toolbar {
            border-radius: 12px 12px 0 0 !important;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 240px;
            }
            
            .main-content {
                margin-left: 240px;
            }
        }

        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                position: static;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .pages-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
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
                <h1 class="page-title"><?= $pageTitle ?></h1>
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                        <div style="color: #64748b; font-size: 0.875rem;">Admin</div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="content-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2>Yasal Sayfalar Yönetimi</h2>
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i class="fas fa-plus"></i>
                        Yeni Sayfa
                    </button>
                </div>

                <!-- Pages Grid -->
                <div class="pages-grid">
                    <?php foreach ($pageTypes as $type => $name): ?>
                        <?php 
                        $existingPage = null;
                        foreach ($pages as $page) {
                            if ($page['page_type'] === $type) {
                                $existingPage = $page;
                                break;
                            }
                        }
                        ?>
                        <div class="page-card <?= $existingPage ? ($existingPage['is_active'] ? 'active' : 'inactive') : '' ?>">
                            <?php if ($existingPage): ?>
                                <div class="page-status <?= $existingPage['is_active'] ? 'active' : 'inactive' ?>">
                                    <?= $existingPage['is_active'] ? 'Aktif' : 'Pasif' ?>
                                </div>
                            <?php endif; ?>
                            
                            <h3><?= htmlspecialchars($name) ?></h3>
                            <p>
                                <?php if ($existingPage): ?>
                                    Son güncelleme: <?= date('d.m.Y H:i', strtotime($existingPage['updated_at'])) ?>
                                <?php else: ?>
                                    Henüz oluşturulmamış
                                <?php endif; ?>
                            </p>
                            
                            <div class="page-actions">
                                <?php if ($existingPage): ?>
                                    <button class="btn btn-warning btn-sm" onclick="editPage('<?= $type ?>')">
                                        <i class="fas fa-edit"></i>
                                        Düzenle
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deletePage('<?= $type ?>')">
                                        <i class="fas fa-trash"></i>
                                        Sil
                                    </button>
                                    <a href="../<?= $type ?>.php" target="_blank" class="btn btn-primary btn-sm">
                                        <i class="fas fa-external-link-alt"></i>
                                        Görüntüle
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-success btn-sm" onclick="createPage('<?= $type ?>')">
                                        <i class="fas fa-plus"></i>
                                        Oluştur
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Edit Modal -->
    <div id="pageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Sayfa Düzenle</h3>
                <button type="button" class="close" onclick="closeModal()">&times;</button>
            </div>
            <form id="pageForm">
                <input type="hidden" id="pageType" name="page_type">
                
                <div class="form-group">
                    <label for="pageTitle">Sayfa Başlığı *</label>
                    <input type="text" class="form-control" id="pageTitle" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="metaDescription">Meta Açıklama</label>
                    <input type="text" class="form-control" id="metaDescription" name="meta_description" 
                           placeholder="SEO için kısa açıklama (160 karakter)">
                </div>
                
                <div class="form-group">
                    <label for="pageContent">İçerik *</label>
                    <textarea class="form-control" id="pageContent" name="content" rows="20"></textarea>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="isActive" name="is_active" checked>
                        <label for="isActive">Sayfa aktif</label>
                    </div>
                </div>
                
                <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="closeModal()" style="background: #6b7280; color: white;">
                        İptal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // CKEditor global değişkeni
        let ckEditorInstance = null;
        
        const pageTypes = <?= json_encode($pageTypes) ?>;
        
        // CKEditor'ü başlat
        function initCKEditor() {
            if (ckEditorInstance) {
                return Promise.resolve(ckEditorInstance);
            }
            
            return ClassicEditor
                .create(document.querySelector('#pageContent'), {
                    toolbar: {
                        items: [
                            'heading', '|',
                            'bold', 'italic', 'link', '|',
                            'bulletedList', 'numberedList', '|',
                            'outdent', 'indent', '|',
                            'blockQuote', 'insertTable', '|',
                            'undo', 'redo'
                        ]
                    },
                    language: 'tr'
                })
                .then(editor => {
                    ckEditorInstance = editor;
                    console.log('CKEditor başarıyla başlatıldı:', editor);
                    return editor;
                })
                .catch(error => {
                    console.error('CKEditor başlatılamadı:', error);
                    throw error;
                });
        }
        
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Yeni Sayfa Ekle';
            document.getElementById('pageForm').reset();
            document.getElementById('pageType').value = '';
            
            // Modal'ı göster ve CKEditor'ü başlat
            document.getElementById('pageModal').style.display = 'block';
            
            // Kısa bir gecikme ile CKEditor'ü başlat
            setTimeout(() => {
                initCKEditor().then(editor => {
                    editor.setData('');
                }).catch(error => {
                    console.error('CKEditor başlatılamadı:', error);
                });
            }, 100);
        }
        
        function editPage(pageType) {
            document.getElementById('modalTitle').textContent = 'Sayfa Düzenle - ' + pageTypes[pageType];
            
            // Modal'ı göster
            document.getElementById('pageModal').style.display = 'block';
            
            // CKEditor'ü başlat ve sonra veri yükle
            setTimeout(() => {
                initCKEditor().then(editor => {
                    // Sayfa verilerini al
                    fetch('legal-pages.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=get_page&page_type=${pageType}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const page = data.page;
                            document.getElementById('pageType').value = page.page_type;
                            document.getElementById('pageTitle').value = page.title;
                            document.getElementById('metaDescription').value = page.meta_description || '';
                            document.getElementById('isActive').checked = page.is_active == 1;
                            editor.setData(page.content || '');
                        } else {
                            showToast(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Veri yüklenirken hata:', error);
                        showToast('Bir hata oluştu', 'error');
                    });
                }).catch(error => {
                    console.error('CKEditor başlatılamadı:', error);
                    showToast('Editor başlatılamadı', 'error');
                });
            }, 100);
        }
        
        function createPage(pageType) {
            document.getElementById('modalTitle').textContent = 'Yeni Sayfa Oluştur - ' + pageTypes[pageType];
            document.getElementById('pageForm').reset();
            document.getElementById('pageType').value = pageType;
            document.getElementById('pageTitle').value = pageTypes[pageType];
            
            // Modal'ı göster ve CKEditor'ü başlat
            document.getElementById('pageModal').style.display = 'block';
            
            setTimeout(() => {
                initCKEditor().then(editor => {
                    editor.setData('');
                }).catch(error => {
                    console.error('CKEditor başlatılamadı:', error);
                });
            }, 100);
        }
        
        function closeModal() {
            document.getElementById('pageModal').style.display = 'none';
            
            // CKEditor'ü temizle
            if (ckEditorInstance) {
                ckEditorInstance.destroy()
                    .then(() => {
                        ckEditorInstance = null;
                        console.log('CKEditor temizlendi');
                    })
                    .catch(error => {
                        console.error('CKEditor temizlenirken hata:', error);
                    });
            }
        }
        
        function deletePage(pageType) {
            if (confirm(`${pageTypes[pageType]} sayfasını silmek istediğinizden emin misiniz?`)) {
                fetch('legal-pages.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete_page&page_type=${pageType}`
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
        
        // Form submit
        document.getElementById('pageForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!ckEditorInstance) {
                showToast('Editor henüz hazır değil', 'error');
                return;
            }
            
            const formData = new FormData(this);
            formData.append('action', 'save_page');
            formData.set('content', ckEditorInstance.getData());
            
            fetch('legal-pages.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    closeModal();
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                }
            })
            .catch(error => {
                console.error('Form gönderilirken hata:', error);
                showToast('Bir hata oluştu', 'error');
            });
        });
        
        // Toast notification
        function showToast(message, type = 'success') {
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
        
        // Modal kapatma
        window.addEventListener('click', function(e) {
            const modal = document.getElementById('pageModal');
            if (e.target === modal) {
                closeModal();
            }
        });
    </script>
</body>
</html>

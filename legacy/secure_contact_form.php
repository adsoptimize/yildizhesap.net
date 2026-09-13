<?php
/**
 * Secure Contact Form Example
 * CSRF korumalı güvenli iletişim formu örneği
 */

require_once 'config.php';

$page_title = "Güvenli İletişim Formu";
$success_message = '';
$error_message = '';

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting kontrolü
    if (!check_form_rate_limit('contact_form_' . $_SERVER['REMOTE_ADDR'], 5, 300)) {
        secure_error_response('Çok fazla form gönderim denemesi. Lütfen 5 dakika bekleyin.', 429);
    }

    // CSRF token kontrolü
    require_csrf_token();
    
    // Honeypot kontrolü (bot koruması)
    if (!validate_honeypot()) {
        secure_error_response('Bot aktivitesi tespit edildi.', 403);
    }
    
    // Form verilerini al ve sanitize et
    $name = XSSProtection::sanitizeInput($_POST['name'] ?? '');
    $email = XSSProtection::sanitizeInput($_POST['email'] ?? '');
    $subject = XSSProtection::sanitizeInput($_POST['subject'] ?? '');
    $message = XSSProtection::sanitizeInput($_POST['message'] ?? '');
    
    // Validasyon
    $errors = [];
    
    if (empty($name) || strlen($name) < 2) {
        $errors[] = 'İsim en az 2 karakter olmalıdır.';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir email adresi giriniz.';
    }
    
    if (empty($subject) || strlen($subject) < 3) {
        $errors[] = 'Konu en az 3 karakter olmalıdır.';
    }
    
    if (empty($message) || strlen($message) < 10) {
        $errors[] = 'Mesaj en az 10 karakter olmalıdır.';
    }
    
    if (!empty($errors)) {
        $error_message = implode('<br>', $errors);
    } else {
        try {
            // Veritabanına kaydet
            $stmt = $pdo->prepare("
                INSERT INTO contact_messages (name, email, subject, message, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $name,
                $email, 
                $subject,
                $message,
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            $success_message = 'Mesajınız başarıyla gönderildi. En kısa sürede size dönüş yapacağız.';
            
            // Formu temizle
            $_POST = [];
            
        } catch (Exception $e) {
            error_log('Contact form error: ' . $e->getMessage());
            $error_message = 'Mesaj gönderilirken bir hata oluştu. Lütfen tekrar deneyin.';
        }
    }
}

include 'header.php';
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-envelope"></i> Güvenli İletişim Formu</h3>
                </div>
                <div class="card-body">
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php safe_echo($success_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> <?php safe_echo($error_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-shield-alt"></i> Bu form CSRF koruması, XSS önleme, rate limiting ve bot koruması ile güvenlidir.
                    </div>
                    
                    <?php secure_form_start('', 'POST', 'class="needs-validation"'); ?>
                        
                        <!-- Honeypot field (bot protection) -->
                        <?php echo honeypot_field(); ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">İsim <span class="text-danger">*</span></label>
                                <?php secure_input('text', 'name', $_POST['name'] ?? '', 'class="form-control" id="name" required minlength="2" maxlength="100"'); ?>
                                <div class="invalid-feedback">
                                    Lütfen isminizi giriniz (en az 2 karakter).
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <?php secure_input('email', 'email', $_POST['email'] ?? '', 'class="form-control" id="email" required maxlength="255"'); ?>
                                <div class="invalid-feedback">
                                    Lütfen geçerli bir email adresi giriniz.
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Konu <span class="text-danger">*</span></label>
                            <?php secure_input('text', 'subject', $_POST['subject'] ?? '', 'class="form-control" id="subject" required minlength="3" maxlength="200"'); ?>
                            <div class="invalid-feedback">
                                Lütfen konuyu giriniz (en az 3 karakter).
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Mesaj <span class="text-danger">*</span></label>
                            <?php secure_textarea('message', $_POST['message'] ?? '', 'class="form-control" id="message" rows="5" required minlength="10" maxlength="2000"'); ?>
                            <div class="invalid-feedback">
                                Lütfen mesajınızı giriniz (en az 10 karakter).
                            </div>
                            <div class="form-text">
                                Kalan karakter: <span id="charCount">2000</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="privacy" required>
                                <label class="form-check-label" for="privacy">
                                    <a href="/privacy.php" target="_blank">Gizlilik Politikası</a>'nı okudum ve kabul ediyorum. <span class="text-danger">*</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane"></i> Mesajı Gönder
                            </button>
                        </div>
                        
                    <?php secure_form_end(); ?>
                    
                </div>
            </div>
            
            <!-- Güvenlik bilgileri -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Güvenlik Özellikleri</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> CSRF Token Koruması</li>
                                <li><i class="fas fa-check text-success"></i> XSS Önleme</li>
                                <li><i class="fas fa-check text-success"></i> Input Sanitizasyonu</li>
                                <li><i class="fas fa-check text-success"></i> SQL Injection Koruması</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> Rate Limiting</li>
                                <li><i class="fas fa-check text-success"></i> Bot Koruması (Honeypot)</li>
                                <li><i class="fas fa-check text-success"></i> Session Hijacking Koruması</li>
                                <li><i class="fas fa-check text-success"></i> Güvenli Header'lar</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="<?php echo safe_attr($cspNonce); ?>">
// Bootstrap form validation
(function() {
    'use strict';
    
    // Karakter sayacı
    const messageField = document.getElementById('message');
    const charCount = document.getElementById('charCount');
    
    if (messageField && charCount) {
        messageField.addEventListener('input', function() {
            const remaining = 2000 - this.value.length;
            charCount.textContent = remaining;
            
            if (remaining < 100) {
                charCount.className = 'text-warning';
            } else if (remaining < 50) {
                charCount.className = 'text-danger';
            } else {
                charCount.className = '';
            }
        });
    }
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php include 'footer.php'; ?>

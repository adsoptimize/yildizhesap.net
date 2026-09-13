<?php
/**
 * Form Security Helper
 * CSRF token ve form güvenlik fonksiyonları
 */

// CSRF Token output helper
function csrf_token_field() {
    $token = SessionSecurity::generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . safe_attr($token) . '">';
}

// Form başlangıç helper'ı
function secure_form_start($action = '', $method = 'POST', $attributes = '') {
    $method = strtoupper($method);
    $actionAttr = $action ? 'action="' . safe_attr($action) . '"' : '';
    
    echo '<form ' . $actionAttr . ' method="' . safe_attr($method) . '" ' . $attributes . '>';
    
    // CSRF token'ı otomatik ekle (POST istekleri için)
    if ($method === 'POST') {
        echo csrf_token_field();
    }
}

// Form bitiş helper'ı
function secure_form_end() {
    echo '</form>';
}

// Input helper'ları
function secure_input($type, $name, $value = '', $attributes = '') {
    echo '<input type="' . safe_attr($type) . '" name="' . safe_attr($name) . '" value="' . safe_attr($value) . '" ' . $attributes . '>';
}

function secure_textarea($name, $value = '', $attributes = '') {
    echo '<textarea name="' . safe_attr($name) . '" ' . $attributes . '>' . safe_echo($value) . '</textarea>';
}

function secure_select_start($name, $attributes = '') {
    echo '<select name="' . safe_attr($name) . '" ' . $attributes . '>';
}

function secure_option($value, $text, $selected = false) {
    $selectedAttr = $selected ? ' selected' : '';
    echo '<option value="' . safe_attr($value) . '"' . $selectedAttr . '>' . safe_echo($text) . '</option>';
}

function secure_select_end() {
    echo '</select>';
}

// Form validasyon helper'ları
function validate_csrf_token($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    }
    
    return SessionSecurity::validateCSRFToken($token);
}

function require_csrf_token() {
    if (!validate_csrf_token()) {
        http_response_code(403);
        die('CSRF token validation failed');
    }
}

// Güvenli redirect
function secure_redirect($url, $statusCode = 302) {
    // URL'yi validate et
    if (!filter_var($url, FILTER_VALIDATE_URL) && !preg_match('/^\/[^\/]/', $url)) {
        $url = '/'; // Güvenli fallback
    }
    
    // Header injection'ı önle
    $url = str_replace(["\r", "\n"], '', $url);
    
    header('Location: ' . $url, true, $statusCode);
    exit;
}

// Error handling
function secure_error_response($message, $statusCode = 400) {
    http_response_code($statusCode);
    
    if ($_SERVER['HTTP_ACCEPT'] && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode(['error' => safe_echo($message)]);
    } else {
        echo '<div class="alert alert-danger">' . safe_echo($message) . '</div>';
    }
    
    exit;
}

// Success response
function secure_success_response($message, $data = null) {
    if ($_SERVER['HTTP_ACCEPT'] && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        header('Content-Type: application/json');
        $response = ['success' => true, 'message' => safe_echo($message)];
        if ($data !== null) {
            $response['data'] = $data;
        }
        echo json_encode($response);
    } else {
        echo '<div class="alert alert-success">' . safe_echo($message) . '</div>';
    }
}

// File upload güvenlik
function validate_uploaded_file($file, $allowedTypes = [], $maxSize = 5242880) { // 5MB default
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['valid' => false, 'error' => 'Invalid file upload'];
    }
    
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['valid' => false, 'error' => 'No file uploaded'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['valid' => false, 'error' => 'File too large'];
        default:
            return ['valid' => false, 'error' => 'Upload error'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'error' => 'File exceeds maximum size'];
    }
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
        return ['valid' => false, 'error' => 'File type not allowed'];
    }
    
    // Tehlikeli dosya uzantılarını kontrol et
    $dangerous_extensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'pl', 'py', 'jsp', 'asp', 'sh', 'cgi'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (in_array($file_extension, $dangerous_extensions)) {
        return ['valid' => false, 'error' => 'Dangerous file type'];
    }
    
    return ['valid' => true, 'file' => $file, 'mime_type' => $mimeType];
}

// Honeypot field (bot protection)
function honeypot_field() {
    return '<input type="text" name="website" value="" style="display:none !important;" tabindex="-1" autocomplete="off">';
}

function validate_honeypot() {
    return empty($_POST['website']);
}

// Rate limiting helper
function check_form_rate_limit($identifier, $maxAttempts = 5, $timeWindow = 300) {
    return SessionSecurity::checkRateLimit($identifier, $maxAttempts, $timeWindow);
}
?>

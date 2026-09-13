<?php
/**
 * XSS Protection Class
 * Tüm XSS saldırılarına karşı kapsamlı koruma sağlar
 */
class XSSProtection {
    
    /**
     * HTML output için güvenli sanitizasyon
     */
    public static function sanitizeOutput($input, $context = 'html') {
        if (is_null($input)) {
            return '';
        }
        
        switch ($context) {
            case 'html':
                return self::sanitizeHTML($input);
            case 'html_attr':
                return self::sanitizeHTMLAttribute($input);
            case 'js':
                return self::sanitizeJavaScript($input);
            case 'css':
                return self::sanitizeCSS($input);
            case 'url':
                return self::sanitizeURL($input);
            case 'email':
                return self::sanitizeEmail($input);
            default:
                return self::sanitizeHTML($input);
        }
    }
    
    /**
     * HTML içeriği için güvenli sanitizasyon
     */
    private static function sanitizeHTML($input) {
        // Null kontrolü
        if (is_null($input)) {
            return '';
        }
        
        // Array kontrolü
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeHTML'], $input);
        }
        
        // String'e çevir
        $input = (string) $input;
        
        // HTML karakterleri encode et
        $output = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8', false);
        
        // Ekstra XSS koruması - tehlikeli pattern'leri temizle
        $dangerousPatterns = [
            '/javascript:/i',
            '/vbscript:/i',
            '/data:/i',
            '/on\w+\s*=/i', // onclick, onload vs.
            '/expression\s*\(/i',
            '/url\s*\(/i',
            '/<script[\s\S]*?\/script>/i',
            '/<iframe[\s\S]*?\/iframe>/i',
            '/<object[\s\S]*?\/object>/i',
            '/<embed[\s\S]*?\/embed>/i',
            '/<form[\s\S]*?\/form>/i',
            '/<input[\s\S]*?>/i',
            '/<textarea[\s\S]*?\/textarea>/i',
            '/<select[\s\S]*?\/select>/i',
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            $output = preg_replace($pattern, '', $output);
        }
        
        return $output;
    }
    
    /**
     * HTML attribute'ları için güvenli sanitizasyon
     */
    private static function sanitizeHTMLAttribute($input) {
        if (is_null($input)) {
            return '';
        }
        
        $input = (string) $input;
        
        // HTML attribute encoding
        $output = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8', true);
        
        // Attribute içinde tehlikeli pattern'leri temizle
        $dangerousPatterns = [
            '/javascript:/i',
            '/vbscript:/i',
            '/data:(?!image\/)/i', // image data URL'leri hariç
            '/on\w+/i',
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            $output = preg_replace($pattern, '', $output);
        }
        
        return $output;
    }
    
    /**
     * JavaScript içeriği için güvenli sanitizasyon
     */
    private static function sanitizeJavaScript($input) {
        if (is_null($input)) {
            return '""';
        }
        
        $input = (string) $input;
        
        // JSON encode kullan (JavaScript için güvenli)
        return json_encode($input, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * CSS içeriği için güvenli sanitizasyon
     */
    private static function sanitizeCSS($input) {
        if (is_null($input)) {
            return '';
        }
        
        $input = (string) $input;
        
        // CSS'te tehlikeli pattern'leri temizle
        $dangerousPatterns = [
            '/expression\s*\(/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/data:/i',
            '/url\s*\(/i',
            '/@import/i',
            '/binding:/i',
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            $input = preg_replace($pattern, '', $input);
        }
        
        // Sadece güvenli karakterleri tut
        return preg_replace('/[^a-zA-Z0-9\s\-_#.,;:%()]/', '', $input);
    }
    
    /**
     * URL için güvenli sanitizasyon
     */
    private static function sanitizeURL($input) {
        if (is_null($input)) {
            return '';
        }
        
        $input = (string) $input;
        
        // URL'yi doğrula ve temizle
        $url = filter_var($input, FILTER_SANITIZE_URL);
        
        // Tehlikeli protokolleri engelle
        $dangerousProtocols = ['javascript:', 'vbscript:', 'data:', 'file:', 'ftp:'];
        
        foreach ($dangerousProtocols as $protocol) {
            if (stripos($url, $protocol) === 0) {
                return '#';
            }
        }
        
        // URL'yi doğrula
        if (!filter_var($url, FILTER_VALIDATE_URL) && !preg_match('/^\//', $url)) {
            return '#';
        }
        
        return $url;
    }
    
    /**
     * Email için güvenli sanitizasyon
     */
    private static function sanitizeEmail($input) {
        if (is_null($input)) {
            return '';
        }
        
        $input = (string) $input;
        
        // Email'i sanitize et
        $email = filter_var($input, FILTER_SANITIZE_EMAIL);
        
        // Email'i doğrula
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '';
        }
        
        return $email;
    }
    
    /**
     * Form input'ları için güvenli sanitizasyon
     */
    public static function sanitizeInput($input, $type = 'string') {
        if (is_null($input)) {
            return null;
        }
        
        switch ($type) {
            case 'int':
                return filter_var($input, FILTER_VALIDATE_INT);
            case 'float':
                return filter_var($input, FILTER_VALIDATE_FLOAT);
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL);
            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL);
            case 'string':
            default:
                if (is_array($input)) {
                    return array_map([self::class, 'sanitizeInput'], $input);
                }
                
                // String olarak sanitize et
                $input = trim((string) $input);
                
                // Null bytes'ları temizle
                $input = str_replace("\0", '', $input);
                
                // Control karakterleri temizle (tab, newline hariç)
                $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
                
                return $input;
        }
    }
    
    /**
     * SQL Injection koruması için input sanitizasyonu
     */
    public static function sanitizeForSQL($input) {
        if (is_null($input)) {
            return null;
        }
        
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeForSQL'], $input);
        }
        
        // String'e çevir ve trim
        $input = trim((string) $input);
        
        // Null bytes'ları temizle
        $input = str_replace("\0", '', $input);
        
        // SQL injection pattern'lerini temizle
        $dangerousPatterns = [
            '/(\s*(union|select|insert|update|delete|drop|create|alter|exec|execute)\s+)/i',
            '/(\s*(or|and)\s+[\'"]*\d[\'"]*\s*[=<>]+\s*[\'"]*\d[\'"]*)/i',
            '/[\'"]\s*(or|and)\s+[\'"]/i',
            '/;\s*(drop|delete|update|insert)/i',
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            $input = preg_replace($pattern, '', $input);
        }
        
        return $input;
    }
    
    /**
     * Dosya yolu için güvenli sanitizasyon
     */
    public static function sanitizeFilePath($input) {
        if (is_null($input)) {
            return '';
        }
        
        $input = (string) $input;
        
        // Tehlikeli karakterleri temizle
        $input = str_replace(['../', '.\\', '..\\', '../'], '', $input);
        $input = preg_replace('/[^a-zA-Z0-9\-_\.\/]/', '', $input);
        
        // Dosya uzantısını kontrol et
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'doc', 'docx'];
        $extension = strtolower(pathinfo($input, PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedExtensions)) {
            return '';
        }
        
        return $input;
    }
    
    /**
     * Tüm $_POST verilerini güvenli şekilde sanitize et
     */
    public static function sanitizeAllPOST() {
        foreach ($_POST as $key => $value) {
            $_POST[$key] = self::sanitizeInput($value);
        }
    }
    
    /**
     * Tüm $_GET verilerini güvenli şekilde sanitize et
     */
    public static function sanitizeAllGET() {
        foreach ($_GET as $key => $value) {
            $_GET[$key] = self::sanitizeInput($value);
        }
    }
    
    /**
     * Content Security Policy nonce oluştur
     */
    public static function generateCSPNonce() {
        if (!isset($_SESSION['_csp_nonce'])) {
            $_SESSION['_csp_nonce'] = base64_encode(random_bytes(16));
        }
        return $_SESSION['_csp_nonce'];
    }
}

/**
 * Global XSS koruması için helper fonksiyonlar
 */

// HTML output için güvenli echo
function safe_echo($input, $context = 'html') {
    echo XSSProtection::sanitizeOutput($input, $context);
}

// HTML attribute için güvenli output
function safe_attr($input) {
    echo XSSProtection::sanitizeOutput($input, 'html_attr');
}

// JavaScript için güvenli output
function safe_js($input) {
    echo XSSProtection::sanitizeOutput($input, 'js');
}

// URL için güvenli output
function safe_url($input) {
    echo XSSProtection::sanitizeOutput($input, 'url');
}
?>

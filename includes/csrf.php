<?php
class CSRF {
    private static $token_name = 'csrf_token';
    
    public static function generateToken() {
        if (!isset($_SESSION[self::$token_name])) {
            $_SESSION[self::$token_name] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::$token_name];
    }
    
    public static function getToken() {
        return $_SESSION[self::$token_name] ?? null;
    }
    
    public static function validateToken($token) {
        if (!isset($_SESSION[self::$token_name]) || empty($token)) {
            return false;
        }
        
        return hash_equals($_SESSION[self::$token_name], $token);
    }
    
    public static function getTokenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    public static function getTokenMeta() {
        $token = self::generateToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }
    
    public static function validateRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            
            if (!self::validateToken($token)) {
                http_response_code(403);
                die('CSRF token validation failed');
            }
        }
    }
    
    public static function regenerateToken() {
        $_SESSION[self::$token_name] = bin2hex(random_bytes(32));
        return $_SESSION[self::$token_name];
    }
}
?>
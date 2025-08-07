<?php
define('BASE_URL', 'http://localhost/trade-logger');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_MAX_SIZE', 4 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif']);
define('DATE_FORMAT', 'Y/m/d');
define('TIME_FORMAT', 'H:i');
define('SESSION_LIFETIME', 86400);
define('EMAIL_VERIFICATION_EXPIRY', 86400);
define('PASSWORD_RESET_EXPIRY', 3600);
define('DEFAULT_STRATEGY_LIMIT', 3);

ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
session_set_cookie_params(SESSION_LIFETIME);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../models/' . $class . '.php',
        __DIR__ . '/../includes/' . $class . '.php'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/helpers.php';

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
}

function redirect($url) {
    header('Location: ' . BASE_URL . $url);
    exit;
}

function flashMessage($type, $message) {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function getFlashMessages() {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $db = new Database();
    return $db->fetch(
        "SELECT id, username, email, is_admin, strategy_limit, account_size FROM users WHERE id = ?", 
        [$_SESSION['user_id']]
    );
}

$db = new Database();
?>
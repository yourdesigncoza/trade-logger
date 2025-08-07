<?php
// Enable error logging
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/config_errors.log');

// Create logs directory if it doesn't exist
$logs_dir = __DIR__ . '/../logs';
if (!file_exists($logs_dir)) {
    if (!mkdir($logs_dir, 0755, true)) {
        error_log("Failed to create logs directory: " . $logs_dir);
    }
}

// Define constants with error handling
try {
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
    
    error_log("Config: Constants defined successfully");
} catch (Exception $e) {
    error_log("Config: Error defining constants - " . $e->getMessage());
    // Define minimal fallback constants
    if (!defined('BASE_URL')) define('BASE_URL', '');
    if (!defined('DEFAULT_STRATEGY_LIMIT')) define('DEFAULT_STRATEGY_LIMIT', 3);
}

// Configure session settings with error handling
try {
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    session_set_cookie_params(SESSION_LIFETIME);
    
    if (session_status() === PHP_SESSION_NONE) {
        if (!session_start()) {
            throw new Exception("Failed to start session");
        }
        error_log("Config: Session started successfully");
    }
} catch (Exception $e) {
    error_log("Config: Session error - " . $e->getMessage());
    // Try to start session with minimal configuration
    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    } catch (Exception $e2) {
        error_log("Config: Critical session error - " . $e2->getMessage());
    }
}

// Autoloader with error handling
try {
    spl_autoload_register(function ($class) {
        $paths = [
            __DIR__ . '/../models/' . $class . '.php',
            __DIR__ . '/../includes/' . $class . '.php'
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                try {
                    require_once $path;
                    error_log("Config: Successfully loaded class: " . $class . " from " . $path);
                    return;
                } catch (Exception $e) {
                    error_log("Config: Error loading class file " . $path . " - " . $e->getMessage());
                }
            }
        }
        error_log("Config: Class file not found for: " . $class);
    });
    error_log("Config: Autoloader registered successfully");
} catch (Exception $e) {
    error_log("Config: Failed to register autoloader - " . $e->getMessage());
}

// Include required files with error handling
try {
    if (file_exists(__DIR__ . '/database.php')) {
        require_once __DIR__ . '/database.php';
        error_log("Config: Database class included successfully");
    } else {
        throw new Exception("Database.php file not found");
    }
} catch (Exception $e) {
    error_log("Config: Error including database.php - " . $e->getMessage());
}

try {
    if (file_exists(__DIR__ . '/../includes/helpers.php')) {
        require_once __DIR__ . '/../includes/helpers.php';
        error_log("Config: Helpers included successfully");
    } else {
        throw new Exception("helpers.php file not found");
    }
} catch (Exception $e) {
    error_log("Config: Error including helpers.php - " . $e->getMessage());
}

function requireLogin() {
    try {
        if (!isset($_SESSION['user_id'])) {
            $redirect_url = defined('BASE_URL') ? BASE_URL . '/login.php' : '/login.php';
            header('Location: ' . $redirect_url);
            exit;
        }
    } catch (Exception $e) {
        error_log("requireLogin Error: " . $e->getMessage());
        // Force redirect to login even on error
        header('Location: /login.php');
        exit;
    }
}

function requireAdmin() {
    try {
        requireLogin();
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            $redirect_url = defined('BASE_URL') ? BASE_URL . '/dashboard.php' : '/dashboard.php';
            header('Location: ' . $redirect_url);
            exit;
        }
    } catch (Exception $e) {
        error_log("requireAdmin Error: " . $e->getMessage());
        // Force redirect to dashboard on error
        header('Location: /dashboard.php');
        exit;
    }
}

function redirect($url) {
    try {
        if (empty($url)) {
            error_log("redirect: Empty URL provided");
            $url = '/';
        }
        $full_url = defined('BASE_URL') ? BASE_URL . $url : $url;
        header('Location: ' . $full_url);
        exit;
    } catch (Exception $e) {
        error_log("redirect Error: " . $e->getMessage() . " - URL: " . $url);
        // Fallback redirect
        header('Location: /');
        exit;
    }
}

function flashMessage($type, $message) {
    try {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            error_log("flashMessage: Session not active");
            return false;
        }
        
        if (empty($type) || empty($message)) {
            error_log("flashMessage: Empty type or message provided");
            return false;
        }
        
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
        return true;
    } catch (Exception $e) {
        error_log("flashMessage Error: " . $e->getMessage());
        return false;
    }
}

function getFlashMessages() {
    try {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            error_log("getFlashMessages: Session not active");
            return [];
        }
        
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    } catch (Exception $e) {
        error_log("getFlashMessages Error: " . $e->getMessage());
        return [];
    }
}

function isLoggedIn() {
    try {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    } catch (Exception $e) {
        error_log("isLoggedIn Error: " . $e->getMessage());
        return false;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    try {
        $db = new Database();
        $user = $db->fetch(
            "SELECT id, username, email, is_admin, strategy_limit, account_size FROM users WHERE id = ?", 
            [$_SESSION['user_id']]
        );
        
        if (!$user) {
            error_log("getCurrentUser: User not found for ID: " . $_SESSION['user_id']);
            return null;
        }
        
        return $user;
    } catch (Exception $e) {
        error_log("getCurrentUser Error: " . $e->getMessage());
        // Return a fallback user object to prevent crashes
        return [
            'id' => $_SESSION['user_id'] ?? 0,
            'username' => 'User',
            'email' => '',
            'is_admin' => false,
            'strategy_limit' => DEFAULT_STRATEGY_LIMIT,
            'account_size' => 0
        ];
    }
}

// Initialize global database connection with comprehensive error handling
try {
    $db = new Database();
    error_log("Config: Global database connection established successfully");
} catch (Exception $e) {
    error_log("Config: Failed to initialize global database connection - " . $e->getMessage());
    $db = null;
    
    // Log critical system state
    error_log("Config: System running in degraded mode - database unavailable");
    
    // Create upload directories if they don't exist
    try {
        $upload_dir = __DIR__ . '/../uploads';
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                error_log("Config: Failed to create uploads directory");
            } else {
                error_log("Config: Created uploads directory");
            }
        }
        
        $subdirs = ['trades', 'strategies'];
        foreach ($subdirs as $subdir) {
            $subdir_path = $upload_dir . '/' . $subdir;
            if (!file_exists($subdir_path)) {
                if (!mkdir($subdir_path, 0755, true)) {
                    error_log("Config: Failed to create upload subdirectory: " . $subdir);
                } else {
                    error_log("Config: Created upload subdirectory: " . $subdir);
                }
            }
        }
    } catch (Exception $e2) {
        error_log("Config: Error creating upload directories - " . $e2->getMessage());
    }
}

// Log final configuration status
error_log("Config: Configuration loaded - Database: " . ($db ? "Available" : "Unavailable") . 
          ", Session: " . (session_status() === PHP_SESSION_ACTIVE ? "Active" : "Inactive"));
?>
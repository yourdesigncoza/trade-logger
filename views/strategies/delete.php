<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Strategy.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/views/strategies/');
}

try {
    if (class_exists('CSRF')) {
        CSRF::validateRequest();
    } else {
        // Simple CSRF validation if class is not available
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
            $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid CSRF token');
        }
    }
} catch (Exception $e) {
    error_log("Strategies Delete: CSRF validation error - " . $e->getMessage());
    flashMessage('error', 'Security validation failed.');
    redirect('/views/strategies/');
}

$strategy_model = new Strategy();
$strategy_id = (int)($_POST['id'] ?? 0);

if (!$strategy_id) {
    flashMessage('error', 'Invalid strategy ID');
    redirect('/views/strategies/');
}

try {
    $strategy = $strategy_model->getById($strategy_id, $_SESSION['user_id']);
    
    if (!$strategy) {
        throw new Exception('Strategy not found');
    }
    
    $strategy_name = $strategy['name'];
    $result = $strategy_model->delete($strategy_id, $_SESSION['user_id']);
    
    if ($result) {
        flashMessage('success', 'Strategy "' . $strategy_name . '" has been deleted successfully');
    } else {
        throw new Exception('Failed to delete strategy');
    }
    
} catch (Exception $e) {
    flashMessage('error', $e->getMessage());
}

redirect('/views/strategies/');
?>
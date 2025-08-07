<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Trade.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/views/trades/');
}

CSRF::validateRequest();

$trade_model = new Trade();
$trade_id = (int)($_POST['id'] ?? 0);

if (!$trade_id) {
    flashMessage('error', 'Invalid trade ID');
    redirect('/views/trades/');
}

try {
    $trade = $trade_model->getById($trade_id, $_SESSION['user_id']);
    
    if (!$trade) {
        throw new Exception('Trade not found');
    }
    
    $trade_name = formatDate($trade['date']) . ' ' . $trade['instrument'];
    $result = $trade_model->delete($trade_id, $_SESSION['user_id']);
    
    if ($result) {
        flashMessage('success', 'Trade "' . $trade_name . '" has been deleted successfully');
    } else {
        throw new Exception('Failed to delete trade');
    }
    
} catch (Exception $e) {
    flashMessage('error', $e->getMessage());
}

redirect('/views/trades/');
?>
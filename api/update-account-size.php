<?php
require_once __DIR__ . '/../config/config.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/views/dashboard/analytics.php');
}

CSRF::validateRequest();

$account_size = (float)($_POST['account_size'] ?? 0);

if ($account_size < 0) {
    flashMessage('error', 'Account size cannot be negative');
    redirect('/views/dashboard/analytics.php');
}

try {
    $db->execute(
        "UPDATE users SET account_size = ? WHERE id = ?",
        [$account_size, $_SESSION['user_id']]
    );
    
    flashMessage('success', 'Account size updated successfully');
    
} catch (Exception $e) {
    flashMessage('error', 'Failed to update account size');
}

redirect('/views/dashboard/analytics.php');
?>
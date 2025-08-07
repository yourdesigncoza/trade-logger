<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$auth = new Auth();
$auth->logout();

flashMessage('success', 'You have been logged out successfully');
redirect('/login.php');
?>
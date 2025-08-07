<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

if (isLoggedIn()) {
    redirect('/dashboard.php');
}

$error = null;
$success = null;
$auth = new Auth();

if (isset($_GET['token'])) {
    $token = sanitize($_GET['token']);
    
    try {
        $user = $auth->verifyEmail($token);
        $success = 'Your email has been verified successfully! You can now log in to your account.';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
} else {
    $error = 'No verification token provided.';
}

$page_title = 'Verify Email - Trade Logger';
?>

<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $page_title ?></title>
    
    <link href="<?= BASE_URL ?>/assets/css/theme.css" rel="stylesheet" id="style-default">
    <link href="<?= BASE_URL ?>/assets/css/custom.css" rel="stylesheet">
</head>

<body>
    <main class="main" id="top">
        <div class="container">
            <div class="row flex-center min-vh-100 py-5">
                <div class="col-sm-10 col-md-8 col-lg-5 col-xl-5 col-xxl-3">
                    <div class="d-flex align-items-center fw-bolder fs-5 d-inline-block mb-4">
                        <img src="<?= BASE_URL ?>/assets/img/icons/logo.png" alt="phoenix" width="32">
                        <span class="text-primary ms-2">Trade Logger</span>
                    </div>
                    
                    <div class="text-center mb-7">
                        <h3 class="text-body-highlight">Email Verification</h3>
                        <p class="text-body-tertiary">Verify your email address</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger text-center" role="alert">
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <span class="fas fa-times-circle text-danger fs-1"></span>
                            </div>
                            <h5 class="mb-2">Verification Failed</h5>
                            <p class="mb-3"><?= sanitize($error) ?></p>
                            <div class="d-flex gap-2 justify-content-center">
                                <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/register.php">Register Again</a>
                                <a class="btn btn-primary" href="<?= BASE_URL ?>/login.php">Go to Login</a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success text-center" role="alert">
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <span class="fas fa-check-circle text-success fs-1"></span>
                            </div>
                            <h5 class="mb-2">Email Verified!</h5>
                            <p class="mb-3"><?= sanitize($success) ?></p>
                            <a class="btn btn-primary" href="<?= BASE_URL ?>/login.php">Sign In</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="<?= BASE_URL ?>/assets/js/vendor/popper.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/vendor/bootstrap.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/vendor/anchor.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/vendor/is.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/phoenix.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
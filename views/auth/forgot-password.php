<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

if (isLoggedIn()) {
    redirect('/dashboard.php');
}

$error = null;
$success = null;
$auth = new Auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validateRequest();
    
    try {
        $email = sanitize($_POST['email'] ?? '');
        
        if (empty($email)) {
            throw new Exception('Please enter your email address');
        }
        
        if (!validateEmail($email)) {
            throw new Exception('Please enter a valid email address');
        }
        
        $auth->requestPasswordReset($email);
        $success = 'If an account with that email exists, we\'ve sent password reset instructions.';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$page_title = 'Forgot Password - Trade Logger';
?>

<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $page_title ?></title>
    <?= CSRF::getTokenMeta() ?>
    
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
                        <h3 class="text-body-highlight">Forgot Password</h3>
                        <p class="text-body-tertiary">Enter your email to reset your password</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <div class="d-flex align-items-center">
                                <span class="fas fa-times-circle text-danger fs-5 me-2"></span>
                                <?= sanitize($error) ?>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <div class="d-flex align-items-center">
                                <span class="fas fa-check-circle text-success fs-5 me-2"></span>
                                <?= sanitize($success) ?>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!$success): ?>
                    <form method="POST" data-validate novalidate>
                        <?= CSRF::getTokenField() ?>
                        
                        <div class="mb-3 text-start">
                            <label class="form-label" for="email">Email Address</label>
                            <div class="form-icon-container">
                                <input class="form-control form-icon-input" 
                                       id="email" 
                                       name="email" 
                                       type="email" 
                                       placeholder="name@example.com"
                                       value="<?= sanitize($_POST['email'] ?? '') ?>"
                                       required>
                                <span class="fas fa-envelope text-body fs-9 form-icon"></span>
                                <div class="invalid-feedback">Please enter your email address.</div>
                            </div>
                        </div>
                        
                        <button class="btn btn-primary w-100 mb-3" type="submit">Send Reset Link</button>
                        
                        <div class="text-center">
                            <a class="fs-9 fw-bold" href="<?= BASE_URL ?>/login.php">Back to Login</a>
                        </div>
                    </form>
                    <?php else: ?>
                        <div class="text-center">
                            <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/login.php">Back to Login</a>
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
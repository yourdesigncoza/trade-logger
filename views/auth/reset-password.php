<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

if (isLoggedIn()) {
    redirect('/dashboard.php');
}

$error = null;
$success = null;
$auth = new Auth();
$token = sanitize($_GET['token'] ?? '');

if (empty($token)) {
    $error = 'Invalid reset link. Please request a new password reset.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    CSRF::validateRequest();
    
    try {
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        
        if (empty($password) || empty($password_confirm)) {
            throw new Exception('Please fill in all fields');
        }
        
        if ($password !== $password_confirm) {
            throw new Exception('Passwords do not match');
        }
        
        $auth->resetPassword($token, $password);
        $success = 'Your password has been reset successfully! You can now log in with your new password.';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$page_title = 'Reset Password - Trade Logger';
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
                        <h3 class="text-body-highlight">Reset Password</h3>
                        <p class="text-body-tertiary">Enter your new password</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger text-center" role="alert">
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <span class="fas fa-times-circle text-danger fs-1"></span>
                            </div>
                            <h5 class="mb-2">Reset Failed</h5>
                            <p class="mb-3"><?= sanitize($error) ?></p>
                            <div class="d-flex gap-2 justify-content-center">
                                <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/views/auth/forgot-password.php">Request New Reset</a>
                                <a class="btn btn-primary" href="<?= BASE_URL ?>/login.php">Go to Login</a>
                            </div>
                        </div>
                    <?php elseif ($success): ?>
                        <div class="alert alert-success text-center" role="alert">
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <span class="fas fa-check-circle text-success fs-1"></span>
                            </div>
                            <h5 class="mb-2">Password Reset!</h5>
                            <p class="mb-3"><?= sanitize($success) ?></p>
                            <a class="btn btn-primary" href="<?= BASE_URL ?>/login.php">Sign In</a>
                        </div>
                    <?php else: ?>
                    <form method="POST" data-validate novalidate>
                        <?= CSRF::getTokenField() ?>
                        
                        <div class="mb-3 text-start">
                            <label class="form-label" for="password">New Password</label>
                            <div class="form-icon-container" data-password="data-password">
                                <input class="form-control form-icon-input pe-6" 
                                       id="password" 
                                       name="password" 
                                       type="password" 
                                       placeholder="Enter new password"
                                       pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}"
                                       title="At least 8 characters with uppercase, lowercase, and number"
                                       data-password-input="data-password-input" 
                                       required>
                                <span class="fas fa-key text-body fs-9 form-icon"></span>
                                <button type="button" class="btn px-3 py-0 h-100 position-absolute top-0 end-0 fs-7 text-body-tertiary" data-password-toggle="data-password-toggle">
                                    <span class="uil uil-eye show"></span>
                                    <span class="uil uil-eye-slash hide"></span>
                                </button>
                                <div class="invalid-feedback">Password must be at least 8 characters with uppercase, lowercase, and number.</div>
                            </div>
                        </div>
                        
                        <div class="mb-3 text-start">
                            <label class="form-label" for="password_confirm">Confirm New Password</label>
                            <div class="form-icon-container" data-password="data-password">
                                <input class="form-control form-icon-input pe-6" 
                                       id="password_confirm" 
                                       name="password_confirm" 
                                       type="password" 
                                       placeholder="Confirm new password"
                                       data-password-input="data-password-input" 
                                       required>
                                <span class="fas fa-key text-body fs-9 form-icon"></span>
                                <button type="button" class="btn px-3 py-0 h-100 position-absolute top-0 end-0 fs-7 text-body-tertiary" data-password-toggle="data-password-toggle">
                                    <span class="uil uil-eye show"></span>
                                    <span class="uil uil-eye-slash hide"></span>
                                </button>
                                <div class="invalid-feedback">Please confirm your password.</div>
                            </div>
                        </div>
                        
                        <button class="btn btn-primary w-100 mb-3" type="submit">Reset Password</button>
                        
                        <div class="text-center">
                            <a class="fs-9 fw-bold" href="<?= BASE_URL ?>/login.php">Back to Login</a>
                        </div>
                    </form>
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
    
    <script>
        // Password matching validation
        document.getElementById('password_confirm').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect('/dashboard.php');
}

$error = null;
$auth = new Auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validateRequest();
    
    try {
        $username_or_email = sanitize($_POST['username_or_email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']);
        
        if (empty($username_or_email) || empty($password)) {
            throw new Exception('Please fill in all fields');
        }
        
        $user = $auth->login($username_or_email, $password, $remember_me);
        
        flashMessage('success', 'Welcome back, ' . $user['username'] . '!');
        redirect('/dashboard.php');
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$page_title = 'Login - Trade Logger';
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
                        <h3 class="text-body-highlight">Sign In</h3>
                        <p class="text-body-tertiary">Get access to your account</p>
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
                    
                    <form method="POST" data-validate novalidate>
                        <?= CSRF::getTokenField() ?>
                        
                        <div class="mb-3 text-start">
                            <label class="form-label" for="username_or_email">Username or Email</label>
                            <div class="form-icon-container">
                                <input class="form-control form-icon-input" 
                                       id="username_or_email" 
                                       name="username_or_email" 
                                       type="text" 
                                       placeholder="Enter username or email"
                                       value="<?= sanitize($_POST['username_or_email'] ?? '') ?>"
                                       required>
                                <span class="fas fa-user text-body fs-9 form-icon"></span>
                                <div class="invalid-feedback">Please enter your username or email.</div>
                            </div>
                        </div>
                        
                        <div class="mb-3 text-start">
                            <label class="form-label" for="password">Password</label>
                            <div class="form-icon-container" data-password="data-password">
                                <input class="form-control form-icon-input pe-6" 
                                       id="password" 
                                       name="password" 
                                       type="password" 
                                       placeholder="Enter password"
                                       data-password-input="data-password-input" 
                                       required>
                                <span class="fas fa-key text-body fs-9 form-icon"></span>
                                <button type="button" class="btn px-3 py-0 h-100 position-absolute top-0 end-0 fs-7 text-body-tertiary" data-password-toggle="data-password-toggle">
                                    <span class="uil uil-eye show"></span>
                                    <span class="uil uil-eye-slash hide"></span>
                                </button>
                                <div class="invalid-feedback">Please enter your password.</div>
                            </div>
                        </div>
                        
                        <div class="row flex-between-center mb-7">
                            <div class="col-auto">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" 
                                           id="remember_me" 
                                           name="remember_me" 
                                           type="checkbox"
                                           <?= isset($_POST['remember_me']) ? 'checked' : '' ?>>
                                    <label class="form-check-label mb-0" for="remember_me">Remember me</label>
                                </div>
                            </div>
                            <div class="col-auto">
                                <a class="fs-9 fw-semibold" href="<?= BASE_URL ?>/views/auth/forgot-password.php">Forgot Password?</a>
                            </div>
                        </div>
                        
                        <button class="btn btn-primary w-100 mb-3" type="submit">Sign In</button>
                        
                        <div class="text-center">
                            <a class="fs-9 fw-bold" href="<?= BASE_URL ?>/register.php">Create an account</a>
                        </div>
                    </form>
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
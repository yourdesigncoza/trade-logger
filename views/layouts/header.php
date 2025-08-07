<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $page_title ?? 'Trade Logger' ?></title>
    
    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= BASE_URL ?>/assets/img/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= BASE_URL ?>/assets/img/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= BASE_URL ?>/assets/img/favicons/favicon-16x16.png">
    <link rel="shortcut icon" type="image/x-icon" href="<?= BASE_URL ?>/assets/img/favicons/favicon.ico">
    <meta name="msapplication-TileImage" content="<?= BASE_URL ?>/assets/img/favicons/mstile-150x150.png">
    <meta name="theme-color" content="#ffffff">
    
    <!-- Phoenix CSS -->
    <link href="<?= BASE_URL ?>/assets/css/theme.css" rel="stylesheet" id="style-default">
    <link href="<?= BASE_URL ?>/assets/css/custom.css" rel="stylesheet">
    
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css_file): ?>
            <link href="<?= BASE_URL . $css_file ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <script>
        var phoenixIsRTL = window.config?.isRTL ?? false;
        if (phoenixIsRTL) {
            var linkDefault = document.getElementById('style-default');
            linkDefault.setAttribute('disabled', true);
            document.querySelector('html').setAttribute('dir', 'rtl');
        }
    </script>
</head>

<body>
    <main class="main" id="top">
        <?php if (isLoggedIn()): ?>
            <?php include __DIR__ . '/nav.php'; ?>
        <?php endif; ?>
        
        <div class="content">
            <?php 
            $flash_messages = getFlashMessages();
            if (!empty($flash_messages)): 
            ?>
                <div class="container-fluid">
                    <?php foreach ($flash_messages as $flash): ?>
                        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
                            <?= sanitize($flash['message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
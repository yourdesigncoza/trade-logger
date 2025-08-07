<?php 
$current_user = getCurrentUser();
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar navbar-vertical navbar-expand-lg">
    <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
        <div class="navbar-vertical-content">
            <ul class="navbar-nav flex-column" id="navbarVerticalNav">
                <li class="nav-item">
                    <div class="nav-item-wrapper">
                        <a class="nav-link label-1 <?= $current_page === 'dashboard.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/dashboard.php">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon">
                                    <span data-feather="pie-chart"></span>
                                </span>
                                <span class="nav-link-text-wrapper">
                                    <span class="nav-link-text">Dashboard</span>
                                </span>
                            </div>
                        </a>
                    </div>
                </li>

                <li class="nav-item">
                    <div class="nav-item-wrapper">
                        <a class="nav-link label-1 <?= strpos($current_page, 'strategies') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>/views/strategies/">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon">
                                    <span data-feather="layers"></span>
                                </span>
                                <span class="nav-link-text-wrapper">
                                    <span class="nav-link-text">Strategies</span>
                                    <?php 
                                    $strategy_count = $db->fetch("SELECT COUNT(*) as count FROM strategies WHERE user_id = ?", [$_SESSION['user_id']]);
                                    if ($strategy_count['count'] > 0): 
                                    ?>
                                        <span class="badge badge-phoenix badge-phoenix-primary ms-2"><?= $strategy_count['count'] ?></span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </a>
                    </div>
                </li>

                <li class="nav-item">
                    <div class="nav-item-wrapper">
                        <a class="nav-link label-1 <?= strpos($current_page, 'trades') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>/views/trades/">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon">
                                    <span data-feather="trending-up"></span>
                                </span>
                                <span class="nav-link-text-wrapper">
                                    <span class="nav-link-text">Trade Journal</span>
                                </span>
                            </div>
                        </a>
                    </div>
                </li>

                <?php if ($current_user && $current_user['is_admin']): ?>
                <li class="nav-item">
                    <div class="nav-item-wrapper">
                        <a class="nav-link dropdown-indicator label-1" href="#admin" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="admin">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon">
                                    <span data-feather="settings"></span>
                                </span>
                                <span class="nav-link-text">Admin</span>
                                <span class="nav-link-toggle"></span>
                            </div>
                        </a>
                        <div class="parent-wrapper label-1">
                            <ul class="nav collapse parent" data-bs-parent="#navbarVerticalCollapse" id="admin">
                                <li class="collapsed-nav-item-title d-none">Admin</li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= BASE_URL ?>/views/admin/dashboard.php">
                                        <div class="d-flex align-items-center">
                                            <span class="nav-link-text">Dashboard</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= BASE_URL ?>/views/admin/users.php">
                                        <div class="d-flex align-items-center">
                                            <span class="nav-link-text">Users</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= BASE_URL ?>/views/admin/system-health.php">
                                        <div class="d-flex align-items-center">
                                            <span class="nav-link-text">System Health</span>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
    <div class="navbar-vertical-footer">
        <button class="btn navbar-vertical-toggle border-0 fw-semibold w-100 white-space-nowrap d-flex align-items-center">
            <span class="uil uil-left-arrow-to-left fs-8"></span>
            <span class="uil uil-arrow-from-right fs-8"></span>
            <span class="navbar-vertical-footer-text ms-2">Collapsed View</span>
        </button>
    </div>
</nav>

<nav class="navbar navbar-top fixed-top navbar-expand" id="navbarDefault">
    <div class="collapse navbar-collapse justify-content-between">
        <div class="navbar-logo">
            <button class="btn navbar-toggler navbar-toggler-humburger-icon hover-bg-transparent" type="button" data-bs-toggle="collapse" data-bs-target="#navbarVerticalCollapse" aria-controls="navbarVerticalCollapse" aria-expanded="false" aria-label="Toggle Navigation">
                <span class="navbar-toggle-icon">
                    <span class="toggle-line"></span>
                </span>
            </button>
            <a class="navbar-brand me-1 me-sm-3" href="<?= BASE_URL ?>">
                <div class="d-flex align-items-center">
                    <div class="d-flex align-items-center">
                        <h5 class="logo-text ms-2 d-none d-sm-block">Trade Logger</h5>
                    </div>
                </div>
            </a>
        </div>

        <ul class="navbar-nav navbar-nav-icons flex-row">
            <li class="nav-item dropdown">
                <a class="nav-link lh-1 pe-0" id="navbarDropdownUser" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false">
                    <div class="avatar avatar-l">
                        <img class="rounded-circle" src="<?= BASE_URL ?>/assets/img/team/40x40/57.webp" alt="Profile">
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end navbar-dropdown-caret py-0 dropdown-profile shadow border" aria-labelledby="navbarDropdownUser">
                    <div class="card position-relative border-0">
                        <div class="card-body p-0">
                            <div class="text-center pt-4 pb-3">
                                <div class="avatar avatar-xl">
                                    <img class="rounded-circle" src="<?= BASE_URL ?>/assets/img/team/72x72/57.webp" alt="Profile">
                                </div>
                                <h6 class="mt-2 text-body"><?= sanitize($current_user['username']) ?></h6>
                            </div>
                        </div>
                        <div class="overflow-auto scrollbar">
                            <ul class="nav d-flex flex-column mb-2 pb-1">
                                <li class="nav-item">
                                    <a class="nav-link px-3" href="#!">
                                        <span class="me-2 text-body" data-feather="user"></span>
                                        <span>Profile</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link px-3" href="#!">
                                        <span class="me-2 text-body" data-feather="settings"></span>
                                        <span>Settings</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-footer p-0 border-top border-translucent">
                            <div class="px-3">
                                <a class="btn btn-phoenix-secondary d-flex flex-center w-100" href="<?= BASE_URL ?>/logout.php">
                                    <span class="me-2" data-feather="log-out"></span>
                                    Sign out
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</nav>
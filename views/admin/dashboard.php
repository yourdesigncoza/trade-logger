<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Admin.php';

requireAdmin();

$admin = new Admin();
$current_user = getCurrentUser();

// Get system statistics
$system_stats = $admin->getSystemStats();
$storage_usage = $admin->getStorageUsage();
$recent_activity = $admin->getRecentActivity(5);

// Get recent user registrations
$recent_users = $admin->getUserList('', 5, 0);

// Calculate growth metrics
$user_growth = $system_stats['new_users_30d'];
$trade_growth = $system_stats['new_trades_30d'] ?? 0;
$strategy_growth = $system_stats['new_strategies_30d'] ?? 0;

$page_title = 'Admin Dashboard';
include __DIR__ . '/../layouts/header.php';
?>

<div class="pb-9">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="page-header-title">
                                <span class="fas fa-tachometer-alt text-primary me-2"></span>
                                Admin Dashboard
                            </h3>
                            <p class="page-header-text mb-0">
                                Welcome back, <?= sanitize($current_user['username']) ?>. System overview and management.
                            </p>
                        </div>
                        <div class="col-auto">
                            <div class="btn-group" role="group">
                                <a href="<?= BASE_URL ?>/views/admin/users.php" class="btn btn-primary">
                                    <span class="fas fa-users me-2"></span>Manage Users
                                </a>
                                <a href="<?= BASE_URL ?>/views/admin/system-health.php" class="btn btn-outline-success">
                                    <span class="fas fa-heartbeat me-2"></span>System Health
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Metrics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card stat-card primary h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="text-white mb-1"><?= number_format($system_stats['total_users']) ?></h3>
                                <p class="fs-9 text-white-75 mb-0">Total Users</p>
                                <div class="d-flex align-items-center mt-1">
                                    <span class="fas fa-arrow-up text-white-50 me-1"></span>
                                    <small class="text-white-50">+<?= $user_growth ?> this month</small>
                                </div>
                            </div>
                            <div class="ms-3">
                                <span class="fas fa-users fs-1 text-white-25"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card stat-card success h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="text-white mb-1"><?= number_format($system_stats['verified_users']) ?></h3>
                                <p class="fs-9 text-white-75 mb-0">Verified Users</p>
                                <div class="d-flex align-items-center mt-1">
                                    <span class="fas fa-percentage text-white-50 me-1"></span>
                                    <small class="text-white-50"><?= number_format($system_stats['user_verification_rate'], 1) ?>% verified</small>
                                </div>
                            </div>
                            <div class="ms-3">
                                <span class="fas fa-check-circle fs-1 text-white-25"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card stat-card warning h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="text-white mb-1"><?= number_format($system_stats['total_trades']) ?></h3>
                                <p class="fs-9 text-white-75 mb-0">Total Trades</p>
                                <div class="d-flex align-items-center mt-1">
                                    <span class="fas fa-chart-line text-white-50 me-1"></span>
                                    <small class="text-white-50"><?= number_format($system_stats['system_win_rate'], 1) ?>% win rate</small>
                                </div>
                            </div>
                            <div class="ms-3">
                                <span class="fas fa-chart-line fs-1 text-white-25"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card stat-card info h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="text-white mb-1"><?= number_format($system_stats['total_strategies']) ?></h3>
                                <p class="fs-9 text-white-75 mb-0">Total Strategies</p>
                                <div class="d-flex align-items-center mt-1">
                                    <span class="fas fa-arrow-up text-white-50 me-1"></span>
                                    <small class="text-white-50">+<?= $strategy_growth ?> this month</small>
                                </div>
                            </div>
                            <div class="ms-3">
                                <span class="fas fa-layer-group fs-1 text-white-25"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <!-- System Health Overview -->
            <div class="col-xl-8">
                <div class="card h-100">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="card-title mb-0">
                                <span class="fas fa-pulse me-2"></span>System Performance
                            </h6>
                            <a href="<?= BASE_URL ?>/views/admin/system-health.php" class="btn btn-outline-primary btn-sm">
                                View Details
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded">
                                    <div class="mb-2">
                                        <span class="fas fa-hdd text-primary fs-4"></span>
                                    </div>
                                    <div class="fw-bold text-dark"><?= $storage_usage['total_size_mb'] ?> MB</div>
                                    <small class="text-body-tertiary">Storage Used</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded">
                                    <div class="mb-2">
                                        <span class="fas fa-file text-success fs-4"></span>
                                    </div>
                                    <div class="fw-bold text-dark"><?= number_format($storage_usage['file_count']) ?></div>
                                    <small class="text-body-tertiary">Files</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded">
                                    <div class="mb-2">
                                        <span class="fas fa-eye text-warning fs-4"></span>
                                    </div>
                                    <div class="fw-bold text-dark"><?= $system_stats['active_users_24h'] ?></div>
                                    <small class="text-body-tertiary">Active (24h)</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded">
                                    <div class="mb-2">
                                        <span class="fas fa-database text-info fs-4"></span>
                                    </div>
                                    <div class="fw-bold text-dark"><?= number_format(($system_stats['total_users'] + $system_stats['total_trades'] + $system_stats['total_strategies']) * 0.5, 0) ?> KB</div>
                                    <small class="text-body-tertiary">DB Size (est.)</small>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h6 class="mb-3">Quick Actions</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= BASE_URL ?>/views/admin/users.php" class="btn btn-outline-primary btn-sm">
                                    <span class="fas fa-users me-1"></span>Manage Users
                                </a>
                                <a href="<?= BASE_URL ?>/views/admin/system-health.php" class="btn btn-outline-success btn-sm">
                                    <span class="fas fa-heartbeat me-1"></span>System Health
                                </a>
                                <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#activityModal">
                                    <span class="fas fa-history me-1"></span>View Activity
                                </button>
                                <a href="<?= BASE_URL ?>/views/dashboard/analytics.php" class="btn btn-outline-secondary btn-sm">
                                    <span class="fas fa-arrow-left me-1"></span>User View
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Summary -->
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <span class="fas fa-clock me-2"></span>Recent Activity
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_activity)): ?>
                            <div class="text-center py-4">
                                <span class="fas fa-history fs-1 text-body-tertiary mb-2"></span>
                                <p class="text-body-tertiary mb-0">No recent activity</p>
                            </div>
                        <?php else: ?>
                            <div class="activity-feed">
                                <?php foreach ($recent_activity as $activity): ?>
                                    <div class="d-flex mb-3">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="avatar avatar-xs">
                                                <div class="avatar-name rounded-circle bg-<?= 
                                                    $activity['activity_type'] === 'user_registered' ? 'primary' : 
                                                    ($activity['activity_type'] === 'strategy_created' ? 'success' : 'warning') 
                                                ?> text-white d-flex align-items-center justify-content-center">
                                                    <span class="fas fa-<?= 
                                                        $activity['activity_type'] === 'user_registered' ? 'user-plus' : 
                                                        ($activity['activity_type'] === 'strategy_created' ? 'layer-group' : 'chart-line') 
                                                    ?> fs-8"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="mb-1 fs-9"><?= sanitize($activity['activity_description']) ?></p>
                                            <small class="text-body-tertiary"><?= timeAgo($activity['activity_date']) ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-center mt-3">
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#activityModal">
                                    View All Activity
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Users and System Info -->
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="card-title mb-0">
                                <span class="fas fa-user-plus me-2"></span>Recent Users
                            </h6>
                            <a href="<?= BASE_URL ?>/views/admin/users.php" class="btn btn-outline-primary btn-sm">
                                Manage All
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recent_users)): ?>
                            <div class="p-4 text-center">
                                <p class="text-body-tertiary mb-0">No users registered yet</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>User</th>
                                            <th>Email</th>
                                            <th>Status</th>
                                            <th>Activity</th>
                                            <th>Registered</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_users as $user): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-xs me-2">
                                                            <div class="avatar-name rounded-circle bg-primary text-white d-flex align-items-center justify-content-center">
                                                                <?= strtoupper(substr($user['username'], 0, 2)) ?>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="fw-medium"><?= sanitize($user['username']) ?></div>
                                                            <?php if ($user['is_admin']): ?>
                                                                <span class="badge badge-phoenix-primary badge-phoenix-outline">Admin</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fs-9"><?= sanitize($user['email']) ?></div>
                                                </td>
                                                <td>
                                                    <?php if ($user['email_verified']): ?>
                                                        <span class="badge badge-phoenix-success">Verified</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-phoenix-danger">Unverified</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <small class="text-primary"><?= $user['strategy_count'] ?>S</small>
                                                        <small class="text-success"><?= $user['trade_count'] ?>T</small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <small class="text-body-tertiary">
                                                        <?= timeAgo($user['created_at']) ?>
                                                    </small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <span class="fas fa-info-circle me-2"></span>System Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr>
                                <td><strong>PHP Version:</strong></td>
                                <td><?= PHP_VERSION ?></td>
                            </tr>
                            <tr>
                                <td><strong>Server Time:</strong></td>
                                <td><?= date('H:i:s') ?></td>
                            </tr>
                            <tr>
                                <td><strong>Timezone:</strong></td>
                                <td><?= date_default_timezone_get() ?></td>
                            </tr>
                            <tr>
                                <td><strong>Memory Limit:</strong></td>
                                <td><?= ini_get('memory_limit') ?></td>
                            </tr>
                            <tr>
                                <td><strong>Upload Limit:</strong></td>
                                <td><?= ini_get('upload_max_filesize') ?></td>
                            </tr>
                        </table>

                        <hr>

                        <div class="text-center">
                            <small class="text-body-tertiary">
                                <strong>Trade Logger v1.0</strong><br>
                                Generated by John @ YourDesign.co.za
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Activity Modal -->
<div class="modal fade" id="activityModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Recent System Activity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php
                $all_activity = $admin->getRecentActivity(20);
                if (empty($all_activity)):
                ?>
                    <div class="text-center py-4">
                        <span class="fas fa-history fs-1 text-body-tertiary mb-3"></span>
                        <p class="text-body-tertiary">No recent activity to display</p>
                    </div>
                <?php else: ?>
                    <div class="activity-timeline">
                        <?php foreach ($all_activity as $activity): ?>
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-sm">
                                        <div class="avatar-name rounded-circle bg-<?= 
                                            $activity['activity_type'] === 'user_registered' ? 'primary' : 
                                            ($activity['activity_type'] === 'strategy_created' ? 'success' : 'info') 
                                        ?> text-white d-flex align-items-center justify-content-center">
                                            <span class="fas fa-<?= 
                                                $activity['activity_type'] === 'user_registered' ? 'user-plus' : 
                                                ($activity['activity_type'] === 'strategy_created' ? 'layer-group' : 'chart-line') 
                                            ?> fs-6"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-1"><?= sanitize($activity['activity_description']) ?></p>
                                    <small class="text-body-tertiary">
                                        <?= date('M j, Y \a\t g:i A', strtotime($activity['activity_date'])) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

<script>
// Auto-refresh activity every 2 minutes
setInterval(function() {
    location.reload();
}, 120000);
</script>
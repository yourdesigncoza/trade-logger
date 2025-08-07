<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Admin.php';

requireAdmin();

$admin = new Admin();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    CSRF::validateRequest();
    
    try {
        switch ($_POST['action']) {
            case 'update_strategy_limit':
                $user_id = (int)($_POST['user_id'] ?? 0);
                $new_limit = (int)($_POST['strategy_limit'] ?? 3);
                $admin->updateStrategyLimit($user_id, $new_limit);
                flashMessage('success', 'Strategy limit updated successfully');
                break;
                
            case 'delete_user':
                $user_id = (int)($_POST['user_id'] ?? 0);
                $user = $admin->getUserById($user_id);
                $admin->deleteUser($user_id);
                flashMessage('success', 'User "' . $user['username'] . '" has been deleted');
                break;
                
            case 'promote_admin':
                $user_id = (int)($_POST['user_id'] ?? 0);
                $admin->promoteToAdmin($user_id);
                flashMessage('success', 'User promoted to admin');
                break;
                
            case 'demote_admin':
                $user_id = (int)($_POST['user_id'] ?? 0);
                $admin->demoteFromAdmin($user_id);
                flashMessage('success', 'User demoted from admin');
                break;
        }
    } catch (Exception $e) {
        flashMessage('error', $e->getMessage());
    }
    
    redirect('/views/admin/users.php');
}

// Get parameters
$search = sanitize($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get data
$users = $admin->getUserList($search, $per_page + 1, $offset);
$has_more = count($users) > $per_page;
if ($has_more) {
    array_pop($users);
}

$total_users = $admin->getUserCount($search);
$system_stats = $admin->getSystemStats();
$recent_activity = $admin->getRecentActivity(10);
$storage_usage = $admin->getStorageUsage();

$page_title = 'Admin Panel - User Management';
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
                                <span class="fas fa-shield-alt text-primary me-2"></span>
                                Admin Panel
                            </h3>
                            <p class="page-header-text mb-0">System management and user administration</p>
                        </div>
                        <div class="col-auto">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#systemStatsModal">
                                    <span class="fas fa-chart-bar me-2"></span>System Stats
                                </button>
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#activityModal">
                                    <span class="fas fa-history me-2"></span>Recent Activity
                                </button>
                                <a href="<?= BASE_URL ?>/views/admin/system-health.php" class="btn btn-outline-success">
                                    <span class="fas fa-heartbeat me-2"></span>System Health
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Overview Cards -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-1">
                            <h4 class="text-white mb-0"><?= $system_stats['total_users'] ?></h4>
                            <p class="fs-9 text-white-75 mb-0">Total Users</p>
                            <small class="text-white-50"><?= $system_stats['new_users_30d'] ?> new (30d)</small>
                        </div>
                        <div class="ms-3">
                            <span class="fas fa-users fs-4 text-white-50"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card stat-card success h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-1">
                            <h4 class="text-white mb-0"><?= number_format($system_stats['user_verification_rate'], 1) ?>%</h4>
                            <p class="fs-9 text-white-75 mb-0">Verified Users</p>
                            <small class="text-white-50"><?= $system_stats['verified_users'] ?>/<?= $system_stats['total_users'] ?></small>
                        </div>
                        <div class="ms-3">
                            <span class="fas fa-check-circle fs-4 text-white-50"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card stat-card warning h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-1">
                            <h4 class="text-white mb-0"><?= $system_stats['total_trades'] ?></h4>
                            <p class="fs-9 text-white-75 mb-0">Total Trades</p>
                            <small class="text-white-50"><?= number_format($system_stats['system_win_rate'], 1) ?>% win rate</small>
                        </div>
                        <div class="ms-3">
                            <span class="fas fa-chart-line fs-4 text-white-50"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card stat-card danger h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-1">
                            <h4 class="text-white mb-0"><?= $system_stats['active_users_24h'] ?></h4>
                            <p class="fs-9 text-white-75 mb-0">Active (24h)</p>
                            <small class="text-white-50"><?= $storage_usage['total_size_mb'] ?>MB storage</small>
                        </div>
                        <div class="ms-3">
                            <span class="fas fa-eye fs-4 text-white-50"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Management -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="card-title mb-0">User Management (<?= $total_users ?> users)</h5>
                            </div>
                            <div class="col-auto">
                                <form method="GET" class="d-flex gap-2">
                                    <input type="search" 
                                           class="form-control form-control-sm" 
                                           name="search" 
                                           placeholder="Search users..."
                                           value="<?= sanitize($search) ?>"
                                           style="min-width: 200px;">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <span class="fas fa-search"></span>
                                    </button>
                                    <?php if ($search): ?>
                                        <a href="<?= BASE_URL ?>/views/admin/users.php" class="btn btn-outline-secondary btn-sm">
                                            <span class="fas fa-times"></span>
                                        </a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (empty($users)): ?>
                        <div class="card-body text-center py-5">
                            <div class="mb-4">
                                <span class="fas fa-users fs-1 text-body-tertiary"></span>
                            </div>
                            <h4 class="text-body-tertiary">No users found</h4>
                            <p class="text-body-tertiary">
                                <?= $search ? 'Try adjusting your search terms' : 'No users have registered yet' ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>User</th>
                                            <th>Email Status</th>
                                            <th>Trading Activity</th>
                                            <th>Account</th>
                                            <th>Last Activity</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-sm me-2">
                                                            <div class="avatar-name rounded-circle bg-primary text-white d-flex align-items-center justify-content-center">
                                                                <?= strtoupper(substr($user['username'], 0, 2)) ?>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="fw-medium">
                                                                <?= sanitize($user['username']) ?>
                                                                <?php if ($user['is_admin']): ?>
                                                                    <span class="badge badge-phoenix-primary badge-phoenix-outline ms-1">Admin</span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <small class="text-body-tertiary">ID: <?= $user['id'] ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div><?= sanitize($user['email']) ?></div>
                                                    <div>
                                                        <?php if ($user['email_verified']): ?>
                                                            <span class="badge badge-phoenix-success">Verified</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-phoenix-danger">Unverified</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="text-center">
                                                            <div class="fw-bold text-primary"><?= $user['strategy_count'] ?></div>
                                                            <small class="text-body-tertiary">Strategies</small>
                                                        </div>
                                                        <div class="text-center">
                                                            <div class="fw-bold text-success"><?= $user['trade_count'] ?></div>
                                                            <small class="text-body-tertiary">Trades</small>
                                                        </div>
                                                        <div class="text-center">
                                                            <div class="fw-bold text-info"><?= number_format($user['win_rate'], 1) ?>%</div>
                                                            <small class="text-body-tertiary">Win Rate</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong>Limit:</strong> <?= $user['strategy_limit'] ?>
                                                    </div>
                                                    <div>
                                                        <strong>Size:</strong> $<?= number_format($user['account_size'] ?? 0) ?>
                                                    </div>
                                                    <div>
                                                        <small class="text-body-tertiary">
                                                            Joined <?= date('M j, Y', strtotime($user['created_at'])) ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($user['last_activity_formatted']): ?>
                                                        <div><?= date('M j, Y', strtotime($user['last_activity_formatted'])) ?></div>
                                                        <small class="text-body-tertiary">
                                                            <?= date('H:i', strtotime($user['last_activity_formatted'])) ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="text-body-tertiary">Never</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button type="button" 
                                                                class="btn btn-phoenix-primary btn-sm"
                                                                onclick="editStrategyLimit(<?= $user['id'] ?>, '<?= sanitize($user['username']) ?>', <?= $user['strategy_limit'] ?>)"
                                                                title="Edit Strategy Limit">
                                                            <span class="fas fa-edit"></span>
                                                        </button>
                                                        
                                                        <?php if (!$user['is_admin']): ?>
                                                            <button type="button" 
                                                                    class="btn btn-phoenix-success btn-sm"
                                                                    onclick="promoteUser(<?= $user['id'] ?>, '<?= sanitize($user['username']) ?>')"
                                                                    title="Promote to Admin">
                                                                <span class="fas fa-user-shield"></span>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" 
                                                                    class="btn btn-phoenix-warning btn-sm"
                                                                    onclick="demoteUser(<?= $user['id'] ?>, '<?= sanitize($user['username']) ?>')"
                                                                    title="Remove Admin">
                                                                <span class="fas fa-user-minus"></span>
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (!$user['is_admin']): ?>
                                                            <button type="button" 
                                                                    class="btn btn-phoenix-danger btn-sm"
                                                                    onclick="deleteUser(<?= $user['id'] ?>, '<?= sanitize($user['username']) ?>')"
                                                                    title="Delete User">
                                                                <span class="fas fa-trash"></span>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <?php if ($has_more || $page > 1): ?>
                            <div class="card-footer">
                                <nav aria-label="User pagination">
                                    <ul class="pagination justify-content-center mb-0">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?= buildQueryString(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <li class="page-item active">
                                            <span class="page-link">Page <?= $page ?></span>
                                        </li>
                                        
                                        <?php if ($has_more): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?= buildQueryString(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Strategy Limit Modal -->
<div class="modal fade" id="strategyLimitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Strategy Limit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?= CSRF::getTokenField() ?>
                <input type="hidden" name="action" value="update_strategy_limit">
                <input type="hidden" name="user_id" id="strategyLimitUserId">
                <div class="modal-body">
                    <p>Update strategy limit for user: <strong id="strategyLimitUsername"></strong></p>
                    <div class="mb-3">
                        <label class="form-label" for="strategy_limit">Strategy Limit</label>
                        <input type="number" 
                               class="form-control" 
                               id="strategy_limit" 
                               name="strategy_limit" 
                               min="0" 
                               max="1000" 
                               required>
                        <div class="form-text">Maximum number of strategies this user can create (0-1000)</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Limit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- System Stats Modal -->
<div class="modal fade" id="systemStatsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">System Statistics</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="text-primary">User Statistics</h6>
                        <table class="table table-sm">
                            <tr><td>Total Users:</td><td><strong><?= $system_stats['total_users'] ?></strong></td></tr>
                            <tr><td>Verified Users:</td><td><strong><?= $system_stats['verified_users'] ?></strong></td></tr>
                            <tr><td>Admin Users:</td><td><strong><?= $system_stats['admin_users'] ?></strong></td></tr>
                            <tr><td>New Users (30d):</td><td><strong><?= $system_stats['new_users_30d'] ?></strong></td></tr>
                            <tr><td>Active (24h):</td><td><strong><?= $system_stats['active_users_24h'] ?></strong></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-success">Trading Statistics</h6>
                        <table class="table table-sm">
                            <tr><td>Total Strategies:</td><td><strong><?= $system_stats['total_strategies'] ?></strong></td></tr>
                            <tr><td>Total Trades:</td><td><strong><?= $system_stats['total_trades'] ?></strong></td></tr>
                            <tr><td>Winning Trades:</td><td><strong><?= $system_stats['winning_trades'] ?></strong></td></tr>
                            <tr><td>System Win Rate:</td><td><strong><?= number_format($system_stats['system_win_rate'], 1) ?>%</strong></td></tr>
                            <tr><td>Average RRR:</td><td><strong><?= number_format($system_stats['avg_rrr'] ?? 0, 2) ?></strong></td></tr>
                        </table>
                    </div>
                    <div class="col-12">
                        <h6 class="text-warning">System Resources</h6>
                        <table class="table table-sm">
                            <tr><td>Storage Used:</td><td><strong><?= $storage_usage['total_size_mb'] ?> MB</strong></td></tr>
                            <tr><td>Uploaded Files:</td><td><strong><?= $storage_usage['file_count'] ?></strong></td></tr>
                            <tr><td>Database Size:</td><td><strong>~<?= number_format($system_stats['total_trades'] * 0.5 + $system_stats['total_users'] * 0.1, 1) ?> KB</strong></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity Modal -->
<div class="modal fade" id="activityModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Recent Activity (Last 7 Days)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if (empty($recent_activity)): ?>
                    <div class="text-center py-4">
                        <span class="fas fa-history fs-1 text-body-tertiary mb-3"></span>
                        <p class="text-body-tertiary">No recent activity</p>
                    </div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($recent_activity as $activity): ?>
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
                                        <?= timeAgo($activity['activity_date']) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modals -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this user?</p>
                <p class="text-danger">
                    <strong>User:</strong> <span id="deleteUsername"></span>
                </p>
                <p class="text-muted">This will permanently delete all user data including strategies, trades, and uploaded files. This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <?= CSRF::getTokenField() ?>
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="promoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Promote to Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to promote this user to admin?</p>
                <p class="text-primary">
                    <strong>User:</strong> <span id="promoteUsername"></span>
                </p>
                <p class="text-muted">Admin users will have full access to the admin panel and can manage other users.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <?= CSRF::getTokenField() ?>
                    <input type="hidden" name="action" value="promote_admin">
                    <input type="hidden" name="user_id" id="promoteUserId">
                    <button type="submit" class="btn btn-primary">Promote to Admin</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="demoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Remove Admin Access</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to remove admin access for this user?</p>
                <p class="text-warning">
                    <strong>User:</strong> <span id="demoteUsername"></span>
                </p>
                <p class="text-muted">This user will lose access to the admin panel and user management features.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <?= CSRF::getTokenField() ?>
                    <input type="hidden" name="action" value="demote_admin">
                    <input type="hidden" name="user_id" id="demoteUserId">
                    <button type="submit" class="btn btn-warning">Remove Admin</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

<script>
function editStrategyLimit(userId, username, currentLimit) {
    document.getElementById('strategyLimitUserId').value = userId;
    document.getElementById('strategyLimitUsername').textContent = username;
    document.getElementById('strategy_limit').value = currentLimit;
    new bootstrap.Modal(document.getElementById('strategyLimitModal')).show();
}

function deleteUser(userId, username) {
    document.getElementById('deleteUserId').value = userId;
    document.getElementById('deleteUsername').textContent = username;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

function promoteUser(userId, username) {
    document.getElementById('promoteUserId').value = userId;
    document.getElementById('promoteUsername').textContent = username;
    new bootstrap.Modal(document.getElementById('promoteModal')).show();
}

function demoteUser(userId, username) {
    document.getElementById('demoteUserId').value = userId;
    document.getElementById('demoteUsername').textContent = username;
    new bootstrap.Modal(document.getElementById('demoteModal')).show();
}
</script>
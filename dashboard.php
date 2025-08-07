<?php
require_once __DIR__ . '/config/config.php';

// Enable error logging for development
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/dashboard_errors.log');

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

try {
    requireLogin();

    $current_user = getCurrentUser();
    $page_title = 'Dashboard - Trade Logger';

    // Check if database is available before querying
    if (!$db) {
        throw new Exception("Database connection not available");
    }

    // Get basic stats with error handling
    $total_trades = $db->fetch("SELECT COUNT(*) as count FROM trades WHERE user_id = ?", [$_SESSION['user_id']]);
    if (!$total_trades) {
        error_log("Dashboard: Failed to fetch total trades for user ID: " . $_SESSION['user_id']);
        $total_trades = ['count' => 0];
    }

    $total_strategies = $db->fetch("SELECT COUNT(*) as count FROM strategies WHERE user_id = ?", [$_SESSION['user_id']]);
    if (!$total_strategies) {
        error_log("Dashboard: Failed to fetch total strategies for user ID: " . $_SESSION['user_id']);
        $total_strategies = ['count' => 0];
    }

    $winning_trades = $db->fetch("SELECT COUNT(*) as count FROM trades WHERE user_id = ? AND outcome = 'Win'", [$_SESSION['user_id']]);
    if (!$winning_trades) {
        error_log("Dashboard: Failed to fetch winning trades for user ID: " . $_SESSION['user_id']);
        $winning_trades = ['count' => 0];
    }

    $losing_trades = $db->fetch("SELECT COUNT(*) as count FROM trades WHERE user_id = ? AND outcome = 'Loss'", [$_SESSION['user_id']]);
    if (!$losing_trades) {
        error_log("Dashboard: Failed to fetch losing trades for user ID: " . $_SESSION['user_id']);
        $losing_trades = ['count' => 0];
    }

    $win_rate = 0;
    if ($total_trades['count'] > 0) {
        $win_rate = ($winning_trades['count'] / $total_trades['count']) * 100;
    }

    error_log("Dashboard: Successfully loaded stats for user ID: " . $_SESSION['user_id'] . " - Trades: " . $total_trades['count'] . ", Win Rate: " . number_format($win_rate, 2) . "%");

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    flashMessage('error', 'An error occurred while loading the dashboard. Please try again.');
    
    // Set default values to prevent page crash
    $total_trades = ['count' => 0];
    $total_strategies = ['count' => 0];
    $winning_trades = ['count' => 0];
    $losing_trades = ['count' => 0];
    $win_rate = 0;
    
    // Ensure $current_user is always defined
    if (!isset($current_user) || !$current_user) {
        $current_user = ['username' => 'User', 'strategy_limit' => 0, 'account_size' => 0];
    }
}

include __DIR__ . '/views/layouts/header.php';
?>

<div class="pb-9">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="page-header-title">Welcome back, <?= sanitize($current_user['username']) ?>!</h3>
                            <p class="page-header-text mb-0">Here's what's happening with your trading journal</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row g-3 mb-6">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-1">
                            <h4 class="text-white mb-0"><?= $total_trades['count'] ?></h4>
                            <p class="fs-9 text-white-75 mb-0">Total Trades</p>
                        </div>
                        <div class="ms-3">
                            <span class="fas fa-chart-line fs-4 text-white-50"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card stat-card success h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-1">
                            <h4 class="text-white mb-0"><?= number_format($win_rate, 1) ?>%</h4>
                            <p class="fs-9 text-white-75 mb-0">Win Rate</p>
                        </div>
                        <div class="ms-3">
                            <span class="fas fa-percentage fs-4 text-white-50"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card stat-card warning h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-1">
                            <h4 class="text-white mb-0"><?= $total_strategies['count'] ?>/<?= $current_user['strategy_limit'] ?></h4>
                            <p class="fs-9 text-white-75 mb-0">Strategies</p>
                        </div>
                        <div class="ms-3">
                            <span class="fas fa-layer-group fs-4 text-white-50"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card stat-card danger h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-1">
                            <h4 class="text-white mb-0">$<?= number_format($current_user['account_size'] ?? 0, 2) ?></h4>
                            <p class="fs-9 text-white-75 mb-0">Account Size</p>
                        </div>
                        <div class="ms-3">
                            <span class="fas fa-dollar-sign fs-4 text-white-50"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row g-3 mb-6">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="mb-0">Quick Actions</h6>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6 col-lg-3">
                                <a href="<?= BASE_URL ?>/views/trades/create.php" class="btn btn-phoenix-primary w-100">
                                    <span class="fas fa-plus me-2"></span>Log New Trade
                                </a>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3">
                                <a href="<?= BASE_URL ?>/views/strategies/create.php" class="btn btn-phoenix-secondary w-100">
                                    <span class="fas fa-layer-group me-2"></span>Create Strategy
                                </a>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3">
                                <a href="<?= BASE_URL ?>/views/trades/" class="btn btn-phoenix-info w-100">
                                    <span class="fas fa-list me-2"></span>View Trades
                                </a>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3">
                                <a href="<?= BASE_URL ?>/views/dashboard/analytics.php" class="btn btn-phoenix-success w-100">
                                    <span class="fas fa-chart-bar me-2"></span>Analytics
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row g-3">
            <div class="col-12 col-lg-8">
                <div class="card h-100">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="mb-0">Recent Trades</h6>
                            </div>
                            <div class="col-auto">
                                <a href="<?= BASE_URL ?>/views/trades/" class="btn btn-link btn-sm">View All</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $recent_trades = $db->fetchAll(
                                "SELECT t.*, s.name as strategy_name 
                                 FROM trades t 
                                 LEFT JOIN strategies s ON t.strategy_id = s.id 
                                 WHERE t.user_id = ? 
                                 ORDER BY t.created_at DESC 
                                 LIMIT 5",
                                [$_SESSION['user_id']]
                            );
                            if (!$recent_trades) {
                                $recent_trades = [];
                                error_log("Dashboard: No recent trades found for user ID: " . $_SESSION['user_id']);
                            }
                        } catch (Exception $e) {
                            error_log("Dashboard: Error fetching recent trades - " . $e->getMessage());
                            $recent_trades = [];
                            flashMessage('warning', 'Could not load recent trades at this time.');
                        }
                        ?>

                        <?php if (empty($recent_trades)): ?>
                            <div class="text-center py-4">
                                <div class="mb-3">
                                    <span class="fas fa-chart-line fs-1 text-body-tertiary"></span>
                                </div>
                                <h6 class="text-body-tertiary">No trades yet</h6>
                                <p class="text-body-tertiary mb-3">Start by logging your first trade</p>
                                <a href="<?= BASE_URL ?>/views/trades/create.php" class="btn btn-phoenix-primary">
                                    <span class="fas fa-plus me-2"></span>Log Your First Trade
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Instrument</th>
                                            <th>Direction</th>
                                            <th>Outcome</th>
                                            <th>RRR</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_trades as $trade): ?>
                                            <tr>
                                                <td><?= formatDate($trade['date']) ?></td>
                                                <td><?= sanitize($trade['instrument']) ?></td>
                                                <td>
                                                    <span class="badge badge-phoenix-<?= $trade['direction'] === 'long' ? 'success' : 'danger' ?>">
                                                        <?= ucfirst($trade['direction']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($trade['outcome']): ?>
                                                        <span class="badge trade-status-<?= strtolower(str_replace('-', '-', $trade['outcome'])) ?>">
                                                            <?= sanitize($trade['outcome']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge trade-status-<?= $trade['status'] ?>">
                                                            <?= ucfirst($trade['status']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $trade['rrr'] ? formatCurrency($trade['rrr']) : '-' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="mb-0">My Strategies</h6>
                            </div>
                            <div class="col-auto">
                                <a href="<?= BASE_URL ?>/views/strategies/" class="btn btn-link btn-sm">View All</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $strategies = $db->fetchAll(
                                "SELECT s.*, COUNT(t.id) as trade_count 
                                 FROM strategies s 
                                 LEFT JOIN trades t ON s.id = t.strategy_id 
                                 WHERE s.user_id = ? 
                                 GROUP BY s.id 
                                 ORDER BY s.created_at DESC",
                                [$_SESSION['user_id']]
                            );
                            if (!$strategies) {
                                $strategies = [];
                                error_log("Dashboard: No strategies found for user ID: " . $_SESSION['user_id']);
                            }
                        } catch (Exception $e) {
                            error_log("Dashboard: Error fetching strategies - " . $e->getMessage());
                            $strategies = [];
                            flashMessage('warning', 'Could not load strategies at this time.');
                        }
                        ?>

                        <?php if (empty($strategies)): ?>
                            <div class="text-center py-4">
                                <div class="mb-3">
                                    <span class="fas fa-layer-group fs-1 text-body-tertiary"></span>
                                </div>
                                <h6 class="text-body-tertiary">No strategies yet</h6>
                                <p class="text-body-tertiary mb-3">Create your first trading strategy</p>
                                <a href="<?= BASE_URL ?>/views/strategies/create.php" class="btn btn-phoenix-secondary">
                                    <span class="fas fa-plus me-2"></span>Create Strategy
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($strategies as $strategy): ?>
                                <div class="strategy-card p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0"><?= sanitize($strategy['name']) ?></h6>
                                        <span class="badge badge-phoenix-outline badge-phoenix-primary"><?= $strategy['trade_count'] ?> trades</span>
                                    </div>
                                    <p class="text-body-tertiary fs-9 mb-2"><?= sanitize(substr($strategy['description'] ?? '', 0, 100)) ?><?= strlen($strategy['description'] ?? '') > 100 ? '...' : '' ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-body-tertiary"><?= sanitize($strategy['instrument']) ?></small>
                                        <a href="<?= BASE_URL ?>/views/strategies/view.php?id=<?= $strategy['id'] ?>" class="btn btn-link btn-sm p-0">View</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/views/layouts/footer.php'; ?>
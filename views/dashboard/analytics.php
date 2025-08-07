<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Trade.php';
require_once __DIR__ . '/../../models/Strategy.php';

requireLogin();

$trade_model = new Trade();
$strategy_model = new Strategy();
$current_user = getCurrentUser();

// Get filter parameters
$filters = [
    'strategy_id' => (int)($_GET['strategy_id'] ?? 0) ?: null,
    'instrument' => sanitize($_GET['instrument'] ?? ''),
    'session' => sanitize($_GET['session'] ?? ''),
    'direction' => sanitize($_GET['direction'] ?? ''),
    'date_from' => sanitize($_GET['date_from'] ?? ''),
    'date_to' => sanitize($_GET['date_to'] ?? ''),
    'year' => (int)($_GET['year'] ?? date('Y'))
];

// Get analytics data
$stats = $trade_model->getTradeStats($_SESSION['user_id'], $filters);
$monthly_stats = $trade_model->getMonthlyStats($_SESSION['user_id'], $filters['year']);
$instrument_stats = $trade_model->getInstrumentStats($_SESSION['user_id']);

// Get filter options
$user_strategies = $strategy_model->getStrategyOptions($_SESSION['user_id']);
$user_instruments = $trade_model->getUserInstruments($_SESSION['user_id']);
$session_options = $trade_model->getSessionOptions();
$direction_options = $trade_model->getDirectionOptions();

// Calculate additional metrics
$total_risk_reward = 0;
$profitable_trades = 0;
if ($stats['total_trades'] > 0) {
    // Get trades for P&L calculation
    $trades = $trade_model->getByUserId($_SESSION['user_id'], $filters, 1000);
    
    foreach ($trades as $trade) {
        if ($trade['outcome'] === 'Win' && $current_user['account_size'] > 0) {
            $profitable_trades++;
            // Calculate P&L based on account size and RRR
            if ($trade['rrr']) {
                $risk_percent = 1; // Assume 1% risk per trade
                $reward_percent = $risk_percent * $trade['rrr'];
                $total_risk_reward += $reward_percent;
            }
        } elseif ($trade['outcome'] === 'Loss' && $current_user['account_size'] > 0) {
            $total_risk_reward -= 1; // Assume 1% loss per losing trade
        }
    }
}

// Prepare chart data
$monthly_chart_data = array_fill(0, 12, 0);
$monthly_pnl_data = array_fill(0, 12, 0);
$monthly_labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

foreach ($monthly_stats as $month_data) {
    $month_index = $month_data['month'] - 1;
    $monthly_chart_data[$month_index] = $month_data['trade_count'];
    
    // Calculate monthly P&L based on wins/losses
    $monthly_pnl = ($month_data['wins'] * 1) - ($month_data['losses'] * 1); // Simplified 1% per trade
    $monthly_pnl_data[$month_index] = $monthly_pnl;
}

// Calculate cumulative P&L for equity curve
$cumulative_pnl = [];
$running_total = 0;
foreach ($monthly_pnl_data as $month_pnl) {
    $running_total += $month_pnl;
    $cumulative_pnl[] = $running_total;
}

$page_title = 'Analytics Dashboard - Trade Logger';
include __DIR__ . '/../layouts/header.php';
?>

<div class="pb-9">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="page-header-title">Analytics Dashboard</h3>
                            <p class="page-header-text mb-0">Deep insights into your trading performance</p>
                        </div>
                        <div class="col-auto">
                            <div class="btn-group" role="group">
                                <a href="<?= BASE_URL ?>/api/export-csv.php?<?= buildQueryString($filters) ?>" 
                                   class="btn btn-outline-primary">
                                    <span class="fas fa-download me-2"></span>Export CSV
                                </a>
                                <a href="<?= BASE_URL ?>/api/export-pdf.php?<?= buildQueryString($filters) ?>" 
                                   class="btn btn-outline-secondary">
                                    <span class="fas fa-file-pdf me-2"></span>Export PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="card-title mb-0">Analytics Filters</h5>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-phoenix-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#analyticsFilters" aria-expanded="true">
                                    <span class="fas fa-filter me-1"></span>Toggle Filters
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="collapse show" id="analyticsFilters">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-12 col-md-6 col-lg-3">
                                    <label class="form-label">Strategy</label>
                                    <select class="form-select form-select-sm" name="strategy_id">
                                        <option value="">All Strategies</option>
                                        <?php foreach ($user_strategies as $strategy): ?>
                                            <option value="<?= $strategy['id'] ?>" 
                                                    <?= $filters['strategy_id'] == $strategy['id'] ? 'selected' : '' ?>>
                                                <?= sanitize($strategy['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6 col-lg-3">
                                    <label class="form-label">Instrument</label>
                                    <select class="form-select form-select-sm" name="instrument">
                                        <option value="">All Instruments</option>
                                        <?php foreach ($user_instruments as $instrument): ?>
                                            <option value="<?= $instrument['instrument'] ?>" 
                                                    <?= $filters['instrument'] === $instrument['instrument'] ? 'selected' : '' ?>>
                                                <?= sanitize($instrument['instrument']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6 col-lg-2">
                                    <label class="form-label">Session</label>
                                    <select class="form-select form-select-sm" name="session">
                                        <option value="">All Sessions</option>
                                        <?php foreach ($session_options as $value => $label): ?>
                                            <option value="<?= $value ?>" 
                                                    <?= $filters['session'] === $value ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6 col-lg-2">
                                    <label class="form-label">Direction</label>
                                    <select class="form-select form-select-sm" name="direction">
                                        <option value="">All Directions</option>
                                        <?php foreach ($direction_options as $value => $label): ?>
                                            <option value="<?= $value ?>" 
                                                    <?= $filters['direction'] === $value ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6 col-lg-2">
                                    <label class="form-label">Year</label>
                                    <select class="form-select form-select-sm" name="year">
                                        <?php for ($y = date('Y'); $y >= (date('Y') - 5); $y--): ?>
                                            <option value="<?= $y ?>" <?= $filters['year'] == $y ? 'selected' : '' ?>>
                                                <?= $y ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6 col-lg-3">
                                    <label class="form-label">Date From</label>
                                    <input type="date" class="form-control form-control-sm" name="date_from" 
                                           value="<?= $filters['date_from'] ?>">
                                </div>

                                <div class="col-12 col-md-6 col-lg-3">
                                    <label class="form-label">Date To</label>
                                    <input type="date" class="form-control form-control-sm" name="date_to" 
                                           value="<?= $filters['date_to'] ?>">
                                </div>

                                <div class="col-12">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <span class="fas fa-chart-line me-1"></span>Update Analytics
                                        </button>
                                        <a href="<?= BASE_URL ?>/views/dashboard/analytics.php" class="btn btn-outline-secondary btn-sm">
                                            <span class="fas fa-times me-1"></span>Clear Filters
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-1">
                            <h3 class="text-white mb-0"><?= $stats['total_trades'] ?></h3>
                            <p class="fs-9 text-white-75 mb-0">Total Trades</p>
                        </div>
                        <div class="ms-3">
                            <span class="fas fa-chart-line fs-3 text-white-50"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card stat-card success h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-1">
                            <h3 class="text-white mb-0"><?= number_format($stats['win_rate'], 1) ?>%</h3>
                            <p class="fs-9 text-white-75 mb-0">Win Rate</p>
                        </div>
                        <div class="ms-3">
                            <span class="fas fa-trophy fs-3 text-white-50"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card stat-card warning h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-1">
                            <h3 class="text-white mb-0"><?= formatCurrency($stats['avg_rrr'] ?? 0) ?></h3>
                            <p class="fs-9 text-white-75 mb-0">Avg RRR</p>
                        </div>
                        <div class="ms-3">
                            <span class="fas fa-balance-scale fs-3 text-white-50"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card stat-card <?= $total_risk_reward >= 0 ? 'success' : 'danger' ?> h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-1">
                            <h3 class="text-white mb-0"><?= $total_risk_reward > 0 ? '+' : '' ?><?= number_format($total_risk_reward, 1) ?>%</h3>
                            <p class="fs-9 text-white-75 mb-0">Total P&L</p>
                        </div>
                        <div class="ms-3">
                            <span class="fas fa-dollar-sign fs-3 text-white-50"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-lg-8">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Monthly Trade Activity (<?= $filters['year'] ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container large">
                            <canvas id="monthlyTradesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Trade Outcomes</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="outcomeChart"></canvas>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-success">
                                    <span class="fas fa-circle me-1"></span>Wins
                                </span>
                                <span><?= $stats['winning_trades'] ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-danger">
                                    <span class="fas fa-circle me-1"></span>Losses
                                </span>
                                <span><?= $stats['losing_trades'] ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-warning">
                                    <span class="fas fa-circle me-1"></span>Break-even
                                </span>
                                <span><?= $stats['breakeven_trades'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-lg-8">
                <div class="card h-100">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="card-title mb-0">Equity Curve (<?= $filters['year'] ?>)</h5>
                            </div>
                            <div class="col-auto">
                                <small class="text-body-tertiary">
                                    Account Size: $<?= number_format($current_user['account_size'] ?? 0) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container large">
                            <canvas id="equityChart"></canvas>
                        </div>
                        <div class="mt-2">
                            <small class="text-body-tertiary">
                                * Based on 1% risk per trade and actual RRR ratios
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Performance Breakdown</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h6 class="text-body-secondary">Trading Period</h6>
                            <p class="mb-1">
                                <strong>From:</strong> <?= $stats['first_trade_date'] ? formatDate($stats['first_trade_date']) : 'N/A' ?>
                            </p>
                            <p class="mb-0">
                                <strong>To:</strong> <?= $stats['last_trade_date'] ? formatDate($stats['last_trade_date']) : 'N/A' ?>
                            </p>
                        </div>

                        <div class="mb-4">
                            <h6 class="text-body-secondary">Trade Status</h6>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="text-center p-2 bg-light rounded">
                                        <div class="h5 mb-0 text-success"><?= $stats['winning_trades'] + $stats['losing_trades'] + $stats['breakeven_trades'] ?></div>
                                        <small>Closed</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-2 bg-light rounded">
                                        <div class="h5 mb-0 text-primary"><?= $stats['open_trades'] ?></div>
                                        <small>Open</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($instrument_stats)): ?>
                            <div>
                                <h6 class="text-body-secondary mb-2">Top Instruments</h6>
                                <?php foreach (array_slice($instrument_stats, 0, 3) as $instrument): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge badge-phoenix-outline badge-phoenix-primary">
                                            <?= sanitize($instrument['instrument']) ?>
                                        </span>
                                        <small class="text-body-tertiary">
                                            <?= $instrument['trade_count'] ?> trades • 
                                            <?= $instrument['trade_count'] > 0 ? round(($instrument['wins'] / $instrument['trade_count']) * 100, 1) : 0 ?>% win rate
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Size Update -->
        <?php if ($current_user['account_size'] == 0): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card border-warning">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="text-warning mb-2">
                                        <span class="fas fa-exclamation-triangle me-2"></span>
                                        Account Size Not Set
                                    </h5>
                                    <p class="mb-0">
                                        Set your account size to see more accurate P&L calculations and equity curves.
                                    </p>
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#accountSizeModal">
                                        <span class="fas fa-cog me-2"></span>Set Account Size
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Account Size Modal -->
<div class="modal fade" id="accountSizeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Set Account Size</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/api/update-account-size.php">
                <?= CSRF::getTokenField() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="account_size">Account Size ($)</label>
                        <input type="number" 
                               class="form-control" 
                               id="account_size" 
                               name="account_size" 
                               step="0.01" 
                               min="0"
                               value="<?= $current_user['account_size'] ?>"
                               placeholder="10000.00"
                               required>
                        <div class="form-text">
                            This is used to calculate percentage-based P&L and equity curves
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Account Size</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$additional_js = ['/assets/js/charts.js'];
include __DIR__ . '/../layouts/footer.php';
?>

<script>
// Chart Data
const chartData = {
    monthlyTrades: <?= json_encode($monthly_chart_data) ?>,
    monthlyLabels: <?= json_encode($monthly_labels) ?>,
    outcomeData: {
        wins: <?= $stats['winning_trades'] ?>,
        losses: <?= $stats['losing_trades'] ?>,
        breakeven: <?= $stats['breakeven_trades'] ?>
    },
    equityData: <?= json_encode($cumulative_pnl) ?>,
    accountSize: <?= $current_user['account_size'] ?? 0 ?>
};

// Initialize Charts
document.addEventListener('DOMContentLoaded', function() {
    AnalyticsCharts.initializeAllCharts(chartData);
});
</script>
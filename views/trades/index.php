<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Trade.php';
require_once __DIR__ . '/../../models/Strategy.php';

requireLogin();

$trade_model = new Trade();
$strategy_model = new Strategy();

// Get filter parameters
$filters = [
    'strategy_id' => (int)($_GET['strategy_id'] ?? 0) ?: null,
    'instrument' => sanitize($_GET['instrument'] ?? ''),
    'session' => sanitize($_GET['session'] ?? ''),
    'direction' => sanitize($_GET['direction'] ?? ''),
    'outcome' => sanitize($_GET['outcome'] ?? ''),
    'status' => sanitize($_GET['status'] ?? ''),
    'date_from' => sanitize($_GET['date_from'] ?? ''),
    'date_to' => sanitize($_GET['date_to'] ?? ''),
    'sort' => sanitize($_GET['sort'] ?? 'date'),
    'sort_dir' => sanitize($_GET['sort_dir'] ?? 'desc')
];

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get trades and stats
$trades = $trade_model->getByUserId($_SESSION['user_id'], $filters, $per_page + 1, $offset);
$has_more = count($trades) > $per_page;
if ($has_more) {
    array_pop($trades);
}

$stats = $trade_model->getTradeStats($_SESSION['user_id'], $filters);

// Get filter options
$user_strategies = $strategy_model->getStrategyOptions($_SESSION['user_id']);
$user_instruments = $trade_model->getUserInstruments($_SESSION['user_id']);
$session_options = $trade_model->getSessionOptions();
$outcome_options = $trade_model->getOutcomeOptions();
$status_options = $trade_model->getStatusOptions();
$direction_options = $trade_model->getDirectionOptions();

$page_title = 'Trade Journal - Trade Logger';
include __DIR__ . '/../layouts/header.php';
?>

<div class="pb-9">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="page-header-title">Trade Journal</h3>
                            <p class="page-header-text mb-0">Track and analyze your trading performance</p>
                        </div>
                        <div class="col-auto">
                            <a href="<?= BASE_URL ?>/views/trades/create.php" class="btn btn-primary">
                                <span class="fas fa-plus me-2"></span>Log New Trade
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-1">
                            <h4 class="text-white mb-0"><?= $stats['total_trades'] ?></h4>
                            <p class="fs-9 text-white-75 mb-0">Total Trades</p>
                        </div>
                        <div class="ms-3">
                            <span class="fas fa-chart-line fs-4 text-white-50"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card stat-card success h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-1">
                            <h4 class="text-white mb-0"><?= number_format($stats['win_rate'], 1) ?>%</h4>
                            <p class="fs-9 text-white-75 mb-0">Win Rate</p>
                        </div>
                        <div class="ms-3">
                            <span class="fas fa-percentage fs-4 text-white-50"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card stat-card warning h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-1">
                            <h4 class="text-white mb-0"><?= formatCurrency($stats['avg_rrr'] ?? 0) ?></h4>
                            <p class="fs-9 text-white-75 mb-0">Avg RRR</p>
                        </div>
                        <div class="ms-3">
                            <span class="fas fa-balance-scale fs-4 text-white-50"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card stat-card danger h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-1">
                            <h4 class="text-white mb-0"><?= $stats['open_trades'] ?></h4>
                            <p class="fs-9 text-white-75 mb-0">Open Trades</p>
                        </div>
                        <div class="ms-3">
                            <span class="fas fa-clock fs-4 text-white-50"></span>
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
                                <h5 class="card-title mb-0">Filters</h5>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-phoenix-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#filtersCollapse" aria-expanded="false">
                                    <span class="fas fa-filter me-1"></span>Toggle Filters
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="collapse show" id="filtersCollapse">
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

                                <div class="col-12 col-md-6 col-lg-3">
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

                                <div class="col-12 col-md-6 col-lg-3">
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

                                <div class="col-12 col-md-6 col-lg-3">
                                    <label class="form-label">Outcome</label>
                                    <select class="form-select form-select-sm" name="outcome">
                                        <option value="">All Outcomes</option>
                                        <?php foreach ($outcome_options as $value => $label): ?>
                                            <option value="<?= $value ?>" 
                                                    <?= $filters['outcome'] === $value ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6 col-lg-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select form-select-sm" name="status">
                                        <option value="">All Status</option>
                                        <?php foreach ($status_options as $value => $label): ?>
                                            <option value="<?= $value ?>" 
                                                    <?= $filters['status'] === $value ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
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
                                            <span class="fas fa-search me-1"></span>Apply Filters
                                        </button>
                                        <a href="<?= BASE_URL ?>/views/trades/" class="btn btn-outline-secondary btn-sm">
                                            <span class="fas fa-times me-1"></span>Clear Filters
                                        </a>
                                        <div class="ms-auto">
                                            <a href="<?= BASE_URL ?>/api/export-csv.php?<?= buildQueryString($filters) ?>" 
                                               class="btn btn-outline-primary btn-sm">
                                                <span class="fas fa-download me-1"></span>Export CSV
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trades Table -->
        <div class="row">
            <div class="col-12">
                <?php if (empty($trades)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <div class="mb-4">
                                <span class="fas fa-chart-line fs-1 text-body-tertiary"></span>
                            </div>
                            <h4 class="text-body-tertiary">No trades found</h4>
                            <p class="text-body-tertiary mb-4">
                                <?php if (!empty(array_filter($filters))): ?>
                                    Try adjusting your filters or
                                <?php endif; ?>
                                start by logging your first trade
                            </p>
                            <a href="<?= BASE_URL ?>/views/trades/create.php" class="btn btn-primary">
                                <span class="fas fa-plus me-2"></span>Log Your First Trade
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="card-title mb-0">
                                        Trades (<?= $stats['total_trades'] ?> total)
                                    </h5>
                                </div>
                                <div class="col-auto">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="?<?= buildQueryString(array_merge($filters, ['sort' => 'date', 'sort_dir' => $filters['sort'] === 'date' && $filters['sort_dir'] === 'desc' ? 'asc' : 'desc'])) ?>" 
                                           class="btn btn-outline-secondary <?= $filters['sort'] === 'date' ? 'active' : '' ?>">
                                            Date <?= $filters['sort'] === 'date' ? ($filters['sort_dir'] === 'desc' ? '↓' : '↑') : '' ?>
                                        </a>
                                        <a href="?<?= buildQueryString(array_merge($filters, ['sort' => 'outcome', 'sort_dir' => $filters['sort'] === 'outcome' && $filters['sort_dir'] === 'desc' ? 'asc' : 'desc'])) ?>" 
                                           class="btn btn-outline-secondary <?= $filters['sort'] === 'outcome' ? 'active' : '' ?>">
                                            Outcome <?= $filters['sort'] === 'outcome' ? ($filters['sort_dir'] === 'desc' ? '↓' : '↑') : '' ?>
                                        </a>
                                        <a href="?<?= buildQueryString(array_merge($filters, ['sort' => 'rrr', 'sort_dir' => $filters['sort'] === 'rrr' && $filters['sort_dir'] === 'desc' ? 'asc' : 'desc'])) ?>" 
                                           class="btn btn-outline-secondary <?= $filters['sort'] === 'rrr' ? 'active' : '' ?>">
                                            RRR <?= $filters['sort'] === 'rrr' ? ($filters['sort_dir'] === 'desc' ? '↓' : '↑') : '' ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover trade-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Strategy</th>
                                            <th>Instrument</th>
                                            <th>Direction</th>
                                            <th>Entry</th>
                                            <th>SL</th>
                                            <th>TP</th>
                                            <th>RRR</th>
                                            <th>Outcome</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($trades as $trade): ?>
                                            <tr>
                                                <td>
                                                    <?= formatDate($trade['date']) ?>
                                                    <?php if ($trade['entry_time']): ?>
                                                        <br><small class="text-body-tertiary"><?= formatTime($trade['entry_time']) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($trade['strategy_name']): ?>
                                                        <a href="<?= BASE_URL ?>/views/strategies/view.php?id=<?= $trade['strategy_id'] ?>" 
                                                           class="text-decoration-none">
                                                            <?= sanitize($trade['strategy_name']) ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-body-tertiary">No strategy</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-phoenix-outline badge-phoenix-primary">
                                                        <?= sanitize($trade['instrument']) ?>
                                                    </span>
                                                    <br><small class="text-body-tertiary"><?= $trade['session'] ?></small>
                                                </td>
                                                <td>
                                                    <span class="trade-direction-<?= $trade['direction'] ?>">
                                                        <?= ucfirst($trade['direction']) ?>
                                                    </span>
                                                </td>
                                                <td><?= formatCurrency($trade['entry_price'], 5) ?></td>
                                                <td><?= formatCurrency($trade['sl'], 5) ?></td>
                                                <td><?= $trade['tp'] ? formatCurrency($trade['tp'], 5) : '-' ?></td>
                                                <td><?= $trade['rrr'] ? formatCurrency($trade['rrr']) : '-' ?></td>
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
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="<?= BASE_URL ?>/views/trades/view.php?id=<?= $trade['id'] ?>" 
                                                           class="btn btn-phoenix-primary btn-sm" title="View">
                                                            <span class="fas fa-eye"></span>
                                                        </a>
                                                        <a href="<?= BASE_URL ?>/views/trades/edit.php?id=<?= $trade['id'] ?>" 
                                                           class="btn btn-phoenix-secondary btn-sm" title="Edit">
                                                            <span class="fas fa-edit"></span>
                                                        </a>
                                                        <button type="button" 
                                                                class="btn btn-phoenix-danger btn-sm"
                                                                onclick="deleteTrade(<?= $trade['id'] ?>, '<?= formatDate($trade['date']) ?> <?= sanitize($trade['instrument']) ?>')"
                                                                title="Delete">
                                                            <span class="fas fa-trash"></span>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <?php if ($has_more || $page > 1): ?>
                        <div class="row mt-4">
                            <div class="col-12">
                                <nav aria-label="Trades pagination">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?= buildQueryString(array_merge($filters, ['page' => $page - 1])) ?>">Previous</a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <li class="page-item active">
                                            <span class="page-link">Page <?= $page ?></span>
                                        </li>
                                        
                                        <?php if ($has_more): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?= buildQueryString(array_merge($filters, ['page' => $page + 1])) ?>">Next</a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Trade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this trade?</p>
                <p class="text-danger">
                    <strong>Trade:</strong> <span id="tradeName"></span>
                </p>
                <p class="text-muted">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="<?= BASE_URL ?>/views/trades/delete.php" style="display: inline;">
                    <?= CSRF::getTokenField() ?>
                    <input type="hidden" name="id" id="deleteTradeId">
                    <button type="submit" class="btn btn-danger">Delete Trade</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$additional_js = ['/assets/js/trades.js'];
include __DIR__ . '/../layouts/footer.php';
?>

<script>
function deleteTrade(id, name) {
    document.getElementById('tradeName').textContent = name;
    document.getElementById('deleteTradeId').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
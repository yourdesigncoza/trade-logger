<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Strategy.php';

requireLogin();

$strategy_model = new Strategy();
$strategy_id = (int)($_GET['id'] ?? 0);

if (!$strategy_id) {
    flashMessage('error', 'Invalid strategy ID');
    redirect('/views/strategies/');
}

$stats = $strategy_model->getStrategyStats($strategy_id, $_SESSION['user_id']);

if (!$stats) {
    flashMessage('error', 'Strategy not found');
    redirect('/views/strategies/');
}

$strategy = $stats['strategy'];

$page_title = sanitize($strategy['name']) . ' - Trade Logger';
include __DIR__ . '/../layouts/header.php';
?>

<div class="pb-9">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/views/strategies/">Strategies</a></li>
                                    <li class="breadcrumb-item active" aria-current="page"><?= sanitize($strategy['name']) ?></li>
                                </ol>
                            </nav>
                            <h3 class="page-header-title"><?= sanitize($strategy['name']) ?></h3>
                            <p class="page-header-text mb-0">Strategy details and performance</p>
                        </div>
                        <div class="col-auto">
                            <div class="btn-group" role="group">
                                <a href="<?= BASE_URL ?>/views/strategies/edit.php?id=<?= $strategy['id'] ?>" 
                                   class="btn btn-phoenix-primary">
                                    <span class="fas fa-edit me-2"></span>Edit Strategy
                                </a>
                                <a href="<?= BASE_URL ?>/views/trades/create.php?strategy_id=<?= $strategy['id'] ?>" 
                                   class="btn btn-phoenix-success">
                                    <span class="fas fa-plus me-2"></span>Log Trade
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Strategy Overview -->
            <div class="col-12 col-lg-8">
                <div class="card mb-4">
                    <?php if ($strategy['chart_image_path']): ?>
                        <div class="card-img-top" style="height: 300px; overflow: hidden;">
                            <img src="<?= BASE_URL ?>/uploads/<?= sanitize($strategy['chart_image_path']) ?>" 
                                 alt="Strategy Chart" 
                                 class="w-100 h-100" 
                                 style="object-fit: cover;">
                        </div>
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <h5 class="card-title"><?= sanitize($strategy['name']) ?></h5>
                                <?php if ($strategy['description']): ?>
                                    <p class="card-text"><?= nl2br(sanitize($strategy['description'])) ?></p>
                                <?php endif; ?>
                            </div>

                            <?php if ($strategy['instrument']): ?>
                                <div class="col-12 col-md-6">
                                    <h6 class="text-body-secondary">Primary Instrument</h6>
                                    <span class="badge badge-phoenix-primary fs-8"><?= sanitize($strategy['instrument']) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($strategy['timeframes'])): ?>
                                <div class="col-12 col-md-6">
                                    <h6 class="text-body-secondary">Timeframes</h6>
                                    <div>
                                        <?php foreach ($strategy['timeframes'] as $tf): ?>
                                            <span class="badge badge-phoenix-outline badge-phoenix-info me-1"><?= sanitize($tf) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($strategy['sessions'])): ?>
                                <div class="col-12 col-md-6">
                                    <h6 class="text-body-secondary">Trading Sessions</h6>
                                    <div>
                                        <?php foreach ($strategy['sessions'] as $session): ?>
                                            <span class="badge badge-phoenix-outline badge-phoenix-secondary me-1"><?= sanitize($session) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="col-12 col-md-6">
                                <h6 class="text-body-secondary">Created</h6>
                                <p class="mb-0"><?= formatDate($strategy['created_at']) ?> (<?= timeAgo($strategy['created_at']) ?>)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Strategy Conditions -->
                <?php if (!empty($strategy['conditions'])): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Strategy Conditions</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($strategy['conditions'] as $condition): ?>
                                <div class="condition-item condition-type-<?= $condition['type'] ?> mb-3">
                                    <div class="d-flex align-items-start">
                                        <span class="badge badge-phoenix-<?= $condition['type'] === 'entry' ? 'success' : ($condition['type'] === 'exit' ? 'danger' : 'warning') ?> me-2 mt-1">
                                            <?= ucfirst($condition['type']) ?>
                                        </span>
                                        <p class="mb-0"><?= nl2br(sanitize($condition['description'])) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Recent Trades -->
                <?php if (!empty($stats['recent_trades'])): ?>
                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="card-title mb-0">Recent Trades</h5>
                                </div>
                                <div class="col-auto">
                                    <a href="<?= BASE_URL ?>/views/trades/?strategy_id=<?= $strategy['id'] ?>" 
                                       class="btn btn-link btn-sm">View All</a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm trade-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Instrument</th>
                                            <th>Direction</th>
                                            <th>Entry</th>
                                            <th>Outcome</th>
                                            <th>RRR</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stats['recent_trades'] as $trade): ?>
                                            <tr>
                                                <td><?= formatDate($trade['date']) ?></td>
                                                <td><?= sanitize($trade['instrument']) ?></td>
                                                <td>
                                                    <span class="trade-direction-<?= $trade['direction'] ?>">
                                                        <?= ucfirst($trade['direction']) ?>
                                                    </span>
                                                </td>
                                                <td><?= formatCurrency($trade['entry_price'], 5) ?></td>
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
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Statistics Sidebar -->
            <div class="col-12 col-lg-4">
                <!-- Performance Stats -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <h4 class="text-primary mb-0"><?= $stats['total_trades'] ?></h4>
                                    <small class="text-body-tertiary">Total Trades</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h4 class="text-success mb-0"><?= number_format($stats['win_rate'], 1) ?>%</h4>
                                <small class="text-body-tertiary">Win Rate</small>
                            </div>
                        </div>
                        
                        <?php if ($stats['avg_rrr'] > 0): ?>
                            <hr class="my-3">
                            <div class="text-center">
                                <h5 class="text-info mb-0"><?= formatCurrency($stats['avg_rrr']) ?></h5>
                                <small class="text-body-tertiary">Average RRR</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Trade Distribution -->
                <?php if ($stats['total_trades'] > 0): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Trade Distribution</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="height: 250px;">
                                <canvas id="strategyChart"></canvas>
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
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="<?= BASE_URL ?>/views/trades/create.php?strategy_id=<?= $strategy['id'] ?>" 
                               class="btn btn-phoenix-primary">
                                <span class="fas fa-plus me-2"></span>Log New Trade
                            </a>
                            <a href="<?= BASE_URL ?>/views/strategies/edit.php?id=<?= $strategy['id'] ?>" 
                               class="btn btn-phoenix-secondary">
                                <span class="fas fa-edit me-2"></span>Edit Strategy
                            </a>
                            <a href="<?= BASE_URL ?>/views/trades/?strategy_id=<?= $strategy['id'] ?>" 
                               class="btn btn-phoenix-info">
                                <span class="fas fa-list me-2"></span>View All Trades
                            </a>
                            <hr>
                            <button type="button" 
                                    class="btn btn-phoenix-danger"
                                    onclick="deleteStrategy(<?= $strategy['id'] ?>, '<?= sanitize($strategy['name']) ?>')">
                                <span class="fas fa-trash me-2"></span>Delete Strategy
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Strategy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the strategy "<span id="strategyName"></span>"?</p>
                <p class="text-danger">
                    <strong>Warning:</strong> This will remove the strategy from all associated trades, but the trades themselves will not be deleted.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="<?= BASE_URL ?>/views/strategies/delete.php" style="display: inline;">
                    <?= CSRF::getTokenField() ?>
                    <input type="hidden" name="id" id="deleteStrategyId">
                    <button type="submit" class="btn btn-danger">Delete Strategy</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$additional_js = ['/assets/js/strategies.js'];
include __DIR__ . '/../layouts/footer.php';
?>

<script>
function deleteStrategy(id, name) {
    document.getElementById('strategyName').textContent = name;
    document.getElementById('deleteStrategyId').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Initialize strategy chart if there are trades
<?php if ($stats['total_trades'] > 0): ?>
document.addEventListener('DOMContentLoaded', function() {
    StrategyManager.initializeStrategyChart(<?= json_encode($stats) ?>);
});
<?php endif; ?>
</script>
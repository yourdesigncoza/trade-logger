<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Trade.php';

requireLogin();

$trade_model = new Trade();
$trade_id = (int)($_GET['id'] ?? 0);

if (!$trade_id) {
    flashMessage('error', 'Invalid trade ID');
    redirect('/views/trades/');
}

$trade = $trade_model->getById($trade_id, $_SESSION['user_id']);

if (!$trade) {
    flashMessage('error', 'Trade not found');
    redirect('/views/trades/');
}

// Calculate percentage return if trade is closed
$percentage_return = null;
if ($trade['outcome'] && $trade['exit_time']) {
    // This is a simplified calculation - in real trading you'd need position size
    $price_diff = $trade['direction'] === 'long' 
        ? ($trade['tp'] ?: $trade['entry_price']) - $trade['entry_price']
        : $trade['entry_price'] - ($trade['tp'] ?: $trade['entry_price']);
    
    $percentage_return = ($price_diff / $trade['entry_price']) * 100;
}

$page_title = formatDate($trade['date']) . ' ' . sanitize($trade['instrument']) . ' - Trade Logger';
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
                                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/views/trades/">Trades</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">
                                        <?= formatDate($trade['date']) ?> <?= sanitize($trade['instrument']) ?>
                                    </li>
                                </ol>
                            </nav>
                            <h3 class="page-header-title">
                                Trade Details
                                <span class="badge trade-status-<?= strtolower(str_replace('-', '-', $trade['outcome'] ?: $trade['status'])) ?> ms-2">
                                    <?= sanitize($trade['outcome'] ?: ucfirst($trade['status'])) ?>
                                </span>
                            </h3>
                            <p class="page-header-text mb-0">
                                <?= formatDate($trade['date']) ?> • <?= sanitize($trade['instrument']) ?> • 
                                <span class="trade-direction-<?= $trade['direction'] ?>"><?= ucfirst($trade['direction']) ?></span>
                            </p>
                        </div>
                        <div class="col-auto">
                            <div class="btn-group" role="group">
                                <a href="<?= BASE_URL ?>/views/trades/edit.php?id=<?= $trade['id'] ?>" 
                                   class="btn btn-phoenix-primary">
                                    <span class="fas fa-edit me-2"></span>Edit Trade
                                </a>
                                <a href="<?= BASE_URL ?>/views/trades/" class="btn btn-phoenix-secondary">
                                    <span class="fas fa-list me-2"></span>All Trades
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Trade Overview -->
            <div class="col-12 col-lg-8">
                <div class="card mb-4">
                    <?php if ($trade['screenshot_path']): ?>
                        <div class="card-img-top" style="height: 400px; overflow: hidden;">
                            <img src="<?= BASE_URL ?>/uploads/<?= sanitize($trade['screenshot_path']) ?>" 
                                 alt="Trade Screenshot" 
                                 class="w-100 h-100" 
                                 style="object-fit: cover; cursor: pointer;"
                                 onclick="showImageModal('<?= BASE_URL ?>/uploads/<?= sanitize($trade['screenshot_path']) ?>')">
                        </div>
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <div class="row g-4">
                            <!-- Basic Info -->
                            <div class="col-12 col-md-6">
                                <h5 class="card-title mb-3">Trade Information</h5>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="text-body-secondary small">Date</div>
                                        <div class="fw-medium"><?= formatDate($trade['date']) ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-body-secondary small">Instrument</div>
                                        <div class="fw-medium">
                                            <span class="badge badge-phoenix-primary"><?= sanitize($trade['instrument']) ?></span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-body-secondary small">Session</div>
                                        <div class="fw-medium"><?= sanitize($trade['session']) ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-body-secondary small">Direction</div>
                                        <div class="fw-medium">
                                            <span class="trade-direction-<?= $trade['direction'] ?>">
                                                <?= ucfirst($trade['direction']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php if ($trade['entry_time']): ?>
                                        <div class="col-6">
                                            <div class="text-body-secondary small">Entry Time</div>
                                            <div class="fw-medium"><?= formatTime($trade['entry_time']) ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($trade['exit_time']): ?>
                                        <div class="col-6">
                                            <div class="text-body-secondary small">Exit Time</div>
                                            <div class="fw-medium"><?= formatTime($trade['exit_time']) ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Price Levels -->
                            <div class="col-12 col-md-6">
                                <h5 class="card-title mb-3">Price Levels</h5>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="text-body-secondary small">Entry Price</div>
                                        <div class="fw-medium"><?= formatCurrency($trade['entry_price'], 5) ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-body-secondary small">Stop Loss</div>
                                        <div class="fw-medium text-danger"><?= formatCurrency($trade['sl'], 5) ?></div>
                                    </div>
                                    <?php if ($trade['tp']): ?>
                                        <div class="col-6">
                                            <div class="text-body-secondary small">Take Profit</div>
                                            <div class="fw-medium text-success"><?= formatCurrency($trade['tp'], 5) ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($trade['rrr']): ?>
                                        <div class="col-6">
                                            <div class="text-body-secondary small">Risk:Reward</div>
                                            <div class="fw-medium"><?= formatCurrency($trade['rrr']) ?>:1</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Strategy Link -->
                            <?php if ($trade['strategy_name']): ?>
                                <div class="col-12">
                                    <div class="border-top pt-3">
                                        <h6 class="text-body-secondary mb-2">Linked Strategy</h6>
                                        <a href="<?= BASE_URL ?>/views/strategies/view.php?id=<?= $trade['strategy_id'] ?>" 
                                           class="btn btn-outline-secondary btn-sm">
                                            <span class="fas fa-layer-group me-1"></span>
                                            <?= sanitize($trade['strategy_name']) ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Notes -->
                            <?php if ($trade['notes']): ?>
                                <div class="col-12">
                                    <div class="<?= $trade['strategy_name'] ? '' : 'border-top pt-3' ?>">
                                        <h6 class="text-body-secondary mb-2">Trade Notes</h6>
                                        <div class="bg-light rounded p-3">
                                            <?= nl2br(sanitize($trade['notes'])) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Trade Timeline -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Trade Timeline</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Trade Created</h6>
                                    <p class="text-body-secondary mb-0">
                                        <?= formatDate($trade['created_at']) ?> <?= formatTime($trade['created_at']) ?>
                                    </p>
                                </div>
                            </div>

                            <?php if ($trade['entry_time']): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-info"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">Entry Executed</h6>
                                        <p class="text-body-secondary mb-0">
                                            <?= formatDate($trade['date']) ?> <?= formatTime($trade['entry_time']) ?> 
                                            at <?= formatCurrency($trade['entry_price'], 5) ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($trade['exit_time'] && $trade['outcome']): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-<?= $trade['outcome'] === 'Win' ? 'success' : ($trade['outcome'] === 'Loss' ? 'danger' : 'warning') ?>"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">Trade Closed - <?= sanitize($trade['outcome']) ?></h6>
                                        <p class="text-body-secondary mb-0">
                                            <?= formatDate($trade['date']) ?> <?= formatTime($trade['exit_time']) ?>
                                            <?php if ($percentage_return !== null): ?>
                                                • <?= $percentage_return > 0 ? '+' : '' ?><?= number_format($percentage_return, 2) ?>%
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($trade['updated_at'] && $trade['updated_at'] !== $trade['created_at']): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-secondary"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">Last Updated</h6>
                                        <p class="text-body-secondary mb-0">
                                            <?= formatDate($trade['updated_at']) ?> <?= formatTime($trade['updated_at']) ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-12 col-lg-4">
                <!-- Quick Stats -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Trade Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <div class="h4 mb-0 <?= $trade['outcome'] === 'Win' ? 'text-success' : ($trade['outcome'] === 'Loss' ? 'text-danger' : 'text-warning') ?>">
                                        <?= $trade['outcome'] ?: ucfirst($trade['status']) ?>
                                    </div>
                                    <small class="text-body-tertiary">Outcome</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="h4 mb-0 text-info">
                                    <?= $trade['rrr'] ? formatCurrency($trade['rrr']) . ':1' : 'N/A' ?>
                                </div>
                                <small class="text-body-tertiary">RRR</small>
                            </div>
                        </div>

                        <?php if ($percentage_return !== null): ?>
                            <hr class="my-3">
                            <div class="text-center">
                                <div class="h5 mb-0 <?= $percentage_return > 0 ? 'text-success' : ($percentage_return < 0 ? 'text-danger' : 'text-warning') ?>">
                                    <?= $percentage_return > 0 ? '+' : '' ?><?= number_format($percentage_return, 2) ?>%
                                </div>
                                <small class="text-body-tertiary">Price Return</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Risk Analysis -->
                <?php if ($trade['tp'] && $trade['sl']): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Risk Analysis</h5>
                        </div>
                        <div class="card-body">
                            <?php 
                            $risk_distance = abs($trade['entry_price'] - $trade['sl']);
                            $reward_distance = abs($trade['tp'] - $trade['entry_price']);
                            $risk_pips = round($risk_distance * 10000, 1); // Simplified pip calculation
                            $reward_pips = round($reward_distance * 10000, 1);
                            ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span class="text-danger">Risk</span>
                                    <span><?= $risk_pips ?> pips</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-danger" style="width: <?= $risk_distance / ($risk_distance + $reward_distance) * 100 ?>%"></div>
                                </div>
                            </div>
                            <div class="mb-0">
                                <div class="d-flex justify-content-between">
                                    <span class="text-success">Reward</span>
                                    <span><?= $reward_pips ?> pips</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: <?= $reward_distance / ($risk_distance + $reward_distance) * 100 ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="<?= BASE_URL ?>/views/trades/edit.php?id=<?= $trade['id'] ?>" 
                               class="btn btn-phoenix-primary">
                                <span class="fas fa-edit me-2"></span>Edit Trade
                            </a>
                            
                            <?php if ($trade['strategy_id']): ?>
                                <a href="<?= BASE_URL ?>/views/strategies/view.php?id=<?= $trade['strategy_id'] ?>" 
                                   class="btn btn-phoenix-secondary">
                                    <span class="fas fa-layer-group me-2"></span>View Strategy
                                </a>
                            <?php endif; ?>
                            
                            <a href="<?= BASE_URL ?>/views/trades/?instrument=<?= urlencode($trade['instrument']) ?>" 
                               class="btn btn-phoenix-info">
                                <span class="fas fa-filter me-2"></span>Similar Trades
                            </a>
                            
                            <hr>
                            
                            <button type="button" 
                                    class="btn btn-phoenix-danger"
                                    onclick="deleteTrade(<?= $trade['id'] ?>, '<?= formatDate($trade['date']) ?> <?= sanitize($trade['instrument']) ?>')">
                                <span class="fas fa-trash me-2"></span>Delete Trade
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Trade Screenshot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" class="img-fluid" alt="Trade Screenshot">
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

<style>
.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 0.75rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--phoenix-gray-300);
}

.timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
}

.timeline-marker {
    position: absolute;
    left: -1.9rem;
    top: 0.2rem;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid white;
}

.timeline-content {
    background: var(--phoenix-gray-50);
    padding: 1rem;
    border-radius: 0.375rem;
    border-left: 3px solid var(--phoenix-primary);
}
</style>

<?php
$additional_js = ['/assets/js/trades.js'];
include __DIR__ . '/../layouts/footer.php';
?>

<script>
function showImageModal(imageSrc) {
    document.getElementById('modalImage').src = imageSrc;
    new bootstrap.Modal(document.getElementById('imageModal')).show();
}

function deleteTrade(id, name) {
    document.getElementById('tradeName').textContent = name;
    document.getElementById('deleteTradeId').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
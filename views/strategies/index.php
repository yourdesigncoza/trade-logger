<?php
// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/strategies_errors.log');

try {
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../models/Strategy.php';
    require_once __DIR__ . '/../../includes/csrf.php';

    requireLogin();

    $current_user = getCurrentUser();
    if (!$current_user) {
        throw new Exception("Unable to get current user");
    }

    $strategy_model = new Strategy();

    $page = max(1, (int)($_GET['page'] ?? 1));
    $per_page = 12;
    $offset = ($page - 1) * $per_page;

    // Get strategies with error handling
    $strategies = [];
    $has_more = false;
    $strategy_count = 0;
    
    try {
        $strategies = $strategy_model->getByUserId($_SESSION['user_id'], $per_page + 1, $offset);
        $has_more = count($strategies) > $per_page;
        if ($has_more) {
            array_pop($strategies);
        }
        
        $strategy_count = $strategy_model->getUserStrategyCount($_SESSION['user_id']);
        error_log("Strategies: Successfully loaded " . count($strategies) . " strategies for user " . $_SESSION['user_id']);
    } catch (Exception $e) {
        error_log("Strategies: Error loading strategies - " . $e->getMessage());
        flashMessage('error', 'Unable to load strategies at this time.');
        $strategies = [];
        $strategy_count = 0;
    }

    $page_title = 'My Strategies - Trade Logger';
    
} catch (Exception $e) {
    error_log("Strategies: Critical error - " . $e->getMessage());
    
    // Set fallback values
    $strategies = [];
    $strategy_count = 0;
    $has_more = false;
    $page = 1;
    $current_user = ['strategy_limit' => DEFAULT_STRATEGY_LIMIT];
    $page_title = 'My Strategies - Trade Logger';
    
    flashMessage('error', 'An error occurred while loading the strategies page.');
}

include __DIR__ . '/../layouts/header.php';
?>

<div class="pb-9">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="page-header-title">My Trading Strategies</h3>
                            <p class="page-header-text mb-0">
                                Manage your trading strategies (<?= $strategy_count ?>/<?= $current_user['strategy_limit'] ?> used)
                            </p>
                        </div>
                        <div class="col-auto">
                            <?php if ($strategy_count < $current_user['strategy_limit']): ?>
                                <a href="<?= BASE_URL ?>/views/strategies/create.php" class="btn btn-primary">
                                    <span class="fas fa-plus me-2"></span>Create Strategy
                                </a>
                            <?php else: ?>
                                <span class="text-warning">
                                    <span class="fas fa-exclamation-triangle me-1"></span>
                                    Strategy limit reached
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($strategy_count >= $current_user['strategy_limit']): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="strategy-limit-warning">
                        <div class="d-flex align-items-center">
                            <span class="fas fa-exclamation-triangle me-2"></span>
                            <div>
                                <strong>Strategy Limit Reached</strong>
                                <p class="mb-0">You have reached your limit of <?= $current_user['strategy_limit'] ?> strategies. Contact an administrator to increase your limit.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($strategies)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <div class="mb-4">
                                <span class="fas fa-layer-group fs-1 text-body-tertiary"></span>
                            </div>
                            <h4 class="text-body-tertiary">No strategies yet</h4>
                            <p class="text-body-tertiary mb-4">Create your first trading strategy to get started</p>
                            <?php if ($strategy_count < $current_user['strategy_limit']): ?>
                                <a href="<?= BASE_URL ?>/views/strategies/create.php" class="btn btn-primary">
                                    <span class="fas fa-plus me-2"></span>Create Your First Strategy
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($strategies as $strategy): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card strategy-card h-100">
                            <?php if ($strategy['chart_image_path']): ?>
                                <div class="card-img-top" style="height: 200px; overflow: hidden;">
                                    <img src="<?= BASE_URL ?>/uploads/<?= sanitize($strategy['chart_image_path']) ?>" 
                                         alt="Strategy Chart" 
                                         class="w-100 h-100" 
                                         style="object-fit: cover;">
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-body d-flex flex-column">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title mb-0"><?= sanitize($strategy['name']) ?></h5>
                                        <span class="badge badge-phoenix-primary"><?= $strategy['trade_count'] ?> trades</span>
                                    </div>
                                    
                                    <?php if ($strategy['instrument']): ?>
                                        <p class="text-body-secondary mb-2">
                                            <span class="fas fa-chart-line me-1"></span>
                                            <?= sanitize($strategy['instrument']) ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if ($strategy['description']): ?>
                                        <p class="card-text text-body-tertiary">
                                            <?= sanitize(substr($strategy['description'], 0, 120)) ?>
                                            <?= strlen($strategy['description']) > 120 ? '...' : '' ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <?php if (!empty($strategy['timeframes'])): ?>
                                        <div class="mb-2">
                                            <small class="text-body-secondary d-block mb-1">Timeframes:</small>
                                            <?php foreach ($strategy['timeframes'] as $tf): ?>
                                                <span class="badge badge-phoenix-outline badge-phoenix-info me-1"><?= sanitize($tf) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($strategy['sessions'])): ?>
                                        <div>
                                            <small class="text-body-secondary d-block mb-1">Sessions:</small>
                                            <?php foreach ($strategy['sessions'] as $session): ?>
                                                <span class="badge badge-phoenix-outline badge-phoenix-secondary me-1"><?= sanitize($session) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-body-tertiary">
                                            Created <?= timeAgo($strategy['created_at']) ?>
                                        </small>
                                        
                                        <div class="btn-group" role="group">
                                            <a href="<?= BASE_URL ?>/views/strategies/view.php?id=<?= $strategy['id'] ?>" 
                                               class="btn btn-phoenix-primary btn-sm">
                                                <span class="fas fa-eye"></span>
                                            </a>
                                            <a href="<?= BASE_URL ?>/views/strategies/edit.php?id=<?= $strategy['id'] ?>" 
                                               class="btn btn-phoenix-secondary btn-sm">
                                                <span class="fas fa-edit"></span>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-phoenix-danger btn-sm"
                                                    onclick="deleteStrategy(<?= $strategy['id'] ?>, '<?= sanitize($strategy['name']) ?>')">
                                                <span class="fas fa-trash"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($has_more || $page > 1): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <nav aria-label="Strategies pagination">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                                    </li>
                                <?php endif; ?>
                                
                                <li class="page-item active">
                                    <span class="page-link">Page <?= $page ?></span>
                                </li>
                                
                                <?php if ($has_more): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
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
                    <?php 
                    try {
                        if (class_exists('CSRF')) {
                            echo CSRF::getTokenField();
                        } else {
                            // Generate a simple token if CSRF class is not available
                            $token = bin2hex(random_bytes(32));
                            $_SESSION['csrf_token'] = $token;
                            echo '<input type="hidden" name="csrf_token" value="' . $token . '">';
                        }
                    } catch (Exception $e) {
                        error_log("Strategies: CSRF token error - " . $e->getMessage());
                    }
                    ?>
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
</script>
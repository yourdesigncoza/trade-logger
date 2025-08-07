<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Strategy.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireLogin();

$current_user = getCurrentUser();
$strategy_model = new Strategy();

$strategy_count = $strategy_model->getUserStrategyCount($_SESSION['user_id']);
$strategy_count = (int)$strategy_count;
if ($strategy_count >= $current_user['strategy_limit']) {
    flashMessage('error', 'You have reached your strategy limit. Contact admin to increase your limit.');
    redirect('/views/strategies/');
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (class_exists('CSRF')) {
            CSRF::validateRequest();
        } else {
            // Simple CSRF validation if class is not available
            if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
                $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                throw new Exception('Invalid CSRF token');
            }
        }
    } catch (Exception $e) {
        error_log("Strategies Create: CSRF validation error - " . $e->getMessage());
        $error = 'Security validation failed. Please try again.';
    }
    
    if (!isset($error)) {
    
    try {
        $data = [
            'name' => sanitize($_POST['name'] ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'instrument' => sanitize($_POST['instrument'] ?? ''),
            'timeframes' => $_POST['timeframes'] ?? [],
            'sessions' => $_POST['sessions'] ?? [],
            'conditions' => []
        ];
        
        if (!empty($_POST['conditions'])) {
            foreach ($_POST['conditions'] as $condition) {
                if (!empty($condition['type']) && !empty($condition['description'])) {
                    $data['conditions'][] = [
                        'type' => sanitize($condition['type']),
                        'description' => sanitize($condition['description'])
                    ];
                }
            }
        }
        
        if (isset($_FILES['chart_image']) && $_FILES['chart_image']['error'] === UPLOAD_ERR_OK) {
            $image_path = uploadImage($_FILES['chart_image'], 'strategies');
            if ($image_path) {
                $data['chart_image_path'] = $image_path;
            }
        }
        
        $strategy_id = $strategy_model->create($_SESSION['user_id'], $data);
        
        flashMessage('success', 'Strategy created successfully!');
        redirect('/views/strategies/view.php?id=' . $strategy_id);
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    } // Close the if (!isset($error)) block
}

$timeframe_options = $strategy_model->getTimeframeOptions();
$session_options = $strategy_model->getSessionOptions();
$instrument_options = $strategy_model->getInstrumentOptions();

$page_title = 'Create Strategy - Trade Logger';
include __DIR__ . '/../layouts/header.php';
?>

<div class="pb-9">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="page-header-title">Create New Strategy</h3>
                            <p class="page-header-text mb-0">
                                Define your trading strategy (<?= (int)$strategy_count + 1 ?>/<?= (int)($current_user['strategy_limit'] ?? DEFAULT_STRATEGY_LIMIT) ?>)
                            </p>
                        </div>
                        <div class="col-auto">
                            <a href="<?= BASE_URL ?>/views/strategies/" class="btn btn-phoenix-secondary">
                                <span class="fas fa-arrow-left me-2"></span>Back to Strategies
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="row mb-3">
                <div class="col-12">
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <span class="fas fa-exclamation-triangle me-2"></span>
                        <?= sanitize($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" data-validate novalidate>
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
                error_log("Strategies Create: CSRF token error - " . $e->getMessage());
            }
            ?>
            
            <div class="row">
                <div class="col-12 col-lg-8">
                    <!-- Basic Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Basic Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="name">Strategy Name <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="name" 
                                           name="name" 
                                           placeholder="Enter strategy name"
                                           value="<?= sanitize($_POST['name'] ?? '') ?>"
                                           maxlength="100"
                                           required>
                                    <div class="invalid-feedback">Please enter a strategy name (3-100 characters).</div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="description">Description</label>
                                    <textarea class="form-control" 
                                              id="description" 
                                              name="description" 
                                              rows="4" 
                                              placeholder="Describe your trading strategy..."
                                              maxlength="1000"><?= sanitize($_POST['description'] ?? '') ?></textarea>
                                    <div class="form-text">Optional: Describe the key aspects of your strategy (max 1000 characters)</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="instrument">Primary Instrument</label>
                                    <select class="form-select" id="instrument" name="instrument">
                                        <option value="">Select instrument (optional)</option>
                                        <?php foreach ($instrument_options as $instrument): ?>
                                            <option value="<?= $instrument ?>" 
                                                    <?= ($_POST['instrument'] ?? '') === $instrument ? 'selected' : '' ?>>
                                                <?= $instrument ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Timeframes and Sessions -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Trading Parameters</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Timeframes</label>
                                    <div class="timeframe-options">
                                        <?php foreach ($timeframe_options as $value => $label): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       id="timeframe_<?= $value ?>" 
                                                       name="timeframes[]" 
                                                       value="<?= $value ?>"
                                                       <?= in_array($value, $_POST['timeframes'] ?? []) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="timeframe_<?= $value ?>">
                                                    <?= $label ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label">Trading Sessions</label>
                                    <div class="session-options">
                                        <?php foreach ($session_options as $value => $label): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       id="session_<?= $value ?>" 
                                                       name="sessions[]" 
                                                       value="<?= $value ?>"
                                                       <?= in_array($value, $_POST['sessions'] ?? []) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="session_<?= $value ?>">
                                                    <?= $label ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Strategy Conditions -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="card-title mb-0">Strategy Conditions</h5>
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-phoenix-primary btn-sm" id="addCondition">
                                        <span class="fas fa-plus me-1"></span>Add Condition
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="conditionsContainer">
                                <!-- Conditions will be added here dynamically -->
                            </div>
                            <div class="text-muted">
                                <small>Define the specific conditions for your strategy entry, exit, and invalidation rules.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <!-- Chart Image Upload -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Strategy Chart</h5>
                        </div>
                        <div class="card-body">
                            <div class="upload-zone mb-3" id="chartUploadZone">
                                <div class="text-center">
                                    <span class="fas fa-cloud-upload-alt fs-1 text-body-tertiary mb-2"></span>
                                    <p class="mb-2">Drop chart image here or click to browse</p>
                                    <p class="text-body-tertiary fs-9 mb-0">Maximum 4MB • JPG, PNG, GIF</p>
                                </div>
                                <input type="file" 
                                       class="form-control d-none" 
                                       id="chart_image" 
                                       name="chart_image" 
                                       accept="image/*">
                            </div>
                            <div id="imagePreview" class="d-none">
                                <img id="previewImg" class="img-fluid rounded mb-2" alt="Chart preview">
                                <button type="button" class="btn btn-phoenix-danger btn-sm w-100" id="removeImage">
                                    <span class="fas fa-trash me-1"></span>Remove Image
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="card">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100 mb-2">
                                <span class="fas fa-save me-2"></span>Create Strategy
                            </button>
                            <a href="<?= BASE_URL ?>/views/strategies/" class="btn btn-outline-secondary w-100">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
$additional_js = ['/assets/js/strategies.js'];
include __DIR__ . '/../layouts/footer.php';
?>
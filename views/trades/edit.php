<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Trade.php';
require_once __DIR__ . '/../../models/Strategy.php';

requireLogin();

$trade_model = new Trade();
$strategy_model = new Strategy();

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

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validateRequest();
    
    try {
        $data = [
            'strategy_id' => (int)($_POST['strategy_id'] ?? 0) ?: null,
            'date' => sanitize($_POST['date'] ?? ''),
            'instrument' => sanitize($_POST['instrument'] ?? ''),
            'session' => sanitize($_POST['session'] ?? ''),
            'direction' => sanitize($_POST['direction'] ?? ''),
            'entry_time' => sanitize($_POST['entry_time'] ?? ''),
            'exit_time' => sanitize($_POST['exit_time'] ?? ''),
            'entry_price' => (float)($_POST['entry_price'] ?? 0),
            'sl' => (float)($_POST['sl'] ?? 0),
            'tp' => (float)($_POST['tp'] ?? 0) ?: null,
            'rrr' => (float)($_POST['rrr'] ?? 0) ?: null,
            'outcome' => sanitize($_POST['outcome'] ?? '') ?: null,
            'status' => sanitize($_POST['status'] ?? 'open'),
            'notes' => sanitize($_POST['notes'] ?? '') ?: null
        ];
        
        // Handle screenshot upload
        if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
            // Delete old screenshot if it exists
            if ($trade['screenshot_path']) {
                deleteFile($trade['screenshot_path']);
            }
            
            $image_path = uploadImage($_FILES['screenshot'], 'trades');
            if ($image_path) {
                $data['screenshot_path'] = $image_path;
            }
        }
        
        $trade_model->update($trade_id, $_SESSION['user_id'], $data);
        
        flashMessage('success', 'Trade updated successfully!');
        redirect('/views/trades/view.php?id=' . $trade_id);
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
} else {
    // Pre-populate form data with current trade values
    $_POST = [
        'strategy_id' => $trade['strategy_id'],
        'date' => $trade['date'],
        'instrument' => $trade['instrument'],
        'session' => $trade['session'],
        'direction' => $trade['direction'],
        'entry_time' => $trade['entry_time'],
        'exit_time' => $trade['exit_time'],
        'entry_price' => $trade['entry_price'],
        'sl' => $trade['sl'],
        'tp' => $trade['tp'],
        'rrr' => $trade['rrr'],
        'outcome' => $trade['outcome'],
        'status' => $trade['status'],
        'notes' => $trade['notes']
    ];
}

// Get form options
$user_strategies = $strategy_model->getStrategyOptions($_SESSION['user_id']);
$session_options = $trade_model->getSessionOptions();
$outcome_options = $trade_model->getOutcomeOptions();
$status_options = $trade_model->getStatusOptions();
$direction_options = $trade_model->getDirectionOptions();
$instrument_options = $strategy_model->getInstrumentOptions();

$page_title = 'Edit Trade - Trade Logger';
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
                                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/views/trades/view.php?id=<?= $trade['id'] ?>"><?= formatDate($trade['date']) ?> <?= sanitize($trade['instrument']) ?></a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                                </ol>
                            </nav>
                            <h3 class="page-header-title">Edit Trade</h3>
                            <p class="page-header-text mb-0">Update your trade information</p>
                        </div>
                        <div class="col-auto">
                            <div class="btn-group" role="group">
                                <a href="<?= BASE_URL ?>/views/trades/view.php?id=<?= $trade['id'] ?>" 
                                   class="btn btn-phoenix-secondary">
                                    <span class="fas fa-arrow-left me-2"></span>Back to Trade
                                </a>
                                <a href="<?= BASE_URL ?>/views/trades/" class="btn btn-phoenix-info">
                                    <span class="fas fa-list me-2"></span>All Trades
                                </a>
                            </div>
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
            <?= CSRF::getTokenField() ?>
            
            <div class="row">
                <div class="col-12 col-lg-8">
                    <!-- Basic Trade Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Trade Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="date">Trade Date <span class="text-danger">*</span></label>
                                    <input type="date" 
                                           class="form-control" 
                                           id="date" 
                                           name="date" 
                                           value="<?= $_POST['date'] ?>"
                                           max="<?= date('Y-m-d') ?>"
                                           required>
                                    <div class="invalid-feedback">Please enter a valid trade date.</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="strategy_id">Strategy</label>
                                    <select class="form-select" id="strategy_id" name="strategy_id">
                                        <option value="">No specific strategy</option>
                                        <?php foreach ($user_strategies as $strategy): ?>
                                            <option value="<?= $strategy['id'] ?>" 
                                                    <?= ($_POST['strategy_id'] ?? '') == $strategy['id'] ? 'selected' : '' ?>>
                                                <?= sanitize($strategy['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="instrument">Instrument <span class="text-danger">*</span></label>
                                    <select class="form-select" id="instrument" name="instrument" required>
                                        <option value="">Select instrument</option>
                                        <?php foreach ($instrument_options as $instrument): ?>
                                            <option value="<?= $instrument ?>" 
                                                    <?= ($_POST['instrument'] ?? '') === $instrument ? 'selected' : '' ?>>
                                                <?= $instrument ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Please select an instrument.</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="session">Trading Session <span class="text-danger">*</span></label>
                                    <select class="form-select" id="session" name="session" required>
                                        <option value="">Select session</option>
                                        <?php foreach ($session_options as $value => $label): ?>
                                            <option value="<?= $value ?>" 
                                                    <?= ($_POST['session'] ?? '') === $value ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Please select a trading session.</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="direction">Direction <span class="text-danger">*</span></label>
                                    <select class="form-select" id="direction" name="direction" required>
                                        <option value="">Select direction</option>
                                        <?php foreach ($direction_options as $value => $label): ?>
                                            <option value="<?= $value ?>" 
                                                    <?= ($_POST['direction'] ?? '') === $value ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Please select trade direction.</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="entry_time">Entry Time</label>
                                    <input type="time" 
                                           class="form-control" 
                                           id="entry_time" 
                                           name="entry_time" 
                                           value="<?= $_POST['entry_time'] ?? '' ?>">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="exit_time">Exit Time</label>
                                    <input type="time" 
                                           class="form-control" 
                                           id="exit_time" 
                                           name="exit_time" 
                                           value="<?= $_POST['exit_time'] ?? '' ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Price Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Price Levels</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label class="form-label" for="entry_price">Entry Price <span class="text-danger">*</span></label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="entry_price" 
                                           name="entry_price" 
                                           step="0.00001" 
                                           min="0.00001"
                                           placeholder="0.00000"
                                           value="<?= $_POST['entry_price'] ?? '' ?>"
                                           required>
                                    <div class="invalid-feedback">Please enter a valid entry price.</div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label" for="sl">Stop Loss <span class="text-danger">*</span></label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="sl" 
                                           name="sl" 
                                           step="0.00001" 
                                           min="0.00001"
                                           placeholder="0.00000"
                                           value="<?= $_POST['sl'] ?? '' ?>"
                                           required>
                                    <div class="invalid-feedback">Please enter a valid stop loss.</div>
                                    <div id="slValidationFeedback" class="invalid-feedback"></div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label" for="tp">Take Profit</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="tp" 
                                           name="tp" 
                                           step="0.00001" 
                                           min="0.00001"
                                           placeholder="0.00000"
                                           value="<?= $_POST['tp'] ?? '' ?>">
                                    <div id="tpValidationFeedback" class="invalid-feedback"></div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label" for="rrr">Risk:Reward Ratio</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="rrr" 
                                           name="rrr" 
                                           step="0.01" 
                                           min="0"
                                           placeholder="1.50"
                                           value="<?= $_POST['rrr'] ?? '' ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Trade Status -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Trade Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="status">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <?php foreach ($status_options as $value => $label): ?>
                                            <option value="<?= $value ?>" 
                                                    <?= ($_POST['status'] ?? 'open') === $value ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="outcome">Outcome</label>
                                    <select class="form-select" id="outcome" name="outcome">
                                        <option value="">Not determined yet</option>
                                        <?php foreach ($outcome_options as $value => $label): ?>
                                            <option value="<?= $value ?>" 
                                                    <?= ($_POST['outcome'] ?? '') === $value ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Trade Notes</h5>
                        </div>
                        <div class="card-body">
                            <textarea class="form-control" 
                                      id="notes" 
                                      name="notes" 
                                      rows="4" 
                                      placeholder="Add any notes about this trade..."
                                      maxlength="1000"><?= sanitize($_POST['notes'] ?? '') ?></textarea>
                            <div class="form-text">Optional: Analysis, reasons, observations (max 1000 characters)</div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <!-- Current Screenshot -->
                    <?php if ($trade['screenshot_path']): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Current Screenshot</h5>
                            </div>
                            <div class="card-body text-center">
                                <img src="<?= BASE_URL ?>/uploads/<?= sanitize($trade['screenshot_path']) ?>" 
                                     alt="Current Trade Screenshot" 
                                     class="img-fluid rounded mb-2">
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Screenshot Upload -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><?= $trade['screenshot_path'] ? 'Update Screenshot' : 'Add Screenshot' ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="upload-zone mb-3" id="screenshotUploadZone">
                                <div class="text-center">
                                    <span class="fas fa-camera fs-1 text-body-tertiary mb-2"></span>
                                    <p class="mb-2">Drop screenshot here or click to browse</p>
                                    <p class="text-body-tertiary fs-9 mb-0">Maximum 4MB • JPG, PNG, GIF</p>
                                </div>
                                <input type="file" 
                                       class="form-control d-none" 
                                       id="screenshot" 
                                       name="screenshot" 
                                       accept="image/*">
                            </div>
                            <div id="imagePreview" class="d-none">
                                <img id="previewImg" class="img-fluid rounded mb-2" alt="Screenshot preview">
                                <button type="button" class="btn btn-phoenix-danger btn-sm w-100" id="removeImage">
                                    <span class="fas fa-trash me-1"></span>Remove Screenshot
                                </button>
                            </div>
                            <?php if ($trade['screenshot_path']): ?>
                                <div class="form-text">Leave empty to keep current screenshot, or upload a new one to replace it.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="card">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100 mb-2">
                                <span class="fas fa-save me-2"></span>Update Trade
                            </button>
                            <a href="<?= BASE_URL ?>/views/trades/view.php?id=<?= $trade['id'] ?>" class="btn btn-outline-secondary w-100">
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
$additional_js = ['/assets/js/trades.js'];
include __DIR__ . '/../layouts/footer.php';
?>
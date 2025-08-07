<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Admin.php';

requireAdmin();

$admin = new Admin();

// Perform various health checks
$health_checks = [];

// Database connectivity check
try {
    $db_test = $admin->getSystemStats();
    $health_checks['database'] = [
        'status' => 'healthy',
        'message' => 'Database connection successful',
        'details' => 'Connected and responsive'
    ];
} catch (Exception $e) {
    $health_checks['database'] = [
        'status' => 'error',
        'message' => 'Database connection failed',
        'details' => $e->getMessage()
    ];
}

// Upload directory check
$upload_writable = is_writable(UPLOAD_PATH);
$health_checks['uploads'] = [
    'status' => $upload_writable ? 'healthy' : 'warning',
    'message' => $upload_writable ? 'Upload directory is writable' : 'Upload directory is not writable',
    'details' => 'Path: ' . UPLOAD_PATH
];

// Session configuration check
$session_healthy = session_status() === PHP_SESSION_ACTIVE;
$health_checks['sessions'] = [
    'status' => $session_healthy ? 'healthy' : 'error',
    'message' => $session_healthy ? 'Sessions are working' : 'Sessions are not active',
    'details' => 'Session ID: ' . session_id()
];

// Helper functions
function parseMemoryLimit($limit) {
    $limit = trim($limit);
    $last = strtolower($limit[strlen($limit) - 1]);
    $value = (int)$limit;
    
    switch($last) {
        case 'g': $value *= 1024;
        case 'm': $value *= 1024;
        case 'k': $value *= 1024;
    }
    
    return $value;
}

function formatBytes($size, $precision = 2) {
    if ($size <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $base = log($size, 1024);
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
}

// Memory usage check
$memory_limit = ini_get('memory_limit');
$memory_usage = memory_get_usage(true);
$memory_peak = memory_get_peak_usage(true);
$memory_percent = ($memory_usage / parseMemoryLimit($memory_limit)) * 100;

$health_checks['memory'] = [
    'status' => $memory_percent < 80 ? 'healthy' : ($memory_percent < 95 ? 'warning' : 'error'),
    'message' => 'Memory usage: ' . number_format($memory_percent, 1) . '%',
    'details' => 'Used: ' . formatBytes($memory_usage) . ' / Peak: ' . formatBytes($memory_peak) . ' / Limit: ' . $memory_limit
];

// Disk space check
$disk_total = disk_total_space('.');
$disk_free = disk_free_space('.');
$disk_used_percent = (($disk_total - $disk_free) / $disk_total) * 100;

$health_checks['disk_space'] = [
    'status' => $disk_used_percent < 85 ? 'healthy' : ($disk_used_percent < 95 ? 'warning' : 'error'),
    'message' => 'Disk usage: ' . number_format($disk_used_percent, 1) . '%',
    'details' => 'Free: ' . formatBytes($disk_free) . ' / Total: ' . formatBytes($disk_total)
];

// PHP version check
$php_version = PHP_VERSION;
$php_major_minor = (float)substr($php_version, 0, 3);
$health_checks['php_version'] = [
    'status' => $php_major_minor >= 7.4 ? 'healthy' : 'warning',
    'message' => 'PHP Version: ' . $php_version,
    'details' => $php_major_minor >= 8.0 ? 'Modern PHP version' : 'Consider upgrading to PHP 8.0+'
];

// Email configuration check (basic)
$email_configured = !empty(SMTP_HOST) && !empty(SMTP_USERNAME);
$health_checks['email'] = [
    'status' => $email_configured ? 'healthy' : 'warning',
    'message' => $email_configured ? 'Email configuration present' : 'Email not fully configured',
    'details' => $email_configured ? 'SMTP settings configured' : 'Check SMTP configuration in config.php'
];

// Storage usage
$storage = $admin->getStorageUsage();
$storage_mb = $storage['total_size_mb'];
$health_checks['storage'] = [
    'status' => $storage_mb < 500 ? 'healthy' : ($storage_mb < 1000 ? 'warning' : 'error'),
    'message' => 'Storage usage: ' . $storage_mb . ' MB',
    'details' => $storage['file_count'] . ' files uploaded'
];

// Calculate overall system status
$status_counts = array_count_values(array_column($health_checks, 'status'));
$overall_status = 'healthy';
if ($status_counts['error'] ?? 0 > 0) {
    $overall_status = 'error';
} elseif ($status_counts['warning'] ?? 0 > 0) {
    $overall_status = 'warning';
}

$page_title = 'Admin Panel - System Health';
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
                                <span class="fas fa-heartbeat text-primary me-2"></span>
                                System Health Monitor
                            </h3>
                            <p class="page-header-text mb-0">Real-time system status and health checks</p>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-outline-primary" onclick="location.reload()">
                                <span class="fas fa-sync-alt me-2"></span>Refresh
                            </button>
                            <a href="/views/admin/users.php" class="btn btn-secondary">
                                <span class="fas fa-arrow-left me-2"></span>Back to Admin
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overall System Status -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-<?= $overall_status === 'healthy' ? 'success' : ($overall_status === 'warning' ? 'warning' : 'danger') ?>">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="fas fa-<?= $overall_status === 'healthy' ? 'check-circle text-success' : ($overall_status === 'warning' ? 'exclamation-triangle text-warning' : 'times-circle text-danger') ?> fs-1"></span>
                            </div>
                            <div class="flex-grow-1">
                                <h4 class="mb-1">
                                    System Status: 
                                    <span class="text-<?= $overall_status === 'healthy' ? 'success' : ($overall_status === 'warning' ? 'warning' : 'danger') ?>">
                                        <?= ucfirst($overall_status) ?>
                                    </span>
                                </h4>
                                <p class="mb-0 text-body-tertiary">
                                    Last checked: <?= date('F j, Y \a\t g:i:s A') ?>
                                </p>
                            </div>
                            <div class="text-end">
                                <div class="d-flex gap-3">
                                    <div class="text-center">
                                        <div class="fs-4 text-success"><?= $status_counts['healthy'] ?? 0 ?></div>
                                        <small class="text-body-tertiary">Healthy</small>
                                    </div>
                                    <div class="text-center">
                                        <div class="fs-4 text-warning"><?= $status_counts['warning'] ?? 0 ?></div>
                                        <small class="text-body-tertiary">Warnings</small>
                                    </div>
                                    <div class="text-center">
                                        <div class="fs-4 text-danger"><?= $status_counts['error'] ?? 0 ?></div>
                                        <small class="text-body-tertiary">Errors</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Health Check Details -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">System Component Status</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="60">Status</th>
                                        <th>Component</th>
                                        <th>Message</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($health_checks as $component => $check): ?>
                                        <tr>
                                            <td class="text-center">
                                                <span class="fas fa-<?= $check['status'] === 'healthy' ? 'check-circle text-success' : ($check['status'] === 'warning' ? 'exclamation-triangle text-warning' : 'times-circle text-danger') ?> fs-5"></span>
                                            </td>
                                            <td>
                                                <div class="fw-medium"><?= ucwords(str_replace('_', ' ', $component)) ?></div>
                                            </td>
                                            <td>
                                                <span class="text-<?= $check['status'] === 'healthy' ? 'success' : ($check['status'] === 'warning' ? 'warning' : 'danger') ?>">
                                                    <?= sanitize($check['message']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-body-tertiary"><?= sanitize($check['details']) ?></small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <span class="fas fa-server me-2"></span>Server Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr>
                                <td><strong>Server Software:</strong></td>
                                <td><?= sanitize($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') ?></td>
                            </tr>
                            <tr>
                                <td><strong>PHP Version:</strong></td>
                                <td><?= PHP_VERSION ?></td>
                            </tr>
                            <tr>
                                <td><strong>Operating System:</strong></td>
                                <td><?= PHP_OS ?></td>
                            </tr>
                            <tr>
                                <td><strong>Server Time:</strong></td>
                                <td><?= date('Y-m-d H:i:s T') ?></td>
                            </tr>
                            <tr>
                                <td><strong>Timezone:</strong></td>
                                <td><?= date_default_timezone_get() ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <span class="fas fa-database me-2"></span>Database Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php try {
                            $pdo = new PDO(
                                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                                DB_USER,
                                DB_PASS
                            );
                            $db_version = $pdo->query('SELECT VERSION()')->fetchColumn();
                            $db_charset = $pdo->query('SELECT @@character_set_database')->fetchColumn();
                            $db_collation = $pdo->query('SELECT @@collation_database')->fetchColumn();
                        ?>
                            <table class="table table-sm mb-0">
                                <tr>
                                    <td><strong>Database:</strong></td>
                                    <td><?= sanitize(DB_NAME) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>MySQL Version:</strong></td>
                                    <td><?= sanitize($db_version) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Character Set:</strong></td>
                                    <td><?= sanitize($db_charset) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Collation:</strong></td>
                                    <td><?= sanitize($db_collation) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Connection Status:</strong></td>
                                    <td><span class="text-success">Connected</span></td>
                                </tr>
                            </table>
                        <?php } catch (Exception $e): ?>
                            <div class="alert alert-danger mb-0">
                                <strong>Database Connection Error:</strong><br>
                                <?= sanitize($e->getMessage()) ?>
                            </div>
                        <?php endtry; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- PHP Configuration -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <span class="fab fa-php me-2"></span>PHP Configuration
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Memory Limit:</strong></td>
                                        <td><?= ini_get('memory_limit') ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Max Execution Time:</strong></td>
                                        <td><?= ini_get('max_execution_time') ?>s</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Upload Max Filesize:</strong></td>
                                        <td><?= ini_get('upload_max_filesize') ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Post Max Size:</strong></td>
                                        <td><?= ini_get('post_max_size') ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Session Auto Start:</strong></td>
                                        <td><?= ini_get('session.auto_start') ? 'On' : 'Off' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Session Timeout:</strong></td>
                                        <td><?= ini_get('session.gc_maxlifetime') ?>s</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Display Errors:</strong></td>
                                        <td><?= ini_get('display_errors') ? 'On' : 'Off' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Error Reporting:</strong></td>
                                        <td><?= ini_get('error_reporting') ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

<script>
// Auto-refresh every 5 minutes
setTimeout(function() {
    location.reload();
}, 300000);

// Add refresh indicator
let refreshTimer = setInterval(function() {
    const now = new Date();
    const lastRefresh = new Date('<?= date('c') ?>');
    const minutesAgo = Math.floor((now - lastRefresh) / 60000);
    
    if (minutesAgo > 0) {
        document.querySelector('.page-header-text').textContent = 
            `Real-time system status and health checks (${minutesAgo}m ago)`;
    }
}, 60000);
</script>
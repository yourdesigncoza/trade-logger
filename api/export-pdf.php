<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Trade.php';
require_once __DIR__ . '/../models/Strategy.php';

requireLogin();

// For now, we'll create a simple HTML-to-PDF solution
// In production, you might want to use a library like TCPDF or DOMPDF

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
];

// Get data
$stats = $trade_model->getTradeStats($_SESSION['user_id'], $filters);
$trades = $trade_model->getByUserId($_SESSION['user_id'], $filters, 100); // Limit for PDF
$monthly_stats = $trade_model->getMonthlyStats($_SESSION['user_id'], date('Y'));

// Set PDF headers for browser display
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Trading Report - <?= sanitize($current_user['username']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3874ff;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #3874ff;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .stats-grid {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            gap: 15px;
        }
        .stat-card {
            flex: 1;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #3874ff;
        }
        .stat-card.success { border-left-color: #25b003; }
        .stat-card.warning { border-left-color: #e5780b; }
        .stat-card.danger { border-left-color: #fa3b1d; }
        .stat-value {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #666;
            font-size: 11px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            color: #3874ff;
            font-size: 16px;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }
        .trades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }
        .trades-table th,
        .trades-table td {
            padding: 6px 4px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .trades-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #3874ff;
        }
        .trades-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .outcome-win { color: #25b003; font-weight: bold; }
        .outcome-loss { color: #fa3b1d; font-weight: bold; }
        .outcome-breakeven { color: #e5780b; font-weight: bold; }
        .direction-long { color: #25b003; }
        .direction-short { color: #fa3b1d; }
        .filters {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 11px;
        }
        .filters h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
        }
        .filter-item {
            display: inline-block;
            margin-right: 20px;
            margin-bottom: 5px;
        }
        .monthly-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .monthly-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
            min-width: 80px;
            font-size: 10px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #3874ff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">Print/Save as PDF</button>

    <div class="header">
        <h1>Trading Performance Report</h1>
        <p><strong>Trader:</strong> <?= sanitize($current_user['username']) ?></p>
        <p><strong>Generated:</strong> <?= date('F j, Y \a\t g:i A') ?></p>
        <?php if ($current_user['account_size']): ?>
            <p><strong>Account Size:</strong> $<?= number_format($current_user['account_size']) ?></p>
        <?php endif; ?>
    </div>

    <?php if (!empty(array_filter($filters))): ?>
        <div class="filters">
            <h3>Applied Filters:</h3>
            <?php if ($filters['strategy_id']): ?>
                <?php
                $strategy = $strategy_model->getById($filters['strategy_id']);
                if ($strategy):
                ?>
                    <div class="filter-item"><strong>Strategy:</strong> <?= sanitize($strategy['name']) ?></div>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($filters['instrument']): ?>
                <div class="filter-item"><strong>Instrument:</strong> <?= sanitize($filters['instrument']) ?></div>
            <?php endif; ?>
            <?php if ($filters['session']): ?>
                <div class="filter-item"><strong>Session:</strong> <?= sanitize($filters['session']) ?></div>
            <?php endif; ?>
            <?php if ($filters['direction']): ?>
                <div class="filter-item"><strong>Direction:</strong> <?= ucfirst($filters['direction']) ?></div>
            <?php endif; ?>
            <?php if ($filters['date_from']): ?>
                <div class="filter-item"><strong>From:</strong> <?= formatDate($filters['date_from']) ?></div>
            <?php endif; ?>
            <?php if ($filters['date_to']): ?>
                <div class="filter-item"><strong>To:</strong> <?= formatDate($filters['date_to']) ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $stats['total_trades'] ?></div>
            <div class="stat-label">Total Trades</div>
        </div>
        <div class="stat-card success">
            <div class="stat-value"><?= number_format($stats['win_rate'], 1) ?>%</div>
            <div class="stat-label">Win Rate</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-value"><?= formatCurrency($stats['avg_rrr'] ?? 0) ?></div>
            <div class="stat-label">Avg RRR</div>
        </div>
        <div class="stat-card danger">
            <div class="stat-value"><?= $stats['open_trades'] ?></div>
            <div class="stat-label">Open Trades</div>
        </div>
    </div>

    <div class="section">
        <h2>Performance Breakdown</h2>
        <div class="stats-grid">
            <div class="stat-card success">
                <div class="stat-value"><?= $stats['winning_trades'] ?></div>
                <div class="stat-label">Winning Trades</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-value"><?= $stats['losing_trades'] ?></div>
                <div class="stat-label">Losing Trades</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-value"><?= $stats['breakeven_trades'] ?></div>
                <div class="stat-label">Break-even Trades</div>
            </div>
        </div>
    </div>

    <?php if (!empty($monthly_stats)): ?>
        <div class="section">
            <h2>Monthly Activity (<?= date('Y') ?>)</h2>
            <div class="monthly-summary">
                <?php 
                $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                $monthly_data = array_column($monthly_stats, 'trade_count', 'month');
                
                foreach ($months as $index => $month):
                    $month_num = $index + 1;
                    $count = $monthly_data[$month_num] ?? 0;
                ?>
                    <div class="monthly-item">
                        <div style="font-weight: bold;"><?= $month ?></div>
                        <div><?= $count ?> trades</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($trades)): ?>
        <div class="section">
            <h2>Recent Trades <?= count($trades) >= 100 ? '(Last 100)' : '' ?></h2>
            <table class="trades-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Strategy</th>
                        <th>Instrument</th>
                        <th>Dir.</th>
                        <th>Entry</th>
                        <th>SL</th>
                        <th>TP</th>
                        <th>RRR</th>
                        <th>Outcome</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($trades, 0, 50) as $trade): ?>
                        <tr>
                            <td><?= formatDate($trade['date']) ?></td>
                            <td><?= $trade['strategy_name'] ? sanitize(substr($trade['strategy_name'], 0, 15)) : '-' ?></td>
                            <td><?= sanitize($trade['instrument']) ?></td>
                            <td class="direction-<?= $trade['direction'] ?>"><?= strtoupper(substr($trade['direction'], 0, 1)) ?></td>
                            <td><?= formatCurrency($trade['entry_price'], 5) ?></td>
                            <td><?= formatCurrency($trade['sl'], 5) ?></td>
                            <td><?= $trade['tp'] ? formatCurrency($trade['tp'], 5) : '-' ?></td>
                            <td><?= $trade['rrr'] ? formatCurrency($trade['rrr']) : '-' ?></td>
                            <td class="outcome-<?= strtolower($trade['outcome'] ?? 'open') ?>">
                                <?= $trade['outcome'] ?: ucfirst($trade['status']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="section">
        <h2>Trading Summary</h2>
        <div style="display: flex; gap: 20px;">
            <div style="flex: 1;">
                <p><strong>Trading Period:</strong><br>
                <?= $stats['first_trade_date'] ? formatDate($stats['first_trade_date']) : 'N/A' ?> to 
                <?= $stats['last_trade_date'] ? formatDate($stats['last_trade_date']) : 'N/A' ?></p>
                
                <p><strong>Best Performing Outcome:</strong><br>
                <?php
                $outcomes = ['Win' => $stats['winning_trades'], 'Loss' => $stats['losing_trades'], 'Break-even' => $stats['breakeven_trades']];
                $best_outcome = array_keys($outcomes, max($outcomes))[0];
                echo $best_outcome . ' (' . max($outcomes) . ' trades)';
                ?></p>
            </div>
            <div style="flex: 1;">
                <p><strong>Risk Management:</strong><br>
                Average RRR: <?= formatCurrency($stats['avg_rrr'] ?? 0) ?>:1</p>
                
                <p><strong>Activity Level:</strong><br>
                <?php
                if ($stats['total_trades'] > 0 && $stats['first_trade_date']) {
                    $days = (strtotime($stats['last_trade_date'] ?: date('Y-m-d')) - strtotime($stats['first_trade_date'])) / 86400;
                    $trades_per_day = $days > 0 ? $stats['total_trades'] / $days : 0;
                    echo number_format($trades_per_day, 1) . ' trades per day (avg)';
                } else {
                    echo 'N/A';
                }
                ?></p>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Report generated by Trade Logger - <?= BASE_URL ?></p>
        <p>Generated by John @ YourDesign.co.za</p>
    </div>

    <script>
        // Auto-print dialog on load (optional)
        // window.addEventListener('load', function() {
        //     setTimeout(() => window.print(), 1000);
        // });
    </script>
</body>
</html>
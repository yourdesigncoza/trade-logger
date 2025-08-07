<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Trade.php';

requireLogin();

$trade_model = new Trade();

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
];

// Get all trades matching filters
$trades = $trade_model->getByUserId($_SESSION['user_id'], $filters, 10000); // Large limit to get all

// Set CSV headers
$filename = 'trade_export_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

// Create output handle
$output = fopen('php://output', 'w');

// CSV Headers
$headers = [
    'Date',
    'Strategy',
    'Instrument', 
    'Session',
    'Direction',
    'Entry Time',
    'Exit Time',
    'Entry Price',
    'Stop Loss',
    'Take Profit',
    'RRR',
    'Outcome',
    'Status',
    'Notes',
    'Created At',
    'Updated At'
];

fputcsv($output, $headers);

// Export data
foreach ($trades as $trade) {
    $row = [
        $trade['date'],
        $trade['strategy_name'] ?: 'No Strategy',
        $trade['instrument'],
        $trade['session'],
        ucfirst($trade['direction']),
        $trade['entry_time'] ?: '',
        $trade['exit_time'] ?: '',
        $trade['entry_price'],
        $trade['sl'],
        $trade['tp'] ?: '',
        $trade['rrr'] ?: '',
        $trade['outcome'] ?: '',
        ucfirst($trade['status']),
        $trade['notes'] ?: '',
        $trade['created_at'],
        $trade['updated_at']
    ];
    
    fputcsv($output, $row);
}

// Add summary statistics
fputcsv($output, []); // Empty row
fputcsv($output, ['=== SUMMARY STATISTICS ===']);

$stats = $trade_model->getTradeStats($_SESSION['user_id'], $filters);

$summary = [
    ['Total Trades', $stats['total_trades']],
    ['Winning Trades', $stats['winning_trades']],
    ['Losing Trades', $stats['losing_trades']],
    ['Break-even Trades', $stats['breakeven_trades']],
    ['Open Trades', $stats['open_trades']],
    ['Win Rate (%)', number_format($stats['win_rate'], 2)],
    ['Average RRR', number_format($stats['avg_rrr'] ?? 0, 2)],
    ['First Trade Date', $stats['first_trade_date'] ?: 'N/A'],
    ['Last Trade Date', $stats['last_trade_date'] ?: 'N/A']
];

foreach ($summary as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
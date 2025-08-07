<?php
class Trade {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function create($user_id, $data) {
        $this->validateTradeData($data);
        
        try {
            $this->db->beginTransaction();
            
            $trade_id = $this->db->query(
                "INSERT INTO trades (user_id, strategy_id, date, instrument, session, direction, 
                 entry_time, exit_time, entry_price, sl, tp, rrr, outcome, status, screenshot_path, notes) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $user_id,
                    $data['strategy_id'] ?: null,
                    $data['date'],
                    $data['instrument'],
                    $data['session'],
                    $data['direction'],
                    $data['entry_time'] ?: null,
                    $data['exit_time'] ?: null,
                    $data['entry_price'],
                    $data['sl'],
                    $data['tp'] ?: null,
                    $data['rrr'] ?: null,
                    $data['outcome'] ?: null,
                    $data['status'] ?: 'open',
                    $data['screenshot_path'] ?: null,
                    $data['notes'] ?: null
                ]
            );
            
            $trade_id = $this->db->lastInsertId();
            $this->db->commit();
            
            return $trade_id;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception('Failed to create trade: ' . $e->getMessage());
        }
    }
    
    public function update($id, $user_id, $data) {
        $trade = $this->getById($id, $user_id);
        if (!$trade) {
            throw new Exception('Trade not found');
        }
        
        $this->validateTradeData($data);
        
        try {
            $this->db->beginTransaction();
            
            $this->db->execute(
                "UPDATE trades SET 
                 strategy_id = ?, date = ?, instrument = ?, session = ?, direction = ?,
                 entry_time = ?, exit_time = ?, entry_price = ?, sl = ?, tp = ?, rrr = ?,
                 outcome = ?, status = ?, screenshot_path = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
                 WHERE id = ? AND user_id = ?",
                [
                    $data['strategy_id'] ?: null,
                    $data['date'],
                    $data['instrument'],
                    $data['session'],
                    $data['direction'],
                    $data['entry_time'] ?: null,
                    $data['exit_time'] ?: null,
                    $data['entry_price'],
                    $data['sl'],
                    $data['tp'] ?: null,
                    $data['rrr'] ?: null,
                    $data['outcome'] ?: null,
                    $data['status'] ?: 'open',
                    $data['screenshot_path'] ?: $trade['screenshot_path'],
                    $data['notes'] ?: null,
                    $id,
                    $user_id
                ]
            );
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception('Failed to update trade: ' . $e->getMessage());
        }
    }
    
    public function delete($id, $user_id) {
        $trade = $this->getById($id, $user_id);
        if (!$trade) {
            throw new Exception('Trade not found');
        }
        
        try {
            $result = $this->db->execute("DELETE FROM trades WHERE id = ? AND user_id = ?", [$id, $user_id]);
            
            if ($trade['screenshot_path']) {
                deleteFile($trade['screenshot_path']);
            }
            
            return $result > 0;
            
        } catch (Exception $e) {
            throw new Exception('Failed to delete trade: ' . $e->getMessage());
        }
    }
    
    public function getById($id, $user_id = null) {
        $sql = "SELECT t.*, s.name as strategy_name 
                FROM trades t 
                LEFT JOIN strategies s ON t.strategy_id = s.id 
                WHERE t.id = ?";
        $params = [$id];
        
        if ($user_id) {
            $sql .= " AND t.user_id = ?";
            $params[] = $user_id;
        }
        
        return $this->db->fetch($sql, $params);
    }
    
    public function getByUserId($user_id, $filters = [], $limit = null, $offset = 0) {
        $sql = "SELECT t.*, s.name as strategy_name 
                FROM trades t 
                LEFT JOIN strategies s ON t.strategy_id = s.id 
                WHERE t.user_id = ?";
        $params = [$user_id];
        
        // Apply filters
        if (!empty($filters['strategy_id'])) {
            $sql .= " AND t.strategy_id = ?";
            $params[] = $filters['strategy_id'];
        }
        
        if (!empty($filters['instrument'])) {
            $sql .= " AND t.instrument = ?";
            $params[] = $filters['instrument'];
        }
        
        if (!empty($filters['session'])) {
            $sql .= " AND t.session = ?";
            $params[] = $filters['session'];
        }
        
        if (!empty($filters['direction'])) {
            $sql .= " AND t.direction = ?";
            $params[] = $filters['direction'];
        }
        
        if (!empty($filters['outcome'])) {
            $sql .= " AND t.outcome = ?";
            $params[] = $filters['outcome'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND t.date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND t.date <= ?";
            $params[] = $filters['date_to'];
        }
        
        // Sorting
        $sort_field = $filters['sort'] ?? 'date';
        $sort_direction = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
        
        $allowed_sorts = ['date', 'created_at', 'instrument', 'outcome', 'rrr', 'entry_price'];
        if (in_array($sort_field, $allowed_sorts)) {
            $sql .= " ORDER BY t.{$sort_field} {$sort_direction}, t.created_at DESC";
        } else {
            $sql .= " ORDER BY t.date DESC, t.created_at DESC";
        }
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getTradeStats($user_id, $filters = []) {
        $sql = "SELECT 
                    COUNT(*) as total_trades,
                    COUNT(CASE WHEN outcome = 'Win' THEN 1 END) as winning_trades,
                    COUNT(CASE WHEN outcome = 'Loss' THEN 1 END) as losing_trades,
                    COUNT(CASE WHEN outcome = 'Break-even' THEN 1 END) as breakeven_trades,
                    COUNT(CASE WHEN status = 'open' THEN 1 END) as open_trades,
                    AVG(CASE WHEN rrr IS NOT NULL THEN rrr END) as avg_rrr,
                    MIN(date) as first_trade_date,
                    MAX(date) as last_trade_date
                FROM trades WHERE user_id = ?";
        $params = [$user_id];
        
        // Apply same filters as getByUserId
        if (!empty($filters['strategy_id'])) {
            $sql .= " AND strategy_id = ?";
            $params[] = $filters['strategy_id'];
        }
        
        if (!empty($filters['instrument'])) {
            $sql .= " AND instrument = ?";
            $params[] = $filters['instrument'];
        }
        
        if (!empty($filters['session'])) {
            $sql .= " AND session = ?";
            $params[] = $filters['session'];
        }
        
        if (!empty($filters['direction'])) {
            $sql .= " AND direction = ?";
            $params[] = $filters['direction'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND date <= ?";
            $params[] = $filters['date_to'];
        }
        
        $stats = $this->db->fetch($sql, $params);
        
        // Calculate win rate
        $completed_trades = $stats['winning_trades'] + $stats['losing_trades'] + $stats['breakeven_trades'];
        $stats['win_rate'] = $completed_trades > 0 ? ($stats['winning_trades'] / $completed_trades) * 100 : 0;
        
        return $stats;
    }
    
    public function getMonthlyStats($user_id, $year = null) {
        if (!$year) {
            $year = date('Y');
        }
        
        return $this->db->fetchAll(
            "SELECT 
                MONTH(date) as month,
                COUNT(*) as trade_count,
                COUNT(CASE WHEN outcome = 'Win' THEN 1 END) as wins,
                COUNT(CASE WHEN outcome = 'Loss' THEN 1 END) as losses,
                AVG(CASE WHEN rrr IS NOT NULL THEN rrr END) as avg_rrr
             FROM trades 
             WHERE user_id = ? AND YEAR(date) = ?
             GROUP BY MONTH(date) 
             ORDER BY MONTH(date)",
            [$user_id, $year]
        );
    }
    
    public function getInstrumentStats($user_id) {
        return $this->db->fetchAll(
            "SELECT 
                instrument,
                COUNT(*) as trade_count,
                COUNT(CASE WHEN outcome = 'Win' THEN 1 END) as wins,
                COUNT(CASE WHEN outcome = 'Loss' THEN 1 END) as losses,
                AVG(CASE WHEN rrr IS NOT NULL THEN rrr END) as avg_rrr
             FROM trades 
             WHERE user_id = ? 
             GROUP BY instrument 
             ORDER BY trade_count DESC",
            [$user_id]
        );
    }
    
    public function getUserInstruments($user_id) {
        return $this->db->fetchAll(
            "SELECT DISTINCT instrument FROM trades WHERE user_id = ? ORDER BY instrument",
            [$user_id]
        );
    }
    
    private function validateTradeData($data) {
        // Required fields
        if (empty($data['date'])) {
            throw new Exception('Trade date is required');
        }
        
        if (empty($data['instrument'])) {
            throw new Exception('Instrument is required');
        }
        
        if (empty($data['session'])) {
            throw new Exception('Trading session is required');
        }
        
        if (empty($data['direction']) || !in_array($data['direction'], ['long', 'short'])) {
            throw new Exception('Valid trade direction is required');
        }
        
        if (empty($data['entry_price']) || !is_numeric($data['entry_price']) || $data['entry_price'] <= 0) {
            throw new Exception('Valid entry price is required');
        }
        
        if (empty($data['sl']) || !is_numeric($data['sl']) || $data['sl'] <= 0) {
            throw new Exception('Valid stop loss is required');
        }
        
        // Validate date format and range
        $trade_date = strtotime($data['date']);
        if (!$trade_date) {
            throw new Exception('Invalid date format');
        }
        
        if ($trade_date > time()) {
            throw new Exception('Trade date cannot be in the future');
        }
        
        // Validate session
        if (!in_array($data['session'], ['Asia', 'London', 'NY', 'Multiple'])) {
            throw new Exception('Invalid trading session');
        }
        
        // Validate SL based on direction
        $this->validateStopLoss($data['direction'], $data['entry_price'], $data['sl']);
        
        // Validate TP if provided
        if (!empty($data['tp'])) {
            if (!is_numeric($data['tp']) || $data['tp'] <= 0) {
                throw new Exception('Invalid take profit value');
            }
            
            $this->validateTakeProfit($data['direction'], $data['entry_price'], $data['tp']);
        }
        
        // Validate RRR if provided
        if (!empty($data['rrr']) && (!is_numeric($data['rrr']) || $data['rrr'] < 0)) {
            throw new Exception('Invalid RRR value');
        }
        
        // Validate outcome if provided
        if (!empty($data['outcome']) && !in_array($data['outcome'], ['Win', 'Loss', 'Break-even'])) {
            throw new Exception('Invalid trade outcome');
        }
        
        // Validate status
        if (!empty($data['status']) && !in_array($data['status'], ['open', 'closed', 'cancelled'])) {
            throw new Exception('Invalid trade status');
        }
        
        // Validate times if provided
        if (!empty($data['entry_time'])) {
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['entry_time'])) {
                throw new Exception('Invalid entry time format');
            }
        }
        
        if (!empty($data['exit_time'])) {
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['exit_time'])) {
                throw new Exception('Invalid exit time format');
            }
        }
        
        // Validate instrument length
        if (strlen($data['instrument']) > 50) {
            throw new Exception('Instrument name too long');
        }
        
        // Validate notes length
        if (!empty($data['notes']) && strlen($data['notes']) > 1000) {
            throw new Exception('Notes too long (max 1000 characters)');
        }
    }
    
    private function validateStopLoss($direction, $entry_price, $sl) {
        if ($direction === 'long' && $sl >= $entry_price) {
            throw new Exception('For long trades, stop loss must be below entry price');
        }
        
        if ($direction === 'short' && $sl <= $entry_price) {
            throw new Exception('For short trades, stop loss must be above entry price');
        }
    }
    
    private function validateTakeProfit($direction, $entry_price, $tp) {
        if ($direction === 'long' && $tp <= $entry_price) {
            throw new Exception('For long trades, take profit must be above entry price');
        }
        
        if ($direction === 'short' && $tp >= $entry_price) {
            throw new Exception('For short trades, take profit must be below entry price');
        }
    }
    
    public function getSessionOptions() {
        return [
            'Asia' => 'Asian Session',
            'London' => 'London Session',
            'NY' => 'New York Session',
            'Multiple' => 'Multiple Sessions'
        ];
    }
    
    public function getOutcomeOptions() {
        return [
            'Win' => 'Win',
            'Loss' => 'Loss',
            'Break-even' => 'Break-even'
        ];
    }
    
    public function getStatusOptions() {
        return [
            'open' => 'Open',
            'closed' => 'Closed',
            'cancelled' => 'Cancelled'
        ];
    }
    
    public function getDirectionOptions() {
        return [
            'long' => 'Long',
            'short' => 'Short'
        ];
    }
}
?>
<?php
class Strategy {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function create($user_id, $data) {
        $this->validateStrategyData($data);
        $this->checkStrategyLimit($user_id);
        
        try {
            $this->db->beginTransaction();
            
            $strategy_id = $this->db->query(
                "INSERT INTO strategies (user_id, name, description, instrument, timeframes, sessions, chart_image_path) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $user_id,
                    $data['name'],
                    $data['description'] ?? null,
                    $data['instrument'] ?? null,
                    json_encode($data['timeframes'] ?? []),
                    json_encode($data['sessions'] ?? []),
                    $data['chart_image_path'] ?? null
                ]
            );
            
            $strategy_id = $this->db->lastInsertId();
            
            if (!empty($data['conditions'])) {
                $this->saveConditions($strategy_id, $data['conditions']);
            }
            
            $this->db->commit();
            return $strategy_id;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception('Failed to create strategy: ' . $e->getMessage());
        }
    }
    
    public function update($id, $user_id, $data) {
        $strategy = $this->getById($id, $user_id);
        if (!$strategy) {
            throw new Exception('Strategy not found');
        }
        
        $this->validateStrategyData($data);
        
        try {
            $this->db->beginTransaction();
            
            $this->db->execute(
                "UPDATE strategies SET 
                 name = ?, description = ?, instrument = ?, timeframes = ?, sessions = ?, 
                 chart_image_path = ?, updated_at = CURRENT_TIMESTAMP 
                 WHERE id = ? AND user_id = ?",
                [
                    $data['name'],
                    $data['description'] ?? null,
                    $data['instrument'] ?? null,
                    json_encode($data['timeframes'] ?? []),
                    json_encode($data['sessions'] ?? []),
                    $data['chart_image_path'] ?? $strategy['chart_image_path'],
                    $id,
                    $user_id
                ]
            );
            
            $this->db->execute("DELETE FROM strategy_conditions WHERE strategy_id = ?", [$id]);
            
            if (!empty($data['conditions'])) {
                $this->saveConditions($id, $data['conditions']);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception('Failed to update strategy: ' . $e->getMessage());
        }
    }
    
    public function delete($id, $user_id) {
        $strategy = $this->getById($id, $user_id);
        if (!$strategy) {
            throw new Exception('Strategy not found');
        }
        
        try {
            $this->db->beginTransaction();
            
            $this->db->execute("DELETE FROM strategy_conditions WHERE strategy_id = ?", [$id]);
            
            $this->db->execute("UPDATE trades SET strategy_id = NULL WHERE strategy_id = ?", [$id]);
            
            $result = $this->db->execute("DELETE FROM strategies WHERE id = ? AND user_id = ?", [$id, $user_id]);
            
            if ($strategy['chart_image_path']) {
                deleteFile($strategy['chart_image_path']);
            }
            
            $this->db->commit();
            return $result > 0;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception('Failed to delete strategy: ' . $e->getMessage());
        }
    }
    
    public function getById($id, $user_id = null) {
        $sql = "SELECT * FROM strategies WHERE id = ?";
        $params = [$id];
        
        if ($user_id) {
            $sql .= " AND user_id = ?";
            $params[] = $user_id;
        }
        
        $strategy = $this->db->fetch($sql, $params);
        
        if ($strategy) {
            $strategy['timeframes'] = json_decode($strategy['timeframes'] ?? '[]', true);
            $strategy['sessions'] = json_decode($strategy['sessions'] ?? '[]', true);
            $strategy['conditions'] = $this->getConditions($id);
        }
        
        return $strategy;
    }
    
    public function getByUserId($user_id, $limit = null, $offset = 0) {
        $sql = "SELECT s.*, COUNT(t.id) as trade_count 
                FROM strategies s 
                LEFT JOIN trades t ON s.id = t.strategy_id 
                WHERE s.user_id = ? 
                GROUP BY s.id 
                ORDER BY s.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params = [$user_id, $limit, $offset];
        } else {
            $params = [$user_id];
        }
        
        $strategies = $this->db->fetchAll($sql, $params);
        
        foreach ($strategies as &$strategy) {
            $strategy['timeframes'] = json_decode($strategy['timeframes'] ?? '[]', true);
            $strategy['sessions'] = json_decode($strategy['sessions'] ?? '[]', true);
        }
        
        return $strategies;
    }
    
    public function getConditions($strategy_id) {
        return $this->db->fetchAll(
            "SELECT * FROM strategy_conditions WHERE strategy_id = ? ORDER BY id",
            [$strategy_id]
        );
    }
    
    public function getStrategyOptions($user_id) {
        return $this->db->fetchAll(
            "SELECT id, name FROM strategies WHERE user_id = ? ORDER BY name",
            [$user_id]
        );
    }
    
    public function getStrategyStats($strategy_id, $user_id) {
        $strategy = $this->getById($strategy_id, $user_id);
        if (!$strategy) {
            return null;
        }
        
        $stats = [
            'strategy' => $strategy,
            'total_trades' => 0,
            'winning_trades' => 0,
            'losing_trades' => 0,
            'breakeven_trades' => 0,
            'win_rate' => 0,
            'avg_rrr' => 0,
            'recent_trades' => []
        ];
        
        $total = $this->db->fetch(
            "SELECT COUNT(*) as count FROM trades WHERE strategy_id = ?",
            [$strategy_id]
        );
        $stats['total_trades'] = $total['count'];
        
        if ($stats['total_trades'] > 0) {
            $outcomes = $this->db->fetchAll(
                "SELECT outcome, COUNT(*) as count FROM trades 
                 WHERE strategy_id = ? AND outcome IS NOT NULL 
                 GROUP BY outcome",
                [$strategy_id]
            );
            
            foreach ($outcomes as $outcome) {
                switch ($outcome['outcome']) {
                    case 'Win':
                        $stats['winning_trades'] = $outcome['count'];
                        break;
                    case 'Loss':
                        $stats['losing_trades'] = $outcome['count'];
                        break;
                    case 'Break-even':
                        $stats['breakeven_trades'] = $outcome['count'];
                        break;
                }
            }
            
            $completed_trades = $stats['winning_trades'] + $stats['losing_trades'] + $stats['breakeven_trades'];
            if ($completed_trades > 0) {
                $stats['win_rate'] = ($stats['winning_trades'] / $completed_trades) * 100;
            }
            
            $avg_rrr = $this->db->fetch(
                "SELECT AVG(rrr) as avg_rrr FROM trades WHERE strategy_id = ? AND rrr IS NOT NULL",
                [$strategy_id]
            );
            $stats['avg_rrr'] = $avg_rrr['avg_rrr'] ?? 0;
            
            $stats['recent_trades'] = $this->db->fetchAll(
                "SELECT * FROM trades WHERE strategy_id = ? ORDER BY date DESC, created_at DESC LIMIT 10",
                [$strategy_id]
            );
        }
        
        return $stats;
    }
    
    public function getUserStrategyCount($user_id) {
        $count = $this->db->fetch("SELECT COUNT(*) as count FROM strategies WHERE user_id = ?", [$user_id]);
        return $count['count'];
    }
    
    private function validateStrategyData($data) {
        if (empty($data['name']) || strlen($data['name']) < 3) {
            throw new Exception('Strategy name must be at least 3 characters');
        }
        
        if (strlen($data['name']) > 100) {
            throw new Exception('Strategy name cannot exceed 100 characters');
        }
        
        if (!empty($data['description']) && strlen($data['description']) > 1000) {
            throw new Exception('Strategy description cannot exceed 1000 characters');
        }
        
        if (!empty($data['instrument']) && strlen($data['instrument']) > 50) {
            throw new Exception('Instrument name cannot exceed 50 characters');
        }
    }
    
    private function checkStrategyLimit($user_id) {
        $user = $this->db->fetch("SELECT strategy_limit FROM users WHERE id = ?", [$user_id]);
        $current_count = $this->getUserStrategyCount($user_id);
        
        if ($current_count >= $user['strategy_limit']) {
            throw new Exception('You have reached your strategy limit. Contact admin to increase your limit.');
        }
    }
    
    private function saveConditions($strategy_id, $conditions) {
        foreach ($conditions as $condition) {
            if (empty($condition['type']) || empty($condition['description'])) {
                continue;
            }
            
            if (!in_array($condition['type'], ['entry', 'exit', 'invalidation'])) {
                continue;
            }
            
            $this->db->execute(
                "INSERT INTO strategy_conditions (strategy_id, type, description) VALUES (?, ?, ?)",
                [$strategy_id, $condition['type'], $condition['description']]
            );
        }
    }
    
    public function getTimeframeOptions() {
        return [
            '1m' => '1 Minute',
            '5m' => '5 Minutes', 
            '15m' => '15 Minutes',
            '30m' => '30 Minutes',
            '1h' => '1 Hour',
            '4h' => '4 Hours',
            '1d' => '1 Day',
            '1w' => '1 Week',
            '1M' => '1 Month'
        ];
    }
    
    public function getSessionOptions() {
        return [
            'Asia' => 'Asian Session',
            'London' => 'London Session', 
            'NY' => 'New York Session',
            'Multiple' => 'Multiple Sessions'
        ];
    }
    
    public function getInstrumentOptions() {
        return [
            'EURUSD', 'GBPUSD', 'USDJPY', 'USDCHF', 'AUDUSD', 'USDCAD', 'NZDUSD',
            'EURJPY', 'GBPJPY', 'EURGBP', 'AUDJPY', 'EURAUD', 'EURCHF', 'AUDNZD',
            'NZDJPY', 'GBPAUD', 'GBPCAD', 'EURNZD', 'AUDCAD', 'GBPCHF', 'EURAUD',
            'XAUUSD', 'XAGUSD', 'USOIL', 'UK100', 'US30', 'NAS100', 'SPX500', 'BTC/USD'
        ];
    }
}
?>
<?php
class Admin {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function getUserList($search = '', $limit = null, $offset = 0) {
        $sql = "SELECT 
                    u.id, u.username, u.email, u.email_verified, u.strategy_limit, 
                    u.is_admin, u.account_size, u.created_at, u.updated_at,
                    COUNT(DISTINCT s.id) as strategy_count,
                    COUNT(DISTINCT t.id) as trade_count,
                    COUNT(CASE WHEN t.outcome = 'Win' THEN 1 END) as winning_trades,
                    MAX(t.created_at) as last_trade_date,
                    MAX(sess.last_activity) as last_activity
                FROM users u
                LEFT JOIN strategies s ON u.id = s.user_id
                LEFT JOIN trades t ON u.id = t.user_id
                LEFT JOIN sessions sess ON u.id = sess.user_id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (u.username LIKE ? OR u.email LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $sql .= " GROUP BY u.id ORDER BY u.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        $users = $this->db->fetchAll($sql, $params);
        
        // Calculate additional metrics for each user
        foreach ($users as &$user) {
            $completed_trades = $user['trade_count'] - $this->getOpenTradesCount($user['id']);
            $user['win_rate'] = $completed_trades > 0 ? ($user['winning_trades'] / $completed_trades) * 100 : 0;
            $user['last_activity_formatted'] = $user['last_activity'] ? date('Y-m-d H:i:s', $user['last_activity']) : null;
        }
        
        return $users;
    }
    
    public function getUserCount($search = '') {
        $sql = "SELECT COUNT(*) as count FROM users WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (username LIKE ? OR email LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }
    
    public function getUserById($id) {
        return $this->db->fetch(
            "SELECT * FROM users WHERE id = ?",
            [$id]
        );
    }
    
    public function updateStrategyLimit($user_id, $new_limit) {
        if ($new_limit < 0 || $new_limit > 1000) {
            throw new Exception('Strategy limit must be between 0 and 1000');
        }
        
        $user = $this->getUserById($user_id);
        if (!$user) {
            throw new Exception('User not found');
        }
        
        return $this->db->execute(
            "UPDATE users SET strategy_limit = ? WHERE id = ?",
            [$new_limit, $user_id]
        );
    }
    
    public function deleteUser($user_id) {
        $user = $this->getUserById($user_id);
        if (!$user) {
            throw new Exception('User not found');
        }
        
        if ($user['is_admin']) {
            throw new Exception('Cannot delete admin users');
        }
        
        try {
            $this->db->beginTransaction();
            
            // Delete user's uploaded files
            $strategies = $this->db->fetchAll("SELECT chart_image_path FROM strategies WHERE user_id = ?", [$user_id]);
            foreach ($strategies as $strategy) {
                if ($strategy['chart_image_path']) {
                    deleteFile($strategy['chart_image_path']);
                }
            }
            
            $trades = $this->db->fetchAll("SELECT screenshot_path FROM trades WHERE user_id = ?", [$user_id]);
            foreach ($trades as $trade) {
                if ($trade['screenshot_path']) {
                    deleteFile($trade['screenshot_path']);
                }
            }
            
            // Delete user data (CASCADE will handle related records)
            $result = $this->db->execute("DELETE FROM users WHERE id = ?", [$user_id]);
            
            $this->db->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception('Failed to delete user: ' . $e->getMessage());
        }
    }
    
    public function getSystemStats() {
        $stats = [];
        
        // User statistics
        $user_stats = $this->db->fetch("
            SELECT 
                COUNT(*) as total_users,
                COUNT(CASE WHEN email_verified = 1 THEN 1 END) as verified_users,
                COUNT(CASE WHEN is_admin = 1 THEN 1 END) as admin_users,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_users_30d
            FROM users
        ");
        
        // Strategy statistics
        $strategy_stats = $this->db->fetch("
            SELECT 
                COUNT(*) as total_strategies,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_strategies_30d
            FROM strategies
        ");
        
        // Trade statistics
        $trade_stats = $this->db->fetch("
            SELECT 
                COUNT(*) as total_trades,
                COUNT(CASE WHEN outcome = 'Win' THEN 1 END) as winning_trades,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_trades_30d,
                AVG(CASE WHEN rrr IS NOT NULL THEN rrr END) as avg_rrr
            FROM trades
        ");
        
        // Session statistics
        $session_stats = $this->db->fetch("
            SELECT 
                COUNT(DISTINCT user_id) as active_users_24h
            FROM sessions 
            WHERE last_activity >= ?
        ", [time() - 86400]);
        
        // Combine all statistics
        $stats = array_merge($user_stats, $strategy_stats, $trade_stats, $session_stats);
        
        // Calculate additional metrics
        $stats['user_verification_rate'] = $stats['total_users'] > 0 ? 
            ($stats['verified_users'] / $stats['total_users']) * 100 : 0;
            
        $completed_trades = $trade_stats['total_trades'] - $this->getOpenTradesCount();
        $stats['system_win_rate'] = $completed_trades > 0 ? 
            ($stats['winning_trades'] / $completed_trades) * 100 : 0;
        
        return $stats;
    }
    
    public function getRecentActivity($limit = 10) {
        return $this->db->fetchAll("
            SELECT 
                'user_registered' as activity_type,
                u.username as username,
                u.email as email,
                u.created_at as activity_date,
                CONCAT('User ', u.username, ' registered') as activity_description
            FROM users u
            WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            
            UNION ALL
            
            SELECT 
                'strategy_created' as activity_type,
                u.username as username,
                u.email as email,
                s.created_at as activity_date,
                CONCAT('Strategy \"', s.name, '\" created by ', u.username) as activity_description
            FROM strategies s
            JOIN users u ON s.user_id = u.id
            WHERE s.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            
            UNION ALL
            
            SELECT 
                'trade_logged' as activity_type,
                u.username as username,
                u.email as email,
                t.created_at as activity_date,
                CONCAT('Trade on ', t.instrument, ' logged by ', u.username) as activity_description
            FROM trades t
            JOIN users u ON t.user_id = u.id
            WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            
            ORDER BY activity_date DESC
            LIMIT ?
        ", [$limit]);
    }
    
    public function getUserActivity($user_id) {
        return $this->db->fetchAll("
            SELECT 
                'strategy' as type,
                s.name as title,
                s.created_at as date,
                'Created strategy' as action
            FROM strategies s
            WHERE s.user_id = ?
            
            UNION ALL
            
            SELECT 
                'trade' as type,
                CONCAT(t.instrument, ' (', t.direction, ')') as title,
                t.created_at as date,
                CONCAT('Logged ', t.outcome, ' trade') as action
            FROM trades t
            WHERE t.user_id = ?
            
            ORDER BY date DESC
            LIMIT 20
        ", [$user_id, $user_id]);
    }
    
    public function getStorageUsage() {
        $upload_path = UPLOAD_PATH;
        $total_size = 0;
        $file_count = 0;
        
        if (is_dir($upload_path)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($upload_path, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $total_size += $file->getSize();
                    $file_count++;
                }
            }
        }
        
        return [
            'total_size' => $total_size,
            'total_size_mb' => round($total_size / (1024 * 1024), 2),
            'file_count' => $file_count
        ];
    }
    
    private function getOpenTradesCount($user_id = null) {
        $sql = "SELECT COUNT(*) as count FROM trades WHERE status = 'open'";
        $params = [];
        
        if ($user_id) {
            $sql .= " AND user_id = ?";
            $params[] = $user_id;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }
    
    public function searchUsers($query, $limit = 10) {
        return $this->db->fetchAll(
            "SELECT id, username, email FROM users 
             WHERE (username LIKE ? OR email LIKE ?) 
             AND email_verified = 1 
             ORDER BY username 
             LIMIT ?",
            ["%{$query}%", "%{$query}%", $limit]
        );
    }
    
    public function promoteToAdmin($user_id) {
        $user = $this->getUserById($user_id);
        if (!$user) {
            throw new Exception('User not found');
        }
        
        if ($user['is_admin']) {
            throw new Exception('User is already an admin');
        }
        
        return $this->db->execute(
            "UPDATE users SET is_admin = 1 WHERE id = ?",
            [$user_id]
        );
    }
    
    public function demoteFromAdmin($user_id) {
        $user = $this->getUserById($user_id);
        if (!$user) {
            throw new Exception('User not found');
        }
        
        if (!$user['is_admin']) {
            throw new Exception('User is not an admin');
        }
        
        // Don't allow demoting the last admin
        $admin_count = $this->db->fetch("SELECT COUNT(*) as count FROM users WHERE is_admin = 1");
        if ($admin_count['count'] <= 1) {
            throw new Exception('Cannot demote the last admin user');
        }
        
        return $this->db->execute(
            "UPDATE users SET is_admin = 0 WHERE id = ?",
            [$user_id]
        );
    }
}
?>
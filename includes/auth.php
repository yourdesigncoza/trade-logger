<?php
require_once __DIR__ . '/csrf.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function register($username, $email, $password) {
        if (!$this->validateRegistration($username, $email, $password)) {
            return false;
        }
        
        if ($this->userExists($username, $email)) {
            throw new Exception('Username or email already exists');
        }
        
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $verification_token = generateToken();
        
        try {
            $this->db->beginTransaction();
            
            $user_id = $this->db->query(
                "INSERT INTO users (username, email, password_hash, verification_token, email_verified) 
                 VALUES (?, ?, ?, ?, FALSE)",
                [$username, $email, $password_hash, $verification_token]
            );
            
            $this->queueVerificationEmail($email, $verification_token, $username);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'user_id' => $this->db->lastInsertId(),
                'verification_token' => $verification_token
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception('Registration failed: ' . $e->getMessage());
        }
    }
    
    public function login($username_or_email, $password, $remember_me = false) {
        $user = $this->getUserByUsernameOrEmail($username_or_email);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new Exception('Invalid username/email or password');
        }
        
        if (!$user['email_verified']) {
            throw new Exception('Please verify your email address before logging in');
        }
        
        $this->createUserSession($user, $remember_me);
        
        return $user;
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->db->execute(
                "DELETE FROM sessions WHERE user_id = ?",
                [$_SESSION['user_id']]
            );
        }
        
        session_unset();
        session_destroy();
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
    }
    
    public function verifyEmail($token) {
        $user = $this->db->fetch(
            "SELECT id, email FROM users WHERE verification_token = ? AND email_verified = FALSE",
            [$token]
        );
        
        if (!$user) {
            throw new Exception('Invalid or expired verification token');
        }
        
        $this->db->execute(
            "UPDATE users SET email_verified = TRUE, verification_token = NULL WHERE id = ?",
            [$user['id']]
        );
        
        return $user;
    }
    
    public function requestPasswordReset($email) {
        $user = $this->db->fetch(
            "SELECT id, username FROM users WHERE email = ? AND email_verified = TRUE",
            [$email]
        );
        
        if (!$user) {
            return false; // Don't reveal if email exists
        }
        
        $reset_token = generateToken();
        $expires = date('Y-m-d H:i:s', time() + PASSWORD_RESET_EXPIRY);
        
        $this->db->execute(
            "UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?",
            [$reset_token, $expires, $user['id']]
        );
        
        $this->queuePasswordResetEmail($email, $reset_token, $user['username']);
        
        return true;
    }
    
    public function resetPassword($token, $new_password) {
        if (!$this->validatePassword($new_password)) {
            throw new Exception('Password does not meet requirements');
        }
        
        $user = $this->db->fetch(
            "SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()",
            [$token]
        );
        
        if (!$user) {
            throw new Exception('Invalid or expired reset token');
        }
        
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $this->db->execute(
            "UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?",
            [$password_hash, $user['id']]
        );
        
        return true;
    }
    
    private function validateRegistration($username, $email, $password) {
        if (strlen($username) < 3 || strlen($username) > 50) {
            throw new Exception('Username must be 3-50 characters');
        }
        
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            throw new Exception('Username can only contain letters, numbers, hyphens, and underscores');
        }
        
        if (!validateEmail($email)) {
            throw new Exception('Invalid email address');
        }
        
        if (!validatePassword($password)) {
            throw new Exception('Password must be at least 8 characters with uppercase, lowercase, and number');
        }
        
        return true;
    }
    
    private function validatePassword($password) {
        return strlen($password) >= 8 && 
               preg_match('/[A-Z]/', $password) && 
               preg_match('/[a-z]/', $password) && 
               preg_match('/[0-9]/', $password);
    }
    
    private function userExists($username, $email) {
        $user = $this->db->fetch(
            "SELECT id FROM users WHERE username = ? OR email = ?",
            [$username, $email]
        );
        
        return $user !== false;
    }
    
    private function getUserByUsernameOrEmail($username_or_email) {
        return $this->db->fetch(
            "SELECT id, username, email, password_hash, email_verified, is_admin, strategy_limit 
             FROM users WHERE username = ? OR email = ?",
            [$username_or_email, $username_or_email]
        );
    }
    
    private function createUserSession($user, $remember_me = false) {
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['strategy_limit'] = $user['strategy_limit'];
        $_SESSION['logged_in_at'] = time();
        
        $session_id = session_id();
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = substr($_SERVER['HTTP_USER_AGENT'], 0, 255);
        $last_activity = time();
        
        $this->db->execute(
            "INSERT INTO sessions (id, user_id, ip_address, user_agent, last_activity) 
             VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE 
             ip_address = VALUES(ip_address), 
             user_agent = VALUES(user_agent), 
             last_activity = VALUES(last_activity)",
            [$session_id, $user['id'], $ip_address, $user_agent, $last_activity]
        );
        
        if ($remember_me) {
            $cookie_lifetime = 30 * 24 * 60 * 60; // 30 days
            setcookie(session_name(), $session_id, time() + $cookie_lifetime, '/');
        }
    }
    
    private function queueVerificationEmail($email, $token, $username) {
        $subject = 'Verify Your Email - Trade Logger';
        $verification_link = BASE_URL . "/views/auth/verify-email.php?token=" . urlencode($token);
        
        $body = "
            <h2>Welcome to Trade Logger!</h2>
            <p>Hello {$username},</p>
            <p>Thank you for registering. Please click the link below to verify your email address:</p>
            <p><a href='{$verification_link}' style='background-color: #3874ff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Verify Email</a></p>
            <p>Or copy and paste this link into your browser:</p>
            <p>{$verification_link}</p>
            <p>This link will expire in 24 hours.</p>
            <p>Best regards,<br>Trade Logger Team</p>
        ";
        
        $this->db->execute(
            "INSERT INTO email_queue (to_email, subject, body) VALUES (?, ?, ?)",
            [$email, $subject, $body]
        );
    }
    
    private function queuePasswordResetEmail($email, $token, $username) {
        $subject = 'Password Reset - Trade Logger';
        $reset_link = BASE_URL . "/views/auth/reset-password.php?token=" . urlencode($token);
        
        $body = "
            <h2>Password Reset Request</h2>
            <p>Hello {$username},</p>
            <p>You requested to reset your password. Click the link below to set a new password:</p>
            <p><a href='{$reset_link}' style='background-color: #3874ff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
            <p>Or copy and paste this link into your browser:</p>
            <p>{$reset_link}</p>
            <p>This link will expire in 1 hour.</p>
            <p>If you didn't request this, please ignore this email.</p>
            <p>Best regards,<br>Trade Logger Team</p>
        ";
        
        $this->db->execute(
            "INSERT INTO email_queue (to_email, subject, body) VALUES (?, ?, ?)",
            [$email, $subject, $body]
        );
    }
    
    public function cleanupExpiredTokens() {
        $this->db->execute(
            "UPDATE users SET verification_token = NULL 
             WHERE verification_token IS NOT NULL 
             AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR) 
             AND email_verified = FALSE"
        );
        
        $this->db->execute(
            "UPDATE users SET reset_token = NULL, reset_token_expires = NULL 
             WHERE reset_token_expires < NOW()"
        );
        
        $this->db->execute(
            "DELETE FROM sessions WHERE last_activity < ?",
            [time() - SESSION_LIFETIME]
        );
    }
}
?>
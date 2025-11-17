<?php
// includes/Logger.php
class Logger {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function log($action, $module = '', $record_id = null) {
        $sql = "INSERT INTO user_activity_logs (user_id, username, action, module, record_id, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $_SESSION['username'] ?? 'system',
            $action,
            $module,
            $record_id,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }
    
    // Специальные методы для частых действий
    public function logLogin($success = true) {
        $this->log($success ? 'login_success' : 'login_failed', 'auth');
    }
    
    public function logLogout() {
        $this->log('logout', 'auth');
    }
    
    public function logCreate($module, $record_id) {
        $this->log('create', $module, $record_id);
    }
    
    public function logUpdate($module, $record_id) {
        $this->log('update', $module, $record_id);
    }
    
    public function logDelete($module, $record_id) {
        $this->log('delete', $module, $record_id);
    }
    
    public function logView($module, $record_id) {
        $this->log('view', $module, $record_id);
    }
}
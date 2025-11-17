<?php
// check_users.php
require_once 'includes/db.php';

try {
    $stmt = $pdo->query("SELECT id, username, password, role, is_active FROM users");
    $users = $stmt->fetchAll();
    
    echo "<h3>Пользователи в системе:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Username</th><th>Password Hash</th><th>Role</th><th>Active</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['password']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td>{$user['is_active']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>
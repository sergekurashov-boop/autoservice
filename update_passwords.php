<?php
// update_passwords.php
require_once 'includes/db.php';

// Пользователи и их новые пароли
$users = [
    'admin' => 'TPZK3915',
    'manager' => 'manager123', 
    'mechanic' => 'mechanic123',
    'reception' => 'reception123'
];

try {
    foreach ($users as $username => $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$hashedPassword, $username]);
        
        echo "Пароль для пользователя $username обновлен<br>";
    }
    
    echo "<br>Все пароли успешно обновлены!";
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>
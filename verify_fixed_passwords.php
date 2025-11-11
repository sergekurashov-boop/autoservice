<?php
// verify_fixed_passwords.php
require_once 'includes/db.php';

// Пользователи и их пароли
$users = [
    'admin' => 'admin123',
    'manager' => 'manager123',
    'mechanic' => 'mechanic123',
    'reception' => 'reception123'
];

try {
    foreach ($users as $username => $password) {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user) {
            $isValid = password_verify($password, $user['password']);
            echo "Пользователь: $username - Проверка пароля: " . ($isValid ? 'УСПЕХ' : 'НЕУДАЧА') . "<br>";
            
            if (!$isValid) {
                echo "Хеш в базе: {$user['password']}<br>";
            }
        } else {
            echo "Пользователь $username не найден<br>";
        }
        echo "<br>";
    }
    
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>
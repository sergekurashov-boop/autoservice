<?php
// fix_passwords.php
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
        // Генерируем правильный хеш
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Обновляем пароль в базе
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$hashedPassword, $username]);
        
        echo "Пароль для пользователя $username обновлен<br>";
        echo "Новый хеш: $hashedPassword<br><br>";
    }
    
    echo "<strong>Все пароли успешно обновлены!</strong><br>";
    echo "Теперь попробуйте войти с паролями: admin123, manager123, etc.";
    
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>
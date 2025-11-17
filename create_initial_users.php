<?php
// Используем существующий файл подключения к БД
require_once 'includes/db.php';

// Хеши паролей для предустановленных пользователей
$users = [
    [
        'username' => 'admin',
        'password' => 'TPZk3915',
        'email' => 'admin@autoservice.local',
        'full_name' => 'Администратор Системы',
        'role' => 'admin',
        'is_active' => true
    ],
    [
        'username' => 'manager',
        'password' => 'manager123',
        'email' => 'manager@autoservice.local',
        'full_name' => 'Менеджер Сервиса',
        'role' => 'manager',
        'is_active' => true
    ],
    [
        'username' => 'mechanic',
        'password' => 'mechanic123',
        'email' => 'mechanic@autoservice.local',
        'full_name' => 'Иванов Иван Иванович',
        'role' => 'mechanic',
        'is_active' => true
    ],
    [
        'username' => 'reception',
        'password' => 'reception123',
        'email' => 'reception@autoservice.local',
        'full_name' => 'Петрова Мария Сергеевна',
        'role' => 'reception',
        'is_active' => true
    ]
];

try {
    // Используем существующее подключение $pdo из db.php
    
    // Проверяем, есть ли уже пользователи
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        // Создаем предустановленных пользователей
        foreach ($users as $user) {
            $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, email, full_name, role, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $user['username'],
                $hashedPassword,
                $user['email'],
                $user['full_name'],
                $user['role'],
                $user['is_active'] ? 1 : 0
            ]);
            
            echo "Создан пользователь: " . $user['username'] . "<br>";
        }
        
        echo "<br><strong>Все пользователи успешно созданы!</strong><br>";
        echo "Пароли по умолчанию: admin123, manager123, mechanic123, reception123<br>";
        echo "<strong>Не забудьте изменить пароли после первого входа!</strong>";
    } else {
        echo "В базе данных уже есть пользователи. Скрипт не был выполнен.";
    }
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
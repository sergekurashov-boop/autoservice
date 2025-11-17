<?php
require_once 'includes/db.php';

// Если уже есть активные администраторы, перенаправляем на главную
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND is_active = 1");
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result['count'] > 0) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_username = trim($_POST['admin_username']);
    $admin_password = trim($_POST['admin_password']);
    $admin_email = trim($_POST['admin_email']);
    $admin_fullname = trim($_POST['admin_fullname']);
    
    if (empty($admin_username) || empty($admin_password) || empty($admin_email) || empty($admin_fullname)) {
        $error = 'Все поля обязательны для заполнения';
    } elseif (strlen($admin_password) < 6) {
        $error = 'Пароль должен содержать не менее 6 символов';
    } else {
        try {
            $hashedPassword = password_hash($admin_password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, email, full_name, role, is_active, created_at) 
                VALUES (?, ?, ?, ?, 'admin', 1, NOW())
            ");
            
            if ($stmt->execute([$admin_username, $hashedPassword, $admin_email, $admin_fullname])) {
                $success = 'Аккаунт администратора успешно создан! Теперь вы можете войти в систему.';
                
                // Создаем базовых пользователей других ролей
                $defaultUsers = [
                    ['manager', 'manager123', 'manager@autoservice.local', 'Менеджер Сервиса', 'manager'],
                    ['mechanic', 'mechanic123', 'mechanic@autoservice.local', 'Иванов Иван Иванович', 'mechanic'],
                    ['reception', 'reception123', 'reception@autoservice.local', 'Петрова Мария Сергеевна', 'reception']
                ];
                
                foreach ($defaultUsers as $user) {
                    $hashedPass = password_hash($user[1], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, password, email, full_name, role, is_active, created_at) 
                        VALUES (?, ?, ?, ?, ?, 1, NOW())
                    ");
                    $stmt->execute([$user[0], $hashedPass, $user[2], $user[3], $user[4]]);
                }
            } else {
                $error = 'Ошибка при создании администратора. Попробуйте позже.';
            }
        } catch (PDOException $e) {
            $error = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Первоначальная настройка - Автосервис</title>
    <link rel="stylesheet" href="/autoservice/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/autoservice/assets/icons/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .setup-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
            padding: 2rem;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <h2 class="text-center mb-4">Первоначальная настройка системы</h2>
        <p class="text-center text-muted mb-4">Создайте аккаунт администратора для управления системой</p>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
                <div class="mt-3">
                    <a href="login.php" class="btn btn-primary">Перейти к входу</a>
                </div>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="admin_username" class="form-label">Имя пользователя администратора</label>
                    <input type="text" class="form-control" id="admin_username" name="admin_username" required>
                </div>
                
                <div class="mb-3">
                    <label for="admin_email" class="form-label">Email администратора</label>
                    <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                </div>
                
                <div class="mb-3">
                    <label for="admin_fullname" class="form-label">Полное имя администратора</label>
                    <input type="text" class="form-control" id="admin_fullname" name="admin_fullname" required>
                </div>
                
                <div class="mb-3">
                    <label for="admin_password" class="form-label">Пароль администратора</label>
                    <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                    <div class="form-text">Пароль должен содержать не менее 6 символов</div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Создать администратора</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
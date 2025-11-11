<?php
// Используем существующий файл подключения к БД
require_once 'includes/db.php';
require_once 'auth_check.php';
requireRole('admin');

// Логика управления пользователями
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

// Активация/деактивация пользователя
if (isset($_GET['action']) && isset($_GET['id'])) {
    $userId = intval($_GET['id']);
    
    if ($_GET['action'] === 'activate') {
        $stmt = $pdo->prepare("UPDATE users SET is_active = TRUE WHERE id = ?");
        $stmt->execute([$userId]);
    } elseif ($_GET['action'] === 'deactivate') {
        $stmt = $pdo->prepare("UPDATE users SET is_active = FALSE WHERE id = ?");
        $stmt->execute([$userId]);
    } elseif ($_GET['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
    }
    
    header('Location: user_management.php');
    exit;
}

// Изменение роли пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $userId = intval($_POST['user_id']);
    $newRole = $_POST['new_role'];
    
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$newRole, $userId]);
    
    header('Location: user_management.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями - Автосервис</title>
    <link rel="stylesheet" href="/autoservice/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/autoservice/assets/icons/font/bootstrap-icons.css">
</head>
<body>
    <!-- Подключение sidebar и header -->
    <?php include 'header.php'; ?>
    
    
    <!-- Основной контент -->
    <div class="main-content">
        <div class="container-fluid py-4">
            <h1 class="page-title">Управление пользователями</h1>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Список пользователей</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Имя пользователя</th>
                                    <th>Полное имя</th>
                                    <th>Email</th>
                                    <th>Роль</th>
                                    <th>Статус</th>
                                    <th>Дата регистрации</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <select name="new_role" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Администратор</option>
                                                <option value="manager" <?= $user['role'] === 'manager' ? 'selected' : '' ?>>Менеджер</option>
                                                <option value="mechanic" <?= $user['role'] === 'mechanic' ? 'selected' : '' ?>>Механик</option>
                                                <option value="reception" <?= $user['role'] === 'reception' ? 'selected' : '' ?>>Ресепшен</option>
                                            </select>
                                            <input type="hidden" name="change_role" value="1">
                                        </form>
                                    </td>
                                    <td>
                                        <?php if ($user['is_active']): ?>
                                            <span class="badge bg-success">Активен</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Неактивен</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <?php if ($user['is_active']): ?>
                                            <a href="user_management.php?action=deactivate&id=<?= $user['id'] ?>" class="btn btn-sm btn-warning">Деактивировать</a>
                                        <?php else: ?>
                                            <a href="user_management.php?action=activate&id=<?= $user['id'] ?>" class="btn btn-sm btn-success">Активировать</a>
                                        <?php endif; ?>
                                        <a href="user_management.php?action=delete&id=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Вы уверены?')">Удалить</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'templates/footer.php'; ?>
</body>
</html>
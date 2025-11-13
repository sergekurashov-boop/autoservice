<?php
session_start();
require_once '../includes/db.php';

// Проверка админа
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die('❌ Доступ только для администраторов');
}

// ... весь PHP код логики БЕЗ ИЗМЕНЕНИЙ ...

// ПОДКЛЮЧАЕМ HEADER И SIDEBAR
include '../templates/header.php';
include '../templates/sidebar.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями - Автосервис</title>
    <style>
        /* ВСЕ СТИЛИ ВНУТРИ */
        body {
            background: #fff8dc;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container-main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        .page-title {
            color: #8b6914;
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 30px;
            text-align: center;
            padding: 20px;
        }
        .enhanced-card {
            background: white;
            border: 2px solid #8b6914;
            border-radius: 10px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .enhanced-card-header {
            background: #8b6914;
            color: white;
            padding: 15px 20px;
            font-weight: bold;
            font-size: 1.1em;
        }
        .card-body {
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #6b5200;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #8b6914;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 14px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn-primary {
            background: #8b6914;
            color: white;
        }
        .btn-warning {
            background: #ffc107;
            color: black;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .table tr:hover {
            background: #f5f5f5;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .bg-success {
            background: #d4edda;
            color: #155724;
        }
        .bg-danger {
            background: #f8d7da;
            color: #721c24;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .empty-state-icon {
            font-size: 3em;
            margin-bottom: 10px;
        }
        .navigation {
            text-align: center;
            margin-top: 30px;
        }
        .nav-link {
            color: #8b6914;
            text-decoration: none;
            font-weight: bold;
            padding: 10px 20px;
            border: 2px solid #8b6914;
            border-radius: 5px;
            display: inline-block;
        }
        .nav-link:hover {
            background: #8b6914;
            color: white;
        }
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }
        .col-md-6 {
            flex: 0 0 50%;
            padding: 0 10px;
            box-sizing: border-box;
        }
        @media (max-width: 768px) {
            .col-md-6 {
                flex: 0 0 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container-main">
        <!-- ТВОЙ КОНТЕНТ -->
        <h1 class="page-title">👨‍💼 Управление пользователями</h1>
        
        <!-- ФОРМА СОЗДАНИЯ ПОЛЬЗОВАТЕЛЯ -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">
                ➕ Создать пользователя
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">👤 Имя пользователя:</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">🔑 Пароль:</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">📧 Email:</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">📛 Полное имя:</label>
                                <input type="text" name="full_name" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">🎭 Роль:</label>
                        <select name="role" class="form-control">
                            <option value="admin">👑 Администратор</option>
                            <option value="manager">💼 Менеджер</option>
                            <option value="mechanic">🔧 Механик</option>
                            <option value="reception">📞 Ресепшен</option>
                        </select>
                    </div>
                    <button type="submit" name="create_user" class="btn btn-primary">✅ Создать пользователя</button>
                </form>
            </div>
        </div>
        
        <!-- СПИСОК ПОЛЬЗОВАТЕЛЕЙ -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">
                📋 Список пользователей
            </div>
            <div class="card-body">
                <?php if (empty($users)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">👥</div>
                        <h3>Нет пользователей</h3>
                        <p>Создайте первого пользователя используя форму выше</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Имя пользователя</th>
                                    <th>Email</th>
                                    <th>Полное имя</th>
                                    <th>Роль</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><strong><?= $user['id'] ?></strong></td>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <select name="new_role" onchange="this.form.submit()" style="padding: 5px; border: 1px solid #8b6914; border-radius: 3px;">
                                                <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Админ</option>
                                                <option value="manager" <?= $user['role'] == 'manager' ? 'selected' : '' ?>>Менеджер</option>
                                                <option value="mechanic" <?= $user['role'] == 'mechanic' ? 'selected' : '' ?>>Механик</option>
                                                <option value="reception" <?= $user['role'] == 'reception' ? 'selected' : '' ?>>Ресепшен</option>
                                            </select>
                                            <input type="hidden" name="change_role" value="1">
                                        </form>
                                    </td>
                                    <td>
                                        <?php if ($user['is_active']): ?>
                                            <span class="badge bg-success">✅ Активен</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">❌ Неактивен</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($user['is_active']): ?>
                                                <a href="?action=deactivate&id=<?= $user['id'] ?>" class="btn btn-warning">⏸️</a>
                                            <?php else: ?>
                                                <a href="?action=activate&id=<?= $user['id'] ?>" class="btn btn-success">▶️</a>
                                            <?php endif; ?>
                                            <a href="?action=delete&id=<?= $user['id'] ?>" class="btn btn-danger" onclick="return confirm('Удалить пользователя?')">🗑️</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- НАВИГАЦИЯ -->
        <div class="navigation">
            <a href="../index.php" class="nav-link">← На главную</a>
        </div>
    </div>
</body>
</html>
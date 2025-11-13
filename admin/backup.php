<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die('❌ Доступ только для администраторов');
}

// Логика бэкапов будет здесь
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Резервные копии</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        .card { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 10px 0; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
    </style>
</head>
<body>
    <div class="container">
        <h1>💾 Резервные копии</h1>
        
        <div class="card">
            <h3>Создание резервной копии</h3>
            <p>Создайте полную резервную копию базы данных</p>
            <button class="btn btn-success">🔄 Создать бэкап</button>
        </div>
        
        <div class="card">
            <h3>Восстановление из копии</h3>
            <p>Восстановите систему из резервной копии</p>
            <button class="btn btn-warning">📥 Восстановить</button>
        </div>
        
        <p><a href="user_management.php">← К пользователям</a></p>
    </div>
</body>
</html>
<?php
// defects.php
require 'includes/db.php';
session_start();

define('ACCESS', true);
include 'templates/header.php';

// Получаем список дефектных ведомостей
$query = "SELECT d.*, c.name as client_name, car.model as car_model 
          FROM defects d 
          LEFT JOIN clients c ON d.client_id = c.id 
          LEFT JOIN cars car ON d.car_id = car.id 
          ORDER BY d.created_at DESC";
$result = $pdo->query($query);
$defects = $result->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Дефектные ведомости - АВТОСЕРВИС</title>
    <style>
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; }
        .header { background: #2c3e50; color: white; padding: 20px; border-radius: 5px; }
        .defect-list { margin-top: 20px; }
        .defect-item { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .status-draft { background: #fff3cd; }
        .status-approved { background: #d4edda; }
        .btn { padding: 10px 15px; margin: 5px; border: none; border-radius: 3px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ПРЕДВАРИТЕЛЬНЫЕ ДЕФЕКТНЫЕ ВЕДОМОСТИ</h1>
            <a href="defect_create.php" class="btn btn-primary">+ Новая ведомость</a>
        </div>

        <div class="defect-list">
            <?php foreach ($defects as $defect): ?>
            <div class="defect-item status-<?= $defect['status'] ?>">
                <h3>Ведомость №: <?= $defect['defect_number'] ?></h3>
                <p>Клиент: <?= $defect['client_name'] ?> | Авто: <?= $defect['car_model'] ?></p>
                <p>Статус: <?= $defect['status'] ?> | Общая сумма: <?= $defect['grand_total'] ?> руб.</p>
                <a href="defect_view.php?id=<?= $defect['id'] ?>" class="btn">Просмотр</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAnyRole(['admin', 'manager', 'reception', 'mechanic']);

// Получаем список заданий на осмотр
$requests = [];
$result = $conn->query("
    SELECT ir.*, u.full_name as created_by_name, o.order_number,
           (SELECT COUNT(*) FROM inspection_acts ia WHERE ia.request_id = ir.id) as has_act
    FROM inspection_requests ir
    LEFT JOIN users u ON ir.created_by = u.id
    LEFT JOIN orders o ON ir.order_id = o.id
    ORDER BY ir.created_at DESC
    LIMIT 100
");

if ($result) {
    $requests = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Задания на осмотр - Autoservice</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title { font-size: 1.5rem; font-weight: 600; color: var(--text-dark); }
        .requests-table { width: 100%; border-collapse: collapse; background: white; }
        .requests-table th { background: #f8f9fa; padding: 12px 8px; border: 1px solid #dee2e6; text-align: left; font-weight: 600; }
        .requests-table td { padding: 10px 8px; border: 1px solid #dee2e6; vertical-align: middle; }
        .request-number { font-weight: 600; color: #1976d2; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .status-new { background: #fff3cd; color: #856404; }
        .status-in_progress { background: #cce7ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .btn { padding: 8px 16px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
        .btn-primary { background: #1976d2; color: white; border-color: #1976d2; }
        .btn-success { background: #388e3c; color: white; border-color: #388e3c; }
        .btn-secondary { background: #6c757d; color: white; border-color: #6c757d; }
        .empty-state { text-align: center; padding: 40px 20px; color: #6c757d; }
    </style>
</head>
<body>
    <?php include 'templates/header.php'; ?>
    
    <div class="main-content-1c">
        <div class="content-container">
            <div class="container">
                <div class="page-header">
                    <h1 class="page-title">📋 Задания на технический осмотр</h1>
                    <a href="inspection_request.php" class="btn btn-primary">➕ Новое задание</a>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert-enhanced alert-success"><?= $_SESSION['success'] ?></div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (!empty($requests)): ?>
                    <table class="requests-table">
                        <thead>
                            <tr>
                                <th>№ задания</th>
                                <th>Дата</th>
                                <th>Клиент</th>
                                <th>Автомобиль</th>
                                <th>Жалобы</th>
                                <th>Создал</th>
                                <th>Статус</th>
                                <th>Акт</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                            <tr>
                                <td>
                                    <span class="request-number"><?= $request['request_number'] ?></span>
                                    <?php if ($request['order_number']): ?>
                                        <br><small>Заказ: <?= $request['order_number'] ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d.m.Y', strtotime($request['request_date'])) ?></td>
                                <td><?= htmlspecialchars($request['client_name']) ?></td>
                                <td><?= htmlspecialchars($request['vehicle_info']) ?></td>
                                <td>
                                    <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?= htmlspecialchars($request['client_complaints']) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($request['created_by_name']) ?></td>
                                <td>
                                    <span class="status-badge status-<?= $request['status'] ?>">
                                        <?= [
                                            'new' => 'Новое',
                                            'in_progress' => 'В работе',
                                            'completed' => 'Завершено'
                                        ][$request['status']] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($request['has_act'] > 0): ?>
                                        <span style="color: green;">✅ Создан</span>
                                    <?php else: ?>
                                        <span style="color: orange;">⏳ Ожидает</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <a href="inspection_request_view.php?id=<?= $request['id'] ?>" class="btn btn-sm" title="Просмотр">
                                            👁️
                                        </a>
                                        <?php if ($request['has_act'] == 0): ?>
                                            <a href="inspection_create.php?request_id=<?= $request['id'] ?>" class="btn btn-success btn-sm" title="Создать акт">
                                                📝
                                            </a>
                                        <?php else: ?>
                                            <a href="inspection_view.php?request_id=<?= $request['id'] ?>" class="btn btn-primary btn-sm" title="Просмотреть акт">
                                                📄
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;">📋</div>
                        <h3>Задания на осмотр не найдены</h3>
                        <p>Создайте первое задание на технический осмотр</p>
                        <a href="inspection_request.php" class="btn btn-primary" style="margin-top: 15px;">
                            ➕ Создать задание
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAnyRole(['admin', 'manager', 'mechanic']);

// Получаем список актов осмотра
$inspections = [];
$result = $conn->query("
    SELECT ia.*, u.full_name as master_name, o.order_number
    FROM inspection_acts ia
    LEFT JOIN users u ON ia.master_id = u.id
    LEFT JOIN orders o ON ia.order_id = o.id
    ORDER BY ia.created_at DESC
    LIMIT 100
");

if ($result) {
    $inspections = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Акты осмотра - Autoservice</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .inspections-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        .inspections-table th {
            background: #f8f9fa;
            padding: 12px 8px;
            border: 1px solid #dee2e6;
            text-align: left;
            font-weight: 600;
            color: #495057;
            font-size: 13px;
        }
        
        .inspections-table td {
            padding: 10px 8px;
            border: 1px solid #dee2e6;
            vertical-align: middle;
            font-size: 13px;
        }
        
        .act-number {
            font-weight: 600;
            color: #8b6914;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-new { background: #d4edda; color: #155724; }
        .status-in-progress { background: #fff3cd; color: #856404; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
        
        .btn {
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            background: white;
            color: #333;
            transition: all 0.2s ease;
        }
        
        .btn:hover {
            background: #f5f5f5;
            text-decoration: none;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .btn-primary:hover {
            background: #0056b3;
            color: white;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 11px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <?php include 'templates/header.php'; ?>
    
    <div class="main-content-1c">
        <div class="content-container">
            <div class="container">
                <div class="page-header">
                    <h1 class="page-title">📋 Акты технического осмотра</h1>
                    <a href="inspection.php" class="btn btn-primary">
                        ➕ Создать акт осмотра
                    </a>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert-enhanced alert-success">
                        <?= $_SESSION['success'] ?>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert-enhanced alert-danger">
                        <?= $_SESSION['error'] ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <?php if (!empty($inspections)): ?>
                    <table class="inspections-table">
                        <thead>
                            <tr>
                                <th>№ акта</th>
                                <th>Дата осмотра</th>
                                <th>Клиент</th>
                                <th>Автомобиль</th>
                                <th>Мастер</th>
                                <th>Общее время</th>
                                <th>Заказ</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inspections as $inspection): ?>
                            <tr>
                                <td>
                                    <span class="act-number"><?= htmlspecialchars($inspection['act_number']) ?></span>
                                </td>
                                <td><?= date('d.m.Y', strtotime($inspection['inspection_date'])) ?></td>
                                <td><?= htmlspecialchars($inspection['client_name']) ?></td>
                                <td><?= htmlspecialchars($inspection['vehicle_info']) ?></td>
                                <td><?= htmlspecialchars($inspection['master_name'] ?? 'не указан') ?></td>
                                <td><?= $inspection['total_work_time'] ?></td>
                                <td>
                                    <?php if ($inspection['order_number']): ?>
                                        №<?= $inspection['order_number'] ?>
                                    <?php else: ?>
                                        <span style="color: #6c757d;">не привязан</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <a href="inspection_task.php?id=<?= $inspection['id'] ?>" class="btn btn-sm" title="Просмотр">
                                            👁️
                                        </a>
                                        <a href="inspection_task.php?id=<?= $inspection['id'] ?>&print=1" class="btn btn-sm" title="Печать" target="_blank">
                                            🖨️
                                        </a>
                                        <?php if ($inspection['order_id']): ?>
                                            <a href="order_edit.php?id=<?= $inspection['order_id'] ?>" class="btn btn-sm" title="К заказу">
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
                        <div class="empty-state-icon">📋</div>
                        <h3>Акты осмотра не найдены</h3>
                        <p>Создайте первый акт технического осмотра</p>
                        <a href="inspection.php" class="btn btn-primary" style="margin-top: 15px;">
                            ➕ Создать акт осмотра
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
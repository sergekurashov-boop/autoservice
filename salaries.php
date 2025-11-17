<?php
// autoservice/salaries.php
require 'includes/db.php';
session_start();

define('ACCESS', true);
include 'templates/header.php';

// Получаем список сотрудников
$employees = $conn->query("
    SELECT e.*, 
           COUNT(sc.id) as calc_count,
           MAX(sc.period) as last_calculation
    FROM employees e 
    LEFT JOIN salary_calculations sc ON e.id = sc.employee_id 
    WHERE e.active = 1
    GROUP BY e.id 
    ORDER BY e.position, e.name
");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление зарплатами</title>
    <link rel="stylesheet" href="assets/css/services.css?v=<?= time() ?>">
    <style>
    .salary-card {
        background: #fffef5;
        border: 1px solid #e6d8a8;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        position: relative;
    }
    .salary-type-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-left: 10px;
    }
    .percentage { background: #d4edda; color: #155724; }
    .sales { background: #cce7ff; color: #004085; }
    .fixed { background: #fff3cd; color: #856404; }
    .calculation-row {
        border-bottom: 1px solid #f5f0d8;
        padding: 10px 0;
    }
    .calculation-row:last-child {
        border-bottom: none;
    }
    
    .employee-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }
    .employee-info {
        flex: 1;
        padding-right: 20px;
    }
    .employee-actions {
        flex-shrink: 0;
        margin-top: 5px;
    }
</style>
</head>
<body class="services-container">
   
    
    <div class="container mt-4">
        <div class="header-compact">
            <h1 class="page-title-compact">💰 Управление зарплатами</h1>
            <!-- ИСПРАВЛЕНО: убраны ссылки с $employee ДО цикла -->
            <div class="header-actions-compact">
                <a href="salary_calculate.php" class="action-btn-compact primary">
                    <span class="action-icon">🧮</span>
                    <span class="action-label">Рассчитать зарплаты</span>
                </a>
                <a href="salary_reports.php" class="action-btn-compact">
                    <span class="action-icon">📊</span>
                    <span class="action-label">Отчеты</span>
                </a>
                <a href="index.php" class="action-btn-compact">
                    <span class="action-icon">←</span>
                    <span class="action-label">На главную</span>
                </a>
            </div>
        </div>

        <!-- Список сотрудников -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">
                👥 Сотрудники (<?= $employees->num_rows ?>)
            </div>
            <div class="card-body">
                <?php while($employee = $employees->fetch_assoc()): 
                    $type_class = '';
                    $type_label = '';
                    switch($employee['salary_type']) {
                        case 'percentage': 
                            $type_class = 'percentage';
                            $type_label = 'Процент от работ';
                            break;
                        case 'sales': 
                            $type_class = 'sales';
                            $type_label = 'Продажи запчастей';
                            break;
                        case 'fixed': 
                            $type_class = 'fixed';
                            $type_label = 'Фиксированная';
                            break;
                    }
                ?>
                <div class="salary-card">
                    <!-- Информация о сотруднике -->
                    <div class="d-flex justify-content-between align-items-start">
                        <div style="flex: 1;">
                            <h5><?= htmlspecialchars($employee['name']) ?></h5>
                            <div class="text-muted">
                                <?= htmlspecialchars($employee['position']) ?>
                                <span class="salary-type-badge <?= $type_class ?>">
                                    <?= $type_label ?>
                                </span>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">
                                    Базовая ставка: <strong><?= number_format($employee['base_rate'], 0, '.', ' ') ?> ₽</strong>
                                    <?php if($employee['percentage_rate'] > 0): ?>
                                        | Процент: <strong><?= $employee['percentage_rate'] ?>%</strong>
                                    <?php endif; ?>
                                    <?php if($employee['sales_percentage'] > 0): ?>
                                        | Продажи: <strong><?= $employee['sales_percentage'] ?>%</strong>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                        <!-- ИСПРАВЛЕНО: кнопка действий ПЕРЕНЕСЕНА сюда -->
                        <div class="employee-actions">
                            <a href="employee_edit.php?id=<?= $employee['id'] ?>" class="btn-1c-warning" 
                               style="padding: 3px 8px; font-size: 0.8rem;">
                                ✏️Настроить 
                            </a>
                            <a href="salary_calculate.php?employee_id=<?= $employee['id'] ?>" class="btn-1c-primary" 
                               style="padding: 3px 8px; font-size: 0.8rem; margin-top: 5px;">
                                💰 Рассчитать
                            </a>
                        </div>
                    </div>
                    
                    <!-- История расчетов -->
                    <?php 
                    $calculations = $conn->query("
                        SELECT * FROM salary_calculations 
                        WHERE employee_id = {$employee['id']} 
                        ORDER BY period DESC 
                        LIMIT 3
                    ");
                    if($calculations->num_rows > 0): ?>
                    <div class="mt-3">
                        <h6>📅 Последние расчеты:</h6>
                        <?php while($calc = $calculations->fetch_assoc()): ?>
                        <div class="calculation-row">
                            <div class="d-flex justify-content-between">
                                <span><?= date('m/Y', strtotime($calc['period'])) ?></span>
                                <span><strong><?= number_format($calc['calculated_salary'], 0, '.', ' ') ?> ₽</strong></span>
                                <span class="badge <?= $calc['status'] == 'paid' ? 'bg-success' : 'bg-warning' ?>">
                                    <?= $calc['status'] == 'paid' ? 'Выплачено' : 'Рассчитано' ?>
                                </span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="mt-3 text-muted">
                        <small>Расчетов зарплаты еще не было</small>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
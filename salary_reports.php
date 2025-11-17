<?php
// autoservice/salary_reports.php
require 'includes/db.php';
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "❌ Требуется авторизация";
    header("Location: login.php");
    exit;
}

define('ACCESS', true);

// Параметры фильтрации
$month = $_GET['month'] ?? date('Y-m');
$employee_id = $_GET['employee_id'] ?? 'all';

// Получаем список сотрудников для фильтра
$employees = [];
try {
    $result = $conn->query("SELECT id, name FROM employees WHERE active = 1 ORDER BY name");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching employees: " . $e->getMessage());
}

// Проверяем существование таблицы salary_payments
$table_exists = false;
try {
    $check_table = $conn->query("SHOW TABLES LIKE 'salary_payments'");
    $table_exists = $check_table && $check_table->num_rows > 0;
} catch (Exception $e) {
    error_log("Error checking table: " . $e->getMessage());
}

$payments = [];
$total_base = 0;
$total_bonus = 0;
$total_salary = 0;
$salary_stats = [];

if ($table_exists) {
    // Формируем запрос с фильтрами
    $where_conditions = ["sp.month = ?"];
    $params = [$month];
    $types = "s";

    if ($employee_id !== 'all') {
        $where_conditions[] = "sp.employee_id = ?";
        $params[] = $employee_id;
        $types .= "i";
    }

    $where_sql = implode(" AND ", $where_conditions);

    try {
        // Получаем данные для отчета
        $stmt = $conn->prepare("
            SELECT 
                sp.*,
                e.name as employee_name,
                e.position,
                e.salary_type
            FROM salary_payments sp
            LEFT JOIN employees e ON sp.employee_id = e.id
            WHERE $where_sql
            ORDER BY sp.payment_date DESC, e.name
        ");

        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $payments_result = $stmt->get_result();
            
            while ($row = $payments_result->fetch_assoc()) {
                $payments[] = $row;
                $total_base += $row['base_salary'];
                $total_bonus += $row['bonus_amount'];
                $total_salary += $row['total_salary'];
            }
        }

        // Статистика по типам оплат
        $stats_stmt = $conn->prepare("
            SELECT 
                e.salary_type,
                COUNT(*) as count,
                SUM(sp.total_salary) as total
            FROM salary_payments sp
            LEFT JOIN employees e ON sp.employee_id = e.id
            WHERE $where_sql
            GROUP BY e.salary_type
        ");

        if ($stats_stmt) {
            $stats_stmt->bind_param($types, ...$params);
            $stats_stmt->execute();
            $stats_result = $stats_stmt->get_result();
            
            while ($row = $stats_result->fetch_assoc()) {
                $salary_stats[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching salary data: " . $e->getMessage());
        $_SESSION['error'] = "❌ Ошибка при загрузке данных: " . $e->getMessage();
    }
}

include 'templates/header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отчеты по зарплатам - Автосервис</title>
    <link rel="stylesheet" href="assets/css/services.css?v=<?= time() ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .stats-card {
            background: #BDB76B;
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stat-item {
            text-align: center;
            padding: 15px;
        }
        .stat-value {
            font-size: 1.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        .payment-card {
            border-left: 4px solid #28a745;
            margin-bottom: 15px;
        }
        .salary-type-badge {
            font-size: 0.8em;
            padding: 4px 8px;
            border-radius: 12px;
        }
        .export-buttons {
            margin-bottom: 20px;
        }
        .amount-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #28a745;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .bg-secondary { background-color: #6c757d !important; color: white; }
        .bg-info { background-color: #17a2b8 !important; color: white; }
        .bg-warning { background-color: #ffc107 !important; color: black; }
        
        /* Стили для dropdown */
        .btn-group {
            position: relative;
        }
        .dropdown-menu {
            position: absolute;
            z-index: 1000;
            display: none;
        }
        .show .dropdown-menu {
            display: block;
        }
    </style>
</head>
<body class="services-container">
    
    <div class="container mt-4">
        <div class="header-compact">
            <h1 class="page-title-compact">📊 Отчеты по выплатам</h1>
            <div class="header-actions-compact">
                <a href="salaries.php" class="action-btn-compact">
                    <span class="action-icon">←</span>
                    <span class="action-label">Назад к зарплатам</span>
                </a>
                <a href="salary_calculate.php" class="action-btn-compact">
                    <span class="action-icon">💰</span>
                    <span class="action-label">Расчет зарплат</span>
                </a>
            </div>
        </div>
        
        <!-- Вывод сообщений -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-enhanced alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-enhanced alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Предупреждение если таблицы нет -->
        <?php if (!$table_exists): ?>
            <div class="alert-enhanced alert-warning">
                ⚠️ Таблица выплат зарплат не найдена. 
                <a href="salary_calculate.php" class="alert-link">Создайте первую выплату</a> чтобы начать работу с отчетами.
            </div>
        <?php endif; ?>

        <!-- Фильтры -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">🔍 Фильтры отчета</div>
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">📅 Месяц</label>
                        <input type="month" name="month" class="form-control" 
                               value="<?= htmlspecialchars($month) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">👤 Сотрудник</label>
                        <select name="employee_id" class="form-control">
                            <option value="all">Все сотрудники</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id'] ?>" 
                                    <?= $employee_id == $emp['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn-1c-primary">
                            🔍 Применить фильтры
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Кнопки экспорта -->
        <?php if ($table_exists && !empty($payments)): ?>
        <div class="export-buttons">
            <div class="btn-group">
                <button type="button" class="btn-1c-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    📤 Экспорт отчетов
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a class="dropdown-item" href="salary_export.php?month=<?= $month ?>&employee_id=<?= $employee_id ?>&format=pdf" target="_blank">
                            📄 PDF документ
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="salary_export.php?month=<?= $month ?>&employee_id=<?= $employee_id ?>&format=excel">
                            📊 Excel таблица
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="salary_export.php?month=<?= $month ?>&employee_id=<?= $employee_id ?>&format=print" target="_blank">
                            🖨️ Версия для печати
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- Статистика -->
        <?php if ($table_exists): ?>
        <div class="stats-card">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-value"><?= count($payments) ?></div>
                        <div class="stat-label">Количество выплат</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-value"><?= number_format($total_base, 2, '.', ' ') ?> ₽</div>
                        <div class="stat-label">Сумма базовых окладов</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-value"><?= number_format($total_bonus, 2, '.', ' ') ?> ₽</div>
                        <div class="stat-label">Сумма бонусов</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-value"><?= number_format($total_salary, 2, '.', ' ') ?> ₽</div>
                        <div class="stat-label">Общая сумма выплат</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Статистика по типам оплат -->
        <?php if (!empty($salary_stats)): ?>
        <div class="enhanced-card">
            <div class="enhanced-card-header">📈 Статистика по типам оплат</div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($salary_stats as $stat): 
                        $type_name = $stat['salary_type'] === 'fixed' ? 'Фиксированная' : 
                                    ($stat['salary_type'] === 'percentage' ? 'Процент от работ' : 'Продажи');
                        $percentage = $total_salary > 0 ? ($stat['total'] / $total_salary) * 100 : 0;
                    ?>
                    <div class="col-md-4">
                        <div class="amount-card">
                            <h6><?= $type_name ?></h6>
                            <h5><?= number_format($stat['total'], 2, '.', ' ') ?> ₽</h5>
                            <small class="text-muted">
                                <?= $stat['count'] ?> выплат (<?= number_format($percentage, 1) ?>%)
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Список выплат -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">💳 Список выплат</div>
            <div class="card-body">
                <?php if (!$table_exists): ?>
                    <div class="text-center py-4">
                        <p class="text-muted">📊 Таблица выплат не создана</p>
                        <a href="salary_calculate.php" class="btn-1c-primary">Создать первую выплату</a>
                    </div>
                <?php elseif (empty($payments)): ?>
                    <div class="text-center py-4">
                        <p class="text-muted">💡 Нет данных за выбранный период</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Сотрудник</th>
                                    <th>Должность</th>
                                    <th>Тип оплаты</th>
                                    <th>Базовая ставка</th>
                                    <th>Бонус</th>
                                    <th>Итого</th>
                                    <th>Дата выплаты</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($payment['employee_name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($payment['position']) ?></td>
                                    <td>
                                        <span class="salary-type-badge 
                                            <?= $payment['salary_type'] === 'fixed' ? 'bg-secondary' : 
                                               ($payment['salary_type'] === 'percentage' ? 'bg-info' : 'bg-warning') ?>">
                                            <?= $payment['salary_type'] === 'fixed' ? 'Фиксированная' : 
                                               ($payment['salary_type'] === 'percentage' ? 'Процент' : 'Продажи') ?>
                                        </span>
                                    </td>
                                    <td><?= number_format($payment['base_salary'], 2, '.', ' ') ?> ₽</td>
                                    <td><?= number_format($payment['bonus_amount'], 2, '.', ' ') ?> ₽</td>
                                    <td><strong><?= number_format($payment['total_salary'], 2, '.', ' ') ?> ₽</strong></td>
                                    <td><?= date('d.m.Y', strtotime($payment['payment_date'])) ?></td>
                                    <td>
                                        <a href="salary_export.php?format=print&month=<?= $month ?>&employee_id=<?= $payment['employee_id'] ?>" 
                                           class="btn-1c-sm" title="Печать" target="_blank">
                                            🖨️
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-active">
                                    <td colspan="3"><strong>Итого:</strong></td>
                                    <td><strong><?= number_format($total_base, 2, '.', ' ') ?> ₽</strong></td>
                                    <td><strong><?= number_format($total_bonus, 2, '.', ' ') ?> ₽</strong></td>
                                    <td><strong><?= number_format($total_salary, 2, '.', ' ') ?> ₽</strong></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
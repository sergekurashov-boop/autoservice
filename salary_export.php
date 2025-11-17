<?php
// autoservice/salary_export.php
require 'includes/db.php';
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

// Получаем параметры
$month = $_GET['month'] ?? date('Y-m');
$employee_id = $_GET['employee_id'] ?? 'all';
$format = $_GET['format'] ?? 'pdf';

// Формируем запрос
$where_conditions = ["sp.month = ?"];
$params = [$month];
$types = "s";

if ($employee_id !== 'all') {
    $where_conditions[] = "sp.employee_id = ?";
    $params[] = $employee_id;
    $types .= "i";
}

$where_sql = implode(" AND ", $where_conditions);

$stmt = $conn->prepare("
    SELECT 
        sp.*,
        e.name as employee_name,
        e.position,
        e.salary_type,
        e.type as employee_type
    FROM salary_payments sp
    LEFT JOIN employees e ON sp.employee_id = e.id
    WHERE $where_sql
    ORDER BY e.type, e.name
");

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$payments = [];

$total_base = 0;
$total_bonus = 0;
$total_salary = 0;

while ($row = $result->fetch_assoc()) {
    $payments[] = $row;
    $total_base += $row['base_salary'];
    $total_bonus += $row['bonus_amount'];
    $total_salary += $row['total_salary'];
}

// Статистика
$stats_stmt = $conn->prepare("
    SELECT 
        e.type,
        COUNT(*) as count,
        SUM(sp.total_salary) as total
    FROM salary_payments sp
    LEFT JOIN employees e ON sp.employee_id = e.id
    WHERE $where_sql
    GROUP BY e.type
");

$stats_stmt->bind_param($types, ...$params);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$salary_stats = [];

while ($row = $stats_result->fetch_assoc()) {
    $salary_stats[] = $row;
}

// В зависимости от формата выводим данные
switch ($format) {
    case 'pdf':
        exportToPDF($payments, $month, $employee_id, $total_base, $total_bonus, $total_salary, $salary_stats);
        break;
    case 'excel':
        exportToExcel($payments, $month, $employee_id, $total_base, $total_bonus, $total_salary, $salary_stats);
        break;
    case 'print':
        exportToPrint($payments, $month, $employee_id, $total_base, $total_bonus, $total_salary, $salary_stats);
        break;
    default:
        header("HTTP/1.1 400 Bad Request");
        exit;
}

function exportToPDF($payments, $month, $employee_id, $total_base, $total_bonus, $total_salary, $salary_stats) {
    // Для PDF нужно установить библиотеку (например, TCPDF)
    // Покажем простой HTML для печати
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Отчет по зарплатам - <?= date('m.Y', strtotime($month)) ?></title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
            .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; }
            .stat-card { border: 1px solid #ddd; padding: 15px; text-align: center; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f5f5f5; }
            .total-row { background-color: #f9f9f9; font-weight: bold; }
            @media print {
                .no-print { display: none; }
                body { margin: 0; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Отчет по выплатам зарплат</h1>
            <h3>За <?= date('F Y', strtotime($month)) ?></h3>
            <p>Сформирован: <?= date('d.m.Y H:i') ?></p>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3><?= count($payments) ?></h3>
                <p>Количество выплат</p>
            </div>
            <div class="stat-card">
                <h3><?= number_format($total_base, 2, '.', ' ') ?> ₽</h3>
                <p>Сумма окладов</p>
            </div>
            <div class="stat-card">
                <h3><?= number_format($total_salary, 2, '.', ' ') ?> ₽</h3>
                <p>Общая сумма</p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Сотрудник</th>
                    <th>Должность</th>
                    <th>Тип</th>
                    <th>Оклад</th>
                    <th>Бонус</th>
                    <th>Итого</th>
                    <th>Дата выплаты</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                <tr>
                    <td><?= htmlspecialchars($payment['employee_name']) ?></td>
                    <td><?= htmlspecialchars($payment['position']) ?></td>
                    <td>
                        <?= $payment['employee_type'] === 'mechanic' ? 'Мастер' : 'Сотрудник' ?>
                        (<?= $payment['salary_type'] === 'fixed' ? 'Фикс' : 
                           ($payment['salary_type'] === 'percentage' ? 'Процент' : 'Продажи') ?>)
                    </td>
                    <td><?= number_format($payment['base_salary'], 2, '.', ' ') ?> ₽</td>
                    <td><?= number_format($payment['bonus_amount'], 2, '.', ' ') ?> ₽</td>
                    <td><?= number_format($payment['total_salary'], 2, '.', ' ') ?> ₽</td>
                    <td><?= date('d.m.Y', strtotime($payment['payment_date'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3"><strong>Итого:</strong></td>
                    <td><strong><?= number_format($total_base, 2, '.', ' ') ?> ₽</strong></td>
                    <td><strong><?= number_format($total_bonus, 2, '.', ' ') ?> ₽</strong></td>
                    <td><strong><?= number_format($total_salary, 2, '.', ' ') ?> ₽</strong></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <div class="no-print" style="margin-top: 30px; text-align: center;">
            <button onclick="window.print()" class="btn-1c-primary">🖨️ Печать</button>
            <button onclick="window.close()" class="btn-1c-outline">❌ Закрыть</button>
        </div>
    </body>
    </html>
    <?php
    exit;
}

function exportToExcel($payments, $month, $employee_id, $total_base, $total_bonus, $total_salary, $salary_stats) {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="salary_report_' . $month . '.xls"');
    
    echo "<html>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid black; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total { background-color: #e6e6e6; font-weight: bold; }
    </style>";
    echo "</head>";
    echo "<body>";
    
    echo "<h2>Отчет по выплатам зарплат</h2>";
    echo "<h3>За " . date('F Y', strtotime($month)) . "</h3>";
    echo "<p>Сформирован: " . date('d.m.Y H:i') . "</p>";
    
    echo "<table>";
    echo "<tr>
        <th>Сотрудник</th>
        <th>Должность</th>
        <th>Тип сотрудника</th>
        <th>Тип оплаты</th>
        <th>Оклад</th>
        <th>Бонус</th>
        <th>Итого</th>
        <th>Дата выплаты</th>
    </tr>";
    
    foreach ($payments as $payment) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($payment['employee_name']) . "</td>";
        echo "<td>" . htmlspecialchars($payment['position']) . "</td>";
        echo "<td>" . ($payment['employee_type'] === 'mechanic' ? 'Мастер' : 'Сотрудник') . "</td>";
        echo "<td>" . ($payment['salary_type'] === 'fixed' ? 'Фиксированная' : 
                      ($payment['salary_type'] === 'percentage' ? 'Процент от работ' : 'Продажи')) . "</td>";
        echo "<td>" . number_format($payment['base_salary'], 2, '.', ' ') . " ₽</td>";
        echo "<td>" . number_format($payment['bonus_amount'], 2, '.', ' ') . " ₽</td>";
        echo "<td>" . number_format($payment['total_salary'], 2, '.', ' ') . " ₽</td>";
        echo "<td>" . date('d.m.Y', strtotime($payment['payment_date'])) . "</td>";
        echo "</tr>";
    }
    
    echo "<tr class='total'>";
    echo "<td colspan='4'><strong>Итого:</strong></td>";
    echo "<td><strong>" . number_format($total_base, 2, '.', ' ') . " ₽</strong></td>";
    echo "<td><strong>" . number_format($total_bonus, 2, '.', ' ') . " ₽</strong></td>";
    echo "<td><strong>" . number_format($total_salary, 2, '.', ' ') . " ₽</strong></td>";
    echo "<td></td>";
    echo "</tr>";
    
    echo "</table>";
    
    echo "</body>";
    echo "</html>";
    exit;
}

function exportToPrint($payments, $month, $employee_id, $total_base, $total_bonus, $total_salary, $salary_stats) {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Печать отчета по зарплатам</title>
        <style>
            @media print {
                body { margin: 0; font-size: 12px; }
                .no-print { display: none; }
                table { page-break-inside: auto; }
                tr { page-break-inside: avoid; }
            }
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 11px; }
            th, td { border: 1px solid #000; padding: 5px; text-align: left; }
            th { background-color: #f0f0f0; }
            .total-row { background-color: #e0e0e0; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="header">
            <h2>Отчет по выплатам зарплат</h2>
            <h3>За <?= date('m.Y', strtotime($month)) ?></h3>
            <p>Сформирован: <?= date('d.m.Y H:i') ?></p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Сотрудник</th>
                    <th>Должность</th>
                    <th>Оклад</th>
                    <th>Бонус</th>
                    <th>Итого</th>
                    <th>Дата выплаты</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                <tr>
                    <td><?= htmlspecialchars($payment['employee_name']) ?></td>
                    <td><?= htmlspecialchars($payment['position']) ?></td>
                    <td><?= number_format($payment['base_salary'], 2, '.', ' ') ?> ₽</td>
                    <td><?= number_format($payment['bonus_amount'], 2, '.', ' ') ?> ₽</td>
                    <td><?= number_format($payment['total_salary'], 2, '.', ' ') ?> ₽</td>
                    <td><?= date('d.m.Y', strtotime($payment['payment_date'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="2"><strong>Итого:</strong></td>
                    <td><strong><?= number_format($total_base, 2, '.', ' ') ?> ₽</strong></td>
                    <td><strong><?= number_format($total_bonus, 2, '.', ' ') ?> ₽</strong></td>
                    <td><strong><?= number_format($total_salary, 2, '.', ' ') ?> ₽</strong></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <div class="no-print" style="margin-top: 20px; text-align: center;">
            <button onclick="window.print()" class="btn-1c-primary">🖨️ Печать</button>
            <button onclick="window.close()" class="btn-1c-outline">❌ Закрыть</button>
        </div>

        <script>
            window.onload = function() {
                window.print();
            };
        </script>
    </body>
    </html>
    <?php
    exit;
}
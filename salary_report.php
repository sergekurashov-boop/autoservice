<?php
// Расчет зарплаты сотрудников - с реквизитами компании
session_start();
require 'includes/db.php';
require_once 'auth_check.php';

// Получаем реквизиты компании
$company_details = [];
try {
    $result = $conn->query("SELECT * FROM company_details ORDER BY id DESC LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $company_details = $result->fetch_assoc();
    }
} catch (Exception $e) {
    error_log("Error fetching company details: " . $e->getMessage());
}

// Русские названия месяцев
$russian_months = [
    1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель', 
    5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август', 
    9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
];

// Получаем выбранный месяц и год
$selected_month = $_GET['month'] ?? date('n');
$selected_year = $_GET['year'] ?? date('Y');

// Функция для получения данных о зарплате
function getSalaryData($conn, $month, $year) {
    // Получаем всех активных сотрудников из таблицы employees
    $stmt = $conn->prepare("
        SELECT id, name, position, salary_type, base_rate, percentage_rate, sales_percentage 
        FROM employees 
        WHERE active = 1
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $employees = $result->fetch_all(MYSQLI_ASSOC);
    
    $result_data = [];
    
    foreach ($employees as $employee) {
        $salary = 0;
        $details = '';
        
        switch ($employee['salary_type']) {
            case 'fixed':
                $salary = $employee['base_rate'];
                $details = "Фиксированный оклад";
                break;
                
            case 'percentage':
                // Упрощенный расчет - только базовая ставка
                $salary = $employee['base_rate'];
                $bonus = $employee['base_rate'] * ($employee['percentage_rate'] / 100);
                $salary += $bonus;
                $details = "Базовая ставка: {$employee['base_rate']} + бонус {$employee['percentage_rate']}% ({$bonus} руб.)";
                break;
                
            case 'sales':
                // Упрощенный расчет - только базовая ставка
                $salary = $employee['base_rate'];
                $bonus = $employee['base_rate'] * ($employee['sales_percentage'] / 100);
                $salary += $bonus;
                $details = "Базовая ставка: {$employee['base_rate']} + бонус с продаж {$employee['sales_percentage']}% ({$bonus} руб.)";
                break;
                
            default:
                $salary = $employee['base_rate'];
                $details = "Базовая ставка";
                break;
        }
        
        $result_data[] = [
            'name' => $employee['name'],
            'position' => $employee['position'],
            'salary' => $salary,
            'details' => $details
        ];
    }
    
    return $result_data;
}

// Получаем данные
$salary_data = getSalaryData($conn, $selected_month, $selected_year);

// Обработка экспорта в CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="salary_report_' . date('Y-m') . '.csv"');
    
    $output = fopen('php://output', 'w');
    // Добавляем BOM для корректного отображения кириллицы в Excel
    fwrite($output, "\xEF\xBB\xBF");
    
    // Заголовки с разделителем ;
    fputcsv($output, ['ФИО', 'Должность', 'Зарплата', 'Примечания'], ';');
    
    foreach ($salary_data as $row) {
        fputcsv($output, [
            $row['name'],
            $row['position'],
            $row['salary'],
            $row['details']
        ], ';');
    }
    
    fclose($output);
    exit;
}

// Обработка печати
if (isset($_GET['print']) && $_GET['print'] == 'true') {
    echo "<script>window.onload = function() { window.print(); }</script>";
}
include 'templates/header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ведомость зарплат - <?= htmlspecialchars($company_details['company_name'] ?? 'Автосервис') ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .company-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .company-details {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .navigation {
            background: #343a40;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .nav-link {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            margin: 0 5px;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .nav-link:hover {
            background: #495057;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .controls {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        
        th {
            background-color: #e9ecef;
            font-weight: bold;
        }
        
        .total {
            font-weight: bold;
            background-color: #d4edda;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 15px;
            margin: 5px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .btn-print {
            background: #28a745;
        }
        
        .btn-print:hover {
            background: #1e7e34;
        }
        
        .btn-back {
            background: #6c757d;
        }
        
        .btn-back:hover {
            background: #545b62;
        }
        
        select, button {
            padding: 8px 12px;
            margin: 0 5px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        
        /* Стили для печати */
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                font-size: 12pt;
                margin: 0;
                padding: 10px;
                background: white;
            }
            
            .container {
                box-shadow: none;
                padding: 0;
            }
            
            table {
                font-size: 10pt;
            }
            
            th, td {
                padding: 8px;
                border: 1px solid #000;
            }
            
            .company-header {
                border-bottom: 2px solid #000;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Шапка компании -->
        <div class="company-header">
            <?php if (!empty($company_details['company_name'])): ?>
                <div class="company-name"><?= htmlspecialchars($company_details['company_name']) ?></div>
            <?php else: ?>
                <div class="company-name">Автосервис</div>
            <?php endif; ?>
            
            <?php if (!empty($company_details['legal_name'])): ?>
                <div class="company-details"><?= htmlspecialchars($company_details['legal_name']) ?></div>
            <?php endif; ?>
            
            <div class="company-details">
                <?php if (!empty($company_details['inn'])): ?>
                    ИНН: <?= htmlspecialchars($company_details['inn']) ?> 
                <?php endif; ?>
                <?php if (!empty($company_details['ogrn'])): ?>
                    | ОГРН: <?= htmlspecialchars($company_details['ogrn']) ?>
                <?php endif; ?>
                <?php if (!empty($company_details['phone'])): ?>
                    | Тел: <?= htmlspecialchars($company_details['phone']) ?>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($company_details['actual_address'])): ?>
                <div class="company-details">Адрес: <?= htmlspecialchars($company_details['actual_address']) ?></div>
            <?php endif; ?>
        </div>

             <div class="header">
            <h1>Ведомость начисления зарплаты</h1>
            <p>за <?= $russian_months[$selected_month] ?> <?= $selected_year ?> года</p>
        </div>

        <div class="controls no-print">
            <form method="GET" style="display: inline-block;">
                <label>Месяц:
                    <select name="month">
                        <?php for($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>" <?= $i == $selected_month ? 'selected' : '' ?>>
                                <?= $russian_months[$i] ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </label>
                
                <label>Год:
                    <select name="year">
                        <?php for($i = 2024; $i <= 2025; $i++): ?>
                            <option value="<?= $i ?>" <?= $i == $selected_year ? 'selected' : '' ?>>
                                <?= $i ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </label>
                
                <button type="submit" class="btn">🔄 Обновить данные</button>
            </form>
            
            <a href="?month=<?= $selected_month ?>&year=<?= $selected_year ?>&export=csv" class="btn">
                📥 Экспорт в CSV
            </a>
            
            <a href="?month=<?= $selected_month ?>&year=<?= $selected_year ?>&print=true" class="btn btn-print">
                🖨️ Печать отчета
            </a>
            
            <a href="index.php" class="btn btn-back">← Назад</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ФИО</th>
                    <th>Должность</th>
                    <th>Зарплата</th>
                    <th>Примечания</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_salary = 0;
                foreach ($salary_data as $row): 
                    $total_salary += $row['salary'];
                ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['position']) ?></td>
                        <td><?= number_format($row['salary'], 2, '.', ' ') ?> руб.</td>
                        <td><?= htmlspecialchars($row['details']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total">
                    <td colspan="2"><strong>Итого:</strong></td>
                    <td><strong><?= number_format($total_salary, 2, '.', ' ') ?> руб.</strong></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <div class="no-print" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
            <p><strong>Примечание:</strong> Данные актуальны на <?= date('d.m.Y H:i') ?></p>
            <p>Для корректного отображения кириллицы в Excel откройте CSV файл с указанием кодировки UTF-8 и разделителя ";"</p>
        </div>
    </div>
	 <?php include 'templates/footer.php'; ?>
</body>
</html>
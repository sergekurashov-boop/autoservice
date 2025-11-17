<?php
// autoservice/salary_calculate.php
require 'includes/db.php';
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "❌ Требуется авторизация";
    header("Location: login.php");
    exit;
}

define('ACCESS', true);
// МРОТ 2025
define('MIN_SALARY', 22440);

// Получаем активных сотрудников
$employees = [];
$result = $conn->query("
    SELECT * FROM employees 
    WHERE active = 1 
    ORDER BY name
");

while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
}

// Обработка расчета зарплаты
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate_salary'])) {
    $employee_id = (int)$_POST['employee_id'];
    $month = $_POST['month'];
    $work_amount = floatval(str_replace(',', '.', $_POST['work_amount'] ?? 0));
    $sales_amount = floatval(str_replace(',', '.', $_POST['sales_amount'] ?? 0));
    
    // Находим сотрудника
    $employee = null;
    foreach ($employees as $emp) {
        if ($emp['id'] == $employee_id) {
            $employee = $emp;
            break;
        }
    }
    
    if (!$employee) {
        $_SESSION['error'] = "❌ Сотрудник не найден";
        header("Location: salary_calculate.php");
        exit;
    }
    
    // Проверяем базовую ставку на соответствие МРОТ
    if ($employee['base_rate'] < MIN_SALARY) {
        $_SESSION['error'] = "⚠️ Внимание: Базовая ставка сотрудника (" . number_format($employee['base_rate'], 2, '.', ' ') . " ₽) ниже МРОТ (" . number_format(MIN_SALARY, 0, '.', ' ') . " ₽). Рекомендуется обновить данные сотрудника.";
    }
    
    // Расчет зарплаты в зависимости от типа
    $salary_details = calculateSalary($employee, $work_amount, $sales_amount);
    
    // Сохраняем расчет в сессии для отображения
    $_SESSION['salary_calculation'] = [
        'employee' => $employee,
        'month' => $month,
        'work_amount' => $work_amount,
        'sales_amount' => $sales_amount,
        'salary_details' => $salary_details
    ];
    
    header("Location: salary_calculate.php");
    exit;
}

// Обработка сохранения выплаты
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment'])) {
    $employee_id = (int)$_POST['employee_id'];
    $month = $_POST['month'];
    $total_salary = floatval(str_replace(',', '.', $_POST['total_salary']));
    $work_amount = floatval(str_replace(',', '.', $_POST['work_amount'] ?? 0));
    $sales_amount = floatval(str_replace(',', '.', $_POST['sales_amount'] ?? 0));
    $base_salary = floatval(str_replace(',', '.', $_POST['base_salary']));
    $bonus_amount = floatval(str_replace(',', '.', $_POST['bonus_amount']));
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
    
    // Проверяем базовую ставку на соответствие МРОТ
    if ($base_salary < MIN_SALARY) {
        if (!isset($_POST['confirm_low_salary'])) {
            $_SESSION['error'] = "⚠️ Базовая ставка ниже МРОТ. Для сохранения подтвердите действие.";
            $_SESSION['salary_calculation'] = $_SESSION['salary_calculation'] ?? [];
            $_SESSION['salary_calculation']['needs_confirmation'] = true;
            header("Location: salary_calculate.php");
            exit;
        }
    }
    
    // Проверяем, не существует ли уже запись за этот месяц
    $check_stmt = $conn->prepare("
        SELECT id FROM salary_payments 
        WHERE employee_id = ? AND month = ?
    ");
    $check_stmt->bind_param("is", $employee_id, $month);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $_SESSION['error'] = "❌ Зарплата за этот месяц уже была рассчитана";
    } else {
        // Сохраняем выплату
        $stmt = $conn->prepare("
            INSERT INTO salary_payments 
            (employee_id, month, work_amount, sales_amount, base_salary, bonus_amount, total_salary, payment_date, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("isddddds", $employee_id, $month, $work_amount, $sales_amount, 
                         $base_salary, $bonus_amount, $total_salary, $payment_date);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "✅ Выплата успешно сохранена";
            unset($_SESSION['salary_calculation']);
        } else {
            $_SESSION['error'] = "❌ Ошибка при сохранении выплаты";
        }
    }
    
    header("Location: salary_calculate.php");
    exit;
}

// Функция расчета зарплаты
function calculateSalary($employee, $work_amount, $sales_amount) {
    $base_salary = $employee['base_rate'];
    $bonus_amount = 0;
    
    switch ($employee['salary_type']) {
        case 'percentage':
            if ($work_amount > 0 && $employee['percentage_rate'] > 0) {
                $bonus_amount = $work_amount * ($employee['percentage_rate'] / 100);
            }
            break;
            
        case 'sales':
            if ($sales_amount > 0 && $employee['sales_percentage'] > 0) {
                $bonus_amount = $sales_amount * ($employee['sales_percentage'] / 100);
            }
            break;
            
        case 'fixed':
            // Для фиксированной ЗП бонусов нет
            $bonus_amount = 0;
            break;
    }
    
    $total_salary = $base_salary + $bonus_amount;
    
    return [
        'base_salary' => $base_salary,
        'bonus_amount' => $bonus_amount,
        'total_salary' => $total_salary,
        'work_amount' => $work_amount,
        'sales_amount' => $sales_amount
    ];
}

include 'templates/header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Расчет зарплат - Автосервис</title>
    <link rel="stylesheet" href="assets/css/services.css?v=<?= time() ?>">
    <style>
        .calculation-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .salary-breakdown {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        .amount-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #28a745;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .dynamic-fields {
            transition: all 0.3s ease;
        }
        .field-hidden {
            display: none;
        }
        .warning-card {
            border-left: 4px solid #ffc107;
            background: #fff3cd;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body class="services-container">
    
    <div class="container mt-4">
        <div class="header-compact">
            <h1 class="page-title-compact">💰 Расчет зарплат</h1>
            <div class="header-actions-compact">
                <a href="salaries.php" class="action-btn-compact">
                    <span class="action-icon">←</span>
                    <span class="action-label">Назад к зарплатам</span>
                </a>
                <a href="salary_reports.php" class="action-btn-compact">
                    <span class="action-icon">📊</span>
                    <span class="action-label">Отчеты</span>
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

        <!-- Форма расчета -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">🧮 Расчет зарплаты</div>
            <div class="card-body">
                <div class="mrot-hint mb-3">
                    💡 <strong>МРОТ в 2025 году:</strong> <?= number_format(MIN_SALARY, 0, '.', ' ') ?> ₽
                </div>
                <form method="post" id="calculateForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">👤 Сотрудник *</label>
                                <select name="employee_id" class="form-control" required id="employeeSelect">
                                    <option value="">-- Выберите сотрудника --</option>
                                    <?php foreach ($employees as $emp): ?>
                                        <option value="<?= $emp['id'] ?>" 
                                                data-salary-type="<?= $emp['salary_type'] ?>"
                                                data-base-rate="<?= $emp['base_rate'] ?>">
                                            <?= htmlspecialchars($emp['name']) ?> - <?= htmlspecialchars($emp['position']) ?>
                                            (<?= $emp['salary_type'] === 'fixed' ? 'Фиксированная' : 
                                                ($emp['salary_type'] === 'percentage' ? 'Процент от работ' : 'Продажи') ?>)
                                            - <?= number_format($emp['base_rate'], 2, '.', ' ') ?> ₽
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">📅 Месяц расчета *</label>
                                <input type="month" name="month" class="form-control" 
                                       value="<?= date('Y-m') ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Динамические поля -->
                    <div class="dynamic-fields" id="dynamicFields">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3 field-hidden" id="workAmountField">
                                    <label class="form-label">🔧 Сумма выполненных работ (₽)</label>
                                    <input type="number" step="0.01" name="work_amount" class="form-control" 
                                           value="0" min="0">
                                    <div class="form-text">Общая сумма работ для расчета процента</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3 field-hidden" id="salesAmountField">
                                    <label class="form-label">🛒 Сумма продаж запчастей (₽)</label>
                                    <input type="number" step="0.01" name="sales_amount" class="form-control" 
                                           value="0" min="0">
                                    <div class="form-text">Общая сумма продаж для расчета процента</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="calculate_salary" class="btn-1c-primary">
                            🧮 Рассчитать зарплату
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Результаты расчета -->
        <?php if (isset($_SESSION['salary_calculation'])): 
            $calc = $_SESSION['salary_calculation'];
            $employee = $calc['employee'];
            $details = $calc['salary_details'];
            $needs_confirmation = $calc['needs_confirmation'] ?? false;
        ?>
        <div class="calculation-card">
            <h4>📊 Результаты расчета</h4>
            <div class="row">
                <div class="col-md-6">
                    <h5><?= htmlspecialchars($employee['name']) ?></h5>
                    <p class="mb-1"><?= htmlspecialchars($employee['position']) ?></p>
                    <p class="mb-1">Месяц: <?= date('m.Y', strtotime($calc['month'])) ?></p>
                    <p class="mb-0">Тип оплаты: 
                        <?= $employee['salary_type'] === 'fixed' ? 'Фиксированная' : 
                          ($employee['salary_type'] === 'percentage' ? 'Процент от работ' : 'Продажи запчастей') ?>
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <h2><?= number_format($details['total_salary'], 2, '.', ' ') ?> ₽</h2>
                    <p class="mb-0">Итоговая зарплата</p>
                </div>
            </div>
        </div>

        <div class="salary-breakdown">
            <h5>🔍 Детали расчета</h5>
            
            <!-- Предупреждение о низкой базовой ставке -->
            <?php if ($employee['base_rate'] < MIN_SALARY): ?>
            <div class="warning-card">
                ⚠️ <strong>Внимание:</strong> Базовая ставка сотрудника (<?= number_format($employee['base_rate'], 2, '.', ' ') ?> ₽) 
                ниже МРОТ (<?= number_format(MIN_SALARY, 0, '.', ' ') ?> ₽)
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="amount-card">
                        <h6>Базовая ставка</h6>
                        <h4 class="text-success"><?= number_format($details['base_salary'], 2, '.', ' ') ?> ₽</h4>
                        <?php if ($details['base_salary'] < MIN_SALARY): ?>
                        <small class="text-warning">⚠️ Ниже МРОТ</small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="amount-card">
                        <h6>Бонус/процент</h6>
                        <h4 class="text-primary"><?= number_format($details['bonus_amount'], 2, '.', ' ') ?> ₽</h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="amount-card">
                        <h6>Итого к выплате</h6>
                        <h4 class="text-warning"><?= number_format($details['total_salary'], 2, '.', ' ') ?> ₽</h4>
                    </div>
                </div>
            </div>

            <?php if ($employee['salary_type'] === 'percentage' && $calc['work_amount'] > 0): ?>
                <div class="mt-3">
                    <small class="text-muted">
                        📈 Процент от работ: <?= $employee['percentage_rate'] ?>% 
                        от <?= number_format($calc['work_amount'], 2, '.', ' ') ?> ₽
                    </small>
                </div>
            <?php elseif ($employee['salary_type'] === 'sales' && $calc['sales_amount'] > 0): ?>
                <div class="mt-3">
                    <small class="text-muted">
                        🛒 Процент от продаж: <?= $employee['sales_percentage'] ?>% 
                        от <?= number_format($calc['sales_amount'], 2, '.', ' ') ?> ₽
                    </small>
                </div>
            <?php endif; ?>

            <!-- Форма сохранения выплаты -->
            <form method="post" class="mt-4">
                <input type="hidden" name="employee_id" value="<?= $employee['id'] ?>">
                <input type="hidden" name="month" value="<?= $calc['month'] ?>">
                <input type="hidden" name="work_amount" value="<?= $calc['work_amount'] ?>">
                <input type="hidden" name="sales_amount" value="<?= $calc['sales_amount'] ?>">
                <input type="hidden" name="base_salary" value="<?= $details['base_salary'] ?>">
                <input type="hidden" name="bonus_amount" value="<?= $details['bonus_amount'] ?>">
                <input type="hidden" name="total_salary" value="<?= $details['total_salary'] ?>">
                
                <?php if ($needs_confirmation): ?>
                <div class="warning-card">
                    <div class="form-check">
                        <input type="checkbox" name="confirm_low_salary" class="form-check-input" id="confirmLowSalary" required>
                        <label class="form-check-label" for="confirmLowSalary">
                            ✅ Подтверждаю сохранение выплаты с базовой ставкой ниже МРОТ
                        </label>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">📅 Дата выплаты</label>
                            <input type="date" name="payment_date" class="form-control" 
                                   value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3 d-flex align-items-end">
                            <button type="submit" name="save_payment" class="btn-1c-success">
                                💾 Сохранить выплату
                            </button>
                            <?php if ($employee['base_rate'] < MIN_SALARY): ?>
                            <a href="employee_edit.php?id=<?= $employee['id'] ?>" class="btn-1c-outline ms-2">
                                ✏️ Исправить ставку
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const employeeSelect = document.getElementById('employeeSelect');
        const workAmountField = document.getElementById('workAmountField');
        const salesAmountField = document.getElementById('salesAmountField');
        
        employeeSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const salaryType = selectedOption.getAttribute('data-salary-type');
            const baseRate = parseFloat(selectedOption.getAttribute('data-base-rate'));
            const minSalary = <?= MIN_SALARY ?>;
            
            // Скрываем все поля
            workAmountField.classList.add('field-hidden');
            salesAmountField.classList.add('field-hidden');
            
            // Показываем нужные поля в зависимости от типа оплаты
            switch(salaryType) {
                case 'percentage':
                    workAmountField.classList.remove('field-hidden');
                    break;
                case 'sales':
                    salesAmountField.classList.remove('field-hidden');
                    break;
                case 'fixed':
                    // Для фиксированной ЗП дополнительные поля не нужны
                    break;
            }
            
            // Подсвечиваем опцию если базовая ставка ниже МРОТ
            if (baseRate < minSalary) {
                selectedOption.style.backgroundColor = '#fff3cd';
                selectedOption.title = 'Базовая ставка ниже МРОТ';
            }
        });
        
        // Инициализация при загрузке
        if (employeeSelect.value) {
            employeeSelect.dispatchEvent(new Event('change'));
        }
    });
    </script>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
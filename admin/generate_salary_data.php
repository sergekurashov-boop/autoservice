<?php
// admin/generate_salary_data.php
session_start();
require '../includes/db.php';
require_once '../auth_check.php';
requireAnyRole(['admin']);

// Генерируем тестовые данные за последние 6 месяцев
function generateSalaryData($pdo) {
    $months = 6;
    $users = $pdo->query("SELECT id, role FROM users WHERE role IN ('manager', 'mechanic', 'reception')")->fetchAll();
    
    $base_salaries = [
        'manager' => 45000,
        'mechanic' => 40000, 
        'reception' => 35000
    ];
    
    for ($i = 0; $i < $months; $i++) {
        $month = date('Y-m', strtotime("-$i month"));
        
        foreach ($users as $user) {
            // Проверяем нет ли уже данных за этот месяц
            $check = $pdo->prepare("SELECT id FROM salary_calculations WHERE user_id = ? AND DATE_FORMAT(calculation_date, '%Y-%m') = ?");
            $check->execute([$user['id'], $month]);
            
            if (!$check->fetch()) {
                $base = $base_salaries[$user['role']] + (rand(-5, 5) * 500); // ±2500
                $bonus = rand(2000, 10000);
                $deductions = rand(800, 2500);
                $net = $base + $bonus - $deductions;
                
                $stmt = $pdo->prepare("INSERT INTO salary_calculations (user_id, calculation_date, base_salary, bonus, deductions, net_salary) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user['id'], $month . '-01', $base, $bonus, $deductions, $net]);
            }
        }
    }
    
    return "Данные зарплат сгенерированы за последние 6 месяцев";
}

if (isset($_POST['generate'])) {
    $result = generateSalaryData($pdo);
    $_SESSION['success'] = $result;
    header("Location: salary_report.php");
    exit;
}

include '../templates/header.php';
?>

<div style="max-width: 600px; margin: 50px auto; padding: 20px; text-align: center;">
    <h1>🧮 Генератор тестовых данных зарплат</h1>
    <p>Создаст расчеты зарплат за последние 6 месяцев для всех сотрудников</p>
    
    <form method="post">
        <button type="submit" name="generate" class="btn btn-primary" style="padding: 15px 30px; font-size: 16px;">
            🚀 Сгенерировать данные зарплат
        </button>
    </form>
    
    <div style="margin-top: 30px; background: #f8f9fa; padding: 20px; border-radius: 8px;">
        <h3>Что будет создано:</h3>
        <ul style="text-align: left; display: inline-block;">
            <li>Расчеты зарплат за последние 6 месяцев</li>
            <li>Для менеджеров, механиков и приемщиков</li>
            <li>Реалистичные суммы с премиями и удержаниями</li>
        </ul>
    </div>
</div>

<?php include '../templates/footer.php'; ?>
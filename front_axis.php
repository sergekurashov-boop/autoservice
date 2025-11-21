<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAnyRole(['admin', 'manager', 'mechanic']);

// Получаем услуги для передней оси
$services = [];
$result = $conn->query("
    SELECT code, name, typical_price 
    FROM inspection_services 
    WHERE is_active = 1 AND (axis_type = 'front' OR axis_type = 'both') 
    ORDER BY CAST(code AS UNSIGNED)
");

if (!$result) {
    die("Ошибка при получении услуг: " . $conn->error);
}

$services = $result->fetch_all(MYSQLI_ASSOC);

// Обработка сохранения формы
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $date = $conn->real_escape_string($_POST['date']);
    $client = $conn->real_escape_string($_POST['client']);
    $vehicle = $conn->real_escape_string($_POST['vehicle']);
    $services_data = json_encode($_POST['services'], JSON_UNESCAPED_UNICODE);
    $total_work = floatval($_POST['total_work']);
    $total_parts = floatval($_POST['total_parts']);
    $total_preliminary = floatval($_POST['total_preliminary']);
    
    $stmt = $conn->prepare("INSERT INTO inspection_acts (date, client, vehicle, axis_type, services_data, total_work, total_parts, total_preliminary, created_by) VALUES (?, ?, ?, 'front', ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("ssssdddi", $date, $client, $vehicle, $services_data, $total_work, $total_parts, $total_preliminary, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Акт осмотра передней оси сохранен!";
            header("Location: rear_axis.php?inspection_id=" . $conn->insert_id);
            exit();
        } else {
            $error = "Ошибка при сохранении: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Ошибка подготовки запроса: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Передняя ось - Акт осмотра</title>
    <style>
        .inspection-act { max-width: 1000px; margin: 0 auto; padding: 20px; background: white; }
        .act-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px; }
        .act-title { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
        .client-info { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 30px; padding: 15px; background: #f8f9fa; border-radius: 8px; }
        .inspection-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px; }
        .inspection-table th { background: #34495e; color: white; padding: 10px 5px; text-align: center; font-weight: bold; border: 1px solid #ddd; }
        .inspection-table td { padding: 6px; border: 1px solid #ddd; text-align: center; }
        .service-code { width: 60px; font-weight: bold; }
        .service-name { text-align: left; padding-left: 10px; width: 250px; }
        .checkbox-cell { width: 60px; }
        .price-cell { width: 100px; }
        .totals-row { background: #ecf0f1; font-weight: bold; }
        .navigation { margin-top: 30px; text-align: center; }
        .btn { padding: 10px 20px; margin: 0 10px; text-decoration: none; background: #3498db; color: white; border-radius: 5px; border: none; cursor: pointer; display: inline-block; }
        .btn-success { background: #27ae60; }
        .message { padding: 10px; margin: 10px 0; border-radius: 5px; text-align: center; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        input[type="text"], input[type="date"], input[type="number"] { width: 100%; padding: 8px; box-sizing: border-box; }
        input[type="checkbox"] { transform: scale(1.2); }
    </style>
</head>
<body>
    <?php include 'templates/header.php'; ?>
    
    <div class="inspection-act">
        <?php if (isset($error)): ?>
            <div class="message error"><?= $error ?></div>
        <?php endif; ?>
        
        <form id="inspectionForm" method="POST">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="total_work" id="totalWorkInput">
            <input type="hidden" name="total_parts" id="totalPartsInput">
            <input type="hidden" name="total_preliminary" id="totalPreliminaryInput">
            
            <div class="act-header">
                <div class="act-title">АКТ ОСМОТРА - ПЕРЕДНЯЯ ОСЬ</div>
            </div>
            
            <div class="client-info">
                <div>
                    <label>ДАТА:</label><br>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label>КЛИЕНТ:</label><br>
                    <input type="text" name="client" placeholder="ФИО" required>
                </div>
                <div>
                    <label>ТС:</label><br>
                    <input type="text" name="vehicle" placeholder="Марка, модель" required>
                </div>
            </div>

            <table class="inspection-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="service-code">код</th>
                        <th rowspan="2" class="service-name">наименование услуги</th>
                        <th colspan="2">сторона</th>
                        <th colspan="2">действия</th>
                        <th colspan="2">предварительная цена</th>
                    </tr>
                    <tr>
                        <th>левая</th><th>правая</th>
                        <th>ремонт</th><th>замена</th>
                        <th>работ</th><th>запчастей</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 20px; color: #666;">
                                Нет доступных услуг для передней оси.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($services as $service): ?>
                        <tr>
                            <td class="service-code"><?= htmlspecialchars($service['code']) ?></td>
                            <td class="service-name"><?= htmlspecialchars($service['name']) ?></td>
                            <td class="checkbox-cell">
                                <input type="checkbox" name="services[<?= htmlspecialchars($service['code']) ?>][left]" value="1">
                            </td>
                            <td class="checkbox-cell">
                                <input type="checkbox" name="services[<?= htmlspecialchars($service['code']) ?>][right]" value="1">
                            </td>
                            <td class="checkbox-cell">
                                <input type="checkbox" name="services[<?= htmlspecialchars($service['code']) ?>][repair]" value="1">
                            </td>
                            <td class="checkbox-cell">
                                <input type="checkbox" name="services[<?= htmlspecialchars($service['code']) ?>][replace]" value="1">
                            </td>
                            <td class="price-cell">
                                <input type="number" name="services[<?= htmlspecialchars($service['code']) ?>][work_price]" 
                                       value="<?= $service['typical_price'] ?>" step="0.01" min="0" 
                                       class="price-input" data-type="work" style="width: 90px;">
                            </td>
                            <td class="price-cell">
                                <input type="number" name="services[<?= htmlspecialchars($service['code']) ?>][part_price]" 
                                       step="0.01" min="0" style="width: 90px;" 
                                       class="price-input" data-type="parts">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="totals-row">
                        <td colspan="6" style="text-align: right;">ВСЕГО</td>
                        <td id="totalWork">0.00</td>
                        <td id="totalParts">0.00</td>
                    </tr>
                    <tr class="totals-row">
                        <td colspan="6" style="text-align: right;">ИТОГО ПРЕДВАРИТЕЛЬНО</td>
                        <td colspan="2" id="totalPreliminary">0.00</td>
                    </tr>
                </tfoot>
            </table>
            
            <div class="navigation">
                <button type="submit" class="btn btn-success">💾 Сохранить и перейти к задней оси</button>
                <a href="orders.php" class="btn">← Назад к заказам</a>
            </div>
        </form>
    </div>

    <script>
    function calculateTotals() {
        let totalWork = 0, totalParts = 0;
        
        document.querySelectorAll('.price-input').forEach(input => {
            const value = parseFloat(input.value) || 0;
            if (input.dataset.type === 'work') {
                totalWork += value;
            } else if (input.dataset.type === 'parts') {
                totalParts += value;
            }
        });
        
        const totalPreliminary = totalWork + totalParts;
        
        document.getElementById('totalWork').textContent = totalWork.toFixed(2);
        document.getElementById('totalParts').textContent = totalParts.toFixed(2);
        document.getElementById('totalPreliminary').textContent = totalPreliminary.toFixed(2);
        
        document.getElementById('totalWorkInput').value = totalWork;
        document.getElementById('totalPartsInput').value = totalParts;
        document.getElementById('totalPreliminaryInput').value = totalPreliminary;
    }
    
    document.addEventListener('input', calculateTotals);
    document.addEventListener('DOMContentLoaded', calculateTotals);
    
    document.getElementById('inspectionForm').addEventListener('submit', function(e) {
        const client = document.querySelector('input[name="client"]').value.trim();
        const vehicle = document.querySelector('input[name="vehicle"]').value.trim();
        
        if (!client || !vehicle) {
            e.preventDefault();
            alert('Пожалуйста, заполните все обязательные поля (Клиент и ТС)');
            return false;
        }
    });
    </script>
</body>
</html>
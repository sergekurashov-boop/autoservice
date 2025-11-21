<?php
// inspection_act.php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAnyRole(['admin', 'manager', 'mechanic']);

// Получаем услуги для передней оси
$services = [];
$result = $conn->query("
    SELECT code, name, typical_price 
    FROM inspection_services 
    WHERE is_active = 1 
    ORDER BY code
");
$services = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Акт осмотра - Autoservice</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .inspection-act {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: white;
        }
        
        .act-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        
        .act-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .client-info {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .info-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .info-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .axis-section {
            margin-bottom: 40px;
        }
        
        .axis-title {
            background: #808000;
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            border-radius: 5px 5px 0 0;
        }
        
        .inspection-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .inspection-table th {
            background: #34495e;
            color: white;
            padding: 12px 8px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #ddd;
        }
        
        .inspection-table td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: center;
        }
        
        .service-code {
            width: 80px;
            text-align: center;
            font-weight: bold;
        }
        
        .service-name {
            text-align: left;
            padding-left: 15px;
        }
        
        .checkbox-cell {
            width: 80px;
        }
        
        .price-cell {
            width: 120px;
        }
        
        .checkbox-group {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        
        .checkbox-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }
        
        .totals-row {
            background: #ecf0f1;
            font-weight: bold;
        }
        
        .totals-row td {
            padding: 12px 8px;
        }
        
        .notes-section {
            margin-top: 30px;
            padding: 15px;
            background: #fff3cd;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
        }
        
        .form-actions {
            margin-top: 30px;
            text-align: center;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0 10px;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        input[type="checkbox"] {
            transform: scale(1.2);
        }
        
        input[type="number"] {
            width: 100%;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'templates/header.php'; ?>
    
    <div class="inspection-act">
        <form id="inspectionForm" method="post" action="save_inspection.php">
            <!-- Заголовок акта -->
            <div class="act-header">
                <div class="act-title">АКТ ОСМОТРА</div>
            </div>
            
            <!-- Информация о клиенте и ТС -->
            <div class="client-info">
                <div class="info-group">
                    <label>ДАТА</label>
                    <input type="date" name="inspection_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="info-group">
                    <label>КЛИЕНТ</label>
                    <input type="text" name="client_name" placeholder="ФИО клиента" required>
                </div>
                <div class="info-group">
                    <label>ТРАНСПОРТНОЕ СРЕДСТВО</label>
                    <input type="text" name="vehicle_info" placeholder="Марка, модель, гос. номер" required>
                </div>
            </div>
            
            <!-- Передняя ось -->
            <div class="axis-section">
                <div class="axis-title">ПЕРЕДНЯЯ ОСЬ</div>
                <table class="inspection-table">
                    <thead>
                        <tr>
                            <th rowspan="2" class="service-code">код услуги</th>
                            <th rowspan="2" class="service-name">наименование услуги</th>
                            <th colspan="2">сторона</th>
                            <th colspan="2">действия</th>
                            <th colspan="2">предварительная цена</th>
                        </tr>
                        <tr>
                            <th>левая</th>
                            <th>правая</th>
                            <th>ремонт</th>
                            <th>замена</th>
                            <th>работ</th>
                            <th>запчастей</th>
                        </tr>
                    </thead>
                    <tbody id="frontAxisServices">
                        <?php foreach ($services as $service): ?>
                        <tr class="service-row" data-code="<?= $service['code'] ?>">
                            <td class="service-code">
                                <?= $service['code'] ?>
                            </td>
                            <td class="service-name">
                                <?= htmlspecialchars($service['name']) ?>
                            </td>
                            <td class="checkbox-cell">
                                <input type="checkbox" name="services[<?= $service['code'] ?>][left_side]" value="1">
                            </td>
                            <td class="checkbox-cell">
                                <input type="checkbox" name="services[<?= $service['code'] ?>][right_side]" value="1">
                            </td>
                            <td class="checkbox-cell">
                                <input type="checkbox" name="services[<?= $service['code'] ?>][repair]" value="1">
                            </td>
                            <td class="checkbox-cell">
                                <input type="checkbox" name="services[<?= $service['code'] ?>][replace]" value="1">
                            </td>
                            <td class="price-cell">
                                <input type="number" name="services[<?= $service['code'] ?>][work_price]" 
                                       value="<?= $service['typical_price'] ?>" 
                                       step="0.01" min="0" placeholder="0.00">
                            </td>
                            <td class="price-cell">
                                <input type="number" name="services[<?= $service['code'] ?>][part_price]" 
                                       step="0.01" min="0" placeholder="0.00">
                            </td>
                        </tr>
                        <?php endforeach; ?>
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
            </div>
            
            <!-- Задняя ось (аналогичная структура) -->
            <div class="axis-section">
                <div class="axis-title">ЗАДНЯЯ ОСЬ</div>
                <table class="inspection-table">
                    <!-- Такая же структура как для передней оси -->
                    <thead>
                        <tr>
                            <th rowspan="2" class="service-code">код услуги</th>
                            <th rowspan="2" class="service-name">наименование услуги</th>
                            <th colspan="2">сторона</th>
                            <th colspan="2">действия</th>
                            <th colspan="2">предварительная цена</th>
                        </tr>
                        <tr>
                            <th>левая</th>
                            <th>правая</th>
                            <th>ремонт</th>
                            <th>замена</th>
                            <th>работ</th>
                            <th>запчастей</th>
                        </tr>
                    </thead>
                    <tbody id="rearAxisServices">
                        <?php foreach ($services as $service): ?>
                        <tr class="service-row" data-code="<?= $service['code'] ?>">
                            <td class="service-code"><?= $service['code'] ?></td>
                            <td class="service-name"><?= htmlspecialchars($service['name']) ?></td>
                            <td class="checkbox-cell"><input type="checkbox" name="rear_services[<?= $service['code'] ?>][left_side]" value="1"></td>
                            <td class="checkbox-cell"><input type="checkbox" name="rear_services[<?= $service['code'] ?>][right_side]" value="1"></td>
                            <td class="checkbox-cell"><input type="checkbox" name="rear_services[<?= $service['code'] ?>][repair]" value="1"></td>
                            <td class="checkbox-cell"><input type="checkbox" name="rear_services[<?= $service['code'] ?>][replace]" value="1"></td>
                            <td class="price-cell"><input type="number" name="rear_services[<?= $service['code'] ?>][work_price]" step="0.01" min="0" placeholder="0.00"></td>
                            <td class="price-cell"><input type="number" name="rear_services[<?= $service['code'] ?>][part_price]" step="0.01" min="0" placeholder="0.00"></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Примечания -->
            <div class="notes-section">
                <strong>Примечания для DeepSeek:</strong>
                <p>Эта форма позволяет механику отмечать необходимые работы с помощью чекбоксов и указывать предварительные цены.</p>
            </div>
            
            <!-- Кнопки действий -->
            <div class="form-actions">
                <button type="submit" class="btn btn-success">💾 Сохранить акт</button>
                <button type="button" onclick="window.print()" class="btn btn-primary">🖨️ Печать</button>
                <a href="orders.php" class="btn">← Назад</a>
            </div>
        </form>
    </div>

    <script>
    // Автоматический расчет итогов
    function calculateTotals() {
        let totalWork = 0;
        let totalParts = 0;
        
        // Считаем для передней оси
        document.querySelectorAll('#frontAxisServices input[type="number"]').forEach(input => {
            const value = parseFloat(input.value) || 0;
            if (input.name.includes('work_price')) {
                totalWork += value;
            } else if (input.name.includes('part_price')) {
                totalParts += value;
            }
        });
        
        // Считаем для задней оси
        document.querySelectorAll('#rearAxisServices input[type="number"]').forEach(input => {
            const value = parseFloat(input.value) || 0;
            if (input.name.includes('work_price')) {
                totalWork += value;
            } else if (input.name.includes('part_price')) {
                totalParts += value;
            }
        });
        
        document.getElementById('totalWork').textContent = totalWork.toFixed(2);
        document.getElementById('totalParts').textContent = totalParts.toFixed(2);
        document.getElementById('totalPreliminary').textContent = (totalWork + totalParts).toFixed(2);
    }
    
    // Слушаем изменения в полях цен
    document.addEventListener('input', function(e) {
        if (e.target.type === 'number') {
            calculateTotals();
        }
    });
    
    // Инициализация
    document.addEventListener('DOMContentLoaded', function() {
        calculateTotals();
    });
    </script>
	 <?php include 'templates/footer.php'; ?>
</body>
</html>
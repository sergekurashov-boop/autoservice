<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAnyRole(['admin', 'manager', 'mechanic']);

if (!isset($_GET['order_id'])) {
    die("Ошибка: ID заказа не указан");
}

$order_id = (int)$_GET['order_id'];

// Получаем информацию о заказе
$order = [];
$stmt = $conn->prepare("
    SELECT o.*, c.make, c.model, c.year, c.license_plate, 
           cl.name as client_name, cl.phone
    FROM orders o
    JOIN cars c ON o.car_id = c.id
    JOIN clients cl ON c.client_id = cl.id
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Заказ не найден");
}

// Получаем шаблонные пункты
$categories = [];
$result = $conn->query("
    SELECT ic.name as category_name, ic.id as category_id,
           ii.id, ii.name, ii.default_side, ii.default_action,
           ii.typical_work_price, ii.typical_part_price
    FROM inspection_categories ic 
    JOIN inspection_items ii ON ic.id = ii.category_id 
    ORDER BY ic.sort_order, ii.sort_order
");

while ($row = $result->fetch_assoc()) {
    if (!isset($categories[$row['category_name']])) {
        $categories[$row['category_name']] = [];
    }
    $categories[$row['category_name']][] = $row;
}

// Получаем существующие данные осмотра
$inspection_data = [];
$result = $conn->prepare("SELECT * FROM order_inspection_data WHERE order_id = ?");
$result->bind_param("i", $order_id);
$result->execute();
$inspection_data = $result->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Осмотр авто #<?= $order_id ?> - Autoservice</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .inspection-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 20px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .search-box {
            margin-bottom: 15px;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .template-section {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .inspection-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .category-section {
            margin-bottom: 20px;
        }
        
        .category-title {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #3498db;
        }
        
        .template-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 10px;
            margin-bottom: 5px;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .template-item:hover {
            background: #f8f9fa;
            border-color: #3498db;
        }
        
        .inspection-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .inspection-table th,
        .inspection-table td {
            padding: 10px;
            border: 1px solid #dee2e6;
            text-align: left;
        }
        
        .inspection-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .order-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            grid-column: 1 / -1;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }
        
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <?php include 'templates/header.php'; ?>
    
    <div class="inspection-container">
        <!-- Информация о заказе -->
        <div class="order-info">
            <h2>🔍 Осмотр авто #<?= $order_id ?></h2>
            <p><strong>Клиент:</strong> <?= htmlspecialchars($order['client_name']) ?> | 
               <strong>Телефон:</strong> <?= htmlspecialchars($order['phone']) ?></p>
            <p><strong>Автомобиль:</strong> <?= htmlspecialchars($order['make']) ?> <?= htmlspecialchars($order['model']) ?> 
               (<?= $order['year'] ?>) | <strong>Гос. номер:</strong> <?= htmlspecialchars($order['license_plate']) ?></p>
        </div>
        
        <!-- Левая колонка - шаблонные пункты -->
        <div class="template-section">
            <h3>📋 Шаблонные пункты</h3>
            
            <div class="search-box">
                <input type="text" id="itemSearch" placeholder="🔍 Поиск детали..." onkeyup="filterItems()">
            </div>
            
            <?php foreach ($categories as $category_name => $items): ?>
            <div class="category-section">
                <div class="category-title"><?= $category_name ?></div>
                <?php foreach ($items as $item): ?>
                <div class="template-item" data-name="<?= strtolower($item['name']) ?>" 
                     onclick="addTemplateItem(<?= $item['id'] ?>, '<?= addslashes($item['name']) ?>', 
                     '<?= $item['default_side'] ?>', '<?= $item['default_action'] ?>', 
                     <?= $item['typical_work_price'] ?? 0 ?>, <?= $item['typical_part_price'] ?? 0 ?>)">
                    <span><?= $item['name'] ?></span>
                    <button type="button" class="btn btn-primary btn-sm" style="padding: 4px 8px; font-size: 12px;">+</button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Правая колонка - ведомость осмотра -->
        <div class="inspection-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>📝 Ведомость осмотра</h3>
                <button type="button" onclick="addCustomItem()" class="btn btn-success">
                    ➕ Произвольная позиция
                </button>
            </div>
            
            <table class="inspection-table">
                <thead>
                    <tr>
                        <th width="40%">Деталь/Работа</th>
                        <th width="80px">Сторона</th>
                        <th width="100px">Действие</th>
                        <th width="100px">Работа, руб</th>
                        <th width="100px">Запчасть, руб</th>
                        <th width="100px">Итого</th>
                        <th width="60px"></th>
                    </tr>
                </thead>
                <tbody id="inspectionItems">
                    <!-- Сюда будут добавляться строки через JavaScript -->
                    <tr id="noItems" style="display: none;">
                        <td colspan="7" style="text-align: center; color: #6c757d; padding: 20px;">
                            Нет добавленных пунктов. Выберите пункты из списка слева или добавьте произвольную позицию.
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" style="text-align: right; font-weight: bold;">Общая сумма:</td>
                        <td id="totalSum" style="font-weight: bold;">0.00 руб</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="button" onclick="saveInspection()" class="btn btn-success">💾 Сохранить осмотр</button>
                <a href="order_print.php?id=<?= $order_id ?>" class="btn btn-primary" target="_blank">🖨️ Печать</a>
                <a href="orders.php" class="btn btn-secondary">← Назад к заказам</a>
            </div>
        </div>
    </div>

    <script>
    let inspectionItems = [];
    let itemCounter = 0;
    
    // Фильтрация пунктов при поиске
    function filterItems() {
        const search = document.getElementById('itemSearch').value.toLowerCase();
        document.querySelectorAll('.template-item').forEach(item => {
            const itemName = item.getAttribute('data-name');
            if (itemName.includes(search)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    // Добавление шаблонного пункта
    function addTemplateItem(itemId, name, side, action, workPrice, partPrice) {
        const item = {
            id: 'tpl_' + itemCounter++,
            type: 'template',
            itemId: itemId,
            name: name,
            side: side,
            action: action,
            work_price: workPrice || 0,
            part_price: partPrice || 0,
            total_price: (workPrice || 0) + (partPrice || 0)
        };
        
        inspectionItems.push(item);
        renderInspectionTable();
    }
    
    // Добавление произвольной позиции
    function addCustomItem() {
        const name = prompt('Введите название детали/работы:');
        if (!name) return;
        
        const side = prompt('Сторона (left/right/both/none):', 'none');
        const action = prompt('Действие (repair/replace/diagnostic):', 'replace');
        const workPrice = parseFloat(prompt('Стоимость работы:', '0')) || 0;
        const partPrice = parseFloat(prompt('Стоимость запчасти:', '0')) || 0;
        
        const item = {
            id: 'cust_' + itemCounter++,
            type: 'custom',
            name: name,
            side: side || 'none',
            action: action || 'replace',
            work_price: workPrice,
            part_price: partPrice,
            total_price: workPrice + partPrice
        };
        
        inspectionItems.push(item);
        renderInspectionTable();
    }
    
    // Удаление пункта
    function removeItem(itemId) {
        inspectionItems = inspectionItems.filter(item => item.id !== itemId);
        renderInspectionTable();
    }
    
    // Отрисовка таблицы
    function renderInspectionTable() {
        const tbody = document.getElementById('inspectionItems');
        const totalElement = document.getElementById('totalSum');
        const noItemsRow = document.getElementById('noItems');
        
        let total = 0;
        let html = '';
        
        inspectionItems.forEach(item => {
            total += item.total_price;
            html += `
                <tr>
                    <td>${item.name}</td>
                    <td>${getSideLabel(item.side)}</td>
                    <td>${getActionLabel(item.action)}</td>
                    <td>${item.work_price.toFixed(2)}</td>
                    <td>${item.part_price.toFixed(2)}</td>
                    <td>${item.total_price.toFixed(2)}</td>
                    <td>
                        <button type="button" onclick="removeItem('${item.id}')" class="btn btn-danger" style="padding: 4px 8px;">🗑️</button>
                    </td>
                </tr>
            `;
        });
        
        tbody.innerHTML = html;
        totalElement.textContent = total.toFixed(2) + ' руб';
        
        // Показываем/скрываем сообщение "нет пунктов"
        if (inspectionItems.length === 0) {
            noItemsRow.style.display = '';
        } else {
            noItemsRow.style.display = 'none';
        }
    }
    
    // Вспомогательные функции
    function getSideLabel(side) {
        const labels = {
            'left': 'Левая',
            'right': 'Правая', 
            'both': 'Обе',
            'none': '-'
        };
        return labels[side] || side;
    }
    
    function getActionLabel(action) {
        const labels = {
            'repair': 'Ремонт',
            'replace': 'Замена',
            'diagnostic': 'Диагностика'
        };
        return labels[action] || action;
    }
    
    // Сохранение осмотра
    function saveInspection() {
        if (inspectionItems.length === 0) {
            alert('Добавьте хотя бы один пункт осмотра');
            return;
        }
        
        // Здесь будет AJAX запрос для сохранения в order_inspection_data
        alert('Функция сохранения будет реализована в следующем шаге');
        console.log('Данные для сохранения:', inspectionItems);
    }
    
    // Инициализация
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('noItems').style.display = '';
    });
    </script>
</body>
</html>
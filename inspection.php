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
    SELECT o.*, c.make, c.model, c.year, c.license_plate, c.vin,
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

// Обработка сохранения осмотра
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_inspection'])) {
    $conn->begin_transaction();
    try {
        // Удаляем старые данные осмотра
        $stmt = $conn->prepare("DELETE FROM order_inspection_data WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        
        // Сохраняем новые данные
        if (isset($_POST['inspection_items']) && is_array($_POST['inspection_items'])) {
            $total_inspection = 0;
            
            foreach ($_POST['inspection_items'] as $item) {
                $item_type = $item['type'];
                $inspection_item_id = ($item_type === 'template') ? $item['item_id'] : null;
                $custom_name = ($item_type === 'custom') ? $item['name'] : null;
                $side = $item['side'];
                $action = $item['action'];
                $work_price = (float)$item['work_price'];
                $part_price = (float)$item['part_price'];
                $total_price = $work_price + $part_price;
                
                $stmt = $conn->prepare("
                    INSERT INTO order_inspection_data 
                    (order_id, item_type, inspection_item_id, custom_name, side, action, work_price, part_price, total_price)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("isisssddd", $order_id, $item_type, $inspection_item_id, $custom_name, $side, $action, $work_price, $part_price, $total_price);
                $stmt->execute();
                
                $total_inspection += $total_price;
            }
            
            // Обновляем общую сумму заказа
            $stmt = $conn->prepare("UPDATE orders SET total = ? WHERE id = ?");
            $stmt->bind_param("di", $total_inspection, $order_id);
            $stmt->execute();
        }
        
        $conn->commit();
        $_SESSION['success'] = "Осмотр успешно сохранен!";
        
        header("Location: inspection.php?order_id=" . $order_id);
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Ошибка при сохранении: " . $e->getMessage();
    }
}

// Получаем существующие данные осмотра
$inspection_data = [];
$result = $conn->prepare("
    SELECT oid.*, ii.name as template_name 
    FROM order_inspection_data oid 
    LEFT JOIN inspection_items ii ON oid.inspection_item_id = ii.id 
    WHERE oid.order_id = ?
    ORDER BY oid.created_at
");
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
	<link rel="stylesheet" href="assets/css/orders.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Осмотр авто #<?= $order_id ?></h1>
            <div class="header-actions">
			<button class="btn-1c-primary">
                <a href="order_print.php?id=<?= $order_id ?>" class="btn-1c" target="_blank">🖨️ </a></button>
                <button class="btn-1c-primary">
				<a href="orders.php" class="btn-1c-outline">
                ← Назад к заказам
            </a></button>
            </div>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-1c error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-1c success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Информация о заказе -->
        <div class="card-1c">
            <div class="card-header">
                <h3>Информация о заказе</h3>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Клиент:</label>
                        <div class="form-value"><?= htmlspecialchars($order['client_name']) ?></div>
                    </div>
                    <div class="form-group">
                        <label>Телефон:</label>
                        <div class="form-value"><?= htmlspecialchars($order['phone']) ?></div>
                    </div>
                    <div class="form-group">
                        <label>Автомобиль:</label>
                        <div class="form-value">
                            <?= htmlspecialchars($order['make']) ?> <?= htmlspecialchars($order['model']) ?> 
                            (<?= $order['year'] ?>)
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Гос. номер:</label>
                        <div class="form-value"><?= htmlspecialchars($order['license_plate']) ?></div>
                    </div>
                    <?php if (!empty($order['vin'])): ?>
                    <div class="form-group">
                        <label>VIN:</label>
                        <div class="form-value"><?= htmlspecialchars($order['vin']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="layout-2col">
            <!-- Левая колонка - шаблонные пункты -->
            <div class="layout-col">
                <div class="card-1c">
                    <div class="card-header">
                        <h3>Шаблонные пункты осмотра</h3>
                    </div>
                    <div class="card-body">
                        <div class="search-box">
                            <input type="text" id="itemSearch" placeholder="Поиск детали..." class="form-control">
                        </div>
                        
                        <div class="template-list">
                            <?php foreach ($categories as $category_name => $items): ?>
                            <div class="category-section">
                                <h4 class="category-title"><?= $category_name ?></h4>
                                <?php foreach ($items as $item): ?>
                                <div class="template-item" data-name="<?= strtolower($item['name']) ?>">
                                    <span class="template-name"><?= $item['name'] ?></span>
                                    <button type="button" class="btn-1c btn-sm" 
                                            onclick="addTemplateItem(<?= $item['id'] ?>, '<?= addslashes($item['name']) ?>', 
                                            '<?= $item['default_side'] ?>', '<?= $item['default_action'] ?>', 
                                            <?= $item['typical_work_price'] ?? 0 ?>, <?= $item['typical_part_price'] ?? 0 ?>)">
                                        Добавить
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Правая колонка - ведомость осмотра -->
            <div class="layout-col">
                <div class="card-1c">
                    <div class="card-header">
                        <div class="header-with-actions">
                            <h3>Ведомость осмотра</h3>
                            <button type="button" onclick="showCustomForm()" class="btn-1c-primary">
                                ➕ Произвольная позиция
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Форма для произвольной позиции -->
                        <div id="customItemForm" class="custom-form" style="display: none;">
                            <h4>Добавить произвольную позицию</h4>
                            <form id="customForm" onsubmit="addCustomItem(event)">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Название детали/работы:</label>
                                        <input type="text" name="custom_name" class="form-control" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Сторона:</label>
                                        <select name="side" class="form-control">
                                            <option value="none">Не применимо</option>
                                            <option value="left">Левая</option>
                                            <option value="right">Правая</option>
                                            <option value="both">Обе</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Действие:</label>
                                        <select name="action" class="form-control">
                                            <option value="replace">Замена</option>
                                            <option value="repair">Ремонт</option>
                                            <option value="diagnostic">Диагностика</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Стоимость работы:</label>
                                        <input type="number" name="work_price" class="form-control" step="0.01" min="0" value="0">
                                    </div>
                                    <div class="form-group">
                                        <label>Стоимость запчасти:</label>
                                        <input type="number" name="part_price" class="form-control" step="0.01" min="0" value="0">
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn-1c-primary">Добавить</button>
                                    <button type="button" onclick="hideCustomForm()" class="btn-1c-primary">Отмена</button>
                                </div>
                            </form>
                        </div>

                        <!-- Основная форма осмотра -->
                        <form method="post" id="inspectionForm">
                            <input type="hidden" name="save_inspection" value="1">
                            
                            <table class="table-1c">
                                <thead>
                                    <tr>
                                        <th>Деталь/Работа</th>
                                        <th>Сторона</th>
                                        <th>Действие</th>
                                        <th>Работа, руб</th>
                                        <th>Запчасть, руб</th>
                                        <th>Итого</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody id="inspectionItems">
                                    <?php if (empty($inspection_data)): ?>
                                    <tr id="noItems">
                                        <td colspan="7" class="text-center">
                                            Нет добавленных пунктов
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($inspection_data as $item): ?>
                                        <tr>
                                            <td>
                                                <?= htmlspecialchars($item['custom_name'] ?? $item['template_name']) ?>
                                                <input type="hidden" name="inspection_items[<?= $item['id'] ?>][type]" value="<?= $item['item_type'] ?>">
                                                <input type="hidden" name="inspection_items[<?= $item['id'] ?>][item_id]" value="<?= $item['inspection_item_id'] ?>">
                                                <input type="hidden" name="inspection_items[<?= $item['id'] ?>][name]" value="<?= htmlspecialchars($item['custom_name'] ?? $item['template_name']) ?>">
                                                <input type="hidden" name="inspection_items[<?= $item['id'] ?>][side]" value="<?= $item['side'] ?>">
                                                <input type="hidden" name="inspection_items[<?= $item['id'] ?>][action]" value="<?= $item['action'] ?>">
                                                <input type="hidden" name="inspection_items[<?= $item['id'] ?>][work_price]" value="<?= $item['work_price'] ?>">
                                                <input type="hidden" name="inspection_items[<?= $item['id'] ?>][part_price]" value="<?= $item['part_price'] ?>">
                                            </td>
                                            <td><?= getSideLabel($item['side']) ?></td>
                                            <td><?= getActionLabel($item['action']) ?></td>
                                            <td><?= number_format($item['work_price'], 2) ?></td>
                                            <td><?= number_format($item['part_price'], 2) ?></td>
                                            <td><?= number_format($item['total_price'], 2) ?></td>
                                            <td>
                                                <button type="button" onclick="removeItem(this)" class="btn-1c btn-danger btn-sm">Удалить</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="text-right"><strong>Общая сумма:</strong></td>
                                        <td id="totalSum"><strong><?= number_format(array_sum(array_column($inspection_data, 'total_price')), 2) ?> руб</strong></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-1c-primary"">Сохранить осмотр</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    let itemCounter = <?= count($inspection_data) ?>;
    
    // Фильтрация пунктов при поиске
    document.getElementById('itemSearch').addEventListener('input', function(e) {
        const search = e.target.value.toLowerCase();
        document.querySelectorAll('.template-item').forEach(item => {
            const itemName = item.getAttribute('data-name');
            if (itemName.includes(search)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    // Добавление шаблонного пункта
    function addTemplateItem(itemId, name, side, action, workPrice, partPrice) {
        const item = {
            id: 'tpl_' + itemCounter++,
            type: 'template',
            item_id: itemId,
            name: name,
            side: side,
            action: action,
            work_price: workPrice || 0,
            part_price: partPrice || 0
        };
        
        addItemToTable(item);
    }
    
    // Показать/скрыть форму произвольной позиции
    function showCustomForm() {
        document.getElementById('customItemForm').style.display = 'block';
    }
    
    function hideCustomForm() {
        document.getElementById('customItemForm').style.display = 'none';
        document.getElementById('customForm').reset();
    }
    
    // Добавление произвольной позиции
    function addCustomItem(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        
        const item = {
            id: 'cust_' + itemCounter++,
            type: 'custom',
            name: formData.get('custom_name'),
            side: formData.get('side'),
            action: formData.get('action'),
            work_price: parseFloat(formData.get('work_price')) || 0,
            part_price: parseFloat(formData.get('part_price')) || 0
        };
        
        addItemToTable(item);
        hideCustomForm();
        form.reset();
    }
    
    // Добавление пункта в таблицу
    function addItemToTable(item) {
        const tbody = document.getElementById('inspectionItems');
        const noItemsRow = document.getElementById('noItems');
        
        if (noItemsRow) {
            noItemsRow.remove();
        }
        
        const totalPrice = item.work_price + item.part_price;
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                ${item.name}
                <input type="hidden" name="inspection_items[${item.id}][type]" value="${item.type}">
                <input type="hidden" name="inspection_items[${item.id}][item_id]" value="${item.item_id || ''}">
                <input type="hidden" name="inspection_items[${item.id}][name]" value="${item.name}">
                <input type="hidden" name="inspection_items[${item.id}][side]" value="${item.side}">
                <input type="hidden" name="inspection_items[${item.id}][action]" value="${item.action}">
                <input type="hidden" name="inspection_items[${item.id}][work_price]" value="${item.work_price}">
                <input type="hidden" name="inspection_items[${item.id}][part_price]" value="${item.part_price}">
            </td>
            <td>${getSideLabel(item.side)}</td>
            <td>${getActionLabel(item.action)}</td>
            <td>${item.work_price.toFixed(2)}</td>
            <td>${item.part_price.toFixed(2)}</td>
            <td>${totalPrice.toFixed(2)}</td>
            <td>
                <button type="button" onclick="removeItem(this)" class="btn-1c btn-danger btn-sm">Удалить</button>
            </td>
        `;
        
        tbody.appendChild(row);
        updateTotalSum();
    }
    
    // Удаление пункта
    function removeItem(button) {
        const row = button.closest('tr');
        row.remove();
        
        if (document.getElementById('inspectionItems').children.length === 0) {
            const tbody = document.getElementById('inspectionItems');
            tbody.innerHTML = `
                <tr id="noItems">
                    <td colspan="7" class="text-center">
                        Нет добавленных пунктов
                    </td>
                </tr>
            `;
        }
        
        updateTotalSum();
    }
    
    // Обновление общей суммы
    function updateTotalSum() {
        let total = 0;
        document.querySelectorAll('#inspectionItems tr').forEach(row => {
            if (row.id !== 'noItems') {
                const cells = row.cells;
                if (cells.length >= 6) {
                    const itemTotal = parseFloat(cells[5].textContent) || 0;
                    total += itemTotal;
                }
            }
        });
        document.getElementById('totalSum').innerHTML = '<strong>' + total.toFixed(2) + ' руб</strong>';
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
    
    // Инициализация при загрузке
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($inspection_data)): ?>
        const noItemsRow = document.getElementById('noItems');
        if (noItemsRow) {
            noItemsRow.remove();
        }
        <?php endif; ?>
    });
    </script>
	<?php include 'templates/footer.php'; ?>
</body>
</html>

<?php
function getSideLabel($side) {
    $labels = [
        'left' => 'Левая',
        'right' => 'Правая',
        'both' => 'Обе',
        'none' => '-'
    ];
    return $labels[$side] ?? $side;
}

function getActionLabel($action) {
    $labels = [
        'repair' => 'Ремонт',
        'replace' => 'Замена',
        'diagnostic' => 'Диагностика'
    ];
    return $labels[$action] ?? $action;
}
?>
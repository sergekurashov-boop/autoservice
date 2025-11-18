<?php
require 'includes/db.php';
session_start();
define('ACCESS', true);

$defect_id = $_GET['id'] ?? 0;

// Получаем данные ведомости
$stmt = $pdo->prepare("SELECT * FROM defects WHERE id = ?");
$stmt->execute([$defect_id]);
$defect = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$defect) {
    die("Ведомость не найдена");
}

// Получаем существующие позиции
$items_stmt = $pdo->prepare("SELECT * FROM defect_items WHERE defect_id = ?");
$items_stmt->execute([$defect_id]);
$existing_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем список услуг
$services = $pdo->query("SELECT * FROM services WHERE active = 1 ORDER BY name")->fetchAll();

// Обработка добавления услуги
if (isset($_POST['add_service'])) {
    $service_id = $_POST['service_id'];
    $quantity = $_POST['quantity'] ?? 1;
    
    // Получаем данные услуги
    $service_stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $service_stmt->execute([$service_id]);
    $service = $service_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($service) {
        $total = $service['price'] * $quantity;
        
        $insert_stmt = $pdo->prepare("
            INSERT INTO defect_items (defect_id, type, service_id, name, quantity, price, total, unit) 
            VALUES (?, 'service', ?, ?, ?, ?, ?, ?)
        ");
        $insert_stmt->execute([
            $defect_id, $service_id, $service['name'], $quantity, $service['price'], $total, $service['unit']
        ]);
        
        // Обновляем общую сумму ведомости
        updateDefectTotal($pdo, $defect_id);
        
        header("Location: defect_edit.php?id=$defect_id");
        exit;
    }
}

// Обработка добавления запчасти
if (isset($_POST['add_part'])) {
    $name = $_POST['part_name'];
    $manufacturer = $_POST['manufacturer'];
    $quantity = $_POST['part_quantity'] ?? 1;
    $price = $_POST['part_price'];
    $unit = $_POST['part_unit'] ?? 'шт.';
    
    $total = $price * $quantity;
    
    $insert_stmt = $pdo->prepare("
        INSERT INTO defect_items (defect_id, type, name, manufacturer, quantity, price, total, unit) 
        VALUES (?, 'part', ?, ?, ?, ?, ?, ?)
    ");
    $insert_stmt->execute([
        $defect_id, $name, $manufacturer, $quantity, $price, $total, $unit
    ]);
    
    updateDefectTotal($pdo, $defect_id);
    header("Location: defect_edit.php?id=$defect_id");
    exit;
}

// Функция для обновления общей суммы
function updateDefectTotal($pdo, $defect_id) {
    $total_stmt = $pdo->prepare("
        SELECT SUM(total) as grand_total 
        FROM defect_items 
        WHERE defect_id = ? AND type IN ('service', 'part')
    ");
    $total_stmt->execute([$defect_id]);
    $total = $total_stmt->fetch(PDO::FETCH_ASSOC)['grand_total'] ?? 0;
    
    $update_stmt = $pdo->prepare("UPDATE defects SET grand_total = ? WHERE id = ?");
    $update_stmt->execute([$total, $defect_id]);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование ведомости - АВТОСЕРВИС</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'templates/header.php';?>

        <div class="container">
            <!-- Заголовок -->
            <div class="header-compact">
                <h1 class="page-title-compact">РЕДАКТИРОВАНИЕ ДЕФЕКТНОЙ ВЕДОМОСТИ</h1>
                <div class="header-actions-compact">
                    <a href="defect_view.php?id=<?= $defect_id ?>" class="action-btn-compact">
                        <span class="action-icon">👁️</span>
                        <span class="action-label">Просмотр</span>
                    </a>
                    <a href="defects.php" class="action-btn-compact">
                        <span class="action-icon">←</span>
                        <span class="action-label">Назад</span>
                    </a>
                </div>
            </div>

            <div class="row-1c">
                <!-- Добавление услуги -->
                <div class="card-1c">
                    <div class="card-header-1c">
                        <h5>➕ ДОБАВИТЬ УСЛУГУ</h5>
                    </div>
                    <div style="padding: 1.5rem;">
                        <form method="POST">
                            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 1rem; align-items: end;">
                                <div>
                                    <label><strong>Услуга:</strong></label>
                                    <select name="service_id" required style="width: 100%; padding: 0.5rem; border: 1px solid #e6d8a8;">
                                        <option value="">-- Выберите услугу --</option>
                                        <?php foreach ($services as $service): ?>
                                        <option value="<?= $service['id'] ?>" data-price="<?= $service['price'] ?>">
                                            <?= htmlspecialchars($service['name']) ?> - <?= number_format($service['price'], 2, ',', ' ') ?> руб.
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label><strong>Кол-во:</strong></label>
                                    <input type="number" name="quantity" value="1" min="1" step="0.5" style="width: 100%; padding: 0.5rem; border: 1px solid #e6d8a8;">
                                </div>
                                <div>
                                    <label><strong>Цена:</strong></label>
                                    <input type="text" id="service_price" readonly style="width: 100%; padding: 0.5rem; border: 1px solid #e6d8a8; background: #f8f9fa;">
                                </div>
                                <div>
                                    <button type="submit" name="add_service" class="action-btn-compact primary">
                                        <span class="action-icon">✅</span>
                                        <span class="action-label">Добавить</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Добавление запчасти -->
                <div class="card-1c">
                    <div class="card-header-1c">
                        <h5>⚙️ ДОБАВИТЬ ЗАПЧАСТЬ</h5>
                    </div>
                    <div style="padding: 1.5rem;">
                        <form method="POST">
                            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 1rem; align-items: end;">
                                <div>
                                    <label><strong>Наименование:</strong></label>
                                    <input type="text" name="part_name" required placeholder="Название запчасти" style="width: 100%; padding: 0.5rem; border: 1px solid #e6d8a8;">
                                </div>
                                <div>
                                    <label><strong>Производитель:</strong></label>
                                    <input type="text" name="manufacturer" placeholder="Производитель" style="width: 100%; padding: 0.5rem; border: 1px solid #e6d8a8;">
                                </div>
                                <div>
                                    <label><strong>Кол-во:</strong></label>
                                    <input type="number" name="part_quantity" value="1" min="1" step="1" style="width: 100%; padding: 0.5rem; border: 1px solid #e6d8a8;">
                                </div>
                                <div>
                                    <label><strong>Цена:</strong></label>
                                    <input type="number" name="part_price" required step="0.01" min="0" placeholder="0.00" style="width: 100%; padding: 0.5rem; border: 1px solid #e6d8a8;">
                                </div>
                                <div>
                                    <button type="submit" name="add_part" class="action-btn-compact primary">
                                        <span class="action-icon">✅</span>
                                        <span class="action-label">Добавить</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Существующие позиции -->
            <div class="card-1c">
                <div class="card-header-1c">
                    <h5>📋 ТЕКУЩИЕ ПОЗИЦИИ</h5>
                </div>
                <div class="orders-table-container">
                    <table class="orders-table-enhanced">
                        <thead>
                            <tr>
                                <th class="col-id">#</th>
                                <th class="col-desc">Наименование</th>
                                <th class="col-status">Тип</th>
                                <th class="col-status">Кол-во</th>
                                <th class="col-amount">Цена</th>
                                <th class="col-amount">Сумма</th>
                                <th class="col-actions">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_amount = 0;
                            foreach ($existing_items as $index => $item): 
                                $total_amount += $item['total'];
                            ?>
                            <tr class="order-row">
                                <td class="order-id"><?= $index + 1 ?></td>
                                <td class="order-desc">
                                    <div class="desc-text"><?= htmlspecialchars($item['name']) ?></div>
                                    <?php if (!empty($item['manufacturer'])): ?>
                                    <div style="font-size: 0.8rem; color: #8b6914;">
                                        Производитель: <?= htmlspecialchars($item['manufacturer']) ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge-enhanced <?= $item['type'] === 'service' ? 'working' : 'diagnosis' ?>">
                                        <?= $item['type'] === 'service' ? '🔧 Услуга' : '⚙️ Запчасть' ?>
                                    </span>
                                </td>
                                <td><?= $item['quantity'] ?> <?= $item['unit'] ?></td>
                                <td class="order-amount"><?= number_format($item['price'], 2, ',', ' ') ?></td>
                                <td class="order-amount"><?= number_format($item['total'], 2, ',', ' ') ?></td>
                                <td class="order-actions">
                                    <div class="action-buttons">
                                        <a href="defect_item_delete.php?defect_id=<?= $defect_id ?>&item_id=<?= $item['id'] ?>" class="action-btn delete" title="Удалить">
                                            🗑️
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($existing_items)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem; color: #8b6914;">
                                    📋 Позиции не добавлены
                                </td>
                            </tr>
                            <?php endif; ?>
                            
                            <!-- Итог -->
                            <tr style="background: #fff8dc;">
                                <td colspan="5"><strong>ОБЩАЯ СУММА:</strong></td>
                                <td class="order-amount" colspan="2">
                                    <div class="amount-main"><?= number_format($total_amount, 2, ',', ' ') ?> руб.</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include 'templates/footer.php';?>

    <script>
        // Обновление цены при выборе услуги
        document.querySelector('select[name="service_id"]').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.getAttribute('data-price') || '0';
            document.getElementById('service_price').value = parseFloat(price).toLocaleString('ru-RU', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + ' руб.';
        });
    </script>
</body>
</html>
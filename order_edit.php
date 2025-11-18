<?php
// order_edit.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'includes/db.php';
session_start();

require_once 'auth_check.php';
requireAnyRole(['admin', 'manager']);

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "ID заказа не указан";
    header("Location: orders.php");
    exit;
}
$order_id = (int)$_GET['id'];

// Получаем информацию о заказе с проверкой ошибок
$order = null;
$order_services = [];
$order_parts = [];
$services = [];
$parts = [];

try {
    $stmt = $conn->prepare("
        SELECT o.id, o.car_id, o.description, o.status, o.total, o.created,
               o.services_data, o.parts_data, o.services_total, o.parts_total,
               c.make, c.model, c.year, c.license_plate, c.vin,
               cl.id AS client_id, cl.name AS client_name, cl.phone
        FROM orders o
        JOIN cars c ON o.car_id = c.id
        JOIN clients cl ON c.client_id = cl.id
        WHERE o.id = ?
    ");

    if (!$stmt) {
        throw new Exception("Ошибка подготовки запроса заказа: " . $conn->error);
    }

    $stmt->bind_param('i', $order_id);
    if (!$stmt->execute()) {
        throw new Exception("Ошибка выполнения запроса: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    if (!$order) {
        $_SESSION['error'] = "Заказ №{$order_id} не найден";
        header("Location: orders.php");
        exit;
    }

    // Функция для миграции старых данных в JSON
    function migrateOrderData($conn, $order_id) {
        // Получаем услуги из order_services
        $stmt_services = $conn->prepare("
            SELECT os.service_id, os.quantity, os.price, s.name, s.unit
            FROM order_services os
            JOIN services s ON os.service_id = s.id
            WHERE os.order_id = ?
        ");
        $stmt_services->bind_param('i', $order_id);
        $stmt_services->execute();
        $services_result = $stmt_services->get_result();
        $services_data = $services_result->fetch_all(MYSQLI_ASSOC);
        
        // Получаем запчасти из order_parts
        $stmt_parts = $conn->prepare("
            SELECT op.part_id, op.quantity, p.name, p.part_number, p.price
            FROM order_parts op
            JOIN parts p ON op.part_id = p.id
            WHERE op.order_id = ?
        ");
        $stmt_parts->bind_param('i', $order_id);
        $stmt_parts->execute();
        $parts_result = $stmt_parts->get_result();
        $parts_data = $parts_result->fetch_all(MYSQLI_ASSOC);
        
        // Рассчитываем суммы
        $services_total = 0;
        $parts_total = 0;
        
        foreach ($services_data as $service) {
            $services_total += $service['price'] * $service['quantity'];
        }
        
        foreach ($parts_data as $part) {
            $parts_total += $part['price'] * $part['quantity'];
        }
        
        $total = $services_total + $parts_total;
        
        // Сохраняем в JSON поля
        $services_json = json_encode($services_data, JSON_UNESCAPED_UNICODE);
        $parts_json = json_encode($parts_data, JSON_UNESCAPED_UNICODE);
        
        $stmt = $conn->prepare("UPDATE orders SET 
            services_data = ?, 
            parts_data = ?, 
            services_total = ?, 
            parts_total = ?, 
            total = ? 
            WHERE id = ?");
        
        $stmt->bind_param('ssdddi', $services_json, $parts_json, $services_total, $parts_total, $total, $order_id);
        return $stmt->execute();
    }

    // Функция обновления заказа с JSON данными
    function updateOrderWithJSON($conn, $order_id, $services_data, $parts_data) {
        $services_total = 0;
        $parts_total = 0;
        
        foreach ($services_data as $service) {
            $services_total += $service['price'] * $service['quantity'];
        }
        
        foreach ($parts_data as $part) {
            $parts_total += $part['price'] * $part['quantity'];
        }
        
        $total = $services_total + $parts_total;
        
        $stmt = $conn->prepare("UPDATE orders SET 
            services_data = ?, 
            parts_data = ?, 
            services_total = ?, 
            parts_total = ?, 
            total = ? 
            WHERE id = ?");
        
        $services_json = json_encode($services_data, JSON_UNESCAPED_UNICODE);
        $parts_json = json_encode($parts_data, JSON_UNESCAPED_UNICODE);
        
        $stmt->bind_param('ssdddi', $services_json, $parts_json, $services_total, $parts_total, $total, $order_id);
        return $stmt->execute();
    }

    // Получаем услуги из JSON
    if (!empty($order['services_data']) && $order['services_data'] != 'null') {
        $decoded = json_decode($order['services_data'], true);
        if (is_array($decoded)) {
            $order_services = $decoded;
        }
    } else {
        // Мигрируем данные если JSON пустой
        migrateOrderData($conn, $order_id);
        // Перезагружаем данные
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        if (!empty($order['services_data']) && $order['services_data'] != 'null') {
            $order_services = json_decode($order['services_data'], true) ?: [];
        }
    }

    // Получаем запчасти из JSON
    if (!empty($order['parts_data']) && $order['parts_data'] != 'null') {
        $decoded = json_decode($order['parts_data'], true);
        if (is_array($decoded)) {
            $order_parts = $decoded;
        }
    }

    // Получаем список всех услуг
    $services_result = $conn->query("SELECT id, name, price, unit FROM services ORDER BY name");
    if ($services_result) {
        $services = $services_result->fetch_all(MYSQLI_ASSOC);
    }

    // Получаем список всех запчастей
    $parts_result = $conn->query("SELECT id, name, part_number, price FROM parts ORDER BY name");
    if ($parts_result) {
        $parts = $parts_result->fetch_all(MYSQLI_ASSOC);
    }

} catch (Exception $e) {
    $_SESSION['error'] = "Ошибка загрузки данных заказа: " . $e->getMessage();
    header("Location: orders.php");
    exit;
}

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // [Ваш существующий код обработки POST запросов остается без изменений]
    // Добавление услуги
    if (isset($_POST['add_service'])) {
        $service_id = (int)$_POST['service_id'];
        $quantity = (int)$_POST['quantity'];
        $price = (float)$_POST['price'];

        if ($service_id > 0 && $quantity > 0 && $price >= 0) {
            $selected_service = null;
            foreach ($services as $service) {
                if ($service['id'] == $service_id) {
                    $selected_service = $service;
                    break;
                }
            }
            
            if ($selected_service) {
                $new_service = [
                    'service_id' => $service_id,
                    'name' => $selected_service['name'],
                    'quantity' => $quantity,
                    'price' => $price,
                    'unit' => $selected_service['unit']
                ];
                
                // Проверяем, не добавлена ли уже эта услуга
                $found = false;
                foreach ($order_services as &$existing_service) {
                    if ($existing_service['service_id'] == $service_id) {
                        $existing_service['quantity'] += $quantity;
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    $order_services[] = $new_service;
                }
                
                if (updateOrderWithJSON($conn, $order_id, $order_services, $order_parts)) {
                    $_SESSION['success'] = "Услуга добавлена";
                } else {
                    $_SESSION['error'] = "Ошибка сохранения услуги";
                }
            }
        }
        header("Location: order_edit.php?id=" . $order_id);
        exit;
    }
    
    // [Остальные обработчики POST остаются без изменений...]
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование заказа №<?= $order_id ?> - Autoservice</title>
    <link href="assets/css/orders.css" rel="stylesheet">
    <style>
        .order-edit-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 20px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .order-info-sidebar {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .order-header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #3498db;
        }
        
        .order-icon {
            font-size: 48px;
            margin-bottom: 10px;
            display: block;
        }
        
        .order-id {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .order-status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-top: 10px;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-in-progress { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-delivered { background: #e2e3e5; color: #383d41; }
        
        .info-block {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        
        .info-label {
            font-size: 0.8rem;
            color: #95a5a6;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 1rem;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .client-avatar {
            width: 60px;
            height: 60px;
            background: #3498db;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            margin-right: 15px;
        }
        
        .client-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }
        
        .car-info {
            background: white;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #e9ecef;
            margin-bottom: 20px;
        }
        
        .car-icon {
            font-size: 2rem;
            margin-right: 15px;
            color: #e74c3c;
        }
        
        .form-main-content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-icon {
            font-size: 1.5rem;
            margin-right: 10px;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        
        .services-grid, .parts-grid {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 15px;
            align-items: end;
            margin-bottom: 20px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .items-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }
        
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .items-table tr:hover {
            background: #f8f9fa;
        }
        
        .cost-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .cost-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 1rem;
        }
        
        .cost-total {
            font-weight: 700;
            font-size: 1.3rem;
            color: #2c3e50;
            border-top: 2px solid #dee2e6;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
        }
    </style>
</head>
<body>
    <?php include 'templates/header.php'; ?>
    
    <div class="orders-container">
        <div class="container-header">
            <h1 class="page-title">
                <span class="page-title-icon">🔧</span>
                Редактирование заказа
            </h1>
            <div class="header-actions">
                <a href="orders.php" class="btn-1c-outline">← Назад к заказам</a>
                <a href="order_parts_management.php?order_id=<?= $order_id ?>" class="btn-1c-outline">
                    📦 Управление запчастями
                </a>
                <a href="order_print.php?id=<?= $order_id ?>" class="btn-1c-outline" target="_blank">
                    🖨️ Печать
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-enhanced alert-danger">
                <?= $_SESSION['error'] ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-enhanced alert-success">
                <?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (!$order): ?>
            <div class="alert-enhanced alert-danger">
                ❌ Ошибка: Заказ не найден или произошла ошибка при загрузке данных
            </div>
        <?php else: ?>

        <div class="order-edit-container">
            <!-- Левая колонка - информация о заказе -->
            <div class="order-info-sidebar">
                <div class="order-header">
                    <span class="order-icon">📋</span>
                    <div class="order-id">Заказ №<?= $order_id ?></div>
                    <div class="order-status <?= 
                        $order['status'] == 'В ожидании' ? 'status-pending' : 
                        ($order['status'] == 'В работе' ? 'status-in-progress' :
                        ($order['status'] == 'Готов' ? 'status-completed' : 'status-delivered')) 
                    ?>">
                        <?= htmlspecialchars($order['status'] ?? 'Не указан') ?>
                    </div>
                </div>
                
                <div class="client-info">
                    <div class="client-avatar">
                        <?= strtoupper(mb_substr($order['client_name'] ?? '?', 0, 1)) ?>
                    </div>
                    <div>
                        <div class="info-value" style="font-weight: 700;"><?= htmlspecialchars($order['client_name'] ?? 'Не указан') ?></div>
                        <div class="info-label">📞 <?= htmlspecialchars($order['phone'] ?? 'Не указан') ?></div>
                    </div>
                </div>
                
                <div class="car-info">
                    <div style="display: flex; align-items: center; margin-bottom: 10px;">
                        <span class="car-icon">🚗</span>
                        <div>
                            <div class="info-value" style="font-weight: 700;">
                                <?= htmlspecialchars($order['make'] ?? '') ?> <?= htmlspecialchars($order['model'] ?? '') ?>
                            </div>
                            <div class="info-label"><?= $order['year'] ?? '' ?> года</div>
                        </div>
                    </div>
                    
                    <?php if (!empty($order['vin'])): ?>
                    <div class="info-block">
                        <div class="info-label">VIN</div>
                        <div class="info-value"><?= htmlspecialchars($order['vin']) ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($order['license_plate'])): ?>
                    <div class="info-block">
                        <div class="info-label">Гос. номер</div>
                        <div class="info-value"><?= htmlspecialchars($order['license_plate']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="info-block">
                    <div class="info-label">Дата создания</div>
                    <div class="info-value"><?= date('d.m.Y в H:i', strtotime($order['created'] ?? 'now')) ?></div>
                </div>
                
                <div class="cost-summary">
                    <div class="cost-item">
                        <span>Услуги:</span>
                        <span><?= number_format($order['services_total'] ?? 0, 2) ?> руб.</span>
                    </div>
                    <div class="cost-item">
                        <span>Запчасти:</span>
                        <span><?= number_format($order['parts_total'] ?? 0, 2) ?> руб.</span>
                    </div>
                    <div class="cost-item cost-total">
                        <span>Итого:</span>
                        <span><?= number_format($order['total'] ?? 0, 2) ?> руб.</span>
                    </div>
                </div>
            </div>

            <!-- Правая колонка - форма редактирования -->
            <div class="form-main-content">
                <form method="post" id="orderForm">
                    <!-- Общие данные -->
                    <div class="form-section">
                        <div class="section-header">
                            <span class="section-icon">📝</span>
                            <h3 class="section-title">Общие данные заказа</h3>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Описание проблемы / работ</label>
                            <textarea name="description" class="form-control textarea-large" rows="4"><?= htmlspecialchars($order['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group" style="max-width: 300px;">
                            <label class="form-label">Статус заказа</label>
                            <select name="status" class="form-control">
                                <option value="В ожидании" <?= ($order['status'] ?? '') == 'В ожидании' ? 'selected' : '' ?>>В ожидании</option>
                                <option value="В работе" <?= ($order['status'] ?? '') == 'В работе' ? 'selected' : '' ?>>В работе</option>
                                <option value="Готов" <?= ($order['status'] ?? '') == 'Готов' ? 'selected' : '' ?>>Готов</option>
                                <option value="Выдан" <?= ($order['status'] ?? '') == 'Выдан' ? 'selected' : '' ?>>Выдан</option>
                            </select>
                        </div>
                    </div>

                    <!-- Услуги -->
                    <div class="form-section">
                        <div class="section-header">
                            <span class="section-icon">🛠️</span>
                            <h3 class="section-title">Услуги</h3>
                            <button type="button" class="btn-1c-outline ml-auto" data-bs-toggle="modal" data-bs-target="#newServiceModal">
                                + Новая услуга
                            </button>
                        </div>
                        
                        <?php if (count($order_services) > 0): ?>
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Услуга</th>
                                    <th width="120">Кол-во</th>
                                    <th width="150">Цена за ед.</th>
                                    <th width="150">Сумма</th>
                                    <th width="80">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_services as $service): 
                                    $sum = $service['price'] * $service['quantity'];
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($service['name']) ?></strong>
                                        </td>
                                        <td><?= $service['quantity'] ?> <?= htmlspecialchars($service['unit']) ?></td>
                                        <td><?= number_format($service['price'], 2) ?> руб.</td>
                                        <td><strong><?= number_format($sum, 2) ?> руб.</strong></td>
                                        <td>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="service_id" value="<?= $service['service_id'] ?>">
                                                <button type="submit" name="remove_service" class="btn-danger-sm" 
                                                        onclick="return confirm('Удалить услугу из заказа?')">
                                                    🗑️
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="empty-state">
                            <i>🛠️</i>
                            <div>Нет услуг в заказе</div>
                        </div>
                        <?php endif; ?>

                        <h5 style="margin: 25px 0 15px 0;">Добавить услугу</h5>
                        <form method="post" class="services-grid">
                            <div class="form-group">
                                <label class="form-label">Услуга</label>
                                <select name="service_id" class="form-control" required>
                                    <option value="">Выберите услугу</option>
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?= $service['id'] ?>" data-price="<?= $service['price'] ?>">
                                            <?= htmlspecialchars($service['name']) ?> (<?= number_format($service['price'], 2) ?> руб.)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Кол-во</label>
                                <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Цена за ед., руб.</label>
                                <input type="number" step="0.01" name="price" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" name="add_service" class="btn-1c-primary">
                                    + Добавить
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Запчасти -->
                    <div class="form-section">
                        <div class="section-header">
                            <span class="section-icon">🔧</span>
                            <h3 class="section-title">Запчасти</h3>
                            <button type="button" class="btn-1c-outline ml-auto" data-bs-toggle="modal" data-bs-target="#newPartModal">
                                + Новая запчасть
                            </button>
                        </div>
                        
                        <?php if (count($order_parts) > 0): ?>
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Запчасть</th>
                                    <th width="120">Артикул</th>
                                    <th width="100">Кол-во</th>
                                    <th width="150">Цена за ед.</th>
                                    <th width="150">Сумма</th>
                                    <th width="80">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_parts as $part): 
                                    $sum = $part['price'] * $part['quantity'];
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($part['name']) ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($part['part_number']) ?></td>
                                        <td><?= $part['quantity'] ?></td>
                                        <td><?= number_format($part['price'], 2) ?> руб.</td>
                                        <td><strong><?= number_format($sum, 2) ?> руб.</strong></td>
                                        <td>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="part_id" value="<?= $part['part_id'] ?>">
                                                <button type="submit" name="remove_part" class="btn-danger-sm"
                                                        onclick="return confirm('Удалить запчасть из заказа?')">
                                                    🗑️
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="empty-state">
                            <i>🔧</i>
                            <div>Нет запчастей в заказе</div>
                        </div>
                        <?php endif; ?>

                        <h5 style="margin: 25px 0 15px 0;">Добавить запчасть</h5>
                        <form method="post" class="parts-grid">
                            <div class="form-group">
                                <label class="form-label">Запчасть</label>
                                <select name="part_id" class="form-control" required>
                                    <option value="">Выберите запчасть</option>
                                    <?php foreach ($parts as $part): ?>
                                        <option value="<?= $part['id'] ?>">
                                            <?= htmlspecialchars($part['name']) ?> (<?= $part['part_number'] ?>) - <?= number_format($part['price'], 2) ?> руб.
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Кол-во</label>
                                <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" name="add_part" class="btn-1c-primary">
                                    + Добавить
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_order" class="btn-1c-primary btn-large">
                            💾 Сохранить заказ
                        </button>
                        <a href="orders.php" class="btn-1c-outline">Отмена</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Модальные окна (добавьте ваши модальные окна здесь) -->

    <script>
        // Обновление цены при выборе услуги
        document.querySelector('select[name="service_id"]')?.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.getAttribute('data-price')) {
                document.querySelector('input[name="price"]').value = selectedOption.getAttribute('data-price');
            }
        });

        // Подтверждение удаления
        function confirmAction(message) {
            return confirm(message);
        }
    </script>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
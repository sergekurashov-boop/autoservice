<?php
// order_edit_with_parts_check.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'includes/db.php';
session_start();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Ошибка: ID заказа не указан");
}
$order_id = (int)$_GET['id'];

// Получаем информацию о заказе
$order = [];
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
    die("Ошибка подготовки запроса заказа: " . $conn->error);
}

$stmt->bind_param('i', $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    die("Заказ не найден");
}

// ПРОВЕРКА НЕВЫДАННЫХ ЗАПЧАСТЕЙ
$pending_parts_sql = "
    SELECT COUNT(*) as pending_count 
    FROM order_parts 
    WHERE order_id = ? AND issue_status = 'reserved' AND source_type = 'service_warehouse'
";
$pending_stmt = $conn->prepare($pending_parts_sql);
$pending_stmt->bind_param("i", $order_id);
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result()->fetch_assoc();
$pending_parts_count = $pending_result['pending_count'] ?? 0;

// Получаем детали невыданных запчастей для уведомления
$pending_details_sql = "
    SELECT op.part_id, p.name, op.quantity 
    FROM order_parts op 
    JOIN parts p ON op.part_id = p.id 
    WHERE op.order_id = ? AND op.issue_status = 'reserved' AND op.source_type = 'service_warehouse'
";
$pending_details_stmt = $conn->prepare($pending_details_sql);
$pending_details_stmt->bind_param("i", $order_id);
$pending_details_stmt->execute();
$pending_parts_details = $pending_details_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

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
$order_services = [];
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
$order_parts = [];
if (!empty($order['parts_data']) && $order['parts_data'] != 'null') {
    $decoded = json_decode($order['parts_data'], true);
    if (is_array($decoded)) {
        $order_parts = $decoded;
    }
}

// Получаем список всех услуг
$services = [];
$services_result = $conn->query("SELECT id, name, price, unit FROM services ORDER BY name");
if ($services_result) {
    $services = $services_result->fetch_all(MYSQLI_ASSOC);
}

// Получаем список всех запчастей
$parts = [];
$parts_result = $conn->query("SELECT id, name, part_number, price FROM parts ORDER BY name");
if ($parts_result) {
    $parts = $parts_result->fetch_all(MYSQLI_ASSOC);
}

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ПРОВЕРКА ПРИ ИЗМЕНЕНИИ СТАТУСА НА "ГОТОВ" ИЛИ "ВЫДАН"
    if (isset($_POST['update_order']) && in_array($_POST['status'], ['Готов', 'Выдан'])) {
        if ($pending_parts_count > 0) {
            $_SESSION['error'] = "Невозможно завершить заказ! Имеются невыданные запчасти со склада.";
            header("Location: order_edit.php?id=" . $order_id);
            exit;
        }
    }
    
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
    
    // Удаление услуги
    elseif (isset($_POST['remove_service'])) {
        $service_id = (int)$_POST['service_id'];
        
        $order_services = array_filter($order_services, function($service) use ($service_id) {
            return $service['service_id'] != $service_id;
        });
        
        if (updateOrderWithJSON($conn, $order_id, $order_services, $order_parts)) {
            $_SESSION['success'] = "Услуга удалена";
        } else {
            $_SESSION['error'] = "Ошибка удаления услуги";
        }
        header("Location: order_edit.php?id=" . $order_id);
        exit;
    }
    
    // Добавление запчасти
    elseif (isset($_POST['add_part'])) {
        $part_id = (int)$_POST['part_id'];
        $quantity = (int)$_POST['quantity'];

        if ($part_id > 0 && $quantity > 0) {
            $selected_part = null;
            foreach ($parts as $part) {
                if ($part['id'] == $part_id) {
                    $selected_part = $part;
                    break;
                }
            }
            
            if ($selected_part) {
                $new_part = [
                    'part_id' => $part_id,
                    'name' => $selected_part['name'],
                    'part_number' => $selected_part['part_number'],
                    'quantity' => $quantity,
                    'price' => $selected_part['price']
                ];
                
                $found = false;
                foreach ($order_parts as &$existing_part) {
                    if ($existing_part['part_id'] == $part_id) {
                        $existing_part['quantity'] += $quantity;
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    $order_parts[] = $new_part;
                }
                
                if (updateOrderWithJSON($conn, $order_id, $order_services, $order_parts)) {
                    $_SESSION['success'] = "Запчасть добавлена";
                } else {
                    $_SESSION['error'] = "Ошибка сохранения запчасти";
                }
            }
        }
        header("Location: order_edit.php?id=" . $order_id);
        exit;
    }
    
    // Удаление запчасти
    elseif (isset($_POST['remove_part'])) {
        $part_id = (int)$_POST['part_id'];
        
        $order_parts = array_filter($order_parts, function($part) use ($part_id) {
            return $part['part_id'] != $part_id;
        });
        
        if (updateOrderWithJSON($conn, $order_id, $order_services, $order_parts)) {
            $_SESSION['success'] = "Запчасть удалена";
        } else {
            $_SESSION['error'] = "Ошибка удаления запчасти";
        }
        header("Location: order_edit.php?id=" . $order_id);
        exit;
    }
    
    // Обновление заказа
    elseif (isset($_POST['update_order'])) {
        $description = trim($_POST['description']);
        $status = trim($_POST['status']);

        $stmt = $conn->prepare("UPDATE orders SET description = ?, status = ? WHERE id = ?");
        $stmt->bind_param('ssi', $description, $status, $order_id);
        
        if ($stmt->execute()) {
            updateOrderWithJSON($conn, $order_id, $order_services, $order_parts);
            $_SESSION['success'] = "Заказ обновлен";
        } else {
            $_SESSION['error'] = "Ошибка обновления заказа";
        }
        header("Location: order_edit.php?id=" . $order_id);
        exit;
    }
    
    // Создание услуги
    elseif (isset($_POST['create_service'])) {
        $name = trim($_POST['service_name']);
        $price = (float)$_POST['service_price'];
        $unit = trim($_POST['service_unit']);

        if (!empty($name) && $price > 0) {
            $stmt = $conn->prepare("INSERT INTO services (name, price, unit) VALUES (?, ?, ?)");
            $stmt->bind_param('sds', $name, $price, $unit);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Услуга создана";
            } else {
                $_SESSION['error'] = "Ошибка создания услуги";
            }
        }
        header("Location: order_edit.php?id=" . $order_id);
        exit;
    }
    
    // Создание запчасти
    elseif (isset($_POST['create_part'])) {
        $name = trim($_POST['part_name']);
        $part_number = trim($_POST['part_number']);
        $price = (float)$_POST['part_price'];

        if (!empty($name) && $price > 0) {
            $stmt = $conn->prepare("INSERT INTO parts (name, part_number, price) VALUES (?, ?, ?)");
            $stmt->bind_param('ssd', $name, $part_number, $price);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Запчасть создана";
            } else {
                $_SESSION['error'] = "Ошибка создания запчасти";
            }
        }
        header("Location: order_edit.php?id=" . $order_id);
        exit;
    }
}

include 'templates/header.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование заказа #<?= $order_id ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/icons/bootstrap-icons/font/bootstrap-icons.css">
    <style>
    body {
        background-color: #FFE4B5;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .card-header {
        border-radius: 10px 10px 0 0 !important;
        font-weight: 600;
    }
    
    .table th {
        background-color: #f8f9fa;
        border-top: none;
        font-weight: 600;
        color: #495057;
    }
    
    .btn {
        border-radius: 6px;
        font-weight: 500;
    }
    
    .alert {
        border: none;
        border-radius: 8px;
        border-left: 4px solid;
    }
    
    .border.rounded {
        background: white;
    }
    
    .pending-warning {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-left: 4px solid #ffc107;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
    }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- УВЕДОМЛЕНИЕ О НЕВЫДАННЫХ ЗАПЧАСТЯХ -->
        <?php if ($pending_parts_count > 0 && in_array($order['status'], ['В ожидании', 'В работе'])): ?>
        <div class="pending-warning">
            <h5><i class="bi bi-exclamation-triangle text-warning me-2"></i>Внимание!</h5>
            <p class="mb-2">В заказе имеются <strong><?= $pending_parts_count ?></strong> невыданных запчастей со склада:</p>
            <ul class="mb-2">
                <?php foreach ($pending_parts_details as $part): ?>
                <li><?= htmlspecialchars($part['name']) ?> (<?= $part['quantity'] ?> шт.)</li>
                <?php endforeach; ?>
            </ul>
            <p class="mb-0">Для завершения заказа необходимо выдать все запчасти со склада.</p>
            <a href="order_parts_management.php?order_id=<?= $order_id ?>" class="btn btn-warning btn-sm mt-2">
                <i class="bi bi-box-seam me-1"></i> Перейти к управлению запчастями
            </a>
        </div>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Редактирование заказа #<?= $order_id ?></h1>
            <div>
                <a href="orders.php" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left"></i> Назад
                </a>
                <a href="order_parts_management.php?order_id=<?= $order_id ?>" class="btn btn-outline-info me-2">
                    <i class="bi bi-box-seam"></i> Управление запчастями
                </a>
                <a href="order_print.php?id=<?= $order_id ?>" class="btn btn-outline-primary me-2" target="_blank">
                    <i class="bi bi-printer"></i> Печать
                </a>
                <button type="submit" form="orderForm" name="update_order" class="btn btn-success">
                    <i class="bi bi-check-lg"></i> Сохранить
                </button>
            </div>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <form method="post" id="orderForm">
            <!-- Информация о заказе -->
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <i class="bi bi-info-circle me-2"></i>Информация о заказе
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="border rounded p-3 mb-3">
                                <h5><i class="bi bi-person me-2"></i>Клиент</h5>
                                <p class="mb-1"><strong><?= htmlspecialchars($order['client_name']) ?></strong></p>
                                <p class="mb-0 text-muted"><?= htmlspecialchars($order['phone']) ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 mb-3">
                                <h5><i class="bi bi-car-front me-2"></i>Автомобиль</h5>
                                <p class="mb-1"><strong><?= htmlspecialchars($order['make']) ?> <?= htmlspecialchars($order['model']) ?> (<?= $order['year'] ?>)</strong></p>
                                <?php if (!empty($order['vin'])): ?>
                                <p class="mb-1 text-muted">VIN: <?= htmlspecialchars($order['vin']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($order['license_plate'])): ?>
                                <p class="mb-0 text-muted">Гос. номер: <?= htmlspecialchars($order['license_plate']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <h5><i class="bi bi-calendar me-2"></i>Дата создания</h5>
                                <p class="mb-0"><?= date('d.m.Y H:i', strtotime($order['created'])) ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <h5><i class="bi bi-tag me-2"></i>Статус</h5>
                                <span class="badge 
                                    <?= $order['status'] == 'В ожидании' ? 'bg-warning' : '' ?>
                                    <?= $order['status'] == 'В работе' ? 'bg-info' : '' ?>
                                    <?= $order['status'] == 'Готов' ? 'bg-success' : '' ?>
                                    <?= $order['status'] == 'Выдан' ? 'bg-secondary' : '' ?>
                                ">
                                    <?= $order['status'] ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <h5><i class="bi bi-currency-dollar me-2"></i>Сумма заказа</h5>
                                <p class="mb-0 fs-5 fw-bold text-primary"><?= number_format($order['total'], 2) ?> руб.</p>
                                <?php if ($order['services_total'] > 0): ?>
                                <small class="text-muted">Услуги: <?= number_format($order['services_total'], 2) ?> руб.</small><br>
                                <?php endif; ?>
                                <?php if ($order['parts_total'] > 0): ?>
                                <small class="text-muted">Запчасти: <?= number_format($order['parts_total'], 2) ?> руб.</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Общие данные -->
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <i class="bi bi-pencil-square me-2"></i>Общие данные заказа
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Описание проблемы</label>
                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($order['description']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Статус</label>
                        <select name="status" class="form-control" <?= $pending_parts_count > 0 ? 'onchange="checkPendingParts(this)"' : '' ?>>
                            <option value="В ожидании" <?= $order['status'] == 'В ожидании' ? 'selected' : '' ?>>В ожидании</option>
                            <option value="В работе" <?= $order['status'] == 'В работе' ? 'selected' : '' ?>>В работе</option>
                            <option value="Готов" <?= $order['status'] == 'Готов' ? 'selected' : '' ?>>Готов</option>
                            <option value="Выдан" <?= $order['status'] == 'Выдан' ? 'selected' : '' ?>>Выдан</option>
                        </select>
                        <?php if ($pending_parts_count > 0): ?>
                        <div class="form-text text-warning">
                            <i class="bi bi-exclamation-triangle"></i> 
                            При смене статуса на "Готов" или "Выдан" система проверит невыданные запчасти
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>

        <!-- Услуги -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-tools me-2"></i>Услуги</span>
                <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#newServiceModal">
                    <i class="bi bi-plus-circle me-1"></i> Новая услуга
                </button>
            </div>
            <div class="card-body">
                <?php if (count($order_services) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Услуга</th>
                                <th width="120">Количество</th>
                                <th width="120">Цена за ед.</th>
                                <th width="120">Сумма</th>
                                <th width="80">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_services as $service): 
                                $sum = $service['price'] * $service['quantity'];
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($service['name']) ?></td>
                                    <td><?= $service['quantity'] ?> <?= htmlspecialchars($service['unit']) ?></td>
                                    <td><?= number_format($service['price'], 2) ?> руб.</td>
                                    <td><strong><?= number_format($sum, 2) ?> руб.</strong></td>
                                    <td>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="service_id" value="<?= $service['service_id'] ?>">
                                            <button type="submit" name="remove_service" class="btn btn-danger btn-sm">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-inbox display-4 d-block mb-2"></i>
                    Нет услуг в заказе
                </div>
                <?php endif; ?>

                <h5 class="mt-4 mb-3">Добавить услугу в заказ</h5>
                <form method="post">
                    <div class="row g-3">
                        <div class="col-md-5">
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
                        <div class="col-md-2">
                            <label class="form-label">Количество</label>
                            <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Цена за ед., руб.</label>
                            <input type="number" step="0.01" name="price" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" name="add_service" class="btn btn-success w-100">
                                <i class="bi bi-plus-lg me-1"></i> Добавить
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Запчасти -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-gear me-2"></i>Запчасти</span>
                <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#newPartModal">
                    <i class="bi bi-plus-circle me-1"></i> Новая запчасть
                </button>
            </div>
            <div class="card-body">
                <?php if (count($order_parts) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Запчасть</th>
                                <th width="120">Артикул</th>
                                <th width="100">Количество</th>
                                <th width="120">Цена за ед.</th>
                                <th width="120">Сумма</th>
                                <th width="80">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_parts as $part): 
                                $sum = $part['price'] * $part['quantity'];
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($part['name']) ?></td>
                                    <td><?= htmlspecialchars($part['part_number']) ?></td>
                                    <td><?= $part['quantity'] ?></td>
                                    <td><?= number_format($part['price'], 2) ?> руб.</td>
                                    <td><strong><?= number_format($sum, 2) ?> руб.</strong></td>
                                    <td>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="part_id" value="<?= $part['part_id'] ?>">
                                            <button type="submit" name="remove_part" class="btn btn-danger btn-sm">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-inbox display-4 d-block mb-2"></i>
                    Нет запчастей в заказе
                </div>
                <?php endif; ?>

                <h5 class="mt-4 mb-3">Добавить запчасть в заказ</h5>
                <form method="post">
                    <div class="row g-3">
                        <div class="col-md-5">
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
                        <div class="col-md-2">
                            <label class="form-label">Количество</label>
                            <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" name="add_part" class="btn btn-success w-100">
                                <i class="bi bi-plus-lg me-1"></i> Добавить
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Кнопка сохранения -->
        <div class="text-center mt-4 mb-4">
            <button type="submit" form="orderForm" name="update_order" class="btn btn-success btn-lg">
                <i class="bi bi-check-lg me-2"></i> Сохранить заказ
            </button>
        </div>
    </div>

    <!-- Модальные окна -->
    <div class="modal fade" id="newServiceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Создание новой услуги</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Название услуги *</label>
                            <input type="text" name="service_name" class="form-control" required>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Цена *</label>
                                <input type="number" step="0.01" min="0.01" name="service_price" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Единица измерения</label>
                                <input type="text" name="service_unit" class="form-control" value="шт.">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" name="create_service" class="btn btn-primary">Создать услугу</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newPartModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Создание новой запчасти</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Название запчасти *</label>
                            <input type="text" name="part_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Артикул</label>
                            <input type="text" name="part_number" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Цена *</label>
                            <input type="number" step="0.01" min="0.01" name="part_price" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" name="create_part" class="btn btn-primary">Создать запчасть</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Обновление цены при выборе услуги
        document.querySelector('select[name="service_id"]').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.getAttribute('data-price')) {
                document.querySelector('input[name="price"]').value = selectedOption.getAttribute('data-price');
            }
        });

        // Проверка невыданных запчастей при смене статуса
        function checkPendingParts(select) {
            const newStatus = select.value;
            if (newStatus === 'Готов' || newStatus === 'Выдан') {
                if (!confirm('Внимание! В заказе есть невыданные запчасти со склада. Вы уверены, что хотите завершить заказ?')) {
                    select.value = '<?= $order['status'] ?>';
                }
            }
        }
    </script>
    <?php include 'templates/footer.php'; ?>
</body>
</html>
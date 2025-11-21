<?php
// order_edit.php
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
<<<<<<< Updated upstream
=======

    if (!$order) {
        $_SESSION['error'] = "Заказ №{$order_id} не найден";
        header("Location: orders.php");
        exit;
    }

    // Функция для миграции старых данных в JSON
    function migrateOrderData($conn, $order_id) {
        // Получаем услуги из order_services
        $stmt_services = $conn->prepare("
            SELECT os.service_id, os.quantity, os.price, s.name, s.unit, s.code
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
>>>>>>> Stashed changes
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

<<<<<<< Updated upstream
// Получаем список всех услуг
$services = [];
$services_result = $conn->query("SELECT id, name, price, unit FROM services ORDER BY name");
if ($services_result) {
    $services = $services_result->fetch_all(MYSQLI_ASSOC);
}
=======
    // Получаем список всех услуг (С КОДАМИ)
    $services_result = $conn->query("SELECT id, name, code, price, unit FROM services ORDER BY name");
    if ($services_result) {
        $services = $services_result->fetch_all(MYSQLI_ASSOC);
    }
>>>>>>> Stashed changes

// Получаем список всех запчастей
$parts = [];
$parts_result = $conn->query("SELECT id, name, part_number, price FROM parts ORDER BY name");
if ($parts_result) {
    $parts = $parts_result->fetch_all(MYSQLI_ASSOC);
}

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                    'code' => $selected_service['code'] ?? '',
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
<<<<<<< Updated upstream
    elseif (isset($_POST['remove_service'])) {
        $service_id = (int)$_POST['service_id'];
        
=======
    if (isset($_POST['remove_service'])) {
        $service_id = (int)$_POST['service_id'];
>>>>>>> Stashed changes
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
<<<<<<< Updated upstream
    elseif (isset($_POST['add_part'])) {
        $part_id = (int)$_POST['part_id'];
        $quantity = (int)$_POST['quantity'];
=======
    if (isset($_POST['add_part'])) {
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
                
                // Проверяем, не добавлена ли уже эта запчасть
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
    if (isset($_POST['remove_part'])) {
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
    
    // Сохранение заказа
    if (isset($_POST['update_order'])) {
        $description = $conn->real_escape_string($_POST['description'] ?? '');
        $status = $conn->real_escape_string($_POST['status'] ?? 'В ожидании');
        
        try {
            $stmt = $conn->prepare("UPDATE orders SET description = ?, status = ? WHERE id = ?");
            $stmt->bind_param('ssi', $description, $status, $order_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "✅ Заказ успешно обновлен";
            } else {
                throw new Exception("Ошибка обновления заказа: " . $stmt->error);
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "❌ Ошибка сохранения: " . $e->getMessage();
        }
        
        header("Location: order_edit.php?id=" . $order_id);
        exit;
    }
}
?>
>>>>>>> Stashed changes

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
<<<<<<< Updated upstream
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
</style>
    
</head>
<body>
    <div class="container mt-4">
       
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Редактирование заказа #<?= $order_id ?></h1>
            <div>
                <a href="orders.php" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left"></i> Назад
=======
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
            grid-template-columns: 1fr 120px 100px 150px auto;
            gap: 15px;
            align-items: end;
            margin-bottom: 20px;
        }
        
        .quick-search-grid {
            display: grid;
            grid-template-columns: 150px 1fr 100px 150px auto;
            gap: 15px;
            align-items: end;
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        
        .search-results {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            display: none;
            position: absolute;
            width: calc(100% - 30px);
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .search-result-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }
        
        .search-result-item:hover {
            background: #f8f9fa;
        }
        
        .search-result-item:last-child {
            border-bottom: none;
        }
        
        .service-code {
            font-weight: bold;
            color: #2c3e50;
            margin-right: 10px;
        }
        
        .service-name {
            color: #495057;
        }
        
        .service-price {
            color: #28a745;
            font-weight: 500;
            float: right;
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
        
        .ml-auto {
            margin-left: auto;
        }
        
        .relative {
            position: relative;
        }

        /* Стили для интеграции осмотра */
        .inspection-quick-access {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .inspection-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            background: white;
        }
        
        .inspection-card:hover {
            border-color: #3498db;
            transform: translateY(-2px);
            text-decoration: none;
            color: inherit;
        }
        
        .inspection-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: block;
        }
        
        .inspection-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        
        .inspection-desc {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .inspection-status {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .popular-services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .popular-service-btn {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 12px;
            background: white;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: left;
        }
        
        .popular-service-btn:hover {
            border-color: #3498db;
            background: #f8f9fa;
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
                <a href="inspection.php?order_id=<?= $order_id ?>" class="btn-1c-primary">
                    🔍 Осмотр автомобиля
                </a>
                <a href="order_parts_management.php?order_id=<?= $order_id ?>" class="btn-1c-outline">
                    📦 Управление запчастями
>>>>>>> Stashed changes
                </a>
                <a href="order_print.php?id=<?= $order_id ?>" class="btn btn-outline-primary me-2" target="_blank">
                    <i class="bi bi-printer"></i> Печать
                </a>
				<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Редактирование заказа #<?= $order_id ?></h1>
    <div>
        <a href="orders.php" class="btn btn-outline-secondary me-2">
            <i class="bi bi-arrow-left"></i> Назад
        </a>
        <!-- ДОБАВИТЬ ЭТУ КНОПКУ -->
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

<<<<<<< Updated upstream
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
                        <select name="status" class="form-control">
                            <option value="В ожидании" <?= $order['status'] == 'В ожидании' ? 'selected' : '' ?>>В ожидании</option>
                            <option value="В работе" <?= $order['status'] == 'В работе' ? 'selected' : '' ?>>В работе</option>
                            <option value="Готов" <?= $order['status'] == 'Готов' ? 'selected' : '' ?>>Готов</option>
                            <option value="Выдан" <?= $order['status'] == 'Выдан' ? 'selected' : '' ?>>Выдан</option>
                        </select>
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
=======
            <!-- Правая колонка - форма редактирования -->
            <div class="form-main-content">
                <!-- ОСНОВНАЯ ФОРМА для сохранения заказа -->
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

                    <!-- Интеграция осмотра -->
                    <div class="form-section">
                        <div class="section-header">
                            <span class="section-icon">🔍</span>
                            <h3 class="section-title">Осмотр автомобиля</h3>
                            <div class="ml-auto">
                                <a href="inspection.php?order_id=<?= $order_id ?>&tab=act" class="btn-1c-primary" target="_blank">
                                    📋 Открыть полный осмотр
                                </a>
                            </div>
                        </div>
                        
                        <!-- Статус осмотра -->
                        <?php
                        // Проверяем существование данных осмотра
                        $inspection_check = $conn->prepare("SELECT id FROM inspection_acts WHERE order_id = ?");
                        $inspection_check->bind_param("i", $order_id);
                        $inspection_check->execute();
                        $has_inspection = $inspection_check->get_result()->num_rows > 0;
                        ?>
                        
                        <div class="inspection-status">
                            <div style="display: flex; justify-content: between; align-items: center;">
                                <div>
                                    <div style="font-weight: 600; color: #2c3e50;">Статус осмотра</div>
                                    <div style="color: #6c757d; font-size: 14px;">
                                        <?php if ($has_inspection): ?>
                                            <span style="color: #28a745;">✅ Осмотр проведен</span>
                                        <?php else: ?>
                                            <span style="color: #dc3545;">❌ Осмотр не проводился</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($has_inspection): ?>
                                <a href="inspection_view.php?order_id=<?= $order_id ?>" 
                                   class="btn-1c-primary" style="font-size: 12px; padding: 8px 12px;">
                                    📄 Просмотр акта
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Быстрый доступ к осмотру -->
                        <div class="inspection-quick-access">
                            <a href="inspection.php?order_id=<?= $order_id ?>&tab=quick" class="inspection-card">
                                <span class="inspection-icon">🚗</span>
                                <div class="inspection-title">Быстрый осмотр</div>
                                <div class="inspection-desc">Свободное добавление работ и услуг</div>
                            </a>
                            
                            <a href="inspection.php?order_id=<?= $order_id ?>&tab=act&axis=front" class="inspection-card">
                                <span class="inspection-icon">📋</span>
                                <div class="inspection-title">Акт осмотра</div>
                                <div class="inspection-desc">Структурированный осмотр по осям</div>
                            </a>
                        </div>

                        <!-- Популярные услуги для быстрого добавления -->
                        <h5 style="margin: 20px 0 15px 0; color: #2c3e50;">⚡ Популярные услуги</h5>
                        <div class="popular-services-grid">
                            <?php
                            // Получаем популярные услуги
                            $popular_services = $conn->query("
                                SELECT id, name, code, price 
                                FROM services 
                                WHERE is_popular = 1 
                                ORDER BY name 
                                LIMIT 6
                            ");
                            
                            while ($service = $popular_services->fetch_assoc()): 
                            ?>
                            <form method="post" style="margin: 0;">
                                <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                                <input type="hidden" name="quantity" value="1">
                                <input type="hidden" name="price" value="<?= $service['price'] ?>">
                                <button type="submit" name="add_service" class="popular-service-btn">
                                    <div style="font-weight: 600; margin-bottom: 5px;"><?= htmlspecialchars($service['name']) ?></div>
                                    <div style="font-size: 12px; color: #6c757d;">
                                        Код: <?= $service['code'] ?>
                                    </div>
                                    <div style="font-size: 14px; color: #28a745; font-weight: 500; margin-top: 5px;">
                                        <?= number_format($service['price'], 2) ?> руб
                                    </div>
                                </button>
                            </form>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </form>

                <!-- Услуги - ОТДЕЛЬНАЯ ФОРМА -->
                <div class="form-section">
                    <div class="section-header">
                        <span class="section-icon">🛠️</span>
                        <h3 class="section-title">Услуги</h3>
                    </div>
                    
                    <?php if (count($order_services) > 0): ?>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th width="100">Код</th>
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
                                        <?php if (!empty($service['code'])): ?>
                                            <strong><?= htmlspecialchars($service['code']) ?></strong>
                                        <?php else: ?>
                                            <span style="color: #999;">—</span>
                                        <?php endif; ?>
                                    </td>
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
                        <div style="font-size: 14px; margin-top: 10px; color: #6c757d;">
                            Добавьте услуги через осмотр или форму ниже
                        </div>
                    </div>
                    <?php endif; ?>

                    <h5 style="margin: 25px 0 15px 0;">Добавить услугу вручную</h5>
                    <form method="post" class="quick-search-grid">
                        <div class="form-group">
                            <label class="form-label">Поиск по коду</label>
                            <input type="text" id="serviceCodeSearch" class="form-control" placeholder="Например: 17" 
                                   style="font-weight: bold; text-align: center;">
                        </div>
                        
                        <div class="form-group relative">
                            <label class="form-label">Выберите услугу</label>
                            <select name="service_id" id="serviceSelect" class="form-control" required>
                                <option value="">Сначала введите код</option>
                            </select>
                            <div id="searchResults" class="search-results"></div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Кол-во</label>
                            <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Цена за ед., руб.</label>
                            <input type="number" step="0.01" name="price" id="servicePrice" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" name="add_service" class="btn-1c-primary">
                                + Добавить
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Запчасти - ОТДЕЛЬНАЯ ФОРМА -->
                <div class="form-section">
                    <div class="section-header">
                        <span class="section-icon">🔧</span>
                        <h3 class="section-title">Запчасти</h3>
                    </div>
                    
                    <?php if (count($order_parts) > 0): ?>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Запчасть</th>
                                <th width="100">Артикул</th>
                                <th width="120">Кол-во</th>
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
                                    <td><?= $part['quantity'] ?> шт</td>
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

                <!-- Кнопка сохранения заказа ПОСЛЕ ВСЕХ форм -->
                <div class="form-actions">
                    <form method="post" style="display: inline;">
                        <button type="submit" name="update_order" class="btn-1c-primary btn-large">
                            💾 Сохранить заказ
                        </button>
                    </form>
                    <a href="orders.php" class="btn-1c-outline">Отмена</a>
                </div>
>>>>>>> Stashed changes
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
<<<<<<< Updated upstream

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

   
=======
       
>>>>>>> Stashed changes
    <script>
        // Все услуги для поиска
        const allServices = <?= json_encode($services) ?>;

        // Поиск услуг по коду
        document.getElementById('serviceCodeSearch').addEventListener('input', function(e) {
            const searchCode = e.target.value.trim();
            const serviceSelect = document.getElementById('serviceSelect');
            const searchResults = document.getElementById('searchResults');
            
            // Очищаем предыдущие результаты
            serviceSelect.innerHTML = '<option value="">Выберите услугу</option>';
            searchResults.innerHTML = '';
            searchResults.style.display = 'none';
            
            if (searchCode.length > 0) {
                // Ищем услуги по коду (частичное совпадение)
                const filteredServices = allServices.filter(service => 
                    service.code && service.code.toString().includes(searchCode)
                );
                
                if (filteredServices.length > 0) {
                    // Заполняем выпадающий список
                    filteredServices.forEach(service => {
                        const option = document.createElement('option');
                        option.value = service.id;
                        option.textContent = `${service.code} - ${service.name} (${service.price} руб.)`;
                        option.setAttribute('data-price', service.price);
                        serviceSelect.appendChild(option);
                    });
                    
                    // Показываем результаты поиска
                    filteredServices.forEach(service => {
                        const div = document.createElement('div');
                        div.className = 'search-result-item';
                        div.innerHTML = `
                            <span class="service-code">${service.code}</span>
                            <span class="service-name">${service.name}</span>
                            <span class="service-price">${service.price} руб.</span>
                        `;
                        div.addEventListener('click', function() {
                            serviceSelect.value = service.id;
                            document.getElementById('servicePrice').value = service.price;
                            searchResults.style.display = 'none';
                        });
                        searchResults.appendChild(div);
                    });
                    
                    searchResults.style.display = 'block';
                } else {
                    serviceSelect.innerHTML = '<option value="">Услуг с таким кодом не найдено</option>';
                }
            }
        });

        // Обновление цены при выборе услуги
<<<<<<< Updated upstream
        document.querySelector('select[name="service_id"]').addEventListener('change', function() {
=======
        document.getElementById('serviceSelect').addEventListener('change', function() {
>>>>>>> Stashed changes
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.getAttribute('data-price')) {
                document.getElementById('servicePrice').value = selectedOption.getAttribute('data-price');
            }
        });

        // Скрываем результаты поиска при клике вне области
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.relative')) {
                document.getElementById('searchResults').style.display = 'none';
            }
        });
    </script>
	<?php include 'templates/footer.php'; ?>
</body>
</html>
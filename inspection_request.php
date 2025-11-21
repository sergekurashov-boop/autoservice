<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAnyRole(['admin', 'manager', 'reception']);

// Получаем информацию о заказе если передан order_id
$order_id = $_GET['order_id'] ?? null;
$order = null;

if ($order_id) {
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
}

// Генерация номера задания на осмотр
function generateRequestNumber($conn) {
    $year = date('y');
    $month = date('m');
    
    $result = $conn->query("
        SELECT request_number FROM inspection_requests 
        WHERE request_number LIKE 'ОСМ-{$year}{$month}%' 
        ORDER BY id DESC LIMIT 1
    ");
    
    if ($result && $result->num_rows > 0) {
        $last_number = $result->fetch_assoc()['request_number'];
        $last_seq = intval(substr($last_number, -3));
        $new_seq = str_pad($last_seq + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $new_seq = '001';
    }
    
    return "ОСМ-{$year}{$month}{$new_seq}";
}

// Обработка сохранения задания на осмотр
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_request'])) {
    try {
        $request_number = generateRequestNumber($conn);
        
        $stmt = $conn->prepare("
            INSERT INTO inspection_requests (
                order_id, request_number, request_date, client_name, vehicle_info, 
                vin, license_plate, year, client_complaints, inspection_scope,
                master_notes, created_by, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new')
        ");
        
        $request_date = $_POST['request_date'];
        $client_name = $_POST['client_name'];
        $vehicle_info = $_POST['vehicle_info'];
        $vin = $_POST['vin'];
        $license_plate = $_POST['license_plate'];
        $year = $_POST['year'];
        $client_complaints = $_POST['client_complaints'];
        $inspection_scope = $_POST['inspection_scope'];
        $master_notes = $_POST['master_notes'];
        $created_by = $_SESSION['user_id'];
        
        $stmt->bind_param("issssssisssi", 
            $order_id, $request_number, $request_date, $client_name, $vehicle_info,
            $vin, $license_plate, $year, $client_complaints, $inspection_scope,
            $master_notes, $created_by
        );
        
        $stmt->execute();
        $request_id = $conn->insert_id;
        
        $_SESSION['success'] = "✅ Задание на осмотр №{$request_number} создано!";
        header("Location: inspection_request_view.php?id=" . $request_id);
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = "❌ Ошибка сохранения: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Задание на осмотр - Autoservice</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        .request-header { background: #e3f2fd; padding: 20px; margin-bottom: 20px; text-align: center; }
        .request-title { font-size: 1.5rem; font-weight: 700; color: #1565c0; }
        .form-section { margin-bottom: 25px; padding: 20px; background: white; border-radius: 5px; }
        .section-title { font-size: 1.2rem; font-weight: 600; margin-bottom: 15px; color: #333; }
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-control { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
        .btn-primary { background: #1976d2; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
    </style>
</head>
<body>
    <?php include 'templates/header.php'; ?>
    
    <div class="main-content-1c">
        <div class="content-container">
            <div class="container">
                <div class="request-header">
                    <div class="request-title">ЗАДАНИЕ НА ТЕХНИЧЕСКИЙ ОСМОТР</div>
                    <div style="color: #666;">Первичное задание мастеру-приемщику</div>
                </div>

                <form method="post">
                    <input type="hidden" name="save_request" value="1">
                    
                    <!-- 1. Информация о клиенте и автомобиле -->
                    <div class="form-section">
                        <div class="section-title">📋 Данные клиента и автомобиля</div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label class="form-label">Дата обращения</label>
                                <input type="date" name="request_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Заказчик *</label>
                                <input type="text" name="client_name" class="form-control" value="<?= $order ? htmlspecialchars($order['client_name']) : '' ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Автомобиль *</label>
                                <input type="text" name="vehicle_info" class="form-control" value="<?= $order ? htmlspecialchars($order['make'] . ' ' . $order['model']) : '' ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">VIN</label>
                                <input type="text" name="vin" class="form-control" value="<?= $order ? htmlspecialchars($order['vin'] ?? '') : '' ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Гос. номер</label>
                                <input type="text" name="license_plate" class="form-control" value="<?= $order ? htmlspecialchars($order['license_plate'] ?? '') : '' ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Год выпуска</label>
                                <input type="number" name="year" class="form-control" value="<?= $order ? $order['year'] : '' ?>">
                            </div>
                        </div>
                    </div>

                    <!-- 2. Жалобы клиента -->
                    <div class="form-section">
                        <div class="section-title">🎯 Жалобы и проблемы клиента</div>
                        <div class="form-group">
                            <textarea name="client_complaints" class="form-control" rows="4" placeholder="Опишите жалобы клиента, симптомы проблемы..." required></textarea>
                        </div>
                    </div>

                    <!-- 3. Объем осмотра -->
                    <div class="form-section">
                        <div class="section-title">🔍 Объем осмотра</div>
                        <div class="form-group">
                            <textarea name="inspection_scope" class="form-control" rows="3" placeholder="Что необходимо осмотреть? (например: ходовая часть, тормозная система, рулевое управление...)" required></textarea>
                        </div>
                    </div>

                    <!-- 4. Примечания приемщика -->
                    <div class="form-section">
                        <div class="section-title">💬 Примечания приемщика</div>
                        <div class="form-group">
                            <textarea name="master_notes" class="form-control" rows="3" placeholder="Дополнительные замечания, рекомендации..."></textarea>
                        </div>
                    </div>

                    <!-- Кнопки -->
                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="submit" class="btn btn-primary">✅ Создать задание на осмотр</button>
                        <?php if ($order_id): ?>
                            <a href="order_edit.php?id=<?= $order_id ?>" class="btn btn-secondary">← Назад к заказу</a>
                        <?php endif; ?>
                        <a href="orders.php" class="btn btn-secondary">📋 К списку заказов</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
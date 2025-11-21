<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAnyRole(['admin', 'manager', 'mechanic']);

$request_id = $_GET['request_id'] ?? null;

if (!$request_id) {
    $_SESSION['error'] = "❌ ID задания не указан";
    header("Location: inspection_requests_list.php");
    exit;
}

// Получаем данные из задания на осмотр
$stmt = $conn->prepare("SELECT * FROM inspection_requests WHERE id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();

if (!$request) {
    $_SESSION['error'] = "❌ Задание на осмотр не найдено";
    header("Location: inspection_requests_list.php");
    exit;
}

// Проверяем, не создан ли уже акт для этого задания
$act_stmt = $conn->prepare("SELECT id FROM inspection_acts WHERE request_id = ?");
$act_stmt->bind_param("i", $request_id);
$act_stmt->execute();
if ($act_stmt->get_result()->num_rows > 0) {
    $_SESSION['error'] = "❌ Акт осмотра для этого задания уже создан";
    header("Location: inspection_request_view.php?id=" . $request_id);
    exit;
}

// Получаем базовые услуги для осмотра
$services = [];
$result = $conn->query("SELECT code, name, typical_price FROM inspection_services WHERE is_active = 1 ORDER BY CAST(code AS UNSIGNED)");
if ($result) {
    $services = $result->fetch_all(MYSQLI_ASSOC);
}

// Получаем список механиков
$mechanics = [];
$mech_result = $conn->query("SELECT id, name, position, specialty FROM employees WHERE type = 'mechanic' AND active = 1 ORDER BY name");
if ($mech_result) {
    $mechanics = $mech_result->fetch_all(MYSQLI_ASSOC);
}

// Генерация номера акта
function generateActNumber($conn) {
    $year = date('y');
    $month = date('m');
    
    $result = $conn->query("SELECT act_number FROM inspection_acts WHERE act_number LIKE 'АКТ-{$year}{$month}%' ORDER BY id DESC LIMIT 1");
    
    if ($result && $result->num_rows > 0) {
        $last_number = $result->fetch_assoc()['act_number'];
        $last_seq = intval(substr($last_number, -3));
        $new_seq = str_pad($last_seq + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $new_seq = '001';
    }
    
    return "АКТ-{$year}{$month}{$new_seq}";
}

// Обработка сохранения акта осмотра
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_inspection'])) {
    try {
        $conn->begin_transaction();
        
        $act_number = generateActNumber($conn);
        
        // Сохраняем акт осмотра
        $stmt = $conn->prepare("
            INSERT INTO inspection_acts (
                request_id, order_id, act_number, inspection_date, client_name, vehicle_info, 
                vin, license_plate, year, mileage, master_notes, master_id,
                post, expected_completion, total_work_time
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $inspection_date = $_POST['inspection_date'];
        $client_name = $_POST['client_name'];
        $vehicle_info = $_POST['vehicle_info'];
        $vin = $_POST['vin'];
        $license_plate = $_POST['license_plate'];
        $year = $_POST['year'];
        $mileage = $_POST['mileage'] ?? 0;
        $master_notes = $_POST['master_notes'];
        $master_id = $_SESSION['user_id'];
        $post = $_POST['post'];
        $expected_completion = $_POST['expected_completion'];
        $total_work_time = $_POST['total_work_time'];
        
        $stmt->bind_param("iissssssissssss", 
            $request_id, $request['order_id'], $act_number, $inspection_date, $client_name, $vehicle_info,
            $vin, $license_plate, $year, $mileage, $master_notes, $master_id,
            $post, $expected_completion, $total_work_time
        );
        
        $stmt->execute();
        $inspection_id = $conn->insert_id;
        
        // Сохраняем работы
        if (isset($_POST['works']) && is_array($_POST['works'])) {
            $work_stmt = $conn->prepare("
                INSERT INTO inspection_works (inspection_id, service_code, work_name, quantity, work_time, mechanic_id, mechanic_name, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($_POST['works'] as $index => $work) {
                if (!empty($work['work_name'])) {
                    $work_stmt->bind_param("issdissi",
                        $inspection_id, $work['service_code'], $work['work_name'],
                        $work['quantity'], $work['work_time'], $work['mechanic_id'],
                        $work['mechanic_name'], $index
                    );
                    $work_stmt->execute();
                }
            }
        }
        
        $conn->commit();
        
        // Обновляем статус задания на осмотр
        $update_stmt = $conn->prepare("UPDATE inspection_requests SET status = 'completed' WHERE id = ?");
        $update_stmt->bind_param("i", $request_id);
        $update_stmt->execute();
        
        $_SESSION['success'] = "✅ Акт осмотра №{$act_number} успешно создан!";
        header("Location: inspection_view.php?id=" . $inspection_id);
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "❌ Ошибка сохранения: " . $e->getMessage();
    }
}
?>

<!-- HTML форма аналогична вашему оригинальному inspection.php -->
<!-- Но с предзаполненными данными из $request -->
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание акта осмотра - Autoservice</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Стили из вашего inspection.php -->
</head>
<body>
    <?php include 'templates/header.php'; ?>
    
    <div class="main-content-1c">
        <div class="content-container">
            <div class="inspection-container">
                <div class="inspection-header">
                    <div class="act-number">АКТ ТЕХНИЧЕСКОГО ОСМОТРА</div>
                    <div style="color: var(--text-light); font-size: 14px;">
                        На основании задания: <?= $request['request_number'] ?>
                    </div>
                    <div style="color: var(--text-light); font-size: 12px; margin-top: 5px;">
                        Жалобы клиента: <?= htmlspecialchars(mb_substr($request['client_complaints'], 0, 100)) ?>...
                    </div>
                </div>

                <form method="post" class="inspection-form" id="inspectionForm">
                    <input type="hidden" name="save_inspection" value="1">
                    
                    <!-- 1. Информация о клиенте и автомобиле -->
                    <div class="form-section">
                        <div class="section-title">📋 1. ИНФОРМАЦИЯ О КЛИЕНТЕ И АВТОМОБИЛЕ</div>
                        
                        <div class="client-info-grid">
                            <div class="form-group">
                                <label class="form-label">Дата осмотра</label>
                                <input type="date" name="inspection_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Заказчик *</label>
                                <input type="text" name="client_name" class="form-control" value="<?= htmlspecialchars($request['client_name']) ?>" required readonly style="background: #f5f5f5;">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Автомобиль *</label>
                                <input type="text" name="vehicle_info" class="form-control" value="<?= htmlspecialchars($request['vehicle_info']) ?>" required readonly style="background: #f5f5f5;">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">VIN</label>
                                <input type="text" name="vin" class="form-control" value="<?= htmlspecialchars($request['vin'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Гос номер</label>
                                <input type="text" name="license_plate" class="form-control" value="<?= htmlspecialchars($request['license_plate'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Год выпуска</label>
                                <input type="number" name="year" class="form-control" value="<?= $request['year'] ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Пробег (км)</label>
                                <input type="number" name="mileage" class="form-control" placeholder="Текущий пробег">
                            </div>
                        </div>
                    </div>

                    <!-- Остальные секции из вашего inspection.php -->
                    <!-- 2. Быстрый выбор услуг -->
                    <!-- 3. Список работ -->
                    <!-- 4. Комментарии приемщика -->
                    <!-- 5. Организационная информация -->

                    <!-- Кнопки действий -->
                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="submit" class="btn btn-success btn-large">💾 Сохранить акт осмотра</button>
                        <a href="inspection_request_view.php?id=<?= $request_id ?>" class="btn btn-secondary">← Назад к заданию</a>
                        <a href="inspection_requests_list.php" class="btn btn-secondary">📋 К списку заданий</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript из вашего inspection.php -->
</body>
</html>
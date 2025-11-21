<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAnyRole(['admin', 'manager', 'mechanic']);

// Получаем базовые услуги для осмотра
$services = [];
$result = $conn->query("
    SELECT code, name, typical_price 
    FROM inspection_services 
    WHERE is_active = 1 
    ORDER BY CAST(code AS UNSIGNED)
");

if ($result) {
    $services = $result->fetch_all(MYSQLI_ASSOC);
} else {
    // Если таблицы нет, создаем временный список
    $services = [
        ['code' => '1', 'name' => 'Рулевые тяги', 'typical_price' => 1500],
        ['code' => '2', 'name' => 'Шаровые опоры', 'typical_price' => 2000],
        ['code' => '3', 'name' => 'Сайлентблоки рычагов', 'typical_price' => 1800],
        ['code' => '4', 'name' => 'Ступичные подшипники', 'typical_price' => 2500],
        ['code' => '5', 'name' => 'Тормозные суппорты', 'typical_price' => 2200],
        ['code' => '6', 'name' => 'Тормозные диски', 'typical_price' => 1900],
        ['code' => '7', 'name' => 'Тормозные колодки', 'typical_price' => 1200],
        ['code' => '8', 'name' => 'Пыльники ШРУС', 'typical_price' => 1600],
        ['code' => '9', 'name' => 'Стойки стабилизатора', 'typical_price' => 1400],
        ['code' => '10', 'name' => 'Амортизаторы', 'typical_price' => 3000],
        ['code' => '11', 'name' => 'Тормозные барабаны', 'typical_price' => 2100],
        ['code' => '12', 'name' => 'Регуляторы тормозных усилий', 'typical_price' => 1700],
        ['code' => '13', 'name' => 'Пружины', 'typical_price' => 2300],
        ['code' => '14', 'name' => 'Рычаги подвески', 'typical_price' => 2700],
        ['code' => '15', 'name' => 'Опоры амортизаторов', 'typical_price' => 1300],
        ['code' => '16', 'name' => 'Подшипники ступиц', 'typical_price' => 2800],
        ['code' => '17', 'name' => 'Тормозные шланги', 'typical_price' => 1100],
        ['code' => '18', 'name' => 'Сайлентблоки реактивных тяг', 'typical_price' => 1500],
        ['code' => '19', 'name' => 'Стабилизаторы поперечной устойчивости', 'typical_price' => 2400],
        ['code' => '20', 'name' => 'Комплекты креплений', 'typical_price' => 900]
    ];
}

// Добавляем стандартное время для каждой услуги
$typical_times = [
    '1' => '0:30', '2' => '1:00', '3' => '0:45', '4' => '1:30',
    '5' => '1:15', '6' => '1:00', '7' => '0:30', '8' => '0:45',
    '9' => '0:40', '10' => '2:00', '11' => '1:15', '12' => '0:50',
    '13' => '1:00', '14' => '1:20', '15' => '0:35', '16' => '1:45',
    '17' => '0:25', '18' => '0:55', '19' => '1:10', '20' => '0:20'
];

foreach ($services as &$service) {
    $code = $service['code'];
    $service['typical_time'] = $typical_times[$code] ?? '0:45';
}

// Получаем список механиков из таблицы employees
$mechanics = [];
$mech_result = $conn->query("
    SELECT id, name, position, specialty, specialization
    FROM employees 
    WHERE type = 'mechanic' AND active = 1
    ORDER BY name
");

if ($mech_result) {
    $mechanics = $mech_result->fetch_all(MYSQLI_ASSOC);
} else {
    // Если нет механиков, создаем временный список
    $mechanics = [
        ['id' => 1, 'name' => 'Иванов А.П.', 'position' => 'Механик', 'specialty' => 'Ходовая часть', 'specialization' => 'all'],
        ['id' => 2, 'name' => 'Петров С.М.', 'position' => 'Старший механик', 'specialty' => 'Тормозная система', 'specialization' => 'all'],
        ['id' => 3, 'name' => 'Сидоров В.К.', 'position' => 'Механик', 'specialty' => 'Подвеска', 'specialization' => 'all']
    ];
}

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

// Генерация номера акта
function generateActNumber($conn) {
    $year = date('y');
    $month = date('m');
    
    // Проверяем существование таблицы inspection_acts
    $table_exists = $conn->query("SHOW TABLES LIKE 'inspection_acts'");
    
    if ($table_exists && $table_exists->num_rows > 0) {
        // Ищем последний номер за этот месяц
        $result = $conn->query("
            SELECT act_number FROM inspection_acts 
            WHERE act_number LIKE 'ЗН-{$year}{$month}%' 
            ORDER BY id DESC LIMIT 1
        ");
        
        if ($result && $result->num_rows > 0) {
            $last_number = $result->fetch_assoc()['act_number'];
            $last_seq = intval(substr($last_number, -3));
            $new_seq = str_pad($last_seq + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $new_seq = '001';
        }
    } else {
        $new_seq = '001';
    }
    
    return "ЗН-{$year}{$month}{$new_seq}-К";
}

// Обработка сохранения осмотра
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_inspection'])) {
    try {
        $conn->begin_transaction();
        
        // Генерируем номер акта
        $act_number = generateActNumber($conn);
        
        // Сохраняем основной акт
        $stmt = $conn->prepare("
            INSERT INTO inspection_acts (
                order_id, act_number, inspection_date, client_name, vehicle_info, 
                vin, license_plate, year, mileage, master_notes, master_id,
                post, expected_completion, total_work_time
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $inspection_date = $_POST['inspection_date'];
        $client_name = $_POST['client_name'];
        $vehicle_info = $_POST['vehicle_info'];
        $vin = $_POST['vin'];
        $license_plate = $_POST['license_plate'];
        $year = $_POST['year'];
        $mileage = $_POST['mileage'] ?? 0; // Пробег может быть не указан
        $master_notes = $_POST['master_notes'];
        $master_id = $_SESSION['user_id'];
        $post = $_POST['post'];
        $expected_completion = $_POST['expected_completion'];
        $total_work_time = $_POST['total_work_time'];
        
        $stmt->bind_param("issssssissssss", 
            $order_id, $act_number, $inspection_date, $client_name, $vehicle_info,
            $vin, $license_plate, $year, $mileage, $master_notes, $master_id,
            $post, $expected_completion, $total_work_time
        );
        
        $stmt->execute();
        $inspection_id = $conn->insert_id;
        
        // Сохраняем работы
        if (isset($_POST['works']) && is_array($_POST['works'])) {
            $work_stmt = $conn->prepare("
                INSERT INTO inspection_works (
                    inspection_id, service_code, work_name, quantity, 
                    work_time, mechanic_id, mechanic_name, sort_order
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
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
        
        // Сохраняем запчасти
        if (isset($_POST['parts']) && is_array($_POST['parts'])) {
            $part_stmt = $conn->prepare("
                INSERT INTO inspection_parts (
                    inspection_id, part_name, part_number, quantity, 
                    price, source, used_location
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($_POST['parts'] as $part) {
                if (!empty($part['part_name'])) {
                    $part_stmt->bind_param("issddss",
                        $inspection_id, $part['part_name'], $part['part_number'],
                        $part['quantity'], $part['price'], $part['source'],
                        $part['used_location']
                    );
                    $part_stmt->execute();
                }
            }
        }
        
        $conn->commit();
        $_SESSION['success'] = "✅ Акт осмотра №{$act_number} успешно создан!";
        header("Location: inspection_task.php?id=" . $inspection_id);
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "❌ Ошибка сохранения: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание акта осмотра - Autoservice</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .inspection-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: var(--bg-main);
        }
        
        .inspection-header {
            background: #fffef5;
            border: 1px solid var(--border-color);
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .act-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 10px;
        }
        
        .inspection-form {
            background: #fffef5;
            border: 1px solid var(--border-color);
            padding: 20px;
        }
        
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .client-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--text-dark);
            font-size: 13px;
        }
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            background: #fffef5;
            color: var(--text-dark);
            font-size: 13px;
            border-radius: 0;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #8b6914;
            background: #fff8dc;
        }
        
        .works-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 13px;
        }
        
        .works-table th {
            background: #fff8dc;
            padding: 10px 8px;
            border: 1px solid var(--border-color);
            text-align: left;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .works-table td {
            padding: 8px;
            border: 1px solid var(--border-color);
            vertical-align: middle;
        }
        
        .service-code-cell {
            width: 80px;
        }
        
        .work-name-cell {
            width: 300px;
        }
        
        .quantity-cell {
            width: 80px;
        }
        
        .time-cell {
            width: 100px;
        }
        
        .mechanic-cell {
            width: 150px;
        }
        
        .action-cell {
            width: 60px;
            text-align: center;
        }
        
        .btn {
            padding: 6px 12px;
            border: 1px solid var(--border-color);
            border-radius: 0;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            background: #fffef5;
            color: var(--text-dark);
            transition: all 0.2s ease;
        }
        
        .btn:hover {
            background: #f5e8b0;
            text-decoration: none;
            color: var(--text-dark);
        }
        
        .btn-primary { 
            background: #8b6914; 
            color: white; 
            border-color: #7a5a10; 
        }
        
        .btn-primary:hover { 
            background: #7a5a10; 
            color: white; 
        }
        
        .btn-success { 
            background: #28a745; 
            color: white; 
            border-color: #1e7e34; 
        }
        
        .btn-success:hover { 
            background: #1e7e34; 
            color: white; 
        }
        
        .btn-danger { 
            background: #dc3545; 
            color: white; 
            border-color: #c82333; 
        }
        
        .btn-danger:hover { 
            background: #c82333; 
            color: white; 
        }
        
        .time-total {
            background: #f5e8b0;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            font-weight: 600;
            text-align: right;
            margin-top: 10px;
        }
        
        .quick-service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .quick-service-btn {
            border: 1px solid var(--border-color);
            padding: 10px;
            background: #fffef5;
            cursor: pointer;
            text-align: left;
            transition: all 0.2s ease;
            font-size: 12px;
        }
        
        .quick-service-btn:hover {
            background: #f5e8b0;
            border-color: #8b6914;
        }
        
        .service-code {
            font-weight: 600;
            color: #8b6914;
            margin-right: 8px;
        }
        
        .service-time {
            color: #6c757d;
            font-size: 11px;
            float: right;
        }
        
        .textarea-large {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn-large {
            padding: 10px 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <?php include 'templates/header.php'; ?>
    
   
        <div class="content-container">
            <div class="inspection-container">
                <div class="inspection-header">
                    <div class="act-number">ЗАДАНИЕ В РЕМЗОНУ</div>
                    <div style="color: var(--text-light); font-size: 14px;">
                        Акт технического осмотра автомобиля
                    </div>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert-enhanced alert-danger">
                        <?= $_SESSION['error'] ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <form method="post" class="inspection-form" id="inspectionForm">
                    <input type="hidden" name="save_inspection" value="1">
                    
                    <!-- 1. Информация о клиенте и автомобиле -->
                    <div class="form-section">
                        <div class="section-title">
                            <span>📋</span>
                            1. ИНФОРМАЦИЯ О КЛИЕНТЕ И АВТОМОБИЛЕ
                        </div>
                        
                        <div class="client-info-grid">
                            <div class="form-group">
                                <label class="form-label">Дата осмотра</label>
                                <input type="date" name="inspection_date" class="form-control" 
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Заказчик</label>
                                <input type="text" name="client_name" class="form-control" 
                                       value="<?= $order ? htmlspecialchars($order['client_name']) : '' ?>" 
                                       placeholder="ФИО клиента" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Автомобиль</label>
                                <input type="text" name="vehicle_info" class="form-control" 
                                       value="<?= $order ? htmlspecialchars($order['make'] . ' ' . $order['model']) : '' ?>" 
                                       placeholder="Марка, модель" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">VIN</label>
                                <input type="text" name="vin" class="form-control" 
                                       value="<?= $order ? htmlspecialchars($order['vin'] ?? '') : '' ?>" 
                                       placeholder="VIN номер">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Гос номер</label>
                                <input type="text" name="license_plate" class="form-control" 
                                       value="<?= $order ? htmlspecialchars($order['license_plate'] ?? '') : '' ?>" 
                                       placeholder="Государственный номер">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Год выпуска</label>
                                <input type="number" name="year" class="form-control" 
                                       value="<?= $order ? $order['year'] : '' ?>" 
                                       placeholder="Год выпуска">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Пробег (км)</label>
                                <input type="number" name="mileage" class="form-control" 
                                       value="" 
                                       placeholder="Текущий пробег">
                            </div>
                        </div>
                    </div>

                    <!-- 2. Быстрый выбор услуг -->
                    <div class="form-section">
                        <div class="section-title">
                            <span>⚡</span>
                            2. БЫСТРЫЙ ВЫБОР УСЛУГ
                        </div>
                        
                        <div class="quick-service-grid">
                            <?php foreach ($services as $service): ?>
                            <button type="button" class="quick-service-btn" 
                                    onclick="addQuickService('<?= $service['code'] ?>', '<?= addslashes($service['name']) ?>', '<?= $service['typical_time'] ?>')">
                                <span class="service-code"><?= $service['code'] ?></span>
                                <?= $service['name'] ?>
                                <span class="service-time"><?= $service['typical_time'] ?></span>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 3. Список работ -->
                    <div class="form-section">
                        <div class="section-title">
                            <span>🛠️</span>
                            3. СПИСОК НЕОБХОДИМЫХ РАБОТ И УСЛУГ
                        </div>
                        
                        <table class="works-table" id="worksTable">
                            <thead>
                                <tr>
                                    <th class="service-code-cell">Код</th>
                                    <th class="work-name-cell">Наименование работ и услуг</th>
                                    <th class="quantity-cell">Кол-во</th>
                                    <th class="time-cell">Норма времени</th>
                                    <th class="mechanic-cell">Механик</th>
                                    <th class="action-cell">Действия</th>
                                </tr>
                            </thead>
                            <tbody id="worksBody">
                                <!-- Работы будут добавляться динамически -->
                            </tbody>
                        </table>
                        
                        <div class="time-total">
                            Итого оценка времени исполнения работ и услуг: 
                            <span id="totalWorkTime">0:00</span>
                        </div>
                        
                        <button type="button" class="btn btn-primary" onclick="addEmptyWork()">
                            ➕ Добавить работу
                        </button>
                    </div>

                    <!-- 4. Комментарии приемщика -->
                    <div class="form-section">
                        <div class="section-title">
                            <span>💬</span>
                            4. КОММЕНТАРИИ МАСТЕРА-ПРИЕМЩИКА
                        </div>
                        
                        <div class="form-group">
                            <textarea name="master_notes" class="form-control textarea-large" rows="4" 
                                      placeholder="Опишите выявленные проблемы, замечания, рекомендации..."></textarea>
                        </div>
                    </div>

                    <!-- 5. Организационная информация -->
                    <div class="form-section">
                        <div class="section-title">
                            <span>🏢</span>
                            5. ОРГАНИЗАЦИОННАЯ ИНФОРМАЦИЯ
                        </div>
                        
                        <div class="client-info-grid">
                            <div class="form-group">
                                <label class="form-label">Пост/рабочее место</label>
                                <input type="text" name="post" class="form-control" 
                                       value="Пост №1" placeholder="Номер поста или рабочего места">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Ожидаемое завершение работ</label>
                                <input type="datetime-local" name="expected_completion" class="form-control" 
                                       value="<?= date('Y-m-d\T18:00') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Кнопки действий -->
                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="submit" class="btn btn-success btn-large">
                            💾 Сохранить акт осмотра
                        </button>
                        <button type="button" class="btn btn-primary" onclick="generateTask()">
                            🖨️ Сгенерировать задание
                        </button>
                        <?php if ($order_id): ?>
                            <a href="order_edit.php?id=<?= $order_id ?>" class="btn btn-secondary">
                                ← Назад к заказу
                            </a>
                        <?php endif; ?>
                        <a href="orders.php" class="btn btn-secondary">
                            📋 К списку заказов
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let workCounter = 0;
        let totalWorkMinutes = 0;
        
        // Добавление быстрой услуги
        function addQuickService(code, name, time) {
            const tbody = document.getElementById('worksBody');
            const row = tbody.insertRow();
            
            row.innerHTML = `
                <td>
                    <input type="text" name="works[${workCounter}][service_code]" 
                           value="${code}" class="form-control" style="text-align: center; font-weight: 600;">
                </td>
                <td>
                    <input type="text" name="works[${workCounter}][work_name]" 
                           value="${name}" class="form-control">
                </td>
                <td>
                    <input type="number" name="works[${workCounter}][quantity]" 
                           value="1" step="0.1" min="0.1" class="form-control">
                </td>
                <td>
                    <input type="text" name="works[${workCounter}][work_time]" 
                           value="${time}" class="form-control work-time-input" 
                           onchange="calculateTotalTime()">
                </td>
                <td>
                    <select name="works[${workCounter}][mechanic_id]" class="form-control" onchange="updateMechanicName(this)">
                        <option value="">Выберите механика</option>
                        <?php foreach ($mechanics as $mechanic): ?>
                            <option value="<?= $mechanic['id'] ?>" data-name="<?= htmlspecialchars($mechanic['name']) ?>">
                                <?= htmlspecialchars($mechanic['name']) ?> (<?= $mechanic['specialty'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="works[${workCounter}][mechanic_name]" value="">
                </td>
                <td style="text-align: center;">
                    <button type="button" class="btn btn-danger" onclick="removeWork(this)" style="padding: 4px 8px;">
                        🗑️
                    </button>
                </td>
            `;
            
            workCounter++;
            calculateTotalTime();
        }
        
        // Добавление пустой строки для работы
        function addEmptyWork() {
            const tbody = document.getElementById('worksBody');
            const row = tbody.insertRow();
            
            row.innerHTML = `
                <td>
                    <input type="text" name="works[${workCounter}][service_code]" 
                           class="form-control" placeholder="Код" style="text-align: center;">
                </td>
                <td>
                    <input type="text" name="works[${workCounter}][work_name]" 
                           class="form-control" placeholder="Наименование работы" required>
                </td>
                <td>
                    <input type="number" name="works[${workCounter}][quantity]" 
                           value="1" step="0.1" min="0.1" class="form-control">
                </td>
                <td>
                    <input type="text" name="works[${workCounter}][work_time]" 
                           value="0:30" class="form-control work-time-input" 
                           placeholder="0:30" onchange="calculateTotalTime()">
                </td>
                <td>
                    <select name="works[${workCounter}][mechanic_id]" class="form-control" onchange="updateMechanicName(this)">
                        <option value="">Выберите механика</option>
                        <?php foreach ($mechanics as $mechanic): ?>
                            <option value="<?= $mechanic['id'] ?>" data-name="<?= htmlspecialchars($mechanic['name']) ?>">
                                <?= htmlspecialchars($mechanic['name']) ?> (<?= $mechanic['specialty'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="works[${workCounter}][mechanic_name]" value="">
                </td>
                <td style="text-align: center;">
                    <button type="button" class="btn btn-danger" onclick="removeWork(this)" style="padding: 4px 8px;">
                        🗑️
                    </button>
                </td>
            `;
            
            workCounter++;
            calculateTotalTime();
        }
        
        // Удаление работы
        function removeWork(button) {
            const row = button.closest('tr');
            row.remove();
            calculateTotalTime();
        }
        
        // Обновление имени механика
        function updateMechanicName(select) {
            const selectedOption = select.options[select.selectedIndex];
            const mechanicName = selectedOption.getAttribute('data-name') || '';
            const hiddenInput = select.parentNode.querySelector('input[type="hidden"]');
            hiddenInput.value = mechanicName;
        }
        
        // Расчет общего времени
        function calculateTotalTime() {
            let totalMinutes = 0;
            
            document.querySelectorAll('.work-time-input').forEach(input => {
                const time = input.value;
                if (time) {
                    const [hours, minutes] = time.split(':').map(Number);
                    totalMinutes += (hours * 60) + (minutes || 0);
                }
            });
            
            const hours = Math.floor(totalMinutes / 60);
            const minutes = totalMinutes % 60;
            
            document.getElementById('totalWorkTime').textContent = 
                `${hours}:${minutes.toString().padStart(2, '0')}`;
            
            // Обновляем скрытое поле для формы
            document.querySelector('input[name="total_work_time"]')?.remove();
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'total_work_time';
            hiddenInput.value = `${hours}:${minutes.toString().padStart(2, '0')}`;
            document.getElementById('inspectionForm').appendChild(hiddenInput);
        }
        
        // Генерация задания (предпросмотр)
        function generateTask() {
            // Здесь будет логика генерации PDF или предпросмотра
            alert('Функция генерации задания в разработке');
        }
        
        // Инициализация
        document.addEventListener('DOMContentLoaded', function() {
            // Добавляем первую пустую строку при загрузке
            addEmptyWork();
        });
    </script>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
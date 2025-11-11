<?php
function safe($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function highlight_search($text, $search) {
    if (empty($search)) return safe($text);
    return preg_replace('/(' . preg_quote($search, '/') . ')/i', '<mark>$1</mark>', safe($text));
}

function format_date($date) {
    if (!$date) return 'Не указано';
    return date('d.m.Y', strtotime($date));
}

function get_status_badge($status) {
    $statuses = [
        'pending' => ['text' => 'Ожидание', 'class' => 'bg-secondary'],
        'confirmed' => ['text' => 'Подтвержден', 'class' => 'bg-primary'],
        'in_progress' => ['text' => 'В работе', 'class' => 'bg-warning'],
        'completed' => ['text' => 'Завершен', 'class' => 'bg-success'],
        'cancelled' => ['text' => 'Отменен', 'class' => 'bg-danger']
    ];
    $info = $statuses[$status] ?? ['text' => $status, 'class' => 'bg-dark'];
    return '<span class="badge ' . $info['class'] . '">' . $info['text'] . '</span>';
}
//--070725
function get_tasks($department = '', $mechanic = '', $status = '') {
    global $pdo;
    
    $sql = "SELECT t.*, c.make, c.model, cl.first_name, cl.last_name AS client_name
            FROM tasks t
            JOIN cars c ON t.car_id = c.id
            JOIN clients cl ON t.client_id = cl.id
            WHERE 1";
    
    $params = [];
    
    if (!empty($department)) {
        $sql .= " AND t.department = ?";
        $params[] = $department;
    }
    
    if (!empty($mechanic)) {
        $sql .= " AND t.mechanic_id = ?";
        $params[] = $mechanic;
    }
    
    if (!empty($status)) {
        $statuses = explode(',', $status);
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $sql .= " AND t.status IN ($placeholders)";
        $params = array_merge($params, $statuses);
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_department_text($code) {
    $departments = [
        'engine' => 'Двигатель',
        'transmission' => 'Трансмиссия',
        'electrical' => 'Электрика',
        'body' => 'Кузов'
    ];
    
    return $departments[$code] ?? $code;
}

function get_priority_text($code) {
    $priorities = [
        'low' => 'Низкий',
        'medium' => 'Средний',
        'high' => 'Высокий',
        'urgent' => 'Срочный'
    ];
    
    return $priorities[$code] ?? $code;
}//formaone
function get_all_clients() {
    global $pdo;
    $stmt = $pdo->query("SELECT id, first_name, last_name, phone FROM clients ORDER BY last_name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function process_unified_booking($data) {
    // Обработка клиента
    if (!empty($data['client_id'])) {
        $clientId = $data['client_id'];
    } else {
        // Создание нового клиента
        $clientId = create_client([
            'first_name' => $data['new_first_name'],
            'last_name' => $data['new_last_name'],
            'phone' => $data['new_phone'],
            'email' => $data['new_email']
        ]);
    }
    
    // Обработка автомобиля
    if (!empty($data['car_id'])) {
        $carId = $data['car_id'];
    } else {
        // Создание нового автомобиля
        $carId = create_car([
            'client_id' => $clientId,
            'make' => $data['new_make'],
            'model' => $data['new_model'],
            'year' => $data['new_year'],
            'vin' => $data['new_vin'],
            'license_plate' => $data['new_license_plate'],
            'mileage' => $data['new_mileage']
        ]);
    }
    
    // Создание записи
    $bookingId = create_booking([
        'client_id' => $clientId,
        'car_id' => $carId,
        'problem_description' => $data['problem_description'],
        'mechanic_id' => $data['mechanic_id'],
        'desired_date' => $data['desired_date'],
        'desired_time' => $data['desired_time'],
        'priority' => $data['priority']
    ]);
    
    // Добавление услуг
    if (!empty($data['services'])) {
        foreach ($data['services'] as $serviceId) {
            $quantity = $data['service_quantity'][$serviceId] ?? 1;
            add_service_to_booking($bookingId, $serviceId, $quantity);
        }
    }
    
    // Обработка загруженных фото
    if (!empty($_FILES['problem_photos'])) {
        process_uploaded_photos($bookingId, $_FILES['problem_photos']);
    }
    
    return $bookingId;
}
// functions.php

// Очистка ввода
function cleanInput($data, $allow_html = false) {
    if ($allow_html) {
        // Разрешаем только безопасные HTML-теги
        $allowed_tags = '<a><strong><em><ul><ol><li><p><br><h3><h4>';
        $data = strip_tags($data, $allowed_tags);
    } else {
        $data = strip_tags($data);
    }
    return trim($data);
}

// Экранирование для JS
function js_escape($str) {
    return addslashes(htmlspecialchars($str, ENT_QUOTES));
}

// Проверка прав администратора
function checkAdminSession() {
    session_start();
    if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        header('HTTP/1.1 403 Forbidden');
        exit('Доступ запрещен');
    }
}
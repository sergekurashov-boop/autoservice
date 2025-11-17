<?php
// подключение к базе данных
require_once 'db_config.php';

// Получаем параметры
$date = $_GET['date'] ?? '';
$service_id = intval($_GET['service_id'] ?? 0);

header('Content-Type: application/json');

if (!$date || $service_id <= 0) {
    echo json_encode(['error' => 'Некорректные параметры']);
    exit;
}

// Предположим, что у вас есть таблица appointments с полями: date, start_time, end_time, service_id
try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Ошибка подключения к базе: ' . $e->getMessage()]);
    exit;
}

// Получаем длительность услуги
try {
    $stmt = $pdo->prepare("SELECT duration FROM services WHERE id = ?");
    $stmt->execute([$service_id]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$service) {
        echo json_encode(['error' => 'Услуга не найдена']);
        exit;
    }
    $duration = (int)$service['duration'];
} catch (PDOException $e) {
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
    exit;
}

// Задаем диапазон времени работы салона (например, 09:00 - 18:00)
$work_start = '09:00';
$work_end = '18:00';

// Создаем массив всех возможных времен
$times = [];
$current_time = strtotime($work_start);
$end_time = strtotime($work_end);

while ($current_time + $duration * 60 <= $end_time) {
    $time_str = date('H:i', $current_time);
    $times[] = $time_str;
    $current_time += 30 * 60; // интервал проверки — 30 минут
}

// Получаем уже забронированные интервалы на выбранную дату
try {
    $stmt = $pdo->prepare("SELECT start_time, end_time FROM appointments WHERE date = ? AND service_id = ?");
    $stmt->execute([$date, $service_id]);
    $booked = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
    exit;
}

// Фильтруем доступное время
$available_times = [];

foreach ($times as $time) {
    $slot_start = strtotime($time);
    $slot_end = $slot_start + $duration * 60;

    // Проверяем, пересекается ли слот с забронированными
    $conflict = false;
    foreach ($booked as $b) {
        $b_start = strtotime($b['start_time']);
        $b_end = strtotime($b['end_time']);

        if (($slot_start < $b_end) && ($slot_end > $b_start)) {
            $conflict = true;
            break;
        }
    }

    if (!$conflict) {
        $available_times[] = $time;
    }
}

// Возвращаем результат
echo json_encode(['available_times' => $available_times]);
?>
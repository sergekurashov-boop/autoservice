<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

$date = $_GET['date'] ?? '';
$service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;
$mechanic_id = isset($_GET['mechanic_id']) ? (int)$_GET['mechanic_id'] : 0;

if (!$date || $service_id <= 0 || $mechanic_id <= 0) {
    echo json_encode(['error' => 'Неверные параметры']);
    exit;
}

// Получаем длительность услуги
$stmt = $conn->prepare("SELECT duration FROM services WHERE id = ?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$stmt->bind_result($duration);
if (!$stmt->fetch()) {
    echo json_encode(['error' => 'Услуга не найдена']);
    exit;
}
$stmt->close();

$duration = (int)$duration;

// Рабочие часы (например, 09:00 - 18:00)
$work_start = strtotime('09:00');
$work_end = strtotime('18:00');

// Получаем занятые интервалы механика на выбранную дату
$stmt = $conn->prepare("
    SELECT start_time, end_time FROM appointments
    WHERE mechanic_id = ? AND date = ?
");
$stmt->bind_param("is", $mechanic_id, $date);
$stmt->execute();
$result = $stmt->get_result();

$busy_intervals = [];
while ($row = $result->fetch_assoc()) {
    $busy_intervals[] = [
        'start' => strtotime($row['start_time']),
        'end' => strtotime($row['end_time']),
    ];
}
$stmt->close();

// Проверка пересечения интервалов
function isBusy($start, $end, $busy_intervals) {
    foreach ($busy_intervals as $interval) {
        if ($start < $interval['end'] && $end > $interval['start']) {
            return true;
        }
    }
    return false;
}

// Генерация доступных интервалов с шагом 15 минут
$available_times = [];
for ($time = $work_start; $time + $duration * 60 <= $work_end; $time += 15 * 60) {
    $start = $time;
    $end = $time + $duration * 60;
    if (!isBusy($start, $end, $busy_intervals)) {
        $available_times[] = date('H:i', $start);
    }
}

echo json_encode(['available_times' => $available_times]);
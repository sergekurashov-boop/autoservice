<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

$service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

if ($service_id <= 0) {
    echo json_encode(['error' => 'Неверный ID услуги']);
    exit;
}

// Запрос механиков, связанных с услугой через связующую таблицу service_mechanics
$stmt = $conn->prepare("
    SELECT m.id, m.name 
    FROM mechanics m
    INNER JOIN service_mechanics sm ON m.id = sm.mechanic_id
    WHERE sm.service_id = ?
");
if (!$stmt) {
    echo json_encode(['error' => 'Ошибка подготовки запроса: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();

$mechanics = [];
while ($row = $result->fetch_assoc()) {
    $mechanics[] = $row;
}

echo json_encode(['mechanics' => $mechanics]);
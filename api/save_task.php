<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

try {
    // Сохранение основной задачи
    $stmt = $pdo->prepare("INSERT INTO tasks (...) VALUES (...)");
    $stmt->execute([...]);
    $taskId = $pdo->lastInsertId();
    
    // Сохранение связанных услуг
    if (!empty($data['services'])) {
        foreach ($data['services'] as $serviceId) {
            $quantity = $data['quantities'][$serviceId] ?? 1;
            $stmt = $pdo->prepare("INSERT INTO task_services (task_id, service_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$taskId, $serviceId, $quantity]);
        }
    }
    
    echo json_encode(['success' => true, 'task_id' => $taskId]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
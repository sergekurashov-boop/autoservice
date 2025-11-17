<?php
require_once __DIR__ . '/../includes/db_connect.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

try {
    $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    $stmt->execute([$data['status'], $data['task_id']]);
    
    if ($data['status'] === 'completed') {
        $stmt = $pdo->prepare("UPDATE tasks SET completed_at = NOW() WHERE id = ?");
        $stmt->execute([$data['task_id']]);
    }
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
<?php
// update_service_price.php
require 'includes/db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = (int)$_POST['service_id'];
    $new_price = floatval($_POST['new_price']);
    
    if ($service_id > 0 && $new_price > 0) {
        $stmt = $conn->prepare("UPDATE services SET price = ? WHERE id = ?");
        $stmt->bind_param("di", $new_price, $service_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Ошибка базы данных']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Неверные данные']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Неверный метод запроса']);
}
?>
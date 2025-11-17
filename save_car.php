<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = (int)$_POST['client_id'];
    $make = trim($_POST['make']);
    $model = trim($_POST['model']);
    $license_plate = trim($_POST['license_plate']);
    $year = !empty($_POST['year']) ? (int)$_POST['year'] : null;
    $vin = trim($_POST['vin'] ?? '');
    
    if (empty($make) || empty($model) || empty($license_plate)) {
        echo json_encode(['success' => false, 'error' => 'Заполните обязательные поля']);
        exit;
    }
    
    try {
        // Проверяем нет ли уже авто с таким гос. номером
        $check_stmt = $conn->prepare("SELECT id FROM cars WHERE license_plate = ?");
        $check_stmt->bind_param("s", $license_plate);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $existing = $check_result->fetch_assoc();
            echo json_encode(['success' => true, 'car_id' => $existing['id']]);
            exit;
        }
        
        // Добавляем новый автомобиль
        $stmt = $conn->prepare("INSERT INTO cars (client_id, make, model, license_plate, year, vin) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssis", $client_id, $make, $model, $license_plate, $year, $vin);
        
        if ($stmt->execute()) {
            $car_id = $conn->insert_id;
            echo json_encode(['success' => true, 'car_id' => $car_id]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Неверный метод запроса']);
}
?>
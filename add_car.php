<?php
require 'includes/db.php';

$client_id = intval($_POST['client_id'] ?? 0);
$make = $_POST['make'] ?? '';
$model = $_POST['model'] ?? '';
$year = $_POST['year'] ?? null;
$vin = $_POST['vin'] ?? null;
$license_plate = $_POST['license_plate'] ?? null;

if ($client_id > 0 && !empty($make) && !empty($model)) {
    $stmt = $conn->prepare("INSERT INTO cars (client_id, make, model, year, vin, license_plate) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $client_id, $make, $model, $year, $vin, $license_plate);
    
    if ($stmt->execute()) {
        header("Location: orders.php?success=Автомобиль успешно добавлен");
    } else {
        header("Location: orders.php?error=Ошибка при добавлении автомобиля");
    }
} else {
    header("Location: orders.php?error=Заполните обязательные поля");
}
exit;
<?php
require 'includes/db.php';

$client_id = (int)($_GET['client_id'] ?? 0);
if (!$client_id) {
    echo '<option value="">Ошибка: не указан клиент</option>';
    exit;
}

$stmt = $conn->prepare("SELECT id, make, model, license_plate 
                       FROM cars WHERE client_id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();

$result = $stmt->get_result();
$options = '<option value="">Выберите автомобиль</option>';

while ($car = $result->fetch_assoc()) {
    $carInfo = htmlspecialchars("{$car['make']} {$car['model']}");
    $license = $car['license_plate'] ? " ({$car['license_plate']})" : "";
    $options .= "<option value='{$car['id']}'>{$carInfo}{$license}</option>";
}

echo $options;
?>
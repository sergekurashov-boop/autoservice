<?php
require '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_GET['client_id']) || empty($_GET['client_id'])) {
    echo json_encode([]);
    exit;
}

$client_id = (int)$_GET['client_id'];

$stmt = $conn->prepare("
    SELECT id, make, model, year, vin, license_plate 
    FROM cars 
    WHERE client_id = ? 
    ORDER BY make, model
");

$stmt->bind_param('i', $client_id);
$stmt->execute();
$result = $stmt->get_result();

$cars = [];
while ($row = $result->fetch_assoc()) {
    $cars[] = $row;
}

echo json_encode($cars);
?>
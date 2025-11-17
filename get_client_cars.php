<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAuth();

header('Content-Type: application/json');

if (isset($_GET['client_id'])) {
    $client_id = (int)$_GET['client_id'];
    
    $sql = "SELECT id, make, model, year, license_plate, vin FROM cars 
            WHERE client_id = ? ORDER BY make, model";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cars = [];
    while ($row = $result->fetch_assoc()) {
        $cars[] = $row;
    }
    
    echo json_encode($cars);
} else {
    echo json_encode([]);
}
?>
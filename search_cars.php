<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAuth();

header('Content-Type: application/json');

if (isset($_GET['license_plate'])) {
    $license_plate = trim($_GET['license_plate']);
    
    $sql = "SELECT c.*, cl.name as client_name 
            FROM cars c 
            LEFT JOIN clients cl ON c.client_id = cl.id 
            WHERE c.license_plate LIKE ? 
            ORDER BY c.license_plate LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $search_term = "%$license_plate%";
    $stmt->bind_param("s", $search_term);
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
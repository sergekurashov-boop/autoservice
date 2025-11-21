<?php
// ajax_search_cars.php
session_start();
require 'includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $search_query = "%$query%";
    $stmt = $pdo->prepare("
        SELECT id, model, vin, license_plate 
        FROM cars 
        WHERE (model LIKE ? OR vin LIKE ? OR license_plate LIKE ?) 
        ORDER BY model 
        LIMIT 10
    ");
    $stmt->execute([$search_query, $search_query, $search_query]);
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($cars);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>
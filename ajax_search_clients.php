<?php
// ajax_search_clients.php
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
        SELECT id, name, phone 
        FROM clients 
        WHERE (name LIKE ? OR phone LIKE ?) 
        ORDER BY name 
        LIMIT 10
    ");
    $stmt->execute([$search_query, $search_query]);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($clients);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>
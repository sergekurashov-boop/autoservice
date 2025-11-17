<?php
// search_clients.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Логируем запрос
error_log("Search clients called with: " . ($_GET['q'] ?? 'empty'));

require 'includes/db.php';

header('Content-Type: application/json');

if (!isset($_GET['q']) || empty($_GET['q'])) {
    error_log("Empty search query");
    echo json_encode([]);
    exit;
}

$searchTerm = '%' . $_GET['q'] . '%';
error_log("Search term: " . $searchTerm);

try {
    $stmt = $conn->prepare("
        SELECT id, name, phone, email 
        FROM clients 
        WHERE name LIKE ? OR phone LIKE ? 
        ORDER BY name 
        LIMIT 10
    ");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param('ss', $searchTerm, $searchTerm);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $clients = [];
    
    while ($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }
    
    error_log("Found " . count($clients) . " clients");
    echo json_encode($clients);
    
} catch (Exception $e) {
    error_log("Error in search_clients: " . $e->getMessage());
    echo json_encode([]);
}
?>
<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAuth();

header('Content-Type: application/json');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    if (!empty($search)) {
        $sql = "SELECT id, name, phone, email FROM clients 
                WHERE name LIKE ? OR phone LIKE ? OR email LIKE ? 
                ORDER BY name LIMIT 20";
        $stmt = $conn->prepare($sql);
        $search_term = "%$search%";
        $stmt->bind_param("sss", $search_term, $search_term, $search_term);
    } else {
        $sql = "SELECT id, name, phone, email FROM clients ORDER BY name LIMIT 20";
        $stmt = $conn->prepare($sql);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $clients = [];
    while ($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }

    echo json_encode($clients);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
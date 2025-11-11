<?php
require '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_GET['q']) || empty($_GET['q'])) {
    echo json_encode([]);
    exit;
}

$searchTerm = '%' . $_GET['q'] . '%';

$stmt = $conn->prepare("
    SELECT id, name, phone, email 
    FROM clients 
    WHERE name LIKE ? OR phone LIKE ? 
    ORDER BY name 
    LIMIT 10
");

$stmt->bind_param('ss', $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$clients = [];
while ($row = $result->fetch_assoc()) {
    $clients[] = $row;
}

echo json_encode($clients);
?>
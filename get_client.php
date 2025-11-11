<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAuth();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode([]);
    exit;
}

$client_id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT id, name, phone FROM clients WHERE id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();

if ($client = $result->fetch_assoc()) {
    echo json_encode($client);
} else {
    echo json_encode([]);
}
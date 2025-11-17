<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Введите ФИО клиента']);
        exit;
    }
    
    try {
        // Проверяем нет ли уже такого клиента
        $check_stmt = $conn->prepare("SELECT id FROM clients WHERE name = ? OR phone = ?");
        $check_stmt->bind_param("ss", $name, $phone);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $existing = $check_result->fetch_assoc();
            echo json_encode(['success' => true, 'client_id' => $existing['id']]);
            exit;
        }
        
        // Добавляем нового клиента
        $stmt = $conn->prepare("INSERT INTO clients (name, phone, email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $phone, $email);
        
        if ($stmt->execute()) {
            $client_id = $conn->insert_id;
            echo json_encode(['success' => true, 'client_id' => $client_id]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Неверный метод запроса']);
}
?>
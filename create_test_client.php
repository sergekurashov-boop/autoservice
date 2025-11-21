<?php
session_start();
require_once 'auth.php';
requireAuth();

require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = "Тестовый Клиент " . rand(1000, 9999);
    $phone = "+7" . rand(9000000000, 9999999999);
    $email = "test" . rand(100, 999) . "@example.com";
    
    try {
        $stmt = $pdo->prepare("INSERT INTO clients (name, phone, email, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$name, $phone, $email]);
        
        $_SESSION['success'] = "Тестовый клиент создан: $name";
        header("Location: create_order.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Ошибка создания клиента: " . $e->getMessage();
        header("Location: create_order.php");
        exit;
    }
}
?>
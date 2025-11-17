<?php
require 'includes/db.php';

// Включим отображение ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$name = $_POST['name'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';

// Проверка обязательных полей
if (empty($name) || empty($phone)) {
    header("Location: orders.php?error=Заполните обязательные поля");
    exit;
}

try {
    // Подготовка и выполнение запроса
    $stmt = $conn->prepare("INSERT INTO clients (name, phone, email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $phone, $email);
    
    if ($stmt->execute()) {
        header("Location: orders.php?success=Клиент успешно добавлен");
    } else {
        error_log("Ошибка при добавлении клиента: " . $stmt->error);
        header("Location: orders.php?error=Ошибка при добавлении клиента");
    }
} catch (Exception $e) {
    error_log("Ошибка: " . $e->getMessage());
    header("Location: orders.php?error=Произошла ошибка: " . $e->getMessage());
}

$conn->close();
exit;
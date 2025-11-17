<?php
require 'includes/db.php';
session_start();

if (!isset($_POST['order_id']) || empty($_POST['order_id'])) {
    $_SESSION['error'] = "Не указан ID заказа для удаления";
    header("Location: orders.php");
    exit;
}

$order_id = (int)$_POST['order_id'];

// Удаляем связанные услуги
$stmt = $conn->prepare("DELETE FROM order_services WHERE order_id = ?");
$stmt->bind_param('i', $order_id);
$stmt->execute();

// Удаляем связанные запчасти
$stmt = $conn->prepare("DELETE FROM order_parts WHERE order_id = ?");
$stmt->bind_param('i', $order_id);
$stmt->execute();

// Удаляем сам заказ
$stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
$stmt->bind_param('i', $order_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Заказ #$order_id успешно удалён";
} else {
    $_SESSION['error'] = "Ошибка при удалении заказа: " . $conn->error;
}

header("Location: orders.php");
exit;
?>
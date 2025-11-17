<?php
require 'includes/db.php';
// В начало каждого edit-файла
error_log(date('Y-m-d H:i:s') . " - Редактирование записи ID: $id\n", 3, "logs/edits.log");
if (!isset($_GET['id'])) {
    header("Location: cars.php");
    exit;
}

$car_id = $_GET['id'];

// Проверяем, есть ли заказы для этого автомобиля
$orders = $conn->query("SELECT id FROM orders WHERE car_id = $car_id");

if ($orders->num_rows > 0) {
    $_SESSION['error'] = "Нельзя удалить автомобиль, у которого есть заказы!";
    header("Location: cars.php");
    exit;
}

$conn->query("DELETE FROM cars WHERE id = $car_id");
$_SESSION['success'] = "Автомобиль успешно удален!";
header("Location: cars.php");
?>
<?php
require 'includes/db.php';
require 'includes/auth.php';

// Проверка данных
$required = ['order_number', 'client_id', 'car_id', 'company_id', 'issue_description'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        die("Ошибка: Не заполнено поле '$field'");
    }
}

// Сохранение заказ-наряда
$order_number = $conn->real_escape_string($_POST['order_number']);
$client_id = (int)$_POST['client_id'];
$car_id = (int)$_POST['car_id'];
$company_id = (int)$_POST['company_id'];
$issue_description = $conn->real_escape_string($_POST['issue_description']);
$total_amount = 0;

// Расчет общей суммы
if (!empty($_POST['services'])) {
    foreach ($_POST['services'] as $service) {
        $price = (float)$service['price'];
        $quantity = (int)$service['quantity'];
        $total_amount += $price * $quantity;
    }
}

// Сохранение основного заказа
$conn->query("INSERT INTO work_orders 
             (order_number, client_id, car_id, company_id, issue_description, total_amount)
             VALUES
             ('$order_number', $client_id, $car_id, $company_id, 
             '$issue_description', $total_amount)");
             
$work_order_id = $conn->insert_id;

// Сохранение услуг
if (!empty($_POST['services'])) {
    foreach ($_POST['services'] as $service) {
        $name = $conn->real_escape_string($service['name']);
        $price = (float)$service['price'];
        $quantity = (int)$service['quantity'];
        
        $conn->query("INSERT INTO work_order_services 
                     (work_order_id, service_name, price, quantity)
                     VALUES
                     ($work_order_id, '$name', $price, $quantity)");
    }
}

// Редирект на страницу печати
header("Location: print_work_order.php?id=$work_order_id");
exit;
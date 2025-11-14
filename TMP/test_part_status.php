<?php
// test_part_status.php
session_start();
require 'includes/db.php';

// Установим тестовые данные сессии
$_SESSION['user_id'] = 1;
$_SESSION['csrf_token'] = 'test_token';

// Найдем существующий order_part для теста
$test_part = $conn->query("SELECT * FROM order_parts LIMIT 1")->fetch_assoc();

if ($test_part) {
    echo "Тестируем запчасть: " . $test_part['part_id'] . " в заказе: " . $test_part['order_id'] . "\n";
    
    // Тело запроса для update_part_status.php
    $test_data = [
        'part_id' => $test_part['part_id'],
        'order_id' => $test_part['order_id'], 
        'new_status' => 'issued',
        'csrf_token' => 'test_token'
    ];
    
    echo "Тестовые данные: " . json_encode($test_data) . "\n";
    echo "Текущий статус: " . $test_part['issue_status'] . "\n";
} else {
    echo "Нет запчастей для теста. Сначала создайте заказ с запчастями.\n";
}
?>
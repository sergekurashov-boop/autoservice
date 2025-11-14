<?php
session_start();
require 'includes/db.php';

// Установим тестовые данные сессии
$_SESSION['user_id'] = 1;
$_SESSION['csrf_token'] = 'test_token';

// Тестовые данные из предыдущего теста
$test_data = [
    'part_id' => 11,
    'order_id' => 10,
    'new_status' => 'issued',
    'csrf_token' => 'test_token'
];

// Имитируем POST запрос к update_part_status.php
$url = 'http://localhost/autoservice/update_part_status.php';
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $http_code . "\n";
echo "Response: " . $response . "\n";

// Проверим результат в БД
$check_sql = "SELECT * FROM order_parts WHERE order_id = 10 AND part_id = 11";
$result = $conn->query($check_sql);
if ($result && $row = $result->fetch_assoc()) {
    echo "Текущий статус в БД: " . $row['issue_status'] . "\n";
    
    // Проверим логи
    $log_sql = "SELECT * FROM part_status_log WHERE order_id = 10 AND part_id = 11 ORDER BY changed_at DESC LIMIT 1";
    $log_result = $conn->query($log_sql);
    if ($log_result && $log_row = $log_result->fetch_assoc()) {
        echo "Последняя запись в логах: " . $log_row['old_status'] . " → " . $log_row['new_status'] . "\n";
    } else {
        echo "Записей в логах нет\n";
    }
}
?>
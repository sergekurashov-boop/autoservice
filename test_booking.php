<?php
// test_booking.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAuth();

echo "<h1>Диагностика unified_booking.php</h1>";

// Проверим существует ли файл
if (file_exists('unified_booking.php')) {
    echo "<p style='color: green;'>✅ Файл unified_booking.php существует</p>";
    
    // Покажем первые строки файла чтобы увидеть ошибку
    $lines = file('unified_booking.php');
    echo "<h3>Первые 10 строк файла:</h3>";
    echo "<pre>";
    for ($i = 0; $i < min(10, count($lines)); $i++) {
        echo htmlspecialchars($lines[$i]);
    }
    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ Файл unified_booking.php не существует</p>";
}

// Проверим есть ли необходимые таблицы для записи
$tables = ['services', 'mechanics'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Таблица '$table' существует</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Таблица '$table' не существует</p>";
    }
}

exit;
?>
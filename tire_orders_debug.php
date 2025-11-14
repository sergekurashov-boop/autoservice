<?php
session_start();
define('ACCESS', true);
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== ДЕБАГ TIRE_ORDERS ===<br>";

// Проверяем подключения
echo "1. Подключаем db.php... ";
require_once 'includes/db.php';
echo "✅<br>";

echo "2. Подключаем functions.php... ";
require_once 'includes/functions.php';
echo "✅<br>";

echo "3. Подключаем auth.php... ";
require_once 'includes/auth.php';
echo "✅<br>";

echo "4. Проверяем авторизацию... ";
checkAuthentication();
echo "✅<br>";

echo "5. Сессия user_id: " . ($_SESSION['user_id'] ?? 'не установлен') . "<br>";

// Проверяем таблицу tire_orders
try {
    echo "6. Проверяем таблицу tire_orders... ";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tire_orders");
    $result = $stmt->fetch();
    echo "✅ (" . $result['count'] . " записей)<br>";
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "<br>";
}

echo "=== ДЕБАГ ЗАВЕРШЕН ===<br>";
?>
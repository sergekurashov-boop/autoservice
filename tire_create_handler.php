<?php
define('ACCESS', true);
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!-- Debug: Script started -->";

// Подключение к базе данных из папки includes
require_once 'includes/db.php';
echo "<!-- Debug: Database connected -->";

// Проверка авторизации (если есть в functions.php)
require_once 'includes/functions.php';
echo "<!-- Debug: Functions loaded -->";

// Простая проверка авторизации
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

echo "<!-- Debug: User is logged in, ID: " . ($_SESSION['user_id'] ?? 'unknown') . " -->";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h1>🐛 ДЕБАГ ОБРАБОТЧИКА ШИНОМОНТАЖА</h1>";
    echo "<div style='background: #f0f0f0; padding: 20px; margin: 20px; border: 1px solid #ccc;'>";
    
    try {
        // Выводим все POST данные
        echo "<h3>📨 POST данные:</h3>";
        echo "<pre>";
        print_r($_POST);
        echo "</pre>";
        
        // Проверяем подключение к БД
        echo "<h3>🗄️ Проверка базы данных:</h3>";
        $stmt = $pdo->query("SELECT DATABASE() as db_name");
        $db = $stmt->fetch();
        echo "База данных: " . $db['db_name'] . "<br>";
        
        // Проверяем существование таблицы tire_orders
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM tire_orders");
            $result = $stmt->fetch();
            echo "Таблица tire_orders: ✅ существует (" . $result['count'] . " записей)<br>";
        } catch (PDOException $e) {
            echo "Таблица tire_orders: ❌ не существует. Создаем...<br>";
            
            // Создаем таблицу
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS tire_orders (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    client_id INT NOT NULL,
                    car_id INT NOT NULL,
                    vin VARCHAR(50),
                    license_plate VARCHAR(20),
                    mileage INT,
                    services TEXT,
                    tire_data JSON,
                    notes TEXT,
                    status VARCHAR(20) DEFAULT 'draft',
                    created_by INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            echo "✅ Таблица tire_orders создана<br>";
        }
        
        // Основные данные из формы
        echo "<h3>📝 Данные из формы:</h3>";
        $client_id = $_POST['client_id'] ?? null;
        $car_id = $_POST['car_id'] ?? null;
        $vin = $_POST['vin'] ?? '';
        $license_plate = $_POST['license_plate'] ?? '';
        $mileage = $_POST['mileage'] ?? 0;
        $notes = $_POST['notes'] ?? '';
        $services = isset($_POST['services']) ? implode(',', $_POST['services']) : '';
        
        echo "Client ID: " . $client_id . "<br>";
        echo "Car ID: " . $car_id . "<br>";
        echo "VIN: " . $vin . "<br>";
        echo "License Plate: " . $license_plate . "<br>";
        echo "Mileage: " . $mileage . "<br>";
        echo "Services: " . $services . "<br>";
        echo "Notes: " . $notes . "<br>";
        
        // Данные по шинам
        echo "<h3>🛞 Данные по шинам:</h3>";
        $tire_data = [
            'fl_size' => $_POST['tire_fl_size'] ?? '',
            'fl_brand' => $_POST['tire_fl_brand'] ?? '',
            'fr_size' => $_POST['tire_fr_size'] ?? '',
            'fr_brand' => $_POST['tire_fr_brand'] ?? '',
            'rl_size' => $_POST['tire_rl_size'] ?? '',
            'rl_brand' => $_POST['tire_rl_brand'] ?? '',
            'rr_size' => $_POST['tire_rr_size'] ?? '',
            'rr_brand' => $_POST['tire_rr_brand'] ?? ''
        ];
        
        echo "<pre>";
        print_r($tire_data);
        echo "</pre>";
        
        // Пробуем вставить данные
        echo "<h3>💾 Попытка сохранения в БД:</h3>";
        
        $sql = "INSERT INTO tire_orders 
                (client_id, car_id, vin, license_plate, mileage, services, tire_data, notes, created_by, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')";
        
        echo "SQL: " . $sql . "<br>";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $client_id, 
            $car_id, 
            $vin, 
            $license_plate, 
            $mileage, 
            $services, 
            json_encode($tire_data), 
            $notes, 
            $_SESSION['user_id']
        ]);
        
        if ($result) {
            $order_id = $pdo->lastInsertId();
            echo "✅ УСПЕХ! Заказ создан. ID: " . $order_id . "<br>";
            
            // Показываем кнопки вместо редиректа
            echo "<div style='margin-top: 20px; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb;'>";
            echo "<h4>🎉 Заказ успешно создан!</h4>";
            echo "<p>ID заказа: <strong>" . $order_id . "</strong></p>";
            echo "<a href='tire_orders.php' class='btn btn-success'>📋 К списку заказов</a> ";
            echo "<a href='tire_create.php' class='btn btn-primary'>➕ Новый заказ</a>";
            echo "</div>";
            
        } else {
            echo "❌ Ошибка при выполнении запроса<br>";
        }
        
        echo "</div>";
        
    } catch (PDOException $e) {
        echo "<div style='background: #f8d7da; padding: 20px; margin: 20px; border: 1px solid #f5c6cb;'>";
        echo "<h3>❌ ОШИБКА БАЗЫ ДАННЫХ:</h3>";
        echo "<p><strong>" . $e->getMessage() . "</strong></p>";
        echo "<p>Код ошибки: " . $e->getCode() . "</p>";
        echo "</div>";
        
        // Показываем кнопку назад
        echo "<a href='tire_create.php' class='btn btn-warning'>← Назад к форме</a>";
    }
    
} else {
    echo "<h1>❌ Неверный метод запроса</h1>";
    echo "<p>Используйте POST запрос для отправки формы</p>";
    echo "<a href='tire_create.php' class='btn btn-primary'>← Назад к форме</a>";
}

// Добавляем базовые стили для кнопок
echo "
<style>
.btn {
    display: inline-block;
    padding: 10px 20px;
    margin: 5px;
    background: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    border: none;
    cursor: pointer;
}
.btn-success { background: #28a745; }
.btn-primary { background: #007bff; }
.btn-warning { background: #ffc107; color: black; }
</style>
";

echo "<!-- Debug: Script ended -->";
?>
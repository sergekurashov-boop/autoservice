<?php
$host = 'localhost';
$dbname = 'autoservice';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
	// Функция для работы со складом
function addWarehouseItem($data) {
    global $pdo; // Используем существующее подключение
    
    $stmt = $pdo->prepare("
        INSERT INTO warehouse_items 
        (sku, name, description, category_id, manufacturer_id, price, quantity, min_quantity, location)
        VALUES (:sku, :name, :desc, :cat_id, :man_id, :price, :qty, :min_qty, :loc)
    ");
    
    $stmt->execute([
        ':sku'    => $data['sku'],
        ':name'   => $data['name'],
        ':desc'   => $data['description'],
        ':cat_id' => $data['category_id'],
        ':man_id' => $data['manufacturer_id'],
        ':price'  => $data['price'],
        ':qty'    => $data['quantity'],
        ':min_qty'=> $data['min_quantity'],
        ':loc'    => $data['location']
    ]);
    
    return $pdo->lastInsertId();
}
}
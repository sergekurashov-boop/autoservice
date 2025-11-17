<?php
// Подключение к базе данных
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'autoservice';
// ins
$dsn = "mysql:host=$host;dbname=$db;charset=utf8";
try {
    // Создаем PDO соединение
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}
// end ins
$conn = new mysqli($host, $user, $pass, $db);

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]
;

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
//function rotate_logs($log_dir) {
 //   if (file_exists($log_dir . '/edits.log') && filesize($log_dir . '/edits.log') > 1048576) { // 1MB
   //     rename($log_dir . '/edits.log', $log_dir . '/edits_' . date('Y-m-d') . '.log');
    //}
//}
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
?>
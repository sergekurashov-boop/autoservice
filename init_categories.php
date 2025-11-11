<?php
require_once 'config.php';

$categories = [
    ['name' => 'Двигатель', 'description' => 'Запчасти для двигателя'],
    ['name' => 'Тормозная система', 'description' => 'Тормозные колодки, диски, суппорты'],
    ['name' => 'Подвеска', 'description' => 'Амортизаторы, пружины, рычаги'],
    ['name' => 'Электрика', 'description' => 'Проводка, датчики, реле'],
    ['name' => 'Кузовные детали', 'description' => 'Бампера, крылья, двери'],
    ['name' => 'Фильтры', 'description' => 'Воздушные, масляные, салонные фильтры'],
    ['name' => 'Система охлаждения', 'description' => 'Радиаторы, помпы, патрубки'],
    ['name' => 'Трансмиссия', 'description' => 'Сцепление, коробка передач, ШРУСы'],
];

try {
    $pdo = new PDO(...); // Ваши параметры подключения
    
    $stmt = $pdo->prepare("INSERT INTO warehouse_categories (name, description) VALUES (?, ?)");
    
    foreach ($categories as $category) {
        $stmt->execute([$category['name'], $category['description']]);
    }
    
    echo "Категории успешно добавлены!";
} catch (PDOException $e) {
    die("Ошибка: " . $e->getMessage());
}
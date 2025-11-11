<?php
require_once 'includes/db.php';

// Генерация услуг для передней оси
$frontWorks = [];
for ($i = 1; $i <= 50; $i++) {
    $frontWorks[] = [
        'category' => 'front_axis',
        'name' => "Передняя ось - Услуга $i",
        'description' => "Описание услуги для передней оси #$i",
        'duration' => rand(30, 180),
        'price' => rand(500, 5000)
    ];
}

// Генерация услуг для задней оси
$rearWorks = [];
for ($i = 1; $i <= 50; $i++) {
    $rearWorks[] = [
        'category' => 'rear_axis',
        'name' => "Задняя ось - Услуга $i",
        'description' => "Описание услуги для задней оси #$i",
        'duration' => rand(30, 180),
        'price' => rand(500, 5000)
    ];
}

// Объединяем массивы
$allWorks = array_merge($frontWorks, $rearWorks);

// Вставляем в базу
$stmt = $pdo->prepare("
    INSERT INTO works (category, name, description, duration, price)
    VALUES (:category, :name, :description, :duration, :price)
");

foreach ($allWorks as $work) {
    $stmt->execute($work);
}

echo "Успешно импортировано " . count($allWorks) . " услуг!";
<?php
require 'includes/db.php';

$query = $_GET['query'] ?? '';
if (empty($query)) die(json_encode([]));

$results = [];
$searchTerm = "%$query%";

// Клиенты (первые 3)
$clients = $pdo->prepare("SELECT id, name, phone FROM clients 
                         WHERE name LIKE :query 
                            OR phone LIKE :query 
                         LIMIT 3")
               ->execute([':query' => $searchTerm])
               ->fetchAll(PDO::FETCH_ASSOC);

// Автомобили (первые 3)
$cars = $pdo->prepare("SELECT id, make, model, license_plate FROM cars 
                      WHERE make LIKE :query 
                         OR model LIKE :query 
                         OR license_plate LIKE :query 
                      LIMIT 3")
            ->execute([':query' => $searchTerm])
            ->fetchAll(PDO::FETCH_ASSOC);

// Заказы (первые 3)
$orders = $pdo->prepare("SELECT o.id, c.make, c.model, cl.name AS client_name 
                       FROM orders o
                       JOIN cars c ON o.car_id = c.id
                       JOIN clients cl ON c.client_id = cl.id
                       WHERE o.id = :query_id 
                          OR cl.name LIKE :query 
                          OR c.license_plate LIKE :query 
                       LIMIT 3")
              ->execute([
                  ':query' => $searchTerm,
                  ':query_id' => is_numeric($query) ? (int)$query : 0
              ])
              ->fetchAll(PDO::FETCH_ASSOC);

// Формируем ответ
$response = [
    'clients' => $clients,
    'cars' => $cars,
    'orders' => $orders
];

header('Content-Type: application/json');
echo json_encode($response);
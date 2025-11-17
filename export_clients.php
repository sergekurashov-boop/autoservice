<?php
require 'includes/db.php';
session_start();



// Получение данных
$clients = $conn->query("SELECT * FROM clients")->fetch_all(MYSQLI_ASSOC);

// Настройка заголовков для скачивания файла
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=clients_' . date('Y-m-d') . '.csv');

// Создание CSV-файла
$output = fopen('php://output', 'w');

// Заголовки CSV
fputcsv($output, ['ID', 'Имя', 'Телефон'], ';');

// Данные
foreach ($clients as $client) {
    fputcsv($output, [
        $client['id'],
        $client['name'],
        $client['phone']
    ], ';');
}

fclose($output);
exit;
?>
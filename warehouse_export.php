<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAnyRole(['admin', 'manager', 'mechanic']);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=warehouse_export_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// Заголовок CSV
fputcsv($output, [
    'Название',
    'Категория', 
    'Артикул',
    'Цена',
    'Количество',
    'Мин.запас',
    'Местоположение',
    'Описание'
], ';');

// Данные
$sql = "
    SELECT 
        wi.name,
        wc.name as category_name,
        wi.sku,
        wi.price,
        wi.quantity,
        wi.min_quantity,
        wi.location,
        wi.description
    FROM warehouse_items wi
    LEFT JOIN warehouse_categories wc ON wi.category_id = wc.id
    ORDER BY wi.name
";

$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['name'],
            $row['category_name'] ?? '',
            $row['sku'],
            $row['price'],
            $row['quantity'],
            $row['min_quantity'],
            $row['location'] ?? '',
            $row['description'] ?? ''
        ], ';');
    }
}

fclose($output);
exit;
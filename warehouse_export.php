<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAnyRole(['admin', 'manager', 'mechanic']);

// Определяем формат экспорта с валидацией
$format = in_array($_GET['format'] ?? 'csv', ['csv', 'json']) ? $_GET['format'] : 'csv';

try {
    // Получаем данные для экспорта
    $query = "
        SELECT 
            wi.sku,
            wi.name,
            wc.name as category_name,
            wi.part_number,
            wi.price,
            wi.quantity,
            wi.min_quantity,
            wi.location,
            wi.description,
            wi.created_at,
            wi.updated_at
        FROM warehouse_items wi 
        LEFT JOIN warehouse_categories wc ON wi.category_id = wc.id 
        ORDER BY wi.name
    ";

    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception('Ошибка получения данных: ' . $conn->error);
    }
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    if ($format === 'json') {
        // Экспорт в JSON
        $export_data = [
            'export_info' => [
                'export_date' => date('Y-m-d H:i:s'),
                'exported_by' => $_SESSION['user_name'] ?? 'Unknown',
                'total_items' => count($data),
                'version' => '1.1'
            ],
            'warehouse_items' => $data
        ];
        
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="warehouse_export_' . date('Y-m-d_H-i') . '.json"');
        echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
        
    } else {
        // Экспорт в CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="warehouse_export_' . date('Y-m-d_H-i') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // BOM для UTF-8
        
        // Заголовки CSV
        fputcsv($output, [
            'Артикул',
            'Название', 
            'Категория',
            'Артикул производителя',
            'Цена',
            'Количество',
            'Мин. запас',
            'Местоположение',
            'Описание',
            'Создан',
            'Обновлен'
        ], ';');
        
        // Данные
        foreach ($data as $row) {
            fputcsv($output, [
                $row['sku'],
                $row['name'],
                $row['category_name'] ?? '',
                $row['part_number'] ?? '',
                $row['price'],
                $row['quantity'],
                $row['min_quantity'],
                $row['location'] ?? '',
                $row['description'] ?? '',
                $row['created_at'],
                $row['updated_at']
            ], ';');
        }
        
        fclose($output);
    }
    
} catch (Exception $e) {
    // Логирование ошибки и редирект
    error_log("Export error: " . $e->getMessage());
    $_SESSION['error'] = "Ошибка при экспорте данных";
    header("Location: warehouse.php");
    exit;
}
?>
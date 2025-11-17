<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAnyRole(['admin']); // Только для админов!

$format = in_array($_GET['format'] ?? 'json', ['json', 'csv']) ? $_GET['format'] : 'json';

try {
    $export_data = [
        'export_info' => [
            'export_date' => date('Y-m-d H:i:s'),
            'exported_by' => $_SESSION['user_name'],
            'system_version' => '1.0',
            'purpose' => 'full_system_backup'
        ],
        'tables' => []
    ];

    // Получаем список всех таблиц (кроме системных)
    $result = $conn->query("SHOW TABLES");
    $tables = [];
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }

    // Экспортируем каждую таблицу
    foreach ($tables as $table) {
        // Пропускаем временные/системные таблицы
        if (strpos($table, 'tmp_') === 0 || strpos($table, 'log_') === 0) {
            continue;
        }

        $table_data = [];
        $table_result = $conn->query("SELECT * FROM $table");
        
        // Получаем названия колонок
        $columns = [];
        $fields = $table_result->fetch_fields();
        foreach ($fields as $field) {
            $columns[] = $field->name;
        }

        // Получаем данные
        while ($row = $table_result->fetch_assoc()) {
            $table_data[] = $row;
        }

        $export_data['tables'][$table] = [
            'columns' => $columns,
            'row_count' => count($table_data),
            'data' => $table_data
        ];
    }

    // Экспорт в JSON
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="autoservice_full_export_' . date('Y-m-d_H-i') . '.json"');
    
    echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

} catch (Exception $e) {
    error_log("Full export error: " . $e->getMessage());
    $_SESSION['error'] = "Ошибка при полном экспорте системы";
    header("Location: dashboard.php");
    exit;
}
?>
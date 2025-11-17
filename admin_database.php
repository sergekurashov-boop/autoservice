<?php
session_start();
require 'includes/db.php';

// Проверка авторизации
if (!isset($_SESSION['superuser'])) {
    header("Location: super_login.php");
    exit;
}

// Получение статистики БД
$db_stats = [];
$table_sizes = [];

try {
    $result = $conn->query("
        SELECT 
            table_name,
            table_rows,
            ROUND(data_length / 1024 / 1024, 2) as data_mb,
            ROUND(index_length / 1024 / 1024, 2) as index_mb
        FROM information_schema.TABLES 
        WHERE table_schema = 'autoservice'
        ORDER BY data_length DESC
    ");
    $table_sizes = $result->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    $error = "Ошибка: " . $e->getMessage();
}

// Выполнение SQL запросов
$sql_result = null;
if ($_POST && isset($_POST['sql_query'])) {
    $sql_query = trim($_POST['sql_query']);
    
    if (!empty($sql_query)) {
        // Безопасность: только SELECT
        if (stripos($sql_query, 'SELECT') === 0) {
            try {
                $result = $conn->query($sql_query);
                if ($result === true) {
                    $sql_result = ['type' => 'success', 'message' => 'Запрос выполнен'];
                } else {
                    $sql_result_data = [];
                    while ($row = $result->fetch_assoc()) {
                        $sql_result_data[] = $row;
                    }
                    $sql_result = ['type' => 'data', 'data' => $sql_result_data];
                }
            } catch (Exception $e) {
                $sql_result = ['type' => 'error', 'message' => $e->getMessage()];
            }
        } else {
            $sql_result = ['type' => 'error', 'message' => 'Разрешены только SELECT запросы'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление базой данных</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        .header { background: #2c3e50; color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .sql-editor { margin: 20px 0; }
        .sql-textarea { width: 100%; height: 100px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-family: monospace; }
        .btn { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        .table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗃️ Управление базой данных</h1>
            <p>Пользователь: <?= $_SESSION['superuser']['username'] ?> | 
               <a href="super_logout.php" style="color: white;">Выйти</a>
            </p>
        </div>

        <!-- SQL редактор -->
        <div class="sql-editor">
            <h3>🔧 SQL запросы (только SELECT)</h3>
            <form method="post">
                <textarea name="sql_query" class="sql-textarea" placeholder="SELECT * FROM employees WHERE..."><?= $_POST['sql_query'] ?? '' ?></textarea>
                <button type="submit" class="btn">Выполнить</button>
            </form>
            
            <?php if ($sql_result): ?>
                <?php if ($sql_result['type'] === 'error'): ?>
                    <div style="color: red; margin: 10px 0;">❌ <?= $sql_result['message'] ?></div>
                <?php elseif ($sql_result['type'] === 'success'): ?>
                    <div style="color: green; margin: 10px 0;">✅ <?= $sql_result['message'] ?></div>
                <?php elseif ($sql_result['type'] === 'data'): ?>
                    <div style="margin: 10px 0;">
                        <strong>Найдено записей: <?= count($sql_result['data']) ?></strong>
                        <?php if (!empty($sql_result['data'])): ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <?php foreach (array_keys($sql_result['data'][0]) as $column): ?>
                                            <th><?= $column ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sql_result['data'] as $row): ?>
                                        <tr>
                                            <?php foreach ($row as $value): ?>
                                                <td><?= htmlspecialchars($value) ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Статистика таблиц -->
        <div>
            <h3>📊 Статистика таблиц</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Таблица</th>
                        <th>Записей</th>
                        <th>Размер (МБ)</th>
                        <th>Индексы (МБ)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($table_sizes as $table): ?>
                    <tr>
                        <td><?= $table['TABLE_NAME'] ?></td>
                        <td><?= number_format($table['TABLE_ROWS']) ?></td>
                        <td><?= $table['data_mb'] ?></td>
                        <td><?= $table['index_mb'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php
// cleanup_data.php
require_once 'includes/db.php';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->beginTransaction();
    
    // Отключение проверки внешних ключей
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Удаление данных из связанных таблиц
    $tables = ['orders', 'cars', 'appointments', 'invoices', 'tasks'];
    foreach ($tables as $table) {
        $pdo->exec("DELETE FROM $table WHERE client_id IN (SELECT id FROM clients)");
        echo "Данные из таблицы $table удалены.<br>";
    }
    
    // Удаление клиентов
    $pdo->exec("DELETE FROM clients");
    echo "Данные клиентов удалены.<br>";
    
    // Включение проверки внешних ключей
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    $pdo->commit();
    echo "Очистка данных завершена успешно.";
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Ошибка: " . $e->getMessage();
}
?>
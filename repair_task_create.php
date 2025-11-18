<?php
require 'includes/db.php';
session_start();
define('ACCESS', true);

$defect_id = $_GET['defect_id'] ?? 0;

// Получаем данные дефектной ведомости
$defect_stmt = $pdo->prepare("
    SELECT d.*, c.name as client_name, car.*, e.name as master_name
    FROM defects d
    LEFT JOIN clients c ON d.client_id = c.id
    LEFT JOIN cars car ON d.car_id = car.id
    LEFT JOIN employees e ON d.master_id = e.id
    WHERE d.id = ?
");
$defect_stmt->execute([$defect_id]);
$defect = $defect_stmt->fetch(PDO::FETCH_ASSOC);

// Получаем механиков
$mechanics = $pdo->query("SELECT * FROM employees WHERE type = 'mechanic' AND active = 1")->fetchAll();

if ($_POST) {
    // Создаем задание в ремзону
    $task_number = 'TASK-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    $task_stmt = $pdo->prepare("
        INSERT INTO repair_tasks (defect_id, task_number, master_id, mechanic_id, workstation, planned_hours, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $task_stmt->execute([
        $defect_id, $task_number, $_SESSION['user_id'], 
        $_POST['mechanic_id'], $_POST['workstation'], $_POST['planned_hours'], $_POST['notes']
    ]);
    
    $task_id = $pdo->lastInsertId();
    
    // Переносим работы из дефектной ведомости
    $items_stmt = $pdo->prepare("SELECT * FROM defect_items WHERE defect_id = ? AND type = 'service'");
    $items_stmt->execute([$defect_id]);
    $items = $items_stmt->fetchAll();
    
    foreach ($items as $item) {
        $time_stmt = $pdo->prepare("
            INSERT INTO repair_task_items (task_id, defect_item_id, type, name, quantity, planned_time, mechanic_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $time_stmt->execute([
            $task_id, $item['id'], $item['type'], $item['name'], 
            $item['quantity'], $_POST['time_' . $item['id']], $_POST['mechanic_' . $item['id']]
        ]);
    }
    
    header("Location: repair_task_view.php?id=$task_id");
    exit;
}
?>

<!-- HTML интерфейс для создания задания -->
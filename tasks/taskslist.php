<?php
// Подключение к базе
require 'autoservice/includes/db.php';
require_once 'autoservice/includes/navbar.php';

// Получаем фильтры из GET-параметров
$filter_date = $_GET['due_date'] ?? '';
$filter_car_id = $_GET['car_id'] ?? '';

// Получаем список машин для фильтра
$cars = $pdo->query("SELECT id, model FROM cars ORDER BY model")->fetchAll(PDO::FETCH_ASSOC);

// Формируем запрос с фильтрами
$sql = "SELECT t.id, t.description, t.due_date, t.status, c.name AS client_name, car.model AS car_model
        FROM tasks t
        JOIN clients c ON t.client_id = c.id
        LEFT JOIN cars car ON t.car_id = car.id
        WHERE 1=1";

$params = [];

if ($filter_date) {
    $sql .= " AND t.due_date = :due_date";
    $params[':due_date'] = $filter_date;
}

if ($filter_car_id) {
    $sql .= " AND t.car_id = :car_id";
    $params[':car_id'] = $filter_car_id;
}

$sql .= " ORDER BY t.due_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<form method="GET">
    <label>Фильтр по дате:<br>
        <input type="date" name="due_date" value="<?= htmlspecialchars($filter_date) ?>">
    </label>
    
    <label>Фильтр по машине:<br>
        <select name="car_id">
            <option value="">Все машины</option>
            <?php foreach ($cars as $car): ?>
                <option value="<?= htmlspecialchars($car['id']) ?>" <?= $car['id'] == $filter_car_id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($car['model']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    
    <button type="submit">Применить фильтр</button>
</form>

<h2>Список задач</h2>
<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <th>Клиент</th>
        <th>Машина</th>
        <th>Описание</th>
        <th>Срок</th>
        <th>Статус</th>
    </tr>
    <?php if ($tasks): ?>
        <?php foreach ($tasks as $task): ?>
            <tr>
                <td><?= htmlspecialchars($task['client_name']) ?></td>
                <td><?= htmlspecialchars($task['car_model'] ?? '-') ?></td>
                <td><?= htmlspecialchars($task['description']) ?></td>
                <td><?= htmlspecialchars($task['due_date']) ?></td>
                <td><?= htmlspecialchars($task['status']) ?></td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="5">Задачи не найдены</td></tr>
    <?php endif; ?>
</table>
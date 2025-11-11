<?php
require 'includes/db.php';
session_start();
$current_page = basename($_SERVER['PHP_SELF']);
// Включаем шапку с навбаром
define('ACCESS', true);
include 'templates/header.php';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Вспомогательная функция для безопасного вывода
function safe_html($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Получаем список клиентов и машин для форм и фильтров
$clients = $pdo->query("SELECT id, name FROM clients ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$cars = $pdo->query("SELECT id, model FROM cars ORDER BY model")->fetchAll(PDO::FETCH_ASSOC);

// Обработка смены статуса (через GET)
if (isset($_GET['toggle_status_id'])) {
    $id = (int)$_GET['toggle_status_id'];
    $stmt = $pdo->prepare("SELECT status FROM tasks WHERE id = ?");
    $stmt->execute([$id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($task) {
        $new_status = ($task['status'] === 'pending') ? 'done' : 'pending';
        $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
    }
    header("Location: tasks.php");
    exit;
}

// Инициализация переменных для добавления/редактирования
$edit_mode = false;
$task = [
    'id' => null,
    'client_id' => '',
    'car_id' => '',
    'description' => '',
    'due_date' => '',
    'status' => 'pending'
];

// Обработка запроса на редактирование (через GET)
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$edit_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$task) {
        die("Задача не найдена");
    }
}

// Обработка отправки формы добавления/редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'] ?? null;
    $car_id = $_POST['car_id'] ?: null;
    $description = trim($_POST['description'] ?? '');
    $due_date = $_POST['due_date'] ?? '';
    $status = $_POST['status'] ?? 'pending';
    $task_id = $_POST['task_id'] ?? null;

    if ($client_id && $description && $due_date) {
        if ($task_id) {
            // Обновление задачи
            $stmt = $pdo->prepare("UPDATE tasks SET client_id = ?, car_id = ?, description = ?, due_date = ?, status = ? WHERE id = ?");
            $stmt->execute([$client_id, $car_id, $description, $due_date, $status, $task_id]);
            echo "<div class='alert alert-success'>Задача обновлена!</div>";
        } else {
            // Добавление новой задачи
            $stmt = $pdo->prepare("INSERT INTO tasks (client_id, car_id, description, due_date, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$client_id, $car_id, $description, $due_date, $status]);
            echo "<div class='alert alert-success'>Задача добавлена!</div>";
        }
        header("Location: tasks.php");
        exit;
    } else {
        echo "<div class='alert alert-danger'>Пожалуйста, заполните все обязательные поля.</div>";
    }
}

// Фильтры для списка задач
$filter_date = $_GET['due_date'] ?? '';
$filter_car_id = $_GET['car_id'] ?? '';

// Запрос на выборку задач с фильтрами
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

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление задачами автосервиса</title>
    <!-- Подключение Bootstrap CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- Подключение иконок -->
    <link rel="stylesheet" href="assets/icons/font/bootstrap-icons.css">
</head>
<body>
<div class="container my-4">

    <h2><?= $edit_mode ? '<span style="color: #ffffff;">Редактировать задачу' : '<span style="color: #076cd9;">Добавить новую задачу' ?></h2>
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="task_id" value="<?= safe_html($task['id']) ?>">

                <div class="mb-3">
                    <label class="form-label">Клиент:</label>
                    <select class="form-select" name="client_id" required>
                        <option value="">Выберите клиента</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= safe_html($client['id']) ?>" <?= $client['id'] == $task['client_id'] ? 'selected' : '' ?>>
                                <?= safe_html($client['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Машина:</label>
                    <select class="form-select" name="car_id">
                        <option value="">Не выбрано</option>
                        <?php foreach ($cars as $car): ?>
                            <option value="<?= safe_html($car['id']) ?>" <?= $car['id'] == $task['car_id'] ? 'selected' : '' ?>>
                                <?= safe_html($car['model']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Описание задачи:</label>
                    <textarea class="form-control" name="description" required rows="3"><?= safe_html($task['description']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Срок выполнения:</label>
                    <input class="form-control" type="date" name="due_date" value="<?= safe_html($task['due_date']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Статус:</label>
                    <select class="form-select" name="status">
                        <option value="pending" <?= $task['status'] == 'pending' ? 'selected' : '' ?>>Ожидает</option>
                        <option value="done" <?= $task['status'] == 'done' ? 'selected' : '' ?>>Выполнена</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary"><?= $edit_mode ? 'Сохранить' : 'Добавить' ?></button>
                <?php if ($edit_mode): ?>
                    <a href="tasks.php" class="btn btn-secondary ms-2">Отмена</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <h2><span style="color: #076cd9;">Фильтр задач</h2>
    <form method="GET" class="row g-3 mb-4 align-items-end">
        <div class="col-auto">
            <label class="form-label">Дата:</label>
            <input type="date" name="due_date" value="<?= safe_html($filter_date) ?>" class="form-control">
        </div>

        <div class="col-auto">
            <label class="form-label">Машина:</label>
            <select name="car_id" class="form-select">
                <option value="">Все машины</option>
                <?php foreach ($cars as $car): ?>
                    <option value="<?= safe_html($car['id']) ?>" <?= $car['id'] == $filter_car_id ? 'selected' : '' ?>>
                        <?= safe_html($car['model']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-auto">
            <button type="submit" class="btn btn-success">Применить фильтр</button>
        </div>
    </form>

    <h2><span style="color: #076cd9;">Список задач</h2>
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>Клиент</th>
                <th>Машина</th>
                <th>Описание</th>
                <th>Срок</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($tasks): ?>
            <?php foreach ($tasks as $task_item): ?>
                <tr>
                    <td><?= safe_html($task_item['client_name']) ?></td>
                    <td><?= safe_html($task_item['car_model'] ?? '-') ?></td>
                    <td><?= safe_html($task_item['description']) ?></td>
                    <td><?= safe_html($task_item['due_date']) ?></td>
                    <td>
                        <?php if ($task_item['status'] == 'done'): ?>
                            <span class="badge bg-success">Выполнена</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Ожидает</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?edit_id=<?= safe_html($task_item['id']) ?>" class="btn btn-sm btn-primary">Редактировать</a>
                        <a href="?toggle_status_id=<?= safe_html($task_item['id']) ?>" class="btn btn-sm btn-outline-secondary ms-1">
                            <?= $task_item['status'] == 'pending' ? 'Отметить выполненной' : 'Отметить ожидающей' ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6" class="text-center">Задачи не найдены</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

</div>


 <?php include 'templates/footer.php'; ?>
</body>
</html>
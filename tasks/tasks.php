<?php
require 'db.php'; // Подключение к базе

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
<div class="container">

    <h2><?= $edit_mode ? 'Редактировать задачу' : 'Добавить новую задачу' ?></h2>
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="task_id" value="<?= htmlspecialchars($task['id']) ?>">

                <div class="mb-3">
                    <label class="form-label">Клиент:</label>
                    <select class="form-select" name="client_id" required>
                        <option value="">Выберите клиента</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= htmlspecialchars($client['id']) ?>" <?= $client['id'] == $task['client_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($client['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Машина:</label>
                    <select class="form-select" name="car_id">
                        <option value="">Не выбрано</option>
                        <?php foreach ($cars as $car): ?>
                            <option value="<?= htmlspecialchars($car['id']) ?>" <?= $car['id'] == $task['car_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($car['model']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Описание задачи:</label>
                    <textarea class="form-control" name="description" required rows="3"><?= htmlspecialchars($task['description']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Срок выполнения:</label>
                    <input class="form-control" type="date" name="due_date" value="<?= htmlspecialchars($task['due_date']) ?>" required>
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

    <h2>Фильтр задач</h2>
    <form method="GET" class="row g-3 mb-4 align-items-end">
        <div class="col-auto">
            <label class="form-label">Дата:</label>
            <input type="date" name="due_date" value="<?= htmlspecialchars($filter_date) ?>" class="form-control">
        </div>

        <div class="col-auto">
            <label class="form-label">Машина:</label>
            <select name="car_id" class="form-select">
                <option value="">Все машины</option>
                <?php foreach ($cars as $car): ?>
                    <option value="<?= htmlspecialchars($car['id']) ?>" <?= $car['id'] == $filter_car_id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($car['model']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-auto">
            <button type="submit" class="btn btn-success">Применить фильтр</button>
        </div>
    </form>

    <h2>Список задач</h2>
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
                    <td><?= htmlspecialchars($task_item['client_name']) ?></td>
                    <td><?= htmlspecialchars($task_item['car_model'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($task_item['description']) ?></td>
                    <td><?= htmlspecialchars($task_item['due_date']) ?></td>
                    <td>
                        <?php if ($task_item['status'] == 'done'): ?>
                            <span class="badge bg-success">Выполнена</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Ожидает</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?edit_id=<?= $task_item['id'] ?>" class="btn btn-sm btn-primary">Редактировать</a>
                        <a href="?toggle_status_id=<?= $task_item['id'] ?>" class="btn btn-sm btn-outline-secondary ms-1">
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

<!-- Bootstrap JS (опционально) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
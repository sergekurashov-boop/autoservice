<?php
require 'db.php'; // Подключение к базе данных

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID задачи не указан");
}

// Получаем список клиентов и машин
$clients = $pdo->query("SELECT id, name FROM clients ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$cars = $pdo->query("SELECT id, model FROM cars ORDER BY model")->fetchAll(PDO::FETCH_ASSOC);

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'] ?? null;
    $car_id = $_POST['car_id'] ?? null;
    $description = $_POST['description'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $status = $_POST['status'] ?? 'pending';

    if ($client_id && $description && $due_date) {
        $stmt = $pdo->prepare("UPDATE tasks SET client_id = ?, car_id = ?, description = ?, due_date = ?, status = ? WHERE id = ?");
        $stmt->execute([$client_id, $car_id ?: null, $description, $due_date, $status, $id]);
        echo "<p>Задача обновлена!</p>";
    } else {
        echo "<p>Пожалуйста, заполните все обязательные поля.</p>";
    }
}

// Получаем данные задачи для заполнения формы
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->execute([$id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$task) {
    die("Задача не найдена");
}
?>

<form method="POST">
    <label>Клиент:<br>
        <select name="client_id" required>
            <?php foreach ($clients as $client): ?>
                <option value="<?= htmlspecialchars($client['id']) ?>" <?= $client['id'] == $task['client_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($client['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label><br><br>

    <label>Машина:<br>
        <select name="car_id">
            <option value="">Не выбрано</option>
            <?php foreach ($cars as $car): ?>
                <option value="<?= htmlspecialchars($car['id']) ?>" <?= $car['id'] == $task['car_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($car['model']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label><br><br>

    <label>Описание задачи:<br>
        <textarea name="description" required><?= htmlspecialchars($task['description']) ?></textarea>
    </label><br><br>

    <label>Срок выполнения:<br>
        <input type="date" name="due_date" value="<?= htmlspecialchars($task['due_date']) ?>" required>
    </label><br><br>

    <label>Статус:<br>
        <select name="status">
            <option value="pending" <?= $task['status'] == 'pending' ? 'selected' : '' ?>>Ожидает</option>
            <option value="done" <?= $task['status'] == 'done' ? 'selected' : '' ?>>Выполнена</option>
        </select>
    </label><br><br>

    <button type="submit">Сохранить</button>
</form>
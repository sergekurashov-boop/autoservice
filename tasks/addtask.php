<?php
// Подключение к базе (пример)
$pdo = new PDO('mysql:host=localhost;dbname=autoservice', 'user', 'password');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Получаем список клиентов и машин для выпадающих списков
$clients = $pdo->query("SELECT id, name FROM clients ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$cars = $pdo->query("SELECT id, model FROM cars ORDER BY model")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'] ?? null;
    $car_id = $_POST['car_id'] ?? null;
    $description = $_POST['description'] ?? '';
    $due_date = $_POST['due_date'] ?? '';

    if ($client_id && $description && $due_date) {
        $stmt = $pdo->prepare("INSERT INTO tasks (client_id, car_id, description, due_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$client_id, $car_id ?: null, $description, $due_date]);
        echo "<p>Задача успешно добавлена!</p>";
    } else {
        echo "<p>Пожалуйста, заполните все обязательные поля.</p>";
    }
}
?>

<form method="POST">
    <label>Клиент:<br>
        <select name="client_id" required>
            <option value="">Выберите клиента</option>
            <?php foreach ($clients as $client): ?>
                <option value="<?= htmlspecialchars($client['id']) ?>"><?= htmlspecialchars($client['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label><br><br>

    <label>Машина:<br>
        <select name="car_id">
            <option value="">Не выбрано</option>
            <?php foreach ($cars as $car): ?>
                <option value="<?= htmlspecialchars($car['id']) ?>"><?= htmlspecialchars($car['model']) ?></option>
            <?php endforeach; ?>
        </select>
    </label><br><br>

    <label>Описание задачи:<br>
        <textarea name="description" required></textarea>
    </label><br><br>

    <label>Срок выполнения:<br>
        <input type="date" name="due_date" required>
    </label><br><br>

    <button type="submit">Добавить задачу</button>
</form>
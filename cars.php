<?php
require 'includes/db.php';
session_start();

define('ACCESS', true);
include 'templates/header.php';

// Обработка действий с автомобилями
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Добавление автомобиля
    if (isset($_POST['add_car'])) {
        $client_id = (int)$_POST['client_id'];
        $make = trim($_POST['make']);
        $model = trim($_POST['model']);
        $year = trim($_POST['year'] ?? null);
        $vin = trim($_POST['vin'] ?? '');
        $license_plate = trim($_POST['license_plate'] ?? '');

        if (empty($client_id) || empty($make) || empty($model)) {
            $_SESSION['error'] = "Пожалуйста, заполните все обязательные поля";
        } else {
            $stmt = $conn->prepare("INSERT INTO cars (client_id, make, model, year, vin, license_plate) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $client_id, $make, $model, $year, $vin, $license_plate);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "🚗 Автомобиль успешно добавлен";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $_SESSION['error'] = "Ошибка при добавлении автомобиля: " . $conn->error;
            }
        }
    }
    // Удаление автомобиля
    elseif (isset($_POST['delete_car'])) {
        $car_id = (int)$_POST['car_id'];
        
        // Проверяем, есть ли заказы для этого автомобиля
        $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE car_id = ?");
        $stmt->bind_param('i', $car_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_row();
        $order_count = $row[0];
        
        if ($order_count > 0) {
            $_SESSION['error'] = "Невозможно удалить автомобиль, у которого есть заказы";
        } else {
            $stmt = $conn->prepare("DELETE FROM cars WHERE id = ?");
            $stmt->bind_param('i', $car_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "🚗 Автомобиль успешно удалён";
            } else {
                $_SESSION['error'] = "Ошибка при удалении автомобиля: " . $conn->error;
            }
        }
    }
}

// Получаем список клиентов для выпадающего списка
$clients = [];
$clients_result = $conn->query("SELECT id, name FROM clients ORDER BY name");
if ($clients_result) {
    $clients = $clients_result->fetch_all(MYSQLI_ASSOC);
}

// Получаем список автомобилей с информацией о владельцах
$cars = [];
$cars_result = $conn->query("
    SELECT c.id, c.make, c.model, c.year, c.vin, c.license_plate, 
           cl.id AS client_id, cl.name AS client_name
    FROM cars c
    JOIN clients cl ON c.client_id = cl.id
    ORDER BY c.make, c.model
");
if ($cars_result) {
    $cars = $cars_result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление автомобилями</title>
    <link rel="stylesheet" href="assets/css/cars.css?v=<?= time() ?>">
    
</head>
<body class="cars-container">
    <div class="container mt-4">
        <h1 class="page-title">🚗 Управление автомобилями</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-enhanced alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-enhanced alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Форма добавления автомобиля -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">➕ Добавить новый автомобиль</div>
            <div class="card-body">
                <form method="post" id="carForm">
                    <div class="mb-3">
                        <label class="form-label">👤 Владелец*</label>
                        <select name="client_id" class="form-select" required>
                            <option value="">Выберите клиента</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">🏷️ Марка*</label>
                                <input type="text" name="make" class="form-control" placeholder="Например: Toyota" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">🚙 Модель*</label>
                                <input type="text" name="model" class="form-control" placeholder="Например: Camry" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">📅 Год выпуска</label>
                                <input type="number" name="year" class="form-control" placeholder="2020" 
                                       min="1900" max="<?= date('Y') + 1 ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">🔢 VIN</label>
                                <input type="text" name="vin" class="form-control" placeholder="17-значный номер">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">🚘 Гос. номер</label>
                                <input type="text" name="license_plate" class="form-control" placeholder="А123БВ77">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="add_car" class="btn-1c-primary">✅ Добавить автомобиль</button>
                </form>
            </div>
        </div>

        <!-- Список автомобилей -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">
                📋 Список автомобилей (<?= count($cars) ?>)
            </div>
            <div class="card-body">
                <?php if (!empty($cars)): ?>
                    <div class="table-responsive">
                        <table class="table-enhanced">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>🚗 Автомобиль</th>
                                    <th>📅 Год</th>
                                    <th>🔢 VIN</th>
                                    <th>🚘 Гос. номер</th>
                                    <th>👤 Владелец</th>
                                    <th>⚡ Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cars as $car): ?>
                                <tr>
                                    <td><strong><?= $car['id'] ?></strong></td>
                                    <td>
                                        <strong><?= htmlspecialchars($car['make']) ?> <?= htmlspecialchars($car['model']) ?></strong>
                                    </td>
                                    <td><?= $car['year'] ?: '—' ?></td>
                                    <td><?= htmlspecialchars($car['vin']) ?: '—' ?></td>
                                    <td>
                                        <?php if ($car['license_plate']): ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($car['license_plate']) ?></span>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="client_edit.php?id=<?= $car['client_id'] ?>" class="text-decoration-none">
                                            👤 <?= htmlspecialchars($car['client_name']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="car_edit.php?id=<?= $car['id'] ?>" class="btn-1c-warning">
                                                ✏️
                                            </a>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="car_id" value="<?= $car['id'] ?>">
                                                <button type="submit" name="delete_car" class="btn-1c-danger" 
                                                        onclick="return confirm('❌ Вы уверены, что хотите удалить автомобиль «<?= htmlspecialchars($car['make'] . ' ' . $car['model']) ?>»?')">
                                                    🗑️
                                                </button>
                                            </form>
                                            <a href="orders.php?car_id=<?= $car['id'] ?>" class="btn-1c-primary">
                                                📋
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🚗</div>
                        <div>Нет автомобилей в базе данных</div>
                        <div class="mt-3">
                            <p class="text-muted">Добавьте первый автомобиль для клиента</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<script src="assets/js/cars.js?v=<?= time() ?>"></script>
    
    <?php include 'templates/footer.php'; ?>
</body>
</html>
<?php
require 'includes/db.php';
session_start();

$car_id = intval($_GET['id'] ?? 0);

// Проверяем существование автомобиля
$car = $conn->query("SELECT * FROM cars WHERE id = $car_id")->fetch_assoc();
if (!$car) {
    $_SESSION['error'] = "Автомобиль не найден";
    header("Location: cars.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $make = trim($_POST['make'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $year = !empty($_POST['year']) ? intval($_POST['year']) : null;
    $vin = !empty($_POST['vin']) ? trim($_POST['vin']) : null;
    $license_plate = !empty($_POST['license_plate']) ? trim($_POST['license_plate']) : null;
    $client_id = intval($_POST['client_id'] ?? 0);
    
    // Валидация
    $errors = [];
    
    if (empty($client_id)) {
        $errors[] = "Выберите владельца";
    }
    if (empty($make)) {
        $errors[] = "Введите марку автомобиля";
    }
    if (empty($model)) {
        $errors[] = "Введите модель автомобиля";
    }
    if ($year && ($year < 1900 || $year > date('Y') + 1)) {
        $errors[] = "Некорректный год выпуска";
    }
    if ($vin && strlen($vin) !== 17) {
        $errors[] = "VIN должен содержать 17 символов";
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE cars SET client_id=?, make=?, model=?, year=?, vin=?, license_plate=? WHERE id=?");
        $stmt->bind_param("isssssi", $client_id, $make, $model, $year, $vin, $license_plate, $car_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "🚗 Автомобиль успешно обновлен";
            header("Location: cars.php");
            exit;
        } else {
            $_SESSION['error'] = "Ошибка при обновлении автомобиля: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}

$clients = $conn->query("SELECT id, name FROM clients ORDER BY name");

include 'templates/header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование автомобиля</title>
    <link rel="stylesheet" href="assets/css/car_edit.css?v=<?= time() ?>">
    
</head>
<body class="car-edit-container">
    <div class="container mt-4">
        <h1 class="page-title">✏️ Редактирование автомобиля</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-enhanced alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-enhanced alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="enhanced-card">
            <div class="enhanced-card-header">
                🚗 <?= htmlspecialchars($car['make'] . ' ' . $car['model']) ?>
            </div>
            <div class="card-body">
                <form method="post" id="carEditForm">
                    <div class="mb-3">
                        <label class="form-label">👤 Владелец*</label>
                        <select name="client_id" class="form-select" required>
                            <option value="">Выберите клиента</option>
                            <?php while($client = $clients->fetch_assoc()): ?>
                                <option value="<?= $client['id'] ?>" 
                                    <?= $car['client_id'] == $client['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($client['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">🏷️ Марка*</label>
                                <input type="text" name="make" class="form-control" 
                                       value="<?= htmlspecialchars($car['make']) ?>" 
                                       placeholder="Например: Toyota" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">🚙 Модель*</label>
                                <input type="text" name="model" class="form-control" 
                                       value="<?= htmlspecialchars($car['model']) ?>" 
                                       placeholder="Например: Camry" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">📅 Год выпуска</label>
                                <input type="number" name="year" class="form-control" 
                                       value="<?= $car['year'] ?>" 
                                       placeholder="2020" 
                                       min="1900" max="<?= date('Y') + 1 ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">🔢 VIN</label>
                                <input type="text" name="vin" class="form-control" 
                                       value="<?= htmlspecialchars($car['vin']) ?>" 
                                       placeholder="17-значный номер"
                                       maxlength="17">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">🚘 Гос. номер</label>
                                <input type="text" name="license_plate" class="form-control" 
                                       value="<?= htmlspecialchars($car['license_plate']) ?>" 
                                       placeholder="А123БВ77">
                            </div>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn-1c-primary">💾 Сохранить изменения</button>
                        <a href="cars.php" class="btn-1c-secondary">❌ Отмена</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
<script src="assets/js/car_edit.js?v=<?= time() ?>"></script>
   
    <?php include 'templates/footer.php'; ?>
</body>
</html>
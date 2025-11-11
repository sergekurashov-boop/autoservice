<?php
require 'includes/db.php';
session_start();
define('ACCESS', true);
include 'templates/header.php';

// Проверяем ID услуги
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "ID услуги не указан";
    header("Location: services.php");
    exit;
}

$service_id = (int)$_GET['id'];

// Получаем данные услуги с защитой от SQL-инъекций
$stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();
$service = $result->fetch_assoc();

if (!$service) {
    $_SESSION['error'] = "Услуга не найдена";
    header("Location: services.php");
    exit;
}

// Обработка обновления услуги
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);

    // Валидация данных
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Введите название услуги";
    } elseif (strlen($name) < 2) {
        $errors[] = "Название услуги должно содержать минимум 2 символа";
    } elseif (strlen($name) > 100) {
        $errors[] = "Название услуги не должно превышать 100 символов";
    }
    
    if ($price <= 0) {
        $errors[] = "Введите корректную цену";
    } elseif ($price > 1000000) {
        $errors[] = "Цена не может превышать 1 000 000 руб.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE services SET name = ?, price = ? WHERE id = ?");
        $stmt->bind_param("sdi", $name, $price, $service_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "✅ Услуга успешно обновлена";
            header("Location: services.php");
            exit;
        } else {
            $_SESSION['error'] = "Ошибка при обновлении услуги: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
    
    // Обновляем данные для отображения в форме
    $service['name'] = $name;
    $service['price'] = $price;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование услуги</title>
    <link rel="stylesheet" href="assets/css/service_edit.css?v=<?= time() ?>">
    
</head>
<body class="service-edit-container">
   
    
    <div class="container mt-4">
        <h1 class="page-title">✏️ Редактирование услуги</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-enhanced alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-enhanced alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Информация об услуге -->
        <div class="service-info">
            <div class="service-info-item">
                <span class="service-info-label">ID услуги:</span>
                <span class="service-info-value">#<?= $service_id ?></span>
            </div>
            <div class="service-info-item">
                <span class="service-info-label">Текущее название:</span>
                <span class="service-info-value"><?= htmlspecialchars($service['name']) ?></span>
            </div>
            <div class="service-info-item">
                <span class="service-info-label">Текущая цена:</span>
                <span class="service-info-value current-price"><?= number_format($service['price'], 2, '.', ' ') ?> руб.</span>
            </div>
        </div>

        <!-- Форма редактирования -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">
                🛠️ Редактирование услуги
            </div>
            <div class="card-body">
                <form method="post" id="serviceEditForm">
                    <div class="mb-3">
                        <label class="form-label">📝 Название услуги*</label>
                        <input type="text" name="name" class="form-control" 
                               value="<?= htmlspecialchars($service['name']) ?>" 
                               placeholder="Введите название услуги"
                               required minlength="2" maxlength="100">
                        <div class="form-text">Минимум 2 символа, максимум 100</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">💰 Цена (руб.)*</label>
                        <input type="number" name="price" class="form-control" 
                               value="<?= number_format($service['price'], 2, '.', '') ?>" 
                               step="0.01" min="0.01" max="1000000"
                               placeholder="0.00" required>
                        <div class="form-text">От 0.01 до 1 000 000 руб.</div>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" name="update_service" class="btn-1c-primary">
                            💾 Сохранить изменения
                        </button>
                        <a href="services.php" class="btn-1c-secondary">❌ Отмена</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/service_edit.js?v=<?= time() ?>"></script>
    <?php include 'templates/footer.php'; ?>
</body>
</html>
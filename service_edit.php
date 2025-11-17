<?php
require 'includes/db.php';
session_start();

define('ACCESS', true);
include 'templates/header.php';

$id = (int)$_GET['id'] ?? 0;

if ($id === 0) {
    $_SESSION['error'] = "❌ Неверный идентификатор услуги";
    header("Location: services.php");
    exit;
}

// Получение данных услуги
$stmt = $conn->prepare("SELECT id, name, code, price FROM services WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$service = $result->fetch_assoc();

if (!$service) {
    $_SESSION['error'] = "❌ Услуга не найдена";
    header("Location: services.php");
    exit;
}

// Обработка обновления услуги
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $code = trim($_POST['code'] ?? ''); // Новое поле - код

    // Валидация данных
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Введите название услуги";
    } elseif (strlen($name) < 2) {
        $errors[] = "Название услуги должно содержать минимум 2 символа";
    }
    
    if ($price <= 0) {
        $errors[] = "Введите корректную цену";
    } elseif ($price > 1000000) {
        $errors[] = "Цена не может превышать 1 000 000 руб.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE services SET name = ?, price = ?, code = ? WHERE id = ?");
        $stmt->bind_param("sdsi", $name, $price, $code, $id);
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
    
    // Обновляем данные для отображения
    $service['name'] = $name;
    $service['price'] = $price;
    $service['code'] = $code;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование услуги - Автосервис</title>
    <link rel="stylesheet" href="assets/css/services.css?v=<?= time() ?>">
    <style>
        .service-code {
            background: #e6d8a8;
            color: #5c4a00;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
            font-size: 0.8rem;
        }
    </style>
</head>
<body class="services-container">
   
    
    <div class="container mt-4">
        <div class="header-compact">
            <h1 class="page-title-compact">✏️ Редактирование услуги #<?= $service['id'] ?></h1>
            <div class="header-actions-compact">
                <a href="services.php" class="action-btn-compact">
                    <span class="action-icon">←</span>
                    <span class="action-label">Назад к услугам</span>
                </a>
            </div>
        </div>
        
        <!-- Вывод сообщений -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-enhanced alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-enhanced alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Форма редактирования услуги -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">📝 Редактирование услуги</div>
            <div class="card-body">
                <form method="post" id="serviceForm">
                    <div class="mb-3">
                        <label class="form-label">📝 Название услуги*</label>
                        <input type="text" name="name" class="form-control" 
                               value="<?= htmlspecialchars($service['name']) ?>" 
                               placeholder="Например: Замена масла" required
                               minlength="2" maxlength="100">
                        <div class="form-text">Минимум 2 символа</div>
                    </div>
                    
                    <!-- НОВОЕ ПОЛЕ - КОД -->
                    <div class="mb-3">
                        <label class="form-label">🔢 Код для поиска</label>
                        <input type="text" name="code" class="form-control" 
                               value="<?= htmlspecialchars($service['code'] ?? '') ?>"
                               placeholder="Например: 15, TO, OIL"
                               maxlength="20">
                        <div class="form-text">Неуникальный код для быстрого поиска в заказах</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">💰 Цена (руб.)*</label>
                        <input type="number" step="0.01" name="price" class="form-control" 
                               value="<?= number_format($service['price'], 2, '.', '') ?>"
                               placeholder="0.00" required
                               min="0.01" max="1000000">
                        <div class="form-text">От 0.01 до 1 000 000 руб.</div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_service" class="btn-1c-primary">💾 Сохранить изменения</button>
                        <a href="services.php" class="btn-1c-outline">❌ Отмена</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Информация об услуге -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">ℹ️ Информация об услуге</div>
            <div class="card-body">
                <div class="row-1c">
                    <div>
                        <strong>ID услуги:</strong> #<?= $service['id'] ?>
                    </div>
                    <div>
                        <strong>Текущий код:</strong> 
                        <?php if (!empty($service['code'])): ?>
                            <span class="service-code"><?= htmlspecialchars($service['code']) ?></span>
                        <?php else: ?>
                            <span class="text-muted">не установлен</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <strong>Текущая цена:</strong> <?= number_format($service['price'], 2, '.', ' ') ?> руб.
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'templates/footer.php'; ?>
</body>
</html>
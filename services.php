<?php
require 'includes/db.php';
session_start();

define('ACCESS', true);
include 'templates/header.php';

// Обработка добавления услуги
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);

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
        $stmt = $conn->prepare("INSERT INTO services (name, price) VALUES (?, ?)");
        $stmt->bind_param("sd", $name, $price);
        if ($stmt->execute()) {
            $_SESSION['success'] = "✅ Услуга успешно добавлена";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $_SESSION['error'] = "Ошибка при добавлении услуги: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}

// Обработка удаления услуги
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_service'])) {
    $id = (int)$_POST['id'];
    
    if ($id > 0) {
        // Проверяем, используется ли услуга в заказах
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM order_services WHERE service_id = ?");
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_row();
        $usage_count = $row[0];
        
        if ($usage_count > 0) {
            $_SESSION['error'] = "Невозможно удалить услугу, которая используется в заказах";
        } else {
            $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "✅ Услуга успешно удалена";
            } else {
                $_SESSION['error'] = "Ошибка при удалении услуги";
            }
        }
    } else {
        $_SESSION['error'] = "Некорректный идентификатор услуги";
    }
}

// Получение списка услуг
$services = $conn->query("SELECT * FROM services ORDER BY name");
$services_count = $services->num_rows;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление услугами</title>
    <link rel="stylesheet" href="assets/css/services.css?v=<?= time() ?>">
    
</head>
<body class="services-container">
   
    
    <div class="container mt-4">
        <h1 class="page-title">🛠️ Управление услугами</h1>
        
        <!-- Вывод сообщений -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-enhanced alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-enhanced alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Форма добавления услуги -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">➕ Добавить услугу</div>
            <div class="card-body">
                <form method="post" id="serviceForm">
                    <div class="mb-3">
                        <label class="form-label">📝 Название услуги*</label>
                        <input type="text" name="name" class="form-control" 
                               placeholder="Например: Замена масла" required
                               minlength="2" maxlength="100">
                        <div class="form-text">Минимум 2 символа</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">💰 Цена (руб.)*</label>
                        <input type="number" step="0.01" name="price" class="form-control" 
                               placeholder="0.00" required
                               min="0.01" max="1000000">
                        <div class="form-text">От 0.01 до 1 000 000 руб.</div>
                    </div>
                    
                    <button type="submit" name="add_service" class="btn-1c-primary">✅ Добавить услугу</button>
                </form>
            </div>
        </div>

        <!-- Таблица услуг -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">
                📋 Список услуг (<?= $services_count ?>)
            </div>
            <div class="card-body">
                <?php if ($services_count > 0): ?>
                    <div class="table-responsive">
                        <table class="table-enhanced">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>📝 Название</th>
                                    <th>💰 Цена</th>
                                    <th>⚡ Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($service = $services->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= $service['id'] ?></strong></td>
                                    <td>
                                        <strong><?= htmlspecialchars($service['name']) ?></strong>
                                    </td>
                                    <td class="price-cell">
                                        <?= number_format($service['price'], 2, '.', ' ') ?> руб.
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="service_edit.php?id=<?= $service['id'] ?>" class="btn-1c-warning">
                                                ✏️ Редактировать
                                            </a>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="id" value="<?= $service['id'] ?>">
                                                <button type="submit" name="delete_service" class="btn-1c-danger" 
                                                        onclick="return confirm('❌ Вы уверены, что хотите удалить услугу «<?= htmlspecialchars($service['name']) ?>»?')">
                                                    🗑️ Удалить
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🛠️</div>
                        <div>Нет услуг в базе данных</div>
                        <div class="mt-3">
                            <p class="text-muted">Добавьте первую услугу для использования в заказах</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<script src="assets/js/services.js?v=<?= time() ?>"></script>
    
    <?php include 'templates/footer.php'; ?>
</body>
</html>
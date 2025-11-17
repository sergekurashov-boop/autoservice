<?php
require 'includes/db.php';
session_start();

define('ACCESS', true);
include 'templates/header.php';

// Обработка действий с запчастями
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Добавление запчасти
    if (isset($_POST['add_part'])) {
        $name = trim($_POST['name']);
        $part_number = trim($_POST['part_number']);
        $price = (float)($_POST['price'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);

        // Валидация
        $errors = [];
        
        if (empty($name)) {
            $errors[] = "Введите название запчасти";
        } elseif (strlen($name) < 2) {
            $errors[] = "Название должно содержать минимум 2 символа";
        }
        
        if (empty($part_number)) {
            $errors[] = "Введите артикул запчасти";
        }
        
        if ($price <= 0) {
            $errors[] = "Цена должна быть больше 0";
        }
        
        if ($quantity < 0) {
            $errors[] = "Количество не может быть отрицательным";
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("INSERT INTO parts (name, part_number, price, quantity) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssdi", $name, $part_number, $price, $quantity);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "🔧 Запчасть успешно добавлена";
                header("Location: parts.php");
                exit;
            } else {
                $_SESSION['error'] = "Ошибка при добавлении запчасти: " . $conn->error;
            }
        } else {
            $_SESSION['error'] = implode("<br>", $errors);
        }
    }
    // Удаление запчасти
    elseif (isset($_POST['delete_part'])) {
        $part_id = (int)$_POST['part_id'];
        
        // Проверяем, используется ли запчасть в заказах
        $stmt = $conn->prepare("SELECT COUNT(*) FROM order_parts WHERE part_id = ?");
        $stmt->bind_param('i', $part_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_row();
        $order_count = $row[0];
        
        if ($order_count > 0) {
            $_SESSION['error'] = "Невозможно удалить запчасть, которая используется в заказах";
        } else {
            $stmt = $conn->prepare("DELETE FROM parts WHERE id = ?");
            $stmt->bind_param('i', $part_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "🔧 Запчасть успешно удалена";
            } else {
                $_SESSION['error'] = "Ошибка при удалении запчасти: " . $conn->error;
            }
        }
        
        header("Location: parts.php");
        exit;
    }
}

// Получаем список запчастей
$parts = [];
$parts_result = $conn->query("SELECT * FROM parts ORDER BY name");
if ($parts_result) {
    $parts = $parts_result->fetch_all(MYSQLI_ASSOC);
}
$parts_count = count($parts);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление запчастями</title>
    <link rel="stylesheet" href="assets/css/parts.css?v=<?= time() ?>">
    <script src="assets/js/parts.js?v=<?= time() ?>"></script>
</head>
<body class="parts-container">
    <div class="container mt-4">
        <h1 class="page-title">🔧 Управление запчастями</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-enhanced alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-enhanced alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Форма добавления запчасти -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">➕ Добавить новую запчасть</div>
            <div class="card-body">
                <form method="post" id="partForm">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">🔤 Название запчасти*</label>
                                <input type="text" name="name" class="form-control" 
                                       placeholder="Например: Тормозные колодки" 
                                       required minlength="2" maxlength="255">
                                <div class="form-text">Минимум 2 символа</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">🏷️ Артикул*</label>
                                <input type="text" name="part_number" class="form-control" 
                                       placeholder="Например: ABC-123" 
                                       required maxlength="100">
                                <div class="form-text">Уникальный идентификатор запчасти</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">💰 Цена (руб.)*</label>
                                <input type="number" step="0.01" min="0.01" max="1000000" 
                                       name="price" class="form-control" 
                                       placeholder="0.00" required>
                                <div class="form-text">От 0.01 до 1 000 000 руб.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">📦 Количество на складе</label>
                                <input type="number" name="quantity" class="form-control" 
                                       value="0" min="0" max="100000"
                                       placeholder="0">
                                <div class="form-text">0 - нет в наличии</div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="add_part" class="btn-1c-primary">✅ Добавить запчасть</button>
                </form>
            </div>
        </div>

        <!-- Список запчастей -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">
                📋 Список запчастей (<?= $parts_count ?>)
            </div>
            <div class="card-body">
                <?php if (!empty($parts)): ?>
                    <div class="table-responsive">
                        <table class="table-enhanced">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>🔤 Название</th>
                                    <th>🏷️ Артикул</th>
                                    <th>💰 Цена</th>
                                    <th>📦 Количество</th>
                                    <th>⚡ Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($parts as $part): ?>
                                <tr>
                                    <td><strong><?= $part['id'] ?></strong></td>
                                    <td>
                                        <strong><?= htmlspecialchars($part['name']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="part-number"><?= htmlspecialchars($part['part_number']) ?></span>
                                    </td>
                                    <td class="price-cell">
                                        <?= number_format($part['price'], 2, '.', ' ') ?> руб.
                                    </td>
                                    <td class="quantity-cell">
                                        <?= $part['quantity'] ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="part_edit.php?id=<?= $part['id'] ?>" class="btn-1c-warning">
                                                ✏️
                                            </a>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="part_id" value="<?= $part['id'] ?>">
                                                <button type="submit" name="delete_part" class="btn-1c-danger" 
                                                        onclick="return confirm('❌ Вы уверены, что хотите удалить запчасть «<?= htmlspecialchars($part['name']) ?>»?')">
                                                    🗑️
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🔧</div>
                        <div>Нет запчастей в базе данных</div>
                        <div class="mt-3">
                            <p class="text-muted">Добавьте первую запчасть для использования в заказах</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'templates/footer.php'; ?>
</body>
</html>
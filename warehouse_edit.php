<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAnyRole(['admin', 'manager', 'mechanic']);

// Получаем категории
$categories = [];
$result = $conn->query("SELECT id, name FROM warehouse_categories ORDER BY name");
if ($result) {
    $categories = $result->fetch_all(MYSQLI_ASSOC);
}

// Получаем данные запчасти для редактирования
$item = null;
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("
        SELECT wi.*, wc.name as category_name 
        FROM warehouse_items wi 
        LEFT JOIN warehouse_categories wc ON wi.category_id = wc.id 
        WHERE wi.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    
    if (!$item) {
        $_SESSION['error'] = "Запчасть не найдена";
        header("Location: warehouse.php");
        exit;
    }
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name']);
    $category_id = $_POST['category_id'] ?: null;
    $part_number = trim($_POST['part_number']);
    $price = (float)$_POST['price'];
    $quantity = (int)$_POST['quantity'];
    $min_quantity = (int)$_POST['min_quantity'];
    $location = trim($_POST['location']);
    $description = trim($_POST['description']);
    
    if (empty($name)) {
        $_SESSION['error'] = "Название запчасти обязательно";
    } else {
        try {
            if ($id) {
                // Редактирование
                $stmt = $conn->prepare("
                    UPDATE warehouse_items SET 
                    name = ?, category_id = ?, part_number = ?, price = ?, 
                    quantity = ?, min_quantity = ?, location = ?, description = ?
                    WHERE id = ?
                ");
                $stmt->bind_param("sisdiiisi", $name, $category_id, $part_number, $price, 
                                $quantity, $min_quantity, $location, $description, $id);
            } else {
                // Добавление
                $sku = !empty($part_number) ? $part_number : 'ITM-' . strtoupper(uniqid());
                $stmt = $conn->prepare("
                    INSERT INTO warehouse_items 
                    (sku, name, category_id, part_number, price, quantity, min_quantity, location, description) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("ssisdiiis", $sku, $name, $category_id, $part_number, $price, 
                                $quantity, $min_quantity, $location, $description);
            }
            
            if ($stmt->execute()) {
                $_SESSION['success'] = $id ? "Запчасть обновлена" : "Запчасть добавлена";
                header("Location: warehouse.php");
                exit;
            } else {
                $_SESSION['error'] = "Ошибка при сохранении";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Ошибка базы данных: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $item ? 'Редактирование' : 'Добавление' ?> запчасти - Autoservice</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .btn-primary { background: #007bff; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <?php include 'templates/header.php'; ?>
    
    <div class="form-container">
        <h1><?= $item ? '✏️ Редактирование запчасти' : '➕ Добавление запчасти' ?></h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="form-card">
            <form method="post">
                <input type="hidden" name="id" value="<?= $item['id'] ?? '' ?>">
                
                <div class="form-group">
                    <label class="form-label">📝 Название запчасти *</label>
                    <input type="text" name="name" class="form-control" 
                           value="<?= htmlspecialchars($item['name'] ?? '') ?>" 
                           placeholder="Например: Тормозные колодки передние" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">📂 Категория</label>
                        <select name="category_id" class="form-control">
                            <option value="">-- Выберите категорию --</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" 
                                    <?= ($item['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">🔢 Артикул</label>
                        <input type="text" name="part_number" class="form-control" 
                               value="<?= htmlspecialchars($item['part_number'] ?? '') ?>" 
                               placeholder="Оригинальный артикул производителя">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">💰 Цена (руб)</label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0"
                               value="<?= $item['price'] ?? '0' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">📦 Количество в наличии</label>
                        <input type="number" name="quantity" class="form-control" min="0"
                               value="<?= $item['quantity'] ?? '0' ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">⚠️ Минимальный запас</label>
                        <input type="number" name="min_quantity" class="form-control" min="0"
                               value="<?= $item['min_quantity'] ?? '0' ?>" 
                               placeholder="0 - не отслеживать">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">📍 Местоположение на складе</label>
                        <input type="text" name="location" class="form-control" 
                               value="<?= htmlspecialchars($item['location'] ?? '') ?>" 
                               placeholder="Например: Стеллаж A-1">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">📄 Описание</label>
                    <textarea name="description" class="form-control" 
                              placeholder="Дополнительная информация о запчасти..."><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
                </div>
                
                <?php if ($item): ?>
                <div class="form-group" style="background: #f8f9fa; padding: 15px; border-radius: 6px;">
                    <label class="form-label">ℹ️ Системная информация</label>
                    <div style="font-size: 13px; color: #666;">
                        <div>Артикул системы: <strong><?= htmlspecialchars($item['sku']) ?></strong></div>
                        <div>Создано: <?= $item['created_at'] ?></div>
                        <?php if ($item['updated_at'] && $item['updated_at'] != $item['created_at']): ?>
                            <div>Обновлено: <?= $item['updated_at'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?= $item ? '💾 Сохранить изменения' : '✅ Добавить запчасть' ?>
                    </button>
                    <a href="warehouse.php" class="btn btn-secondary">← Назад к списку</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
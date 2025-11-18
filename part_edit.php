<?php
require 'includes/db.php';
session_start();
require_once 'includes/navbar.php';
require_once 'auth_check.php';
requireAnyRole(['admin', 'manager']);

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "ID запчасти не указан";
    header("Location: parts.php");
    exit;
}

$part_id = (int)$_GET['id'];

// Безопасное получение данных запчасти
$stmt = $conn->prepare("SELECT * FROM parts WHERE id = ?");
$stmt->bind_param("i", $part_id);
$stmt->execute();
$part = $stmt->get_result()->fetch_assoc();

if (!$part) {
    $_SESSION['error'] = "Запчасть не найдена";
    header("Location: parts.php");
    exit;
}

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_part'])) {
    $name = trim($_POST['name']);
    $part_number = trim($_POST['part_number']);
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];
    $category = trim($_POST['category'] ?? '');
    $supplier = trim($_POST['supplier'] ?? '');
    $min_stock = (int)($_POST['min_stock'] ?? 0);
    $location = trim($_POST['location'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    // Валидация
    if (empty($name) || empty($part_number)) {
        $error = "Название и артикул обязательны для заполнения";
    } elseif ($quantity < 0) {
        $error = "Количество не может быть отрицательным";
    } elseif ($price < 0) {
        $error = "Цена не может быть отрицательной";
    } else {
        $stmt = $conn->prepare("UPDATE parts SET name = ?, part_number = ?, quantity = ?, price = ?, category = ?, supplier = ?, min_stock = ?, location = ?, notes = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssidssiisi", $name, $part_number, $quantity, $price, $category, $supplier, $min_stock, $location, $notes, $part_id);
        
        if ($stmt->execute()) {
            $success = "✅ Запчасть успешно обновлена!";
            // Обновляем данные для отображения
            $stmt = $conn->prepare("SELECT * FROM parts WHERE id = ?");
            $stmt->bind_param("i", $part_id);
            $stmt->execute();
            $part = $stmt->get_result()->fetch_assoc();
        } else {
            $error = "❌ Ошибка при обновлении: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование запчасти №<?= $part_id ?> - Autoservice</title>
    <link href="assets/css/orders.css" rel="stylesheet">
    <style>
        .parts-edit-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .part-info-sidebar {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            height: fit-content;
        }
        
        .part-header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #3498db;
        }
        
        .part-icon {
            font-size: 48px;
            margin-bottom: 10px;
            display: block;
        }
        
        .part-id {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .part-name {
            font-size: 1.1rem;
            color: #7f8c8d;
            margin-bottom: 15px;
        }
        
        .info-block {
            margin-bottom: 20px;
        }
        
        .info-label {
            font-size: 0.8rem;
            color: #95a5a6;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 1rem;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .stock-indicator {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .stock-low { background: #fff3cd; color: #856404; }
        .stock-normal { background: #d1ecf1; color: #0c5460; }
        .stock-out { background: #f8d7da; color: #721c24; }
        
        .form-main-content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-icon {
            font-size: 1.5rem;
            margin-right: 10px;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-full-width {
            grid-column: 1 / -1;
        }
        
        .cost-calculation {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        
        .cost-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .cost-total {
            font-weight: 700;
            font-size: 1.1rem;
            color: #2c3e50;
            border-top: 1px solid #ddd;
            padding-top: 8px;
            margin-top: 8px;
        }
        
        .last-updated {
            font-size: 0.8rem;
            color: #95a5a6;
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #ecf0f1;
        }
    </style>
</head>
<body>
    <?php include 'templates/header.php'; ?>
    
    <div class="orders-container">
        <div class="container-header">
            <h1 class="page-title">
                <span class="page-title-icon">🔧</span>
                Редактирование запчасти
            </h1>
            <a href="parts.php" class="btn-1c-outline">← Назад к запчастям</a>
        </div>

        <div class="parts-edit-container">
            <!-- Левая колонка - информация о запчасти -->
            <div class="part-info-sidebar">
                <div class="part-header">
                    <span class="part-icon">📦</span>
                    <div class="part-id">№<?= $part_id ?></div>
                    <div class="part-name"><?= htmlspecialchars($part['name']) ?></div>
                </div>
                
                <div class="info-block">
                    <div class="info-label">Статус запаса</div>
                    <div class="info-value">
                        <?php
                        $min_stock = $part['min_stock'] ?? 0;
                        $quantity = $part['quantity'];
                        
                        if ($quantity == 0) {
                            echo '<span class="stock-indicator stock-out">❌ Нет в наличии</span>';
                        } elseif ($quantity <= $min_stock) {
                            echo '<span class="stock-indicator stock-low">⚠️ Низкий запас</span>';
                        } else {
                            echo '<span class="stock-indicator stock-normal">✅ В наличии</span>';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="info-block">
                    <div class="info-label">Текущий остаток</div>
                    <div class="info-value"><?= $part['quantity'] ?> шт.</div>
                </div>
                
                <div class="info-block">
                    <div class="info-label">Минимальный запас</div>
                    <div class="info-value"><?= $part['min_stock'] ?? 0 ?> шт.</div>
                </div>
                
                <div class="info-block">
                    <div class="info-label">Цена за единицу</div>
                    <div class="info-value"><?= number_format($part['price'], 2) ?> руб.</div>
                </div>
                
                <div class="info-block">
                    <div class="info-label">Общая стоимость</div>
                    <div class="info-value" style="font-size: 1.2rem; color: #27ae60; font-weight: 700;">
                        <?= number_format($part['price'] * $part['quantity'], 2) ?> руб.
                    </div>
                </div>
                
                <?php if (!empty($part['category'])): ?>
                <div class="info-block">
                    <div class="info-label">Категория</div>
                    <div class="info-value"><?= htmlspecialchars($part['category']) ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($part['location'])): ?>
                <div class="info-block">
                    <div class="info-label">Место хранения</div>
                    <div class="info-value"><?= htmlspecialchars($part['location']) ?></div>
                </div>
                <?php endif; ?>
                
                <div class="last-updated">
                    <?php if (!empty($part['updated_at'])): ?>
                        Обновлено: <?= date('d.m.Y в H:i', strtotime($part['updated_at'])) ?>
                    <?php else: ?>
                        Создано: <?= date('d.m.Y', strtotime($part['created_at'] ?? 'now')) ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Правая колонка - форма редактирования -->
            <div class="form-main-content">
                <?php if ($success): ?>
                    <div class="alert-enhanced alert-success">
                        <?= $success ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert-enhanced alert-danger">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="post" id="partForm" onsubmit="return validateForm()">
                    <!-- Основная информация -->
                    <div class="form-section">
                        <div class="section-header">
                            <span class="section-icon">📋</span>
                            <h3 class="section-title">Основная информация</h3>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group form-full-width">
                                <label class="form-label">Название запчасти *</label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?= htmlspecialchars($part['name']) ?>" 
                                       required placeholder="Например: Масляный фильтр">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Артикул *</label>
                                <input type="text" name="part_number" class="form-control" 
                                       value="<?= htmlspecialchars($part['part_number']) ?>" 
                                       required placeholder="Уникальный номер">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Категория</label>
                                <input type="text" name="category" class="form-control" 
                                       value="<?= htmlspecialchars($part['category'] ?? '') ?>" 
                                       placeholder="Например: Фильтры">
                            </div>
                        </div>
                    </div>

                    <!-- Складская информация -->
                    <div class="form-section">
                        <div class="section-header">
                            <span class="section-icon">🏪</span>
                            <h3 class="section-title">Складская информация</h3>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Количество на складе *</label>
                                <input type="number" name="quantity" class="form-control" 
                                       value="<?= $part['quantity'] ?>" min="0" required
                                       onchange="calculateTotalCost()">
                                <div class="form-text">Текущий остаток</div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Минимальный запас</label>
                                <input type="number" name="min_stock" class="form-control" 
                                       value="<?= $part['min_stock'] ?? 0 ?>" min="0">
                                <div class="form-text">Триггер для заказа</div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Место хранения</label>
                                <input type="text" name="location" class="form-control" 
                                       value="<?= htmlspecialchars($part['location'] ?? '') ?>" 
                                       placeholder="Например: Стеллаж А-1">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Поставщик</label>
                                <input type="text" name="supplier" class="form-control" 
                                       value="<?= htmlspecialchars($part['supplier'] ?? '') ?>" 
                                       placeholder="Название поставщика">
                            </div>
                        </div>
                    </div>

                    <!-- Финансовая информация -->
                    <div class="form-section">
                        <div class="section-header">
                            <span class="section-icon">💰</span>
                            <h3 class="section-title">Финансовая информация</h3>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Цена за единицу (руб) *</label>
                                <input type="number" name="price" class="form-control" 
                                       value="<?= number_format($part['price'], 2, '.', '') ?>" 
                                       step="0.01" min="0" required
                                       onchange="calculateTotalCost()">
                                <div class="form-text">Себестоимость</div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Расчет стоимости</label>
                                <div class="cost-calculation">
                                    <div class="cost-item">
                                        <span>Количество:</span>
                                        <span id="displayQuantity"><?= $part['quantity'] ?> шт.</span>
                                    </div>
                                    <div class="cost-item">
                                        <span>Цена за шт.:</span>
                                        <span id="displayPrice"><?= number_format($part['price'], 2) ?> руб.</span>
                                    </div>
                                    <div class="cost-item cost-total">
                                        <span>Общая стоимость:</span>
                                        <span id="displayTotal"><?= number_format($part['price'] * $part['quantity'], 2) ?> руб.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Дополнительная информация -->
                    <div class="form-section">
                        <div class="section-header">
                            <span class="section-icon">📝</span>
                            <h3 class="section-title">Дополнительная информация</h3>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Примечания</label>
                            <textarea name="notes" class="form-control textarea-large" 
                                      rows="4" placeholder="Дополнительная информация о запчасти..."><?= htmlspecialchars($part['notes'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_part" class="btn-1c-primary btn-large">
                            💾 Сохранить изменения
                        </button>
                        <a href="parts.php" class="btn-1c-outline">Отмена</a>
                        <button type="button" class="btn-1c-outline" onclick="resetForm()">
                            🔄 Сбросить
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function validateForm() {
        const quantity = document.querySelector('input[name="quantity"]');
        const price = document.querySelector('input[name="price"]');
        
        if (quantity.value < 0) {
            alert('Количество не может быть отрицательным');
            quantity.focus();
            return false;
        }
        
        if (price.value < 0) {
            alert('Цена не может быть отрицательной');
            price.focus();
            return false;
        }
        
        return true;
    }
    
    function resetForm() {
        if (confirm('Вы уверены, что хотите сбросить все изменения?')) {
            document.getElementById('partForm').reset();
            calculateTotalCost();
        }
    }
    
    function calculateTotalCost() {
        const quantity = parseFloat(document.querySelector('input[name="quantity"]').value) || 0;
        const price = parseFloat(document.querySelector('input[name="price"]').value) || 0;
        const total = quantity * price;
        
        document.getElementById('displayQuantity').textContent = quantity + ' шт.';
        document.getElementById('displayPrice').textContent = price.toFixed(2) + ' руб.';
        document.getElementById('displayTotal').textContent = total.toFixed(2) + ' руб.';
    }
    
    // Инициализация при загрузке
    document.addEventListener('DOMContentLoaded', function() {
        const quantityInput = document.querySelector('input[name="quantity"]');
        const priceInput = document.querySelector('input[name="price"]');
        
        quantityInput.addEventListener('input', calculateTotalCost);
        priceInput.addEventListener('input', calculateTotalCost);
    });
    </script>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
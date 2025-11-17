<?php
// unified_booking.php - В СТИЛЕ 1С
session_start();
require 'includes/db.php';
require 'includes/functions.php';
require_once 'auth_check.php';
requireAuth();

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $car_info = trim($_POST['car_info'] ?? '');
    $service_type = trim($_POST['service_type'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $comments = trim($_POST['comments'] ?? '');
    
    if (!empty($name) && !empty($phone) && !empty($date) && !empty($time)) {
        try {
            // Простая вставка в таблицу bookings
            $stmt = $pdo->prepare("
                INSERT INTO bookings (name, phone, car_info, service_type, date, time, comments, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $name,
                $phone,
                $car_info,
                $service_type,
                $date,
                $time,
                $comments
            ]);
            
            $_SESSION['success_message'] = "Запись успешно создана!";
            header('Location: booking_success.php');
            exit;
            
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Ошибка при сохранении записи: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = "Заполните все обязательные поля";
    }
}

// Минимальная дата - сегодня
$min_date = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Предварительная запись - Автосервис</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>
    
            <div class="content-container">
            <!-- Компактный заголовок в стиле 1С -->
            <div class="header-compact">
                <h1 class="page-title-compact">Предварительная запись на обслуживание</h1>
                <div class="header-actions-compact">
                    <a href="orders.php" class="action-btn-compact">
                        <span class="action-icon">←</span>
                        <span class="action-label">К заказам</span>
                    </a>
                    <a href="clients.php" class="action-btn-compact">
                        <span class="action-icon">👥</span>
                        <span class="action-label">Клиенты</span>
                    </a>
                </div>
            </div>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="card-1c" style="background: #f8d7da; border-color: #f5c6cb;">
                    <div class="card-header-1c" style="background: #f8d7da; color: #721c24;">
                        Ошибка
                    </div>
                    <div style="padding: 15px; color: #721c24;">
                        <?= htmlspecialchars($_SESSION['error_message']) ?>
                        <?php unset($_SESSION['error_message']); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="card-1c" style="background: #d4edda; border-color: #c3e6cb;">
                    <div class="card-header-1c" style="background: #d4edda; color: #155724;">
                        Успешно
                    </div>
                    <div style="padding: 15px; color: #155724;">
                        <?= htmlspecialchars($_SESSION['success_message']) ?>
                        <?php unset($_SESSION['success_message']); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Основная форма в стиле 1С -->
            <div class="card-1c">
                <div class="card-header-1c">
                    Данные для записи
                </div>
                <form method="POST" style="padding: 20px;">
                    <div class="row-1c">
                        <!-- Левая колонка -->
                        <div>
                            <div class="form-group">
                                <label for="name">ФИО клиента *</label>
                                <input type="text" id="name" name="name" required 
                                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                       style="width: 100%; padding: 8px; border: 1px solid #d4c49e; background: #fffef5;">
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Телефон *</label>
                                <input type="tel" id="phone" name="phone" required 
                                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                       style="width: 100%; padding: 8px; border: 1px solid #d4c49e; background: #fffef5;">
                            </div>
                            
                            <div class="form-group">
                                <label for="car_info">Автомобиль</label>
                                <input type="text" id="car_info" name="car_info" 
                                       value="<?= htmlspecialchars($_POST['car_info'] ?? '') ?>"
                                       placeholder="Марка, модель, гос. номер"
                                       style="width: 100%; padding: 8px; border: 1px solid #d4c49e; background: #fffef5;">
                            </div>
                        </div>
                        
                        <!-- Правая колонка -->
                        <div>
                            <div class="form-group">
                                <label for="service_type">Тип услуги</label>
                                <select id="service_type" name="service_type" 
                                        style="width: 100%; padding: 8px; border: 1px solid #d4c49e; background: #fffef5;">
                                    <option value="">-- Выберите услугу --</option>
                                    <option value="Техническое обслуживание" <?= (($_POST['service_type'] ?? '') == 'Техническое обслуживание') ? 'selected' : '' ?>>Техническое обслуживание</option>
                                    <option value="Диагностика" <?= (($_POST['service_type'] ?? '') == 'Диагностика') ? 'selected' : '' ?>>Диагностика</option>
                                    <option value="Ремонт двигателя" <?= (($_POST['service_type'] ?? '') == 'Ремонт двигателя') ? 'selected' : '' ?>>Ремонт двигателя</option>
                                    <option value="Ремонт ходовой" <?= (($_POST['service_type'] ?? '') == 'Ремонт ходовой') ? 'selected' : '' ?>>Ремонт ходовой</option>
                                    <option value="Шиномонтаж" <?= (($_POST['service_type'] ?? '') == 'Шиномонтаж') ? 'selected' : '' ?>>Шиномонтаж</option>
                                    <option value="Кузовной ремонт" <?= (($_POST['service_type'] ?? '') == 'Кузовной ремонт') ? 'selected' : '' ?>>Кузовной ремонт</option>
                                    <option value="Электрика" <?= (($_POST['service_type'] ?? '') == 'Электрика') ? 'selected' : '' ?>>Электрика</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="date">Дата *</label>
                                <input type="date" id="date" name="date" required 
                                       min="<?= $min_date ?>" 
                                       value="<?= htmlspecialchars($_POST['date'] ?? '') ?>"
                                       style="width: 100%; padding: 8px; border: 1px solid #d4c49e; background: #fffef5;">
                            </div>
                            
                            <div class="form-group">
                                <label for="time">Время *</label>
                                <input type="time" id="time" name="time" required 
                                       min="09:00" max="18:00" 
                                       value="<?= htmlspecialchars($_POST['time'] ?? '') ?>"
                                       style="width: 100%; padding: 8px; border: 1px solid #d4c49e; background: #fffef5;">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Полноширинное поле -->
                    <div class="form-group">
                        <label for="comments">Комментарий к заявке</label>
                        <textarea id="comments" name="comments" rows="3" 
                                  placeholder="Описание проблемы, пожелания клиента..."
                                  style="width: 100%; padding: 8px; border: 1px solid #d4c49e; background: #fffef5; resize: vertical;"><?= htmlspecialchars($_POST['comments'] ?? '') ?></textarea>
                    </div>
                    
                    <!-- Кнопки действий -->
                    <div style="display: flex; gap: 10px; margin-top: 20px; padding-top: 15px; border-top: 1px solid #e6d8a8;">
                        <button type="submit" class="action-btn-compact primary" style="border: none;">
                            <span class="action-icon">✓</span>
                            <span class="action-label">Сохранить запись</span>
                        </button>
                        <button type="reset" class="action-btn-compact" style="border: none;">
                            <span class="action-icon">↶</span>
                            <span class="action-label">Очистить</span>
                        </button>
                        <a href="index.php" class="action-btn-compact" style="text-decoration: none;">
                            <span class="action-icon">←</span>
                            <span class="action-label">На главную</span>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Информационная карточка -->
            <div class="card-1c compact-card">
                <div class="card-header-1c compact-header">
                    <h5>Информация</h5>
                </div>
                <div class="compact-content">
                    <div style="display: flex; align-items: center; gap: 10px; padding: 10px; border-bottom: 1px solid #f5f0d8;">
                        <span style="color: #8b6914;">ℹ️</span>
                        <span style="color: #5c4a00; font-size: 0.9rem;">
                            Записи сохраняются в систему и будут отображаться в общем списке заказов
                        </span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; padding: 10px;">
                        <span style="color: #8b6914;">⏰</span>
                        <span style="color: #5c4a00; font-size: 0.9rem;">
                            Время работы: с 09:00 до 18:00, без выходных
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'templates/footer.php'; ?>
</body>
</html>
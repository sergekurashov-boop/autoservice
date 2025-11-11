<?php
// unified_booking.php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';



// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Обработка данных и сохранение в БД
    process_unified_booking($_POST);
    header('Location: booking_success.php?complex=1');
    exit;
}

// Получение данных для выпадающих списков
$clients = get_all_clients();
$cars = get_all_cars();
$services = get_all_services();
$mechanics = get_all_mechanics();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Комплексная запись в автосервис</title>
    <link rel="stylesheet" href="assets/css/unified-booking.css">
    <script src="assets/js/unified-booking.js" defer></script>
</head>
<body>
    <?php include 'templates/header.php'; ?>
    
    <div class="container">
        <h1>Комплексная запись</h1>
        
        <form id="unified-booking-form" method="POST">
            <!-- Секция 1: Клиент -->
            <fieldset class="form-section">
                <legend>Данные клиента</legend>
                
                <div class="form-group">
                    <label>Выберите клиента:</label>
                    <select id="client-select" name="client_id" class="searchable">
                        <option value="">-- Выберите клиента --</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= $client['id'] ?>">
                                <?= htmlspecialchars($client['last_name']) ?> 
                                <?= htmlspecialchars($client['first_name']) ?> 
                                (<?= htmlspecialchars($client['phone']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="new-client-btn" class="btn-secondary">+ Новый клиент</button>
                </div>
                
                <div id="new-client-fields" style="display:none;">
                    <div class="form-group">
                        <input type="text" name="new_last_name" placeholder="Фамилия">
                    </div>
                    <div class="form-group">
                        <input type="text" name="new_first_name" placeholder="Имя">
                    </div>
                    <div class="form-group">
                        <input type="tel" name="new_phone" placeholder="Телефон">
                    </div>
                    <div class="form-group">
                        <input type="email" name="new_email" placeholder="Email">
                    </div>
                </div>
            </fieldset>
            
            <!-- Секция 2: Автомобиль -->
            <fieldset class="form-section">
                <legend>Автомобиль</legend>
                
                <div class="form-group">
                    <label>Выберите автомобиль:</label>
                    <select id="car-select" name="car_id" class="searchable">
                        <option value="">-- Выберите автомобиль --</option>
                        <?php foreach ($cars as $car): ?>
                            <option value="<?= $car['id'] ?>">
                                <?= htmlspecialchars($car['make']) ?> 
                                <?= htmlspecialchars($car['model']) ?> 
                                (<?= htmlspecialchars($car['license_plate']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="new-car-btn" class="btn-secondary">+ Новый автомобиль</button>
                </div>
                
                <div id="new-car-fields" style="display:none;">
                    <div class="form-group">
                        <input type="text" name="new_make" placeholder="Марка">
                    </div>
                    <div class="form-group">
                        <input type="text" name="new_model" placeholder="Модель">
                    </div>
                    <div class="form-group">
                        <input type="text" name="new_year" placeholder="Год выпуска">
                    </div>
                    <div class="form-group">
                        <input type="text" name="new_vin" placeholder="VIN-код">
                    </div>
                    <div class="form-group">
                        <input type="text" name="new_license_plate" placeholder="Гос. номер">
                    </div>
                    <div class="form-group">
                        <input type="number" name="new_mileage" placeholder="Пробег (км)">
                    </div>
                </div>
            </fieldset>
            
            <!-- Секция 3: Услуги -->
            <fieldset class="form-section">
                <legend>Услуги</legend>
                
                <div class="form-group">
                    <label>Выберите услуги:</label>
                    <div id="services-container">
                        <?php foreach ($services as $service): ?>
                            <div class="service-item">
                                <input type="checkbox" name="services[]" 
                                       value="<?= $service['id'] ?>" 
                                       data-price="<?= $service['price'] ?>">
                                <label>
                                    <?= htmlspecialchars($service['name']) ?> 
                                    (<?= number_format($service['price'], 0, ',', ' ') ?> руб.)
                                </label>
                                <input type="number" name="service_quantity[<?= $service['id'] ?>]" 
                                       min="1" value="1" class="quantity-input" style="width: 60px;">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Общая стоимость:</label>
                    <span id="total-price">0</span> руб.
                </div>
            </fieldset>
            
            <!-- Секция 4: Причина обращения -->
            <fieldset class="form-section">
                <legend>Причина обращения</legend>
                
                <div class="form-group">
                    <textarea name="problem_description" 
                              placeholder="Подробно опишите проблему..." 
                              rows="5"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Приложить фото:</label>
                    <input type="file" name="problem_photos[]" multiple accept="image/*">
                </div>
            </fieldset>
            
            <!-- Секция 5: Дополнительно -->
            <fieldset class="form-section">
                <legend>Дополнительная информация</legend>
                
                <div class="form-group">
                    <label>Выберите механика:</label>
                    <select name="mechanic_id" class="searchable">
                        <option value="">-- Не назначен --</option>
                        <?php foreach ($mechanics as $mechanic): ?>
                            <option value="<?= $mechanic['id'] ?>">
                                <?= htmlspecialchars($mechanic['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Желаемая дата:</label>
                    <input type="date" name="desired_date" min="<?= date('Y-m-d') ?>">
                </div>
                
                <div class="form-group">
                    <label>Желаемое время:</label>
                    <input type="time" name="desired_time" min="09:00" max="18:00">
                </div>
                
                <div class="form-group">
                    <label>Приоритет:</label>
                    <select name="priority">
                        <option value="normal">Обычный</option>
                        <option value="high">Высокий</option>
                        <option value="urgent">Срочный</option>
                    </select>
                </div>
            </fieldset>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">Сохранить комплексную запись</button>
                <button type="button" id="export-btn" class="btn-secondary">Экспорт в PDF</button>
                <button type="button" id="print-btn" class="btn-secondary">Печать</button>
            </div>
        </form>
    </div>
    
    <?php include 'templates/footer.php'; ?>
</body>
</html>
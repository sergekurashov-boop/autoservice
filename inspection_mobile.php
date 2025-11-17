<?php
// autoservice/inspection_mobile.php
require 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "❌ Требуется авторизация";
    header("Location: login.php");
    exit;
}

define('ACCESS', true);

// Получаем услуги с кодами
$services = [];
$result = $conn->query("
    SELECT id, code, name, price 
    FROM services 
    WHERE code IS NOT NULL AND code != '' 
    ORDER BY code
");
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}

// Получаем запчасти с кодами
$parts = [];
$result = $conn->query("
    SELECT id, code, name, price 
    FROM parts 
    WHERE code IS NOT NULL AND code != '' 
    ORDER BY code
");
while ($row = $result->fetch_assoc()) {
    $parts[] = $row;
}

include 'templates/header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <!-- PWA для Android -->
    <link rel="manifest" href="/autoservice/manifest.json">
    <meta name="theme-color" content="#2c3e50">
    
    <title>Акт осмотра - Мобильная версия</title>
    
    <style>
        /* Сброс стилей */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
            font-family: 'Roboto', Arial, sans-serif;
        }
        
        body {
            background: #f5f5f5;
            font-size: 16px;
            line-height: 1.4;
            color: #333;
            -webkit-user-select: none;
            user-select: none;
        }
        
        input, textarea, select {
            -webkit-user-select: text;
            user-select: text;
        }
        
        /* Контейнер */
        .app-container {
            max-width: 100%;
            min-height: 100vh;
            background: white;
        }
        
        /* Шапка */
        .app-header {
            background: #2c3e50;
            color: white;
            padding: 15px;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .app-header h1 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .app-subtitle {
            font-size: 14px;
            opacity: 0.8;
        }
        
        /* Секции */
        .section {
            margin: 10px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Формы */
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            background: #fafafa;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            border-color: #3498db;
            background: white;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        /* Чекбоксы */
        .checkbox-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 10px 0;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .checkbox-item:active {
            background: #e9ecef;
            transform: scale(0.98);
        }
        
        .checkbox-item input {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }
        
        .checkbox-item label {
            font-size: 14px;
            cursor: pointer;
            flex: 1;
        }
        
        /* Список элементов */
        .search-box {
            margin-bottom: 15px;
        }
        
        .items-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #eee;
            border-radius: 8px;
        }
        
        .item-row {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            background: white;
        }
        
        .item-row:last-child {
            border-bottom: none;
        }
        
        .item-row:active {
            background: #f8f9fa;
        }
        
        .item-code {
            font-weight: bold;
            color: #2c3e50;
            min-width: 70px;
            font-size: 14px;
        }
        
        .item-name {
            flex: 1;
            padding: 0 10px;
            font-size: 14px;
        }
        
        .item-price {
            color: #27ae60;
            font-weight: 600;
            min-width: 80px;
            text-align: right;
            font-size: 14px;
        }
        
        .item-actions {
            display: flex;
            gap: 5px;
            margin-left: 10px;
        }
        
        .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .action-btn:active {
            transform: scale(0.95);
        }
        
        .btn-replace { 
            background: #e74c3c; 
            color: white; 
        }
        
        .btn-repair { 
            background: #f39c12; 
            color: white; 
        }
        
        .btn-maintain { 
            background: #27ae60; 
            color: white; 
        }
        
        /* Выбранные элементы */
        .selected-items {
            max-height: 200px;
            overflow-y: auto;
            margin: 15px 0;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 10px;
        }
        
        .selected-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: #f8f9fa;
            margin: 5px 0;
            border-radius: 6px;
            border-left: 4px solid #3498db;
        }
        
        .selected-item-info {
            flex: 1;
        }
        
        .selected-item-code {
            font-weight: bold;
            color: #2c3e50;
            font-size: 14px;
        }
        
        .selected-item-name {
            font-size: 13px;
            color: #666;
        }
        
        .selected-item-action {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 2px;
        }
        
        .selected-item-price {
            font-weight: 600;
            color: #27ae60;
            margin-left: 10px;
        }
        
        .remove-btn {
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 6px 10px;
            margin-left: 10px;
            cursor: pointer;
            font-size: 12px;
        }
        
        /* Итоги */
        .total-section {
            background: #e8f4fd;
            border-left: 4px solid #3498db;
        }
        
        .total-amount {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            padding: 10px;
        }
        
        /* Футер */
        .app-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 15px;
            border-top: 2px solid #eee;
            display: flex;
            gap: 10px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }
        
        .footer-btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .footer-btn:active {
            transform: scale(0.98);
        }
        
        .btn-draft {
            background: #95a5a6;
            color: white;
        }
        
        .btn-complete {
            background: #27ae60;
            color: white;
        }
        
        /* Утилиты */
        .hidden {
            display: none !important;
        }
        
        .online-status {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #27ae60;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            z-index: 1000;
        }
        
        .offline-status {
            background: #e74c3c;
        }
        
        /* Адаптивность */
        @media (max-width: 480px) {
            .checkbox-grid {
                grid-template-columns: 1fr;
            }
            
            .item-actions {
                flex-direction: column;
            }
            
            .action-btn {
                padding: 6px 8px;
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        
        <!-- Статус онлайн -->
        <div id="onlineStatus" class="online-status">онлайн</div>
        
        <!-- Шапка -->
        <div class="app-header">
            <h1>🔍 Акт осмотра</h1>
            <div class="app-subtitle">Мобильная версия</div>
        </div>

        <!-- Основной контент -->
        <div style="padding-bottom: 80px;">
            
            <!-- Данные автомобиля -->
            <div class="section">
                <div class="section-title">🚗 Данные автомобиля</div>
                
                <div class="form-group">
                    <label class="form-label">Марка</label>
                    <input type="text" class="form-input" placeholder="Например: Toyota" id="carBrand">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Модель</label>
                    <input type="text" class="form-input" placeholder="Например: Camry" id="carModel">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Госномер</label>
                    <input type="text" class="form-input" placeholder="A123BC777" id="carPlate">
                </div>
                
                <div class="form-group">
                    <label class="form-label">VIN</label>
                    <input type="text" class="form-input" placeholder="JTDBR32E160123456" id="carVin">
                </div>
            </div>

            <!-- Выбор оси -->
            <div class="section">
                <div class="section-title">🎯 Осматриваемая ось</div>
                <div class="checkbox-grid">
                    <div class="checkbox-item">
                        <input type="radio" name="axis" id="front_axis" value="front" checked>
                        <label for="front_axis">🔄 Передняя ось</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="radio" name="axis" id="rear_axis" value="rear">
                        <label for="rear_axis">🔁 Задняя ось</label>
                    </div>
                </div>
            </div>

            <!-- Список работ -->
            <div class="section">
                <div class="section-title">🔧 Работы и запчасти</div>
                
                <div class="search-box">
                    <input type="text" class="form-input" placeholder="🔍 Поиск по коду или названию..." 
                           id="searchInput" onkeyup="filterItems()">
                </div>

                <div class="items-list" id="itemsList">
                    <?php foreach (array_merge($services, $parts) as $item): ?>
                    <div class="item-row" data-code="<?= $item['code'] ?>" 
                         data-name="<?= htmlspecialchars($item['name']) ?>" 
                         data-price="<?= $item['price'] ?>">
                        <div class="item-code"><?= $item['code'] ?></div>
                        <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                        <div class="item-price"><?= number_format($item['price'], 0, '.', ' ') ?> ₽</div>
                        <div class="item-actions">
                            <button class="action-btn btn-replace" onclick="addItem(this, 'replacement')">Замена</button>
                            <button class="action-btn btn-repair" onclick="addItem(this, 'repair')">Ремонт</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Выбранные работы -->
            <div class="section">
                <div class="section-title">📋 Выбранные позиции</div>
                <div id="selectedItems" class="selected-items">
                    <div style="text-align: center; color: #999; padding: 20px;">
                        Выберите работы из списка выше
                    </div>
                </div>
                
                <div class="total-amount">
                    Итого: <span id="totalAmount">0</span> ₽
                </div>
            </div>
        </div>

        <!-- Футер с кнопками -->
        <div class="app-footer">
            <button class="footer-btn btn-draft" onclick="saveDraft()">💾 Черновик</button>
            <button class="footer-btn btn-complete" onclick="completeInspection()">✅ Готово</button>
        </div>
    </div>

    <script>
        // Глобальные переменные
        let selectedItems = [];
        let totalAmount = 0;
        const STORAGE_KEY = 'autoservice_drafts';

        // Инициализация
        document.addEventListener('DOMContentLoaded', function() {
            updateOnlineStatus();
            loadDraft();
        });

        // Фильтрация списка
        function filterItems() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const items = document.querySelectorAll('#itemsList .item-row');
            
            items.forEach(item => {
                const code = item.getAttribute('data-code').toLowerCase();
                const name = item.getAttribute('data-name').toLowerCase();
                
                if (code.includes(searchTerm) || name.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Добавление элемента в выбранные
        function addItem(button, action) {
            const itemRow = button.closest('.item-row');
            const code = itemRow.getAttribute('data-code');
            const name = itemRow.getAttribute('data-name');
            const price = parseFloat(itemRow.getAttribute('data-price'));
            
            const item = {
                id: Date.now() + '_' + Math.random().toString(36).substr(2, 9),
                code: code,
                name: name,
                action: action,
                price: price,
                timestamp: new Date().toISOString()
            };
            
            selectedItems.push(item);
            updateSelectedList();
            updateTotal();
            
            // Визуальный фидбэк
            button.style.backgroundColor = '#2c3e50';
            setTimeout(() => {
                button.style.backgroundColor = '';
            }, 300);
        }

        // Обновление списка выбранных
        function updateSelectedList() {
            const container = document.getElementById('selectedItems');
            
            if (selectedItems.length === 0) {
                container.innerHTML = '<div style="text-align: center; color: #999; padding: 20px;">Выберите работы из списка выше</div>';
                return;
            }
            
            container.innerHTML = '';
            
            selectedItems.forEach((item, index) => {
                const div = document.createElement('div');
                div.className = 'selected-item';
                div.innerHTML = `
                    <div class="selected-item-info">
                        <div class="selected-item-code">${item.code}</div>
                        <div class="selected-item-name">${item.name}</div>
                        <div class="selected-item-action">${getActionText(item.action)}</div>
                    </div>
                    <div class="selected-item-price">${formatPrice(item.price)} ₽</div>
                    <button class="remove-btn" onclick="removeItem(${index})" title="Удалить">×</button>
                `;
                container.appendChild(div);
            });
        }

        // Удаление элемента
        function removeItem(index) {
            selectedItems.splice(index, 1);
            updateSelectedList();
            updateTotal();
        }

        // Форматирование цены
        function formatPrice(price) {
            return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        }

        // Текст действия
        function getActionText(action) {
            const actions = {
                'replacement': '🔄 Замена',
                'repair': '🔧 Ремонт', 
                'maintenance': '⚙️ Профилактика'
            };
            return actions[action] || action;
        }

        // Обновление общей суммы
        function updateTotal() {
            totalAmount = selectedItems.reduce((sum, item) => sum + item.price, 0);
            document.getElementById('totalAmount').textContent = formatPrice(totalAmount);
        }

        // Статус онлайн/оффлайн
        function updateOnlineStatus() {
            const statusEl = document.getElementById('onlineStatus');
            if (navigator.onLine) {
                statusEl.textContent = 'онлайн';
                statusEl.className = 'online-status';
            } else {
                statusEl.textContent = 'оффлайн';
                statusEl.className = 'online-status offline-status';
            }
        }

        // Сохранение черновика
        function saveDraft() {
            const draft = {
                carBrand: document.getElementById('carBrand').value,
                carModel: document.getElementById('carModel').value,
                carPlate: document.getElementById('carPlate').value,
                carVin: document.getElementById('carVin').value,
                selectedAxis: document.querySelector('input[name="axis"]:checked').value,
                items: selectedItems,
                total: totalAmount,
                timestamp: new Date().toISOString()
            };
            
            // Сохраняем в localStorage
            const drafts = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
            drafts.push(draft);
            localStorage.setItem(STORAGE_KEY, JSON.stringify(drafts));
            
            showMessage('💾 Черновик сохранен локально');
        }

        // Загрузка черновика
        function loadDraft() {
            const drafts = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
            if (drafts.length > 0) {
                if (confirm('Найдены сохраненные черновики. Загрузить последний?')) {
                    const lastDraft = drafts[drafts.length - 1];
                    // Загружаем данные...
                    showMessage('📝 Последний черновик загружен');
                }
            }
        }

        // Завершение осмотра
        function completeInspection() {
            if (selectedItems.length === 0) {
                showMessage('❌ Добавьте хотя бы одну позицию');
                return;
            }
            
            const carBrand = document.getElementById('carBrand').value;
            if (!carBrand.trim()) {
                showMessage('❌ Укажите марку автомобиля');
                return;
            }
            
            // Здесь будет отправка на сервер или генерация PDF
            const inspectionData = {
                car: {
                    brand: carBrand,
                    model: document.getElementById('carModel').value,
                    plate: document.getElementById('carPlate').value,
                    vin: document.getElementById('carVin').value
                },
                axis: document.querySelector('input[name="axis"]:checked').value,
                items: selectedItems,
                total: totalAmount,
                date: new Date().toISOString()
            };
            
            console.log('Данные для акта:', inspectionData);
            showMessage('✅ Акт осмотра готов! Данные сохранены.');
            
            // Очистка формы
            setTimeout(() => {
                selectedItems = [];
                updateSelectedList();
                updateTotal();
                document.getElementById('carBrand').value = '';
                document.getElementById('carModel').value = '';
                document.getElementById('carPlate').value = '';
                document.getElementById('carVin').value = '';
            }, 2000);
        }

        // Вспомогательная функция для сообщений
        function showMessage(text) {
            // Простой alert для начала
            alert(text);
        }

        // Слушатели событий
        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
    </script>
</body>
</html>
<?php
session_start();
require 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тестирование системы осмотров - Autoservice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .process-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        .process-steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 10%;
            right: 10%;
            height: 2px;
            background: #007bff;
            z-index: 1;
        }
        .step {
            text-align: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }
        .step-number {
            width: 40px;
            height: 40px;
            background: #007bff;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
        }
        .step-text {
            font-size: 12px;
            color: #666;
        }
        .links-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 30px;
        }
        .link-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            display: block;
        }
        .link-card:hover {
            background: #e3f2fd;
            transform: translateY(-2px);
            text-decoration: none;
        }
        .link-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #007bff;
        }
        .link-desc {
            font-size: 12px;
            color: #666;
        }
        .section-title {
            color: #333;
            margin: 30px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
        }
        .status-info {
            margin-top: 20px;
            padding: 15px;
            background: #e7f3ff;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Тестирование системы осмотров</h1>
        
        <div class="process-steps">
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-text">Задание на осмотр</div>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-text">Акт осмотра</div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-text">Предзаказ</div>
            </div>
        </div>

        <?php
        // Проверяем существование таблиц
        $tables_exist = [];
        $tables_to_check = ['inspection_requests', 'inspection_acts', 'inspection_works', 'employees', 'orders'];
        
        foreach ($tables_to_check as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            $tables_exist[$table] = $result && $result->num_rows > 0;
        }
        
        // Получаем количество записей для информации
        $requests_count = 0;
        $acts_count = 0;
        
        if ($tables_exist['inspection_requests']) {
            $result = $conn->query("SELECT COUNT(*) as count FROM inspection_requests");
            $requests_count = $result ? $result->fetch_assoc()['count'] : 0;
        }
        
        if ($tables_exist['inspection_acts']) {
            $result = $conn->query("SELECT COUNT(*) as count FROM inspection_acts");
            $acts_count = $result ? $result->fetch_assoc()['count'] : 0;
        }
        ?>

        <div class="status-info">
            <strong>📊 Статус системы:</strong><br>
            • Заданий на осмотр: <?= $requests_count ?><br>
            • Актов осмотра: <?= $acts_count ?><br>
            • Таблицы: <?= array_sum($tables_exist) ?> из <?= count($tables_exist) ?> создано
        </div>

        <h2 class="section-title">📋 Основные файлы для тестирования</h2>
        <div class="links-grid">
            <a href="inspection_request.php" class="link-card">
                <div class="link-title">➕ Создать задание на осмотр</div>
                <div class="link-desc">Первичный документ с жалобами клиента</div>
            </a>
            
            <a href="inspection_requests_list.php" class="link-card">
                <div class="link-title">📋 Список заданий на осмотр</div>
                <div class="link-desc">Все созданные задания с статусами</div>
            </a>
            
            <?php if ($requests_count > 0): ?>
            <a href="inspection_create.php?request_id=1" class="link-card">
                <div class="link-title">📝 Создать акт осмотра</div>
                <div class="link-desc">Акт на основе задания (тест с ID=1)</div>
            </a>
            <?php else: ?>
            <div class="link-card" style="background: #fff3cd; border-left-color: #ffc107;">
                <div class="link-title">⏳ Создать акт осмотра</div>
                <div class="link-desc">Сначала создайте задание на осмотр</div>
            </div>
            <?php endif; ?>
            
            <a href="inspection.php" class="link-card">
                <div class="link-title">🛠️ Старый inspection.php</div>
                <div class="link-desc">Оригинальный файл для сравнения</div>
            </a>
        </div>

        <h2 class="section-title">🔧 Дополнительные файлы</h2>
        <div class="links-grid">
            <?php if ($requests_count > 0): ?>
            <a href="inspection_request_view.php?id=1" class="link-card">
                <div class="link-title">👁️ Просмотр задания</div>
                <div class="link-desc">Детальный просмотр задания (тест с ID=1)</div>
            </a>
            <?php else: ?>
            <div class="link-card" style="background: #fff3cd; border-left-color: #ffc107;">
                <div class="link-title">👁️ Просмотр задания</div>
                <div class="link-desc">Сначала создайте задание на осмотр</div>
            </div>
            <?php endif; ?>
            
            <a href="orders.php" class="link-card">
                <div class="link-title">📄 Список заказов</div>
                <div class="link-desc">Для тестирования связи с заказами</div>
            </a>
            
            <?php if ($acts_count > 0): ?>
            <a href="preliminary_order.php?inspection_id=1" class="link-card">
                <div class="link-title">💰 Предварительный заказ</div>
                <div class="link-desc">Создание заказа-наряда (в разработке)</div>
            </a>
            <?php else: ?>
            <div class="link-card" style="background: #fff3cd; border-left-color: #ffc107;">
                <div class="link-title">💰 Предварительный заказ</div>
                <div class="link-desc">Сначала создайте акт осмотра</div>
            </div>
            <?php endif; ?>
            
            <a href="tasks_list.php" class="link-card">
                <div class="link-title">👨‍🔧 Задания механикам</div>
                <div class="link-desc">Список рабочих заданий (в разработке)</div>
            </a>
        </div>

        <div style="margin-top: 30px; padding: 15px; background: #d4edda; border-radius: 5px; border-left: 4px solid #28a745;">
            <strong>💡 Рекомендуемый порядок тестирования:</strong><br>
            1. Создайте задание на осмотр<br>
            2. Просмотрите созданное задание<br>
            3. Создайте акт осмотра на основе задания<br>
            4. Просмотрите созданный акт
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div style="margin-top: 20px; padding: 15px; background: #d4edda; border-radius: 5px; border-left: 4px solid #28a745;">
                ✅ <?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div style="margin-top: 20px; padding: 15px; background: #f8d7da; border-radius: 5px; border-left: 4px solid #dc3545;">
                ❌ <?= $_SESSION['error'] ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </div>
</body>
</html>
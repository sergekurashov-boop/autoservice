<?php
define('ACCESS', true);

require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'auth_check.php';

// Получаем ID заказа
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    die("❌ Неверный ID заказа");
}

// Получаем данные заказа
try {
    $sql = "SELECT t.*, c.name as client_name, c.phone,
                   car.make, car.model, car.year, car.license_plate, car.vin as car_vin
            FROM tire_orders t
            LEFT JOIN clients c ON t.client_id = c.id
            LEFT JOIN cars car ON t.car_id = car.id
            WHERE t.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        die("❌ Заказ не найден");
    }
    
    // Декодируем данные по шинам
    $tire_data = !empty($order['tire_data']) ? json_decode($order['tire_data'], true) : [];
    
    // Получаем данные исполнителя (текущий пользователь)
    $executor_sql = "SELECT full_name FROM users WHERE id = ?";
    $executor_stmt = $pdo->prepare($executor_sql);
    $executor_stmt->execute([$_SESSION['user_id']]);
    $executor = $executor_stmt->fetch();
    
    // Получаем реквизиты компании из таблицы company_details
    $company_sql = "SELECT * FROM company_details ORDER BY id DESC LIMIT 1";
    $company_stmt = $pdo->query($company_sql);
    $company = $company_stmt->fetch();
    
} catch (PDOException $e) {
    die("❌ Ошибка базы данных: " . $e->getMessage());
}

// Устанавливаем заголовки для печати
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заказ-наряд шиномонтажа #<?= $order_id ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 10px;
            background: white;
            color: black;
            font-size: 12px;
            line-height: 1.2;
        }
        .print-container {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #000;
            padding: 15px;
        }
        .company-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .company-header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .company-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 10px;
        }
        .document-header {
            text-align: center;
            margin: 15px 0;
            padding: 8px;
            background: #f5f5f5;
            border: 1px solid #ccc;
        }
        .document-header h2 {
            margin: 0;
            font-size: 14px;
            font-weight: bold;
        }
        .section {
            margin-bottom: 10px;
            border: 1px solid #000;
            padding: 8px;
        }
        .section-title {
            background: #e9e9e9;
            padding: 4px 8px;
            margin: -8px -8px 6px -8px;
            border-bottom: 1px solid #ccc;
            font-weight: bold;
            font-size: 10px;
        }
        .two-columns {
            display: flex;
            gap: 15px;
            margin-top: 4px;
        }
        .column {
            flex: 1;
        }
        .tire-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            margin-top: 6px;
        }
        .tire-position {
            border: 1px solid #000;
            padding: 5px;
            background: #f9f9f9;
            font-size: 10px;
        }
        .tire-title {
            font-weight: bold;
            border-bottom: 1px solid #ddd;
            padding-bottom: 2px;
            margin-bottom: 2px;
            font-size: 9px;
        }
        .services-list {
            margin-top: 6px;
        }
        .service-item {
            padding: 1px 0;
            font-size: 10px;
        }
        .signature-area {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }
        .signature {
            text-align: center;
            width: 200px;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 30px;
            padding-top: 2px;
            height: 15px;
        }
        .signature-info {
            font-size: 9px;
            margin-top: 3px;
        }
        .footer {
            margin-top: 15px;
            border-top: 1px solid #000;
            padding-top: 6px;
            text-align: center;
            font-size: 8px;
            color: #666;
        }
        .barcode {
            text-align: center;
            margin: 8px 0;
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
            font-size: 10px;
        }
        .stamp-area {
            position: absolute;
            right: 20px;
            bottom: 100px;
            width: 100px;
            height: 100px;
            border: 2px dashed #000;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
        }
        
        /* Автоматически запускаем печать */
        @media print {
            body { margin: 0; padding: 5px; }
            .print-container { border: none; padding: 10px; }
        }
    </style>
</head>
<body >
    <div class="print-container">
        <!-- Шапка компании -->
        <div class="company-header">
            <h1><?= htmlspecialchars($company['company_name'] ?? 'AUTOSERVICE') ?></h1>
            <div style="font-size: 12px; margin-top: 3px;">Автосервис и шиномонтаж</div>
        </div>

        <!-- Реквизиты компании -->
        <div class="company-info">
            <div>
                <?php if (!empty($company['actual_address'])): ?>
                    <strong>Адрес:</strong> <?= htmlspecialchars($company['actual_address']) ?><br>
                <?php endif; ?>
                <?php if (!empty($company['phone'])): ?>
                    <strong>Телефон:</strong> <?= htmlspecialchars($company['phone']) ?>
                <?php endif; ?>
            </div>
            <div style="text-align: right;">
                <?php if (!empty($company['inn'])): ?>
                    <strong>ИНН:</strong> <?= htmlspecialchars($company['inn']) ?><br>
                <?php endif; ?>
                <?php if (!empty($company['bank_account'])): ?>
                    <strong>Р/с:</strong> <?= htmlspecialchars($company['bank_account']) ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Заголовок документа -->
        <div class="document-header">
            <h2>ЗАКАЗ-НАРЯД № <?= $order_id ?></h2>
            <div>от <?= date('d.m.Y', strtotime($order['created_at'])) ?></div>
        </div>
<div class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>🖨️ Печать заказа шиномонтажа #<?= $order_id ?></h1>
            <div class="header-actions">
                <a href="tire_orders.php" class="btn btn-secondary">📋 К списку заказов</a>
                <a href="tire_edit.php?id=<?= $order_id ?>" class="btn btn-primary">✏️ Редактировать</a>
            </div>
        </div>

        <!-- Предпросмотр печати -->
        <div style="text-align: center; margin-bottom: 20px; padding: 15px; background: #f0f0f0; border: 1px solid #ccc;">
            <button onclick="window.print()" class="btn btn-primary" style="padding: 12px 24px; font-size: 16px;">
                🖨️ Печатать документ
            </button>
            <p style="margin: 10px 0 0 0; color: #666; font-size: 14px;">
                Документ оптимизирован для печати. Для лучшего результата используйте landscape ориентацию.
            </p>
        </div>

        <!-- Документ для печати -->
        <div class="print-container" style="max-width: 800px; margin: 0 auto; border: 2px solid #000; padding: 20px; background: white;">
            <!-- Шапка компании -->
            <div class="company-header" style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px;">
                <h1 style="margin: 0; font-size: 20px; font-weight: bold; text-transform: uppercase;">
                    <?= htmlspecialchars($company['company_name'] ?? 'AUTOSERVICE') ?>
                </h1>
                <div style="font-size: 14px; margin-top: 5px;">Автосервис и шиномонтаж</div>
            </div>

            <!-- Реквизиты компании -->
            <div class="company-info" style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 11px;">
                <div>
                    <?php if (!empty($company['actual_address'])): ?>
                        <strong>Адрес:</strong> <?= htmlspecialchars($company['actual_address']) ?><br>
                    <?php else: ?>
                        <strong>Адрес:</strong> г. Москва, ул. Автосервисная, д. 1<br>
                    <?php endif; ?>
                    
                    <?php if (!empty($company['phone'])): ?>
                        <strong>Телефон:</strong> <?= htmlspecialchars($company['phone']) ?><br>
                    <?php else: ?>
                        <strong>Телефон:</strong> +7 (495) 123-45-67<br>
                    <?php endif; ?>
                    
                    <?php if (!empty($company['email'])): ?>
                        <strong>Email:</strong> <?= htmlspecialchars($company['email']) ?>
                    <?php else: ?>
                        <strong>Email:</strong> info@autoservice.ru
                    <?php endif; ?>
                </div>
                <div style="text-align: right;">
                    <?php if (!empty($company['inn'])): ?>
                        <strong>ИНН:</strong> <?= htmlspecialchars($company['inn']) ?><br>
                    <?php endif; ?>
                    
                    <?php if (!empty($company['ogrn'])): ?>
                        <strong>ОГРН:</strong> <?= htmlspecialchars($company['ogrn']) ?><br>
                    <?php endif; ?>
                    
                    <?php if (!empty($company['bank_account'])): ?>
                        <strong>Р/с:</strong> <?= htmlspecialchars($company['bank_account']) ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Заголовок документа -->
            <div class="document-header" style="text-align: center; margin: 20px 0; padding: 10px; background: #f5f5f5; border: 1px solid #ccc;">
                <h2 style="margin: 0; font-size: 16px; font-weight: bold;">ЗАКАЗ-НАРЯД № <?= $order_id ?></h2>
                <div>от <?= date('d.m.Y', strtotime($order['created_at'])) ?></div>
            </div>

            <!-- Информация о клиенте и автомобиле -->
            <div class="section" style="margin-bottom: 15px; border: 1px solid #ccc; padding: 10px;">
                <div class="section-title" style="background: #e9e9e9; padding: 5px 10px; margin: -10px -10px 8px -10px; border-bottom: 1px solid #ccc; font-weight: bold; font-size: 11px;">КЛИЕНТ И АВТОМОБИЛЬ</div>
                <div class="two-columns" style="display: flex; gap: 20px; margin-top: 5px;">
                    <div class="column" style="flex: 1;">
                        <strong>Клиент:</strong> <?= htmlspecialchars($order['client_name']) ?><br>
                        <strong>Телефон:</strong> <?= $order['phone'] ?>
                    </div>
                    <div class="column" style="flex: 1;">
                        <strong>Автомобиль:</strong> <?= htmlspecialchars($order['make']) ?> <?= htmlspecialchars($order['model']) ?><br>
                        <strong>Год выпуска:</strong> <?= $order['year'] ?><br>
                        <strong>Гос. номер:</strong> <?= $order['license_plate'] ?><br>
                        <?php if (!empty($order['vin']) || !empty($order['car_vin'])): ?>
                            <strong>VIN:</strong> <?= !empty($order['vin']) ? $order['vin'] : $order['car_vin'] ?><br>
                        <?php endif; ?>
                        <?php if (!empty($order['mileage'])): ?>
                            <strong>Пробег:</strong> <?= number_format($order['mileage'], 0, '', ' ') ?> км<br>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Шины по позициям -->
            <div class="section" style="margin-bottom: 15px; border: 1px solid #ccc; padding: 10px;">
                <div class="section-title" style="background: #e9e9e9; padding: 5px 10px; margin: -10px -10px 8px -10px; border-bottom: 1px solid #ccc; font-weight: bold; font-size: 11px;">ШИНЫ ПО ПОЗИЦИЯМ</div>
                <div class="tire-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px;">
                    <!-- Передняя левая -->
                    <div class="tire-position" style="border: 1px solid #ccc; padding: 6px; background: #f9f9f9; font-size: 11px;">
                        <div class="tire-title" style="font-weight: bold; border-bottom: 1px solid #ddd; padding-bottom: 3px; margin-bottom: 3px; font-size: 10px;">ПЕРЕДНЯЯ ЛЕВАЯ (FL)</div>
                        <strong>Размер:</strong> <?= $tire_data['fl_size'] ?? '—' ?><br>
                        <strong>Производитель:</strong> <?= $tire_data['fl_brand'] ?? '—' ?>
                    </div>
                    
                    <!-- Передняя правая -->
                    <div class="tire-position" style="border: 1px solid #ccc; padding: 6px; background: #f9f9f9; font-size: 11px;">
                        <div class="tire-title" style="font-weight: bold; border-bottom: 1px solid #ddd; padding-bottom: 3px; margin-bottom: 3px; font-size: 10px;">ПЕРЕДНЯЯ ПРАВАЯ (FR)</div>
                        <strong>Размер:</strong> <?= $tire_data['fr_size'] ?? '—' ?><br>
                        <strong>Производитель:</strong> <?= $tire_data['fr_brand'] ?? '—' ?>
                    </div>
                    
                    <!-- Задняя левая -->
                    <div class="tire-position" style="border: 1px solid #ccc; padding: 6px; background: #f9f9f9; font-size: 11px;">
                        <div class="tire-title" style="font-weight: bold; border-bottom: 1px solid #ddd; padding-bottom: 3px; margin-bottom: 3px; font-size: 10px;">ЗАДНЯЯ ЛЕВАЯ (RL)</div>
                        <strong>Размер:</strong> <?= $tire_data['rl_size'] ?? '—' ?><br>
                        <strong>Производитель:</strong> <?= $tire_data['rl_brand'] ?? '—' ?>
                    </div>
                    
                    <!-- Задняя правая -->
                    <div class="tire-position" style="border: 1px solid #ccc; padding: 6px; background: #f9f9f9; font-size: 11px;">
                        <div class="tire-title" style="font-weight: bold; border-bottom: 1px solid #ddd; padding-bottom: 3px; margin-bottom: 3px; font-size: 10px;">ЗАДНЯЯ ПРАВАЯ (RR)</div>
                        <strong>Размер:</strong> <?= $tire_data['rr_size'] ?? '—' ?><br>
                        <strong>Производитель:</strong> <?= $tire_data['rr_brand'] ?? '—' ?>
                    </div>
                </div>
            </div>

            <!-- Услуги -->
            <div class="section" style="margin-bottom: 15px; border: 1px solid #ccc; padding: 10px;">
                <div class="section-title" style="background: #e9e9e9; padding: 5px 10px; margin: -10px -10px 8px -10px; border-bottom: 1px solid #ccc; font-weight: bold; font-size: 11px;">ВЫПОЛНЯЕМЫЕ РАБОТЫ</div>
                <div class="services-list" style="margin-top: 8px;">
                    <?php
                    $services = !empty($order['services']) ? explode(',', $order['services']) : [];
                    $service_names = [
                        'mounting' => 'Монтаж/демонтаж шин',
                        'balancing' => 'Балансировка колес',
                        'alignment' => 'Развал-схождение',
                        'repair' => 'Ремонт шин',
                        'seasonal' => 'Сезонная замена'
                    ];
                    
                    if (count($services) > 0) {
                        foreach ($services as $service) {
                            echo '<div class="service-item" style="padding: 1px 0; font-size: 11px;">• ' . ($service_names[$service] ?? $service) . '</div>';
                        }
                    } else {
                        echo '<div class="service-item" style="padding: 1px 0; font-size: 11px;">— Услуги не указаны —</div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Примечания -->
            <?php if (!empty($order['notes'])): ?>
            <div class="section" style="margin-bottom: 15px; border: 1px solid #ccc; padding: 10px;">
                <div class="section-title" style="background: #e9e9e9; padding: 5px 10px; margin: -10px -10px 8px -10px; border-bottom: 1px solid #ccc; font-weight: bold; font-size: 11px;">ПРИМЕЧАНИЯ</div>
                <?= nl2br(htmlspecialchars($order['notes'])) ?>
            </div>
            <?php endif; ?>

            <!-- Штрих-код -->
            <div class="barcode" style="text-align: center; margin: 10px 0; font-family: 'Courier New', monospace; letter-spacing: 2px;">
                *<?= $order_id ?>*<?= date('dmY', strtotime($order['created_at'])) ?>*<?= htmlspecialchars($company['company_name'] ?? 'AUTOSERVICE') ?>*
            </div>

            <!-- Подписи -->
            <div class="signature-area" style="margin-top: 30px; display: flex; justify-content: space-between;">
                <div class="signature" style="text-align: center; width: 250px;">
                    <div><strong>ИСПОЛНИТЕЛЬ</strong></div>
                    <div class="signature-line" style="border-top: 1px solid #000; margin-top: 40px; padding-top: 3px; height: 20px;"></div>
                    <div class="signature-info" style="font-size: 10px; margin-top: 5px;">
                        <?= $executor['full_name'] ?? 'Иванов И.И.' ?><br>
                        Мастер шиномонтажа
                    </div>
                </div>
                <div class="signature" style="text-align: center; width: 250px;">
                    <div><strong>КЛИЕНТ</strong></div>
                    <div class="signature-line" style="border-top: 1px solid #000; margin-top: 40px; padding-top: 3px; height: 20px;"></div>
                    <div class="signature-info" style="font-size: 10px; margin-top: 5px;">
                        <?= htmlspecialchars($order['client_name']) ?><br>
                        <?= $order['phone'] ?>
                    </div>
                </div>
            </div>

            <!-- Место для печати -->
            <div class="stamp-area" style="position: absolute; right: 30px; bottom: 120px; width: 120px; height: 120px; border: 2px dashed #ccc; text-align: center; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #999;">
                М.П.<br>
                (печать)
            </div>

            <!-- Футер -->
            <div class="footer" style="margin-top: 20px; border-top: 1px solid #000; padding-top: 8px; text-align: center; font-size: 9px; color: #666;">
                Заказ-наряд распечатан: <?= date('d.m.Y H:i') ?><br>
                <?= htmlspecialchars($company['company_name'] ?? 'AUTOSERVICE') ?> - Профессиональный шиномонтаж<br>
                <?php if (!empty($company['phone'])): ?>
                    Телефон: <?= htmlspecialchars($company['phone']) ?> | 
                <?php endif; ?>
                <?php if (!empty($company['website'])): ?>
                    <?= htmlspecialchars($company['website']) ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Инструкция по печати -->
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px;">
            <h3 style="margin-top: 0;">📋 Советы по печати:</h3>
            <ul style="margin-bottom: 0;">
                <li>Для лучшего результата используйте альбомную ориентацию</li>
                <li>Убедитесь, что в настройках печати включены фоновые graphics</li>
                <li>Рекомендуемая бумага: A4</li>
                <li>Документ содержит место для подписей и печати</li>
            </ul>
        </div>
    </div>
</div>

<style>
@media print {
    .main-content .container > *:not(.print-container) {
        display: none !important;
    }
    .print-container {
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        max-width: none !important;
    }
    .stamp-area {
        border: 2px dashed #000 !important;
    }
}
</style>

<?php include 'templates/footer.php'; ?>

    </div>
</body>
</html>
<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAuth();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID заказа не указан");
}

$order_id = (int)$_GET['id'];

// Получаем данные заказа
$order_sql = "SELECT * FROM order_tire_services WHERE id = ?";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Заказ не найден");
}

// Получаем услуги
$services_data = [];
if (!empty($order['notes'])) {
    $services_data = json_decode($order['notes'], true) ?: [];
}

// Получаем названия услуг
$service_names = [];
if (!empty($services_data)) {
    $service_ids = array_column($services_data, 'service_id');
    if (!empty($service_ids)) {
        $ids_placeholder = str_repeat('?,', count($service_ids) - 1) . '?';
        $names_sql = "SELECT id, name FROM tire_services WHERE id IN ($ids_placeholder)";
        $names_stmt = $conn->prepare($names_sql);
        $names_stmt->bind_param(str_repeat('i', count($service_ids)), ...$service_ids);
        $names_stmt->execute();
        $names_result = $names_stmt->get_result();
        while ($row = $names_result->fetch_assoc()) {
            $service_names[$row['id']] = $row['name'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заказ-наряд #<?= $order_id ?></title>
    <style>
        body { 
            font-family: 'Arial', sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: white;
            color: black;
        }
        .print-container { 
            max-width: 800px; 
            margin: 0 auto; 
            border: 2px solid #000; 
            padding: 20px; 
        }
        .header { 
            text-align: center; 
            border-bottom: 2px solid #000; 
            padding-bottom: 15px; 
            margin-bottom: 20px; 
        }
        .company-name { 
            font-size: 24px; 
            font-weight: bold; 
            margin-bottom: 5px; 
        }
        .document-title { 
            font-size: 18px; 
            margin-bottom: 10px; 
        }
        .order-info { 
            margin-bottom: 20px; 
        }
        .info-section { 
            margin-bottom: 15px; 
        }
        .info-section h3 { 
            margin: 0 0 5px 0; 
            font-size: 14px; 
            border-bottom: 1px solid #ccc; 
            padding-bottom: 2px; 
        }
        .services-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0; 
        }
        .services-table th, 
        .services-table td { 
            border: 1px solid #000; 
            padding: 8px; 
            text-align: left; 
        }
        .services-table th { 
            background: #f0f0f0; 
            font-weight: bold; 
        }
        .total-section { 
            text-align: right; 
            font-size: 18px; 
            font-weight: bold; 
            margin: 20px 0; 
            padding: 10px; 
            border-top: 2px solid #000; 
        }
        .footer { 
            margin-top: 40px; 
            display: flex; 
            justify-content: space-between; 
        }
        .signature { 
            text-align: center; 
            width: 45%; 
        }
        .signature-line { 
            border-top: 1px solid #000; 
            margin-top: 40px; 
            padding-top: 5px; 
        }
        @media print {
            body { margin: 0; padding: 10px; }
            .no-print { display: none; }
            .print-container { border: none; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="header">
            <div class="company-name">АВТОСЕРВИС "SPK AUTO"</div>
            <div class="document-title">ЗАКАЗ-НАРЯД №<?= $order_id ?></div>
            <div>Шиномонтажный участок</div>
        </div>

        <div class="order-info">
            <div class="info-section">
                <h3>👤 ДАННЫЕ КЛИЕНТА</h3>
                <div><strong>ФИО:</strong> <?= htmlspecialchars($order['client_name']) ?></div>
                <div><strong>Телефон:</strong> <?= htmlspecialchars($order['client_phone']) ?></div>
            </div>

            <div class="info-section">
                <h3>🚗 АВТОМОБИЛЬ</h3>
                <div><strong>Марка, модель:</strong> <?= htmlspecialchars($order['car_model']) ?></div>
                <?php if (!empty($order['car_plate'])): ?>
                <div><strong>Гос. номер:</strong> <?= htmlspecialchars($order['car_plate']) ?></div>
                <?php endif; ?>
                <div><strong>Тип резины:</strong> 
                    <?= $order['tire_type'] == 'summer' ? 'Летняя' : 
                       ($order['tire_type'] == 'winter' ? 'Зимняя' : 'Всесезонная') ?>
                </div>
                <div><strong>Радиус колес:</strong> R<?= $order['radius'] ?></div>
            </div>

            <div class="info-section">
                <h3>📊 СТАТУС ЗАКАЗА</h3>
                <div><strong>Статус:</strong> 
                    <?= $order['status'] == 'new' ? '🆕 НОВЫЙ' : 
                       ($order['status'] == 'in_progress' ? '🔧 В РАБОТЕ' : 
                       ($order['status'] == 'completed' ? '✅ ГОТОВ' : '🚗 ВЫДАН')) ?>
                </div>
                <div><strong>Дата создания:</strong> <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></div>
                <?php if ($order['completed_at']): ?>
                <div><strong>Дата завершения:</strong> <?= date('d.m.Y H:i', strtotime($order['completed_at'])) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <table class="services-table">
            <thead>
                <tr>
                    <th width="50%">Услуга</th>
                    <th width="15%">Кол-во</th>
                    <th width="15%">Цена</th>
                    <th width="20%">Сумма</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services_data as $service): ?>
                <tr>
                    <td><?= htmlspecialchars($service_names[$service['service_id']] ?? 'Услуга') ?></td>
                    <td><?= $service['quantity'] ?> шт.</td>
                    <td><?= number_format($service['price'], 2) ?> руб.</td>
                    <td><?= number_format($service['total'], 2) ?> руб.</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-section">
            ИТОГО: <?= number_format($order['total_price'], 2) ?> руб.
        </div>

        <div class="footer">
            <div class="signature">
                Подпись мастера:<br>
                <div class="signature-line"></div>
            </div>
            <div class="signature">
                Подпись клиента:<br>
                <div class="signature-line"></div>
            </div>
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" class="btn-1c-primary">🖨️ Печать</button>
        <a href="tire_orders.php" class="btn-1c">← Назад к списку</a>
    </div>
</body>
</html>
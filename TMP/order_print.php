<?php
// order_print.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'includes/db.php';
session_start();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Ошибка: ID заказа не указан");
}
$order_id = (int)$_GET['id'];

// Получаем информацию о заказе
$order = [];
$stmt = $conn->prepare("
    SELECT o.id, o.car_id, o.description, o.status, o.total, o.created,
           o.services_data, o.parts_data, o.services_total, o.parts_total,
           c.make, c.model, c.year, c.license_plate, c.vin,
           cl.id AS client_id, cl.name AS client_name, cl.phone
    FROM orders o
    JOIN cars c ON o.car_id = c.id
    JOIN clients cl ON c.client_id = cl.id
    WHERE o.id = ?
");

if (!$stmt) {
    die("Ошибка подготовки запроса: " . $conn->error);
}

$stmt->bind_param('i', $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    die("Заказ не найден");
}

// Получаем услуги из JSON
$order_services = [];
if (!empty($order['services_data']) && $order['services_data'] != 'null') {
    $decoded = json_decode($order['services_data'], true);
    if (is_array($decoded)) {
        $order_services = $decoded;
    }
}

// Получаем запчасти из JSON
$order_parts = [];
if (!empty($order['parts_data']) && $order['parts_data'] != 'null') {
    $decoded = json_decode($order['parts_data'], true);
    if (is_array($decoded)) {
        $order_parts = $decoded;
    }
}

// Рассчитываем суммы для печати
$services_total_print = 0;
$parts_total_print = 0;

foreach ($order_services as $service) {
    $services_total_print += $service['price'] * $service['quantity'];
}

foreach ($order_parts as $part) {
    $parts_total_print += $part['price'] * $part['quantity'];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заказ-наряд #<?= $order_id ?></title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            margin: 0;
            padding: 0;
        }
        .container { 
            width: 210mm;
            margin: 0 auto;
            padding: 15mm;
        }
        .header { 
            text-align: center; 
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .company-name { 
            font-size: 18px; 
            font-weight: bold;
            margin-bottom: 5px;
        }
        .document-title { 
            font-size: 16px; 
            font-weight: bold;
            margin-bottom: 10px;
        }
        .section { 
            margin-bottom: 15px;
        }
        .section-title { 
            font-weight: bold; 
            background: #f0f0f0;
            padding: 5px 10px;
            margin-bottom: 8px;
            border-left: 3px solid #333;
        }
        .info-grid { 
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .info-block { 
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 4px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th { 
            background: #f8f9fa; 
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        td { 
            border: 1px solid #ddd;
            padding: 8px;
        }
        .text-right { 
            text-align: right;
        }
        .text-center { 
            text-align: center;
        }
        .totals { 
            margin-top: 20px;
            border-top: 2px solid #333;
            padding-top: 10px;
        }
        .total-row { 
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .total-final { 
            font-size: 14px;
            font-weight: bold;
            border-top: 1px solid #333;
            padding-top: 5px;
            margin-top: 5px;
        }
        .footer { 
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            font-size: 11px;
        }
        .signatures { 
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 40px;
        }
        .signature-line { 
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 5px;
        }
        @media print {
            body { margin: 0; }
            .container { padding: 10mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Шапка документа -->
        <div class="header">
            <div class="company-name">Автосервис </div>
            <div class="document-title">ЗАКАЗ-НАРЯД #<?= $order_id ?></div>
            <div>Дата создания: <?= date('d.m.Y H:i', strtotime($order['created'])) ?></div>
        </div>

        <!-- Информация о клиенте и автомобиле -->
        <div class="info-grid">
            <div class="info-block">
                <strong>Клиент:</strong><br>
                <?= htmlspecialchars($order['client_name']) ?><br>
                Телефон: <?= htmlspecialchars($order['phone']) ?>
            </div>
            <div class="info-block">
                <strong>Автомобиль:</strong><br>
                <?= htmlspecialchars($order['make']) ?> <?= htmlspecialchars($order['model']) ?> (<?= $order['year'] ?>)<br>
                <?php if (!empty($order['vin'])): ?>
                VIN: <?= htmlspecialchars($order['vin']) ?><br>
                <?php endif; ?>
                <?php if (!empty($order['license_plate'])): ?>
                Гос. номер: <?= htmlspecialchars($order['license_plate']) ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Описание проблемы -->
        <?php if (!empty($order['description'])): ?>
        <div class="section">
            <div class="section-title">Описание проблемы / выполненные работы:</div>
            <div><?= nl2br(htmlspecialchars($order['description'])) ?></div>
        </div>
        <?php endif; ?>

        <!-- Таблица услуг -->
        <?php if (count($order_services) > 0): ?>
        <div class="section">
            <div class="section-title">Выполненные услуги:</div>
            <table>
                <thead>
                    <tr>
                        <th width="5%">№</th>
                        <th>Наименование услуги</th>
                        <th width="10%">Кол-во</th>
                        <th width="15%">Цена за ед., руб.</th>
                        <th width="15%">Сумма, руб.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $service_num = 1;
                    foreach ($order_services as $service): 
                        $service_sum = $service['price'] * $service['quantity'];
                    ?>
                    <tr>
                        <td class="text-center"><?= $service_num++ ?></td>
                        <td><?= htmlspecialchars($service['name']) ?></td>
                        <td class="text-center"><?= $service['quantity'] ?> <?= htmlspecialchars($service['unit']) ?></td>
                        <td class="text-right"><?= number_format($service['price'], 2) ?></td>
                        <td class="text-right"><?= number_format($service_sum, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="4" class="text-right"><strong>Итого по услугам:</strong></td>
                        <td class="text-right"><strong><?= number_format($services_total_print, 2) ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Таблица запчастей -->
        <?php if (count($order_parts) > 0): ?>
        <div class="section">
            <div class="section-title">Использованные запчасти:</div>
            <table>
                <thead>
                    <tr>
                        <th width="5%">№</th>
                        <th>Наименование запчасти</th>
                        <th width="15%">Артикул</th>
                        <th width="10%">Кол-во</th>
                        <th width="15%">Цена за ед., руб.</th>
                        <th width="15%">Сумма, руб.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $part_num = 1;
                    foreach ($order_parts as $part): 
                        $part_sum = $part['price'] * $part['quantity'];
                    ?>
                    <tr>
                        <td class="text-center"><?= $part_num++ ?></td>
                        <td><?= htmlspecialchars($part['name']) ?></td>
                        <td><?= htmlspecialchars($part['part_number']) ?></td>
                        <td class="text-center"><?= $part['quantity'] ?></td>
                        <td class="text-right"><?= number_format($part['price'], 2) ?></td>
                        <td class="text-right"><?= number_format($part_sum, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="5" class="text-right"><strong>Итого по запчастям:</strong></td>
                        <td class="text-right"><strong><?= number_format($parts_total_print, 2) ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Итоговая сумма -->
        <div class="totals">
            <div class="total-row">
                <div>Сумма услуг:</div>
                <div><strong><?= number_format($services_total_print, 2) ?> руб.</strong></div>
            </div>
            <div class="total-row">
                <div>Сумма запчастей:</div>
                <div><strong><?= number_format($parts_total_print, 2) ?> руб.</strong></div>
            </div>
            <div class="total-row total-final">
                <div>ВСЕГО К ОПЛАТЕ:</div>
                <div><strong><?= number_format($order['total'], 2) ?> руб.</strong></div>
            </div>
        </div>

        <!-- Подписи -->
        <div class="signatures">
            <div>
                <div>Исполнитель:</div>
                <div class="signature-line"></div>
                <div class="text-center">(подпись, ФИО)</div>
            </div>
            <div>
                <div>Клиент:</div>
                <div class="signature-line"></div>
                <div class="text-center">(подпись, ФИО)</div>
            </div>
        </div>

        <!-- Статус заказа -->
        <div class="footer">
            <div><strong>Статус заказа:</strong> <?= $order['status'] ?></div>
            <div class="no-print">
                <br>
                <button onclick="window.print()" class="btn">Печать</button>
                <button onclick="window.close()" class="btn">Закрыть</button>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            // Автоматическая печать при открытии (раскомментируйте если нужно)
            // window.print();
        }
    </script>
</body>
</html>
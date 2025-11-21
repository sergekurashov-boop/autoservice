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
    die("Заказ #{$order_id} не найден в базе данных");
}

// Определяем заголовок документа в зависимости от статуса
$document_title = ($order['status'] != 'Готов') ? 'ПРЕДВАРИТЕЛЬНЫЙ ЗАКАЗ-НАРЯД' : 'ЗАКАЗ-НАРЯД';

// Создаем таблицу company_details если её нет
$conn->query("
    CREATE TABLE IF NOT EXISTS company_details (
        id INT PRIMARY KEY AUTO_INCREMENT,
        company_name VARCHAR(255) NOT NULL,
        legal_name VARCHAR(255),
        inn VARCHAR(20),
        kpp VARCHAR(20),
        ogrn VARCHAR(20),
        legal_address TEXT,
        actual_address TEXT,
        phone VARCHAR(50),
        email VARCHAR(100),
        website VARCHAR(255),
        bank_name VARCHAR(255),
        bank_account VARCHAR(50),
        corr_account VARCHAR(50),
        bic VARCHAR(20),
        director_name VARCHAR(255),
        accountant_name VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

// Получаем реквизиты компании
$company_details = [];
try {
    $result = $conn->query("SELECT * FROM company_details ORDER BY id DESC LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $company_details = $result->fetch_assoc();
    }
} catch (Exception $e) {
    error_log("Error fetching company details: " . $e->getMessage());
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
    <title><?= $document_title ?> #<?= $order['id'] ?></title>
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
        .company-details {
            font-size: 11px;
            margin-bottom: 10px;
            line-height: 1.3;
        }
        .document-title { 
            font-size: 16px; 
            font-weight: bold;
            margin-bottom: 10px;
        }
        .preliminary-notice {
            color: #d9534f;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
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
        .company-block {
            border: 1px solid #333;
            padding: 15px;
            margin-bottom: 20px;
            background: #f9f9f9;
        }
        .company-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            font-size: 11px;
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
        .text-left { 
            text-align: left;
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
        .bank-details {
            font-size: 10px;
            line-height: 1.2;
            margin-top: 5px;
        }
        .consent-section {
            margin-top: 30px;
            font-size: 10px;
            line-height: 1.2;
            border: 1px solid #ddd;
            padding: 15px;
            background: #f9f9f9;
        }
        .consent-title {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 10px;
            text-align: center;
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
        <!-- Шапка документа с реквизитами компании -->
        <div class="header">
            <?php if (!empty($company_details['company_name'])): ?>
                <div class="company-name"><?= htmlspecialchars($company_details['company_name']) ?></div>
            <?php else: ?>
                <div class="company-name">Автосервис</div>
                <div class="company-details" style="color: #666; font-style: italic;">
                    Для добавления реквизитов перейдите в Панель управления → Реквизиты компании
                </div>
            <?php endif; ?>
            
            <?php if (!empty($company_details['legal_name'])): ?>
                <div class="company-details"><?= htmlspecialchars($company_details['legal_name']) ?></div>
            <?php endif; ?>
            
            <?php if (!empty($company_details['legal_address']) || !empty($company_details['inn'])): ?>
            <div class="company-grid">
                <div class="text-left">
                    <?php if (!empty($company_details['legal_address'])): ?>
                        <div>Юр. адрес: <?= htmlspecialchars($company_details['legal_address']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($company_details['actual_address'])): ?>
                        <div>Факт. адрес: <?= htmlspecialchars($company_details['actual_address']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($company_details['phone'])): ?>
                        <div>Тел: <?= htmlspecialchars($company_details['phone']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="text-left">
                    <?php if (!empty($company_details['inn'])): ?>
                        <div>ИНН: <?= htmlspecialchars($company_details['inn']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($company_details['kpp'])): ?>
                        <div>КПП: <?= htmlspecialchars($company_details['kpp']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($company_details['ogrn'])): ?>
                        <div>ОГРН: <?= htmlspecialchars($company_details['ogrn']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Измененный заголовок документа -->
            <div class="document-title">
                <?php if ($order['status'] != 'Готов'): ?>
                    <div class="preliminary-notice">ПРЕДВАРИТЕЛЬНЫЙ</div>
                <?php endif; ?>
                ЗАКАЗ-НАРЯД №<?= $order['id'] ?>
            </div>
            <div>Дата создания: <?= date('d.m.Y H:i', strtotime($order['created'])) ?></div>
        </div>

        <!-- Банковские реквизиты -->
        <?php if (!empty($company_details['bank_name']) || !empty($company_details['bank_account'])): ?>
        <div class="company-block">
            <div style="font-weight: bold; margin-bottom: 8px;">Банковские реквизиты:</div>
            <div class="company-grid">
                <div class="text-left">
                    <?php if (!empty($company_details['bank_name'])): ?>
                        <div>Банк: <?= htmlspecialchars($company_details['bank_name']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($company_details['bank_account'])): ?>
                        <div>Р/с: <?= htmlspecialchars($company_details['bank_account']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="text-left">
                    <?php if (!empty($company_details['corr_account'])): ?>
                        <div>К/с: <?= htmlspecialchars($company_details['corr_account']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($company_details['bic'])): ?>
                        <div>БИК: <?= htmlspecialchars($company_details['bic']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

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
                <?php if (!empty($company_details['director_name'])): ?>
                    <div style="margin-top: 5px; font-size: 11px;"><?= htmlspecialchars($company_details['director_name']) ?></div>
                <?php endif; ?>
                <div class="signature-line"></div>
                <div class="text-center">(подпись)</div>
            </div>
            <div>
                <div>Клиент:</div>
                <div style="margin-top: 5px; font-size: 11px;"><?= htmlspecialchars($order['client_name']) ?></div>
                <div class="signature-line"></div>
                <div class="text-center">(подпись)</div>
            </div>
        </div>

        <!-- Согласие на обработку персональных данных -->
        <div class="consent-section">
            <div class="consent-title">СОГЛАСИЕ НА ОБРАБОТКУ ПЕРСОНАЛЬНЫХ ДАННЫХ</div>
            
            <div style="margin-bottom: 10px;">
                Настоящим я, <strong><?= htmlspecialchars($order['client_name']) ?></strong>, 
                даю свое согласие на обработку моих персональных данных, включая: 
                ФИО, контактные данные (телефон), данные автомобиля (VIN, гос. номер), 
                в целях оказания услуг по ремонту и обслуживанию автомобиля.
            </div>
            
            <div style="margin-bottom: 10px;">
                <strong>Обработка персональных данных включает:</strong><br>
                - Сбор, запись, систематизацию, накопление, хранение<br>
                - Уточнение (обновление, изменение), извлечение, использование<br>
                - Передача (предоставление, доступ) только в целях оказания услуг<br>
                - Обезличивание, блокирование, удаление, уничтожение
            </div>
            
            <div style="margin-bottom: 10px;">
                Согласие действует до достижения целей обработка персональных данных 
                или до отзыва согласия. Отзыв согласия осуществляется путем 
                письменного обращения к оператору.
            </div>
            
            <div style="margin-bottom: 10px;">
                <strong>Оператор:</strong> 
                <?php if (!empty($company_details['legal_name'])): ?>
                    <?= htmlspecialchars($company_details['legal_name']) ?>
                <?php else: ?>
                    <?= htmlspecialchars($company_details['company_name'] ?? 'Автосервис') ?>
                <?php endif; ?>
                <?php if (!empty($company_details['legal_address'])): ?>
                    , <?= htmlspecialchars($company_details['legal_address']) ?>
                <?php endif; ?>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                <div>
                    <div style="border-top: 1px solid №000; margin-top: 40px; padding-top: 5px;">
                        <div class="text-center">(подпись клиента)</div>
                        <div class="text-center" style="font-size: 9px; margin-top: 5px;">
                            <?= htmlspecialchars($order['client_name']) ?>
                        </div>
                    </div>
                </div>
                <div>
                    <div style="border-top: 1px solid №000; margin-top: 40px; padding-top: 5px;">
                        <div class="text-center">(дата)</div>
                        <div class="text-center" style="font-size: 9px; margin-top: 5px;">
                            <?= date('d.m.Y') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Политика конфиденциальности -->
        <div class="footer" style="font-size: 9px; color: №666;">
            <div><strong>Информация о защите персональных данных:</strong></div>
            <div>
                Ваши персональные данные защищены в соответствии с ФЗ-152 "О персональных данных". 
                Мы принимаем необходимые организационные и технические меры для защиты 
                персональных данных от неправомерного или случайного доступа, уничтожения, 
                изменения, блокирования, копирования, распространения.
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
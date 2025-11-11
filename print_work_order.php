<?php
require 'includes/db.php';
session_start();

// Проверка доступа
define('ACCESS', true);
include 'templates/header.php';

$order_id = (int)$_GET['id'];
$order = $conn->query("
    SELECT wo.*, c.name AS client_name, c.phone AS client_phone,
           car.make, car.model, car.license_plate, car.vin,
           comp.name AS company_name, comp.address AS company_address,
           comp.phone AS company_phone, comp.director_name
    FROM work_orders wo
    JOIN clients c ON wo.client_id = c.id
    JOIN cars car ON wo.car_id = car.id
    JOIN companies comp ON wo.company_id = comp.id
    WHERE wo.id = $order_id
")->fetch_assoc();

if (!$order) {
    die("Заказ-наряд не найден");
}

$services = $conn->query("
    SELECT * FROM work_order_services
    WHERE work_order_id = $order_id
");

$page_title = "Заказ-наряд " . $order['order_number'];
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        .print-section, .print-section * {
            visibility: visible;
        }
        .print-section {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .no-print {
            display: none !important;
        }
    }
    
    .work-order {
        border: 2px solid #000;
        padding: 20px;
        font-family: Arial, sans-serif;
    }
    
    .header {
        text-align: center;
        margin-bottom: 20px;
    }
    
    .header h1 {
        font-size: 24px;
        margin-bottom: 5px;
    }
    
    .order-number {
        font-size: 20px;
        font-weight: bold;
        margin-bottom: 20px;
    }
    
    .section {
        margin-bottom: 15px;
    }
    
    .section-title {
        font-weight: bold;
        border-bottom: 1px solid #000;
        margin-bottom: 5px;
    }
    
    .company-info {
        text-align: right;
        margin-bottom: 20px;
    }
    
    .signature-block {
        margin-top: 50px;
        display: flex;
        justify-content: space-between;
    }
    
    .signature {
        width: 45%;
        border-top: 1px solid #000;
        text-align: center;
        padding-top: 5px;
    }
</style>

<div class="container mt-4">
    <div class="d-flex justify-content-end mb-3 no-print">
        <button class="btn btn-primary me-2" onclick="window.print()">
            <i class="bi bi-printer"></i> Печать
        </button>
        <a href="create_work_order.php" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Новый заказ-наряд
        </a>
    </div>
    
    <div class="work-order print-section">
        <div class="company-info">
            <div><strong><?= htmlspecialchars($order['company_name']) ?></strong></div>
            <div><?= nl2br(htmlspecialchars($order['company_address'])) ?></div>
            <div>Тел: <?= htmlspecialchars($order['company_phone']) ?></div>
        </div>
        
        <div class="header">
            <h1>ЗАКАЗ-НАРЯД</h1>
            <div class="order-number">№ <?= htmlspecialchars($order['order_number']) ?></div>
            <div>Дата: <?= date('d.m.Y', strtotime($order['created_at'])) ?></div>
        </div>
        
        <div class="section">
            <div class="section-title">Клиент</div>
            <div><strong>Имя:</strong> <?= htmlspecialchars($order['client_name']) ?></div>
            <div><strong>Телефон:</strong> <?= htmlspecialchars($order['client_phone']) ?></div>
        </div>
        
        <div class="section">
            <div class="section-title">Автомобиль</div>
            <div><strong>Марка/Модель:</strong> <?= htmlspecialchars($order['make']) ?> <?= htmlspecialchars($order['model']) ?></div>
            <div><strong>Гос. номер:</strong> <?= htmlspecialchars($order['license_plate']) ?></div>
            <div><strong>VIN:</strong> <?= htmlspecialchars($order['vin']) ?></div>
        </div>
        
        <div class="section">
            <div class="section-title">Причина обращения</div>
            <div><?= nl2br(htmlspecialchars($order['issue_description'])) ?></div>
        </div>
        
        <div class="section">
            <div class="section-title">Выполненные работы</div>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Услуга</th>
                        <th width="15%">Цена</th>
                        <th width="10%">Кол-во</th>
                        <th width="15%">Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($service = $services->fetch_assoc()): 
                        $service_total = $service['price'] * $service['quantity'];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($service['service_name']) ?></td>
                        <td class="text-end"><?= number_format($service['price'], 2, ',', ' ') ?></td>
                        <td class="text-center"><?= $service['quantity'] ?></td>
                        <td class="text-end"><?= number_format($service_total, 2, ',', ' ') ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Итого:</th>
                        <th class="text-end"><?= number_format($order['total_amount'], 2, ',', ' ') ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="signature-block">
            <div class="signature">
                Клиент: ____________________
            </div>
            <div class="signature">
                Принял: <?= htmlspecialchars($order['director_name']) ?> ____________________
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
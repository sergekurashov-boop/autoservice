<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAuth();

// Получаем список заказов шиномонтажа
$sql = "SELECT * FROM order_tire_services ORDER BY created_at DESC";
$result = $conn->query($sql);
$tire_orders = $result->fetch_all(MYSQLI_ASSOC);

include 'templates/header.php';
?>

<div class="container">
    <div class="header-actions">
        <h1>🛞 Заказы шиномонтажа</h1>
        <div class="action-buttons">
            <a href="tire_create.php" class="btn-1c-primary">
                ➕ Новый заказ-наряд
            </a>
            <a href="tire_stats.php" class="btn-1c">
                📊 Статистика
            </a>
            <a href="orders.php" class="btn-1c">
                ← К общим заказам
            </a>
        </div>
    </div>

    <div class="card-1c">
        <div class="card-header-1c">
            <span class="card-header-icon">📋</span> Список заказов
        </div>
        <div class="card-body">
            <?php if (!empty($tire_orders)): ?>
            <div class="table-responsive">
                <table class="orders-table-enhanced">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Клиент</th>
                            <th>Автомобиль</th>
                            <th>Радиус</th>
                            <th>Статус</th>
                            <th>Сумма</th>
                            <th>Дата</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tire_orders as $order): ?>
                        <tr>
                            <td>#<?= $order['id'] ?></td>
                            <td>
                                <div class="client-name"><?= htmlspecialchars($order['client_name']) ?></div>
                                <small class="client-phone"><?= htmlspecialchars($order['client_phone']) ?></small>
                            </td>
                            <td>
                                <div class="car-main"><?= htmlspecialchars($order['car_model']) ?></div>
                                <?php if (!empty($order['car_plate'])): ?>
                                    <small class="car-plate"><?= $order['car_plate'] ?></small>
                                <?php endif; ?>
                            </td>
                            <td>R<?= $order['radius'] ?></td>
                            <td>
                                <span class="status-badge-enhanced 
                                    <?= $order['status'] == 'new' ? 'waiting' : '' ?>
                                    <?= $order['status'] == 'in_progress' ? 'working' : '' ?>
                                    <?= $order['status'] == 'completed' ? 'completed' : '' ?>
                                    <?= $order['status'] == 'issued' ? 'diagnosis' : '' ?>">
                                    <?= getTireStatusText($order['status']) ?>
                                </span>
                            </td>
                            <td><?= number_format($order['total_price'], 2) ?> руб.</td>
                            <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="tire_edit.php?id=<?= $order['id'] ?>" class="action-btn edit" title="Редактировать">✏️</a>
                                    <a href="tire_print.php?id=<?= $order['id'] ?>" class="action-btn print" title="Печать" target="_blank">🖨️</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="no-orders">
                <div class="no-orders-content">
                    <div class="no-orders-icon">🛞</div>
                    <h5 class="no-orders-text">Заказы шиномонтажа не найдены</h5>
                    <a href="tire_create.php" class="btn-1c-primary">Создать первый заказ</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
function getTireStatusText($status) {
    $statuses = [
        'new' => '🆕 Новый',
        'in_progress' => '🔧 В работе', 
        'completed' => '✅ Готов',
        'issued' => '🚗 Выдан'
    ];
    return $statuses[$status] ?? $status;
}
include 'templates/footer.php'; 
?>
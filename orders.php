<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAuth();
define('ACCESS', true);

// Обработка удаления заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    // CSRF защита
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Ошибка безопасности. Пожалуйста, обновите страницу.";
        header("Location: orders.php");
        exit;
    }

    $order_id = (int)$_POST['order_id'];
    
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("DELETE FROM order_services WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        
        $stmt = $conn->prepare("DELETE FROM order_parts WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        
        $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        
        $conn->commit();
        $_SESSION['success'] = "Заказ #$order_id успешно удалён";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Ошибка при удалении заказа: " . $e->getMessage();
    }
    header("Location: orders.php");
    exit;
}

// Генерация CSRF токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Получаем список заказов
$orders = $conn->query("
    SELECT o.id, o.created, o.description, o.status, o.total, 
           o.services_total, o.parts_total,
           c.make, c.model, c.license_plate,
           cl.name AS client_name
    FROM orders o
    JOIN cars c ON o.car_id = c.id
    JOIN clients cl ON c.client_id = cl.id
    ORDER BY o.created DESC
");

include 'templates/header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление заказами</title>
    <link href="assets/css/orders.css" rel="stylesheet">
</head>
<body>
    <div class="orders-container">
        <div class="container-header">
            <h1 class="page-title">Управление заказами</h1>
            <a href="create_order.php" class="btn-1c-primary">
                <span class="btn-icon">+</span> Новый заказ
            </a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-enhanced alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-enhanced alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Список заказов -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">
                <span class="card-header-icon">≡</span> Список заказов
            </div>
            <div class="card-body">
                <?php if ($orders && $orders->num_rows > 0): ?>
                <div class="orders-table-container">
                    <table class="orders-table-enhanced">
                        <thead>
                            <tr>
                                <th class="col-id">№ Заказа</th>
                                <th class="col-date">Дата создания</th>
                                <th class="col-client">Клиент</th>
                                <th class="col-car">Автомобиль</th>
                                <th class="col-desc">Описание</th>
                                <th class="col-status">Статус</th>
                                <th class="col-amount">Сумма</th>
                                <th class="col-services">Услуги</th>
                                <th class="col-parts">Запчасти</th>
                                <th class="col-actions">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($order = $orders->fetch_assoc()): ?>
                                <tr class="order-row">
                                    <td>
                                        <a href="order_edit.php?id=<?= $order['id'] ?>" class="order-link">
                                            #<?= $order['id'] ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="date-main"><?= date('d.m.Y', strtotime($order['created'])) ?></div>
                                        <small class="date-time"><?= date('H:i', strtotime($order['created'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="client-name"><?= htmlspecialchars($order['client_name']) ?></div>
                                    </td>
                                    <td>
                                        <div class="car-main"><?= htmlspecialchars($order['make']) ?> <?= htmlspecialchars($order['model']) ?></div>
                                        <?php if (!empty($order['license_plate'])): ?>
                                            <small class="car-plate"><?= $order['license_plate'] ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="order-desc">
                                            <div class="desc-text" title="<?= htmlspecialchars($order['description']) ?>">
                                                <?= htmlspecialchars($order['description']) ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge-enhanced 
                                            <?= $order['status'] == 'В ожидании' ? 'waiting' : '' ?>
                                            <?= $order['status'] == 'В работе' ? 'working' : '' ?>
                                            <?= $order['status'] == 'Готов' ? 'completed' : '' ?>
                                            <?= $order['status'] == 'Выдан' ? 'diagnosis' : '' ?>
                                        ">
                                            <span class="status-icon">
                                                <?= $order['status'] == 'В ожидании' ? '⏳' : '' ?>
                                                <?= $order['status'] == 'В работе' ? '🔧' : '' ?>
                                                <?= $order['status'] == 'Готов' ? '✅' : '' ?>
                                                <?= $order['status'] == 'Выдан' ? '🚗' : '' ?>
                                            </span>
                                            <?= $order['status'] ?>
                                        </span>
                                    </td>
                                    <td class="order-amount">
                                        <?php if ($order['total'] > 0): ?>
                                            <div class="amount-main"><?= number_format($order['total'], 2) ?> руб.</div>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="order-services">
                                        <?php if ($order['services_total'] > 0): ?>
                                            <span class="services-amount"><?= number_format($order['services_total'], 2) ?> руб.</span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="order-parts">
                                        <?php if ($order['parts_total'] > 0): ?>
                                            <span class="parts-amount"><?= number_format($order['parts_total'], 2) ?> руб.</span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="order_edit.php?id=<?= $order['id'] ?>" 
                                               class="action-btn edit" title="Редактировать">
                                                ✏️
                                            </a>
                                            <a href="order_print.php?id=<?= $order['id'] ?>" 
                                               class="action-btn print" title="Печать" target="_blank">
                                                🖨️
                                            </a>
                                            <form method="post" class="delete-form">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                <button type="submit" name="delete_order" 
                                                        class="action-btn delete" 
                                                        title="Удалить"
                                                        onclick="return confirm('Вы уверены, что хотите удалить заказ #<?= $order['id'] ?>?')">
                                                    🗑️
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="no-orders">
                    <div class="no-orders-content">
                        <div class="no-orders-icon">📋</div>
                        <h5 class="no-orders-text">Нет заказов</h5>
                        <p>Создайте первый заказ используя кнопку "Новый заказ"</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="assets/js/orders.js"></script>
    <?php include 'templates/footer.php'; ?>
</body>
</html>
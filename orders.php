<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
//requireAuth();

// Параметры фильтрации
$status_filter = $_GET['status'] ?? '';
$search_query = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// ПРОСТОЙ ЗАПРОС БЕЗ СЛОЖНЫХ ФИЛЬТРОВ
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($status_filter)) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(" AND ", $where_conditions);
}

// Простой запрос заказов - ДОБАВЛЕНО ОПИСАНИЕ УСЛУГ
$orders_sql = "
    SELECT o.id, o.created, o.description, o.status, o.total, 
           c.make, c.model, c.license_plate,
           cl.name AS client_name, cl.phone as client_phone,
           GROUP_CONCAT(DISTINCT os.service_name SEPARATOR ', ') as services_list
    FROM orders o
    JOIN cars c ON o.car_id = c.id
    JOIN clients cl ON c.client_id = cl.id
    LEFT JOIN order_services os ON o.id = os.order_id
    $where_sql
    GROUP BY o.id
    ORDER BY o.id DESC
    LIMIT 50
";

$stmt = $conn->prepare($orders_sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$orders_result = $stmt->get_result();
$orders = $orders_result->fetch_all(MYSQLI_ASSOC);

// Простая статистика
$stats_sql = "SELECT COUNT(*) as total_orders FROM orders o $where_sql";
$stats_stmt = $conn->prepare($stats_sql);
if (!empty($params)) {
    $stats_stmt->bind_param($param_types, ...$params);
}
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

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
            <a href="create_order.php" class="btn-1c-primary">+ Новый заказ</a>
        </div>

        <!-- Простые фильтры -->
        <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <form method="get">
                <div style="display: flex; gap: 15px; align-items: end;">
                    <div>
                        <label style="display: block; margin-bottom: 5px;">Статус</label>
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="">Все статусы</option>
                            <option value="В ожидании" <?= $status_filter == 'В ожидании' ? 'selected' : '' ?>>В ожидании</option>
                            <option value="В работе" <?= $status_filter == 'В работе' ? 'selected' : '' ?>>В работе</option>
                            <option value="Готов" <?= $status_filter == 'Готов' ? 'selected' : '' ?>>Готов</option>
                            <option value="Выдан" <?= $status_filter == 'Выдан' ? 'selected' : '' ?>>Выдан</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn-1c-primary">Применить</button>
                        <a href="orders.php" class="btn-1c">Сбросить</a>
                    </div>
                    <div style="margin-left: auto; color: #666;">
                        Найдено: <?= $stats['total_orders'] ?? 0 ?> заказов
                    </div>
                </div>
            </form>
        </div>

        <!-- Список заказов -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">
                <span class="card-header-icon">≡</span> Список заказов
            </div>
            <div class="card-body">
                <?php if (!empty($orders)): ?>
                <div class="orders-table-container">
                    <table class="orders-table-enhanced">
                        <thead>
                            <tr>
                                <th>№ Заказа</th>
                                <th>Дата</th>
                                <th>Клиент</th>
                                <th>Автомобиль</th>
                                <th>Проблема</th>
                                <th>Услуги</th>
                                <th>Статус</th>
                                <th>Сумма</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <a href="order_edit.php?id=<?= $order['id'] ?>" 
                                           onclick="logOrderView(<?= $order['id'] ?>)">№<?= $order['id'] ?></a>
                                    </td>
                                    <td><?= date('d.m.Y', strtotime($order['created'])) ?></td>
                                    <td>
                                        <div><?= htmlspecialchars($order['client_name']) ?></div>
                                        <small><?= htmlspecialchars($order['client_phone']) ?></small>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($order['make']) ?> <?= htmlspecialchars($order['model']) ?></div>
                                        <?php if (!empty($order['license_plate'])): ?>
                                            <small><?= $order['license_plate'] ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($order['description']) ?></td>
                                    <td>
                                        <?php if (!empty($order['services_list'])): ?>
                                            <div style="max-width: 200px; font-size: 0.9em;">
                                                <?= htmlspecialchars($order['services_list']) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge-enhanced 
                                            <?= $order['status'] == 'В ожидании' ? 'waiting' : '' ?>
                                            <?= $order['status'] == 'В работе' ? 'working' : '' ?>
                                            <?= $order['status'] == 'Готов' ? 'completed' : '' ?>
                                            <?= $order['status'] == 'Выдан' ? 'diagnosis' : '' ?>
                                        ">
                                            <?= $order['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($order['total'] > 0): ?>
                                            <?= number_format($order['total'], 2) ?> руб.
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="order_edit.php?id=<?= $order['id'] ?>" 
                                           class="btn-1c-outline btn-small"
                                           onclick="logOrderEdit(<?= $order['id'] ?>)">✏️</a>
                                        <a href="order_print.php?id=<?= $order['id'] ?>" 
                                           class="btn-1c-outline btn-small" 
                                           target="_blank"
                                           onclick="logOrderPrint(<?= $order['id'] ?>)">🖨️</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 40px;">
                    <div style="font-size: 48px; margin-bottom: 20px;">📋</div>
                    <h5>Заказы не найдены</h5>
                    <p>Попробуйте изменить параметры фильтра</p>
                    <a href="create_order.php" class="btn-1c-primary">➕ Создать заказ</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>

    <script>
    // Функции для логирования действий через AJAX
    function logOrderView(orderId) {
        fetch('log_action.php?action=view&module=orders&record_id=' + orderId)
            .catch(err => console.log('Log error:', err));
    }
    
    function logOrderEdit(orderId) {
        fetch('log_action.php?action=edit&module=orders&record_id=' + orderId)
            .catch(err => console.log('Log error:', err));
    }
    
    function logOrderPrint(orderId) {
        fetch('log_action.php?action=print&module=orders&record_id=' + orderId)
            .catch(err => console.log('Log error:', err));
    }
    </script>
</body>
</html>
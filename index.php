<?php
// index.php - ИСПРАВЛЕННАЯ ВЕРСИЯ
session_start();
require_once 'includes/db.php';
require_once 'auth_check.php';

// ============================================================================
// ФУНКЦИИ, СООТВЕТСТВУЮЩИЕ РЕАЛЬНОЙ СТРУКТУРЕ БАЗЫ
// ============================================================================

function getTotalClients($pdo) {
    $sql = "SELECT COUNT(*) as count FROM clients";
    $stmt = $pdo->query($sql);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

function getActiveOrdersCount($pdo) {
    $sql = "SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'in_progress')";
    $stmt = $pdo->query($sql);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

function getCarsInProgress($pdo) {
    $sql = "SELECT COUNT(DISTINCT car_id) as count FROM orders WHERE status = 'in_progress'";
    $stmt = $pdo->query($sql);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

function getUrgentTasksCount($pdo) {
    $sql = "SELECT COUNT(*) as count FROM orders WHERE DATE(created) = CURDATE()";
    $stmt = $pdo->query($sql);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

function getMonthlyRevenue($pdo) {
    $sql = "SELECT SUM(total) as revenue FROM orders WHERE status = 'completed' AND MONTH(created) = MONTH(NOW())";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['revenue'] ?: 0;
}

function getLowStockItems($pdo) {
    $sql = "SELECT COUNT(*) as count FROM warehouse_items WHERE quantity <= min_quantity AND min_quantity > 0";
    $stmt = $pdo->query($sql);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

function getRecentOrders($pdo, $limit = 5) {
    // УПРОЩЕННЫЙ ЗАПРОС - только данные из orders, без JOIN
    $sql = "SELECT o.* FROM orders o ORDER BY o.created DESC LIMIT ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ============================================================================
// ПОЛУЧАЕМ РЕАЛЬНЫЕ ДАННЫЕ
// ============================================================================

$stats = [
    'total_clients' => getTotalClients($pdo),
    'active_orders' => getActiveOrdersCount($pdo),
    'cars_in_progress' => getCarsInProgress($pdo),
    'urgent_tasks' => getUrgentTasksCount($pdo),
    'monthly_revenue' => getMonthlyRevenue($pdo),
    'low_stock' => getLowStockItems($pdo)
];

$recent_orders = getRecentOrders($pdo);

// Включаем шапку
$page_title = "ERP Дашборд - Autoservice";
include 'templates/header.php';
?>

<!-- Основной контент -->
<div class="content-container">
    <!-- Заголовок и быстрые действия -->
    <div class="header-compact">
        <h1 class="page-title-compact">🏠 ERP Дашборд</h1>
        <div class="header-actions-compact">
            <a href="booking.php" class="action-btn-compact">
                <span class="action-icon">📅</span>
                <span class="action-label">Запись</span>
            </a>
            <a href="orders.php?action=create" class="action-btn-compact primary">
                <span class="action-icon">➕</span>
                <span class="action-label">Заказ</span>
            </a>
        </div>
    </div>

    <!-- Статистика в реальном времени -->
    <div class="row-1c">
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <h3><?= number_format($stats['monthly_revenue'], 0, ',', ' ') ?> ₽</h3>
                <p>Доход за месяц</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <h3><?= $stats['total_clients'] ?></h3>
                <p>Всего клиентов</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-content">
                <h3><?= $stats['active_orders'] ?></h3>
                <p>Текущих заказов</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🚗</div>
            <div class="stat-content">
                <h3><?= $stats['cars_in_progress'] ?></h3>
                <p>Авто в работе</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⚠️</div>
            <div class="stat-content">
                <h3><?= $stats['urgent_tasks'] ?></h3>
                <p>Срочных задач</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-content">
                <h3><?= $stats['low_stock'] ?></h3>
                <p>Низкий запас</p>
            </div>
        </div>
    </div>

    <div class="row-1c">
        <!-- Текущие заказы -->
        <div class="main-section">
            <div class="card-1c">
                <div class="card-header-1c">
                    <h5>📋 Последние заказы</h5>
                    <a href="orders.php" class="btn-1c">Все заказы</a>
                </div>
                <div class="card-content">
                    <div class="orders-table-container">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th class="col-order">Заказ</th>
                                    <th class="col-description">Описание</th>
                                    <th class="col-status">Статус</th>
                                    <th class="col-total">Сумма</th>
                                    <th class="col-date">Создан</th>
                                    <th class="col-actions">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                <tr class="order-row">
                                    <td class="order-number">
                                        <a href="orders.php?id=<?= $order['id'] ?>" class="order-link">
                                            <span class="order-prefix">#</span><?= $order['id'] ?>
                                        </a>
                                    </td>
                                    <td class="order-description">
                                        <div class="description-text"><?= htmlspecialchars(substr($order['description'] ?? 'Без описания', 0, 50)) ?></div>
                                        <div class="car-id">Авто ID: <?= $order['car_id'] ?></div>
                                    </td>
                                    <td class="order-status">
                                        <span class="status-badge with-icon <?= $order['status'] ?>">
                                            <span class="status-icon">
                                                <?= $order['status'] == 'in_progress' ? '🔧' : 
                                                   ($order['status'] == 'pending' ? '⏳' : '✅') ?>
                                            </span>
                                            <?= $order['status'] == 'in_progress' ? 'В работе' : 
                                               ($order['status'] == 'pending' ? 'Ожидание' : 'Завершен') ?>
                                        </span>
                                    </td>
                                    <td class="order-total">
                                        <strong><?= number_format($order['total'] ?? 0, 0, ',', ' ') ?> ₽</strong>
                                    </td>
                                    <td class="order-date">
                                        <div class="date-main"><?= date('d.m', strtotime($order['created'])) ?></div>
                                        <div class="date-sub"><?= date('H:i', strtotime($order['created'])) ?></div>
                                    </td>
                                    <td class="order-actions">
                                        <a href="orders.php?id=<?= $order['id'] ?>" class="action-btn view" title="Просмотр">
                                            👁️
                                        </a>
                                        <a href="orders.php?action=edit&id=<?= $order['id'] ?>" class="action-btn edit" title="Редактировать">
                                            ✏️
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Быстрые действия -->
        <div class="sidebar-section">
            <div class="card-1c compact-card">
                <div class="card-header-1c compact-header">
                    <h5>⚡ Быстрые действия</h5>
                </div>
                <div class="card-content compact-content">
                    <div class="quick-actions-grid">
                        <a href="clients.php?action=create" class="quick-action">
                            <span class="action-icon">👤</span>
                            <span class="action-text">Клиент</span>
                        </a>
                        <a href="cars.php?action=create" class="quick-action">
                            <span class="action-icon">🚗</span>
                            <span class="action-text">Авто</span>
                        </a>
                        <a href="warehouse.php" class="quick-action">
                            <span class="action-icon">📦</span>
                            <span class="action-text">Склад</span>
                        </a>
                        <a href="reports.php" class="quick-action">
                            <span class="action-icon">📊</span>
                            <span class="action-text">Отчет</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
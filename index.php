<?php
// index.php - ФИНАЛЬНАЯ ВЕРСИЯ
session_start();
require_once 'includes/db.php';
require_once 'auth_check.php';

// ============================================================================
// ФУНКЦИИ
// ============================================================================

function getTotalClients($conn) {
    $sql = "SELECT COUNT(*) as count FROM clients";
    $result = $conn->query($sql);
    if (!$result) return 0;
    return $result->fetch_assoc()['count'];
}

function getActiveOrdersCount($conn) {
    $sql = "SELECT COUNT(*) as count FROM orders WHERE status IN ('В ожидании', 'В работе')";
    $result = $conn->query($sql);
    if (!$result) return 0;
    return $result->fetch_assoc()['count'];
}

function getCarsInProgress($conn) {
    $sql = "SELECT COUNT(DISTINCT car_id) as count FROM orders WHERE status = 'В работе'";
    $result = $conn->query($sql);
    if (!$result) return 0;
    return $result->fetch_assoc()['count'];
}

function getUrgentTasksCount($conn) {
    $sql = "SELECT COUNT(*) as count FROM orders WHERE DATE(created) = CURDATE()";
    $result = $conn->query($sql);
    if (!$result) return 0;
    return $result->fetch_assoc()['count'];
}

function getMonthlyRevenue($conn) {
    $sql = "SELECT SUM(total) as revenue FROM orders WHERE status = 'Готов' AND MONTH(created) = MONTH(NOW())";
    $result = $conn->query($sql);
    if (!$result) return 0;
    $row = $result->fetch_assoc();
    return $row['revenue'] ?: 0;
}

function getLowStockItems($conn) {
    $result = $conn->query("SHOW TABLES LIKE 'warehouse_items'");
    if ($result->num_rows == 0) return 0;
    
    $sql = "SELECT COUNT(*) as count FROM warehouse_items WHERE quantity <= min_quantity AND min_quantity > 0";
    $result = $conn->query($sql);
    if (!$result) return 0;
    return $result->fetch_assoc()['count'];
}

function getRecentOrders($conn, $limit = 5) {
    $sql = "SELECT o.* FROM orders o WHERE o.status IN ('В ожидании', 'В работе') ORDER BY o.created DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    $stmt->bind_param('i', $limit);
    if (!$stmt->execute()) return [];
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// ============================================================================
// ПОЛУЧАЕМ РЕАЛЬНЫЕ ДАННЫЕ
// ============================================================================

$stats = [
    'total_clients' => getTotalClients($conn),
    'active_orders' => getActiveOrdersCount($conn),
    'cars_in_progress' => getCarsInProgress($conn),
    'urgent_tasks' => getUrgentTasksCount($conn),
    'monthly_revenue' => getMonthlyRevenue($conn),
    'low_stock' => getLowStockItems($conn)
];

$recent_orders = getRecentOrders($conn);

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
            <a href="create_order.php" class="action-btn-compact primary">
                <span class="action-icon">➕</span>
                <span class="action-label">Заказ</span>
            </a>
        </div>
    </div>

    <!-- Информация о заказах -->
    <div style="background: #e8f4fd; padding: 15px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #3498db;">
        <strong>📊 Статистика заказов:</strong><br>
        Всего заказов в базе: <?= $stats['active_orders'] + getMonthlyRevenue($conn) ?><br>
        • В ожидании: <?= $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'В ожидании'")->fetch_assoc()['count'] ?><br>
        • В работе: <?= $stats['cars_in_progress'] ?><br>
        • Готово: <?= $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Готов'")->fetch_assoc()['count'] ?>
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

    <div style="margin: 20px 0;">
        <a href="orders.php" class="btn-1c">📋 Все заказы</a>
        <a href="create_order.php" class="btn-1c-primary">➕ Новый заказ</a>
    </div>

    <!-- Быстрые действия -->
    <div class="row-1c">
        <div class="main-section">
            <div class="card-1c">
                <div class="card-header-1c">
                    <h5>⚡ Быстрые действия</h5>
                </div>
                <div class="card-content">
                    <div class="quick-actions-grid" style="grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));">
                        <a href="add_client.php" class="quick-action">
                            <span class="action-icon">👤</span>
                            <span class="action-text">Клиент</span>
                        </a>
                        <a href="add_car.php" class="quick-action">
                            <span class="action-icon">🚗</span>
                            <span class="action-text">Авто</span>
                        </a>
                        <a href="warehouse.php" class="quick-action">
                            <span class="action-icon">📦</span>
                            <span class="action-text">Склад</span>
                        </a>
                        <a href="order_print.php" class="quick-action">
                            <span class="action-icon">🖨️</span>
                            <span class="action-text">Печать</span>
                        </a>
                        <a href="full_export.php" class="quick-action">
                            <span class="action-icon">📤</span>
                            <span class="action-text">Экспорт</span>
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
<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAuth();

// Параметры фильтрации
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Статистика
$stats_sql = "SELECT 
    COUNT(*) as total_orders,
    SUM(total_price) as total_revenue,
    AVG(total_price) as avg_order,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_orders
    FROM order_tire_services 
    WHERE DATE(created_at) BETWEEN ? AND ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("ss", $date_from, $date_to);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Популярные радиусы
$radius_sql = "SELECT radius, COUNT(*) as count 
               FROM order_tire_services 
               WHERE DATE(created_at) BETWEEN ? AND ?
               GROUP BY radius 
               ORDER BY count DESC";
$radius_stmt = $conn->prepare($radius_sql);
$radius_stmt->bind_param("ss", $date_from, $date_to);
$radius_stmt->execute();
$radius_stats = $radius_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Ежедневная статистика
$daily_sql = "SELECT DATE(created_at) as date, COUNT(*) as orders, SUM(total_price) as revenue
              FROM order_tire_services 
              WHERE DATE(created_at) BETWEEN ? AND ?
              GROUP BY DATE(created_at) 
              ORDER BY date";
$daily_stmt = $conn->prepare($daily_sql);
$daily_stmt->bind_param("ss", $date_from, $date_to);
$daily_stmt->execute();
$daily_stats = $daily_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include 'templates/header.php';
?>

<div class="container">
    <div class="header-actions">
        <h1>📊 Статистика шиномонтажа</h1>
        <div class="action-buttons">
            <a href="tire_orders.php" class="btn-1c">← Назад к заказам</a>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="card-1c">
        <div class="card-header-1c">
            <span class="card-header-icon">📅</span> Период
        </div>
        <div class="card-body">
            <form method="get" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Дата с</label>
                        <input type="date" name="date_from" value="<?= $date_from ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Дата по</label>
                        <input type="date" name="date_to" value="<?= $date_to ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn-1c-primary">Применить</button>
                        <a href="tire_stats.php" class="btn-1c">Сбросить</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Основная статистика -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $stats['total_orders'] ?? 0 ?></div>
            <div class="stat-label">Всего заказов</div>
        </div>
        <div class="stat-card success">
            <div class="stat-number"><?= number_format($stats['total_revenue'] ?? 0, 0) ?> ₽</div>
            <div class="stat-label">Общая выручка</div>
        </div>
        <div class="stat-card info">
            <div class="stat-number"><?= number_format($stats['avg_order'] ?? 0, 0) ?> ₽</div>
            <div class="stat-label">Средний чек</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-number"><?= $stats['completed_orders'] ?? 0 ?></div>
            <div class="stat-label">Выполнено</div>
        </div>
    </div>

    <div class="row-1c">
        <!-- Популярные радиусы -->
        <div class="card-1c">
            <div class="card-header-1c">
                <span class="card-header-icon">🛞</span> Популярность радиусов
            </div>
            <div class="card-body">
                <?php if (!empty($radius_stats)): ?>
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Радиус</th>
                                <th>Кол-во заказов</th>
                                <th>Доля</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($radius_stats as $radius): ?>
                            <tr>
                                <td><strong>R<?= $radius['radius'] ?></strong></td>
                                <td><?= $radius['count'] ?></td>
                                <td><?= round(($radius['count'] / $stats['total_orders']) * 100, 1) ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted">Нет данных за выбранный период</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ежедневная статистика -->
        <div class="card-1c">
            <div class="card-header-1c">
                <span class="card-header-icon">📈</span> Ежедневная статистика
            </div>
            <div class="card-body">
                <?php if (!empty($daily_stats)): ?>
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Заказы</th>
                                <th>Выручка</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daily_stats as $day): ?>
                            <tr>
                                <td><?= date('d.m.Y', strtotime($day['date'])) ?></td>
                                <td><?= $day['orders'] ?></td>
                                <td><?= number_format($day['revenue'], 0) ?> ₽</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted">Нет данных за выбранный период</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
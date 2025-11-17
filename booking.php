<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';


// Параметры фильтрации записей
$search_query = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$service_filter = $_GET['service'] ?? '';

// Построение WHERE условия для записей
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($service_filter)) {
    $where_conditions[] = "b.service_name LIKE ?";
    $params[] = "%$service_filter%";
    $param_types .= 's';
}

if (!empty($search_query)) {
    $where_conditions[] = "(b.name LIKE ? OR b.phone LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= 'ss';
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(b.date) >= ?";
    $params[] = $date_from;
    $param_types .= 's';
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(b.date) <= ?";
    $params[] = $date_to;
    $param_types .= 's';
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(" AND ", $where_conditions);
}

// Запрос записей - исправлен под реальную структуру
$bookings_sql = "
    SELECT b.*
    FROM bookings b
    $where_sql
    ORDER BY b.date DESC, b.time DESC
    LIMIT 50
";

$stmt = $conn->prepare($bookings_sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$bookings_result = $stmt->get_result();
$bookings = $bookings_result->fetch_all(MYSQLI_ASSOC);

// Статистика
$stats_sql = "SELECT COUNT(*) as total_bookings FROM bookings b $where_sql";
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
    <title>Запись на обслуживание</title>
    <link href="assets/css/orders.css" rel="stylesheet">
    <style>
    .booking-status {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        display: inline-block;
        text-align: center;
        min-width: 100px;
    }
    
    .status-new { background: #cce7ff; color: #004085; border: 1px solid #b3d7ff; }
    .status-confirmed { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .status-cancelled { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    
    .service-badge {
        background: #e9ecef;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        color: #495057;
    }
    
    .datetime-cell {
        min-width: 120px;
    }
    
    .filters-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 200px;
        gap: 15px;
        align-items: end;
    }
    
    @media (max-width: 1200px) {
        .filters-grid { grid-template-columns: 1fr 1fr; }
    }
    
    @media (max-width: 768px) {
        .filters-grid { grid-template-columns: 1fr; }
    }
    </style>
</head>
<body>
    <div class="orders-container">
        <div class="container-header">
            <h1 class="page-title">📅 Запись на обслуживание</h1>
            <a href="unified_booking.php" class="btn-1c-primary">+ Новая запись</a>
        </div>

        <!-- Фильтры -->
        <div class="filters-section">
            <form method="get" id="filtersForm">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label">Тип услуги</label>
                        <select name="service" class="form-control">
                            <option value="">Все услуги</option>
                            <option value="диагностика" <?= $service_filter == 'диагностика' ? 'selected' : '' ?>>Диагностика</option>
                            <option value="техническое обслуживание" <?= $service_filter == 'техническое обслуживание' ? 'selected' : '' ?>>ТО</option>
                            <option value="ремонт" <?= $service_filter == 'ремонт' ? 'selected' : '' ?>>Ремонт</option>
                            <option value="шиномонтаж" <?= $service_filter == 'шиномонтаж' ? 'selected' : '' ?>>Шиномонтаж</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Дата с</label>
                        <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" class="form-control">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Дата по</label>
                        <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" class="form-control">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Поиск</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" 
                               class="form-control" placeholder="Имя, телефон...">
                    </div>
                </div>
                
                <div style="margin-top: 15px; display: flex; gap: 10px;">
                    <button type="submit" class="btn-1c-primary">Применить фильтры</button>
                    <a href="booking.php" class="btn-1c">Сбросить</a>
                    <span style="margin-left: auto; color: #666; align-self: center;">
                        Найдено: <?= $stats['total_bookings'] ?? 0 ?> записей
                    </span>
                </div>
            </form>
        </div>

        <!-- Список записей -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">
                <span class="card-header-icon">📅</span> Предварительные записи
            </div>
            <div class="card-body">
                <?php if (!empty($bookings)): ?>
                <div class="orders-table-container">
                    <table class="orders-table-enhanced">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Клиент</th>
                                <th>Телефон</th>
                                <th>Услуга</th>
                                <th>Дата</th>
                                <th>Время</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td>#<?= $booking['id'] ?></td>
                                    <td>
                                        <div class="client-name"><?= htmlspecialchars($booking['name']) ?></div>
                                    </td>
                                    <td>
                                        <div class="client-phone"><?= htmlspecialchars($booking['phone']) ?></div>
                                    </td>
                                    <td>
                                        <span class="service-badge">
                                            <?= htmlspecialchars($booking['service_name']) ?>
                                        </span>
                                    </td>
                                    <td class="datetime-cell">
                                        <div class="date-main"><?= date('d.m.Y', strtotime($booking['date'])) ?></div>
                                    </td>
                                    <td>
                                        <div class="time-main"><?= htmlspecialchars($booking['time']) ?></div>
                                    </td>
                                    <td>
                                        <span class="booking-status status-new">
                                            Новая
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn confirm" title="Подтвердить">✅</button>
                                            <button class="action-btn cancel" title="Отменить">❌</button>
                                            <button class="action-btn call" title="Позвонить">📞</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 40px;">
                    <div style="font-size: 48px; margin-bottom: 20px;">📅</div>
                    <h5>Записи не найдены</h5>
                    <p>Создайте первую запись на обслуживание</p>
                    <a href="unified_booking.php" class="btn-1c-primary">➕ Создать запись</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    // Авто-сабмит формы при изменении фильтров
    document.querySelectorAll('select, input[type="date"]').forEach(element => {
        element.addEventListener('change', function() {
            document.getElementById('filtersForm').submit();
        });
    });
    
    // Поиск с задержкой
    let searchTimeout;
    document.querySelector('input[name="search"]').addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            document.getElementById('filtersForm').submit();
        }, 800);
    });

    // Действия с записями
    document.querySelectorAll('.action-btn.confirm').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Подтвердить запись?')) {
                // TODO: реализовать подтверждение
                alert('Запись подтверждена!');
            }
        });
    });

    document.querySelectorAll('.action-btn.cancel').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Отменить запись?')) {
                // TODO: реализовать отмену
                alert('Запись отменена!');
            }
        });
    });

    document.querySelectorAll('.action-btn.call').forEach(btn => {
        btn.addEventListener('click', function() {
            const phone = this.closest('tr').querySelector('.client-phone').textContent.trim();
            window.open('tel:' + phone);
        });
    });
    </script>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
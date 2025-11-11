<?php
session_start();
require_once 'includes/db.php';

// Проверяем, есть ли администраторы в системе
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND is_active = 1");
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result['count'] == 0) {
    // Перенаправляем на страницу первоначальной настройки
    header('Location: initial_setup.php');
    exit;
}

// Включаем шапку
$page_title = "Главная - Autoservice";
include 'templates/header.php';
?>

<!-- Основной контент -->
<div class="content-container">
        <!-- Заголовок и быстрые действия -->
    <div class="header-compact">
        <h1 class="page-title-compact">Главная панель</h1>
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
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <h3>154</h3>
                <p>Активных клиентов</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-content">
                <h3>23</h3>
                <p>Текущих заказов</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🚗</div>
            <div class="stat-content">
                <h3>8</h3>
                <p>Авто в работе</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⚠️</div>
            <div class="stat-content">
                <h3>3</h3>
                <p>Срочных задач</p>
            </div>
        </div>
    </div>

    <div class="row-1c">
        <!-- Текущие заказы -->
        <div class="main-section">
            <div class="card-1c">
                <div class="card-header-1c">
                    <h5>Текущие заказы</h5>
                    <a href="orders.php" class="btn-1c">Все заказы</a>
                </div>
                <div class="card-content">
    <div class="orders-table-container">
        <table class="orders-table">
            <thead>
                <tr>
                    <th class="col-order">Заказ</th>
                    <th class="col-client">Клиент</th>
                    <th class="col-car">Автомобиль</th>
                    <th class="col-status">Статус</th>
                    <th class="col-date">Срок</th>
                    <th class="col-actions">Действия</th>
                </tr>
            </thead>
            <tbody>
                <tr class="order-row">
                    <td class="order-number">
                        <a href="orders.php?id=1052" class="order-link">
                            <span class="order-prefix">#</span>1052
                        </a>
                    </td>
                    <td class="client-info">
                        <div class="client-name">Иванов А.В.</div>
                        <div class="client-phone">+7 (912) 345-67-89</div>
                    </td>
                    <td class="car-info">
                        <div class="car-model">Toyota Camry</div>
                        <div class="car-year">2020 • Серебристый</div>
                    </td>
                    <td class="order-status">
                        <span class="status-badge with-icon working">
                            <span class="status-icon">🔧</span>
                            В работе
                        </span>
                    </td>
                    <td class="order-date">
                        <div class="date-main">25 окт</div>
                        <div class="date-sub">до 18:00</div>
                    </td>
                    <td class="order-actions">
                        <a href="orders.php?id=1052" class="action-btn view" title="Просмотр">
                            👁️
                        </a>
                        <a href="orders.php?action=edit&id=1052" class="action-btn edit" title="Редактировать">
                            ✏️
                        </a>
                    </td>
                </tr>
                <tr class="order-row">
                    <td class="order-number">
                        <a href="orders.php?id=1051" class="order-link">
                            <span class="order-prefix">#</span>1051
                        </a>
                    </td>
                    <td class="client-info">
                        <div class="client-name">Петров С.И.</div>
                        <div class="client-phone">+7 (923) 456-78-90</div>
                    </td>
                    <td class="car-info">
                        <div class="car-model">Honda Civic</div>
                        <div class="car-year">2019 • Красный</div>
                    </td>
                    <td class="order-status">
                        <span class="status-badge with-icon waiting">
                            <span class="status-icon">⏳</span>
                            Ожидает запчасти
                        </span>
                    </td>
                    <td class="order-date">
                        <div class="date-main">26 окт</div>
                        <div class="date-sub">до 16:00</div>
                    </td>
                    <td class="order-actions">
                        <a href="orders.php?id=1051" class="action-btn view" title="Просмотр">
                            👁️
                        </a>
                        <a href="orders.php?action=edit&id=1051" class="action-btn edit" title="Редактировать">
                            ✏️
                        </a>
                    </td>
                </tr>
                <tr class="order-row">
                    <td class="order-number">
                        <a href="orders.php?id=1050" class="order-link">
                            <span class="order-prefix">#</span>1050
                        </a>
                    </td>
                    <td class="client-info">
                        <div class="client-name">Сидорова М.К.</div>
                        <div class="client-phone">+7 (934) 567-89-01</div>
                    </td>
                    <td class="car-info">
                        <div class="car-model">BMW X5</div>
                        <div class="car-year">2021 • Черный</div>
                    </td>
                    <td class="order-status">
                        <span class="status-badge with-icon diagnosis">
                            <span class="status-icon">🔍</span>
                            Диагностика
                        </span>
                    </td>
                    <td class="order-date">
                        <div class="date-main">24 окт</div>
                        <div class="date-sub">до 15:00</div>
                    </td>
                    <td class="order-actions">
                        <a href="orders.php?id=1050" class="action-btn view" title="Просмотр">
                            👁️
                        </a>
                        <a href="orders.php?action=edit&id=1050" class="action-btn edit" title="Редактировать">
                            ✏️
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
                </div>
            </div>
        </div>

        <!-- Ближайшие записи и действия -->
<div class="sidebar-section">
    <!-- Ближайшие записи -->
    <div class="card-1c compact-card">
        <div class="card-header-1c compact-header">
            <h5>📅 Ближайшие записи</h5>
        </div>
        <div class="card-content compact-content">
            <div class="compact-appointment">
                <div class="appointment-main">
                    <strong>Замена масла</strong>
                    <span class="appointment-time">Сегодня, 14:30</span>
                </div>
                <div class="appointment-details">Иванов А.В. (Toyota Camry)</div>
            </div>
            <div class="compact-appointment">
                <div class="appointment-main">
                    <strong>Диагностика подвески</strong>
                    <span class="appointment-time">Завтра, 10:00</span>
                </div>
                <div class="appointment-details">Петров С.И. (Honda Civic)</div>
            </div>
            <div class="compact-actions">
                <a href="booking.php" class="btn-1c small">Все записи</a>
            </div>
        </div>
    </div>

    <!-- Быстрые действия -->
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
                <a href="tasks.php" class="quick-action">
                    <span class="action-icon">✅</span>
                    <span class="action-text">Задачи</span>
                </a>
                <a href="reports.php" class="quick-action">
                    <span class="action-icon">📊</span>
                    <span class="action-text">Отчет</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Уведомления -->
<div class="row-1c">
    <div class="full-width">
        <div class="card-1c compact-card">
            <div class="card-header-1c compact-header">
                <div class="header-with-badge">
                    <h5>🔔 Уведомления</h5>
                    <span class="notification-badge mini">3</span>
                </div>
            </div>
            <div class="card-content compact-content">
                <div class="notifications-list">
                    <div class="notification-item warning">
                        <span class="notification-icon">⚠️</span>
                        <span class="notification-text">Заказ #1048 ожидает подтверждения</span>
                    </div>
                    <div class="notification-item info">
                        <span class="notification-icon">ℹ️</span>
                        <span class="notification-text">Запчасти для #1051 поступят завтра</span>
                    </div>
                    <div class="notification-item danger">
                        <span class="notification-icon">🔴</span>
                        <span class="notification-text">Срочный заказ #1052 требует внимания</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

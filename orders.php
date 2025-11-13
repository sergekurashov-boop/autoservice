<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAuth();
define('ACCESS', true);

// Параметры фильтрации
$status_filter = $_GET['status'] ?? '';
$search_query = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;

// Построение WHERE условия
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($status_filter)) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if (!empty($search_query)) {
    $where_conditions[] = "(cl.name LIKE ? OR c.make LIKE ? OR c.model LIKE ? OR c.license_plate LIKE ? OR o.description LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= 'sssss';
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(o.created) >= ?";
    $params[] = $date_from;
    $param_types .= 's';
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(o.created) <= ?";
    $params[] = $date_to;
    $param_types .= 's';
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(" AND ", $where_conditions);
}

// Получение статистики
$stats = [];
$stats_result = $conn->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN o.status = 'В работе' THEN 1 ELSE 0 END) as active_orders,
        SUM(CASE WHEN o.status = 'Выполнен' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN o.status = 'В ожидании' THEN 1 ELSE 0 END) as pending_orders,
        SUM(o.total) as total_revenue,
        AVG(o.total) as avg_order_value
    FROM orders o
    $where_sql
");

if ($stats_result) {
    $stats = $stats_result->fetch_assoc();
}

// Получение заказов с пагинацией
$offset = ($page - 1) * $per_page;

$orders_sql = "
    SELECT o.id, o.created, o.description, o.status, o.total, 
           o.services_total, o.parts_total,
           c.make, c.model, c.license_plate,
           cl.name AS client_name, cl.phone as client_phone
    FROM orders o
    JOIN cars c ON o.car_id = c.id
    JOIN clients cl ON c.client_id = cl.id
    $where_sql
    ORDER BY o.created DESC
    LIMIT $per_page OFFSET $offset
";

$stmt = $conn->prepare($orders_sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$orders_result = $stmt->get_result();
$orders = $orders_result->fetch_all(MYSQLI_ASSOC);

// Общее количество для пагинации
$count_sql = "SELECT COUNT(*) as total FROM orders o JOIN cars c ON o.car_id = c.id JOIN clients cl ON c.client_id = cl.id $where_sql";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_count_result = $count_stmt->get_result();
$total_count = $total_count_result->fetch_assoc()['total'];
$total_pages = ceil($total_count / $per_page);

// Обработка удаления заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
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

        $stmt = $conn->prepare("DELETE FROM order_inspection_data WHERE order_id = ?");
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
    header("Location: orders.php?" . http_build_query($_GET));
    exit;
}

// Генерация CSRF токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include 'templates/header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление заказами</title>
    <link href="assets/css/orders.css" rel="stylesheet">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #007bff;
        }
        
        .stat-card.success { border-left-color: #28a745; }
        .stat-card.warning { border-left-color: #ffc107; }
        .stat-card.info { border-left-color: #17a2b8; }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .filters-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 200px;
            gap: 15px;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }
        
        .page-link {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #007bff;
        }
        
        .page-link.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .page-link:hover:not(.active) {
            background: #f8f9fa;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }
        
        .search-input {
            padding-left: 35px;
        }
        
        @media (max-width: 1200px) {
            .filters-row {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .filters-row {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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

        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_orders'] ?? 0 ?></div>
                <div class="stat-label">Всего заказов</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number"><?= $stats['active_orders'] ?? 0 ?></div>
                <div class="stat-label">В работе</div>
            </div>
            <div class="stat-card success">
                <div class="stat-number"><?= $stats['completed_orders'] ?? 0 ?></div>
                <div class="stat-label">Выполнено</div>
            </div>
            <div class="stat-card info">
                <div class="stat-number"><?= number_format($stats['total_revenue'] ?? 0, 2) ?> ₽</div>
                <div class="stat-label">Общая выручка</div>
            </div>
        </div>

        <!-- Фильтры -->
        <div class="filters-section">
            <form method="get" id="filtersForm">
                <div class="filters-row">
                    <div class="filter-group">
                        <label class="filter-label">Статус</label>
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="">Все статусы</option>
                            <option value="В ожидании" <?= $status_filter == 'В ожидании' ? 'selected' : '' ?>>В ожидании</option>
                            <option value="В работе" <?= $status_filter == 'В работе' ? 'selected' : '' ?>>В работе</option>
                            <option value="Готов" <?= $status_filter == 'Готов' ? 'selected' : '' ?>>Готов</option>
                            <option value="Выдан" <?= $status_filter == 'Выдан' ? 'selected' : '' ?>>Выдан</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Дата с</label>
                        <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" 
                               class="form-control" onchange="this.form.submit()">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Дата по</label>
                        <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" 
                               class="form-control" onchange="this.form.submit()">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Поиск</label>
                        <div class="search-box">
                            <span class="search-icon">🔍</span>
                            <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" 
                                   class="form-control search-input" placeholder="Клиент, авто, описание...">
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 15px; display: flex; gap: 10px;">
                    <button type="submit" class="btn-1c-primary">Применить фильтры</button>
                    <a href="orders.php" class="btn-1c">Сбросить</a>
                    <span style="margin-left: auto; color: #666; align-self: center;">
                        Найдено: <?= $total_count ?> заказов
                    </span>
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
                            <?php foreach ($orders as $order): ?>
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
                                        <small class="client-phone"><?= htmlspecialchars($order['client_phone']) ?></small>
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
                                            <a href="inspection.php?order_id=<?= $order['id'] ?>" 
                                               class="action-btn inspect" title="Осмотр авто">
                                                🔍
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
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Пагинация -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="orders.php?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                           class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <div class="no-orders">
                    <div class="no-orders-content">
                        <div class="no-orders-icon">📋</div>
                        <h5 class="no-orders-text">Заказы не найдены</h5>
                        <p>Попробуйте изменить параметры фильтра или создайте новый заказ</p>
                        <div class="mt-3">
                            <a href="create_order.php" class="btn-1c-primary">➕ Создать первый заказ</a>
                            <a href="orders.php" class="btn-1c">❌ Сбросить фильтры</a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    // Авто-сабмит формы поиска при вводе
    let searchTimeout;
    document.querySelector('input[name="search"]').addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            document.getElementById('filtersForm').submit();
        }, 500);
    });
    
    // Подтверждение удаления с номером заказа
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const orderId = this.querySelector('input[name="order_id"]').value;
            if (!confirm(`Вы уверены, что хотите удалить заказ #${orderId}?`)) {
                e.preventDefault();
            }
        });
    });
    </script>

    <script src="assets/js/orders.js"></script>
    <?php include 'templates/footer.php'; ?>
</body>
</html>
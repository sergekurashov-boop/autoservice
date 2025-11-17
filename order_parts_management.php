<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAuth();

if (!isset($_GET['order_id'])) {
    $_SESSION['error'] = 'Order ID not specified';
    header('Location: orders.php');
    exit;
}

$order_id = (int)$_GET['order_id'];

// Получаем информацию о заказе
$order_stmt = $conn->prepare("
    SELECT o.*, c.make, c.model, c.license_plate, cl.name as client_name 
    FROM orders o 
    JOIN cars c ON o.car_id = c.id 
    JOIN clients cl ON c.client_id = cl.id 
    WHERE o.id = ?
");
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['error'] = 'Order not found';
    header('Location: orders.php');
    exit;
}

// Получаем запчасти заказа
$parts_sql = "
    SELECT op.*, 
           p.name as part_name, 
           p.part_number,
           p.price as unit_price,
           w.name as warehouse_name
    FROM order_parts op 
    LEFT JOIN parts p ON op.part_id = p.id 
    LEFT JOIN warehouse_items w ON op.warehouse_item_id = w.id
    WHERE op.order_id = ?
    ORDER BY op.source_type, op.issue_status
";
$parts_stmt = $conn->prepare($parts_sql);
$parts_stmt->bind_param("i", $order_id);
$parts_stmt->execute();
$order_parts = $parts_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Группируем запчасти по типу
$warehouse_parts = array_filter($order_parts, function($part) {
    return $part['source_type'] == 'service_warehouse';
});

$client_parts = array_filter($order_parts, function($part) {
    return $part['source_type'] == 'client_provided';
});

function getStatusText($status) {
    $statuses = [
        'reserved' => '🟡 Зарезервировано',
        'issued' => '🔵 Выдано в ремонт',
        'used' => '🟢 Использовано',
        'returned' => '↩️ Возвращено'
    ];
    return $statuses[$status] ?? $status;
}

function getStatusClass($status) {
    $classes = [
        'reserved' => 'status-reserved',
        'issued' => 'status-issued',
        'used' => 'status-used',
        'returned' => 'status-returned'
    ];
    return $classes[$status] ?? '';
}

include 'templates/header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление запчастями - Заказ #<?= $order_id ?></title>
    <style>
        .parts-management-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .order-header {
            background: #fffef5;
            border: 1px solid #e6d8a8;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #8b6914;
        }
        
        .parts-section {
            background: #fffef5;
            border: 1px solid #e6d8a8;
            margin-bottom: 20px;
            border-radius: 0;
        }
        
        .section-header {
            background: #fff8dc;
            padding: 15px 20px;
            border-bottom: 1px solid #e6d8a8;
            font-weight: 600;
            color: #5c4a00;
        }
        
        .section-body {
            padding: 20px;
        }
        
        .part-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #f5f0d8;
            gap: 20px;
        }
        
        .part-item:last-child {
            border-bottom: none;
        }
        
        .part-info {
            flex: 1;
        }
        
        .part-name {
            font-weight: 600;
            color: #5c4a00;
            margin-bottom: 5px;
        }
        
        .part-details {
            font-size: 0.85rem;
            color: #8b6914;
        }
        
        .part-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
            min-width: 120px;
            text-align: center;
        }
        
        .status-reserved {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-issued {
            background: #cce7ff;
            color: #004085;
            border: 1px solid #b3d7ff;
        }
        
        .status-used {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-returned {
            background: #e2e3e5;
            color: #383d41;
            border: 1px solid #d6d8db;
        }
        
        .part-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 0.8rem;
            border: 1px solid #d4c49e;
            background: #fffef5;
            color: #5c4a00;
            cursor: pointer;
            text-decoration: none;
            border-radius: 0;
        }
        
        .btn-small:hover {
            background: #f5e8b0;
        }
        
        .btn-small.primary {
            background: #8b6914;
            color: white;
            border-color: #7a5a10;
        }
        
        .btn-small.primary:hover {
            background: #7a5a10;
        }
        
        .btn-small.danger {
            background: #dc3545;
            color: white;
            border-color: #c82333;
        }
        
        .btn-small.danger:hover {
            background: #c82333;
        }
        
        .no-parts {
            text-align: center;
            padding: 40px;
            color: #8b6914;
        }
        
        .navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e6d8a8;
        }
    </style>
</head>
<body>
    <div class="parts-management-container">
        <div class="order-header">
            <h1>📦 Управление запчастями</h1>
            <p>
                <strong>Заказ:</strong> #<?= $order_id ?> | 
                <strong>Клиент:</strong> <?= htmlspecialchars($order['client_name']) ?> | 
                <strong>Авто:</strong> <?= htmlspecialchars($order['make']) ?> <?= htmlspecialchars($order['model']) ?> 
                <?= !empty($order['license_plate']) ? ' ('.htmlspecialchars($order['license_plate']).')' : '' ?>
            </p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-enhanced alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-enhanced alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Запчасти со склада -->
        <div class="parts-section">
            <div class="section-header">
                🏭 Запчасти со склада сервиса
            </div>
            <div class="section-body">
                <?php if (!empty($warehouse_parts)): ?>
                    <?php foreach ($warehouse_parts as $part): ?>
                    <div class="part-item" id="part-<?= $part['part_id'] ?>">
                        <div class="part-info">
                            <div class="part-name">
                                <?= htmlspecialchars($part['part_name']) ?>
                                <?php if ($part['part_number']): ?>
                                    <small>(арт: <?= $part['part_number'] ?>)</small>
                                <?php endif; ?>
                            </div>
                            <div class="part-details">
                                Кол-во: <?= $part['quantity'] ?> | 
                                Цена: <?= number_format($part['unit_price'], 2) ?> руб. | 
                                Сумма: <?= number_format($part['unit_price'] * $part['quantity'], 2) ?> руб.
                                <?php if ($part['warehouse_name']): ?>
                                    | Склад: <?= $part['warehouse_name'] ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="part-controls">
                            <span class="status-badge <?= getStatusClass($part['issue_status']) ?>">
                                <?= getStatusText($part['issue_status']) ?>
                            </span>
                            <div class="part-actions">
                                <?php if ($part['issue_status'] == 'reserved'): ?>
                                    <button class="btn-small primary" onclick="updatePartStatus(<?= $part['order_id'] ?>, <?= $part['part_id'] ?>, 'issued')">
                                        Выдать
                                    </button>
                                    <button class="btn-small" onclick="updatePartStatus(<?= $part['order_id'] ?>, <?= $part['part_id'] ?>, 'returned')">
                                        Вернуть
                                    </button>
                                <?php elseif ($part['issue_status'] == 'issued'): ?>
                                    <button class="btn-small primary" onclick="updatePartStatus(<?= $part['order_id'] ?>, <?= $part['part_id'] ?>, 'used')">
                                        Использовано
                                    </button>
                                    <button class="btn-small" onclick="updatePartStatus(<?= $part['order_id'] ?>, <?= $part['part_id'] ?>, 'returned')">
                                        Вернуть
                                    </button>
                                <?php elseif ($part['issue_status'] == 'used'): ?>
                                    <span class="text-success">✅ Использовано</span>
                                <?php elseif ($part['issue_status'] == 'returned'): ?>
                                    <span class="text-muted">↩️ Возвращено</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-parts">
                        <p>📭 Запчасти со склада не добавлены</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Запчасти от клиента -->
        <div class="parts-section">
            <div class="section-header">
                👤 Запчасти от клиента
            </div>
            <div class="section-body">
                <?php if (!empty($client_parts)): ?>
                    <?php foreach ($client_parts as $part): ?>
                    <div class="part-item" id="part-<?= $part['part_id'] ?>">
                        <div class="part-info">
                            <div class="part-name">
                                <?= htmlspecialchars($part['part_name']) ?>
                                <?php if ($part['part_number']): ?>
                                    <small>(арт: <?= $part['part_number'] ?>)</small>
                                <?php endif; ?>
                            </div>
                            <div class="part-details">
                                Кол-во: <?= $part['quantity'] ?>
                            </div>
                        </div>
                        <div class="part-controls">
                            <span class="status-badge <?= getStatusClass($part['issue_status']) ?>">
                                <?= getStatusText($part['issue_status']) ?>
                            </span>
                            <div class="part-actions">
                                <?php if ($part['issue_status'] == 'reserved'): ?>
                                    <button class="btn-small primary" onclick="updatePartStatus(<?= $part['order_id'] ?>, <?= $part['part_id'] ?>, 'issued')">
                                        Выдать
                                    </button>
                                    <button class="btn-small danger" onclick="removeClientPart(<?= $part['order_id'] ?>, <?= $part['part_id'] ?>)">
                                        Удалить
                                    </button>
                                <?php elseif ($part['issue_status'] == 'issued'): ?>
                                    <button class="btn-small primary" onclick="updatePartStatus(<?= $part['order_id'] ?>, <?= $part['part_id'] ?>, 'used')">
                                        Использовано
                                    </button>
                                <?php elseif ($part['issue_status'] == 'used'): ?>
                                    <span class="text-success">✅ Использовано</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-parts">
                        <p>📭 Запчасти от клиента не добавлены</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="navigation">
            <a href="order_edit.php?id=<?= $order_id ?>" class="btn-1c">← Назад к заказу</a>
            <a href="orders.php" class="btn-1c">📋 К списку заказов</a>
        </div>
    </div>

    <script>
    function updatePartStatus(orderId, partId, newStatus) {
        if (!confirm('Изменить статус запчасти?')) return;
        
        fetch('update_part_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId,
                part_id: partId,
                new_status: newStatus,
                csrf_token: '<?= $_SESSION['csrf_token'] ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Ошибка: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Произошла ошибка при обновлении статуса');
        });
    }
    
    function removeClientPart(orderId, partId) {
        if (!confirm('Удалить запчасть от клиента?')) return;
        alert('Функция удаления запчасти от клиента будет реализована');
    }
    </script>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
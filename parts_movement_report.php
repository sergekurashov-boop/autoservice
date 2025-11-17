<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAuth();

// Параметры фильтрации
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$warehouse_id = $_GET['warehouse_id'] ?? '';
$part_id = $_GET['part_id'] ?? '';
$order_id = $_GET['order_id'] ?? '';

// Построение условий WHERE
$where_conditions = ["psl.changed_at BETWEEN ? AND ?"];
$params = [$date_from, $date_to . ' 23:59:59'];
$param_types = 'ss';

if (!empty($warehouse_id)) {
    $where_conditions[] = "op.warehouse_item_id = ?";
    $params[] = $warehouse_id;
    $param_types .= 'i';
}

if (!empty($part_id)) {
    $where_conditions[] = "op.part_id = ?";
    $params[] = $part_id;
    $param_types .= 'i';
}

if (!empty($order_id)) {
    $where_conditions[] = "psl.order_id = ?";
    $params[] = $order_id;
    $param_types .= 'i';
}

$where_sql = "WHERE " . implode(" AND ", $where_conditions);

// Получаем данные для отчета
$report_sql = "
    SELECT 
        psl.id,
        psl.order_id,
        psl.part_id,
        psl.old_status,
        psl.new_status,
        psl.changed_at,
        psl.changed_by,
        psl.notes,
        u.username as changed_by_name,
        p.name as part_name,
        p.part_number,
        op.quantity,
        op.source_type,
        w.name as warehouse_name,
        o.created as order_date,
        c.make as car_make,
        c.model as car_model,
        cl.name as client_name
    FROM part_status_log psl
    LEFT JOIN order_parts op ON psl.order_id = op.order_id AND psl.part_id = op.part_id
    LEFT JOIN parts p ON psl.part_id = p.id
    LEFT JOIN warehouse_items w ON op.warehouse_item_id = w.id
    LEFT JOIN users u ON psl.changed_by = u.id
    LEFT JOIN orders o ON psl.order_id = o.id
    LEFT JOIN cars c ON o.car_id = c.id
    LEFT JOIN clients cl ON c.client_id = cl.id
    $where_sql
    ORDER BY psl.changed_at DESC
";

$stmt = $conn->prepare($report_sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$report_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Получаем списки для фильтров
$warehouses = $conn->query("SELECT id, name FROM warehouse_items ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$parts = $conn->query("SELECT id, name, part_number FROM parts ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Статистика
$stats = [
    'total_movements' => count($report_data),
    'reserved_to_issued' => 0,
    'issued_to_used' => 0,
    'returns' => 0
];

foreach ($report_data as $record) {
    if ($record['old_status'] == 'reserved' && $record['new_status'] == 'issued') {
        $stats['reserved_to_issued']++;
    }
    if ($record['old_status'] == 'issued' && $record['new_status'] == 'used') {
        $stats['issued_to_used']++;
    }
    if ($record['new_status'] == 'returned') {
        $stats['returns']++;
    }
}

include 'templates/header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отчет по движению запчастей</title>
    <style>
        .report-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .filters-section {
            background: #fffef5;
            border: 1px solid #e6d8a8;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 0;
        }
        
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
        .stat-card.secondary { border-left-color: #6c757d; }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .report-table {
            width: 100%;
            border-collapse: collapse;
            background: #fffef5;
            font-size: 0.85rem;
        }
        
        .report-table th {
            background: #fff8dc;
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            color: #5c4a00;
            border-bottom: 2px solid #e6d8a8;
        }
        
        .report-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #f5f0d8;
        }
        
        .status-change {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-reserved { background: #fff3cd; color: #856404; }
        .status-issued { background: #cce7ff; color: #004085; }
        .status-used { background: #d4edda; color: #155724; }
        .status-returned { background: #e2e3e5; color: #383d41; }
        
        .filters-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr 200px;
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
        
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
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
    <div class="report-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>📊 Отчет по движению запчастей</h1>
            <a href="orders.php" class="btn-1c">← Назад к заказам</a>
        </div>

        <!-- Фильтры -->
        <div class="filters-section">
            <form method="get" id="reportForm">
                <div class="filters-row">
                    <div class="filter-group">
                        <label class="filter-label">Дата с</label>
                        <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" class="form-control">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Дата по</label>
                        <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" class="form-control">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Склад</label>
                        <select name="warehouse_id" class="form-control">
                            <option value="">Все склады</option>
                            <?php foreach ($warehouses as $warehouse): ?>
                                <option value="<?= $warehouse['id'] ?>" <?= $warehouse_id == $warehouse['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($warehouse['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Запчасть</label>
                        <select name="part_id" class="form-control">
                            <option value="">Все запчасти</option>
                            <?php foreach ($parts as $part): ?>
                                <option value="<?= $part['id'] ?>" <?= $part_id == $part['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($part['name']) ?> (<?= $part['part_number'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">№ Заказа</label>
                        <input type="number" name="order_id" value="<?= htmlspecialchars($order_id) ?>" class="form-control" placeholder="Номер заказа">
                    </div>
                </div>
                
                <div style="margin-top: 15px; display: flex; gap: 10px;">
                    <button type="submit" class="btn-1c-primary">Применить фильтры</button>
                    <a href="parts_movement_report.php" class="btn-1c">Сбросить</a>
                    <span style="margin-left: auto; color: #666; align-self: center;">
                        Найдено: <?= $stats['total_movements'] ?> записей
                    </span>
                </div>
            </form>
        </div>

        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_movements'] ?></div>
                <div class="stat-label">Всего движений</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number"><?= $stats['reserved_to_issued'] ?></div>
                <div class="stat-label">Выдано со склада</div>
            </div>
            <div class="stat-card success">
                <div class="stat-number"><?= $stats['issued_to_used'] ?></div>
                <div class="stat-label">Использовано</div>
            </div>
            <div class="stat-card secondary">
                <div class="stat-number"><?= $stats['returns'] ?></div>
                <div class="stat-label">Возвратов</div>
            </div>
        </div>

        <!-- Таблица отчета -->
        <div class="card-1c">
            <div class="card-header-1c">
                <span class="card-header-icon">📋</span> Движение запчастей
            </div>
            <div class="card-body">
                <?php if (!empty($report_data)): ?>
                <div style="overflow-x: auto;">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Дата/Время</th>
                                <th>Заказ</th>
                                <th>Клиент / Авто</th>
                                <th>Запчасть</th>
                                <th>Изменение статуса</th>
                                <th>Кол-во</th>
                                <th>Источник</th>
                                <th>Изменил</th>
                                <th>Примечания</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $record): ?>
                            <tr>
                                <td>
                                    <div class="date-main"><?= date('d.m.Y', strtotime($record['changed_at'])) ?></div>
                                    <small class="date-time"><?= date('H:i', strtotime($record['changed_at'])) ?></small>
                                </td>
                                <td>
                                    <a href="order_edit.php?id=<?= $record['order_id'] ?>" class="order-link">
                                        #<?= $record['order_id'] ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="client-name"><?= htmlspecialchars($record['client_name'] ?? 'N/A') ?></div>
                                    <small class="car-info"><?= htmlspecialchars($record['car_make'] ?? '') ?> <?= htmlspecialchars($record['car_model'] ?? '') ?></small>
                                </td>
                                <td>
                                    <div class="part-name"><?= htmlspecialchars($record['part_name']) ?></div>
                                    <small class="part-number"><?= htmlspecialchars($record['part_number']) ?></small>
                                </td>
                                <td>
                                    <div class="status-change status-<?= $record['old_status'] ?>">
                                        <?= getStatusText($record['old_status']) ?>
                                    </div>
                                    <span>→</span>
                                    <div class="status-change status-<?= $record['new_status'] ?>">
                                        <?= getStatusText($record['new_status']) ?>
                                    </div>
                                </td>
                                <td><?= $record['quantity'] ?> шт.</td>
                                <td>
                                    <?php if ($record['source_type'] == 'service_warehouse'): ?>
                                        🏭 <?= htmlspecialchars($record['warehouse_name'] ?? 'Склад') ?>
                                    <?php else: ?>
                                        👤 Клиент
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($record['changed_by_name'] ?? 'Система') ?></td>
                                <td>
                                    <small style="color: #8b6914;"><?= htmlspecialchars($record['notes'] ?? '') ?></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Кнопки экспорта -->
                <div class="export-buttons">
                    <button onclick="exportToExcel()" class="btn-1c">
                        📊 Экспорт в Excel
                    </button>
                    <button onclick="window.print()" class="btn-1c">
                        🖨️ Печать отчета
                    </button>
                </div>

                <?php else: ?>
                <div class="no-orders">
                    <div class="no-orders-content">
                        <div class="no-orders-icon">📊</div>
                        <h5 class="no-orders-text">Данные не найдены</h5>
                        <p>Попробуйте изменить параметры фильтра</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    function exportToExcel() {
        // Простой экспорт в CSV
        let csv = [];
        let headers = ['Дата', 'Заказ', 'Клиент', 'Авто', 'Запчасть', 'Артикул', 'Старый статус', 'Новый статус', 'Количество', 'Источник', 'Изменил', 'Примечания'];
        csv.push(headers.join(','));
        
        document.querySelectorAll('.report-table tbody tr').forEach(row => {
            let cells = row.querySelectorAll('td');
            let rowData = [
                cells[0].querySelector('.date-main').textContent.trim(),
                cells[1].querySelector('a').textContent.trim(),
                cells[2].querySelector('.client-name').textContent.trim(),
                cells[2].querySelector('.car-info').textContent.trim(),
                cells[3].querySelector('.part-name').textContent.trim(),
                cells[3].querySelector('.part-number').textContent.trim(),
                cells[4].querySelectorAll('.status-change')[0].textContent.trim(),
                cells[4].querySelectorAll('.status-change')[1].textContent.trim(),
                cells[5].textContent.trim(),
                cells[6].textContent.trim(),
                cells[7].textContent.trim(),
                cells[8].textContent.trim()
            ];
            csv.push(rowData.join(','));
        });
        
        let csvContent = "data:text/csv;charset=utf-8," + csv.join('\n');
        let encodedUri = encodeURI(csvContent);
        let link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "движение_запчастей_<?= date('Y-m-d') ?>.csv");
        document.body.appendChild(link);
        link.click();
    }
    </script>

    <?php 
    function getStatusText($status) {
        $statuses = [
            'reserved' => 'Зарезервировано',
            'issued' => 'Выдано',
            'used' => 'Использовано',
            'returned' => 'Возвращено'
        ];
        return $statuses[$status] ?? $status;
    }
    include 'templates/footer.php'; 
    ?>
</body>
</html>
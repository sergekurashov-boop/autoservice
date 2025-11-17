<?php
// system_logs.php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAnyRole(['admin', 'manager']);

// ЭКСПОРТ В CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=system_logs_' . date('Y-m-d_H-i') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF"); // BOM для UTF-8
    
    // Заголовки CSV
    fputcsv($output, [
        'Дата и время',
        'Пользователь',
        'Действие', 
        'Модуль',
        'ID записи',
        'IP адрес',
        'Браузер'
    ], ';');
    
    // Получаем все логи для экспорта
    $export_sql = "SELECT l.*, u.full_name 
                  FROM user_activity_logs l 
                  LEFT JOIN users u ON l.user_id = u.id 
                  ORDER BY l.created_at DESC 
                  LIMIT 1000";
    $export_stmt = $conn->prepare($export_sql);
    $export_stmt->execute();
    $export_logs = $export_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($export_logs as $log) {
        fputcsv($output, [
            $log['created_at'],
            $log['full_name'] ?? $log['username'],
            $log['action'],
            $log['module'] ?: '—',
            $log['record_id'] ?: '—',
            $log['ip_address'],
            substr($log['user_agent'], 0, 100)
        ], ';');
    }
    
    fclose($output);
    
    // Логируем экспорт
    $logger->logExport('system_logs', 'csv');
    exit;
}

// ЭКСПОРТ В JSON
if (isset($_GET['export']) && $_GET['export'] == 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename=system_logs_' . date('Y-m-d_H-i') . '.json');
    
    // Получаем логи для экспорта
    $export_sql = "SELECT l.*, u.full_name 
                  FROM user_activity_logs l 
                  LEFT JOIN users u ON l.user_id = u.id 
                  ORDER BY l.created_at DESC 
                  LIMIT 1000";
    $export_stmt = $conn->prepare($export_sql);
    $export_stmt->execute();
    $export_logs = $export_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $export_data = [
        'export_info' => [
            'export_date' => date('Y-m-d H:i:s'),
            'exported_by' => $_SESSION['username'],
            'total_records' => count($export_logs),
            'format' => 'JSON'
        ],
        'logs' => $export_logs
    ];
    
    echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    // Логируем экспорт
    $logger->logExport('system_logs', 'json');
    exit;
}

// ВЕРСИЯ ДЛЯ ПЕЧАТИ
if (isset($_GET['print'])) {
    $print_mode = true;
} else {
    $print_mode = false;
}

// Параметры фильтрации (только если не в режиме печати)
if (!$print_mode) {
    $user_filter = $_GET['user'] ?? '';
    $module_filter = $_GET['module'] ?? '';
    $action_filter = $_GET['action'] ?? '';
    $date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
    $date_to = $_GET['date_to'] ?? date('Y-m-d');
}

// Построение запроса с фильтрами
$where_conditions = ["1=1"];
$params = [];
$param_types = '';

if (!$print_mode) {
    if (!empty($user_filter)) {
        $where_conditions[] = "l.username LIKE ?";
        $params[] = "%$user_filter%";
        $param_types .= 's';
    }

    if (!empty($module_filter)) {
        $where_conditions[] = "l.module = ?";
        $params[] = $module_filter;
        $param_types .= 's';
    }

    if (!empty($action_filter)) {
        $where_conditions[] = "l.action = ?";
        $params[] = $action_filter;
        $param_types .= 's';
    }

    if (!empty($date_from)) {
        $where_conditions[] = "DATE(l.created_at) >= ?";
        $params[] = $date_from;
        $param_types .= 's';
    }

    if (!empty($date_to)) {
        $where_conditions[] = "DATE(l.created_at) <= ?";
        $params[] = $date_to;
        $param_types .= 's';
    }
}

$where_sql = implode(" AND ", $where_conditions);

// Получаем логи
$logs_sql = "
    SELECT l.*, u.full_name 
    FROM user_activity_logs l 
    LEFT JOIN users u ON l.user_id = u.id 
    WHERE $where_sql 
    ORDER BY l.created_at DESC 
    LIMIT " . ($print_mode ? "500" : "200")
;

$stmt = $conn->prepare($logs_sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$logs_result = $stmt->get_result();
$logs = $logs_result->fetch_all(MYSQLI_ASSOC);

// Статистика для фильтров
if (!$print_mode) {
    $users_sql = "SELECT DISTINCT username FROM user_activity_logs ORDER BY username";
    $modules_sql = "SELECT DISTINCT module FROM user_activity_logs WHERE module != '' ORDER BY module";
    $actions_sql = "SELECT DISTINCT action FROM user_activity_logs ORDER BY action";

    $users = $conn->query($users_sql)->fetch_all(MYSQLI_ASSOC);
    $modules = $conn->query($modules_sql)->fetch_all(MYSQLI_ASSOC);
    $actions = $conn->query($actions_sql)->fetch_all(MYSQLI_ASSOC);
}

// Если режим печати - выводим специальный HTML и завершаем
if ($print_mode) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Системные логи - Печать</title>
        <style>
            body { font-family: Arial; margin: 20px; color: #000; }
            .print-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
            .print-title { font-size: 24px; font-weight: bold; margin: 0; }
            .print-subtitle { font-size: 14px; color: #666; margin: 5px 0; }
            .print-table { width: 100%; border-collapse: collapse; font-size: 12px; }
            .print-table th { background: #f0f0f0; border: 1px solid #000; padding: 8px; text-align: left; font-weight: bold; }
            .print-table td { border: 1px solid #000; padding: 6px; }
            .print-footer { margin-top: 30px; text-align: center; font-size: 11px; color: #666; }
            .no-print { display: none; }
            @media print {
                .no-print { display: none !important; }
                body { margin: 0; }
                .print-header { margin-bottom: 20px; }
            }
        </style>
    </head>
    <body>
        <div class="print-header">
            <h1 class="print-title">Системные логи</h1>
            <div class="print-subtitle">Автосервис - ' . htmlspecialchars($_SESSION['full_name'] ?? '') . '</div>
            <div class="print-subtitle">Отчет сгенерирован: ' . date('d.m.Y H:i') . '</div>
            <div class="print-subtitle">Всего записей: ' . count($logs) . '</div>
        </div>
        
        <table class="print-table">
            <thead>
                <tr>
                    <th>Дата/Время</th>
                    <th>Пользователь</th>
                    <th>Действие</th>
                    <th>Модуль</th>
                    <th>ID записи</th>
                    <th>IP адрес</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($logs as $log) {
        echo '<tr>
                <td>' . date('d.m.Y H:i', strtotime($log['created_at'])) . '</td>
                <td>' . htmlspecialchars($log['full_name'] ?? $log['username']) . '</td>
                <td>' . htmlspecialchars($log['action']) . '</td>
                <td>' . htmlspecialchars($log['module'] ?: '—') . '</td>
                <td>' . ($log['record_id'] ? '#' . $log['record_id'] : '—') . '</td>
                <td>' . htmlspecialchars($log['ip_address']) . '</td>
              </tr>';
    }
    
    echo '</tbody>
        </table>
        
        <div class="print-footer">
            Система управления автосервисом &copy; ' . date('Y') . '
        </div>
        
        <script>
            window.onload = function() {
                window.print();
                setTimeout(function() {
                    window.close();
                }, 500);
            };
        </script>
    </body>
    </html>';
    exit;
}

include 'templates/header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Системные логи</title>
    <style>
        .logs-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        .filters-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        .filter-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .export-buttons {
            display: flex;
            gap: 10px;
            margin: 15px 0;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .logs-table {
            width: 100%;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .logs-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .logs-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 1px solid #dee2e6;
        }
        .logs-table td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        .logs-table tr:hover {
            background: #f8f9fa;
        }
        .action-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .action-create { background: #d4edda; color: #155724; }
        .action-update { background: #fff3cd; color: #856404; }
        .action-delete { background: #f8d7da; color: #721c24; }
        .action-view { background: #cce7ff; color: #004085; }
        .action-login { background: #d1ecf1; color: #0c5460; }
        .action-logout { background: #e2e3e5; color: #383d41; }
        .module-badge {
            background: #6c757d;
            color: white;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 11px;
        }
        .print-only { display: none; }
    </style>
</head>
<body>
    <div class="logs-container">
        <h1>📊 Системные логи</h1>
        
        <!-- Кнопки экспорта -->
        <div class="export-buttons">
            <a href="system_logs.php?export=csv" class="btn btn-success">
                📥 Экспорт в CSV
            </a>
            <a href="system_logs.php?export=json" class="btn btn-info">
                📥 Экспорт в JSON
            </a>
            <a href="system_logs.php?print=1" class="btn btn-warning" target="_blank">
                🖨️ Версия для печати
            </a>
            <a href="system_logs.php" class="btn btn-primary">
                🔄 Обновить
            </a>
        </div>
        
        <!-- Фильтры -->
        <div class="filters-card">
            <form method="get">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Пользователь</label>
                        <input type="text" name="user" value="<?= htmlspecialchars($user_filter) ?>" 
                               class="filter-control" placeholder="Имя пользователя">
                    </div>
                    <div class="filter-group">
                        <label>Модуль</label>
                        <select name="module" class="filter-control">
                            <option value="">Все модули</option>
                            <?php foreach ($modules as $module): ?>
                                <option value="<?= $module['module'] ?>" <?= $module_filter == $module['module'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($module['module'] ?: 'Система') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Действие</label>
                        <select name="action" class="filter-control">
                            <option value="">Все действия</option>
                            <?php foreach ($actions as $action): ?>
                                <option value="<?= $action['action'] ?>" <?= $action_filter == $action['action'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($action['action']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Дата с</label>
                        <input type="date" name="date_from" value="<?= $date_from ?>" class="filter-control">
                    </div>
                    <div class="filter-group">
                        <label>Дата по</label>
                        <input type="date" name="date_to" value="<?= $date_to ?>" class="filter-control">
                    </div>
                    <div class="filter-group" style="display: flex; align-items: end; gap: 10px;">
                        <button type="submit" class="btn btn-primary">Применить</button>
                        <a href="system_logs.php" class="btn btn-secondary">Сбросить</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Таблица логов -->
        <div class="logs-table">
            <table>
                <thead>
                    <tr>
                        <th>Время</th>
                        <th>Пользователь</th>
                        <th>Действие</th>
                        <th>Модуль</th>
                        <th>ID записи</th>
                        <th>IP адрес</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td>
                            <div><?= date('d.m.Y', strtotime($log['created_at'])) ?></div>
                            <small style="color: #666;"><?= date('H:i:s', strtotime($log['created_at'])) ?></small>
                        </td>
                        <td>
                            <div><strong><?= htmlspecialchars($log['full_name'] ?? $log['username']) ?></strong></div>
                            <small style="color: #666;"><?= htmlspecialchars($log['username']) ?></small>
                        </td>
                        <td>
                            <?php
                            $action_class = '';
                            if (strpos($log['action'], 'create') !== false) $action_class = 'action-create';
                            elseif (strpos($log['action'], 'update') !== false) $action_class = 'action-update';
                            elseif (strpos($log['action'], 'delete') !== false) $action_class = 'action-delete';
                            elseif (strpos($log['action'], 'view') !== false) $action_class = 'action-view';
                            elseif (strpos($log['action'], 'login') !== false) $action_class = 'action-login';
                            elseif (strpos($log['action'], 'logout') !== false) $action_class = 'action-logout';
                            ?>
                            <span class="action-badge <?= $action_class ?>"><?= htmlspecialchars($log['action']) ?></span>
                        </td>
                        <td>
                            <?php if (!empty($log['module'])): ?>
                                <span class="module-badge"><?= htmlspecialchars($log['module']) ?></span>
                            <?php else: ?>
                                <span style="color: #666;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($log['record_id']): ?>
                                <code>#<?= $log['record_id'] ?></code>
                            <?php else: ?>
                                <span style="color: #666;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small style="color: #666;"><?= htmlspecialchars($log['ip_address']) ?></small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (empty($logs)): ?>
        <div style="text-align: center; padding: 40px; background: white; border-radius: 8px; margin-top: 20px;">
            <div style="font-size: 48px; margin-bottom: 20px;">📝</div>
            <h3>Логи не найдены</h3>
            <p>Попробуйте изменить параметры фильтра</p>
        </div>
        <?php endif; ?>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
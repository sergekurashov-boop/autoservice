<?php
require 'includes/db.php';
session_start();
$current_page = basename($_SERVER['PHP_SELF']);
require_once 'auth_check.php';
requireAnyRole(['admin', 'manager']);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Вспомогательная функция для безопасного вывода
function safe_html($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Получаем список клиентов и машин для форм и фильтров
$clients = $pdo->query("SELECT id, name FROM clients ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$cars = $pdo->query("SELECT id, model FROM cars ORDER BY model")->fetchAll(PDO::FETCH_ASSOC);

// Обработка смены статуса (через GET)
if (isset($_GET['toggle_status_id'])) {
    $id = (int)$_GET['toggle_status_id'];
    $stmt = $pdo->prepare("SELECT status FROM tasks WHERE id = ?");
    $stmt->execute([$id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($task) {
        $new_status = ($task['status'] === 'pending') ? 'done' : 'pending';
        $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
        $_SESSION['success'] = "Статус задачи обновлен";
    }
    header("Location: tasks.php");
    exit;
}

// Инициализация переменных для добавления/редактирования
$edit_mode = false;
$task = [
    'id' => null,
    'client_id' => '',
    'car_id' => '',
    'description' => '',
    'due_date' => '',
    'status' => 'pending'
];

// Обработка запроса на редактирование (через GET)
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$edit_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$task) {
        $_SESSION['error'] = "Задача не найдена";
        header("Location: tasks.php");
        exit;
    }
}

// Обработка отправки формы добавления/редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'] ?? null;
    $car_id = $_POST['car_id'] ?: null;
    $description = trim($_POST['description'] ?? '');
    $due_date = $_POST['due_date'] ?? '';
    $status = $_POST['status'] ?? 'pending';
    $task_id = $_POST['task_id'] ?? null;

    if ($client_id && $description && $due_date) {
        try {
            if ($task_id) {
                // Обновление задачи
                $stmt = $pdo->prepare("UPDATE tasks SET client_id = ?, car_id = ?, description = ?, due_date = ?, status = ? WHERE id = ?");
                $stmt->execute([$client_id, $car_id, $description, $due_date, $status, $task_id]);
                $_SESSION['success'] = "✅ Задача успешно обновлена!";
            } else {
                // Добавление новой задачи
                $stmt = $pdo->prepare("INSERT INTO tasks (client_id, car_id, description, due_date, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$client_id, $car_id, $description, $due_date, $status]);
                $_SESSION['success'] = "✅ Новая задача добавлена!";
            }
            header("Location: tasks.php");
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = "❌ Ошибка при сохранении: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "❌ Пожалуйста, заполните все обязательные поля";
    }
}

// Фильтры для списка задач
$filter_date = $_GET['due_date'] ?? '';
$filter_car_id = $_GET['car_id'] ?? '';

// Запрос на выборку задач с фильтрами
$sql = "SELECT t.id, t.description, t.due_date, t.status, c.name AS client_name, car.model AS car_model
        FROM tasks t
        JOIN clients c ON t.client_id = c.id
        LEFT JOIN cars car ON t.car_id = car.id
        WHERE 1=1";
$params = [];

if ($filter_date) {
    $sql .= " AND t.due_date = :due_date";
    $params[':due_date'] = $filter_date;
}

if ($filter_car_id) {
    $sql .= " AND t.car_id = :car_id";
    $params[':car_id'] = $filter_car_id;
}

$sql .= " ORDER BY t.due_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление задачами - Autoservice</title>
    <link href="assets/css/orders.css" rel="stylesheet">
    <style>
        .tasks-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .tasks-layout {
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 20px;
            align-items: start;
        }
        
        .form-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
        }
        
        .form-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #3498db;
        }
        
        .form-icon {
            font-size: 1.5rem;
            margin-right: 10px;
        }
        
        .form-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        
        .content-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: end;
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .tasks-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .tasks-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }
        
        .tasks-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .tasks-table tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-done { background: #d4edda; color: #155724; }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        @media (max-width: 1024px) {
            .tasks-layout {
                grid-template-columns: 1fr;
            }
            
            .form-card {
                position: static;
            }
            
            .filters-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'templates/header.php'; ?>
    
    <div class="orders-container">
        <div class="container-header">
            <h1 class="page-title">
                <span class="page-title-icon">📋</span>
                Управление задачами
            </h1>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-enhanced alert-danger">
                <?= $_SESSION['error'] ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-enhanced alert-success">
                <?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="tasks-layout">
            <!-- Левая колонка - форма -->
            <div class="form-card">
                <div class="form-header">
                    <span class="form-icon"><?= $edit_mode ? '✏️' : '➕' ?></span>
                    <h3 class="form-title">
                        <?= $edit_mode ? 'Редактирование задачи' : 'Новая задача' ?>
                    </h3>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="task_id" value="<?= safe_html($task['id']) ?>">

                    <div class="form-group">
                        <label class="form-label">Клиент *</label>
                        <select class="form-control" name="client_id" required>
                            <option value="">Выберите клиента</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= safe_html($client['id']) ?>" 
                                    <?= $client['id'] == $task['client_id'] ? 'selected' : '' ?>>
                                    <?= safe_html($client['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Автомобиль</label>
                        <select class="form-control" name="car_id">
                            <option value="">Не выбрано</option>
                            <?php foreach ($cars as $car): ?>
                                <option value="<?= safe_html($car['id']) ?>" 
                                    <?= $car['id'] == $task['car_id'] ? 'selected' : '' ?>>
                                    <?= safe_html($car['model']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Описание задачи *</label>
                        <textarea class="form-control textarea-medium" name="description" required rows="3" 
                                  placeholder="Опишите задачу..."><?= safe_html($task['description']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Срок выполнения *</label>
                        <input class="form-control" type="date" name="due_date" 
                               value="<?= safe_html($task['due_date']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Статус</label>
                        <select class="form-control" name="status">
                            <option value="pending" <?= $task['status'] == 'pending' ? 'selected' : '' ?>>⏳ Ожидает</option>
                            <option value="done" <?= $task['status'] == 'done' ? 'selected' : '' ?>>✅ Выполнена</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-1c-primary">
                            <?= $edit_mode ? '💾 Сохранить' : '➕ Добавить задачу' ?>
                        </button>
                        <?php if ($edit_mode): ?>
                            <a href="tasks.php" class="btn-1c-outline">Отмена</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Правая колонка - список задач -->
            <div class="content-card">
                <!-- Фильтры -->
                <div class="filters-grid">
                    <div class="form-group">
                        <label class="form-label">Дата выполнения</label>
                        <input type="date" name="due_date" value="<?= safe_html($filter_date) ?>" 
                               class="form-control" form="filterForm">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Автомобиль</label>
                        <select name="car_id" class="form-control" form="filterForm">
                            <option value="">Все автомобили</option>
                            <?php foreach ($cars as $car): ?>
                                <option value="<?= safe_html($car['id']) ?>" 
                                    <?= $car['id'] == $filter_car_id ? 'selected' : '' ?>>
                                    <?= safe_html($car['model']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <form method="GET" id="filterForm" style="display: contents;">
                            <button type="submit" class="btn-1c-primary">
                                🔍 Применить фильтр
                            </button>
                            <?php if ($filter_date || $filter_car_id): ?>
                                <a href="tasks.php" class="btn-1c-outline">❌ Сбросить</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Список задач -->
                <h3 class="section-title" style="margin-bottom: 20px;">
                    <span class="section-icon">📝</span>
                    Список задач
                </h3>

                <?php if ($tasks): ?>
                    <div class="table-responsive">
                        <table class="tasks-table">
                            <thead>
                                <tr>
                                    <th>Клиент</th>
                                    <th>Автомобиль</th>
                                    <th>Описание</th>
                                    <th>Срок</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tasks as $task_item): ?>
                                    <tr>
                                        <td>
                                            <strong><?= safe_html($task_item['client_name']) ?></strong>
                                        </td>
                                        <td><?= safe_html($task_item['car_model'] ?? '-') ?></td>
                                        <td><?= safe_html($task_item['description']) ?></td>
                                        <td>
                                            <strong><?= date('d.m.Y', strtotime($task_item['due_date'])) ?></strong>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $task_item['status'] == 'done' ? 'status-done' : 'status-pending' ?>">
                                                <?= $task_item['status'] == 'done' ? '✅ Выполнена' : '⏳ Ожидает' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?edit_id=<?= safe_html($task_item['id']) ?>" 
                                                   class="btn-1c-outline btn-sm">
                                                    ✏️ Редактировать
                                                </a>
                                                <a href="?toggle_status_id=<?= safe_html($task_item['id']) ?>" 
                                                   class="btn-1c-secondary btn-sm"
                                                   onclick="return confirm('Изменить статус задачи?')">
                                                    <?= $task_item['status'] == 'pending' ? '✅ Выполнить' : '⏳ В ожидание' ?>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i>📋</i>
                        <h4>Задачи не найдены</h4>
                        <p>Попробуйте изменить параметры фильтра или добавьте новую задачу</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Автоматическое обновление формы фильтров при изменении даты
        document.querySelector('input[name="due_date"]')?.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });

        // Подтверждение действий
        function confirmAction(message) {
            return confirm(message);
        }
    </script>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
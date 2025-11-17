<?php
require 'includes/db.php';
session_start();

define('ACCESS', true);
include 'templates/header.php';

// Функция для проверки кириллицы
function isCyrillic($text) {
    return preg_match('/^[\p{Cyrillic}\s\-]+$/u', $text);
}

// Функция для проверки телефона
function isValidPhone($phone) {
    if (empty($phone)) return true;
    $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
    return strlen($clean_phone) >= 10;
}

// Обработка добавления сотрудника
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    $position = trim($_POST['position'] ?? '');
    $type = $_POST['type'] ?? 'employee';
    $phone = trim($_POST['phone'] ?? '');
    $specialty = trim($_POST['specialty'] ?? '');
    $specialization = $_POST['specialization'] ?? 'all';
    $salary_type = $_POST['salary_type'] ?? 'fixed';
    $base_rate = floatval(str_replace(',', '.', $_POST['base_rate'] ?? 22440));
    $work_hours = trim($_POST['work_hours'] ?? '9:00-18:00');
    
    $errors = [];
    
    // Валидация ФИО
    if (empty($name)) {
        $errors[] = "❌ Введите ФИО сотрудника";
    } elseif (!isCyrillic($name)) {
        $errors[] = "❌ ФИО должно содержать только кириллические буквы, пробелы и дефисы";
    } elseif (strlen($name) < 2) {
        $errors[] = "❌ ФИО должно содержать минимум 2 символа";
    }
    
    // Валидация должности
    if (empty($position)) {
        $errors[] = "❌ Введите должность";
    }
    
    // Валидация телефона
    if (!empty($phone) && !isValidPhone($phone)) {
        $errors[] = "❌ Некорректный номер телефона";
    }
    
    // Валидация базовой ставки
    if ($base_rate < 22440) {
        $errors[] = "❌ Базовая ставка не может быть меньше МРОТ (22 440 ₽)";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO employees 
            (name, position, type, phone, specialty, specialization, work_hours, salary_type, base_rate, active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("ssssssssd", $name, $position, $type, $phone, $specialty, $specialization, $work_hours, $salary_type, $base_rate);
        
        if ($stmt->execute()) {
            $success_message = "✅ Сотрудник успешно добавлен";
            // Очищаем форму
            $name = $position = $phone = $specialty = '';
        } else {
            $error_message = "❌ Ошибка базы данных: " . $conn->error;
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Обработка удаления
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE employees SET active = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success_message = "✅ Сотрудник успешно деактивирован";
        } else {
            $error_message = "❌ Ошибка удаления: " . $conn->error;
        }
    }
}

// Получаем список сотрудников с фильтрацией
$type_filter = $_GET['type'] ?? 'all';
$where = "active = 1";
if ($type_filter === 'mechanic') {
    $where .= " AND type = 'mechanic'";
} elseif ($type_filter === 'employee') {
    $where .= " AND type = 'employee'";
}

$staff_result = $conn->query("
    SELECT * FROM employees 
    WHERE $where
    ORDER BY type, name
");
$staff_count = $staff_result ? $staff_result->num_rows : 0;

// Статистика по типам
$stats_result = $conn->query("
    SELECT type, COUNT(*) as count 
    FROM employees 
    WHERE active = 1 
    GROUP BY type
");
$stats = [];
while ($row = $stats_result->fetch_assoc()) {
    $stats[$row['type']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление персоналом</title>
    <link rel="stylesheet" href="assets/css/services.css?v=<?= time() ?>">
    <style>
        .staff-type-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .type-mechanic { background: #e3f2fd; color: #1976d2; }
        .type-employee { background: #f3e5f5; color: #7b1fa2; }
        .filter-buttons { margin-bottom: 20px; }
        .salary-info { font-size: 0.9em; color: #666; }
        .specialization-badge {
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 0.75em;
            background: #e8f5e8;
            color: #2e7d32;
        }
        .dynamic-fields { transition: all 0.3s ease; }
        .field-hidden { display: none; }
    </style>
</head>
<body class="services-container">
    <div class="container mt-4">
        <div class="header-compact">
            <h1 class="page-title-compact">👥 Управление персоналом</h1>
            <div class="header-actions-compact">
                <a href="salaries.php" class="action-btn-compact">
                    <span class="action-icon">💰</span>
                    <span class="action-label">Зарплаты</span>
                </a>
                <a href="salary_calculate.php" class="action-btn-compact">
                    <span class="action-icon">🧮</span>
                    <span class="action-label">Расчет ЗП</span>
                </a>
            </div>
        </div>
        
        <!-- Сообщения -->
        <?php if (isset($success_message)): ?>
            <div class="alert-enhanced alert-success"><?= $success_message ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert-enhanced alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <!-- Фильтры -->
        <div class="filter-buttons">
            <a href="staff_management.php?type=all" class="btn-1c-outline <?= $type_filter === 'all' ? 'active' : '' ?>">
                Все (<?= ($stats['employee'] ?? 0) + ($stats['mechanic'] ?? 0) ?>)
            </a>
            <a href="staff_management.php?type=employee" class="btn-1c-outline <?= $type_filter === 'employee' ? 'active' : '' ?>">
                Сотрудники (<?= $stats['employee'] ?? 0 ?>)
            </a>
            <a href="staff_management.php?type=mechanic" class="btn-1c-outline <?= $type_filter === 'mechanic' ? 'active' : '' ?>">
                Мастера (<?= $stats['mechanic'] ?? 0 ?>)
            </a>
        </div>

        <!-- Форма добавления -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">➕ Добавить сотрудника</div>
            <div class="card-body">
                <form method="post" id="staffForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">👤 ФИО*</label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?= htmlspecialchars($name ?? '') ?>" 
                                       placeholder="Введите ФИО" 
                                       required
                                       pattern="[А-Яа-яЁё\s\-]+"
                                       title="Только кириллические буквы, пробелы и дефисы">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">💼 Должность*</label>
                                <input type="text" name="position" class="form-control" 
                                       value="<?= htmlspecialchars($position ?? '') ?>" 
                                       placeholder="Например: Менеджер, Механик"
                                       required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">👥 Тип сотрудника</label>
                                <select name="type" class="form-control" id="typeSelect">
                                    <option value="employee">Сотрудник</option>
                                    <option value="mechanic">Мастер</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">📞 Телефон</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?= htmlspecialchars($phone ?? '') ?>" 
                                       placeholder="+7 (XXX) XXX-XX-XX">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">🕐 График работы</label>
                                <input type="text" name="work_hours" class="form-control" 
                                       value="<?= htmlspecialchars($work_hours ?? '9:00-18:00') ?>" 
                                       placeholder="9:00-18:00">
                            </div>
                        </div>
                    </div>

                    <!-- Динамические поля для мастеров -->
                    <div class="dynamic-fields" id="mechanicFields">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">🛠️ Специальность</label>
                                    <input type="text" name="specialty" class="form-control" 
                                           value="<?= htmlspecialchars($specialty ?? '') ?>" 
                                           placeholder="Например: Автоэлектрик, Моторист">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">🔧 Специализация</label>
                                    <select name="specialization" class="form-control">
                                        <option value="all">Универсал</option>
                                        <option value="front_axis">Передняя ось</option>
                                        <option value="rear_axis">Задняя ось</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">💰 Тип оплаты</label>
                                <select name="salary_type" class="form-control">
                                    <option value="fixed">Фиксированная</option>
                                    <option value="percentage">Процент от работ</option>
                                    <option value="sales">Процент от продаж</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">📊 Базовая ставка (₽)*</label>
                                <input type="number" step="0.01" name="base_rate" class="form-control" 
                                       value="<?= $base_rate ?? 22440 ?>" 
                                       required min="22440" max="1000000">
                                <div class="form-text">МРОТ: 22 440 ₽</div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-1c-primary">✅ Добавить сотрудника</button>
                </form>
            </div>
        </div>

        <!-- Таблица сотрудников -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">📋 Список персонала (<?= $staff_count ?>)</div>
            <div class="card-body">
                <?php if ($staff_count > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>👤 ФИО</th>
                                    <th>💼 Должность</th>
                                    <th>👥 Тип</th>
                                    <th>📞 Телефон</th>
                                    <th>🛠️ Специализация</th>
                                    <th>💰 Зарплата</th>
                                    <th>⚡ Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($staff = $staff_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($staff['name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($staff['position']) ?></td>
                                    <td>
                                        <span class="staff-type-badge type-<?= $staff['type'] ?>">
                                            <?= $staff['type'] === 'mechanic' ? '🔧 Мастер' : '👔 Сотрудник' ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($staff['phone'] ?: '—') ?></td>
                                    <td>
                                        <?php if ($staff['specialization'] && $staff['specialization'] !== 'all'): ?>
                                            <span class="specialization-badge">
                                                <?= $staff['specialization'] === 'front_axis' ? 'Передняя ось' : 
                                                   ($staff['specialization'] === 'rear_axis' ? 'Задняя ось' : 'Универсал') ?>
                                            </span>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="salary-info">
                                            <?= number_format($staff['base_rate'], 2, '.', ' ') ?> ₽
                                            <br>
                                            <small>
                                                <?= $staff['salary_type'] === 'fixed' ? 'Фиксированная' : 
                                                   ($staff['salary_type'] === 'percentage' ? 'Процент' : 'Продажи') ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="employee_edit.php?id=<?= $staff['id'] ?>" class="btn-1c-sm">
                                            ✏️
                                        </a>
                                        <a href="staff_management.php?delete=<?= $staff['id'] ?>" class="btn-1c-danger btn-1c-sm" 
                                           onclick="return confirm('❌ Деактивировать сотрудника «<?= htmlspecialchars($staff['name']) ?>»?')">
                                            🗑️
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">👥</div>
                        <div>Нет сотрудников в базе данных</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('typeSelect');
        const mechanicFields = document.getElementById('mechanicFields');
        
        function toggleMechanicFields() {
            if (typeSelect.value === 'mechanic') {
                mechanicFields.style.display = 'block';
            } else {
                mechanicFields.style.display = 'none';
            }
        }
        
        typeSelect.addEventListener('change', toggleMechanicFields);
        toggleMechanicFields(); // Инициализация
    });
    </script>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
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
    if (empty($phone)) return true; // телефон не обязателен
    $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
    return strlen($clean_phone) >= 10;
}

// Обработка добавления мастера
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone'] ?? '');
    $specialty = trim($_POST['specialty'] ?? '');
    
    $errors = [];
    
    // Валидация ФИО
    if (empty($name)) {
        $errors[] = "❌ Введите ФИО мастера";
    } elseif (!isCyrillic($name)) {
        $errors[] = "❌ ФИО должно содержать только кириллические буквы, пробелы и дефисы";
    } elseif (strlen($name) < 2) {
        $errors[] = "❌ ФИО должно содержать минимум 2 символа";
    } elseif (strlen($name) > 100) {
        $errors[] = "❌ ФИО не должно превышать 100 символов";
    }
    
    // Валидация телефона
    if (!empty($phone) && !isValidPhone($phone)) {
        $errors[] = "❌ Некорректный номер телефона";
    }
    
    // Валидация специальности
    if (!empty($specialty) && strlen($specialty) > 255) {
        $errors[] = "❌ Специальность не должна превышать 255 символов";
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO mechanics (name, phone, specialty) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $phone, $specialty);
        
        if ($stmt->execute()) {
            $success_message = "✅ Мастер успешно добавлен";
            // Очищаем форму после успешного добавления
            $name = $phone = $specialty = '';
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
        $stmt = $conn->prepare("DELETE FROM mechanics WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success_message = "✅ Мастер успешно удален";
        } else {
            $error_message = "❌ Ошибка удаления: " . $conn->error;
        }
    }
}

// Получаем список мастеров
$mechanics_result = $conn->query("SELECT * FROM mechanics ORDER BY name");
$mechanics_count = $mechanics_result ? $mechanics_result->num_rows : 0;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление мастерами</title>
    <link rel="stylesheet" href="assets/css/mechanics.css?v=<?= time() ?>">
    <script src="assets/js/mechanics.js?v=<?= time() ?>"></script>
</head>
<body class="mechanics-container">
    <div class="container mt-4">
        <h1 class="page-title">👨‍🔧 Управление мастерами</h1>
        
        <!-- Сообщения -->
        <?php if (isset($success_message)): ?>
            <div class="alert-enhanced alert-success"><?= $success_message ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert-enhanced alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <!-- Форма добавления -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">➕ Добавить нового мастера</div>
            <div class="card-body">
                <form method="post" id="mechanicForm">
                    <div class="mb-3">
                        <label class="form-label">👤 ФИО мастера*</label>
                        <input type="text" name="name" class="form-control" 
                               value="<?= htmlspecialchars($name ?? '') ?>" 
                               placeholder="Введите ФИО мастера (только кириллица)" 
                               required
                               pattern="[А-Яа-яЁё\s\-]+"
                               title="Только кириллические буквы, пробелы и дефисы">
                        <div class="form-text">Только кириллические буквы, пробелы и дефисы</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">📞 Телефон</label>
                        <input type="tel" name="phone" class="form-control" 
                               value="<?= htmlspecialchars($phone ?? '') ?>" 
                               placeholder="+7 (XXX) XXX-XX-XX"
                               pattern="[\+]?[0-9\s\-\(\)]+">
                        <div class="form-text">Необязательное поле</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">🛠️ Специальность</label>
                        <input type="text" name="specialty" class="form-control" 
                               value="<?= htmlspecialchars($specialty ?? '') ?>" 
                               placeholder="Например: Мастер по ремонту, Диагност"
                               maxlength="255">
                        <div class="form-text">Необязательное поле</div>
                    </div>
                    
                    <button type="submit" class="btn-1c-primary">✅ Добавить мастера</button>
                </form>
            </div>
        </div>

        <!-- Таблица мастеров -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">📋 Список мастеров (<?= $mechanics_count ?>)</div>
            <div class="card-body">
                <?php if ($mechanics_count > 0): ?>
                    <div class="table-responsive">
                        <table class="table-enhanced">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>👤 ФИО</th>
                                    <th>📞 Телефон</th>
                                    <th>🛠️ Специальность</th>
                                    <th>⚡ Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($mechanic = $mechanics_result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= $mechanic['id'] ?></strong></td>
                                    <td><strong><?= htmlspecialchars($mechanic['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($mechanic['phone'] ?: '—') ?></td>
                                    <td>
                                        <?php if ($mechanic['specialty']): ?>
                                            <span class="specialty-badge"><?= htmlspecialchars($mechanic['specialty']) ?></span>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="mechanics.php?delete=<?= $mechanic['id'] ?>" class="btn-1c-danger" 
                                           onclick="return confirm('❌ Удалить мастера «<?= htmlspecialchars($mechanic['name']) ?>»?')">
                                            🗑️ Удалить
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">👨‍🔧</div>
                        <div>Нет мастеров в базе данных</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'templates/footer.php'; ?>
</body>
</html>
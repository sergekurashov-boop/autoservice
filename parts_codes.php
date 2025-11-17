<?php
// autoservice/parts_codes.php
require 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "❌ Требуется авторизация";
    header("Location: login.php");
    exit;
}

define('ACCESS', true);

// Обработка сохранения кодов
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_codes'])) {
    $updated = 0;
    foreach ($_POST['codes'] as $part_id => $code) {
        $code = trim($code);
        if (!empty($code)) {
            $stmt = $conn->prepare("UPDATE parts SET code = ? WHERE id = ?");
            $stmt->bind_param("si", $code, $part_id);
            if ($stmt->execute()) {
                $updated++;
            }
        }
    }
    $_SESSION['success'] = "✅ Обновлено кодов: " . $updated;
    header("Location: parts_codes.php");
    exit;
}

// Получаем все запчасти
$parts = [];
$result = $conn->query("SELECT id, code, name, part_number, price FROM parts ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $parts[] = $row;
}

include 'templates/header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление кодами запчастей - Автосервис</title>
    <link rel="stylesheet" href="assets/css/services.css?v=<?= time() ?>">
    <style>
        .code-input {
            font-weight: bold;
            text-transform: uppercase;
        }
        .current-code {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
    </style>
</head>
<body class="services-container">
    <div class="container mt-4">
        <div class="header-compact">
            <h1 class="page-title-compact">🔠 Управление кодами запчастей</h1>
            <div class="header-actions-compact">
                <a href="parts.php" class="action-btn-compact">
                    <span class="action-icon">←</span>
                    <span class="action-label">Назад к запчастям</span>
                </a>
            </div>
        </div>
        
        <!-- Сообщения -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-enhanced alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-enhanced alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Форма управления кодами -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">
                📋 Список запчастей (<?= count($parts) ?>)
                <small class="text-muted">- коды используются в актах осмотра</small>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th width="80">ID</th>
                                    <th width="120">Текущий код</th>
                                    <th>Наименование</th>
                                    <th width="150">Артикул</th>
                                    <th width="150">Цена</th>
                                    <th width="200">Новый код</th>
                                    <th width="150">Предложения</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($parts as $part): ?>
                                <tr>
                                    <td><strong><?= $part['id'] ?></strong></td>
                                    <td>
                                        <?php if (!empty($part['code'])): ?>
                                            <span class="badge bg-primary current-code"><?= $part['code'] ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">нет кода</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($part['name']) ?></td>
                                    <td>
                                        <code><?= htmlspecialchars($part['part_number']) ?></code>
                                    </td>
                                    <td>
                                        <?= number_format($part['price'], 2, '.', ' ') ?> ₽
                                    </td>
                                    <td>
                                        <input type="text" 
                                               name="codes[<?= $part['id'] ?>]" 
                                               value="<?= htmlspecialchars($part['code']) ?>"
                                               class="form-control code-input"
                                               maxlength="20"
                                               placeholder="P001"
                                               style="font-weight: bold; text-transform: uppercase;">
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php
                                            $name_words = explode(' ', $part['name']);
                                            $first_word = $name_words[0] ?? '';
                                            $suggestions = [
                                                'P' . str_pad($part['id'], 3, '0', STR_PAD_LEFT),
                                                substr(strtoupper($first_word), 0, 2) . str_pad($part['id'], 3, '0', STR_PAD_LEFT),
                                                substr(strtoupper(str_replace(' ', '', $part['name'])), 0, 3) . $part['id']
                                            ];
                                            echo implode('<br>', array_slice($suggestions, 0, 2));
                                            ?>
                                        </small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="form-actions mt-4">
                        <button type="submit" name="update_codes" class="btn-1c-primary">
                            💾 Сохранить все коды
                        </button>
                        <a href="parts.php" class="btn-1c-outline">❌ Отмена</a>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                💡 <strong>Совет:</strong> Используйте осмысленные коды (P001, T001, B001) для удобства поиска в актах осмотра
                            </small>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Быстрые действия -->
        <div class="enhanced-card mt-4">
            <div class="enhanced-card-header">⚡ Быстрые действия</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="auto_generate" value="1">
                            <button type="submit" class="btn-1c-outline">
                                🔄 Автогенерация кодов
                            </button>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <a href="inspection_mobile.php" class="btn-1c-outline">
                            📱 Тест в акте осмотра
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="services.php" class="btn-1c-outline">
                            🔧 Коды услуг
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Автоматическое приведение к верхнему регистру
        document.querySelectorAll('.code-input').forEach(input => {
            input.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        });
        
        // Подсказки при клике
        document.querySelectorAll('input[name^="codes"]').forEach(input => {
            input.addEventListener('focus', function() {
                const suggestions = this.closest('tr').querySelector('small').textContent;
                if (!this.value && suggestions) {
                    const firstSuggestion = suggestions.split('\n')[0].trim();
                    this.placeholder = firstSuggestion;
                }
            });
        });
    </script>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
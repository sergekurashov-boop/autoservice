<?php
require 'includes/db.php';
session_start();

define('ACCESS', true);
include 'templates/header.php';

// Обработка добавления услуги
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $code = trim($_POST['code'] ?? '');

    // Валидация данных
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Введите название услуги";
    } elseif (strlen($name) < 2) {
        $errors[] = "Название услуги должно содержать минимум 2 символа";
    }
    
    if (empty($code)) {
        $errors[] = "Введите код услуги";
    } elseif (!is_numeric($code)) {
        $errors[] = "Код должен быть числом";
    } elseif ($code < 10 || $code > 99) {
        $errors[] = "Код должен быть от 10 до 99";
    }
    
    if ($price <= 0) {
        $errors[] = "Введите корректную цену";
    } elseif ($price > 1000000) {
        $errors[] = "Цена не может превышать 1 000 000 руб.";
    }

    // Проверяем уникальность кода
    if (empty($errors)) {
        $check_stmt = $conn->prepare("SELECT id FROM services WHERE code = ?");
        $check_stmt->bind_param("s", $code);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Услуга с таким кодом уже существует";
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO services (name, price, code) VALUES (?, ?, ?)");
        $stmt->bind_param("sds", $name, $price, $code);
        if ($stmt->execute()) {
            $_SESSION['success'] = "✅ Услуга успешно добавлена";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $_SESSION['error'] = "Ошибка при добавлении услуги: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}

// Обработка удаления услуги
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_service'])) {
    $id = (int)$_POST['id'];
    
    if ($id > 0) {
        // Проверяем, используется ли услуга в заказах
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM order_services WHERE service_id = ?");
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_row();
        $usage_count = $row[0];
        
        if ($usage_count > 0) {
            $_SESSION['error'] = "Невозможно удалить услугу, которая используется в заказах";
        } else {
            $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "✅ Услуга успешно удалена";
            } else {
                $_SESSION['error'] = "Ошибка при удалении услуги";
            }
        }
    } else {
        $_SESSION['error'] = "Некорректный идентификатор услуги";
    }
}

// Обработка массового обновления цен
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_update_prices'])) {
    if (!empty($_POST['price_percent'])) {
        $percent = floatval($_POST['price_percent']);
        $multiplier = 1 + ($percent / 100);
        
        $stmt = $conn->prepare("UPDATE services SET price = ROUND(price * ?, 2)");
        $stmt->bind_param("d", $multiplier);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "✅ Цены обновлены на {$percent}%";
        } else {
            $_SESSION['error'] = "❌ Ошибка обновления цен";
        }
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Получение списка услуг
$services = $conn->query("SELECT * FROM services ORDER BY code, name");
$services_count = $services->num_rows;

// Получение статистики по типам услуг
$type_stats = $conn->query("
    SELECT 
        CASE 
            WHEN code IN ('13','14','15','16','17','18') THEN 'Шиномонтаж'
            ELSE 'Основные услуги'
        END as service_type,
        COUNT(*) as count,
        AVG(price) as avg_price
    FROM services 
    GROUP BY service_type 
    ORDER BY service_type
");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление услугами</title>
    <link rel="stylesheet" href="assets/css/services.css?v=<?= time() ?>">
    <style>
        .service-code {
            background: #e6d8a8;
            color: #5c4a00;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .tire-badge {
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 500;
        }
        .service-badge {
            background: #007bff;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 500;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: #fffef5;
            border: 1px solid #e6d8a8;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #5c4a00;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 0.8rem;
            color: #8b6914;
        }
        .quick-edit-form {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .code-help {
            font-size: 0.8rem;
            color: #8b6914;
            margin-top: 5px;
        }
    </style>
</head>
<body class="services-container">
   
    
    <div class="container mt-4">
        <div class="header-compact">
            <h1 class="page-title-compact">🛠️ Управление услугами</h1>
            <div class="header-actions-compact">
                <button type="button" class="action-btn-compact" onclick="showCodeHelp()">
                    <span class="action-icon">❓</span>
                    <span class="action-label">Коды услуг</span>
                </button>
            </div>
        </div>
        
        <!-- Вывод сообщений -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-enhanced alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-enhanced alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Статистика -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">📊 Статистика услуг</div>
            <div class="card-body">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?= $services_count ?></div>
                        <div class="stat-label">Всего услуг</div>
                    </div>
                    <?php while($stat = $type_stats->fetch_assoc()): ?>
                    <div class="stat-card">
                        <div class="stat-value"><?= $stat['count'] ?></div>
                        <div class="stat-label"><?= $stat['service_type'] ?></div>
                        <div style="font-size: 0.7rem; color: #8b6914;">
                            Ср. цена: <?= number_format($stat['avg_price'], 0, '.', ' ') ?> ₽
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- Форма добавления услуги -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">➕ Добавить услугу</div>
            <div class="card-body">
                <form method="post" id="serviceForm">
                    <div class="mb-3">
                        <label class="form-label">📝 Название услуги*</label>
                        <input type="text" name="name" class="form-control" 
                               placeholder="Например: Замена масла" required
                               minlength="2" maxlength="100">
                        <div class="form-text">Минимум 2 символа</div>
                    </div>
                    
                    <!-- КОД -->
                    <div class="mb-3">
                        <label class="form-label">🔢 Код услуги* (10-99)</label>
                        <input type="number" name="code" class="form-control" 
                               placeholder="Например: 15 для шиномонтажа R15"
                               min="10" max="99" required>
                        <div class="code-help">
                            <strong>Шиномонтаж:</strong> 13, 14, 15, 16, 17, 18 (по радиусу)<br>
                            <strong>Основные услуги:</strong> 10-12, 19-99 (двухзначные)
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">💰 Цена (руб.)*</label>
                        <input type="number" step="0.01" name="price" class="form-control" 
                               placeholder="0.00" required
                               min="0.01" max="1000000">
                        <div class="form-text">От 0.01 до 1 000 000 руб.</div>
                    </div>
                    
                    <button type="submit" name="add_service" class="btn-1c-primary">✅ Добавить услугу</button>
                </form>
            </div>
        </div>

        <!-- Массовое обновление цен -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">💹 Массовое обновление цен</div>
            <div class="card-body">
                <form method="post" id="bulkPriceForm">
                    <div class="mb-3">
                        <label class="form-label">📈 Обновить все цены на (%)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="price_percent" class="form-control" 
                                   placeholder="Например: 10 для увеличения на 10%">
                            <span class="input-group-text">%</span>
                        </div>
                        <div class="form-text">Отрицательное значение для уменьшения цен</div>
                    </div>
                    
                    <button type="submit" name="bulk_update_prices" class="btn-1c-warning">
                        📊 Обновить цены
                    </button>
                </form>
            </div>
        </div>

        <!-- Таблица услуг -->
        <div class="enhanced-card">
            <div class="enhanced-card-header">
                📋 Список услуг (<?= $services_count ?>)
            </div>
            <div class="card-body">
                <?php if ($services_count > 0): ?>
                    <div class="table-responsive">
                        <table class="table-enhanced">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>🔢 Код</th>
                                    <th>📝 Название</th>
                                    <th>💰 Цена</th>
                                    <th>📂 Тип</th>
                                    <th>⚡ Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($service = $services->fetch_assoc()): 
                                    // Определяем тип услуги по коду
                                    $is_tire = in_array($service['code'], ['13','14','15','16','17','18']);
                                    $type = $is_tire ? 'Шиномонтаж' : 'Основная услуга';
                                    $badge_class = $is_tire ? 'tire-badge' : 'service-badge';
                                ?>
                                <tr>
                                    <td><strong><?= $service['id'] ?></strong></td>
                                    <td>
                                        <span class="service-code"><?= htmlspecialchars($service['code']) ?></span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($service['name']) ?></strong>
                                    </td>
                                    <td class="price-cell">
                                        <?= number_format($service['price'], 2, '.', ' ') ?> руб.
                                    </td>
                                    <td>
                                        <span class="<?= $badge_class ?>"><?= $type ?></span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="service_edit.php?id=<?= $service['id'] ?>" class="btn-1c-warning">
                                                ✏️ Редактировать
                                            </a>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="id" value="<?= $service['id'] ?>">
                                                <button type="submit" name="delete_service" class="btn-1c-danger" 
                                                        onclick="return confirm('❌ Вы уверены, что хотите удалить услугу «<?= htmlspecialchars($service['name']) ?>»?')">
                                                    🗑️ Удалить
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🛠️</div>
                        <div>Нет услуг в базе данных</div>
                        <div class="mt-3">
                            <p class="text-muted">Добавьте первую услугу для использования в заказах</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<script src="assets/js/services.js?v=<?= time() ?>"></script>
<script>
// Показ справки по кодам
function showCodeHelp() {
    alert(`📋 СИСТЕМА КОДОВ УСЛУГ:

🔧 ОСНОВНЫЕ УСЛУГИ (10-99):
10-12, 19-99 - любые двухзначные коды

🚗 ШИНОМОНТАЖ (по радиусу):
13 - Шиномонтаж R13
14 - Шиномонтаж R14  
15 - Шиномонтаж R15
16 - Шиномонтаж R16
17 - Шиномонтаж R17
18 - Шиномонтаж R18

💡 При создании заказа:
- Введите "15" - увидите все услуги шиномонтажа R15
- Введите код услуги - найдется конкретная услуга`);
}

// Включение быстрого редактирования цен
function enableQuickEdit() {
    document.querySelectorAll('.price-cell').forEach(cell => {
        cell.addEventListener('dblclick', function() {
            const currentPrice = this.textContent.replace(' руб.', '').replace(/\s/g, '');
            const serviceId = this.closest('tr').querySelector('td:first-child strong').textContent;
            
            this.innerHTML = `
                <div class="quick-edit-form">
                    <input type="number" step="0.01" value="${currentPrice}" 
                           class="form-control form-control-sm" style="width: 100px; display: inline-block;">
                    <button type="button" class="btn-1c-primary btn-small" onclick="savePrice(${serviceId}, this)">
                        💾
                    </button>
                    <button type="button" class="btn-1c-outline btn-small" onclick="cancelEdit(this)">
                        ❌
                    </button>
                </div>
            `;
        });
    });
}

function savePrice(serviceId, button) {
    const newPrice = button.parentElement.querySelector('input').value;
    const formData = new FormData();
    formData.append('service_id', serviceId);
    formData.append('new_price', newPrice);
    
    fetch('update_service_price.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            location.reload();
        } else {
            alert('Ошибка: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        alert('Ошибка при сохранении цены');
    });
}

function cancelEdit(button) {
    location.reload();
}

// Включаем быстрое редактирование при загрузке
document.addEventListener('DOMContentLoaded', function() {
    enableQuickEdit();
});
</script>
    
    <?php include 'templates/footer.php'; ?>
</body>
</html>
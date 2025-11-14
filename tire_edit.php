<?php
define('ACCESS', true);

require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'auth_check.php';

// Получаем ID заказа
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    header("Location: tire_orders.php");
    exit();
}

// Получаем данные заказа
try {
    $sql = "SELECT t.*, c.name as client_name, c.phone,
                   car.make, car.model, car.year, car.license_plate, car.vin as car_vin
            FROM tire_orders t
            LEFT JOIN clients c ON t.client_id = c.id
            LEFT JOIN cars car ON t.car_id = car.id
            WHERE t.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        die("❌ Заказ не найден");
    }
    
    // Декодируем данные по шинам
    $tire_data = !empty($order['tire_data']) ? json_decode($order['tire_data'], true) : [];
    
} catch (PDOException $e) {
    die("❌ Ошибка базы данных: " . $e->getMessage());
}

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $status = $_POST['status'];
        $notes = $_POST['notes'];
        
        // Обновляем заказ
        $sql = "UPDATE tire_orders SET status = ?, notes = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$status, $notes, $order_id]);
        
        header("Location: tire_orders.php?success=1");
        exit();
        
    } catch (PDOException $e) {
        $error = "Ошибка сохранения: " . $e->getMessage();
    }
}

$page_title = "Редактирование заказа шиномонтажа #" . $order_id;
include 'templates/header.php';
?>

<div class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>✏️ Редактирование заказа шиномонтажа #<?= $order_id ?></h1>
            <div class="header-actions">
                <a href="tire_orders.php" class="btn btn-secondary">📋 К списку заказов</a>
                <a href="tire_print.php?id=<?= $order_id ?>" class="btn btn-primary" target="_blank">🖨️ Печать</a>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error" style="background: #ffebee; color: #c62828; padding: 15px; border: 1px solid #ffcdd2; margin-bottom: 20px;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="tire_edit.php?id=<?= $order_id ?>">
            <!-- Информация о заказе (только чтение) -->
            <div class="form-section" style="background: white; padding: 20px; border: 1px solid #ccc; margin-bottom: 20px;">
                <h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">📋 Информация о заказе</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                    <div>
                        <strong>Клиент:</strong><br>
                        <?= htmlspecialchars($order['client_name']) ?><br>
                        <span style="color: #666;">📞 <?= $order['phone'] ?></span>
                    </div>
                    <div>
                        <strong>Автомобиль:</strong><br>
                        <?= htmlspecialchars($order['make']) ?> <?= htmlspecialchars($order['model']) ?> 
                        (<?= $order['year'] ?>)<br>
                        <span style="color: #666;">🔢 <?= $order['license_plate'] ?></span>
                    </div>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <strong>Дата создания:</strong> <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                </div>
            </div>

            <!-- Шины по позициям (только чтение) -->
            <div class="form-section" style="background: white; padding: 20px; border: 1px solid #ccc; margin-bottom: 20px;">
                <h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;"> Шины по позициям</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <?php
                    $positions = [
                        'fl' => 'Передняя левая (FL)',
                        'fr' => 'Передняя правая (FR)', 
                        'rl' => 'Задняя левая (RL)',
                        'rr' => 'Задняя правая (RR)'
                    ];
                    
                    foreach ($positions as $key => $title):
                    ?>
                        <div style="border: 1px solid #ddd; padding: 15px; background: #f9f9f9;">
                            <strong><?= $title ?></strong><br>
                            <strong>Размер:</strong> <?= $tire_data[$key . '_size'] ?? '—' ?><br>
                            <strong>Производитель:</strong> <?= $tire_data[$key . '_brand'] ?? '—' ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Услуги (только чтение) -->
            <div class="form-section" style="background: white; padding: 20px; border: 1px solid #ccc; margin-bottom: 20px;">
                <h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">🔧 Услуги</h3>
                
                <?php
                $services = !empty($order['services']) ? explode(',', $order['services']) : [];
                $service_names = [
                    'mounting' => '✅ Монтаж/демонтаж шин',
                    'balancing' => '✅ Балансировка колес', 
                    'alignment' => '✅ Развал-схождение',
                    'repair' => '✅ Ремонт шин',
                    'seasonal' => '✅ Сезонная замена'
                ];
                
                if (count($services) > 0) {
                    foreach ($services as $service) {
                        echo '<div>' . ($service_names[$service] ?? '✅ ' . $service) . '</div>';
                    }
                } else {
                    echo '<div style="color: #666;">— Услуги не указаны —</div>';
                }
                ?>
            </div>

            <!-- Редактируемые поля -->
            <div class="form-section" style="background: white; padding: 20px; border: 1px solid #ccc; margin-bottom: 20px;">
                <h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">⚙️ Изменение заказа</h3>
                
                <!-- Статус -->
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Статус заказа:</label>
                    <select name="status" style="padding: 8px; border: 1px solid #ccc; width: 100%;">
                        <option value="draft" <?= $order['status'] == 'draft' ? 'selected' : '' ?>>📝 Черновик</option>
                        <option value="active" <?= $order['status'] == 'active' ? 'selected' : '' ?>>🔧 В работе</option>
                        <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>✅ Выполнен</option>
                        <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>❌ Отменен</option>
                    </select>
                </div>
                
                <!-- Примечания -->
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Примечания:</label>
                    <textarea name="notes" rows="4" style="width: 100%; padding: 8px; border: 1px solid #ccc;"><?= htmlspecialchars($order['notes'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Кнопки действий -->
            <div class="form-actions" style="text-align: center; padding: 20px; background: #f5f5f5; border: 1px solid #ccc;">
                <button type="submit" class="btn btn-primary" style="padding: 10px 20px; background: #0078d7; color: white; border: none; cursor: pointer;">
                    💾 Сохранить изменения
                </button>
                <a href="tire_orders.php" class="btn btn-secondary" style="padding: 10px 20px; background: #666; color: white; text-decoration: none; margin-left: 10px;">
                    ❌ Отмена
                </a>
            </div>
        </form>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
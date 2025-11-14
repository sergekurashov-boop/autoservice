<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAuth();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'ID заказа не указан';
    header('Location: tire_orders.php');
    exit;
}

$order_id = (int)$_GET['id'];

// Получаем данные заказа
$order_sql = "SELECT * FROM order_tire_services WHERE id = ?";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['error'] = 'Заказ не найден';
    header('Location: tire_orders.php');
    exit;
}

// Получаем список услуг шиномонтажа
$services_sql = "SELECT ts.*, GROUP_CONCAT(CONCAT(tp.radius, ':', tp.price) ORDER BY tp.radius) as prices
                 FROM tire_services ts 
                 LEFT JOIN tire_prices tp ON ts.id = tp.tire_service_id 
                 WHERE ts.is_active = TRUE
                 GROUP BY ts.id 
                 ORDER BY ts.sort_order";
$services_result = $conn->query($services_sql);
$services = $services_result->fetch_all(MYSQLI_ASSOC);

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_name = trim($_POST['client_name']);
    $client_phone = trim($_POST['client_phone']);
    $car_model = trim($_POST['car_model']);
    $car_plate = trim($_POST['car_plate'] ?? '');
    $radius = (int)$_POST['radius'];
    $tire_type = $_POST['tire_type'];
    $status = $_POST['status'];
    $services_selected = $_POST['services'] ?? [];
    
    try {
        $conn->begin_transaction();
        
        // Обновляем заказ
        $update_sql = "UPDATE order_tire_services 
                      SET client_name = ?, client_phone = ?, car_model = ?, car_plate = ?, 
                          radius = ?, tire_type = ?, status = ?
                      WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssssissi", $client_name, $client_phone, $car_model, $car_plate, $radius, $tire_type, $status, $order_id);
        $stmt->execute();
        
        // Пересчитываем сумму
        $total_price = 0;
        $services_data = [];
        foreach ($services_selected as $service_id) {
            $service_id = (int)$service_id;
            
            // Находим цену для выбранного радиуса
            $price_sql = "SELECT price FROM tire_prices WHERE tire_service_id = ? AND radius = ?";
            $price_stmt = $conn->prepare($price_sql);
            $price_stmt->bind_param("ii", $service_id, $radius);
            $price_stmt->execute();
            $price_result = $price_stmt->get_result()->fetch_assoc();
            $price = $price_result['price'] ?? 0;
            
            // Определяем количество
            $service_data = array_filter($services, function($s) use ($service_id) {
                return $s['id'] == $service_id;
            });
            $service = reset($service_data);
            $quantity = $service['is_complex'] ? 4 : 1;
            
            $service_total = $price * $quantity;
            $total_price += $service_total;
            
            $services_data[] = [
                'service_id' => $service_id,
                'price' => $price,
                'quantity' => $quantity,
                'total' => $service_total
            ];
        }
        
        // Обновляем сумму и услуги
        $services_json = json_encode($services_data);
        $total_sql = "UPDATE order_tire_services SET total_price = ?, notes = ? WHERE id = ?";
        $total_stmt = $conn->prepare($total_sql);
        $total_stmt->bind_param("dsi", $total_price, $services_json, $order_id);
        $total_stmt->execute();
        
        $conn->commit();
        $_SESSION['success'] = "Заказ-наряд #$order_id обновлен!";
        header("Location: tire_orders.php");
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Ошибка обновления заказа: " . $e->getMessage();
    }
}

include 'templates/header.php';
?>

<div class="container">
    <div class="header-actions">
        <h1>✏️ Редактирование заказ-наряда #<?= $order_id ?></h1>
        <div class="action-buttons">
            <a href="tire_print.php?id=<?= $order_id ?>" class="btn-1c" target="_blank">🖨️ Печать</a>
            <a href="tire_orders.php" class="btn-1c">← Назад к списку</a>
        </div>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert-enhanced alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-enhanced alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <form method="post" class="tire-order-form">
        <div class="card-1c">
            <div class="card-header-1c">
                <span class="card-header-icon">👤</span> Данные клиента
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>ФИО клиента *</label>
                        <input type="text" name="client_name" class="form-control" 
                               value="<?= htmlspecialchars($order['client_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Телефон *</label>
                        <input type="tel" name="client_phone" class="form-control" 
                               value="<?= htmlspecialchars($order['client_phone']) ?>" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-1c">
            <div class="card-header-1c">
                <span class="card-header-icon">🚗</span> Автомобиль
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Марка и модель *</label>
                        <input type="text" name="car_model" class="form-control" 
                               value="<?= htmlspecialchars($order['car_model']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Гос. номер</label>
                        <input type="text" name="car_plate" class="form-control" 
                               value="<?= htmlspecialchars($order['car_plate'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Тип резины</label>
                        <select name="tire_type" class="form-control">
                            <option value="summer" <?= $order['tire_type'] == 'summer' ? 'selected' : '' ?>>Летняя</option>
                            <option value="winter" <?= $order['tire_type'] == 'winter' ? 'selected' : '' ?>>Зимняя</option>
                            <option value="allseason" <?= $order['tire_type'] == 'allseason' ? 'selected' : '' ?>>Всесезонная</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-1c">
            <div class="card-header-1c">
                <span class="card-header-icon">🛞</span> Работы и статус
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Радиус колес</label>
                        <select name="radius" id="radius-select" class="form-control" required>
                            <option value="15" <?= $order['radius'] == 15 ? 'selected' : '' ?>>R15</option>
                            <option value="16" <?= $order['radius'] == 16 ? 'selected' : '' ?>>R16</option>
                            <option value="17" <?= $order['radius'] == 17 ? 'selected' : '' ?>>R17</option>
                            <option value="18" <?= $order['radius'] == 18 ? 'selected' : '' ?>>R18</option>
                            <option value="19" <?= $order['radius'] == 19 ? 'selected' : '' ?>>R19</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Статус заказа</label>
                        <select name="status" class="form-control">
                            <option value="new" <?= $order['status'] == 'new' ? 'selected' : '' ?>>🆕 Новый</option>
                            <option value="in_progress" <?= $order['status'] == 'in_progress' ? 'selected' : '' ?>>🔧 В работе</option>
                            <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>✅ Готов</option>
                            <option value="issued" <?= $order['status'] == 'issued' ? 'selected' : '' ?>>🚗 Выдан</option>
                        </select>
                    </div>
                </div>

                <div class="services-checkboxes">
                    <h4>Выберите услуги:</h4>
                    <?php 
                    // Получаем выбранные услуги из notes
                    $selected_services = [];
                    if (!empty($order['notes'])) {
                        $services_data = json_decode($order['notes'], true);
                        if (is_array($services_data)) {
                            $selected_services = array_column($services_data, 'service_id');
                        }
                    }
                    ?>
                    
                    <?php foreach ($services as $service): ?>
                    <label class="service-checkbox">
                        <input type="checkbox" name="services[]" value="<?= $service['id'] ?>" 
                               data-complex="<?= $service['is_complex'] ? 'true' : 'false' ?>"
                               data-prices='<?= $service['prices'] ?>'
                               <?= in_array($service['id'], $selected_services) ? 'checked' : '' ?>>
                        <span class="service-name"><?= htmlspecialchars($service['name']) ?></span>
                        <span class="service-price" id="price-<?= $service['id'] ?>">0 руб.</span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div class="total-section">
                    <strong>Итого: <span id="total-price"><?= number_format($order['total_price'], 2) ?></span> руб.</strong>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-1c-primary">💾 Сохранить изменения</button>
            <a href="tire_orders.php" class="btn-1c">❌ Отмена</a>
        </div>
    </form>
</div>

<script>
// Динамический расчет цен
document.getElementById('radius-select').addEventListener('change', updatePrices);
document.querySelectorAll('input[name="services[]"]').forEach(checkbox => {
    checkbox.addEventListener('change', updatePrices);
});

function updatePrices() {
    const radius = parseInt(document.getElementById('radius-select').value);
    let total = 0;
    
    document.querySelectorAll('input[name="services[]"]:checked').forEach(checkbox => {
        const serviceId = checkbox.value;
        const prices = checkbox.dataset.prices.split(',');
        const isComplex = checkbox.dataset.complex === 'true';
        
        // Находим цену для выбранного радиуса
        let price = 0;
        prices.forEach(p => {
            const [r, pr] = p.split(':');
            if (parseInt(r) === radius) {
                price = parseFloat(pr);
            }
        });
        
        // Умножаем на 4 для комплексных услуг
        const quantity = isComplex ? 4 : 1;
        const serviceTotal = price * quantity;
        total += serviceTotal;
        
        // Обновляем отображение цены
        document.getElementById(`price-${serviceId}`).textContent = 
            isComplex ? `${serviceTotal} руб. (4 шт.)` : `${price} руб.`;
    });
    
    document.getElementById('total-price').textContent = total.toFixed(2);
}

// Инициализация цен
updatePrices();
</script>

<?php include 'templates/footer.php'; ?>
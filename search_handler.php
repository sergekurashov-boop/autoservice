<?php
require 'includes/db.php';
session_start();

$query = trim($_GET['query'] ?? '');

if (empty($query)) {
    header("Location: index.php");
    exit;
}

// Ищем по разным сущностям
$results = [];

// Поиск по клиентам
$stmt = $db->prepare("SELECT id, name, phone FROM clients WHERE name LIKE ? OR phone LIKE ?");
$likeQuery = "%$query%";
$stmt->bind_param("ss", $likeQuery, $likeQuery);
$stmt->execute();
$clients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$results['clients'] = $clients;

// Поиск по автомобилям
$stmt = $db->prepare("SELECT id, client_id, model, year, vin FROM cars WHERE model LIKE ? OR vin LIKE ?");
$stmt->bind_param("ss", $likeQuery, $likeQuery);
$stmt->execute();
$cars = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$results['cars'] = $cars;

// Поиск по заказам
$stmt = $db->prepare("SELECT id, client_id, car_id, description FROM orders WHERE description LIKE ?");
$stmt->bind_param("s", $likeQuery);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$results['orders'] = $orders;

include 'templates/header.php';
?>

<div class="container mt-4">
    <h2>Результаты поиска: "<?= htmlspecialchars($query) ?>"</h2>

    <?php if (!empty($results['clients'])): ?>
        <div class="mt-4">
            <h4>Клиенты</h4>
            <ul class="list-group">
                <?php foreach ($results['clients'] as $client): ?>
                    <li class="list-group-item">
                        <a href="client_edit.php?id=<?= $client['id'] ?>">
                            <?= htmlspecialchars($client['name']) ?> (<?= htmlspecialchars($client['phone']) ?>)
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($results['cars'])): ?>
        <div class="mt-4">
            <h4>Автомобили</h4>
            <ul class="list-group">
                <?php foreach ($results['cars'] as $car): ?>
                    <li class="list-group-item">
                        <a href="car_edit.php?id=<?= $car['id'] ?>">
                            <?= htmlspecialchars($car['model']) ?> (<?= htmlspecialchars($car['year']) ?>, VIN: <?= htmlspecialchars($car['vin']) ?>)
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($results['orders'])): ?>
        <div class="mt-4">
            <h4>Заказы</h4>
            <ul class="list-group">
                <?php foreach ($results['orders'] as $order): ?>
                    <li class="list-group-item">
                        <a href="order_edit.php?id=<?= $order['id'] ?>">
                            Заказ #<?= $order['id'] ?>: <?= htmlspecialchars(mb_substr($order['description'], 0, 50)) ?>...
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (empty($results['clients']) && empty($results['cars']) && empty($results['orders'])): ?>
        <div class="alert alert-info mt-4">
            Ничего не найдено.
        </div>
    <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?>
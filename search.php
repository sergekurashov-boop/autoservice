<?php
require 'includes/db.php';
session_start();

define('ACCESS', true);
include 'templates/header.php';

$searchTerm = trim($_GET['q'] ?? '');

if (empty($searchTerm)) {
    echo '<div class="container mt-4"><div class="alert alert-warning">Введите поисковый запрос</div></div>';
    include 'templates/footer.php';
    exit;
}

// Подготавливаем поисковый термин для использования в SQL
$searchTerm = $conn->real_escape_string($searchTerm);
$searchPattern = "%$searchTerm%";

?>

<div class="container mt-4">
    <h2>Результаты поиска: "<?= htmlspecialchars($searchTerm) ?>"</h2>
    
    <!-- Поиск по заказам -->
    <div class="card mb-4">
        <div class="card-header">Заказы</div>
        <div class="card-body">
            <?php
            $stmt = $conn->prepare("
                SELECT o.id, o.created, o.description, o.status, o.total,
                       car.make, car.model, car.license_plate,
                       cl.name AS client_name
                FROM orders o
                JOIN cars car ON o.car_id = car.id
                LEFT JOIN clients cl ON car.client_id = cl.id
                WHERE o.id LIKE ? 
                   OR o.description LIKE ?
                   OR o.status LIKE ?
                   OR o.total LIKE ?
                ORDER BY o.created DESC
            ");
            $stmt->bind_param("ssss", $searchPattern, $searchPattern, $searchPattern, $searchPattern);
            $stmt->execute();
            $orders = $stmt->get_result();
            
            if ($orders->num_rows > 0): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Дата</th>
                            <th>Клиент</th>
                            <th>Автомобиль</th>
                            <th>Описание</th>
                            <th>Статус</th>
                            <th>Сумма</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($order = $orders->fetch_assoc()): ?>
                        <tr>
                            <td><?= $order['id'] ?></td>
                            <td><?= $order['created'] ?></td>
                            <td><?= htmlspecialchars($order['client_name'] ?? 'Не указан') ?></td>
                            <td><?= htmlspecialchars($order['make']) ?> <?= htmlspecialchars($order['model']) ?> (<?= htmlspecialchars($order['license_plate']) ?>)</td>
                            <td><?= htmlspecialchars($order['description']) ?></td>
                            <td><?= htmlspecialchars($order['status']) ?></td>
                            <td><?= number_format($order['total'], 2) ?> руб.</td>
                            <td>
                                <a href="order_edit.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-warning">Редактировать</a>
                                <a href="order_print.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-info" target="_blank">Печать</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted"><span style="color: #ffd700;">Заказы не найдены</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Поиск по клиентам -->
    <div class="card mb-4">
        <div class="card-header">Клиенты</div>
        <div class="card-body">
            <?php
            $stmt = $conn->prepare("
                SELECT * FROM clients 
                WHERE name LIKE ? 
                   OR phone LIKE ? 
                   OR email LIKE ?
            ");
            $stmt->bind_param("sss", $searchPattern, $searchPattern, $searchPattern);
            $stmt->execute();
            $clients = $stmt->get_result();
            
            if ($clients->num_rows > 0): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ФИО</th>
                            <th>Телефон</th>
                            <th>Email</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($client = $clients->fetch_assoc()): ?>
                        <tr>
                            <td><?= $client['id'] ?></td>
                            <td><?= htmlspecialchars($client['name']) ?></td>
                            <td><?= htmlspecialchars($client['phone']) ?></td>
                            <td><?= htmlspecialchars($client['email']) ?></td>
                            <td>
                                <a href="client_edit.php?id=<?= $client['id'] ?>" class="btn btn-sm btn-warning">Редактировать</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted"><span style="color: #fffff;">Клиенты не найдены</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Поиск по автомобилям -->
    <div class="card mb-4">
        <div class="card-header">Автомобили</div>
        <div class="card-body">
            <?php
            $stmt = $conn->prepare("
                SELECT cars.*, clients.name AS client_name 
                FROM cars
                LEFT JOIN clients ON cars.client_id = clients.id
                WHERE make LIKE ? 
                   OR model LIKE ? 
                   OR year LIKE ?
                   OR vin LIKE ?
                   OR license_plate LIKE ?
                   OR clients.name LIKE ?
            ");
            $stmt->bind_param("ssssss", $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern);
            $stmt->execute();
            $cars = $stmt->get_result();
            
            if ($cars->num_rows > 0): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Владелец</th>
                            <th>Марка</th>
                            <th>Модель</th>
                            <th>Год</th>
                            <th>VIN</th>
                            <th>Гос. номер</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($car = $cars->fetch_assoc()): ?>
                        <tr>
                            <td><?= $car['id'] ?></td>
                            <td><?= htmlspecialchars($car['client_name'] ?? 'Не указан') ?></td>
                            <td><?= htmlspecialchars($car['make']) ?></td>
                            <td><?= htmlspecialchars($car['model']) ?></td>
                            <td><?= $car['year'] ?></td>
                            <td><?= htmlspecialchars($car['vin']) ?></td>
                            <td><?= htmlspecialchars($car['license_plate']) ?></td>
                            <td>
                                <a href="car_edit.php?id=<?= $car['id'] ?>" class="btn btn-sm btn-warning">Редактировать</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted"><span style="color: #ffd700;">Автомобили не найдены</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
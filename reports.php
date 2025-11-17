<?php
require 'includes/db.php';
session_start();

// Включаем шапку с навбаром
define('ACCESS', true);
include 'templates/header.php';

// Получение параметров отчета

$start = $_GET['start'] ?? date('Y-m-01');

$end = $_GET['end'] ?? date('Y-m-d');

// Запрос для отчета

$report = $conn->query("

SELECT

o.id,

c.name AS client,

CONCAT(car.make, ' ', car.model) AS car,

o.created,

o.total,

o.status

FROM orders o

JOIN cars car ON o.car_id = car.id

JOIN clients c ON car.client_id = c.id

WHERE o.created BETWEEN '$start' AND '$end'

ORDER BY o.created DESC

");

$total = 0;

while ($row = $report->fetch_assoc()) {

$total += $row['total'];

}

?>

<!DOCTYPE html>

<html lang="ru">

<head>

<meta charset="UTF-8">

<title>Отчеты</title>
<!-- Подключение Bootstrap CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- Подключение иконок -->
    <link rel="stylesheet" href="assets/icons/font/bootstrap-icons.css">
</head>

<body>


<div class="container mt-4">

<h1><span style="color: #ffd700;">Отчеты</span></h1>

<div class="card mb-4">

<div class="card-header">Фильтр отчета</div>

<div class="card-body">

<form method="get">

<div class="row">

<div class="col-md-4">

<label class="form-label">Начальная дата:</label>

<input type="date" name="start" class="form-control" value="<?= $start ?>">

</div>

<div class="col-md-4">

<label class="form-label">Конечная дата:</label>

<input type="date" name="end" class="form-control" value="<?= $end ?>">

</div>

<div class="col-md-4 align-self-end">

<button type="submit" class="btn btn-primary">Сформировать</button>

</div>

</div>

</form>

</div>

</div>

<div class="card">

<div class="card-header">Отчет по заказам (<?= $start ?> - <?= $end ?>)</div>

<div class="card-body">

<table class="table table-striped">

<thead>

<tr>

<th>ID</th>

<th>Клиент</th>

<th>Автомобиль</th>

<th>Дата</th>

<th>Сумма</th>

<th>Статус</th>

</tr>

</thead>

<tbody>

<?php

$report = $conn->query("

SELECT

o.id,

c.name AS client,

CONCAT(car.make, ' ', car.model) AS car,

o.created,

o.total,

o.status

FROM orders o

JOIN cars car ON o.car_id = car.id

JOIN clients c ON car.client_id = c.id

WHERE o.created BETWEEN '$start' AND '$end'

ORDER BY o.created DESC

");

?>

<?php while($row = $report->fetch_assoc()): ?>

<tr>

<td><?= $row['id'] ?></td>

<td><?= $row['client'] ?></td>

<td><?= $row['car'] ?></td>

<td><?= $row['created'] ?></td>

<td><?= $row['total'] ?> руб.</td>

<td><?= $row['status'] ?></td>

</tr>

<?php endwhile; ?>

</tbody>

<tfoot>

<tr class="table-success">

<td colspan="4" class="text-end"><strong>Итого:</strong></td>

<td colspan="2"><strong><?= $total ?> руб.</strong></td>

</tr>

</tfoot>

</table>

</div>

</div>

</div>
<hr>
<!-- Закрываем container-fluid -->

     
<?php include 'templates/footer.php'; ?>

</body>


</html>

<?php
// Начало буферизации вывода
ob_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Автосервис Лавров</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        body { padding-top: 56px; }
        .main-container { min-height: calc(100vh - 120px); }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-warning">
    <div class="container">
        <a class="navbar-brand" href="index.php">АВТОСЕРВИС</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
		<style>
		/* Изменение цвета текста для всех элементов навбара */
.navbar-dark .navbar-brand,
.navbar-dark .navbar-nav .nav-link {
    color: #000000; /* Черный цвет */
}

/* Изменение цвета при наведении */
.navbar-dark .navbar-nav .nav-link:hover {
    color: #ff0000; /* Красный цвет */
}
</style>
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="clients.php">КЛИЕНТЫ</a></li>
                <li class="nav-item"><a class="nav-link" href="cars.php">АВТОМОБИЛИ</a></li>
                <li class="nav-item"><a class="nav-link" href="orders.php">ЗАКАЗЫ</a></li>
                <li class="nav-item"><a class="nav-link" href="services.php">УСЛУГИ</a></li>
                <li class="nav-item"><a class="nav-link" href="parts.php">ЗАПЧАСТИ</a></li>
                <li class="nav-item"><a class="nav-link" href="reports.php">ОТЧЕТЫ</a></li>
				
            </ul>
			        </div>
					    </div>
						</nav>
						

            
            </div>
        </div>
    </div>

<div class="container main-container py-4 mt-2">
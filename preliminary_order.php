<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAnyRole(['admin', 'manager', 'reception']);

$inspection_id = $_GET['inspection_id'] ?? null;
// ... получение данных из акта осмотра ...

// Форма предварительного заказа с согласованными работами и запчастями
// Возможность указать источник запчастей (клиент/сервис)
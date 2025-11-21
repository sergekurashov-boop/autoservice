<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAnyRole(['admin', 'manager', 'reception', 'mechanic']);

$request_id = $_GET['id'] ?? null;
// ... получение данных задания на осмотр ...

// Кнопка для перехода к созданию акта осмотра
echo '<a href="inspection_create.php?request_id=' . $request_id . '" class="btn btn-primary">📝 Перейти к акту осмотра</a>';
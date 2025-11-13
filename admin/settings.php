<?php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die('❌ Доступ только для администраторов');
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Настройки системы</title>
</head>
<body>
    <h1>⚙️ Настройки системы</h1>
    <p>Раздел в разработке</p>
    <p><a href="user_management.php">← К пользователям</a></p>
</body>
</html>
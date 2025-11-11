<?php
session_start();

if (!isset($_SESSION['booking_id'])) {
    header('Location: booking.php');
    exit;
}

$booking_id = $_SESSION['booking_id'];
unset($_SESSION['booking_id']); // Очистить, чтобы не повторять

require_once 'db.php';

// Получаем информацию о бронировании
$stmt = $conn->prepare("
    SELECT a.date, a.start_time, a.end_time, s.name AS service_name, m.name AS mechanic_name, a.client_name
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN mechanics m ON a.mechanic_id = m.id
    WHERE a.id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    echo "Бронирование не найдено.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Бронирование успешно</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>

<div class="container mt-4">
    <h1>Спасибо за запись, <?= htmlspecialchars($booking['client_name']) ?>!</h1>
    <p>Ваше бронирование успешно создано.</p>

    <ul>
        <li><strong>Услуга:</strong> <?= htmlspecialchars($booking['service_name']) ?></li>
        <li><strong>Исполнитель:</strong> <?= htmlspecialchars($booking['mechanic_name']) ?></li>
        <li><strong>Дата:</strong> <?= htmlspecialchars($booking['date']) ?></li>
        <li><strong>Время:</strong> <?= htmlspecialchars($booking['start_time']) ?> - <?= htmlspecialchars($booking['end_time']) ?></li>
    </ul>

    <a href="booking.php" class="btn btn-primary">Сделать новую запись</a>
</div>

</body>
</html>
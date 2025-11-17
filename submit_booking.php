<?php
session_start();
ob_start(); // включаем буферизацию вывода

// Здесь должна быть ваша логика соединения с базой, например:
$host = 'localhost';
$db_name = 'autoservice'; // замените на имя вашей базы
$username = 'root'; // у вас, как я понял, без пароля
$password = '';
$dsn = "mysql:host=$host;dbname=$db_name;charset=utf8";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Ошибка подключения к базе: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получение данных
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $service_id = $_POST['service_id'] ?? '';
    $service_name = $_POST['service_name'] ?? 'Название услуги'; // можно получать из базы
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';

    // Проверка обязательных полей
    if ($name && $phone && $service_id && $date && $time) {
        // --- Здесь вставьте вашу логику сохранения в базу ---
        // Например:
        // Реальное сохранение
$stmt = $pdo->prepare("INSERT INTO bookings (name, phone, service_id, service_name, date, time) VALUES (?, ?, ?, ?, ?, ?)");
$success = $stmt->execute([$name, $phone, $service_id, $service_name, $date, $time]);
        // Для примера — считаем, что успешно
        $success = true;

        if ($success) {
            // Сохраняем детали в сессию
            $_SESSION['booking_confirmed'] = true;
            $_SESSION['booking_details'] = [
                'name' => $name,
                'service_name' => $service_name,
                'date' => $date,
                'time' => $time,
            ];

            // Редирект на страницу успеха
            header('Location: booking_success.php');
            exit;
        } else {
            echo "Ошибка при сохранении бронирования.";
        }
    } else {
        echo "Пожалуйста, заполните все обязательные поля.";
    }
}
?>
<?php
session_start();
// Здесь можно добавить проверку, что пользователь — админ (например, по сессии)

require_once 'db.php';

// Обработка изменения статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'], $_POST['status'])) {
    $appointment_id = (int)$_POST['appointment_id'];
    $status = $_POST['status'];

    $allowed_statuses = ['pending', 'confirmed', 'cancelled'];
    if (in_array($status, $allowed_statuses)) {
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $appointment_id);
        $stmt->execute();
    }
    header('Location: admin_appointments.php');
    exit;
}

// Получаем список записей с информацией
$sql = "
    SELECT a.id, a.date, a.start_time, a.end_time, s.name AS service_name, m.name AS mechanic_name, a.client_name, a.client_phone, a.client_email, a.status
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN mechanics m ON a.mechanic_id = m.id
    ORDER BY a.date DESC, a.start_time DESC
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8" />
<title>Админка: Управление записями</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-4">
    <h1>Управление записями</h1>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Клиент</th>
                <th>Телефон</th>
                <th>Email</th>
                <th>Услуга</th>
                <th>Исполнитель</th>
                <th>Дата</th>
                <th>Время</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['client_name']) ?></td>
                <td><?= htmlspecialchars($row['client_phone']) ?></td>
                <td><?= htmlspecialchars($row['client_email']) ?></td>
                <td><?= htmlspecialchars($row['service_name']) ?></td>
                <td><?= htmlspecialchars($row['mechanic_name']) ?></td>
                <td><?= htmlspecialchars($row['date']) ?></td>
                <td><?= htmlspecialchars($row['start_time']) ?> - <?= htmlspecialchars($row['end_time']) ?></td>
                <td>
                    <?php
                    $badgeClass = 'secondary';
                    if ($row['status'] === 'confirmed') $badgeClass = 'success';
                    elseif ($row['status'] === 'cancelled') $badgeClass = 'danger';
                    elseif ($row['status'] === 'pending') $badgeClass = 'warning';
                    ?>
                    <span class="badge bg-<?= $badgeClass ?>">
                        <?= htmlspecialchars(ucfirst($row['status'])) ?>
                    </span>
                </td>
                <td>
                    <form method="post" style="display:inline-block;">
                        <input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
                        <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                            <option value="pending" <?= $row['status'] === 'pending' ? 'selected' : '' ?>>Ожидает</option>
                            <option value="confirmed" <?= $row['status'] === 'confirmed' ? 'selected' : '' ?>>Подтверждена</option>
                            <option value="cancelled" <?= $row['status'] === 'cancelled' ? 'selected' : '' ?>>Отменена</option>
                        </select>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
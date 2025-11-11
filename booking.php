<?php
session_start();
require_once 'includes/db.php';
$current_page = basename($_SERVER['PHP_SELF']);
// Включаем шапку с навбаром
define('ACCESS', true);
include 'templates/header.php'; 
$errors = [];

// Получаем услуги
try {
    $stmt = $conn->query("SELECT id, name, duration FROM services");
    $services = $stmt->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $errors[] = "Ошибка: " . $e->getMessage();
}

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $service_id = (int)($_POST['service_id'] ?? 0);
    $mechanic_id = (int)($_POST['mechanic_id'] ?? 0);
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';

    // Валидация
    if (empty($name)) $errors[] = "Введите имя";
    if (empty($phone)) $errors[] = "Введите телефон";
    if ($service_id <= 0) $errors[] = "Выберите услугу";
    if ($mechanic_id <= 0) $errors[] = "Выберите исполнителя";
    if (empty($date)) $errors[] = "Выберите дату";
    if (empty($time)) $errors[] = "Выберите время";

    if (empty($errors)) {
        // Получаем длительность услуги
        $service = null;
        foreach ($services as $s) {
            if ($s['id'] == $service_id) {
                $service = $s;
                break;
            }
        }

        if (!$service) {
            $errors[] = "Выбранная услуга не найдена.";
        } else {
            $duration = (int)$service['duration'];
            $start_time = $time;
            $end_time = date('H:i', strtotime($time) + $duration * 60);

            // Проверка занятости механика
            $stmtCheck = $conn->prepare("
                SELECT COUNT(*) FROM appointments
                WHERE mechanic_id = ?
                  AND date = ?
                  AND (
                        (start_time <= ? AND end_time > ?) OR
                        (start_time < ? AND end_time >= ?) OR
                        (start_time >= ? AND end_time <= ?)
                      )
            ");
            $stmtCheck->bind_param(
                "isssssss",
                $mechanic_id,
                $date,
                $start_time, $start_time,
                $end_time, $end_time,
                $start_time, $end_time
            );
            $stmtCheck->execute();
            $stmtCheck->bind_result($count);
            $stmtCheck->fetch();
            $stmtCheck->close();

            if ($count > 0) {
                $errors[] = "Выбранный механик занят в это время. Пожалуйста, выберите другое время.";
            } else {
                // Вставка бронирования
                $stmt = $conn->prepare("INSERT INTO appointments (date, start_time, end_time, service_id, mechanic_id, client_name, client_phone, client_email) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssiisss", $date, $start_time, $end_time, $service_id, $mechanic_id, $name, $phone, $email);
                if ($stmt->execute()) {
                    $_SESSION['booking_id'] = $conn->insert_id;
                    header('Location: booking_success.php');
                    exit;
                } else {
                    $errors[] = "Ошибка базы данных: " . $stmt->error;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Запись на услугу</title>
<link rel="stylesheet" href="/autoservice/assets/css/bootstrap.min.css">
<body>

<div class="container mt-4">
    <h1><span style="color: #076cd9;">Запись на услугу</h1>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
        <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
        <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="post" action="booking.php" id="bookingForm">
        <div class="mb-3">
            <label for="name" class="form-label"><span style="color: #ffffff;">Имя *</label>
            <input type="text" class="form-control" id="name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label"><span style="color: #ffffff;">Телефон *</label>
            <input type="tel" class="form-control" id="phone" name="phone" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label"><span style="color: #ffffff;">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="service_id" class="form-label"><span style="color: #ffffff;">Услуга *</label>
            <select class="form-select" id="service_id" name="service_id" required>
                <option value="">-- Выберите услугу --</option>
                <?php foreach ($services as $service): ?>
                <option value="<?= $service['id'] ?>" <?= (isset($_POST['service_id']) && $_POST['service_id'] == $service['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($service['name']) ?> (<?= $service['duration'] ?> мин)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="mechanic_id" class="form-label"><span style="color: #ffffff;">Исполнитель *</label>
            <select class="form-select" id="mechanic_id" name="mechanic_id" required disabled>
                <option value="">-- Сначала выберите услугу --</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="date" class="form-label"><span style="color: #ffffff;">Дата *</label>
            <input type="date" class="form-control" id="date" name="date" required value="<?= htmlspecialchars($_POST['date'] ?? '') ?>" min="<?= date('Y-m-d') ?>">
        </div>
        <div class="mb-3">
            <label for="time" class="form-label"><span style="color: #ffffff;">Время *</label>
            <select class="form-select" id="time" name="time" required disabled>
                <option value="">-- Сначала выберите дату, услугу и исполнителя --</option>
            </select>
            <div id="loadingTimes" class="mt-2" style="display:none">
                <div class="spinner-border spinner-border-sm" role="status"></div> Загрузка времени...
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><span style="color: #ffffff;">Записаться</button>
    </form>
</div>

<script>
// Функция загрузки механиков по услуге
function updateMechanics() {
    const serviceId = document.getElementById('service_id').value;
    const mechanicSelect = document.getElementById('mechanic_id');

    if (!serviceId) {
        mechanicSelect.disabled = true;
        mechanicSelect.innerHTML = '<option value="">-- Сначала выберите услугу --</option>';
        updateAvailableTimes(); // Очистить время
        return;
    }

    mechanicSelect.disabled = true;
    mechanicSelect.innerHTML = '<option>Загрузка...</option>';

    fetch(`load_mechanics.php?service_id=${serviceId}`)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                mechanicSelect.innerHTML = `<option value="">${data.error}</option>`;
            } else {
                mechanicSelect.innerHTML = '';
                if (data.mechanics.length === 0) {
                    mechanicSelect.innerHTML = '<option value="">Нет доступных исполнителей</option>';
                } else {
                    data.mechanics.forEach(m => {
                        const option = document.createElement('option');
                        option.value = m.id;
                        option.textContent = m.name;
                        mechanicSelect.appendChild(option);
                    });
                }
            }
            mechanicSelect.disabled = false;

            <?php if (isset($_POST['mechanic_id'])): ?>
                mechanicSelect.value = <?= (int)$_POST['mechanic_id'] ?>;
            <?php endif; ?>
            updateAvailableTimes();
        })
        .catch(() => {
            mechanicSelect.innerHTML = '<option value="">Ошибка загрузки исполнителей</option>';
            mechanicSelect.disabled = false;
        });
}

// Функция загрузки доступного времени
function updateAvailableTimes() {
    const serviceId = document.getElementById('service_id').value;
    const date = document.getElementById('date').value;
    const mechanicId = document.getElementById('mechanic_id').value;
    const timeSelect = document.getElementById('time');
    const loading = document.getElementById('loadingTimes');

    if (!serviceId || !date || !mechanicId) {
        timeSelect.disabled = true;
        timeSelect.innerHTML = '<option value="">-- Сначала выберите дату, услугу и исполнителя --</option>';
        return;
    }

    timeSelect.disabled = true;
    loading.style.display = 'block';

    fetch(`load_times.php?date=${date}&service_id=${serviceId}&mechanic_id=${mechanicId}`)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                timeSelect.innerHTML = `<option value="">${data.error}</option>`;
            } else {
                timeSelect.innerHTML = '';
                if (data.available_times.length === 0) {
                    timeSelect.innerHTML = '<option value="">Нет доступного времени</option>';
                } else {
                    data.available_times.forEach(time => {
                        const option = document.createElement('option');
                        option.value = time;
                        option.textContent = time;
                        timeSelect.appendChild(option);
                    });
                }
            }
            timeSelect.disabled = false;
            loading.style.display = 'none';

            <?php if (isset($_POST['time'])): ?>
                timeSelect.value = "<?= htmlspecialchars($_POST['time']) ?>";
            <?php endif; ?>
        })
        .catch(() => {
            timeSelect.innerHTML = '<option value="">Ошибка загрузки времени</option>';
            timeSelect.disabled = false;
            loading.style.display = 'none';
        });
}

document.getElementById('service_id').addEventListener('change', () => {
    updateMechanics();
});
document.getElementById('date').addEventListener('change', updateAvailableTimes);
document.getElementById('mechanic_id').addEventListener('change', updateAvailableTimes);

// При загрузке страницы, если услуга выбрана, загрузить механиков и время
window.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('service_id').value) {
        updateMechanics();
    }
    if (document.getElementById('date').value) {
        updateAvailableTimes();
    }
});
</script>

<?php include 'templates/footer.php'; ?>

</body>
</html>
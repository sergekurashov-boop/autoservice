<?php
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAuth();

// Обработка создания заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_id = (int)$_POST['car_id'];
    $description = trim($_POST['description']);

    if (empty($description)) {
        $_SESSION['error'] = "Пожалуйста, укажите описание проблемы";
    } else {
        $stmt = $conn->prepare("INSERT INTO orders (car_id, description, status) VALUES (?, ?, 'В ожидании')");
        $stmt->bind_param("is", $car_id, $description);
        
        if ($stmt->execute()) {
            $order_id = $conn->insert_id;
            $_SESSION['success'] = "Заказ #$order_id успешно создан!";
            header("Location: order_edit.php?id=$order_id");
            exit;
        } else {
            $_SESSION['error'] = "Ошибка при создании заказа: " . $conn->error;
        }
    }
}

// Если клиент выбран из clients.php
$selected_client_id = null;
if (isset($_GET['selected_client'])) {
    $selected_client_id = (int)$_GET['selected_client'];
}

include 'templates/header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Создание нового заказа</title>
    <link href="assets/css/orders.css" rel="stylesheet">
</head>
<body>
    <div class="orders-container">
        <div class="container-header">
            <h1 class="page-title">Создание нового заказа</h1>
            <a href="orders.php" class="btn-1c-outline">
                ← Назад к заказам
            </a>
        </div>

        <div class="form-container-full">
            <div class="enhanced-card">
                <div class="enhanced-card-header">
                    <span class="card-header-icon">📋</span> Создание нового заказа
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert-enhanced alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <form method="post" id="orderForm" class="client-form">
                        <!-- Выбор клиента -->
                        <div class="form-group">
                            <label class="form-label">Клиент *</label>
                            
                            <div class="search-input-group">
                                <a href="clients.php?return_to=create_order" class="btn-1c-primary">
                                    🔍 Выбрать клиента из базы
                                </a>
                                <span class="form-help-text">или</span>
                                <a href="clients.php" class="btn-1c-outline" target="_blank">
                                    + Добавить нового клиента
                                </a>
                            </div>
                            
                            <!-- Будет заполнено после выбора клиента -->
                            <div id="selectedClientInfo" style="display: none;">
                                <div class="client-info-card mt-3">
                                    <div class="client-info-content">
                                        <div>
                                            <h5 id="selectedClientName"></h5>
                                            <div id="selectedClientPhone"></div>
                                        </div>
                                        <button type="button" class="btn-1c-outline btn-small" 
                                                onclick="clearClientSelection()">
                                            ✕ Изменить
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" id="selectedClientId" name="client_id">
                            </div>
                        </div>

                        <!-- Выбор автомобиля -->
                        <div class="form-group">
                            <label for="carSelect" class="form-label">Автомобиль *</label>
                            <div class="select-group">
                                <select name="car_id" id="carSelect" class="form-control form-select" required disabled>
                                    <option value="">Сначала выберите клиента</option>
                                </select>
                                <a href="cars.php" class="btn-1c-outline" target="_blank">
                                    + Добавить авто
                                </a>
                            </div>
                        </div>

                        <!-- Описание проблемы -->
                        <div class="form-group">
                            <label for="description" class="form-label">Описание проблемы *</label>
                            <textarea name="description" id="description" class="form-control textarea-large" rows="4" required 
                                      placeholder="Опишите проблему или необходимые работы..."
                                      autocomplete="off"></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-1c-primary btn-large" id="createOrderBtn" disabled>
                                ✅ Создать заказ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Обработка выбранного клиента из URL
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($selected_client_id): ?>
            loadClientInfo(<?= $selected_client_id ?>);
        <?php endif; ?>
    });

    function loadClientInfo(clientId) {
        // Загружаем информацию о клиенте
        fetch('get_client_info.php?id=' + clientId)
            .then(response => response.json())
            .then(client => {
                if (client.id) {
                    document.getElementById('selectedClientName').textContent = client.name;
                    document.getElementById('selectedClientPhone').textContent = client.phone;
                    document.getElementById('selectedClientId').value = client.id;
                    document.getElementById('selectedClientInfo').style.display = 'block';
                    
                    // Загружаем автомобили клиента
                    loadClientCars(clientId);
                }
            })
            .catch(error => {
                console.error('Error loading client info:', error);
                alert('Ошибка загрузки информации о клиенте');
            });
    }

    function clearClientSelection() {
        document.getElementById('selectedClientInfo').style.display = 'none';
        document.getElementById('selectedClientId').value = '';
        document.getElementById('carSelect').disabled = true;
        document.getElementById('carSelect').innerHTML = '<option value="">Сначала выберите клиента</option>';
        checkFormCompletion();
    }

    function loadClientCars(clientId) {
        fetch('get_client_cars.php?client_id=' + clientId)
            .then(response => response.json())
            .then(cars => {
                const carSelect = document.getElementById('carSelect');
                carSelect.disabled = false;
                carSelect.innerHTML = '<option value="">Выберите автомобиль</option>';
                
                if (cars.length > 0) {
                    cars.forEach(car => {
                        const option = document.createElement('option');
                        option.value = car.id;
                        let carText = `${car.make} ${car.model}`;
                        if (car.year) carText += ` (${car.year})`;
                        if (car.license_plate) carText += ` - ${car.license_plate}`;
                        option.textContent = carText;
                        carSelect.appendChild(option);
                    });
                } else {
                    carSelect.innerHTML = '<option value="">У клиента нет автомобилей</option>';
                }
                
                checkFormCompletion();
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('carSelect').innerHTML = '<option value="">Ошибка загрузки</option>';
            });
    }

    function checkFormCompletion() {
        const clientId = document.getElementById('selectedClientId').value;
        const carId = document.getElementById('carSelect').value;
        const createOrderBtn = document.getElementById('createOrderBtn');
        
        createOrderBtn.disabled = !(clientId && carId);
    }

    document.getElementById('carSelect').addEventListener('change', checkFormCompletion);
    </script>
    <?php include 'templates/footer.php'; ?>
</body>
</html>
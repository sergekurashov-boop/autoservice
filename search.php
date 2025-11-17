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
                        <!-- Поиск автомобиля по гос. номеру -->
                        <div class="form-group">
                            <label class="form-label">Поиск автомобиля *</label>
                            
                            <div class="search-input-group">
                                <input type="text" 
                                       id="licensePlateSearch" 
                                       class="form-control" 
                                       placeholder="Введите гос. номер..."
                                       autocomplete="off">
                                <button type="button" class="btn-1c-primary" onclick="searchCars()">
                                    🔍 Найти
                                </button>
                            </div>
                            <div class="form-help-text">
                                Начните вводить гос. номер для поиска автомобиля в базе
                            </div>
                            
                            <!-- Результаты поиска -->
                            <div id="searchResults" class="search-results" style="display: none;">
                                <div class="search-results-header">Найденные автомобили:</div>
                                <div id="carsList" class="cars-list"></div>
                            </div>
                            
                            <!-- Выбранный автомобиль -->
                            <div id="selectedCarInfo" class="selected-car-info" style="display: none;">
                                <div class="client-info-card mt-3">
                                    <div class="client-info-content">
                                        <div>
                                            <h5 id="selectedCarTitle"></h5>
                                            <div id="selectedCarDetails"></div>
                                            <div id="selectedCarOwner"></div>
                                        </div>
                                        <button type="button" class="btn-1c-outline btn-small" 
                                                onclick="clearCarSelection()">
                                            ✕ Изменить
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" id="selectedCarId" name="car_id">
                            </div>
                        </div>

                        <!-- Альтернатива: выбор через клиента -->
                        <div class="form-group">
                            <label class="form-label">Или выберите через клиента</label>
                            <div class="search-input-group">
                                <a href="clients.php?return_to=create_order" class="btn-1c-outline">
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
                            
                            <!-- Выбор автомобиля клиента -->
                            <div id="clientCarsSection" style="display: none;">
                                <label for="carSelect" class="form-label">Автомобиль клиента *</label>
                                <div class="select-group">
                                    <select name="car_id" id="carSelect" class="form-control form-select">
                                        <option value="">Выберите автомобиль</option>
                                    </select>
                                </div>
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
    let selectedCarMethod = null; // 'search' или 'client'

    // Поиск автомобилей по гос. номеру
    function searchCars() {
        const licensePlate = document.getElementById('licensePlateSearch').value.trim();
        if (!licensePlate) {
            alert('Введите гос. номер для поиска');
            return;
        }

        const resultsDiv = document.getElementById('searchResults');
        const carsList = document.getElementById('carsList');
        
        carsList.innerHTML = '<div class="loading">Поиск...</div>';
        resultsDiv.style.display = 'block';

        fetch('search_cars.php?license_plate=' + encodeURIComponent(licensePlate))
            .then(response => response.json())
            .then(cars => {
                carsList.innerHTML = '';
                
                if (cars.length > 0) {
                    cars.forEach(car => {
                        const carElement = document.createElement('div');
                        carElement.className = 'car-item';
                        carElement.innerHTML = `
                            <div class="car-info">
                                <strong>${car.make} ${car.model}</strong>
                                ${car.year ? `(${car.year})` : ''}
                                <div class="car-details">
                                    Гос. номер: ${car.license_plate} | 
                                    VIN: ${car.vin || 'не указан'} |
                                    Владелец: ${car.client_name}
                                </div>
                            </div>
                            <button type="button" class="btn-1c-primary btn-small" 
                                    onclick="selectCarFromSearch(${car.id}, '${car.make}', '${car.model}', ${car.year || 'null'}, '${car.license_plate}', '${car.client_name}')">
                                Выбрать
                            </button>
                        `;
                        carsList.appendChild(carElement);
                    });
                } else {
                    carsList.innerHTML = `
                        <div class="no-results">
                            Автомобиль не найден. 
                            <a href="cars.php" class="btn-1c-outline btn-small" target="_blank">
                                + Добавить новый автомобиль
                            </a>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                carsList.innerHTML = '<div class="error">Ошибка поиска</div>';
            });
    }

    // Выбор автомобиля из результатов поиска
    function selectCarFromSearch(carId, make, model, year, licensePlate, owner) {
        selectedCarMethod = 'search';
        
        // Скрываем результаты поиска
        document.getElementById('searchResults').style.display = 'none';
        
        // Показываем выбранный автомобиль
        const selectedCarInfo = document.getElementById('selectedCarInfo');
        document.getElementById('selectedCarTitle').textContent = `${make} ${model}`;
        
        let details = `Гос. номер: ${licensePlate}`;
        if (year) details += ` | Год: ${year}`;
        document.getElementById('selectedCarDetails').textContent = details;
        
        document.getElementById('selectedCarOwner').textContent = `Владелец: ${owner}`;
        document.getElementById('selectedCarId').value = carId;
        selectedCarInfo.style.display = 'block';
        
        // Скрываем секцию выбора через клиента (если была активна)
        clearClientSelection();
        
        checkFormCompletion();
    }

    // Очистка выбора автомобиля
    function clearCarSelection() {
        selectedCarMethod = null;
        document.getElementById('selectedCarInfo').style.display = 'none';
        document.getElementById('selectedCarId').value = '';
        document.getElementById('licensePlateSearch').value = '';
        document.getElementById('searchResults').style.display = 'none';
        checkFormCompletion();
    }

    // Обработка выбора через клиента
    function loadClientInfo(clientId) {
        fetch('get_client_info.php?id=' + clientId)
            .then(response => response.json())
            .then(client => {
                if (client.id) {
                    selectedCarMethod = 'client';
                    
                    document.getElementById('selectedClientName').textContent = client.name;
                    document.getElementById('selectedClientPhone').textContent = client.phone;
                    document.getElementById('selectedClientId').value = client.id;
                    document.getElementById('selectedClientInfo').style.display = 'block';
                    
                    // Скрываем поиск по гос. номеру
                    clearCarSelection();
                    
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
        selectedCarMethod = null;
        document.getElementById('selectedClientInfo').style.display = 'none';
        document.getElementById('clientCarsSection').style.display = 'none';
        document.getElementById('selectedClientId').value = '';
        document.getElementById('carSelect').innerHTML = '<option value="">Выберите автомобиль</option>';
        checkFormCompletion();
    }

    function loadClientCars(clientId) {
        fetch('get_client_cars.php?client_id=' + clientId)
            .then(response => response.json())
            .then(cars => {
                const carSelect = document.getElementById('carSelect');
                const clientCarsSection = document.getElementById('clientCarsSection');
                
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
                    clientCarsSection.style.display = 'block';
                } else {
                    carSelect.innerHTML = '<option value="">У клиента нет автомобилей</option>';
                    clientCarsSection.style.display = 'block';
                }
                
                checkFormCompletion();
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('carSelect').innerHTML = '<option value="">Ошибка загрузки</option>';
                clientCarsSection.style.display = 'block';
            });
    }

    function checkFormCompletion() {
        const carId = document.getElementById('selectedCarId').value || document.getElementById('carSelect').value;
        const description = document.getElementById('description').value.trim();
        const createOrderBtn = document.getElementById('createOrderBtn');
        
        createOrderBtn.disabled = !(carId && description);
    }

    // Обработчики событий
    document.getElementById('licensePlateSearch').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchCars();
        }
    });

    document.getElementById('carSelect').addEventListener('change', checkFormCompletion);
    document.getElementById('description').addEventListener('input', checkFormCompletion);

    // Обработка выбранного клиента из URL
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($_GET['selected_client'])): ?>
            loadClientInfo(<?= (int)$_GET['selected_client'] ?>);
        <?php endif; ?>
    });
    </script>

    <style>
    .search-results {
        border: 1px solid #ddd;
        border-radius: 8px;
        margin-top: 10px;
        max-height: 300px;
        overflow-y: auto;
    }
    
    .search-results-header {
        padding: 10px;
        background: #f8f9fa;
        border-bottom: 1px solid #ddd;
        font-weight: bold;
    }
    
    .cars-list {
        padding: 10px;
    }
    
    .car-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        border-bottom: 1px solid #eee;
    }
    
    .car-item:last-child {
        border-bottom: none;
    }
    
    .car-info {
        flex: 1;
    }
    
    .car-details {
        font-size: 12px;
        color: #666;
        margin-top: 5px;
    }
    
    .loading, .no-results, .error {
        padding: 20px;
        text-align: center;
        color: #666;
    }
    
    .selected-car-info {
        margin-top: 15px;
    }
    </style>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
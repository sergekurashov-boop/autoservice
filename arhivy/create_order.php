<?php
// ВКЛЮЧИТЬ ОШИБКИ ДЛЯ ДЕБАГА
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require 'includes/db.php';
require_once 'auth_check.php';
requireAuth();

// Обработка создания заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = (int)$_POST['client_id'];
    $car_id = (int)$_POST['car_id'];
    $description = trim($_POST['description']);
    $services_data = $_POST['services_data'] ?? '';

    if (empty($client_id) || empty($car_id) || empty($description)) {
        $_SESSION['error'] = "Пожалуйста, заполните все обязательные поля";
    } else {
        $stmt = $conn->prepare("INSERT INTO orders (car_id, description, status) VALUES (?, ?, 'В ожидании')");
        $stmt->bind_param("is", $car_id, $description);
        
        if ($stmt->execute()) {
            $order_id = $conn->insert_id;
            
        // Сохраняем выбранные услуги если есть
if (!empty($services_data)) {
    $services = json_decode($services_data, true);
    
    foreach ($services as $service) {
        // Простая версия без сложных проверок
        $service_id = (int)$service['id'];
        $service_name = $service['name'];
        $quantity = (int)$service['quantity'];
        $price = (float)$service['price'];
        
        $stmt = $conn->prepare("
            INSERT INTO order_services (order_id, service_id, service_name, quantity, price) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("iisid", 
            $order_id, 
            $service_id, 
            $service_name, 
            $quantity, 
            $price
        );
        
        $stmt->execute();
        $stmt->close();
    }
}
            
            $_SESSION['success'] = "Заказ #$order_id успешно создан!";
            header("Location: orders.php");
            exit;
        } else {
            $_SESSION['error'] = "Ошибка при создании заказа: " . $conn->error;
        }
    }
}

//include 'templates/header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Создание нового заказа</title>
    <link href="assets/css/orders.css" rel="stylesheet">
</head>
<body>
<?php include 'templates/header.php'; ?>
    <div class="orders-container">
        <div class="container-header">
            <h1 class="page-title">Создание нового заказа</h1>
            <a href="orders.php" class="btn-1c-outline">← Назад к заказам</a>
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

                    <form method="post" id="orderForm">
                        <!-- 1. КЛИЕНТ -->
                        <div class="form-group">
                            <label class="form-label">Клиент *</label>
                            <div class="search-input-group">
                                <button type="button" class="btn-1c-primary" onclick="openClientSelection()">
                                    🔍 Выбрать клиента
                                </button>
                                <button type="button" class="btn-1c-outline" onclick="openAddClientModal()">
                                    ➕ Новый клиент
                                </button>
                            </div>
                            
                            <!-- Выбранный клиент -->
                            <div id="selectedClientCard" class="selected-card" style="display: none;">
                                <div class="selected-card-content">
                                    <h5 id="selectedClientName"></h5>
                                    <div id="selectedClientDetails"></div>
                                </div>
                                <button type="button" class="btn-1c-outline btn-small" onclick="clearClientSelection()">
                                    ✕ Изменить
                                </button>
                                <input type="hidden" id="selectedClientId" name="client_id">
                            </div>
                        </div>

                        <!-- 2. АВТОМОБИЛЬ -->
                        <div class="form-group">
                            <label class="form-label">Автомобиль *</label>
                            <div class="search-input-group">
                                <button type="button" class="btn-1c-primary" onclick="openCarSelection()">
                                    🔍 Выбрать автомобиль
                                </button>
                                <button type="button" class="btn-1c-outline" onclick="openAddCarModal()">
                                    ➕ Новый автомобиль
                                </button>
                            </div>
                            
                            <!-- Выбранный автомобиль -->
                            <div id="selectedCarCard" class="selected-card" style="display: none;">
                                <div class="selected-card-content">
                                    <h5 id="selectedCarTitle"></h5>
                                    <div id="selectedCarDetails"></div>
                                </div>
                                <button type="button" class="btn-1c-outline btn-small" onclick="clearCarSelection()">
                                    ✕ Изменить
                                </button>
                                <input type="hidden" id="selectedCarId" name="car_id">
                            </div>
                        </div>

                        <!-- 3. ПРОБЛЕМА -->
                        <div class="form-group">
                            <label for="description" class="form-label">Описание проблемы *</label>
                            <textarea name="description" id="description" class="form-control textarea-large" 
                                      rows="6" required placeholder="Опишите проблему или необходимые работы..."></textarea>
                        </div>

                        <!-- 4. УСЛУГИ И РАБОТЫ -->
                        <div class="form-group">
                            <label class="form-label">Быстрый поиск услуг</label>
                            <div class="search-input-group">
                                <input type="text" id="serviceQuickSearch" class="form-control" 
                                       placeholder="Введите номер или название услуги (например: 15, масло, ТО)">
                                <button type="button" class="btn-1c-primary" onclick="searchServices()">
                                    🔍 Найти услуги
                                </button>
                            </div>
                            
                            <!-- Результаты поиска -->
                            <div id="servicesSearchResults" class="search-results" style="display: none;">
                                <div class="search-results-header">
                                    <h5>Результаты поиска услуг:</h5>
                                    <button type="button" class="btn-1c-outline btn-small" onclick="hideServicesResults()">
                                        ✕ Скрыть
                                    </button>
                                </div>
                                <div id="servicesResultsList" class="search-results-list">
                                    <!-- Результаты будут здесь -->
                                </div>
                            </div>
                            
                            <!-- Выбранные услуги -->
                            <div id="selectedServicesCard" class="selected-parts-card" style="display: none;">
                                <div class="selected-parts-header">
                                    <h5>Выбранные услуги:</h5>
                                </div>
                                <div id="selectedServicesList" class="selected-parts-list">
                                    <!-- Список выбранных услуг -->
                                </div>
                                <input type="hidden" id="selectedServicesData" name="services_data">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-1c-primary btn-large" id="createOrderBtn">
                                ✅ Создать заказ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно выбора клиента -->
    <div id="clientModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🔍 Выбор клиента</h3>
                <span class="close" onclick="closeClientModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="search-input-group">
                    <input type="text" id="clientSearch" class="form-control" placeholder="Поиск клиента...">
                    <button type="button" class="btn-1c-primary" onclick="searchClients()">Найти</button>
                </div>
                <div id="clientsList" class="modal-list">
                    <!-- Список клиентов будет здесь -->
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно добавления клиента -->
    <div id="addClientModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>➕ Новый клиент</h3>
                <span class="close" onclick="closeAddClientModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addClientForm">
                    <div class="form-group">
                        <label class="form-label">ФИО *</label>
                        <input type="text" id="newClientName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Телефон</label>
                        <input type="text" id="newClientPhone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" id="newClientEmail" class="form-control">
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn-1c-outline" onclick="closeAddClientModal()">Отмена</button>
                        <button type="submit" class="btn-1c-primary">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно выбора автомобиля -->
    <div id="carModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🚗 Выбор автомобиля</h3>
                <span class="close" onclick="closeCarModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="search-input-group">
                    <input type="text" id="carSearch" class="form-control" placeholder="Поиск по гос. номеру...">
                    <button type="button" class="btn-1c-primary" onclick="searchCars()">Найти</button>
                </div>
                <div id="carsList" class="modal-list">
                    <!-- Список автомобилей будет здесь -->
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно добавления автомобиля -->
    <div id="addCarModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>➕ Новый автомобиль</h3>
                <span class="close" onclick="closeAddCarModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addCarForm">
                    <div class="form-group">
                        <label class="form-label">Клиент *</label>
                        <select id="carClientSelect" class="form-control" required>
                            <option value="">Выберите клиента</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Марка *</label>
                        <input type="text" id="newCarMake" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Модель *</label>
                        <input type="text" id="newCarModel" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Гос. номер *</label>
                        <input type="text" id="newCarLicense" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Год</label>
                        <input type="number" id="newCarYear" class="form-control" min="1990" max="2030">
                    </div>
                    <div class="form-group">
                        <label class="form-label">VIN</label>
                        <input type="text" id="newCarVin" class="form-control">
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn-1c-outline" onclick="closeAddCarModal()">Отмена</button>
                        <button type="submit" class="btn-1c-primary">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
// Полный код create_order.js
let selectedClient = null;
let selectedCar = null;
let selectedServices = [];

// Проверка готовности формы
function checkFormCompletion() {
    const clientId = document.getElementById('selectedClientId').value;
    const carId = document.getElementById('selectedCarId').value;
    const description = document.getElementById('description').value.trim();
    const createOrderBtn = document.getElementById('createOrderBtn');
    
    const isFormComplete = !!(clientId && carId && description);
    createOrderBtn.disabled = !isFormComplete;
}

// РАБОТА С КЛИЕНТАМИ
function openClientSelection() {
    document.getElementById('clientModal').style.display = 'block';
    loadClients();
}

function closeClientModal() {
    document.getElementById('clientModal').style.display = 'none';
}

function openAddClientModal() {
    document.getElementById('addClientModal').style.display = 'block';
    document.getElementById('addClientForm').reset();
}

function closeAddClientModal() {
    document.getElementById('addClientModal').style.display = 'none';
}

function loadClients() {
    const clientsList = document.getElementById('clientsList');
    clientsList.innerHTML = '<div style="padding: 20px; text-align: center;">Загрузка...</div>';

    fetch('get_clients.php')
        .then(response => response.json())
        .then(clients => {
            clientsList.innerHTML = '';
            
            if (clients.length > 0) {
                clients.forEach(client => {
                    const clientElement = document.createElement('div');
                    clientElement.className = 'modal-item';
                    clientElement.onclick = () => selectClient(client);
                    
                    clientElement.innerHTML = `
                        <div class="modal-item-info">
                            <h5>${client.name}</h5>
                            <div class="modal-item-details">
                                ${client.phone ? `📞 ${client.phone}` : ''}
                                ${client.email ? ` | 📧 ${client.email}` : ''}
                            </div>
                        </div>
                        <button type="button" class="btn-1c-primary btn-small" onclick="event.stopPropagation(); selectClient(${JSON.stringify(client).replace(/"/g, '&quot;')})">
                            Выбрать
                        </button>
                    `;
                    clientsList.appendChild(clientElement);
                });
            } else {
                clientsList.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">Клиенты не найдены</div>';
            }
        })
        .catch(error => {
            console.error('Ошибка загрузки клиентов:', error);
            clientsList.innerHTML = '<div style="padding: 20px; text-align: center; color: red;">Ошибка загрузки</div>';
        });
}

function searchClients() {
    const searchTerm = document.getElementById('clientSearch').value.trim();
    const clientsList = document.getElementById('clientsList');
    
    clientsList.innerHTML = '<div style="padding: 20px; text-align: center;">Поиск...</div>';

    fetch('get_clients.php?search=' + encodeURIComponent(searchTerm))
        .then(response => response.json())
        .then(clients => {
            clientsList.innerHTML = '';
            
            if (clients.length > 0) {
                clients.forEach(client => {
                    const clientElement = document.createElement('div');
                    clientElement.className = 'modal-item';
                    clientElement.onclick = () => selectClient(client);
                    
                    clientElement.innerHTML = `
                        <div class="modal-item-info">
                            <h5>${client.name}</h5>
                            <div class="modal-item-details">
                                ${client.phone ? `📞 ${client.phone}` : ''}
                                ${client.email ? ` | 📧 ${client.email}` : ''}
                            </div>
                        </div>
                        <button type="button" class="btn-1c-primary btn-small" onclick="event.stopPropagation(); selectClient(${JSON.stringify(client).replace(/"/g, '&quot;')})">
                            Выбрать
                        </button>
                    `;
                    clientsList.appendChild(clientElement);
                });
            } else {
                clientsList.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">Клиенты не найдены</div>';
            }
        })
        .catch(error => {
            console.error('Ошибка поиска клиентов:', error);
            clientsList.innerHTML = '<div style="padding: 20px; text-align: center; color: red;">Ошибка поиска</div>';
        });
}

function selectClient(client) {
    selectedClient = client;
    
    document.getElementById('selectedClientName').textContent = client.name;
    document.getElementById('selectedClientDetails').innerHTML = `
        <div>📞 ${client.phone || 'Телефон не указан'}</div>
        <div>📧 ${client.email || 'Email не указан'}</div>
    `;
    document.getElementById('selectedClientId').value = client.id;
    document.getElementById('selectedClientCard').style.display = 'flex';
    
    closeClientModal();
    checkFormCompletion();
}

function clearClientSelection() {
    selectedClient = null;
    document.getElementById('selectedClientCard').style.display = 'none';
    document.getElementById('selectedClientId').value = '';
    checkFormCompletion();
}

// Добавление нового клиента
document.getElementById('addClientForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const name = document.getElementById('newClientName').value.trim();
    const phone = document.getElementById('newClientPhone').value.trim();
    const email = document.getElementById('newClientEmail').value.trim();
    
    if (!name) {
        alert('Введите ФИО клиента');
        return;
    }
    
    const formData = new FormData();
    formData.append('name', name);
    formData.append('phone', phone);
    formData.append('email', email);
    
    fetch('save_client.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            closeAddClientModal();
            // Автоматически выбираем нового клиента
            selectClient({
                id: result.client_id,
                name: name,
                phone: phone,
                email: email
            });
            alert('Клиент успешно добавлен!');
        } else {
            alert('Ошибка: ' + (result.error || 'Неизвестная ошибка'));
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        alert('Ошибка при сохранении клиента');
    });
});

// РАБОТА С АВТОМОБИЛЯМИ
function openCarSelection() {
    if (!selectedClient) {
        alert('Сначала выберите клиента');
        return;
    }
    document.getElementById('carModal').style.display = 'block';
    loadClientCars(selectedClient.id);
}

function closeCarModal() {
    document.getElementById('carModal').style.display = 'none';
}

function openAddCarModal() {
    if (!selectedClient) {
        alert('Сначала выберите клиента');
        return;
    }
    document.getElementById('addCarModal').style.display = 'block';
    document.getElementById('addCarForm').reset();
    document.getElementById('carClientSelect').value = selectedClient.id;
}

function closeAddCarModal() {
    document.getElementById('addCarModal').style.display = 'none';
}

function loadClientCars(clientId) {
    const carsList = document.getElementById('carsList');
    carsList.innerHTML = '<div style="padding: 20px; text-align: center;">Загрузка...</div>';

    fetch('get_client_cars.php?client_id=' + clientId)
        .then(response => response.json())
        .then(cars => {
            carsList.innerHTML = '';
            
            if (cars.length > 0) {
                cars.forEach(car => {
                    const carElement = document.createElement('div');
                    carElement.className = 'modal-item';
                    carElement.onclick = () => selectCar(car);
                    
                    carElement.innerHTML = `
                        <div class="modal-item-info">
                            <h5>${car.make} ${car.model}</h5>
                            <div class="modal-item-details">
                                🚗 ${car.license_plate}
                                ${car.year ? ` | 📅 ${car.year}` : ''}
                                ${car.vin ? ` | 🔢 ${car.vin}` : ''}
                            </div>
                        </div>
                        <button type="button" class="btn-1c-primary btn-small" onclick="event.stopPropagation(); selectCar(${JSON.stringify(car).replace(/"/g, '&quot;')})">
                            Выбрать
                        </button>
                    `;
                    carsList.appendChild(carElement);
                });
            } else {
                carsList.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">У клиента нет автомобилей</div>';
            }
        })
        .catch(error => {
            console.error('Ошибка загрузки авто:', error);
            carsList.innerHTML = '<div style="padding: 20px; text-align: center; color: red;">Ошибка загрузки</div>';
        });
}

function searchCars() {
    const searchTerm = document.getElementById('carSearch').value.trim();
    const carsList = document.getElementById('carsList');
    
    if (!searchTerm) {
        loadClientCars(selectedClient.id);
        return;
    }
    
    carsList.innerHTML = '<div style="padding: 20px; text-align: center;">Поиск...</div>';

    fetch('search_cars.php?license_plate=' + encodeURIComponent(searchTerm))
        .then(response => response.json())
        .then(cars => {
            carsList.innerHTML = '';
            
            if (cars.length > 0) {
                cars.forEach(car => {
                    const carElement = document.createElement('div');
                    carElement.className = 'modal-item';
                    carElement.onclick = () => selectCar(car);
                    
                    carElement.innerHTML = `
                        <div class="modal-item-info">
                            <h5>${car.make} ${car.model}</h5>
                            <div class="modal-item-details">
                                🚗 ${car.license_plate}
                                ${car.year ? ` | 📅 ${car.year}` : ''}
                                | 👥 ${car.client_name}
                            </div>
                        </div>
                        <button type="button" class="btn-1c-primary btn-small" onclick="event.stopPropagation(); selectCar(${JSON.stringify(car).replace(/"/g, '&quot;')})">
                            Выбрать
                        </button>
                    `;
                    carsList.appendChild(carElement);
                });
            } else {
                carsList.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">Автомобили не найдены</div>';
            }
        })
        .catch(error => {
            console.error('Ошибка поиска авто:', error);
            carsList.innerHTML = '<div style="padding: 20px; text-align: center; color: red;">Ошибка поиска</div>';
        });
}

function selectCar(car) {
    selectedCar = car;
    
    document.getElementById('selectedCarTitle').textContent = `${car.make} ${car.model}`;
    document.getElementById('selectedCarDetails').innerHTML = `
        <div>🚗 ${car.license_plate}</div>
        <div>📅 ${car.year || 'Год не указан'}</div>
        <div>🔢 VIN: ${car.vin || 'не указан'}</div>
    `;
    document.getElementById('selectedCarId').value = car.id;
    document.getElementById('selectedCarCard').style.display = 'flex';
    
    closeCarModal();
    checkFormCompletion();
}

function clearCarSelection() {
    selectedCar = null;
    document.getElementById('selectedCarCard').style.display = 'none';
    document.getElementById('selectedCarId').value = '';
    checkFormCompletion();
}

// Добавление нового автомобиля
document.getElementById('addCarForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const clientId = document.getElementById('carClientSelect').value;
    const make = document.getElementById('newCarMake').value.trim();
    const model = document.getElementById('newCarModel').value.trim();
    const license_plate = document.getElementById('newCarLicense').value.trim();
    const year = document.getElementById('newCarYear').value;
    const vin = document.getElementById('newCarVin').value.trim();
    
    if (!make || !model || !license_plate) {
        alert('Заполните обязательные поля');
        return;
    }
    
    const formData = new FormData();
    formData.append('client_id', clientId);
    formData.append('make', make);
    formData.append('model', model);
    formData.append('license_plate', license_plate);
    formData.append('year', year);
    formData.append('vin', vin);
    
    fetch('save_car.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            closeAddCarModal();
            // Автоматически выбираем новый автомобиль
            selectCar({
                id: result.car_id,
                make: make,
                model: model,
                license_plate: license_plate,
                year: year,
                vin: vin
            });
            alert('Автомобиль успешно добавлен!');
        } else {
            alert('Ошибка: ' + (result.error || 'Неизвестная ошибка'));
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        alert('Ошибка при сохранении автомобиля');
    });
});

// РАБОТА С УСЛУГАМИ
function searchServices() {
    const searchTerm = document.getElementById('serviceQuickSearch').value.trim();
    
    if (!searchTerm) {
        alert('Введите номер или название услуги для поиска');
        return;
    }
    
    const resultsContainer = document.getElementById('servicesSearchResults');
    const resultsList = document.getElementById('servicesResultsList');
    
    resultsList.innerHTML = '<div style="padding: 20px; text-align: center;">Поиск услуг...</div>';
    resultsContainer.style.display = 'block';
    
    fetch('search_services.php?q=' + encodeURIComponent(searchTerm))
        .then(response => response.json())
        .then(services => {
            resultsList.innerHTML = '';
            
            if (services.length > 0) {
                services.forEach(service => {
                    const serviceElement = document.createElement('div');
                    serviceElement.className = 'search-result-item';
                    serviceElement.innerHTML = `
                        <div class="result-item-info">
                            <div class="result-item-name">${service.name}</div>
                            <div class="result-item-details">
                                ${service.code ? `<span class="badge">Код: ${service.code}</span>` : ''}
                                ${service.price ? `<span class="price">${formatPrice(service.price)} руб.</span>` : ''}
                                ${service.category ? `<span class="category">${service.category}</span>` : ''}
                            </div>
                            ${service.description ? `<div class="result-item-desc">${service.description}</div>` : ''}
                        </div>
                        <div class="result-item-actions">
                            <button type="button" class="btn-1c-primary btn-small" 
                                    onclick="addServiceToOrder(${JSON.stringify(service).replace(/"/g, '&quot;')})">
                                ➕ Добавить
                            </button>
                        </div>
                    `;
                    resultsList.appendChild(serviceElement);
                });
            } else {
                resultsList.innerHTML = `
                    <div style="padding: 20px; text-align: center; color: #666;">
                        Услуги не найдены по запросу "${searchTerm}"
                        <br><small>Попробуйте другой номер или название</small>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Ошибка поиска услуг:', error);
            resultsList.innerHTML = '<div style="padding: 20px; text-align: center; color: red;">Ошибка поиска услуг</div>';
        });
}

// Добавление услуги в заказ
function addServiceToOrder(service) {
    // Проверяем, нет ли уже такой услуги
    const existingIndex = selectedServices.findIndex(s => s.id === service.id);
    
    if (existingIndex === -1) {
        service.quantity = 1;
        selectedServices.push(service);
        updateSelectedServicesList();
    } else {
        selectedServices[existingIndex].quantity += 1;
        updateSelectedServicesList();
    }
    
    // Очищаем поиск и скрываем результаты
    document.getElementById('serviceQuickSearch').value = '';
    hideServicesResults();
}

// Обновление списка выбранных услуг
function updateSelectedServicesList() {
    const container = document.getElementById('selectedServicesCard');
    const list = document.getElementById('selectedServicesList');
    const dataField = document.getElementById('selectedServicesData');
    
    if (selectedServices.length === 0) {
        container.style.display = 'none';
        dataField.value = '';
        return;
    }
    
    list.innerHTML = '';
    let totalAmount = 0;
    
    selectedServices.forEach((service, index) => {
        const serviceElement = document.createElement('div');
        serviceElement.className = 'selected-part-item';
        serviceElement.innerHTML = `
            <div class="part-info">
                <div class="part-name">${service.name}</div>
                <div class="part-details">
                    ${service.code ? `<span>Код: ${service.code}</span>` : ''}
                    ${service.category ? `<span>Категория: ${service.category}</span>` : ''}
                </div>
                <div class="part-price">
                    ${service.price ? `${formatPrice(service.price)} руб. × ${service.quantity} = ${formatPrice(service.price * service.quantity)} руб.` : 'Цена не указана'}
                </div>
            </div>
            <div class="part-actions">
                <div class="quantity-controls">
                    <button type="button" class="btn-quantity" onclick="changeServiceQuantity(${index}, -1)">−</button>
                    <span class="quantity">${service.quantity}</span>
                    <button type="button" class="btn-quantity" onclick="changeServiceQuantity(${index}, 1)">+</button>
                </div>
                <button type="button" class="btn-1c-outline btn-small" onclick="removeService(${index})">
                    🗑️ Удалить
                </button>
            </div>
        `;
        list.appendChild(serviceElement);
        
        if (service.price) {
            totalAmount += service.price * service.quantity;
        }
    });
    
    // Добавляем итого
    const totalElement = document.createElement('div');
    totalElement.className = 'parts-total';
    totalElement.innerHTML = `<strong>Общая стоимость услуг: ${formatPrice(totalAmount)} руб.</strong>`;
    list.appendChild(totalElement);
    
    // Сохраняем данные в скрытое поле
    dataField.value = JSON.stringify(selectedServices);
    container.style.display = 'block';
}

// Изменение количества услуги
function changeServiceQuantity(index, change) {
    const newQuantity = selectedServices[index].quantity + change;
    
    if (newQuantity < 1) {
        removeService(index);
        return;
    }
    
    selectedServices[index].quantity = newQuantity;
    updateSelectedServicesList();
}

// Удаление услуги
function removeService(index) {
    selectedServices.splice(index, 1);
    updateSelectedServicesList();
}

// Скрыть результаты поиска услуг
function hideServicesResults() {
    document.getElementById('servicesSearchResults').style.display = 'none';
}

// Форматирование цены
function formatPrice(price) {
    return new Intl.NumberFormat('ru-RU').format(price);
}

// Поиск услуг при нажатии Enter
document.getElementById('serviceQuickSearch').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchServices();
    }
});

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('description').addEventListener('input', checkFormCompletion);
    checkFormCompletion();
    
    // Обработка выбранного клиента из URL
    <?php if (isset($_GET['selected_client'])): ?>
        fetch('get_client_info.php?id=<?= (int)$_GET['selected_client'] ?>')
            .then(response => response.json())
            .then(client => {
                if (client.id) {
                    selectClient(client);
                }
            });
    <?php endif; ?>
});

// Закрытие модалок по клику вне окна
document.addEventListener('click', function(event) {
    const modals = ['clientModal', 'addClientModal', 'carModal', 'addCarModal'];
    
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal && event.target === modal) {
            modal.style.display = 'none';
        }
    });
});

// Закрытие модалок по ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modals = ['clientModal', 'addClientModal', 'carModal', 'addCarModal'];
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (modal && modal.style.display === 'block') {
                modal.style.display = 'none';
            }
        });
    }
});
</script>

    <style>
    .selected-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border: 2px solid #28a745;
        border-radius: 8px;
        background: #f8fff9;
        margin-top: 15px;
    }

    .selected-card-content h5 {
        margin: 0 0 10px 0;
        color: #2E7D32;
    }

    .modal {
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
        background-color: white;
        margin: 5% auto;
        padding: 0;
        border-radius: 8px;
        width: 600px;
        max-width: 90%;
        max-height: 80vh;
        overflow-y: auto;
    }

    .modal-header {
        padding: 15px 20px;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h3 {
        margin: 0;
    }

    .close {
        font-size: 24px;
        cursor: pointer;
    }

    .modal-body {
        padding: 20px;
    }

    .modal-list {
        max-height: 400px;
        overflow-y: auto;
        margin-top: 15px;
    }

    .modal-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        border: 1px solid #eee;
        border-radius: 6px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .modal-item:hover {
        background-color: #f8f9fa;
    }

    .modal-item-info {
        flex: 1;
    }

    .modal-item-info h5 {
        margin: 0 0 5px 0;
    }

    .modal-item-details {
        font-size: 12px;
        color: #666;
    }

    /* Стили для поиска услуг */
    .search-results {
        border: 1px solid #e6d8a8;
        border-radius: 6px;
        margin-top: 10px;
        background: #fffef5;
    }

    .search-results-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        border-bottom: 1px solid #e6d8a8;
        background: #fff8dc;
    }

    .search-results-list {
        max-height: 400px;
        overflow-y: auto;
    }

    .search-result-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 15px;
        border-bottom: 1px solid #f5f0d8;
    }

    .search-result-item:hover {
        background: #fcf5d9;
    }

    .result-item-info {
        flex: 1;
    }

    .result-item-name {
        font-weight: 600;
        color: #5c4a00;
        margin-bottom: 5px;
    }

    .result-item-details {
        display: flex;
        gap: 10px;
        font-size: 0.8rem;
        color: #8b6914;
        margin-bottom: 5px;
    }

    .result-item-desc {
        font-size: 0.8rem;
        color: #8b6914;
        font-style: italic;
    }

    .badge {
        background: #e6d8a8;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 0.7rem;
    }

    .price {
        color: #28a745;
        font-weight: 600;
    }

    .selected-parts-card {
        border: 2px solid #28a745;
        border-radius: 8px;
        background: #f8fff9;
        margin-top: 15px;
        padding: 0;
    }

    .selected-parts-header {
        padding: 15px;
        border-bottom: 1px solid #e6d8a8;
        background: #fff8dc;
    }

    .selected-parts-header h5 {
        margin: 0;
        color: #2E7D32;
    }

    .selected-parts-list {
        padding: 15px;
    }

    .selected-part-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px;
        border: 1px solid #e6d8a8;
        border-radius: 6px;
        margin-bottom: 10px;
        background: white;
    }

    .part-info {
        flex: 1;
    }

    .part-name {
        font-weight: 600;
        color: #5c4a00;
        margin-bottom: 5px;
    }

    .part-details {
        font-size: 0.8rem;
        color: #8b6914;
        margin-bottom: 5px;
    }

    .part-price {
        font-weight: 600;
        color: #28a745;
    }

    .part-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .quantity-controls {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .btn-quantity {
        width: 25px;
        height: 25px;
        border: 1px solid #d4c49e;
        background: white;
        cursor: pointer;
        border-radius: 3px;
    }

    .btn-quantity:hover {
        background: #f5e8b0;
    }

    .quantity {
        padding: 0 8px;
        font-weight: 600;
    }

    .parts-total {
        padding: 15px;
        border-top: 2px solid #e6d8a8;
        text-align: right;
        background: #fff8dc;
        margin-top: 10px;
    }
    </style>
<?php include 'templates/footer.php'; ?>
</body>
</html>
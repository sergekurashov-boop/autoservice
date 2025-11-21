<?php
// create_order.php - ИСПРАВЛЕННАЯ ВЕРСИЯ
session_start();
require 'includes/db.php';

<<<<<<< Updated upstream
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
=======
// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Инициализация переменных
$error = '';
$success = '';
$order_id = null;
$defect_id = null;

// ============================================================================
// ОБРАБОТКА БЫСТРОГО СОЗДАНИЯ КЛИЕНТА
// ============================================================================

if (isset($_POST['quick_create_client'])) {
    $name = trim($_POST['new_client_name'] ?? '');
    $phone = trim($_POST['new_client_phone'] ?? '');
    
    if (!empty($name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO clients (name, phone) VALUES (?, ?)");
            $stmt->execute([$name, $phone]);
            $_SESSION['new_client_id'] = $pdo->lastInsertId();
            $_SESSION['success'] = "✅ Клиент создан: " . $name;
        } catch (PDOException $e) {
            $_SESSION['error'] = "❌ Ошибка создания клиента: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "❌ Введите ФИО клиента";
    }
    header("Location: create_order.php");
    exit;
}

// ============================================================================
// ОБРАБОТКА БЫСТРОГО СОЗДАНИЯ АВТОМОБИЛЯ
// ============================================================================

if (isset($_POST['quick_create_car'])) {
    $model = trim($_POST['new_car_model'] ?? '');
    $vin = trim($_POST['new_car_vin'] ?? '');
    $plate = trim($_POST['new_car_plate'] ?? '');
    $year = $_POST['new_car_year'] ?? '';
    
    if (!empty($model)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO cars (make, model, vin, license_plate, year) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$model, $model, $vin, $plate, $year]);
            $_SESSION['new_car_id'] = $pdo->lastInsertId();
            $_SESSION['success'] = "✅ Автомобиль создан: " . $model;
        } catch (PDOException $e) {
            $_SESSION['error'] = "❌ Ошибка создания автомобиля: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "❌ Введите марку/модель автомобиля";
    }
    header("Location: create_order.php");
    exit;
}

// ============================================================================
// ОБРАБОТКА СОЗДАНИЯ ЗАКАЗА С ДЕФЕКТНЫМИ ВЕДОМОСТЯМИ - ИСПРАВЛЕННАЯ ВЕРСИЯ
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['quick_create_client']) && !isset($_POST['quick_create_car'])) {
    try {
        // Получаем данные из формы
        $client_id = $_POST['client_id'] ?? null;
        $car_id = $_POST['car_id'] ?? null;
        $order_type = $_POST['order_type'] ?? 'repair';
        $description = trim($_POST['description'] ?? '');
        $create_defect = $_POST['create_defect'] ?? 'yes'; // По умолчанию ДА
        $master_id = $_SESSION['user_id'] ?? 1;
        
        // Проверка обязательных полей
        if (!$client_id || !$car_id) {
            throw new Exception("❌ Выберите клиента и автомобиль");
        }
        
        // Проверка существования клиента и автомобиля
        $client_check = $pdo->prepare("SELECT id, name FROM clients WHERE id = ?");
        $client_check->execute([$client_id]);
        $client = $client_check->fetch();
        if (!$client) {
            throw new Exception("❌ Клиент не найден");
        }
>>>>>>> Stashed changes
        
        $car_check = $pdo->prepare("SELECT id, model FROM cars WHERE id = ?");
        $car_check->execute([$car_id]);
        $car = $car_check->fetch();
        if (!$car) {
            throw new Exception("❌ Автомобиль не найден");
        }
        
        // Генерация номера заказа
        $order_number = 'ORD-' . date('Ymd-His') . '-' . rand(100, 999);
        
        // СОЗДАНИЕ ЗАКАЗА
        $stmt = $pdo->prepare("INSERT INTO orders (order_number, client_id, car_id, description, status, order_type) VALUES (?, ?, ?, ?, 'В ожидании', ?)");
        $stmt->execute([$order_number, $client_id, $car_id, $description, $order_type]);
        $order_id = $pdo->lastInsertId();

        // 🔴🔴🔴 ДЕБАГ ДЕФЕКТНОЙ ВЕДОМОСТИ - УЛУЧШЕННЫЙ 🔴🔴🔴
        error_log("=== ДЕБАГ ДЕФЕКТНОЙ ВЕДОМОСТИ ===");
        error_log("create_defect: " . $create_defect);
        error_log("order_type: " . $order_type);
        error_log("order_id: " . $order_id);
        error_log("client_id: " . $client_id);
        error_log("car_id: " . $car_id);
        error_log("master_id: " . $master_id);

        // ПРОВЕРКА СУЩЕСТВОВАНИЯ ТАБЛИЦЫ DEFECTS
        try {
            $table_check = $pdo->query("SELECT 1 FROM defects LIMIT 1");
            error_log("✅ Таблица defects существует");
        } catch (PDOException $e) {
            error_log("❌ Таблица defects не существует: " . $e->getMessage());
            $create_defect = 'no'; // Отключаем создание если таблицы нет
        }

        // СОЗДАНИЕ ДЕФЕКТНОЙ ВЕДОМОСТИ ЕСЛИ НУЖНО - УПРОЩЕННАЯ ЛОГИКА
        if ($create_defect === 'yes') {
            error_log("✅ УСЛОВИЕ СОЗДАНИЯ ВЫПОЛНЕНО - СОЗДАЕМ ДЕФЕКТНУЮ ВЕДОМОСТЬ");
            
<<<<<<< Updated upstream
            // Сохраняем выбранные услуги если есть
            if (!empty($services_data)) {
                $services = json_decode($services_data, true);
                foreach ($services as $service) {
                    $stmt = $conn->prepare("
                        INSERT INTO order_services (order_id, service_id, service_name, quantity, price) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param("iisid", 
                        $order_id, 
                        $service['id'], 
                        $service['name'], 
                        $service['quantity'], 
                        $service['price'] ?? 0
                    );
                    $stmt->execute();
                }
            }
            
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
                            <button type="submit" class="btn-1c-primary btn-large" id="createOrderBtn" disabled>
                                ✅ Создать заказ
                            </button>
                        </div>
                    </form>
                </div>
=======
            $defect_number = 'DEF-' . date('Ymd') . '-' . $order_id;
            
            try {
                $defect_stmt = $pdo->prepare("INSERT INTO defects (order_id, client_id, car_id, defect_number, master_id, status, notes, created_at) VALUES (?, ?, ?, ?, ?, 'draft', ?, NOW())");
                $defect_stmt->execute([$order_id, $client_id, $car_id, $defect_number, $master_id, $description]);
                $defect_id = $pdo->lastInsertId();
                
                error_log("✅ ДЕФЕКТНАЯ ВЕДОМОСТЬ СОЗДАНА! ID: " . $defect_id);
                
                $_SESSION['last_defect_id'] = $defect_id;
                $_SESSION['success'] = "✅ Заказ успешно создан! Номер: " . $order_number . ". Дефектная ведомость создана!";
                
            } catch (PDOException $e) {
                error_log("❌ ОШИБКА СОЗДАНИЯ ДЕФЕКТНОЙ ВЕДОМОСТИ: " . $e->getMessage());
                $_SESSION['error'] = "❌ Ошибка создания дефектной ведомости: " . $e->getMessage();
                // Продолжаем без дефектной ведомости
                $_SESSION['success'] = "✅ Заказ успешно создан! Номер: " . $order_number . " (дефектная ведомость не создана)";
            }
        } else {
            error_log("❌ СОЗДАНИЕ ДЕФЕКТНОЙ ВЕДОМОСТИ ОТКЛЮЧЕНО");
            $_SESSION['success'] = "✅ Заказ успешно создан! Номер: " . $order_number;
        }
        
        // Редирект на созданную дефектную ведомость или заказ
        if (isset($defect_id) && $defect_id) {
            header("Location: defect_view.php?id=" . $defect_id);
            exit;
        } else {
            header("Location: order_view.php?id=" . $order_id);
            exit;
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    } catch (PDOException $e) {
        $error = "❌ Ошибка базы данных: " . $e->getMessage();
    }
}

// Автовыбор нового клиента если он только что создан
if (isset($_SESSION['new_client_id'])) {
    $client_id = $_SESSION['new_client_id'];
    unset($_SESSION['new_client_id']);
}

// Автовыбор нового автомобиля если он только что создан
if (isset($_SESSION['new_car_id'])) {
    $car_id = $_SESSION['new_car_id'];
    unset($_SESSION['new_car_id']);
}

// Получение данных для формы
try {
    $clients = $pdo->query("SELECT id, name, phone FROM clients ORDER BY name")->fetchAll();
    $cars = $pdo->query("SELECT id, model, vin, license_plate FROM cars ORDER BY model")->fetchAll();
} catch (PDOException $e) {
    $error = "❌ Ошибка загрузки данных: " . $e->getMessage();
}

define('ACCESS', true);
include 'templates/header.php';
?>
    <div class="content-container">
        <!-- Заголовок -->
        <div class="header-compact">
            <h1 class="page-title-compact">📋 СОЗДАНИЕ ЗАКАЗА</h1>
            <div class="header-actions-compact">
                <a href="orders.php" class="action-btn-compact">
                    <span class="action-icon">←</span>
                    <span class="action-label">Назад к заказам</span>
                </a>
>>>>>>> Stashed changes
            </div>
        </div>

        <!-- Сообщения -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="card-1c" style="background: #f8d7da; border-color: #f5c6cb; margin-bottom: 1rem;">
                <div style="padding: 1rem;">
                    <p style="margin: 0; color: #721c24;"><?= $_SESSION['error'] ?></p>
                </div>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="card-1c" style="background: #d4edda; border-color: #c3e6cb; margin-bottom: 1rem;">
                <div style="padding: 1rem;">
                    <p style="margin: 0; color: #155724;"><?= $_SESSION['success'] ?></p>
                </div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="card-1c" style="background: #f8d7da; border-color: #f5c6cb; margin-bottom: 1rem;">
                <div style="padding: 1rem;">
                    <p style="margin: 0; color: #721c24;"><?= $error ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Форма создания заказа -->
<div class="card-1c">
    <div class="card-header-1c">
        <h5>📝 ИНФОРМАЦИЯ О ЗАКАЗЕ</h5>
    </div>
<<<<<<< Updated upstream

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
    
    createOrderBtn.disabled = !(clientId && carId && description);
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
        background-color:
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
=======
    <div style="padding: 2rem;">
        <form method="POST" id="createOrderForm">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Autocomplete для клиента -->
                <div class="form-group">
                    <label for="client_search"><strong>👤 Клиент:</strong></label>
                    <input type="text" id="client_search" name="client_search" 
                           placeholder="Начните вводить ФИО или телефон..."
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e6d8a8; background: #fffef5;"
                           value="<?= isset($_POST['client_search']) ? htmlspecialchars($_POST['client_search']) : '' ?>">
                    <input type="hidden" name="client_id" id="client_id" value="<?= $_POST['client_id'] ?? '' ?>">
                    
                    <!-- Результаты поиска -->
                    <div id="client_results" style="display: none; border: 1px solid #e6d8a8; background: white; max-height: 200px; overflow-y: auto; position: absolute; z-index: 1000; width: 100%;"></div>
                    
                    <!-- Сообщение "не найден" -->
                    <div id="client_not_found" style="display: none; margin-top: 0.5rem;">
                        <p style="color: #8b6914; margin-bottom: 0.5rem;">Клиент не найден</p>
                        <button type="button" class="action-btn-compact small" onclick="showClientModal()">
                            <span class="action-icon">➕</span>
                            <span class="action-label">Создать нового клиента</span>
                        </button>
                    </div>
                </div>

                <!-- Autocomplete для автомобиля -->
                <div class="form-group">
                    <label for="car_search"><strong>🚗 Автомобиль:</strong></label>
                    <input type="text" id="car_search" name="car_search" 
                           placeholder="Начните вводить марку, модель или VIN..."
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e6d8a8; background: #fffef5;"
                           value="<?= isset($_POST['car_search']) ? htmlspecialchars($_POST['car_search']) : '' ?>">
                    <input type="hidden" name="car_id" id="car_id" value="<?= $_POST['car_id'] ?? '' ?>">
                    
                    <div id="car_results" style="display: none; border: 1px solid #e6d8a8; background: white; max-height: 200px; overflow-y: auto; position: absolute; z-index: 1000; width: 100%;"></div>
                    
                    <div id="car_not_found" style="display: none; margin-top: 0.5rem;">
                        <p style="color: #8b6914; margin-bottom: 0.5rem;">Автомобиль не найден</p>
                        <button type="button" class="action-btn-compact small" onclick="showModal('carModal')">
                            <span class="action-icon">➕</span>
                            <span class="action-label">Создать новый автомобиль</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Блоки быстрого создания -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 1rem;">
                <div class="card-1c compact-card">
                    <div class="card-header-1c compact-header">
                        <h5>👤 БЫСТРОЕ СОЗДАНИЕ КЛИЕНТА</h5>
                    </div>
                    <div class="compact-content">
                        <p style="color: #8b6914; font-size: 0.9rem; margin-bottom: 1rem;">Клиент не найден?</p>
                        <button type="button" class="action-btn-compact small" onclick="showModal('clientModal')">
                            <span class="action-icon">➕</span>
                            <span class="action-label">Новый клиент</span>
                        </button>
                    </div>
                </div>

                <div class="card-1c compact-card">
                    <div class="card-header-1c compact-header">
                        <h5>🚗 БЫСТРОЕ СОЗДАНИЕ АВТОМОБИЛЯ</h5>
                    </div>
                    <div class="compact-content">
                        <p style="color: #8b6914; font-size: 0.9rem; margin-bottom: 1rem;">Автомобиль не найден?</p>
                        <button type="button" class="action-btn-compact small" onclick="showModal('carModal')">
                            <span class="action-icon">➕</span>
                            <span class="action-label">Новое авто</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Тип заказа -->
            <div class="form-group" style="margin-top: 1.5rem;">
                <label><strong>📝 Тип заказа:</strong></label>
                <div class="btn-group-1c">
                    <button type="button" class="btn-1c active" data-order-type="repair">🔧 Ремонт</button>
                    <button type="button" class="btn-1c" data-order-type="maintenance">🛠️ ТО</button>
                    <button type="button" class="btn-1c" data-order-type="diagnostics">🔍 Диагностика</button>
                    <button type="button" class="btn-1c" data-order-type="tire">🚗 Шиномонтаж</button>
                    <button type="button" class="btn-1c" data-order-type="other">📄 Прочее</button>
                </div>
                <input type="hidden" name="order_type" id="orderType" value="repair" required>
            </div>

            <!-- Описание заказа -->
            <div class="form-group" style="margin-top: 1.5rem;">
                <label for="description"><strong>📋 Описание проблемы/работ:</strong></label>
                <textarea name="description" id="description" rows="4" 
                          placeholder="Опишите проблему или необходимые работы..."
                          style="width: 100%; padding: 0.75rem; border: 1px solid #e6d8a8; background: #fffef5; resize: vertical;"
                          maxlength="1000"><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                <small style="color: #8b6914;">Максимум 1000 символов</small>
            </div>

          <!-- Блок дефектной ведомости - УПРОЩЕННЫЙ ВАРИАНТ -->
<div class="card-1c" style="margin-top: 1.5rem;">
    <div class="card-header-1c">
        <h5>📋 ДЕФЕКТНАЯ ВЕДОМОСТЬ</h5>
    </div>
    <div style="padding: 1.5rem; text-align: center;">
        <p style="color: #5c4a00; margin-bottom: 1.5rem;">
            Создать дефектную ведомость после сохранения заказа
        </p>
        <a href="defect_create.php" class="action-btn-compact" style="font-size: 1rem; padding: 0.75rem 1.5rem;">
            <span class="action-icon">📋</span>
            <span class="action-label">Создать дефектную ведомость</span>
        </a>
        <p style="color: #8b6914; font-size: 0.9rem; margin-top: 1rem;">
            Можно создать позже из списка заказов
        </p>
    </div>
</div>
            <!-- Кнопки отправки -->
            <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e6d8a8;">
                <button type="submit" class="action-btn-compact primary" style="font-size: 1.1rem; padding: 1rem 2rem;">
                    <span class="action-icon">✅</span>
                    <span class="action-label">Создать заказ</span>
                </button>
                <a href="orders.php" class="action-btn-compact" style="font-size: 1.1rem; padding: 1rem 2rem;">
                    <span class="action-icon">❌</span>
                    <span class="action-label">Отмена</span>
                </a>
            </div>
        </form>
    </div>
</div>
<!-- Модальное окно создания клиента -->
<div id="clientModal" class="modal-1c" style="display: none;">
    <div class="modal-content-1c">
        <div class="modal-header-1c">
            <h3>👤 Создание нового клиента</h3>
            <span class="close-1c" onclick="closeModal('clientModal')">&times;</span>
        </div>
        <div class="modal-body-1c">
            <form method="POST">
                <input type="hidden" name="quick_create_client" value="1">
                
                <div class="form-group">
                    <label><strong>ФИО клиента:</strong></label>
                    <input type="text" name="new_client_name" required style="width: 100%; padding: 0.75rem; border: 1px solid #e6d8a8;">
                </div>
                
                <div class="form-group">
                    <label><strong>Телефон:</strong></label>
                    <input type="tel" name="new_client_phone" style="width: 100%; padding: 0.75rem; border: 1px solid #e6d8a8;">
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="submit" class="action-btn-compact primary">
                        <span class="action-icon">✅</span>
                        <span class="action-label">Создать</span>
                    </button>
                    <button type="button" class="action-btn-compact" onclick="closeModal('clientModal')">
                        <span class="action-icon">❌</span>
                        <span class="action-label">Отмена</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно создания автомобиля -->
<div id="carModal" class="modal-1c" style="display: none;">
    <div class="modal-content-1c">
        <div class="modal-header-1c">
            <h3>🚗 Создание нового автомобиля</h3>
            <span class="close-1c" onclick="closeModal('carModal')">&times;</span>
        </div>
        <div class="modal-body-1c">
            <form method="POST">
                <input type="hidden" name="quick_create_car" value="1">
                
                <div class="form-group">
                    <label><strong>Марка/Модель:</strong></label>
                    <input type="text" name="new_car_model" required style="width: 100%; padding: 0.75rem; border: 1px solid #e6d8a8;">
                </div>
                
                <div class="form-group">
                    <label><strong>VIN номер:</strong></label>
                    <input type="text" name="new_car_vin" style="width: 100%; padding: 0.75rem; border: 1px solid #e6d8a8;">
                </div>
                
                <div class="form-group">
                    <label><strong>Гос. номер:</strong></label>
                    <input type="text" name="new_car_plate" style="width: 100%; padding: 0.75rem; border: 1px solid #e6d8a8;">
                </div>
                
                <div class="form-group">
                    <label><strong>Год выпуска:</strong></label>
                    <input type="number" name="new_car_year" min="1990" max="2030" style="width: 100%; padding: 0.75rem; border: 1px solid #e6d8a8;">
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="submit" class="action-btn-compact primary">
                        <span class="action-icon">✅</span>
                        <span class="action-label">Создать</span>
                    </button>
                    <button type="button" class="action-btn-compact" onclick="closeModal('carModal')">
                        <span class="action-icon">❌</span>
                        <span class="action-label">Отмена</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<style>
.client-result:hover {
    background: #f5e8b0 !important;
}

#client_results {
    border-radius: 0 0 4px 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.form-select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e6d8a8;
    background: #fffef5;
    font-size: 14px;
}

.modal-1c {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: none;
    align-items: center;
    justify-content: center;
}

.modal-content-1c {
    background: #fffef5;
    border: 1px solid #e6d8a8;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

.modal-header-1c {
    background: #fff8dc;
    border-bottom: 1px solid #e6d8a8;
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header-1c h3 {
    margin: 0;
    color: #5c4a00;
}

.close-1c {
    color: #8b6914;
    font-size: 1.5rem;
    font-weight: bold;
    cursor: pointer;
    line-height: 1;
}

.close-1c:hover {
    color: #5c4a00;
}

.modal-body-1c {
    padding: 1.5rem;
}

.btn-group-1c {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn-1c {
    padding: 0.75rem 1.5rem;
    background: #fffef5;
    border: 1px solid #e6d8a8;
    color: #5c4a00;
    border-radius: 0;
    font-weight: 500;
    transition: all 0.2s ease;
    cursor: pointer;
    text-align: center;
}

.btn-1c:hover {
    background: #f5e8b0;
    transform: translateY(-1px);
    color: #5c4a00;
}

.btn-1c.active {
    background: #8b6914;
    color: white;
    border-color: #7a5a10;
}

.btn-1c.primary {
    background: #8b6914;
    color: white;
    border-color: #7a5a10;
}

.btn-1c.primary:hover {
    background: #7a5a10;
    color: white;
}
</style>
<script>
// Оптимизированный и улучшенный скрипт для create_order.php
class OrderFormManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupClientSearch();
        this.setupCarSearch();
        this.setupOrderTypeHandlers();
        this.setupDefectHandlers();
        this.setupFormValidation();
        this.setupModalHandlers();
        this.setupClickOutsideHandlers();
    }

    // Autocomplete для клиентов
    setupClientSearch() {
        this.clientSearchTimeout = null;
        const clientSearch = document.getElementById('client_search');
        
        if (!clientSearch) return;

        clientSearch.addEventListener('input', this.debounce((e) => {
            const query = e.target.value.trim();
            this.clientIdField.value = '';
            
            if (query.length < 2) {
                this.hideElement('client_results');
                this.hideElement('client_not_found');
                return;
            }
            
            this.searchClients(query);
        }, 300));
    }

    // Autocomplete для автомобилей
    setupCarSearch() {
        this.carSearchTimeout = null;
        const carSearch = document.getElementById('car_search');
        
        if (!carSearch) return;

        carSearch.addEventListener('input', this.debounce((e) => {
            const query = e.target.value.trim();
            this.carIdField.value = '';
            
            if (query.length < 2) {
                this.hideElement('car_results');
                this.hideElement('car_not_found');
                return;
            }
            
            this.searchCars(query);
        }, 300));
    }

    // Поиск клиентов
    async searchClients(query) {
        try {
            const response = await fetch(`ajax_search_clients.php?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            this.displayClientResults(data);
        } catch (error) {
            console.error('Client search error:', error);
            this.hideElement('client_results');
        }
    }

    // Поиск автомобилей
    async searchCars(query) {
        try {
            const response = await fetch(`ajax_search_cars.php?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            this.displayCarResults(data);
        } catch (error) {
            console.error('Car search error:', error);
            this.hideElement('car_results');
        }
    }

    // Отображение результатов поиска клиентов
    displayClientResults(clients) {
        const results = document.getElementById('client_results');
        const notFound = document.getElementById('client_not_found');
        
        if (clients.length > 0) {
            results.innerHTML = clients.map(client => 
                `<div class="search-result" data-id="${client.id}" 
                      onclick="orderManager.selectClient(${client.id}, '${this.escapeHtml(client.name)} (${client.phone})')">
                    <strong>${client.name}</strong><br>
                    <small style="color: #8b6914;">${client.phone}</small>
                 </div>`
            ).join('');
            this.showElement('client_results');
            this.hideElement('client_not_found');
        } else {
            this.hideElement('client_results');
            this.showElement('client_not_found');
        }
    }

    // Отображение результатов поиска автомобилей
    displayCarResults(cars) {
        const results = document.getElementById('car_results');
        const notFound = document.getElementById('car_not_found');
        
        if (cars.length > 0) {
            results.innerHTML = cars.map(car => 
                `<div class="search-result" data-id="${car.id}" 
                      onclick="orderManager.selectCar(${car.id}, '${this.escapeHtml(car.model)} (${car.vin})')">
                    <strong>${car.model}</strong><br>
                    <small style="color: #8b6914;">VIN: ${car.vin} | ${car.license_plate || 'нет номера'}</small>
                 </div>`
            ).join('');
            this.showElement('car_results');
            this.hideElement('car_not_found');
        } else {
            this.hideElement('car_results');
            this.showElement('car_not_found');
        }
    }

    // Выбор клиента
    selectClient(clientId, clientText) {
        this.clientIdField.value = clientId;
        this.clientSearchField.value = clientText;
        this.hideElement('client_results');
        this.hideElement('client_not_found');
        
        // Автоматически показываем блок дефектной ведомости при выборе клиента
        this.showDefectBlockIfNeeded();
    }

    // Выбор автомобиля
    selectCar(carId, carText) {
        this.carIdField.value = carId;
        this.carSearchField.value = carText;
        this.hideElement('car_results');
        this.hideElement('car_not_found');
    }

    // Обработчики типа заказа
    setupOrderTypeHandlers() {
        document.querySelectorAll('.btn-group-1c .btn-1c').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.handleOrderTypeChange(e.target);
            });
        });
    }

    handleOrderTypeChange(button) {
        // Сбрасываем активные кнопки
        document.querySelectorAll('.btn-group-1c .btn-1c').forEach(b => {
            b.classList.remove('active');
        });
        
        // Активируем текущую кнопку
        button.classList.add('active');
        
        const orderType = button.getAttribute('data-order-type');
        this.orderTypeField.value = orderType;
        
        // Управляем блоком дефектной ведомости
        if (orderType === 'repair' || orderType === 'diagnostics') {
            this.showDefectBlock();
            this.enableDefect(); // Автоматически включаем для ремонта/диагностики
        } else {
            this.hideDefectBlock();
            this.disableDefect();
        }
    }

    // Управление дефектной ведомостью
    setupDefectHandlers() {
        this.defectEnabled = true; // По умолчанию включено
        this.updateDefectUI();
    }

    enableDefect() {
        this.defectEnabled = true;
        this.createDefectField.value = 'yes';
        this.updateDefectUI();
        
        // Обновляем текст информации
        const infoText = document.querySelector('#defectBlock p');
        if (infoText) {
            infoText.innerHTML = '✅ <strong>Дефектная ведомость будет создана автоматически</strong><br><small style="color: #8b6914;">После создания заказа вы попадете в дефектную ведомость</small>';
        }
    }

    disableDefect() {
        this.defectEnabled = false;
        this.createDefectField.value = 'no';
        this.updateDefectUI();
        
        // Обновляем текст информации
        const infoText = document.querySelector('#defectBlock p');
        if (infoText) {
            infoText.innerHTML = '❌ <strong>Дефектная ведомость не будет создана</strong><br><small style="color: #8b6914;">Вы можете создать её позже из заказа</small>';
        }
    }

    updateDefectUI() {
        const createBtn = document.querySelector('#defectBlock .btn-1c.primary');
        const skipBtn = document.querySelector('#defectBlock .btn-1c:not(.primary)');
        
        if (this.defectEnabled) {
            createBtn?.classList.add('active');
            createBtn.innerHTML = '✅ Дефектная будет создана';
            skipBtn?.classList.remove('active');
            skipBtn.innerHTML = '❌ Пропустить';
        } else {
            createBtn?.classList.remove('active');
            createBtn.innerHTML = '✅ Создать дефектную ведомость';
            skipBtn?.classList.add('active');
            skipBtn.innerHTML = '❌ Дефектная не будет создана';
        }
    }

    showDefectBlock() {
        this.showElement('defectBlock');
    }

    hideDefectBlock() {
        this.hideElement('defectBlock');
    }

    showDefectBlockIfNeeded() {
        const orderType = this.orderTypeField.value;
        if ((orderType === 'repair' || orderType === 'diagnostics') && this.clientIdField.value) {
            this.showDefectBlock();
        }
    }

    // Валидация формы
    setupFormValidation() {
        const form = document.getElementById('createOrderForm');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            if (!this.validateForm()) {
                e.preventDefault();
                return false;
            }
            
            this.showLoadingState();
        });
    }

    validateForm() {
        const clientId = this.clientIdField.value;
        const carId = this.carIdField.value;
        const description = this.descriptionField.value.trim();
        const orderType = this.orderTypeField.value;

        // Проверка клиента
        if (!clientId && !clientId.startsWith('virtual_')) {
            this.showError('❌ Пожалуйста, выберите клиента', 'client_search');
            return false;
        }

        // Проверка автомобиля
        if (!carId) {
            this.showError('❌ Пожалуйста, выберите автомобиль', 'car_search');
            return false;
        }

        // Проверка описания
        if (description.length === 0) {
            this.showError('❌ Пожалуйста, опишите проблему или необходимые работы', 'description');
            return false;
        }

        if (description.length > 1000) {
            this.showError('❌ Описание не должно превышать 1000 символов', 'description');
            return false;
        }

        // Предупреждение для ремонта/диагностики без дефектной ведомости
        if ((orderType === 'repair' || orderType === 'diagnostics') && !this.defectEnabled) {
            const confirmSkip = confirm('⚠️ Для этого типа заказа рекомендуется создать дефектную ведомость.\n\nПродолжить без создания дефектной ведомости?');
            if (!confirmSkip) {
                this.showDefectBlock();
                this.enableDefect();
                return false;
            }
        }

        return true;
    }

    // Модальные окна
    setupModalHandlers() {
        // Закрытие по ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });
    }

    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            
            // Передаем значение поиска в модальное окно клиента
            if (modalId === 'clientModal') {
                const searchValue = this.clientSearchField.value.trim();
                if (searchValue && !this.clientIdField.value) {
                    const nameInput = modal.querySelector('input[name="new_client_name"]');
                    if (nameInput) nameInput.value = searchValue;
                }
            }
        }
    }

    closeModal(modalId) {
        this.hideElement(modalId);
    }

    closeAllModals() {
        document.querySelectorAll('.modal-1c').forEach(modal => {
            modal.style.display = 'none';
        });
    }

    // Обработчики кликов вне элементов
    setupClickOutsideHandlers() {
        document.addEventListener('click', (e) => {
            // Закрытие результатов поиска
            if (!e.target.closest('#client_search') && !e.target.closest('#client_results')) {
                this.hideElement('client_results');
            }
            if (!e.target.closest('#car_search') && !e.target.closest('#car_results')) {
                this.hideElement('car_results');
            }
            
            // Закрытие модальных окон
            if (e.target.classList.contains('modal-1c')) {
                e.target.style.display = 'none';
            }
        });
    }

    // Вспомогательные методы
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showElement(id) {
        const element = document.getElementById(id);
        if (element) element.style.display = 'block';
    }

    hideElement(id) {
        const element = document.getElementById(id);
        if (element) element.style.display = 'none';
    }

    showError(message, focusElementId) {
        alert(message);
        const element = document.getElementById(focusElementId);
        if (element) element.focus();
    }

    showLoadingState() {
        const submitBtn = document.querySelector('#createOrderForm button[type="submit"]');
        if (submitBtn) {
            submitBtn.innerHTML = '<span class="action-icon">⏳</span><span class="action-label">Создание...</span>';
            submitBtn.disabled = true;
        }
    }

    // Геттеры для часто используемых элементов
    get clientSearchField() { return document.getElementById('client_search'); }
    get clientIdField() { return document.getElementById('client_id'); }
    get carSearchField() { return document.getElementById('car_search'); }
    get carIdField() { return document.getElementById('car_id'); }
    get orderTypeField() { return document.getElementById('orderType'); }
    get createDefectField() { return document.getElementById('createDefect'); }
    get descriptionField() { return document.getElementById('description'); }
}

// Создаем виртуального клиента
function createVirtualClient() {
    const virtualName = "Виртуальный Клиент " + Math.floor(Math.random() * 1000);
    const virtualPhone = "+7" + Math.floor(9000000000 + Math.random() * 1000000000);
    
    orderManager.clientSearchField.value = virtualName;
    orderManager.clientIdField.value = 'virtual_' + Date.now();
    
    alert('✅ Создан виртуальный клиент: ' + virtualName);
    orderManager.showDefectBlockIfNeeded();
}

// Глобальные функции для обратной совместимости
function selectClient(clientId, clientText) { orderManager.selectClient(clientId, clientText); }
function selectCar(carId, carText) { orderManager.selectCar(carId, carText); }
function showModal(modalId) { orderManager.showModal(modalId); }
function closeModal(modalId) { orderManager.closeModal(modalId); }
function enableDefect() { orderManager.enableDefect(); }
function disableDefect() { orderManager.disableDefect(); }
function showClientModal() { orderManager.showModal('clientModal'); }

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    window.orderManager = new OrderFormManager();
});
</script>
>>>>>>> Stashed changes

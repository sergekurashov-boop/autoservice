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
    $order_type = $_POST['order_type'] ?? 'standard';
    $description = trim($_POST['description'] ?? '');
    $services_data = $_POST['services_data'] ?? '';
    $inspection_data = $_POST['inspection_data'] ?? '';

    // Валидация
    if (empty($client_id) || empty($car_id)) {
        $_SESSION['error'] = "Пожалуйста, выберите клиента и автомобиль";
    } elseif ($order_type === 'standard' && empty($description)) {
        $_SESSION['error'] = "Пожалуйста, заполните описание проблемы";
    } elseif ($order_type === 'inspection' && empty($inspection_data)) {
        $_SESSION['error'] = "Пожалуйста, добавьте пункты осмотра";
    } else {
        // Для осмотра автоматически генерируем описание
        if ($order_type === 'inspection') {
            $description = "Осмотр ТС по акту";
        }
        
        // ИСПРАВЛЕНО: убрана колонка order_type
        $stmt = $conn->prepare("INSERT INTO orders (car_id, description, status) VALUES (?, ?, 'В ожидании')");
        $stmt->bind_param("is", $car_id, $description);
        
        if ($stmt->execute()) {
            $order_id = $conn->insert_id;
            
            if ($order_type === 'inspection' && !empty($inspection_data)) {
                // Сохраняем данные осмотра
                saveInspectionData($order_id, $inspection_data);
                // Автоматически создаем услуги из осмотра
                createServicesFromInspection($order_id, $inspection_data);
            } else {
                // Стандартное сохранение услуг
                if (!empty($services_data)) {
                    saveStandardServices($order_id, $services_data);
                }
            }
            
            $_SESSION['success'] = "Заказ №$order_id успешно создан!";
            header("Location: " . ($order_type === 'inspection' ? "order_inspection.php?order_id=$order_id" : "orders.php"));
            exit;
        } else {
            $_SESSION['error'] = "Ошибка при создании заказа: " . $conn->error;
        }
    }
}

// Функция сохранения данных осмотра
function saveInspectionData($order_id, $inspection_data) {
    global $conn;
    $data = json_decode($inspection_data, true);
    
    if (is_array($data)) {
        foreach ($data as $item) {
            $stmt = $conn->prepare("
                INSERT INTO order_inspection_data (order_id, item_name, side, action, work_price, part_price, total_price, item_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isssddds", 
                $order_id, 
                $item['name'],
                $item['side'] ?? 'none',
                $item['action'] ?? 'replace',
                $item['work_price'] ?? 0,
                $item['part_price'] ?? 0,
                $item['total_price'] ?? 0,
                $item['type'] ?? 'custom'
            );
            $stmt->execute();
        }
    }
}
// Функция создания услуг из осмотра
function createServicesFromInspection($order_id, $inspection_data) {
    global $conn;
    $data = json_decode($inspection_data, true);
    $total_amount = 0;
    
    if (is_array($data)) {
        foreach ($data as $item) {
            if (($item['total_price'] ?? 0) > 0) {
                // Подготавливаем значения заранее
                $service_name = $item['name'] ?? '';
                if (($item['side'] ?? 'none') !== 'none') {
                    $service_name .= " (" . getSideLabel($item['side']) . ")";
                }
                if (($item['action'] ?? 'replace') !== 'replace') {
                    $service_name .= " - " . getActionLabel($item['action']);
                }
                $price = (float)($item['total_price'] ?? 0);
                
                $stmt = $conn->prepare("
                    INSERT INTO order_services (order_id, service_id, service_name, quantity, price) 
                    VALUES (?, 0, ?, 1, ?)
                ");
                
                if ($stmt) {
                    $stmt->bind_param("isd", $order_id, $service_name, $price);
                    $stmt->execute();
                    $total_amount += $price;
                }
            }
        }
        
        // Обновляем общую сумму заказа
        if ($total_amount > 0) {
            $stmt = $conn->prepare("UPDATE orders SET total = ? WHERE id = ?");
            $stmt->bind_param("di", $total_amount, $order_id);
            $stmt->execute();
        }
    }
}
// Функция сохранения стандартных услуг
function saveStandardServices($order_id, $services_data) {
    global $conn;
    $services = json_decode($services_data, true);
    $total_amount = 0;
    
    if (is_array($services)) {
        foreach ($services as $service) {
            // Подготавливаем значения заранее
            $service_id = (int)($service['id'] ?? 0);
            $service_name = $service['name'] ?? '';
            $quantity = (int)($service['quantity'] ?? 1);
            $price = (float)($service['price'] ?? 0);
            
            $stmt = $conn->prepare("
                INSERT INTO order_services (order_id, service_id, service_name, quantity, price) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            if ($stmt) {
                $stmt->bind_param("iisid", 
                    $order_id, 
                    $service_id, 
                    $service_name, 
                    $quantity, 
                    $price
                );
                $stmt->execute();
                $total_amount += $price * $quantity;
            }
        }
        
        // Обновляем общую сумму заказа
        if ($total_amount > 0) {
            $stmt = $conn->prepare("UPDATE orders SET total = ? WHERE id = ?");
            $stmt->bind_param("di", $total_amount, $order_id);
            $stmt->execute();
        }
    }
}
// Вспомогательные функции
function getSideLabel($side) {
    $labels = ['left' => 'Левая', 'right' => 'Правая', 'both' => 'Обе', 'none' => ''];
    return $labels[$side] ?? $side;
}

function getActionLabel($action) {
    $labels = ['repair' => 'Ремонт', 'replace' => 'Замена', 'diagnostic' => 'Диагностика'];
    return $labels[$action] ?? $action;
}

// Получаем шаблонные пункты для осмотра
$categories = [];
$result = $conn->query("
    SELECT ic.name as category_name, ic.id as category_id,
           ii.id, ii.name, ii.default_side, ii.default_action,
           ii.typical_work_price, ii.typical_part_price
    FROM inspection_categories ic 
    JOIN inspection_items ii ON ic.id = ii.category_id 
    ORDER BY ic.sort_order, ii.sort_order
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (!isset($categories[$row['category_name']])) {
            $categories[$row['category_name']] = [];
        }
        $categories[$row['category_name']][] = $row;
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
    <style>
        /* Стили для модальных окон */
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: none;
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
            background: #2c3e50;
            color: white;
            border-radius: 8px 8px 0 0;
        }

        .modal-header h3 {
            margin: 0;
            color: white;
        }

        .close {
            font-size: 24px;
            cursor: pointer;
            color: white;
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
            padding: 12px 15px;
            border: 1px solid #eee;
            border-radius: 6px;
            margin-bottom: 8px;
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
            margin: 0 0 4px 0;
        }

        .modal-item-details {
            font-size: 0.8rem;
            color: #666;
        }

        .loading, .no-results, .error {
            padding: 20px;
            text-align: center;
            color: #666;
        }

        .error {
            color: #dc3545;
        }

        /* Стили для осмотра */
        .inspection-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 20px;
            margin-top: 15px;
        }
        
        .search-box {
            margin-bottom: 15px;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .template-section {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-height: 400px;
            overflow-y: auto;
        }
        
        .inspection-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .category-section {
            margin-bottom: 20px;
        }
        
        .category-title {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #3498db;
        }
        
        .template-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 10px;
            margin-bottom: 5px;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .template-item:hover {
            background: #f8f9fa;
            border-color: #3498db;
        }
        
        .inspection-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .inspection-table th,
        .inspection-table td {
            padding: 10px;
            border: 1px solid #dee2e6;
            text-align: left;
        }
        
        .inspection-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .hidden {
            display: none;
        }
    </style>
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

                        <!-- 3. ТИП ЗАКАЗА -->
                        <div class="form-group">
                            <label class="form-label">Тип заказа *</label>
                            <select id="orderType" name="order_type" class="form-control" onchange="toggleOrderType()" required>
                                <option value="standard">📝 Стандартный заказ (с описанием проблемы)</option>
                                <option value="inspection">🔍 Осмотр ТС + Акт</option>
                            </select>
                        </div>

                        <!-- 4. СТАНДАРТНАЯ ПРОБЛЕМА -->
                        <div id="problemSection" class="form-group">
                            <label for="description" class="form-label">Описание проблемы *</label>
                            <textarea name="description" id="description" class="form-control textarea-large" 
                                      rows="6" required placeholder="Опишите проблему или необходимые работы..."></textarea>
                        </div>

                        <!-- 5. УСЛУГИ И РАБОТЫ (для стандартного заказа) -->
                        <div id="servicesSection" class="form-group">
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

                        <!-- 6. ФОРМА ОСМОТРА (скрыта по умолчанию) -->
                        <div id="inspectionSection" class="form-group" style="display: none;">
                            <div class="enhanced-card">
                                <div class="enhanced-card-header">
                                    <span class="card-header-icon">🔍</span> Осмотр транспортного средства
                                </div>
                                <div class="card-body">
                                    <div class="inspection-container">
                                        <!-- Левая колонка - шаблонные пункты -->
                                        <div class="template-section">
                                            <h4>📋 Шаблонные пункты</h4>
                                            
                                            <div class="search-box">
                                                <input type="text" id="itemSearch" placeholder="🔍 Поиск детали..." onkeyup="filterItems()">
                                            </div>
                                            
                                            <?php foreach ($categories as $category_name => $items): ?>
                                            <div class="category-section">
                                                <div class="category-title"><?= htmlspecialchars($category_name) ?></div>
                                                <?php foreach ($items as $item): ?>
                                                <div class="template-item" data-name="<?= strtolower(htmlspecialchars($item['name'])) ?>" 
                                                     onclick="addTemplateItem(<?= $item['id'] ?>, '<?= addslashes(htmlspecialchars($item['name'])) ?>', 
                                                     '<?= $item['default_side'] ?>', '<?= $item['default_action'] ?>', 
                                                     <?= $item['typical_work_price'] ?? 0 ?>, <?= $item['typical_part_price'] ?? 0 ?>)">
                                                    <span><?= htmlspecialchars($item['name']) ?></span>
                                                    <button type="button" class="btn-1c-primary btn-small" style="padding: 4px 8px; font-size: 12px;">+</button>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <!-- Правая колонка - ведомость осмотра -->
                                        <div class="inspection-section">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                                <h4>📝 Ведомость осмотра</h4>
                                                <button type="button" onclick="addCustomItem()" class="btn-1c-primary">
                                                    ➕ Произвольная позиция
                                                </button>
                                            </div>
                                            
                                            <table class="inspection-table">
                                                <thead>
                                                    <tr>
                                                        <th width="40%">Деталь/Работа</th>
                                                        <th width="80px">Сторона</th>
                                                        <th width="100px">Действие</th>
                                                        <th width="100px">Работа, руб</th>
                                                        <th width="100px">Запчасть, руб</th>
                                                        <th width="100px">Итого</th>
                                                        <th width="60px"></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="inspectionItems">
                                                    <tr id="noItems">
                                                        <td colspan="7" style="text-align: center; color: #6c757d; padding: 20px;">
                                                            Нет добавленных пунктов. Выберите пункты из списка слева или добавьте произвольную позицию.
                                                        </td>
                                                    </tr>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="5" style="text-align: right; font-weight: bold;">Общая сумма:</td>
                                                        <td id="totalSum" style="font-weight: bold;">0.00 руб</td>
                                                        <td></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                    <input type="hidden" id="inspectionData" name="inspection_data">
                                </div>
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
    // ===== ПЕРЕМЕННЫЕ =====
    let selectedClient = null;
    let selectedCar = null;
    let selectedServices = [];
    let inspectionItems = [];
    let itemCounter = 0;

    // ===== ФУНКЦИИ ПЕРЕКЛЮЧЕНИЯ ТИПА ЗАКАЗА =====
    function toggleOrderType() {
        const orderType = document.getElementById('orderType').value;
        const problemSection = document.getElementById('problemSection');
        const servicesSection = document.getElementById('servicesSection');
        const inspectionSection = document.getElementById('inspectionSection');
        
        if (orderType === 'inspection') {
            problemSection.style.display = 'none';
            servicesSection.style.display = 'none';
            inspectionSection.style.display = 'block';
            document.getElementById('description').value = 'Осмотр ТС по акту';
        } else {
            problemSection.style.display = 'block';
            servicesSection.style.display = 'block';
            inspectionSection.style.display = 'none';
            document.getElementById('description').value = '';
        }
    }

    // ===== ФУНКЦИИ ОСМОТРА =====
    function filterItems() {
        const search = document.getElementById('itemSearch').value.toLowerCase();
        document.querySelectorAll('.template-item').forEach(item => {
            const itemName = item.getAttribute('data-name');
            if (itemName.includes(search)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    function addTemplateItem(itemId, name, side, action, workPrice, partPrice) {
        const item = {
            id: 'tpl_' + itemCounter++,
            type: 'template',
            itemId: itemId,
            name: name,
            side: side,
            action: action,
            work_price: workPrice || 0,
            part_price: partPrice || 0,
            total_price: (workPrice || 0) + (partPrice || 0)
        };
        
        inspectionItems.push(item);
        renderInspectionTable();
        updateInspectionData();
    }
    
    function addCustomItem() {
        const name = prompt('Введите название детали/работы:');
        if (!name) return;
        
        const side = prompt('Сторона (left/right/both/none):', 'none');
        const action = prompt('Действие (repair/replace/diagnostic):', 'replace');
        const workPrice = parseFloat(prompt('Стоимость работы:', '0')) || 0;
        const partPrice = parseFloat(prompt('Стоимость запчасти:', '0')) || 0;
        
        const item = {
            id: 'cust_' + itemCounter++,
            type: 'custom',
            name: name,
            side: side || 'none',
            action: action || 'replace',
            work_price: workPrice,
            part_price: partPrice,
            total_price: workPrice + partPrice
        };
        
        inspectionItems.push(item);
        renderInspectionTable();
        updateInspectionData();
    }
    
    function removeItem(itemId) {
        inspectionItems = inspectionItems.filter(item => item.id !== itemId);
        renderInspectionTable();
        updateInspectionData();
    }
    
    function renderInspectionTable() {
        const tbody = document.getElementById('inspectionItems');
        const totalElement = document.getElementById('totalSum');
        const noItemsRow = document.getElementById('noItems');
        
        let total = 0;
        let html = '';
        
        inspectionItems.forEach(item => {
            total += item.total_price;
            html += `
                <tr>
                    <td>${escapeHtml(item.name)}</td>
                    <td>${getSideLabel(item.side)}</td>
                    <td>${getActionLabel(item.action)}</td>
                    <td>${item.work_price.toFixed(2)}</td>
                    <td>${item.part_price.toFixed(2)}</td>
                    <td>${item.total_price.toFixed(2)}</td>
                    <td>
                        <button type="button" onclick="removeItem('${item.id}')" class="btn-1c-outline btn-small">🗑️</button>
                    </td>
                </tr>
            `;
        });
        
        tbody.innerHTML = html;
        totalElement.textContent = total.toFixed(2) + ' руб';
        
        if (inspectionItems.length === 0) {
            noItemsRow.style.display = '';
        } else {
            noItemsRow.style.display = 'none';
        }
    }
    
    function updateInspectionData() {
        document.getElementById('inspectionData').value = JSON.stringify(inspectionItems);
    }
    
    function getSideLabel(side) {
        const labels = {'left': 'Левая', 'right': 'Правая', 'both': 'Обе', 'none': '-'};
        return labels[side] || side;
    }
    
    function getActionLabel(action) {
        const labels = {'repair': 'Ремонт', 'replace': 'Замена', 'diagnostic': 'Диагностика'};
        return labels[action] || action;
    }

    // ===== ФУНКЦИИ ДЛЯ КЛИЕНТОВ =====
    function openClientSelection() {
        document.getElementById('clientModal').style.display = 'block';
        loadClients();
    }

    function closeClientModal() {
        document.getElementById('clientModal').style.display = 'none';
    }

    function openAddClientModal() {
        document.getElementById('addClientModal').style.display = 'block';
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
                displayClients(clients);
            })
            .catch(error => {
                console.error('Ошибка загрузки клиентов:', error);
                clientsList.innerHTML = '<div style="padding: 20px; text-align: center; color: red;">Ошибка загрузки</div>';
            });
    }

    function displayClients(clients) {
        const clientsList = document.getElementById('clientsList');
        clientsList.innerHTML = '';
        
        if (clients && clients.length > 0) {
            clients.forEach(client => {
                const clientElement = document.createElement('div');
                clientElement.className = 'modal-item';
                clientElement.onclick = () => selectClient(client);
                
                clientElement.innerHTML = `
                    <div class="modal-item-info">
                        <h5>${escapeHtml(client.name)}</h5>
                        <div class="modal-item-details">
                            ${client.phone ? `📞 ${escapeHtml(client.phone)}` : ''}
                            ${client.email ? ` | 📧 ${escapeHtml(client.email)}` : ''}
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
    }

    function searchClients() {
        const searchTerm = document.getElementById('clientSearch').value.trim();
        const clientsList = document.getElementById('clientsList');
        
        clientsList.innerHTML = '<div style="padding: 20px; text-align: center;">Поиск...</div>';

        fetch('get_clients.php?search=' + encodeURIComponent(searchTerm))
            .then(response => response.json())
            .then(clients => {
                displayClients(clients);
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
    }

    function clearClientSelection() {
        selectedClient = null;
        document.getElementById('selectedClientCard').style.display = 'none';
        document.getElementById('selectedClientId').value = '';
    }

    // ===== ФУНКЦИИ ДЛЯ АВТОМОБИЛЕЙ =====
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
                displayCars(cars);
            })
            .catch(error => {
                console.error('Ошибка загрузки авто:', error);
                carsList.innerHTML = '<div style="padding: 20px; text-align: center; color: red;">Ошибка загрузки</div>';
            });
    }

    function displayCars(cars) {
        const carsList = document.getElementById('carsList');
        carsList.innerHTML = '';
        
        if (cars && cars.length > 0) {
            cars.forEach(car => {
                const carElement = document.createElement('div');
                carElement.className = 'modal-item';
                carElement.onclick = () => selectCar(car);
                
                carElement.innerHTML = `
                    <div class="modal-item-info">
                        <h5>${escapeHtml(car.make)} ${escapeHtml(car.model)}</h5>
                        <div class="modal-item-details">
                            🚗 ${escapeHtml(car.license_plate)}
                            ${car.year ? ` | 📅 ${car.year}` : ''}
                            ${car.vin ? ` | 🔢 ${escapeHtml(car.vin)}` : ''}
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
                displayCars(cars);
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
    }

    function clearCarSelection() {
        selectedCar = null;
        document.getElementById('selectedCarCard').style.display = 'none';
        document.getElementById('selectedCarId').value = '';
    }

    // ===== ФУНКЦИИ ДЛЯ СТАНДАРТНЫХ УСЛУГ =====
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
                displayServicesResults(services);
            })
            .catch(error => {
                console.error('Ошибка поиска услуг:', error);
                resultsList.innerHTML = '<div style="padding: 20px; text-align: center; color: red;">Ошибка поиска услуг</div>';
            });
    }

    function displayServicesResults(services) {
        const resultsList = document.getElementById('servicesResultsList');
        resultsList.innerHTML = '';
        
        if (services && services.length > 0) {
            services.forEach(service => {
                const serviceElement = document.createElement('div');
                serviceElement.className = 'search-result-item';
                serviceElement.innerHTML = `
                    <div class="result-item-info">
                        <div class="result-item-name">${escapeHtml(service.name)}</div>
                        <div class="result-item-details">
                            ${service.code ? `<span class="badge">Код: ${escapeHtml(service.code)}</span>` : ''}
                            ${service.price ? `<span class="price">${formatPrice(service.price)} руб.</span>` : ''}
                            ${service.category ? `<span class="category">${escapeHtml(service.category)}</span>` : ''}
                        </div>
                        ${service.description ? `<div class="result-item-desc">${escapeHtml(service.description)}</div>` : ''}
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
    }

    function addServiceToOrder(service) {
        const existingIndex = selectedServices.findIndex(s => s.id === service.id);
        
        if (existingIndex === -1) {
            service.quantity = 1;
            selectedServices.push(service);
            updateSelectedServicesList();
        } else {
            selectedServices[existingIndex].quantity += 1;
            updateSelectedServicesList();
        }
        
        document.getElementById('serviceQuickSearch').value = '';
        hideServicesResults();
    }

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
                    <div class="part-name">${escapeHtml(service.name)}</div>
                    <div class="part-details">
                        ${service.code ? `<span>Код: ${escapeHtml(service.code)}</span>` : ''}
                        ${service.category ? `<span>Категория: ${escapeHtml(service.category)}</span>` : ''}
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
        
        const totalElement = document.createElement('div');
        totalElement.className = 'parts-total';
        totalElement.innerHTML = `<strong>Общая стоимость услуг: ${formatPrice(totalAmount)} руб.</strong>`;
        list.appendChild(totalElement);
        
        dataField.value = JSON.stringify(selectedServices);
        container.style.display = 'block';
    }

    function changeServiceQuantity(index, change) {
        const newQuantity = selectedServices[index].quantity + change;
        
        if (newQuantity < 1) {
            removeService(index);
            return;
        }
        
        selectedServices[index].quantity = newQuantity;
        updateSelectedServicesList();
    }

    function removeService(index) {
        selectedServices.splice(index, 1);
        updateSelectedServicesList();
    }

    function hideServicesResults() {
        document.getElementById('servicesSearchResults').style.display = 'none';
    }

    function formatPrice(price) {
        return new Intl.NumberFormat('ru-RU').format(price);
    }

    // ===== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ =====
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;', 
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // ===== ОБРАБОТЧИКИ СОБЫТИЙ =====
    document.getElementById('serviceQuickSearch').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchServices();
        }
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

    // ===== ИНИЦИАЛИЗАЦИЯ =====
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('noItems').style.display = '';
        
        <?php if (isset($_GET['selected_client'])): ?>
            fetch('get_client_info.php?id=<?= (int)$_GET['selected_client'] ?>')
                .then(response => response.json())
                .then(client => {
                    if (client && client.id) {
                        selectClient(client);
                    }
                });
        <?php endif; ?>
    });
    </script>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
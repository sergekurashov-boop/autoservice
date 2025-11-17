<?php
require 'includes/db.php';
//require 'includes/auth.php';

$page_title = "Создание заказ-наряда";
include 'templates/header.php';

// Генерация номера
$order_number = generateOrderNumber($conn);

// Получение данных
//$companies = $conn->query("SELECT * FROM companies");
$clients = $conn->query("SELECT * FROM clients");
?>

<div class="container mt-4">
    <h2>Создание заказ-наряда</h2>
    <form id="workOrderForm" method="POST" action="save_work_order.php">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                Основная информация
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Номер заказ-наряда</label>
                        <input type="text" class="form-control" 
                               value="<?= $order_number ?>" readonly>
                        <input type="hidden" name="order_number" value="<?= $order_number ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Филиал</label>
                        <select name="company_id" class="form-select" required>
                            <?php while($company = $companies->fetch_assoc()): ?>
                            <option value="<?= $company['id'] ?>">
                                <?= htmlspecialchars($company['name']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Клиент</label>
                        <select name="client_id" class="form-select" id="clientSelect" required>
                            <option value="">Выберите клиента</option>
                            <?php while($client = $clients->fetch_assoc()): ?>
                            <option value="<?= $client['id'] ?>">
                                <?= htmlspecialchars($client['name']) ?> 
                                (<?= $client['phone'] ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Автомобиль</label>
                        <select name="car_id" class="form-select" id="carSelect" required disabled>
                            <option value="">Сначала выберите клиента</option>
                        </select>
                    </div>
                    
                    <div class="col-12 mb-3">
                        <label class="form-label">Причина обращения</label>
                        <textarea name="issue_description" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <span>Услуги</span>
                    <button type="button" class="btn btn-sm btn-light" id="addServiceBtn">
                        <i class="bi bi-plus-circle"></i> Добавить услугу
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="servicesTable">
                        <thead>
                            <tr>
                                <th width="50%">Услуга</th>
                                <th width="15%">Цена</th>
                                <th width="15%">Количество</th>
                                <th width="15%">Сумма</th>
                                <th width="5%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Строки услуг будут добавляться динамически -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Итого:</th>
                                <th id="totalAmount">0.00</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-secondary" onclick="window.print()">
                <i class="bi bi-printer"></i> Предварительный просмотр
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Сохранить заказ-наряд
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Загрузка автомобилей клиента
    document.getElementById('clientSelect').addEventListener('change', function() {
        const clientId = this.value;
        const carSelect = document.getElementById('carSelect');
        
        if (!clientId) {
            carSelect.disabled = true;
            carSelect.innerHTML = '<option value="">Сначала выберите клиента</option>';
            return;
        }
        
        fetch('get_client_cars.php?client_id=' + clientId)
            .then(response => response.json())
            .then(data => {
                carSelect.innerHTML = '';
                
                if (data.length === 0) {
                    carSelect.innerHTML = '<option value="">У клиента нет автомобилей</option>';
                } else {
                    data.forEach(car => {
                        const option = document.createElement('option');
                        option.value = car.id;
                        option.textContent = `${car.make} ${car.model} (${car.license_plate})`;
                        carSelect.appendChild(option);
                    });
                }
                
                carSelect.disabled = false;
            });
    });
    
    // Добавление услуги
    let serviceCounter = 0;
    document.getElementById('addServiceBtn').addEventListener('click', function() {
        serviceCounter++;
        const newRow = `
        <tr id="serviceRow${serviceCounter}">
            <td>
                <input type="text" name="services[${serviceCounter}][name]" 
                       class="form-control service-name" required>
            </td>
            <td>
                <input type="number" name="services[${serviceCounter}][price]" 
                       class="form-control service-price" min="0" step="0.01" 
                       value="0" required>
            </td>
            <td>
                <input type="number" name="services[${serviceCounter}][quantity]" 
                       class="form-control service-quantity" min="1" value="1" required>
            </td>
            <td>
                <span class="service-total">0.00</span>
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm" 
                        onclick="document.getElementById('serviceRow${serviceCounter}').remove(); updateTotal();">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>`;
        
        document.querySelector('#servicesTable tbody').insertAdjacentHTML('beforeend', newRow);
        
        // Назначение обработчиков для новой строки
        const newRowElem = document.getElementById(`serviceRow${serviceCounter}`);
        newRowElem.querySelector('.service-price').addEventListener('input', updateServiceTotal);
        newRowElem.querySelector('.service-quantity').addEventListener('input', updateServiceTotal);
    });
    
    // Обновление суммы для одной услуги
    function updateServiceTotal() {
        const row = this.closest('tr');
        const price = parseFloat(row.querySelector('.service-price').value) || 0;
        const quantity = parseInt(row.querySelector('.service-quantity').value) || 0;
        const total = price * quantity;
        
        row.querySelector('.service-total').textContent = total.toFixed(2);
        updateTotal();
    }
    
    // Обновление общей суммы
    function updateTotal() {
        let total = 0;
        document.querySelectorAll('.service-total').forEach(el => {
            total += parseFloat(el.textContent) || 0;
        });
        
        document.getElementById('totalAmount').textContent = total.toFixed(2);
    }
    
    // Инициализация первой услуги
    document.getElementById('addServiceBtn').click();
});
</script>

<?php include 'templates/footer.php'; ?>
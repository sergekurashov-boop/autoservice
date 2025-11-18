// Простой рабочий JavaScript для create_order.php
console.log('✅ create_order_simple.js загружен!');

let selectedClient = null;
let selectedCar = null;

// БАЗОВЫЕ ФУНКЦИИ
function openClientSelection() {
    console.log('🔍 Открытие выбора клиента');
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
    clientsList.innerHTML = '<div class="loading">Загрузка клиентов...</div>';

    fetch('get_clients.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(clients => {
            displayClients(clients);
        })
        .catch(error => {
            console.error('Ошибка загрузки клиентов:', error);
            clientsList.innerHTML = '<div class="error">Ошибка загрузки клиентов</div>';
        });
}

function displayClients(clients) {
    const clientsList = document.getElementById('clientsList');
    
    if (!clients || clients.length === 0) {
        clientsList.innerHTML = '<div class="no-results">Клиенты не найдены</div>';
        return;
    }

    clientsList.innerHTML = clients.map(client => `
        <div class="client-item" onclick="selectClient(${client.id}, '${escapeHtml(client.name)}', '${escapeHtml(client.phone || '')}', '${escapeHtml(client.email || '')}')">
            <div class="client-info">
                <div class="client-name">${escapeHtml(client.name)}</div>
                <div class="client-details">
                    ${client.phone ? `📞 ${escapeHtml(client.phone)}` : ''}
                    ${client.email ? ` | 📧 ${escapeHtml(client.email)}` : ''}
                </div>
            </div>
            <button type="button" class="btn-1c-primary btn-small" 
                    onclick="event.stopPropagation(); selectClient(${client.id}, '${escapeHtml(client.name)}', '${escapeHtml(client.phone || '')}', '${escapeHtml(client.email || '')}')">
                Выбрать
            </button>
        </div>
    `).join('');
}

// Функция для экранирования HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

function selectClient(clientId, clientName, clientPhone, clientEmail) {
    selectedClient = {
        id: clientId,
        name: clientName,
        phone: clientPhone,
        email: clientEmail
    };
    
    document.getElementById('selectedClientName').textContent = clientName;
    document.getElementById('selectedClientDetails').innerHTML = `
        <div>📞 ${clientPhone || 'Телефон не указан'}</div>
        <div>📧 ${clientEmail || 'Email не указан'}</div>
    `;
    document.getElementById('selectedClientId').value = clientId;
    document.getElementById('selectedClientCard').style.display = 'flex';
    
    closeClientModal();
    checkFormCompletion();
    
    console.log('✅ Клиент выбран:', clientName);
}

function clearClientSelection() {
    selectedClient = null;
    document.getElementById('selectedClientCard').style.display = 'none';
    document.getElementById('selectedClientId').value = '';
    checkFormCompletion();
}

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
    // Заполняем select клиентами перед открытием
    loadClientsForCarSelect();
    document.getElementById('addCarModal').style.display = 'block';
}

function closeAddCarModal() {
    document.getElementById('addCarModal').style.display = 'none';
}

function loadClientsForCarSelect() {
    const clientSelect = document.getElementById('carClientSelect');
    clientSelect.innerHTML = '<option value="">Выберите клиента</option>';
    
    fetch('get_clients.php')
        .then(response => response.json())
        .then(clients => {
            clients.forEach(client => {
                const option = document.createElement('option');
                option.value = client.id;
                option.textContent = client.name;
                option.selected = (client.id == selectedClient?.id);
                clientSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Ошибка загрузки клиентов:', error);
        });
}

function loadClientCars(clientId) {
    const carsList = document.getElementById('carsList');
    carsList.innerHTML = '<div class="loading">Загрузка автомобилей...</div>';

    fetch('get_client_cars.php?client_id=' + clientId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(cars => {
            displayCars(cars);
        })
        .catch(error => {
            console.error('Ошибка загрузки авто:', error);
            carsList.innerHTML = '<div class="error">Ошибка загрузки автомобилей</div>';
        });
}

function displayCars(cars) {
    const carsList = document.getElementById('carsList');
    
    if (!cars || cars.length === 0) {
        carsList.innerHTML = '<div class="no-results">У клиента нет автомобилей</div>';
        return;
    }

    carsList.innerHTML = cars.map(car => `
        <div class="modal-item" onclick="selectCar(${car.id}, '${escapeHtml(car.make)}', '${escapeHtml(car.model)}', '${escapeHtml(car.license_plate)}', '${car.year || ''}', '${escapeHtml(car.vin || '')}')">
            <div class="modal-item-info">
                <h5>${escapeHtml(car.make)} ${escapeHtml(car.model)}</h5>
                <div class="modal-item-details">
                    🚗 ${escapeHtml(car.license_plate)}
                    ${car.year ? ` | 📅 ${car.year}` : ''}
                    ${car.vin ? ` | 🔢 ${escapeHtml(car.vin)}` : ''}
                </div>
            </div>
            <button type="button" class="btn-1c-primary btn-small" 
                    onclick="event.stopPropagation(); selectCar(${car.id}, '${escapeHtml(car.make)}', '${escapeHtml(car.model)}', '${escapeHtml(car.license_plate)}', '${car.year || ''}', '${escapeHtml(car.vin || '')}')">
                Выбрать
            </button>
        </div>
    `).join('');
}

function selectCar(carId, carMake, carModel, carLicense, carYear, carVin) {
    selectedCar = {
        id: carId,
        make: carMake,
        model: carModel,
        license_plate: carLicense,
        year: carYear,
        vin: carVin
    };
    
    document.getElementById('selectedCarTitle').textContent = `${carMake} ${carModel}`;
    document.getElementById('selectedCarDetails').innerHTML = `
        <div>🚗 ${carLicense}</div>
        <div>📅 ${carYear || 'Год не указан'}</div>
        <div>🔢 VIN: ${carVin || 'не указан'}</div>
    `;
    document.getElementById('selectedCarId').value = carId;
    document.getElementById('selectedCarCard').style.display = 'flex';
    
    closeCarModal();
    checkFormCompletion();
    
    console.log('✅ Автомобиль выбран:', carMake, carModel);
}

function clearCarSelection() {
    selectedCar = null;
    document.getElementById('selectedCarCard').style.display = 'none';
    document.getElementById('selectedCarId').value = '';
    checkFormCompletion();
}

function getCarInfoByLicensePlate() {
    const licensePlate = document.getElementById('licensePlateInput').value.trim();
    if (!licensePlate) {
        alert('Введите госномер');
        return;
    }
    
    // Здесь должна быть логика поиска авто по госномеру
    // Временно открываем модалку добавления
    openAddCarModal();
}

// ПРОВЕРКА ФОРМЫ - ИСПРАВЛЕННАЯ ВЕРСИЯ
function checkFormCompletion() {
    const clientId = document.getElementById('selectedClientId').value;
    const carId = document.getElementById('selectedCarId').value;
    const description = document.getElementById('description').value.trim();
    const createOrderBtn = document.getElementById('createOrderBtn');
    
    const isFormComplete = !!(clientId && carId && description);
    createOrderBtn.disabled = !isFormComplete;
    
    console.log('Проверка формы:', {
        clientId: clientId,
        carId: carId,
        description: description,
        isComplete: isFormComplete
    });
}

// ДЕБАГ
function debugForm() {
    console.log('=== ДЕБАГ ФОРМЫ ===');
    console.log('clientId:', document.getElementById('selectedClientId').value);
    console.log('carId:', document.getElementById('selectedCarId').value);
    console.log('description:', document.getElementById('description').value);
    console.log('Кнопка активна:', !document.getElementById('createOrderBtn').disabled);
    
    // Принудительно активируем кнопку для тестирования
    document.getElementById('createOrderBtn').disabled = false;
    
    alert('Проверьте консоль (F12). Кнопка принудительно активирована.');
}

// Обработчик для добавления нового клиента
document.getElementById('addClientForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('name', document.getElementById('newClientName').value);
    formData.append('phone', document.getElementById('newClientPhone').value);
    formData.append('email', document.getElementById('newClientEmail').value);
    
    fetch('add_client.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            selectClient(result.client_id, document.getElementById('newClientName').value, 
                        document.getElementById('newClientPhone').value, 
                        document.getElementById('newClientEmail').value);
            closeAddClientModal();
            document.getElementById('addClientForm').reset();
        } else {
            alert('Ошибка: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        alert('Ошибка при добавлении клиента');
    });
});

// Обработчик для добавления нового автомобиля
document.getElementById('addCarForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('client_id', document.getElementById('carClientSelect').value);
    formData.append('make', document.getElementById('newCarMake').value);
    formData.append('model', document.getElementById('newCarModel').value);
    formData.append('license_plate', document.getElementById('newCarLicense').value);
    formData.append('year', document.getElementById('newCarYear').value);
    formData.append('vin', document.getElementById('newCarVin').value);
    
    fetch('add_car.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            selectCar(result.car_id, document.getElementById('newCarMake').value, 
                     document.getElementById('newCarModel').value, 
                     document.getElementById('newCarLicense').value,
                     document.getElementById('newCarYear').value,
                     document.getElementById('newCarVin').value);
            closeAddCarModal();
            document.getElementById('addCarForm').reset();
        } else {
            alert('Ошибка: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        alert('Ошибка при добавлении автомобиля');
    });
});

// ИНИЦИАЛИЗАЦИЯ
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM загружен');
    
    // Слушатели для проверки формы
    document.getElementById('description').addEventListener('input', checkFormCompletion);
    
    // Принудительная проверка каждые 2 секунды (на время отладки)
    const debugInterval = setInterval(checkFormCompletion, 2000);
    
    // Обработчик отправки формы с дополнительной проверкой
    document.getElementById('orderForm').addEventListener('submit', function(e) {
        console.log('🎯 Отправка формы...');
        
        const clientId = document.getElementById('selectedClientId').value;
        const carId = document.getElementById('selectedCarId').value;
        const description = document.getElementById('description').value.trim();
        
        if (!clientId || !carId || !description) {
            e.preventDefault();
            alert('Заполните все обязательные поля: клиент, автомобиль и описание проблемы');
            return false;
        }
        
        console.log('✅ Форма отправляется с данными:', { clientId, carId, description });
        return true;
    });
    
   
	// Функции для поиска услуг
// СТАРЫЙ РАБОЧИЙ КОД поиска услуг
let selectedServices = [];

function searchServices() {
    const query = document.getElementById('serviceQuickSearch').value.trim();
    if (!query) return;

    // Показываем лоадер
    document.getElementById('servicesResultsList').innerHTML = '<div class="loading">Поиск...</div>';
    document.getElementById('servicesSearchResults').style.display = 'block';

    // Делаем запрос к API
    fetch(`api/services_search.php?query=${encodeURIComponent(query)}`)
        .then(r => r.json())
        .then(services => {
            let html = '';
            services.forEach(service => {
                html += `
                <div class="service-item">
                    <span class="service-code">${service.code}</span>
                    <span class="service-name">${service.name}</span>
                    <span class="service-price">${service.price} руб.</span>
                    <button type="button" onclick="addService(${service.id}, '${service.name}', ${service.price})">+</button>
                </div>`;
            });
            document.getElementById('servicesResultsList').innerHTML = html;
        })
        .catch(error => {
            console.error('Search error:', error);
            document.getElementById('servicesResultsList').innerHTML = '<div class="error">Ошибка поиска</div>';
        });
}

function addService(id, name, price) {
    selectedServices.push({id, name, price, quantity: 1});
    updateSelectedServicesList();
    document.getElementById('servicesSearchResults').style.display = 'none';
}

function updateSelectedServicesList() {
    const container = document.getElementById('selectedServicesList');
    const dataField = document.getElementById('selectedServicesData');
    
    let html = '';
    selectedServices.forEach((service, index) => {
        html += `
        <div class="selected-service">
            ${service.name} - ${service.price} руб.
            <input type="number" value="${service.quantity}" onchange="updateServiceQuantity(${index}, this.value)">
            <button onclick="removeService(${index})">×</button>
        </div>`;
    });
    
    container.innerHTML = html;
    dataField.value = JSON.stringify(selectedServices);
    document.getElementById('selectedServicesCard').style.display = selectedServices.length ? 'block' : 'none';
}

function removeService(index) {
    selectedServices.splice(index, 1);
    updateSelectedServicesList();
}

function updateServiceQuantity(index, quantity) {
    selectedServices[index].quantity = parseInt(quantity);
    updateSelectedServicesList();
}
});
// Полный JavaScript для create_order.php
console.log('✅ create_order.js загружен!');

let selectedClient = null;
let selectedCar = null;
let selectedServices = [];

// 🔧 ФУНКЦИЯ АВТОЗАПОЛНЕНИЯ ПО ГОСНОМЕРУ
function getCarInfoByLicensePlate() {
    const licensePlate = document.getElementById('licensePlateInput').value.trim();
    
    if (!licensePlate) {
        alert('Введите госномер для поиска');
        return;
    }
    
    if (!selectedClient) {
        alert('Сначала выберите клиента');
        return;
    }
    
    const licensePlateInput = document.getElementById('licensePlateInput');
    licensePlateInput.disabled = true;
    
    fetch('get_car_info_dadata.php?license_plate=' + encodeURIComponent(licensePlate))
        .then(response => response.json())
        .then(data => {
            licensePlateInput.disabled = false;
            
            if (data.error) {
                alert('Ошибка: ' + data.error);
                return;
            }
            
            if (data.selection_required) {
                openCarModelSelector(data, licensePlate);
            } else {
                processCarData(data, licensePlate);
            }
        })
        .catch(error => {
            licensePlateInput.disabled = false;
            console.error('Ошибка запроса:', error);
            alert('Ошибка подключения к сервису');
        });
}

// 🔧 СЕЛЕКТОР МОДИФИКАЦИЙ
function openCarModelSelector(data, licensePlate) {
    const modalHtml = `
        <div id="carModelModal" class="modal" style="display: block;">
            <div class="modal-content" style="max-width: 800px;">
                <div class="modal-header">
                    <h3>🚗 Выбор модификации автомобиля</h3>
                    <span class="close" onclick="closeCarModelSelector()">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="license-plate-info" style="background: #e3f2fd; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
                        <strong>Госномер:</strong> ${licensePlate}
                    </div>
                    
                    <div id="modelSelectionSteps">
                        ${renderBrandSelection(data.selection_steps.brand)}
                    </div>
                    
                    <div id="selectedModelInfo" style="display: none; background: #f8fff9; padding: 15px; border: 2px solid #28a745; border-radius: 4px; margin-top: 20px;">
                        <h4>✅ Выбранная модификация:</h4>
                        <div id="modelDetails"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-1c-secondary" onclick="closeCarModelSelector()">Отмена</button>
                    <button type="button" class="btn-1c-primary" id="confirmModelBtn" onclick="confirmModelSelection()" disabled>Подтвердить выбор</button>
                </div>
            </div>
        </div>
    `;
    
    const modalContainer = document.createElement('div');
    modalContainer.innerHTML = modalHtml;
    document.body.appendChild(modalContainer);
    
    window.carSelectionData = {
        licensePlate: licensePlate,
        selectedBrand: null,
        selectedModel: null
    };
}

function renderBrandSelection(brandData) {
    let html = `
        <div class="selection-step active">
            <h4>${brandData.title}</h4>
            <div class="brand-selection" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 15px;">
    `;
    
    brandData.options.forEach(brand => {
        if (brand.group) {
            html += `<div class="brand-group" style="grid-column: 1 / -1; font-weight: bold; color: #2c3e50; margin-top: 10px;">${brand.name}</div>`;
        } else {
            const parentClass = brand.parent ? `brand-${brand.parent}` : '';
            html += `
                <button type="button" class="brand-btn ${parentClass}" 
                        onclick="selectBrand('${brand.id}', '${brand.name}')"
                        style="padding: 12px; border: 2px solid #ddd; background: white; cursor: pointer; text-align: left; border-radius: 4px;">
                    <strong>${brand.name}</strong>
                </button>
            `;
        }
    });
    
    html += `</div></div>`;
    return html;
}

function selectBrand(brandId, brandName) {
    window.carSelectionData.selectedBrand = { id: brandId, name: brandName };
    loadModelsForBrand(brandId);
}

function loadModelsForBrand(brandId) {
    const demoModels = {
        'vw': [
            { id: 'polo', name: 'Polo', generations: ['2010-2014', '2015-2017', '2018-2021', '2022-н.в.'] },
            { id: 'golf', name: 'Golf', generations: ['Golf 7 (2012-2017)', 'Golf 7.5 (2017-2020)', 'Golf 8 (2020-н.в.)'] }
        ],
        'audi': [
            { id: 'a4', name: 'A4', generations: ['B8 (2007-2011)', 'B8 facelift (2011-2015)', 'B9 (2015-2019)', 'B9 facelift (2019-2023)'] }
        ]
    };
    
    const models = demoModels[brandId] || [
        { id: 'model1', name: 'Модель 1', generations: ['2015-2020'] }
    ];
    
    renderModelSelection(models);
}

function renderModelSelection(models) {
    const stepsContainer = document.getElementById('modelSelectionSteps');
    
    stepsContainer.innerHTML += `
        <div class="selection-step active">
            <h4>Выберите модель</h4>
            <div class="model-selection" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 10px; margin-top: 15px;">
                ${models.map(model => `
                    <div class="model-card" onclick="selectModel('${model.id}', '${model.name}')"
                         style="border: 2px solid #e0e0e0; padding: 15px; border-radius: 4px; cursor: pointer; background: white;">
                        <div style="font-weight: bold; margin-bottom: 8px;">${model.name}</div>
                        <div style="font-size: 0.8em; color: #666;">Поколения: ${model.generations.join(', ')}</div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}

function selectModel(modelId, modelName) {
    window.carSelectionData.selectedModel = { id: modelId, name: modelName };
    showSelectedModelInfo();
    document.getElementById('confirmModelBtn').disabled = false;
}

function showSelectedModelInfo() {
    const infoContainer = document.getElementById('selectedModelInfo');
    const detailsContainer = document.getElementById('modelDetails');
    
    const data = window.carSelectionData;
    
    detailsContainer.innerHTML = `
        <p><strong>Марка:</strong> ${data.selectedBrand.name}</p>
        <p><strong>Модель:</strong> ${data.selectedModel.name}</p>
        <p><strong>Госномер:</strong> ${data.licensePlate}</p>
    `;
    
    infoContainer.style.display = 'block';
}

function confirmModelSelection() {
    const data = window.carSelectionData;
    
    document.getElementById('newCarMake').value = data.selectedBrand.name;
    document.getElementById('newCarModel').value = data.selectedModel.name;
    document.getElementById('newCarLicense').value = data.licensePlate;
    
    closeCarModelSelector();
    openAddCarModal();
}

function closeCarModelSelector() {
    const modal = document.getElementById('carModelModal');
    if (modal) modal.remove();
    window.carSelectionData = null;
}

function processCarData(data, licensePlate) {
    if (data.length > 0 && data[0].result) {
        const carInfo = data[0].result;
        
        document.getElementById('newCarMake').value = carInfo.brand || '';
        document.getElementById('newCarModel').value = carInfo.model || '';
        document.getElementById('newCarLicense').value = licensePlate;
        document.getElementById('newCarYear').value = carInfo.year || '';
        document.getElementById('newCarVin').value = carInfo.vin || '';
        
        openAddCarModal();
    } else {
        alert('Автомобиль не найден');
    }
}

// РАБОТА С КЛИЕНТАМИ
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
    document.getElementById('addClientForm').reset();
}

function closeAddClientModal() {
    document.getElementById('addClientModal').style.display = 'none';
}

function loadClients(searchTerm = '') {
    const clientsList = document.getElementById('clientsList');
    clientsList.innerHTML = '<div style="padding: 20px; text-align: center;">Загрузка...</div>';

    const url = searchTerm ? 
        `get_clients.php?search=${encodeURIComponent(searchTerm)}` : 
        'get_clients.php';

    fetch(url)
        .then(response => response.json())
        .then(clients => {
            displayClients(clients);
        })
        .catch(error => {
            console.error('Ошибка при загрузке клиентов:', error);
            clientsList.innerHTML = '<div style="padding: 20px; text-align: center; color: red;">Ошибка загрузки</div>';
        });
}

function searchClients() {
    const searchInput = document.getElementById('clientSearch');
    if (searchInput) {
        loadClients(searchInput.value.trim());
    }
}

function displayClients(clients) {
    const clientsList = document.getElementById('clientsList');
    
    if (!clientsList) return;

    if (!clients || clients.length === 0) {
        clientsList.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">Клиенты не найдены</div>';
        return;
    }

    clientsList.innerHTML = clients.map(client => `
        <div class="modal-item" onclick="selectClient(${JSON.stringify(client).replace(/"/g, '&quot;')})">
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
        </div>
    `).join('');
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
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Ошибка поиска услуг:', error);
            resultsList.innerHTML = '<div style="padding: 20px; text-align: center; color: red;">Ошибка поиска услуг</div>';
        });
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

// ОБЩИЕ ФУНКЦИИ
function checkFormCompletion() {
    const clientId = document.getElementById('selectedClientId').value;
    const carId = document.getElementById('selectedCarId').value;
    const description = document.getElementById('description').value.trim();
    const createOrderBtn = document.getElementById('createOrderBtn');
    
    createOrderBtn.disabled = !(clientId && carId && description);
}

// ДЕБАГ ФУНКЦИЯ - проверка состояния формы
function debugForm() {
    console.log('=== ДЕБАГ ФОРМЫ ===');
    console.log('selectedClient:', selectedClient);
    console.log('selectedCar:', selectedCar);
    console.log('selectedServices:', selectedServices);
    console.log('clientId input:', document.getElementById('selectedClientId').value);
    console.log('carId input:', document.getElementById('selectedCarId').value);
    console.log('description:', document.getElementById('description').value);
    console.log('services_data:', document.getElementById('selectedServicesData').value);
    
    const clientId = document.getElementById('selectedClientId').value;
    const carId = document.getElementById('selectedCarId').value;
    const description = document.getElementById('description').value.trim();
    
    console.log('Форма заполнена:', !!(clientId && carId && description));
    console.log('createOrderBtn disabled:', document.getElementById('createOrderBtn').disabled);
    
    // Тест отправки формы
    console.log('=== ТЕСТ ОТПРАВКИ ===');
    const formData = new FormData(document.getElementById('orderForm'));
    for (let [key, value] of formData.entries()) {
        console.log(key + ': ' + value);
    }
}

// ИНИЦИАЛИЗАЦИЯ
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM загружен, инициализация...');
    
    document.getElementById('description').addEventListener('input', checkFormCompletion);
    
    // Обработка Enter в поиске услуг
    document.getElementById('serviceQuickSearch').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') searchServices();
    });
    
    // Обработка Enter в госномере
    document.getElementById('licensePlateInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            getCarInfoByLicensePlate();
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
});

// ОБРАБОТЧИКИ ФОРМ
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

// ОБРАБОТЧИК СОЗДАНИЯ ЗАКАЗА
document.getElementById('orderForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    console.log('✅ Форма отправляется...');
    
    const clientId = document.getElementById('selectedClientId').value;
    const carId = document.getElementById('selectedCarId').value;
    const description = document.getElementById('description').value.trim();
    
    console.log('Данные формы:', {
        client_id: clientId,
        car_id: carId,
        description: description,
        services_data: document.getElementById('selectedServicesData').value
    });
    
    // Проверка обязательных полей
    if (!clientId) {
        alert('Выберите клиента');
        return;
    }
    
    if (!carId) {
        alert('Выберите автомобиль');
        return;
    }
    
    if (!description) {
        alert('Введите описание проблемы');
        return;
    }
    
    // Блокируем кнопку чтобы избежать повторных отправок
    const submitBtn = document.getElementById('createOrderBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Создание...';
    
    // Отправляем форму
    const formData = new FormData(this);
    
    fetch('create_order.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response redirected:', response.redirected);
        console.log('Response URL:', response.url);
        
        if (response.redirected) {
            window.location.href = response.url;
            return;
        }
        return response.text();
    })
    .then(data => {
        if (data) {
            console.log('Response data:', data);
            if (data.includes('error') || data.includes('Ошибка')) {
                alert('Ошибка при создании заказа. Проверьте консоль для деталей.');
            } else {
                window.location.reload();
            }
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        alert('Ошибка сети: ' + error.message);
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = '✅ Создать заказ';
    });
});

console.log('🚀 Все функции create_order.js инициализированы');
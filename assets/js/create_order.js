let selectedClient = null;
let selectedCar = null;

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
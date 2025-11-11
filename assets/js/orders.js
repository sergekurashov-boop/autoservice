// assets/js/orders.js

function searchClients() {
    const query = document.getElementById('clientSearch').value.trim();
    if (query.length < 2) {
        document.getElementById('clientSearchResults').innerHTML = '';
        return;
    }
    
    const url = `search_clients.php?q=${encodeURIComponent(query)}`;
    console.log("Fetching URL:", url);
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            const resultsContainer = document.getElementById('clientSearchResults');
            resultsContainer.innerHTML = '';
            
            if (data.length === 0) {
                resultsContainer.innerHTML = `
                    <div class="list-group-item">
                        <p class="mb-0">Клиент не найден</p>
                        <button type="button" class="btn btn-sm btn-success mt-2" 
                                onclick="showClientModal()">
                            <i class="bi bi-plus"></i> Добавить нового клиента
                        </button>
                    </div>
                `;
                return;
            }
            
            data.forEach(client => {
                const item = document.createElement('div');
                item.className = 'list-group-item list-group-item-action';
                const safeName = client.name.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                item.innerHTML = `
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">${safeName}</h6>
                        <small>${client.phone}</small>
                    </div>
                    <p class="mb-1">${client.email || 'Email не указан'}</p>
                    <button type="button" class="btn btn-sm btn-primary mt-2" 
                            onclick="selectClient(${client.id}, '${safeName}')">
                        Выбрать
                    </button>
                `;
                resultsContainer.appendChild(item);
            });
        })
        .catch(error => {
            console.error('Fetch error:', error);
            document.getElementById('clientSearchResults').innerHTML = `
                <div class="list-group-item text-danger">
                    Ошибка загрузки данных: ${error.message}
                </div>
            `;
        });
}

function selectClient(clientId, clientName) {
    document.getElementById('selectedClient').innerText = clientName;
    document.getElementById('selectedClientId').value = clientId;
    document.getElementById('clientSearchContainer').classList.add('d-none');
    document.getElementById('clientInfoContainer').classList.remove('d-none');
    document.getElementById('carSelect').disabled = false;
    
    loadClientCars(clientId);
}

function loadClientCars(clientId) {
    const carSelect = document.getElementById('carSelect');
    carSelect.innerHTML = '<option value="">Загрузка...</option>';
    
    fetch(`get_cars.php?client_id=${clientId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Ошибка загрузки автомобилей');
            }
            return response.text();
        })
        .then(data => {
            carSelect.innerHTML = data;
        })
        .catch(error => {
            console.error('Error:', error);
            carSelect.innerHTML = '<option value="">Ошибка загрузки</option>';
            showAlert('Ошибка при загрузке автомобилей: ' + error.message, 'danger');
        });
}

function showClientModal() {
    document.getElementById('clientForm').reset();
    const clientModal = new bootstrap.Modal(document.getElementById('clientModal'));
    clientModal.show();
}

function showCarModal() {
    const clientId = document.getElementById('selectedClientId').value;
    if (!clientId) {
        showAlert('Сначала выберите клиента', 'warning');
        return;
    }
    
    document.getElementById('carForm').reset();
    document.getElementById('carClientId').value = clientId;
    const carModal = new bootstrap.Modal(document.getElementById('carModal'));
    carModal.show();
}

function addClient() {
    const form = document.getElementById('clientForm');
    const formData = new FormData(form);
    
    const submitBtn = document.querySelector('#clientModal .btn-primary');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Добавление...';
    submitBtn.disabled = true;
    
    fetch('orders.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
        } else {
            return response.text();
        }
    })
    .then(() => {
        window.location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Произошла ошибка при добавлении клиента: ' + error.message, 'danger');
    })
    .finally(() => {
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
    });
}

function addCar() {
    const form = document.getElementById('carForm');
    const formData = new FormData(form);
    const clientId = document.getElementById('selectedClientId').value;
    
    const submitBtn = document.querySelector('#carModal .btn-primary');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Добавление...';
    submitBtn.disabled = true;
    
    fetch('orders.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(() => {
        loadClientCars(clientId);
        const carModal = bootstrap.Modal.getInstance(document.getElementById('carModal'));
        carModal.hide();
        showAlert('Автомобиль успешно добавлен!', 'success');
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Произошла ошибка при добавлении автомобиля: ' + error.message, 'danger');
    })
    .finally(() => {
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
    });
}

function showAlert(message, type) {
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-enhanced`;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    const container = document.querySelector('.container');
    container.prepend(alertDiv);
    
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alertDiv);
        bsAlert.close();
    }, 5000);
}

function confirmDelete(orderId) {
    if (confirm(`Вы уверены, что хотите удалить заказ #${orderId}? Это действие необратимо!`)) {
        const deleteBtn = document.querySelector(`#deleteBtn${orderId}`);
        const originalBtnHtml = deleteBtn.innerHTML;
        deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        deleteBtn.disabled = true;
        
        const form = document.getElementById(`deleteForm${orderId}`);
        const formData = new FormData(form);
        formData.append('ajax_request', '1');
        
        fetch('orders.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Заказ успешно удалён!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                deleteBtn.innerHTML = originalBtnHtml;
                deleteBtn.disabled = false;
                showAlert(data.error || 'Ошибка при удалении заказа', 'danger');
            }
        })
        .catch(error => {
            deleteBtn.innerHTML = originalBtnHtml;
            deleteBtn.disabled = false;
            showAlert('Произошла ошибка при удалении заказа: ' + error.message, 'danger');
        });
    }
}

function resetClientSelection() {
    document.getElementById('selectedClientId').value = '';
    document.getElementById('clientSearch').value = '';
    document.getElementById('clientSearchResults').innerHTML = '';
    document.getElementById('clientSearchContainer').classList.remove('d-none');
    document.getElementById('clientInfoContainer').classList.add('d-none');
    document.getElementById('carSelect').disabled = true;
    document.getElementById('carSelect').innerHTML = '<option value="">Сначала выберите клиента</option>';
}

// Инициализация после загрузки DOM
document.addEventListener('DOMContentLoaded', function() {
    // Устанавливаем client_id в форму автомобиля при открытии модального окна
    document.getElementById('carModal').addEventListener('show.bs.modal', function () {
        const clientId = document.getElementById('selectedClientId').value;
        document.getElementById('carClientId').value = clientId;
    });
});
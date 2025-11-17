let currentStep = 1;
let orderData = {
    client: null,
    car: null,
    problem: '',
    services: [],
    parts: []
};

// Навигация по шагам
function nextStep() {
    if (validateStep(currentStep)) {
        document.getElementById(`step-${currentStep}`).classList.remove('active');
        document.querySelector(`.step[data-step="${currentStep}"]`).classList.remove('active');
        
        currentStep++;
        
        document.getElementById(`step-${currentStep}`).classList.add('active');
        document.querySelector(`.step[data-step="${currentStep}"]`).classList.add('active');
        
        updateNavigation();
    }
}

function prevStep() {
    document.getElementById(`step-${currentStep}`).classList.remove('active');
    document.querySelector(`.step[data-step="${currentStep}"]`).classList.remove('active');
    
    currentStep--;
    
    document.getElementById(`step-${currentStep}`).classList.add('active');
    document.querySelector(`.step[data-step="${currentStep}"]`).classList.add('active');
    
    updateNavigation();
}

function updateNavigation() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const createBtn = document.getElementById('createBtn');
    
    prevBtn.style.display = currentStep > 1 ? 'block' : 'none';
    
    if (currentStep < 5) {
        nextBtn.style.display = 'block';
        createBtn.style.display = 'none';
    } else {
        nextBtn.style.display = 'none';
        createBtn.style.display = 'block';
    }
}

function validateStep(step) {
    switch(step) {
        case 1:
            if (!orderData.client) {
                alert('Выберите клиента');
                return false;
            }
            return true;
        case 2:
            if (!orderData.car) {
                alert('Выберите автомобиль');
                return false;
            }
            return true;
        case 3:
            if (!orderData.problem.trim()) {
                alert('Опишите проблему');
                return false;
            }
            return true;
        default:
            return true;
    }
}

// Работа с клиентами
function selectClient(client) {
    orderData.client = client;
    
    document.getElementById('selectedClientName').textContent = client.name;
    document.getElementById('selectedClientDetails').innerHTML = `
        <div>📞 ${client.phone || 'Телефон не указан'}</div>
        <div>📧 ${client.email || 'Email не указан'}</div>
    `;
    document.getElementById('selectedClientCard').style.display = 'flex';
    
    closeClientModal();
}

function clearSelection(type) {
    if (type === 'client') {
        orderData.client = null;
        document.getElementById('selectedClientCard').style.display = 'none';
    } else if (type === 'car') {
        orderData.car = null;
        document.getElementById('selectedCarCard').style.display = 'none';
    }
}

// Работа с автомобилями
function selectCar(car) {
    orderData.car = car;
    
    document.getElementById('selectedCarTitle').textContent = `${car.make} ${car.model}`;
    document.getElementById('selectedCarDetails').innerHTML = `
        <div>🚗 ${car.license_plate}</div>
        <div>📅 ${car.year || 'Год не указан'}</div>
        <div>🔢 VIN: ${car.vin || 'не указан'}</div>
    `;
    document.getElementById('selectedCarCard').style.display = 'flex';
    
    closeCarModal();
}

// Сохранение проблемы
document.getElementById('problemDescription').addEventListener('input', function() {
    orderData.problem = this.value;
});

// Создание заказа
function createOrder() {
    if (!validateStep(5)) return;
    
    // Отправка данных на сервер
    const formData = new FormData();
    formData.append('client_id', orderData.client.id);
    formData.append('car_id', orderData.car.id);
    formData.append('description', orderData.problem);
    formData.append('services', JSON.stringify(orderData.services));
    formData.append('parts', JSON.stringify(orderData.parts));
    
    fetch('save_order.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Заказ успешно создан!');
            window.location.href = `order_edit.php?id=${result.order_id}`;
        } else {
            alert('Ошибка: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        alert('Ошибка при создании заказа');
    });
}

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    updateNavigation();
});
class CarsManager {
    constructor() {
        this.init();
    }

    init() {
        this.initFormValidation();
        this.initClientSearch();
    }

    initFormValidation() {
        const form = document.getElementById('carForm');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            if (!this.validateForm()) {
                e.preventDefault();
                alert('Пожалуйста, исправьте ошибки в форме');
            }
        });
    }

    validateForm() {
        const clientId = document.querySelector('select[name="client_id"]').value;
        const make = document.querySelector('input[name="make"]').value.trim();
        const model = document.querySelector('input[name="model"]').value.trim();
        const year = document.querySelector('input[name="year"]').value;
        const vin = document.querySelector('input[name="vin"]').value.trim();

        let isValid = true;

        if (!clientId) {
            this.markInvalid(document.querySelector('select[name="client_id"]'), 'Выберите владельца');
            isValid = false;
        } else {
            this.markValid(document.querySelector('select[name="client_id"]'));
        }

        if (!make) {
            this.markInvalid(document.querySelector('input[name="make"]'), 'Введите марку автомобиля');
            isValid = false;
        } else {
            this.markValid(document.querySelector('input[name="make"]'));
        }

        if (!model) {
            this.markInvalid(document.querySelector('input[name="model"]'), 'Введите модель автомобиля');
            isValid = false;
        } else {
            this.markValid(document.querySelector('input[name="model"]'));
        }

        if (year && (year < 1900 || year > new Date().getFullYear() + 1)) {
            this.markInvalid(document.querySelector('input[name="year"]'), 'Некорректный год выпуска');
            isValid = false;
        } else {
            this.markValid(document.querySelector('input[name="year"]'));
        }

        if (vin && !this.validateVIN(vin)) {
            this.markInvalid(document.querySelector('input[name="vin"]'), 'Некорректный VIN');
            isValid = false;
        } else {
            this.markValid(document.querySelector('input[name="vin"]'));
        }

        return isValid;
    }

    validateVIN(vin) {
        // Базовая валидация VIN (17 символов, определенный формат)
        const vinRegex = /^[A-HJ-NPR-Z0-9]{17}$/i;
        return vinRegex.test(vin);
    }

    markInvalid(input, message) {
        input.classList.add('error');
        let oldError = input.parentNode.querySelector('.error-text');
        if (oldError) oldError.remove();
        
        let errorDiv = document.createElement('div');
        errorDiv.className = 'error-text text-danger mt-1 small';
        errorDiv.textContent = message;
        input.parentNode.appendChild(errorDiv);
    }

    markValid(input) {
        input.classList.remove('error');
        let errorDiv = input.parentNode.querySelector('.error-text');
        if (errorDiv) errorDiv.remove();
    }

    initClientSearch() {
        // Можно добавить поиск клиента как в clients.js
        console.log('CarsManager инициализирован');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.carsManager = new CarsManager();
});
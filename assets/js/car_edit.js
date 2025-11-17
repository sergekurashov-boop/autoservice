class CarEditManager {
    constructor() {
        this.init();
    }

    init() {
        this.initFormValidation();
        this.initYearValidation();
        this.initVINValidation();
    }

    initFormValidation() {
        const form = document.getElementById('carEditForm');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            if (!this.validateForm()) {
                e.preventDefault();
                this.showAlert('Пожалуйста, исправьте ошибки в форме', 'error');
            }
        });
    }

    initYearValidation() {
        const yearInput = document.querySelector('input[name="year"]');
        if (yearInput) {
            yearInput.addEventListener('blur', () => {
                this.validateYear(yearInput.value);
            });
        }
    }

    initVINValidation() {
        const vinInput = document.querySelector('input[name="vin"]');
        if (vinInput) {
            vinInput.addEventListener('blur', () => {
                this.validateVIN(vinInput.value);
            });
        }
    }

    validateForm() {
        const clientId = document.querySelector('select[name="client_id"]').value;
        const make = document.querySelector('input[name="make"]').value.trim();
        const model = document.querySelector('input[name="model"]').value.trim();
        const year = document.querySelector('input[name="year"]').value;
        const vin = document.querySelector('input[name="vin"]').value.trim();

        let isValid = true;

        // Валидация владельца
        if (!clientId) {
            this.markInvalid(document.querySelector('select[name="client_id"]'), 'Выберите владельца');
            isValid = false;
        } else {
            this.markValid(document.querySelector('select[name="client_id"]'));
        }

        // Валидация марки
        if (!make) {
            this.markInvalid(document.querySelector('input[name="make"]'), 'Введите марку автомобиля');
            isValid = false;
        } else {
            this.markValid(document.querySelector('input[name="make"]'));
        }

        // Валидация модели
        if (!model) {
            this.markInvalid(document.querySelector('input[name="model"]'), 'Введите модель автомобиля');
            isValid = false;
        } else {
            this.markValid(document.querySelector('input[name="model"]'));
        }

        // Валидация года
        if (year && !this.validateYear(year)) {
            isValid = false;
        }

        // Валидация VIN
        if (vin && !this.validateVIN(vin)) {
            isValid = false;
        }

        return isValid;
    }

    validateYear(year) {
        const yearInput = document.querySelector('input[name="year"]');
        const currentYear = new Date().getFullYear();
        
        if (year && (year < 1900 || year > currentYear + 1)) {
            this.markInvalid(yearInput, `Год должен быть между 1900 и ${currentYear + 1}`);
            return false;
        } else {
            this.markValid(yearInput);
            return true;
        }
    }

    validateVIN(vin) {
        const vinInput = document.querySelector('input[name="vin"]');
        
        if (vin && vin.length !== 17) {
            this.markInvalid(vinInput, 'VIN должен содержать 17 символов');
            return false;
        }
        
        // Базовая валидация формата VIN
        const vinRegex = /^[A-HJ-NPR-Z0-9]{17}$/i;
        if (vin && !vinRegex.test(vin)) {
            this.markInvalid(vinInput, 'Некорректный формат VIN');
            return false;
        } else {
            this.markValid(vinInput);
            return true;
        }
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

    showAlert(message, type = 'info') {
        // Можно добавить красивые уведомления
        alert(message);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.carEditManager = new CarEditManager();
});
class ServiceEditManager {
    constructor() {
        this.init();
    }

    init() {
        this.initFormValidation();
        this.initPriceFormatting();
    }

    initFormValidation() {
        const form = document.getElementById('serviceEditForm');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            if (!this.validateForm()) {
                e.preventDefault();
                this.showAlert('Пожалуйста, исправьте ошибки в форме', 'error');
            }
        });

        // Валидация названия при вводе
        const nameInput = document.querySelector('input[name="name"]');
        if (nameInput) {
            nameInput.addEventListener('blur', () => {
                this.validateName(nameInput.value);
            });
        }

        // Валидация цены при вводе
        const priceInput = document.querySelector('input[name="price"]');
        if (priceInput) {
            priceInput.addEventListener('input', (e) => {
                this.formatPriceInput(e.target);
            });
            priceInput.addEventListener('blur', () => {
                this.validatePrice(priceInput.value);
            });
        }
    }

    initPriceFormatting() {
        // Форматирование текущей цены в информации об услуге
        const currentPrice = document.querySelector('.current-price');
        if (currentPrice) {
            const price = parseFloat(currentPrice.textContent);
            if (!isNaN(price)) {
                currentPrice.textContent = this.formatPrice(price);
            }
        }
    }

    validateForm() {
        const name = document.querySelector('input[name="name"]').value.trim();
        const price = document.querySelector('input[name="price"]').value;

        let isValid = true;

        // Валидация названия
        if (!this.validateName(name)) {
            isValid = false;
        }

        // Валидация цены
        if (!this.validatePrice(price)) {
            isValid = false;
        }

        return isValid;
    }

    validateName(name) {
        const nameInput = document.querySelector('input[name="name"]');
        
        if (!name) {
            this.markInvalid(nameInput, 'Введите название услуги');
            return false;
        } else if (name.length < 2) {
            this.markInvalid(nameInput, 'Название должно содержать минимум 2 символа');
            return false;
        } else if (name.length > 100) {
            this.markInvalid(nameInput, 'Название не должно превышать 100 символов');
            return false;
        } else {
            this.markValid(nameInput);
            return true;
        }
    }

    validatePrice(price) {
        const priceInput = document.querySelector('input[name="price"]');
        const priceValue = parseFloat(price);
        
        if (!price || isNaN(priceValue)) {
            this.markInvalid(priceInput, 'Введите корректную цену');
            return false;
        } else if (priceValue <= 0) {
            this.markInvalid(priceInput, 'Цена должна быть больше 0');
            return false;
        } else if (priceValue > 1000000) {
            this.markInvalid(priceInput, 'Цена не может превышать 1 000 000 руб.');
            return false;
        } else {
            this.markValid(priceInput);
            return true;
        }
    }

    formatPriceInput(input) {
        // Убираем все символы кроме цифр и точки
        let value = input.value.replace(/[^\d.]/g, '');
        
        // Оставляем только одну точку
        const parts = value.split('.');
        if (parts.length > 2) {
            value = parts[0] + '.' + parts.slice(1).join('');
        }
        
        // Ограничиваем до 2 знаков после запятой
        if (parts.length === 2 && parts[1].length > 2) {
            value = parts[0] + '.' + parts[1].substring(0, 2);
        }
        
        input.value = value;
    }

    formatPrice(price) {
        return new Intl.NumberFormat('ru-RU', {
            style: 'currency',
            currency: 'RUB',
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        }).format(price);
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
    window.serviceEditManager = new ServiceEditManager();
});
class ServicesManager {
    constructor() {
        this.init();
    }

    init() {
        this.initFormValidation();
        this.initPriceFormatting();
    }

    initFormValidation() {
        const form = document.getElementById('serviceForm');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            if (!this.validateForm()) {
                e.preventDefault();
                this.showAlert('Пожалуйста, исправьте ошибки в форме', 'error');
            }
        });

        // Валидация цены при вводе
        const priceInput = document.querySelector('input[name="price"]');
        if (priceInput) {
            priceInput.addEventListener('input', (e) => {
                this.formatPriceInput(e.target);
            });
        }
    }

    initPriceFormatting() {
        // Форматирование цен в таблице
        const priceCells = document.querySelectorAll('.price-cell');
        priceCells.forEach(cell => {
            const price = parseFloat(cell.textContent);
            if (!isNaN(price)) {
                cell.innerHTML = `<span class="price-badge">${this.formatPrice(price)}</span>`;
            }
        });
    }

    validateForm() {
        const name = document.querySelector('input[name="name"]').value.trim();
        const price = document.querySelector('input[name="price"]').value;

        let isValid = true;

        // Валидация названия
        if (!name) {
            this.markInvalid(document.querySelector('input[name="name"]'), 'Введите название услуги');
            isValid = false;
        } else if (name.length < 2) {
            this.markInvalid(document.querySelector('input[name="name"]'), 'Название должно содержать минимум 2 символа');
            isValid = false;
        } else {
            this.markValid(document.querySelector('input[name="name"]'));
        }

        // Валидация цены
        const priceValue = parseFloat(price);
        if (!price || isNaN(priceValue)) {
            this.markInvalid(document.querySelector('input[name="price"]'), 'Введите корректную цену');
            isValid = false;
        } else if (priceValue <= 0) {
            this.markInvalid(document.querySelector('input[name="price"]'), 'Цена должна быть больше 0');
            isValid = false;
        } else if (priceValue > 1000000) {
            this.markInvalid(document.querySelector('input[name="price"]'), 'Цена не может превышать 1 000 000 руб.');
            isValid = false;
        } else {
            this.markValid(document.querySelector('input[name="price"]'));
        }

        return isValid;
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
    window.servicesManager = new ServicesManager();
});
class PartsManager {
    constructor() {
        this.init();
    }

    init() {
        this.initFormValidation();
        this.initPriceFormatting();
        this.initQuantityValidation();
    }

    initFormValidation() {
        const form = document.getElementById('partForm');
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
            priceInput.addEventListener('blur', () => {
                this.validatePrice(priceInput.value);
            });
        }

        // Валидация количества при вводе
        const quantityInput = document.querySelector('input[name="quantity"]');
        if (quantityInput) {
            quantityInput.addEventListener('blur', () => {
                this.validateQuantity(quantityInput.value);
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

        // Форматирование количества в таблице
        const quantityCells = document.querySelectorAll('.quantity-cell');
        quantityCells.forEach(cell => {
            const quantity = parseInt(cell.textContent);
            if (!isNaN(quantity)) {
                const badgeClass = quantity === 0 ? 'quantity-badge quantity-low' : 'quantity-badge';
                cell.innerHTML = `<span class="${badgeClass}">${quantity} шт.</span>`;
            }
        });
    }

    initQuantityValidation() {
        const quantityInput = document.querySelector('input[name="quantity"]');
        if (quantityInput) {
            quantityInput.addEventListener('input', (e) => {
                // Разрешаем только целые числа
                e.target.value = e.target.value.replace(/[^\d]/g, '');
            });
        }
    }

    validateForm() {
        const name = document.querySelector('input[name="name"]').value.trim();
        const partNumber = document.querySelector('input[name="part_number"]').value.trim();
        const price = document.querySelector('input[name="price"]').value;
        const quantity = document.querySelector('input[name="quantity"]').value;

        let isValid = true;

        // Валидация названия
        if (!this.validateName(name)) {
            isValid = false;
        }

        // Валидация артикула
        if (!this.validatePartNumber(partNumber)) {
            isValid = false;
        }

        // Валидация цены
        if (!this.validatePrice(price)) {
            isValid = false;
        }

        // Валидация количества
        if (!this.validateQuantity(quantity)) {
            isValid = false;
        }

        return isValid;
    }

    validateName(name) {
        const nameInput = document.querySelector('input[name="name"]');
        
        if (!name) {
            this.markInvalid(nameInput, 'Введите название запчасти');
            return false;
        } else if (name.length < 2) {
            this.markInvalid(nameInput, 'Название должно содержать минимум 2 символа');
            return false;
        } else if (name.length > 255) {
            this.markInvalid(nameInput, 'Название не должно превышать 255 символов');
            return false;
        } else {
            this.markValid(nameInput);
            return true;
        }
    }

    validatePartNumber(partNumber) {
        const partNumberInput = document.querySelector('input[name="part_number"]');
        
        if (!partNumber) {
            this.markInvalid(partNumberInput, 'Введите артикул запчасти');
            return false;
        } else if (partNumber.length > 100) {
            this.markInvalid(partNumberInput, 'Артикул не должен превышать 100 символов');
            return false;
        } else {
            this.markValid(partNumberInput);
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

    validateQuantity(quantity) {
        const quantityInput = document.querySelector('input[name="quantity"]');
        const quantityValue = parseInt(quantity) || 0;
        
        if (quantityValue < 0) {
            this.markInvalid(quantityInput, 'Количество не может быть отрицательным');
            return false;
        } else if (quantityValue > 100000) {
            this.markInvalid(quantityInput, 'Количество не может превышать 100 000');
            return false;
        } else {
            this.markValid(quantityInput);
            return true;
        }
    }

    formatPrice(price) {
        return new Intl.NumberFormat('ru-RU', {
            style: 'currency',
            currency: 'RUB',
            minimumFractionDigits: 2,
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
        alert(message);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.partsManager = new PartsManager();
});
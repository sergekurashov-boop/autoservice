class MechanicsManager {
    constructor() {
        this.init();
    }

    init() {
        this.initFormValidation();
        this.initPhoneFormatting();
    }

    initFormValidation() {
        const form = document.getElementById('mechanicForm');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            if (!this.validateForm()) {
                e.preventDefault();
                this.showAlert('Пожалуйста, исправьте ошибки в форме', 'error');
            }
        });

        // Валидация имени при вводе
        const nameInput = document.querySelector('input[name="name"]');
        if (nameInput) {
            nameInput.addEventListener('blur', () => {
                this.validateName(nameInput.value);
            });
        }

        // Валидация телефона при вводе
        const phoneInput = document.querySelector('input[name="phone"]');
        if (phoneInput) {
            phoneInput.addEventListener('blur', () => {
                this.validatePhone(phoneInput.value);
            });
        }
    }

    initPhoneFormatting() {
        const phoneInput = document.querySelector('input[name="phone"]');
        if (phoneInput) {
            phoneInput.addEventListener('input', (e) => {
                this.formatPhoneInput(e.target);
            });
        }
    }

    validateForm() {
        const name = document.querySelector('input[name="name"]').value.trim();
        const phone = document.querySelector('input[name="phone"]').value.trim();
        const specialty = document.querySelector('input[name="specialty"]').value.trim();

        let isValid = true;

        // Валидация имени
        if (!this.validateName(name)) {
            isValid = false;
        }

        // Валидация телефона (если указан)
        if (phone && !this.validatePhone(phone)) {
            isValid = false;
        }

        // Валидация специальности (если указана)
        if (specialty && !this.validateSpecialty(specialty)) {
            isValid = false;
        }

        return isValid;
    }

    validateName(name) {
        const nameInput = document.querySelector('input[name="name"]');
        
        if (!name) {
            this.markInvalid(nameInput, 'Введите ФИО мастера');
            return false;
        } else if (name.length < 2) {
            this.markInvalid(nameInput, 'ФИО должно содержать минимум 2 символа');
            return false;
        } else if (name.length > 100) {
            this.markInvalid(nameInput, 'ФИО не должно превышать 100 символов');
            return false;
        } else {
            this.markValid(nameInput);
            return true;
        }
    }

    validatePhone(phone) {
        const phoneInput = document.querySelector('input[name="phone"]');
        
        if (phone) {
            // Базовая валидация российского номера
            const phoneRegex = /^(\+7|8)[\s\-]?\(?[0-9]{3}\)?[\s\-]?[0-9]{3}[\s\-]?[0-9]{2}[\s\-]?[0-9]{2}$/;
            if (!phoneRegex.test(phone.replace(/\s/g, ''))) {
                this.markInvalid(phoneInput, 'Введите корректный номер телефона');
                return false;
            }
        }
        
        this.markValid(phoneInput);
        return true;
    }

    validateSpecialty(specialty) {
        const specialtyInput = document.querySelector('input[name="specialty"]');
        
        if (specialty && specialty.length > 100) {
            this.markInvalid(specialtyInput, 'Специальность не должна превышать 100 символов');
            return false;
        }
        
        this.markValid(specialtyInput);
        return true;
    }

    formatPhoneInput(input) {
        let value = input.value.replace(/\D/g, '');
        
        if (value.startsWith('7') || value.startsWith('8')) {
            value = value.substring(1);
        }
        
        if (value.length > 0) {
            value = '+7 (' + value;
            if (value.length > 7) value = value.substring(0, 7) + ') ' + value.substring(7);
            if (value.length > 12) value = value.substring(0, 12) + '-' + value.substring(12);
            if (value.length > 15) value = value.substring(0, 15) + '-' + value.substring(15);
        }
        
        input.value = value;
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
    window.mechanicsManager = new MechanicsManager();
});
/**
 * Form Validation Handler
 * Provides real-time form validation for admin forms
 */

class FormValidation {
    constructor() {
        this.forms = document.querySelectorAll('form[data-validate]');
        this.init();
    }
    
    init() {
        this.forms.forEach(form => {
            this.setupFormValidation(form);
        });
    }
    
    setupFormValidation(form) {
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearFieldError(input));
        });
        
        form.addEventListener('submit', (e) => {
            if (!this.validateForm(form)) {
                e.preventDefault();
            }
        });
    }
    
    validateField(field) {
        const rules = this.getValidationRules(field);
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';
        
        // Required validation
        if (rules.required && !value) {
            isValid = false;
            errorMessage = `${this.getFieldLabel(field)} is required.`;
        }
        
        // Min length validation
        if (isValid && rules.minLength && value.length < rules.minLength) {
            isValid = false;
            errorMessage = `${this.getFieldLabel(field)} must be at least ${rules.minLength} characters.`;
        }
        
        // Max length validation
        if (isValid && rules.maxLength && value.length > rules.maxLength) {
            isValid = false;
            errorMessage = `${this.getFieldLabel(field)} must not exceed ${rules.maxLength} characters.`;
        }
        
        // Email validation
        if (isValid && rules.email && value && !this.isValidEmail(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address.';
        }
        
        // Number validation
        if (isValid && rules.number && value && !this.isValidNumber(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid number.';
        }
        
        // Min/Max value validation for numbers
        if (isValid && rules.min !== undefined && parseFloat(value) < rules.min) {
            isValid = false;
            errorMessage = `${this.getFieldLabel(field)} must be at least ${rules.min}.`;
        }
        
        if (isValid && rules.max !== undefined && parseFloat(value) > rules.max) {
            isValid = false;
            errorMessage = `${this.getFieldLabel(field)} must not exceed ${rules.max}.`;
        }
        
        this.updateFieldValidation(field, isValid, errorMessage);
        return isValid;
    }
    
    validateForm(form) {
        const inputs = form.querySelectorAll('input, textarea, select');
        let isFormValid = true;
        
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isFormValid = false;
            }
        });
        
        return isFormValid;
    }
    
    getValidationRules(field) {
        const rules = {};
        
        // Get rules from HTML attributes
        if (field.hasAttribute('required')) rules.required = true;
        if (field.hasAttribute('minlength')) rules.minLength = parseInt(field.getAttribute('minlength'));
        if (field.hasAttribute('maxlength')) rules.maxLength = parseInt(field.getAttribute('maxlength'));
        if (field.type === 'email') rules.email = true;
        if (field.type === 'number') rules.number = true;
        if (field.hasAttribute('min')) rules.min = parseFloat(field.getAttribute('min'));
        if (field.hasAttribute('max')) rules.max = parseFloat(field.getAttribute('max'));
        
        // Get custom rules from data attributes
        if (field.dataset.rules) {
            try {
                const customRules = JSON.parse(field.dataset.rules);
                Object.assign(rules, customRules);
            } catch (e) {
                console.warn('Invalid validation rules JSON:', field.dataset.rules);
            }
        }
        
        return rules;
    }
    
    getFieldLabel(field) {
        const label = field.closest('.form-group')?.querySelector('label');
        if (label) {
            return label.textContent.replace('*', '').trim();
        }
        
        return field.getAttribute('placeholder') || field.name || 'Field';
    }
    
    updateFieldValidation(field, isValid, errorMessage) {
        // Remove existing validation classes
        field.classList.remove('is-valid', 'is-invalid');
        
        // Add appropriate validation class
        if (field.value.trim()) {
            field.classList.add(isValid ? 'is-valid' : 'is-invalid');
        }
        
        // Update error message
        this.updateErrorMessage(field, isValid ? '' : errorMessage);
    }
    
    updateErrorMessage(field, message) {
        const formGroup = field.closest('.form-group') || field.parentNode;
        let errorElement = formGroup.querySelector('.form-error');
        
        if (message) {
            if (!errorElement) {
                errorElement = document.createElement('div');
                errorElement.className = 'form-error';
                formGroup.appendChild(errorElement);
            }
            errorElement.textContent = message;
        } else if (errorElement) {
            errorElement.remove();
        }
    }
    
    clearFieldError(field) {
        field.classList.remove('is-invalid');
        const formGroup = field.closest('.form-group') || field.parentNode;
        const errorElement = formGroup.querySelector('.form-error');
        if (errorElement) {
            errorElement.remove();
        }
    }
    
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    isValidNumber(value) {
        return !isNaN(value) && !isNaN(parseFloat(value));
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new FormValidation();
});

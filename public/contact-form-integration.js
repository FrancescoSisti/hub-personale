/**
 * Contact Form API Integration Script
 * 
 * This script provides an easy way to integrate the contact form API
 * into external websites.
 * 
 * Usage:
 * 1. Include this script in your HTML page
 * 2. Create a form with the required fields
 * 3. Call ContactFormAPI.submit() or use the automatic form handling
 * 
 * @version 1.0
 * @author Hub Personale
 */

class ContactFormAPI {
    constructor(options = {}) {
        this.baseUrl = options.baseUrl || 'https://your-domain.com/api/v1';
        this.formSelector = options.formSelector || '.contact-form';
        this.submitButtonSelector = options.submitButtonSelector || 'button[type="submit"]';
        this.messageContainerSelector = options.messageContainerSelector || '.contact-message';
        this.autoInit = options.autoInit !== false;
        
        if (this.autoInit) {
            this.init();
        }
    }

    init() {
        // Auto-attach to forms when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.attachToForms());
        } else {
            this.attachToForms();
        }
    }

    attachToForms() {
        const forms = document.querySelectorAll(this.formSelector);
        forms.forEach(form => {
            form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        });
    }

    async handleFormSubmit(event) {
        event.preventDefault();
        
        const form = event.target;
        const submitButton = form.querySelector(this.submitButtonSelector);
        const messageContainer = form.querySelector(this.messageContainerSelector);
        
        // Disable submit button
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Invio in corso...';
        }

        try {
            const formData = this.extractFormData(form);
            const result = await this.submit(formData);
            
            if (result.success) {
                this.showMessage(messageContainer, result.message, 'success');
                form.reset();
            } else {
                this.showMessage(messageContainer, result.message, 'error');
                if (result.errors) {
                    this.showErrors(form, result.errors);
                }
            }
        } catch (error) {
            this.showMessage(messageContainer, 'Errore di connessione. Riprova più tardi.', 'error');
            console.error('Contact form error:', error);
        } finally {
            // Re-enable submit button
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = 'Invia Messaggio';
            }
        }
    }

    extractFormData(form) {
        const formData = new FormData(form);
        const data = {};
        
        // Extract standard fields
        const standardFields = ['name', 'email', 'subject', 'message', 'phone', 'company'];
        standardFields.forEach(field => {
            if (formData.has(field)) {
                data[field] = formData.get(field);
            }
        });

        // Extract extra data
        const extraData = {};
        for (const [key, value] of formData.entries()) {
            if (!standardFields.includes(key)) {
                extraData[key] = value;
            }
        }
        
        if (Object.keys(extraData).length > 0) {
            data.extra_data = extraData;
        }

        // Add origin
        data.origin = window.location.href;

        return data;
    }

    async submit(data) {
        const response = await fetch(`${this.baseUrl}/contacts`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    }

    async checkStatus(contactId) {
        const response = await fetch(`${this.baseUrl}/contacts/${contactId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    }

    async getAPIStatus() {
        const response = await fetch(`${this.baseUrl}/contacts`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    }

    showMessage(container, message, type = 'info') {
        if (!container) return;

        container.innerHTML = `
            <div class="contact-alert contact-alert-${type}">
                ${message}
            </div>
        `;

        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
        }
    }

    showErrors(form, errors) {
        // Clear previous errors
        const existingErrors = form.querySelectorAll('.field-error');
        existingErrors.forEach(error => error.remove());

        // Show new errors
        Object.keys(errors).forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'field-error';
                errorDiv.textContent = errors[fieldName][0]; // Get first error message
                field.parentNode.insertBefore(errorDiv, field.nextSibling);
            }
        });
    }
}

// Auto-initialize with default settings
window.ContactFormAPI = ContactFormAPI;
window.contactFormAPI = new ContactFormAPI();

// CSS Styles (auto-inject if not present)
if (!document.querySelector('#contact-form-api-styles')) {
    const styles = `
        .contact-alert {
            padding: 12px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .contact-alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .contact-alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .field-error {
            color: #721c24;
            font-size: 12px;
            margin-top: 4px;
        }
        
        .contact-form button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    `;
    
    const styleSheet = document.createElement('style');
    styleSheet.id = 'contact-form-api-styles';
    styleSheet.textContent = styles;
    document.head.appendChild(styleSheet);
} 
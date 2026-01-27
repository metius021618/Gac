/**
 * GAC - JavaScript para Registro Masivo de Correos
 */

(function() {
    'use strict';

    const bulkRegisterForm = document.getElementById('bulkRegisterForm');
    const emailsTextarea = document.getElementById('emails');
    const accessCodeInput = document.getElementById('access_code');
    const platformSelect = document.getElementById('platform_id');
    const submitBtn = bulkRegisterForm?.querySelector('.btn-bulk-assign');

    /**
     * Inicialización
     */
    function init() {
        if (bulkRegisterForm) {
            bulkRegisterForm.addEventListener('submit', handleSubmit);
            
            // Validación en tiempo real
            emailsTextarea?.addEventListener('blur', validateEmails);
            accessCodeInput?.addEventListener('blur', validateField);
            platformSelect?.addEventListener('change', validateField);
            
            // Limpiar errores al escribir
            emailsTextarea?.addEventListener('input', clearFieldError);
            accessCodeInput?.addEventListener('input', clearFieldError);
            platformSelect?.addEventListener('change', clearFieldError);
        }
    }

    /**
     * Manejar envío del formulario
     */
    async function handleSubmit(e) {
        e.preventDefault();

        if (!validateForm()) {
            await window.GAC.error('Por favor corrige los errores en el formulario.', 'Error de Validación');
            return;
        }

        setLoadingState(true);

        const formData = {
            emails: emailsTextarea.value.trim(),
            access_code: accessCodeInput.value.trim(),
            platform_id: parseInt(platformSelect.value)
        };

        try {
            const response = await fetch('/admin/email-accounts/bulk-register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (result.success) {
                let message = result.message;
                
                // Agregar detalles si hay correos inválidos
                if (result.invalid_emails && result.invalid_emails.length > 0) {
                    message += '\n\nCorreos rechazados:\n' + result.invalid_emails.join('\n');
                }
                
                await window.GAC.success(message, 'Registro Masivo Exitoso');
                
                // Limpiar formulario
                bulkRegisterForm.reset();
                clearAllErrors();
            } else {
                let errorMessage = result.message || 'Error al procesar el registro masivo';
                
                if (result.invalid_emails && result.invalid_emails.length > 0) {
                    errorMessage += '\n\nCorreos inválidos:\n' + result.invalid_emails.join('\n');
                }
                
                await window.GAC.error(errorMessage, 'Error');
            }
        } catch (error) {
            console.error('Error:', error);
            await window.GAC.error('Error de conexión. Por favor intenta nuevamente.', 'Error de Conexión');
        } finally {
            setLoadingState(false);
        }
    }

    /**
     * Validar formulario completo
     */
    function validateForm() {
        let isValid = true;
        
        if (!validateEmails({ target: emailsTextarea })) {
            isValid = false;
        }
        
        if (!validateField({ target: accessCodeInput })) {
            isValid = false;
        }
        
        if (!validateField({ target: platformSelect })) {
            isValid = false;
        }
        
        return isValid;
    }

    /**
     * Validar campo de correos
     */
    function validateEmails(e) {
        const textarea = e.target;
        const errorElement = document.getElementById('emailsError');
        const formGroup = textarea.closest('.form-group');
        let errorMessage = '';

        const emailsText = textarea.value.trim();
        
        if (!emailsText) {
            errorMessage = 'Este campo es requerido';
        } else {
            const emailsArray = emailsText.split('\n')
                .map(email => email.trim())
                .filter(email => email.length > 0);
            
            if (emailsArray.length === 0) {
                errorMessage = 'Debes ingresar al menos un correo electrónico';
            } else {
                // Validar formato y dominio
                const invalidEmails = [];
                emailsArray.forEach(email => {
                    if (!filter_var(email)) {
                        invalidEmails.push(email);
                    } else {
                        const domain = email.split('@')[1];
                        if (!domain || domain.toLowerCase() !== 'pocoyoni.com') {
                            invalidEmails.push(email);
                        }
                    }
                });
                
                if (invalidEmails.length > 0) {
                    errorMessage = `${invalidEmails.length} correo(s) inválido(s) o que no pertenecen al dominio pocoyoni.com`;
                }
            }
        }

        if (errorMessage) {
            showFieldError(errorElement, errorMessage);
            formGroup?.classList.add('has-error');
            return false;
        } else {
            clearFieldError({ target: textarea });
            formGroup?.classList.remove('has-error');
            return true;
        }
    }

    /**
     * Validar campo individual
     */
    function validateField(e) {
        const field = e.target;
        const errorElement = document.getElementById(field.id + 'Error');
        const formGroup = field.closest('.form-group');
        let errorMessage = '';

        if (field.hasAttribute('required') && !field.value.trim()) {
            errorMessage = 'Este campo es requerido';
        } else if (field.id === 'access_code' && field.value.trim().length < 3) {
            errorMessage = 'El código de acceso debe tener al menos 3 caracteres';
        } else if (field.id === 'platform_id' && parseInt(field.value) <= 0) {
            errorMessage = 'Selecciona una plataforma válida';
        }

        if (errorMessage) {
            showFieldError(errorElement, errorMessage);
            formGroup?.classList.add('has-error');
            return false;
        } else {
            clearFieldError({ target: field });
            formGroup?.classList.remove('has-error');
            return true;
        }
    }

    /**
     * Validar formato de email (función helper)
     */
    function filter_var(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    /**
     * Mostrar error de campo
     */
    function showFieldError(errorElement, message) {
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }

    /**
     * Limpiar error de campo
     */
    function clearFieldError(e) {
        const field = e.target;
        const errorElement = document.getElementById(field.id + 'Error');
        const formGroup = field.closest('.form-group');
        
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.style.display = 'none';
        }
        
        formGroup?.classList.remove('has-error');
    }

    /**
     * Limpiar todos los errores
     */
    function clearAllErrors() {
        bulkRegisterForm.querySelectorAll('.form-error').forEach(errorEl => {
            errorEl.textContent = '';
            errorEl.style.display = 'none';
        });
        
        bulkRegisterForm.querySelectorAll('.form-group').forEach(group => {
            group.classList.remove('has-error');
        });
    }

    /**
     * Establecer estado de carga
     */
    function setLoadingState(loading) {
        if (!submitBtn) return;
        
        if (loading) {
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
        } else {
            submitBtn.disabled = false;
            submitBtn.classList.remove('loading');
        }
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

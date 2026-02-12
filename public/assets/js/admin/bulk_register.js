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

        const btnAddStock = document.getElementById('btnAddStock');
        if (btnAddStock && emailsTextarea) {
            btnAddStock.addEventListener('click', handleAddStock);
        }
    }

    /**
     * Agregar correos como stock (solo en email_accounts, sin asignar plataforma)
     */
    async function handleAddStock() {
        const emails = emailsTextarea.value.trim();
        if (!emails) {
            await window.GAC.warning('Ingresa al menos un correo en el cuadro de texto.', 'Correos vacíos');
            return;
        }

        const btn = document.getElementById('btnAddStock');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Agregando...';
        }
        try {
            const response = await fetch('/admin/email-accounts/add-stock', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ emails: emails })
            });
            const result = await response.json();
            if (result.success) {
                await window.GAC.success(result.message || 'Correos agregados como stock.', 'Stock actualizado');
            } else {
                await window.GAC.error(result.message || 'Error al agregar como stock', 'Error');
            }
        } catch (err) {
            console.error(err);
            await window.GAC.error('Error de conexión', 'Error');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Agregar como stock';
            }
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
                const allowedDomains = ['pocoyoni.com', 'gmail.com', 'outlook.com', 'hotmail.com', 'hotmail.es', 'live.com', 'live.es'];
                const invalidEmails = [];
                emailsArray.forEach(email => {
                    if (!filter_var(email)) {
                        invalidEmails.push(email);
                    } else {
                        const domain = (email.split('@')[1] || '').toLowerCase().trim();
                        if (!domain || !allowedDomains.includes(domain)) {
                            invalidEmails.push(email);
                        }
                    }
                });
                if (invalidEmails.length > 0) {
                    errorMessage = `${invalidEmails.length} correo(s) inválido(s) o con dominio no permitido. Permitidos: Pocoyoni, Gmail, Outlook, Hotmail, Live.`;
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

    // ============================================
    // FORMULARIO DE ELIMINACIÓN MASIVA
    // ============================================
    const bulkDeleteForm = document.getElementById('bulkDeleteForm');
    const emailsDeleteTextarea = document.getElementById('emails_delete');
    const deleteBtn = bulkDeleteForm?.querySelector('.btn-bulk-delete');

    /**
     * Inicializar formulario de eliminación
     */
    function initDeleteForm() {
        if (bulkDeleteForm) {
            bulkDeleteForm.addEventListener('submit', handleDeleteSubmit);
            
            emailsDeleteTextarea?.addEventListener('blur', validateDeleteEmails);
            emailsDeleteTextarea?.addEventListener('input', function(e) {
                const errorElement = document.getElementById('emailsDeleteError');
                const formGroup = e.target.closest('.form-group');
                if (errorElement) {
                    errorElement.textContent = '';
                    errorElement.style.display = 'none';
                }
                formGroup?.classList.remove('has-error');
            });

            // Limpiar error de plataforma al cambiar
            const deletePlatformSelect = document.getElementById('delete_platform_id');
            deletePlatformSelect?.addEventListener('change', function() {
                const platError = document.getElementById('deletePlatformError');
                if (platError) {
                    platError.textContent = '';
                    platError.style.display = 'none';
                }
            });
        }
    }

    /**
     * Manejar envío del formulario de eliminación
     */
    async function handleDeleteSubmit(e) {
        e.preventDefault();

        const deletePlatformSelect = document.getElementById('delete_platform_id');
        const deletePlatformId = deletePlatformSelect ? parseInt(deletePlatformSelect.value) : 0;

        // Validar plataforma
        if (!deletePlatformId || deletePlatformId <= 0) {
            const platError = document.getElementById('deletePlatformError');
            if (platError) {
                platError.textContent = 'Selecciona una plataforma';
                platError.style.display = 'block';
            }
            await window.GAC.error('Debes seleccionar una plataforma para saber qué asignaciones eliminar.', 'Error de Validación');
            return;
        }

        if (!validateDeleteEmails({ target: emailsDeleteTextarea })) {
            await window.GAC.error('Por favor corrige los errores en el formulario.', 'Error de Validación');
            return;
        }

        const platformName = deletePlatformSelect.options[deletePlatformSelect.selectedIndex]?.text || '';
        const confirmResult = await window.GAC.confirm(
            `¿Estás seguro de que deseas eliminar las asignaciones de "${platformName}" para estos correos?\n\nEsta acción no se puede deshacer.`,
            'Confirmar Eliminación Masiva'
        );

        if (!confirmResult) {
            return;
        }

        setDeleteLoadingState(true);

        const formData = {
            emails: emailsDeleteTextarea.value.trim(),
            platform_id: deletePlatformId
        };

        try {
            const response = await fetch('/admin/email-accounts/bulk-delete', {
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
                
                if (result.not_found_emails && result.not_found_emails.length > 0) {
                    message += '\n\nCorreos sin esa plataforma:\n' + result.not_found_emails.join('\n');
                }
                
                await window.GAC.success(message, 'Eliminación Masiva Exitosa');
                
                bulkDeleteForm.reset();
                const errorElement = document.getElementById('emailsDeleteError');
                if (errorElement) {
                    errorElement.textContent = '';
                    errorElement.style.display = 'none';
                }
                const platError = document.getElementById('deletePlatformError');
                if (platError) {
                    platError.textContent = '';
                    platError.style.display = 'none';
                }
            } else {
                let errorMessage = result.message || 'Error al procesar la eliminación masiva';
                await window.GAC.error(errorMessage, 'Error');
            }
        } catch (error) {
            console.error('Error:', error);
            await window.GAC.error('Error de conexión. Por favor intenta nuevamente.', 'Error de Conexión');
        } finally {
            setDeleteLoadingState(false);
        }
    }

    /**
     * Validar campo de correos para eliminación
     */
    function validateDeleteEmails(e) {
        const textarea = e.target;
        const errorElement = document.getElementById('emailsDeleteError');
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
                const invalidEmails = [];
                emailsArray.forEach(email => {
                    if (!filter_var(email)) {
                        invalidEmails.push(email);
                    }
                });
                
                if (invalidEmails.length > 0) {
                    errorMessage = `${invalidEmails.length} correo(s) con formato inválido`;
                }
            }
        }

        if (errorMessage) {
            if (errorElement) {
                errorElement.textContent = errorMessage;
                errorElement.style.display = 'block';
            }
            formGroup?.classList.add('has-error');
            return false;
        } else {
            if (errorElement) {
                errorElement.textContent = '';
                errorElement.style.display = 'none';
            }
            formGroup?.classList.remove('has-error');
            return true;
        }
    }

    /**
     * Establecer estado de carga para eliminación
     */
    function setDeleteLoadingState(loading) {
        if (!deleteBtn) return;
        
        if (loading) {
            deleteBtn.disabled = true;
            deleteBtn.classList.add('loading');
        } else {
            deleteBtn.disabled = false;
            deleteBtn.classList.remove('loading');
        }
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            init();
            initDeleteForm();
        });
    } else {
        init();
        initDeleteForm();
    }
})();

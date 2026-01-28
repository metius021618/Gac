/**
 * GAC - JavaScript para Vista de Configuración
 */

(function() {
    'use strict';

    // Elementos del DOM
    const settingsForm = document.getElementById('settingsForm');
    const cancelBtn = document.getElementById('cancelBtn');
    const sessionTimeoutSelect = document.getElementById('session_timeout_hours');

    /**
     * Inicialización
     */
    function init() {
        if (settingsForm) {
            settingsForm.addEventListener('submit', handleFormSubmit);
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', handleCancel);
        }
    }

    /**
     * Manejar envío del formulario
     */
    async function handleFormSubmit(e) {
        e.preventDefault();

        if (!validateForm()) {
            await window.GAC.error('Por favor corrige los errores en el formulario.', 'Error de Validación');
            return;
        }

        setLoadingState(true);
        clearAllErrors();

        const formData = new FormData(settingsForm);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('/admin/settings/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                await window.GAC.success(
                    result.message || 'Configuración actualizada correctamente.',
                    'Configuración Guardada'
                );
                // Opcional: recargar la página después de un breve delay
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                await window.GAC.error(
                    result.message || 'Error al actualizar la configuración.',
                    'Error'
                );
            }
        } catch (error) {
            console.error('Error al actualizar configuración:', error);
            await window.GAC.error(
                'Ocurrió un error al actualizar la configuración. Por favor intenta nuevamente.',
                'Error de Conexión'
            );
        } finally {
            setLoadingState(false);
        }
    }

    /**
     * Manejar cancelar
     */
    function handleCancel() {
        // Resetear formulario al valor original
        if (sessionTimeoutSelect) {
            const originalValue = sessionTimeoutSelect.dataset.originalValue || sessionTimeoutSelect.options[0].value;
            sessionTimeoutSelect.value = originalValue;
        }
        clearAllErrors();
    }

    /**
     * Validar formulario
     */
    function validateForm() {
        let isValid = true;

        // Validar tiempo de sesión
        if (sessionTimeoutSelect) {
            const value = parseInt(sessionTimeoutSelect.value);
            const allowedValues = [1, 2, 3, 5, 7];
            
            if (!allowedValues.includes(value)) {
                showFieldError('sessionTimeoutHoursError', 'Selecciona un tiempo de sesión válido');
                isValid = false;
            } else {
                clearFieldError('sessionTimeoutHoursError');
            }
        }

        return isValid;
    }

    /**
     * Mostrar error de campo
     */
    function showFieldError(fieldId, message) {
        const errorElement = document.getElementById(fieldId);
        const formGroup = errorElement?.closest('.form-group');
        
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
        
        if (formGroup) {
            formGroup.classList.add('has-error');
        }
    }

    /**
     * Limpiar error de campo
     */
    function clearFieldError(fieldId) {
        const errorElement = document.getElementById(fieldId);
        const formGroup = errorElement?.closest('.form-group');
        
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.style.display = 'none';
        }
        
        if (formGroup) {
            formGroup.classList.remove('has-error');
        }
    }

    /**
     * Limpiar todos los errores
     */
    function clearAllErrors() {
        const errorElements = settingsForm?.querySelectorAll('.form-error');
        errorElements?.forEach(error => {
            error.textContent = '';
            error.style.display = 'none';
        });

        const formGroups = settingsForm?.querySelectorAll('.form-group');
        formGroups?.forEach(group => {
            group.classList.remove('has-error');
        });
    }

    /**
     * Establecer estado de carga
     */
    function setLoadingState(loading) {
        const submitBtn = settingsForm?.querySelector('button[type="submit"]');
        if (!submitBtn) return;
        
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoader = submitBtn.querySelector('.btn-loader');
        
        if (loading) {
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
            if (btnText) btnText.style.display = 'none';
            if (btnLoader) btnLoader.style.display = 'block';
        } else {
            submitBtn.disabled = false;
            submitBtn.classList.remove('loading');
            if (btnText) btnText.style.display = 'block';
            if (btnLoader) btnLoader.style.display = 'none';
        }
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

/**
 * GAC - JavaScript para Registro de Accesos
 */

(function() {
    'use strict';

    const form = document.getElementById('userAccessForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const platformSelect = document.getElementById('platform_id');
    const submitBtn = form?.querySelector('button[type="submit"]');

    if (!form) {
        console.error('Formulario de acceso no encontrado');
        return;
    }

    // Manejar envío del formulario
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Limpiar errores previos
        clearErrors();

        // Validar campos
        if (!validateForm()) {
            return;
        }

        // Deshabilitar botón
        setLoadingState(true);

        try {
            const formData = new FormData(form);
            
            const response = await fetch('/admin/user-access', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                await window.GAC.success(data.message || 'Acceso registrado correctamente', 'Éxito');
                form.reset();
            } else {
                await window.GAC.error(data.message || 'Error al registrar el acceso', 'Error');
            }
        } catch (error) {
            console.error('Error:', error);
            await window.GAC.error('Error de conexión. Por favor intenta nuevamente.', 'Error de Conexión');
        } finally {
            setLoadingState(false);
        }
    });

    /**
     * Validar formulario
     */
    function validateForm() {
        let isValid = true;

        // Validar email
        const email = emailInput.value.trim();
        if (!email) {
            showError('emailError', 'El correo es requerido');
            isValid = false;
        } else if (!window.GAC?.validateEmail(email)) {
            showError('emailError', 'El correo electrónico no es válido');
            isValid = false;
        }

        // Validar contraseña
        const password = passwordInput.value.trim();
        if (!password) {
            showError('passwordError', 'La contraseña es requerida');
            isValid = false;
        } else if (password.length < 3) {
            showError('passwordError', 'La contraseña debe tener al menos 3 caracteres');
            isValid = false;
        }

        // Validar plataforma
        const platformId = platformSelect.value;
        if (!platformId) {
            showError('platformError', 'Debe seleccionar una plataforma');
            isValid = false;
        }

        return isValid;
    }

    /**
     * Mostrar error
     */
    function showError(fieldId, message) {
        const errorElement = document.getElementById(fieldId);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.add('show');
        }
    }

    /**
     * Limpiar errores
     */
    function clearErrors() {
        const errorElements = form.querySelectorAll('.form-error');
        errorElements.forEach(el => {
            el.textContent = '';
            el.classList.remove('show');
        });
    }

    /**
     * Establecer estado de carga
     */
    function setLoadingState(loading) {
        if (submitBtn) {
            submitBtn.disabled = loading;
            if (loading) {
                submitBtn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 6v6l4 2"></path>
                    </svg>
                    Guardando...
                `;
            } else {
                submitBtn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Guardar
                `;
            }
        }
    }
})();

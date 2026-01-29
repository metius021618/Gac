/**
 * GAC - JavaScript de Login
 */

(function() {
    'use strict';

    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const submitBtn = document.getElementById('submitBtn');
    const btnLoader = document.getElementById('btnLoader');
    const btnText = submitBtn?.querySelector('.btn-text');
    
    const usernameError = document.getElementById('usernameError');
    const passwordError = document.getElementById('passwordError');

    /**
     * Inicialización
     */
    function init() {
        if (!loginForm) return;

        loginForm.addEventListener('submit', handleSubmit);
        usernameInput?.addEventListener('blur', validateUsername);
        usernameInput?.addEventListener('input', clearError.bind(null, usernameError));
        passwordInput?.addEventListener('blur', validatePassword);
        passwordInput?.addEventListener('input', clearError.bind(null, passwordError));
    }

    /**
     * Manejar envío del formulario
     */
    async function handleSubmit(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }

        const formData = {
            username: usernameInput.value.trim(),
            password: passwordInput.value,
            remember: document.getElementById('remember').checked,
            csrf_token: document.querySelector('input[name="csrf_token"]').value
        };

        setLoadingState(true);

        try {
            const response = await fetch('/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.success) {
                // Redirigir al dashboard o URL guardada
                const redirectUrl = data.redirect || '/admin/dashboard';
                window.location.href = redirectUrl;
            } else {
                await showError(data.message || 'Error al iniciar sesión');
            }
        } catch (error) {
            await showError('Error de conexión. Por favor intenta nuevamente.');
        } finally {
            setLoadingState(false);
        }
    }

    /**
     * Validar formulario
     */
    function validateForm() {
        let isValid = true;

        if (!validateUsername()) {
            isValid = false;
        }

        if (!validatePassword()) {
            isValid = false;
        }

        return isValid;
    }

    /**
     * Validar usuario
     */
    function validateUsername() {
        const username = usernameInput.value.trim();
        
        if (!username) {
            showFieldError(usernameError, 'El usuario es requerido');
            return false;
        }

        if (username.length < 3) {
            showFieldError(usernameError, 'El usuario debe tener al menos 3 caracteres');
            return false;
        }

        clearError(usernameError);
        return true;
    }

    /**
     * Validar contraseña
     */
    function validatePassword() {
        const password = passwordInput.value;
        
        if (!password) {
            showFieldError(passwordError, 'La contraseña es requerida');
            return false;
        }

        if (password.length < 6) {
            showFieldError(passwordError, 'La contraseña debe tener al menos 6 caracteres');
            return false;
        }

        clearError(passwordError);
        return true;
    }

    /**
     * Mostrar error en campo
     */
    function showFieldError(errorElement, message) {
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }

    /**
     * Limpiar error
     */
    function clearError(errorElement) {
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.style.display = 'none';
        }
    }

    /**
     * Establecer estado de carga
     */
    function setLoadingState(loading) {
        if (loading) {
            submitBtn.disabled = true;
            btnLoader?.classList.add('active');
            if (btnText) btnText.textContent = 'Iniciando sesión...';
            loginForm.classList.add('loading');
        } else {
            submitBtn.disabled = false;
            btnLoader?.classList.remove('active');
            if (btnText) btnText.textContent = 'Iniciar Sesión';
            loginForm.classList.remove('loading');
        }
    }

    /**
     * Mostrar error general
     */
    async function showError(message) {
        // Mostrar error en el primer campo disponible o usar modal
        if (usernameError) {
            usernameError.textContent = message;
            usernameError.style.display = 'block';
        } else {
            await window.GAC.error(message, 'Error');
        }
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

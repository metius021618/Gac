/**
 * GAC - JavaScript para Registro de Accesos
 * Permite guardar solo email (Stock) o email + usuario + plataforma.
 * Bloquea @gmail / @hotmail / @outlook en el input (deben usar los botones).
 */

(function() {
    'use strict';

    const form = document.getElementById('userAccessForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const platformSelect = document.getElementById('platform_id');
    const submitBtn = form?.querySelector('button[type="submit"]');
    const emailDomainWarning = document.getElementById('emailDomainWarning');

    const USE_BUTTONS_DOMAINS = ['gmail.com', 'hotmail.com', 'outlook.com', 'live.com'];

    function getEmailDomain(email) {
        const at = (email || '').trim().toLowerCase().lastIndexOf('@');
        return at >= 0 ? (email.trim().toLowerCase().slice(at + 1)) : '';
    }

    function isDomainRequiringButtons(email) {
        return USE_BUTTONS_DOMAINS.includes(getEmailDomain(email));
    }

    function updateDomainWarningAndSubmitState() {
        const email = emailInput.value.trim();
        const requireButtons = email && isDomainRequiringButtons(email);
        if (emailDomainWarning) {
            emailDomainWarning.style.display = requireButtons ? 'block' : 'none';
        }
        if (submitBtn) {
            submitBtn.disabled = !!requireButtons;
            submitBtn.classList.toggle('btn-disabled-domain', !!requireButtons);
        }
    }

    if (!form) {
        console.error('Formulario de acceso no encontrado');
        return;
    }

    if (emailInput) {
        emailInput.addEventListener('input', updateDomainWarningAndSubmitState);
        emailInput.addEventListener('change', updateDomainWarningAndSubmitState);
    }
    updateDomainWarningAndSubmitState();

    // Manejar envío del formulario
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        clearErrors();

        if (!validateForm()) {
            return;
        }

        const email = emailInput.value.trim();
        const password = passwordInput.value.trim();
        const platformId = platformSelect.value || '';

        const isStock = !password || !platformId;
        if (isStock && window.GAC && window.GAC.confirm) {
            const missing = [];
            if (!password) missing.push('usuario (contraseña)');
            if (!platformId) missing.push('plataforma');
            const msg = 'Este correo se guardará como Stock ya que no tiene asignado ' + (missing.join(' ni ')) + '. Estará disponible en la sección que le corresponda (Gmail, Outlook o Pocoyoni). ¿Continuar?';
            try {
                const ok = await window.GAC.confirm(msg, 'Guardar como Stock');
                if (!ok) return;
            } catch (err) {
                return;
            }
        }

        setLoadingState(true);

        try {
            const formData = new FormData(form);
            const response = await fetch('/admin/user-access', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
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

    function validateForm() {
        let isValid = true;
        const email = emailInput.value.trim();

        if (!email) {
            showError('emailError', 'El correo es requerido');
            isValid = false;
        } else if (!window.GAC?.validateEmail?.(email)) {
            showError('emailError', 'El correo electrónico no es válido');
            isValid = false;
        }

        const password = passwordInput.value.trim();
        const platformId = platformSelect.value || '';
        if (password && password.length < 3) {
            showError('passwordError', 'La contraseña debe tener al menos 3 caracteres');
            isValid = false;
        }
        if (password && !platformId) {
            showError('platformError', 'Si indica contraseña, debe seleccionar una plataforma');
            isValid = false;
        }
        if (platformId && !password) {
            showError('passwordError', 'Si selecciona plataforma, debe indicar la contraseña');
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

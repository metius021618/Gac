/**
 * GAC - JavaScript de Consulta de Códigos
 * Manejo del formulario de consulta y API
 */

(function() {
    'use strict';

    // Elementos del DOM
    const consultForm = document.getElementById('consultForm');
    const emailInput = document.getElementById('email');
    const usernameInput = document.getElementById('username');
    const platformSelect = document.getElementById('platform');
    const submitBtn = document.getElementById('submitBtn');
    const btnLoader = document.getElementById('btnLoader');
    const btnText = submitBtn?.querySelector('.btn-text');
    
    const emailError = document.getElementById('emailError');
    const usernameError = document.getElementById('usernameError');
    const platformError = document.getElementById('platformError');
    
    const resultSection = document.getElementById('resultSection');
    const resultCard = document.getElementById('resultCard');
    const resultIcon = document.getElementById('resultIcon');
    const resultTitle = document.getElementById('resultTitle');
    const resultMessage = document.getElementById('resultMessage');
    const resultCode = document.getElementById('resultCode');
    const copyBtn = document.getElementById('copyBtn');

    // API Endpoint
    const API_ENDPOINT = '/api/v1/codes/consult';

    /**
     * Inicialización
     */
    function init() {
        if (!consultForm) return;

        // Event listeners
        consultForm.addEventListener('submit', handleSubmit);
        emailInput?.addEventListener('blur', validateEmail);
        emailInput?.addEventListener('input', clearError.bind(null, emailError));
        usernameInput?.addEventListener('blur', validateUsername);
        usernameInput?.addEventListener('input', clearError.bind(null, usernameError));
        platformSelect?.addEventListener('change', clearError.bind(null, platformError));
        
        if (copyBtn) {
            copyBtn.addEventListener('click', handleCopy);
        }
    }

    /**
     * Manejar envío del formulario
     */
    async function handleSubmit(e) {
        e.preventDefault();
        
        // Validar formulario
        if (!validateForm()) {
            return;
        }

        // Obtener datos
        const formData = {
            email: emailInput.value.trim(),
            username: usernameInput.value.trim(),
            platform: platformSelect.value
        };

        // Mostrar estado de carga
        setLoadingState(true);

        try {
            // Llamar a la API
            const response = await fetch(API_ENDPOINT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(formData)
            });

            // Verificar si la respuesta es JSON
            const contentType = response.headers.get('content-type');
            let data;
            
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                // Si no es JSON, intentar leer como texto
                const text = await response.text();
                console.error('Respuesta no JSON:', text);
                showError('Error: El servidor no devolvió una respuesta válida');
                setLoadingState(false);
                return;
            }

            // Si es un 404, mostrar información de debug
            if (response.status === 404 && data.debug) {
                console.error('404 Debug Info:', data.debug);
                showError(`Error 404: ${data.message || 'Endpoint no encontrado'}. Revisa la consola para más detalles.`);
            } else if (data.success) {
                showSuccess(data);
            } else {
                showError(data.message || 'Error al consultar el código');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Error de conexión. Por favor intenta nuevamente.');
        } finally {
            setLoadingState(false);
        }
    }

    /**
     * Validar formulario completo
     */
    function validateForm() {
        let isValid = true;

        // Validar email
        if (!validateEmail()) {
            isValid = false;
        }

        // Validar usuario
        if (!validateUsername()) {
            isValid = false;
        }

        // Validar plataforma
        if (!validatePlatform()) {
            isValid = false;
        }

        return isValid;
    }

    /**
     * Validar email
     */
    function validateEmail() {
        const email = emailInput.value.trim();
        
        if (!email) {
            showFieldError(emailError, 'El correo electrónico es requerido');
            return false;
        }

        if (!window.GAC?.validateEmail(email)) {
            showFieldError(emailError, 'El correo electrónico no es válido');
            return false;
        }

        clearError(emailError);
        return true;
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
     * Validar plataforma
     */
    function validatePlatform() {
        const platform = platformSelect.value;
        
        if (!platform) {
            showFieldError(platformError, 'Debes seleccionar una plataforma');
            return false;
        }

        clearError(platformError);
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
     * Limpiar error de campo
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
            if (btnText) btnText.textContent = 'Consultando...';
            consultForm.classList.add('loading');
        } else {
            submitBtn.disabled = false;
            btnLoader?.classList.remove('active');
            if (btnText) btnText.textContent = 'Consultar Código';
            consultForm.classList.remove('loading');
        }
    }

    /**
     * Mostrar resultado exitoso
     */
    function showSuccess(data) {
        // Ocultar formulario (opcional)
        // consultForm.style.display = 'none';

        // Configurar resultado
        resultCard.className = 'result-card success';
        resultIcon.innerHTML = '✓';
        resultTitle.textContent = '¡Código encontrado!';
        
        // Construir mensaje principal
        let messageHTML = `<div>Tu código para ${getPlatformName(data.platform)} está listo:</div>`;
        
        // Si hay advertencia (código no reciente), agregarla
        if (data.warning) {
            messageHTML += `
                <div style="margin-top: 12px; padding: 10px; background: rgba(255, 193, 7, 0.1); border-left: 3px solid #ffc107; border-radius: 4px; font-size: 0.9em; color: #856404;">
                    <div style="display: flex; align-items: flex-start; gap: 8px;">
                        <span style="font-size: 1.2em;">ℹ️</span>
                        <span>${data.warning}</span>
                    </div>
                </div>
            `;
        }
        
        resultMessage.innerHTML = messageHTML;
        
        if (data.code) {
            resultCode.textContent = data.code;
            resultCode.classList.remove('hidden');
            copyBtn.classList.remove('hidden');
            copyBtn.dataset.code = data.code;
        } else {
            resultCode.classList.add('hidden');
            copyBtn.classList.add('hidden');
        }

        // Mostrar sección de resultado
        resultSection.classList.remove('hidden');
        
        // Scroll suave al resultado
        resultSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /**
     * Mostrar error
     */
    function showError(message) {
        // Configurar resultado de error
        resultCard.className = 'result-card error';
        resultIcon.innerHTML = '✕';
        resultTitle.textContent = 'Error';
        resultMessage.textContent = message;
        resultCode.classList.add('hidden');
        copyBtn.classList.add('hidden');

        // Mostrar sección de resultado
        resultSection.classList.remove('hidden');
        
        // Scroll suave al resultado
        resultSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /**
     * Manejar copia de código
     */
    async function handleCopy() {
        const code = copyBtn.dataset.code;
        
        if (!code) return;

        try {
            const success = await window.GAC?.copyToClipboard(code);
            
            if (success) {
                // Feedback visual
                const originalText = copyBtn.innerHTML;
                copyBtn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    ¡Copiado!
                `;
                copyBtn.style.color = '#28a745';
                
                setTimeout(() => {
                    copyBtn.innerHTML = originalText;
                    copyBtn.style.color = '';
                }, 2000);
            } else {
                await window.GAC.warning('No se pudo copiar el código. Por favor cópialo manualmente.', 'Advertencia');
            }
        } catch (error) {
            console.error('Error al copiar:', error);
            await window.GAC.warning('Error al copiar el código. Por favor cópialo manualmente.', 'Advertencia');
        }
    }

    /**
     * Obtener nombre de plataforma
     */
    function getPlatformName(key) {
        const platforms = {
            'netflix': 'Netflix',
            'disney': 'Disney+',
            'prime': 'Amazon Prime Video',
            'spotify': 'Spotify',
            'crunchyroll': 'Crunchyroll',
            'paramount': 'Paramount+',
            'chatgpt': 'ChatGPT',
            'canva': 'Canva'
        };
        return platforms[key] || key;
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

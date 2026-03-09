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
    
    // Ya no se usan estos elementos - solo se muestra el modal

    // API Endpoint: consult solo consulta la BD (instantáneo). Los lectores deben correr cada 30 s con cron/sync_loop.py
    const API_ENDPOINT = '/api/v1/codes/consult';
    const FETCH_TIMEOUT_MS = 25000;  // 25 s: evita esperar eternamente si el servidor tarda (503/timeout)
    const RETRY_ON_503_DELAY_MS = 4000;  // Reintentar una vez tras 4 s si el servidor devuelve 503/502/504

    /**
     * Inicialización
     */
    function init() {
        if (!consultForm) return;

        // No disparar sync desde la web: el cron (run_readers_loop_30s.sh) ya actualiza cada 30 s.
        // Llamar sync-emails aquí lentaba todo el sitio en hosting compartido.

        // Event listeners
        consultForm.addEventListener('submit', handleSubmit);
        emailInput?.addEventListener('blur', validateEmail);
        emailInput?.addEventListener('input', clearError.bind(null, emailError));
        usernameInput?.addEventListener('blur', validateUsername);
        usernameInput?.addEventListener('input', clearError.bind(null, usernameError));
        platformSelect?.addEventListener('change', clearError.bind(null, platformError));
        
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

        var lastError = null;
        var retried = false;

        function doRequest() {
            var ctrl = new AbortController();
            var timeoutId = setTimeout(function() { ctrl.abort(); }, FETCH_TIMEOUT_MS);
            return fetch(API_ENDPOINT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                },
                body: JSON.stringify(formData),
                cache: 'no-store',
                signal: ctrl.signal
            }).finally(function() { clearTimeout(timeoutId); });
        }

        try {
            var response = await doRequest();

            // 503/502/504: servidor sobrecargado o timeout; reintentar una vez
            if (!retried && (response.status === 503 || response.status === 502 || response.status === 504)) {
                retried = true;
                if (btnText) btnText.textContent = 'Reintentando...';
                await new Promise(function(r) { setTimeout(r, RETRY_ON_503_DELAY_MS); });
                response = await doRequest();
            }

            // Verificar si la respuesta es JSON
            var contentType = response.headers.get('content-type');
            var data;
            
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                var text = await response.text();
                console.error('Respuesta no JSON:', text);
                if (response.status === 503 || response.status === 502 || response.status === 504) {
                    showError('El servidor está temporalmente ocupado (error ' + response.status + '). Por favor intenta de nuevo en unos segundos.');
                } else {
                    showError('Error: El servidor no devolvió una respuesta válida');
                }
                setLoadingState(false);
                return;
            }

            // Debug: Log de la respuesta
            console.log('Respuesta del servidor:', data);
            console.log('Status:', response.status);

            // Servidor sobrecargado o indisponible (incluso si el body es JSON)
            if (response.status === 503 || response.status === 502 || response.status === 504) {
                showError('El servidor está temporalmente ocupado (error ' + response.status + '). Por favor intenta de nuevo en unos segundos.');
                setLoadingState(false);
                return;
            }
            
            // Si es un 404, mostrar información de debug
            if (response.status === 404 && data.debug) {
                console.error('404 Debug Info:', data.debug);
                showError(`Error 404: ${data.message || 'Endpoint no encontrado'}. Revisa la consola para más detalles.`, data);
            } else if (data.success) {
                console.log('Respuesta exitosa, abriendo modal...');
                console.log('Datos completos:', data);
                console.log('Email body existe:', !!data.email_body);
                console.log('Email body length:', data.email_body ? data.email_body.length : 0);
                console.log('Email subject:', data.email_subject);
                
                // Abrir automáticamente el modal con el email completo
                // Si no hay email_body, mostrar un mensaje indicando que el contenido no está disponible
                const emailBody = data.email_body || '<p style="color: #ffc107; padding: 20px; text-align: center;">El contenido del email no está disponible en este momento. Por favor intenta más tarde.</p>';
                
                console.log('Abriendo modal con datos:', {
                    email_from: data.email_from,
                    email_subject: data.email_subject,
                    email_body_length: emailBody.length
                });
                
                showEmailModal({
                    ...data,
                    email_body: emailBody
                });
            } else {
                console.error('Respuesta no exitosa:', data);
                showError(data.message || 'No se encontraron correos para esta plataforma', data);
            }
        } catch (error) {
            console.error('Error:', error);
            if (error && error.name === 'AbortError') {
                showError('La consulta tardó demasiado. Por favor intenta de nuevo.');
            } else {
                showError('Error de conexión. Por favor intenta nuevamente.', null);
            }
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
     * Mostrar modal con el email completo
     */
    function showEmailModal(data) {
        console.log('showEmailModal llamado con:', data);
        
        const modal = document.getElementById('emailModal');
        const modalContent = document.getElementById('emailModalContent');
        const modalSubject = document.getElementById('emailModalSubject');
        const modalFrom = document.getElementById('emailModalFrom');
        const modalDate = document.getElementById('emailModalDate');
        const modalBody = document.getElementById('emailModalBody');
        const closeModal = document.getElementById('closeEmailModal');
        
        console.log('Elementos del modal:', {
            modal: !!modal,
            modalContent: !!modalContent,
            modalSubject: !!modalSubject,
            modalFrom: !!modalFrom,
            modalDate: !!modalDate,
            modalBody: !!modalBody,
            closeModal: !!closeModal
        });
        
        if (!modal) {
            console.error('Modal no encontrado en el DOM');
            alert('Error: No se pudo abrir el modal. Por favor recarga la página.');
            return;
        }
        
        if (!modalContent) {
            console.error('ModalContent no encontrado en el DOM');
            return;
        }
        
        // Llenar información del email
        if (modalSubject) modalSubject.textContent = data.email_subject || 'Sin asunto';
        if (modalFrom) modalFrom.textContent = data.email_from || 'Desconocido';
        if (modalDate) {
            // received_at se guarda en UTC; interpretar como UTC y mostrar en zona horaria de Perú (GMT-5)
            const raw = (data.received_at || '').trim();
            const utcStr = raw.includes('Z') || raw.includes('+') ? raw : raw ? raw.replace(' ', 'T') + 'Z' : '';
            const date = utcStr ? new Date(utcStr) : new Date();
            modalDate.textContent = date.toLocaleString('es-ES', {
                timeZone: 'America/Lima',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        // Mostrar cuerpo del email (HTML o texto)
        if (modalBody && data.email_body) {
            // Detectar si es HTML
            const isHTML = data.email_body.trim().startsWith('<');
            if (isHTML) {
                modalBody.innerHTML = data.email_body;
            } else {
                // Convertir saltos de línea a <br>
                modalBody.innerHTML = data.email_body.replace(/\n/g, '<br>');
            }
        }
        
        // Mostrar modal
        console.log('Removiendo clase hidden del modal');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        console.log('Modal visible:', !modal.classList.contains('hidden'));
        console.log('Modal display style:', window.getComputedStyle(modal).display);
        
        // Cerrar modal al hacer clic fuera
        modal.onclick = (e) => {
            if (e.target === modal) {
                closeEmailModal();
            }
        };
        
        // Cerrar con botón
        if (closeModal) {
            closeModal.onclick = closeEmailModal;
        }
        
        // Cerrar con ESC
        const escHandler = (e) => {
            if (e.key === 'Escape') {
                closeEmailModal();
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
    }
    
    /**
     * Cerrar modal de email
     */
    function closeEmailModal() {
        const modal = document.getElementById('emailModal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    /**
     * Mostrar error
     */
    function showError(message, data = null) {
        // Mostrar error simple - el cliente solo quiere ver el email, no códigos
        if (window.GAC && window.GAC.error) {
            window.GAC.error(message, 'Error');
        } else {
            alert(message);
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

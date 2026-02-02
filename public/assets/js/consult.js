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

    // API Endpoint: consult-with-sync ejecuta el lector de correos antes de consultar (~30-60 s)
    const API_ENDPOINT = '/api/v1/codes/consult-with-sync';

    /**
     * Inicialización
     */
    function init() {
        if (!consultForm) return;

        // Al cargar la vista de consulta, disparar el lector de correos en segundo plano (throttle en servidor)
        fetch('/api/v1/sync-emails', { method: 'GET', credentials: 'same-origin' }).catch(function() {});

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

            // Debug: Log de la respuesta
            console.log('Respuesta del servidor:', data);
            console.log('Status:', response.status);
            
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
            showError('Error de conexión. Por favor intenta nuevamente.', null);
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
            if (btnText) btnText.textContent = 'Sincronizando correos...';
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
            const date = new Date(data.received_at);
            modalDate.textContent = date.toLocaleString('es-ES', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        const recipientRow = document.getElementById('emailModalRecipientRow');
        const recipientValue = document.getElementById('emailModalRecipient');
        if (data.is_master_view && recipientRow && recipientValue) {
            recipientValue.textContent = data.recipient_email || '-';
            recipientRow.classList.remove('hidden');
        } else if (recipientRow) {
            recipientRow.classList.add('hidden');
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

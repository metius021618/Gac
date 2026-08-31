/**
 * GAC - Consulta Hogar / Actualizar hogar (un solo formulario, endpoint según tab activo)
 */
(function () {
    'use strict';

    var consultForm = document.getElementById('consultForm');
    var emailInput = document.getElementById('email');
    var submitBtn = document.getElementById('submitBtn');
    var btnLoader = document.getElementById('btnLoader');
    var btnText = submitBtn && submitBtn.querySelector('.btn-text');
    var emailError = document.getElementById('emailError');

    var FETCH_TIMEOUT_MS = 25000;
    var BTN_LABEL = 'Consultar';

    function getEndpoint() {
        var mode = window.HogarModeSwitch ? window.HogarModeSwitch.getActiveMode() : 'hogar';
        return mode === 'viaje' ? '/MViaje' : '/hogar';
    }

    function init() {
        if (!consultForm) return;

        consultForm.addEventListener('submit', handleSubmit);
        if (emailInput) {
            emailInput.addEventListener('blur', validateEmail);
            emailInput.addEventListener('input', function () { clearError(emailError); });
        }
    }

    async function handleSubmit(e) {
        e.preventDefault();
        if (!validateEmail()) return;

        var email = emailInput.value.trim();
        setLoadingState(true);

        try {
            var ctrl = new AbortController();
            var timeoutId = setTimeout(function () { ctrl.abort(); }, FETCH_TIMEOUT_MS);

            var response = await fetch(getEndpoint(), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                },
                body: JSON.stringify({ email: email }),
                cache: 'no-store',
                signal: ctrl.signal
            }).finally(function () { clearTimeout(timeoutId); });

            var contentType = response.headers.get('content-type');
            var data;
            if (contentType && contentType.indexOf('application/json') !== -1) {
                data = await response.json();
            } else {
                console.error('Respuesta no JSON:', await response.text());
                showError('Error: El servidor no devolvió una respuesta válida');
                setLoadingState(false);
                return;
            }

            if (data.success) {
                var emailBody = data.email_body || '<p style="color: #ffc107; padding: 20px; text-align: center;">El contenido del email no está disponible en este momento. Por favor intenta más tarde.</p>';
                showEmailModal(Object.assign({}, data, { email_body: emailBody }));
            } else {
                showError(data.message || 'No se encontraron correos para este correo.');
            }
        } catch (error) {
            console.error('Error:', error);
            if (error && error.name === 'AbortError') {
                showError('La consulta tardó demasiado. Por favor intenta de nuevo.');
            } else {
                showError('Error de conexión. Por favor intenta nuevamente.');
            }
        } finally {
            setLoadingState(false);
        }
    }

    function validateEmail() {
        var email = emailInput.value.trim();
        if (!email) {
            showFieldError(emailError, 'El correo electrónico es requerido');
            return false;
        }
        if (!window.GAC || !window.GAC.validateEmail(email)) {
            showFieldError(emailError, 'El correo electrónico no es válido');
            return false;
        }
        clearError(emailError);
        return true;
    }

    function showFieldError(el, msg) {
        if (el) {
            el.textContent = msg;
            el.style.display = 'block';
        }
    }

    function clearError(el) {
        if (el) {
            el.textContent = '';
            el.style.display = 'none';
        }
    }

    function setLoadingState(loading) {
        if (!submitBtn) return;
        if (loading) {
            submitBtn.disabled = true;
            if (btnLoader) btnLoader.classList.add('active');
            if (btnText) btnText.textContent = 'Consultando...';
            consultForm.classList.add('loading');
        } else {
            submitBtn.disabled = false;
            if (btnLoader) btnLoader.classList.remove('active');
            if (btnText) btnText.textContent = BTN_LABEL;
            consultForm.classList.remove('loading');
        }
    }

    function showEmailModal(data) {
        var modal = document.getElementById('emailModal');
        var modalSubject = document.getElementById('emailModalSubject');
        var modalFrom = document.getElementById('emailModalFrom');
        var modalDate = document.getElementById('emailModalDate');
        var modalBody = document.getElementById('emailModalBody');
        var closeModal = document.getElementById('closeEmailModal');

        if (!modal) return;

        if (modalSubject) modalSubject.textContent = data.email_subject || 'Sin asunto';
        if (modalFrom) modalFrom.textContent = data.email_from || 'Desconocido';
        if (modalDate) {
            if (data.received_at_display) {
                modalDate.textContent = data.received_at_display;
            } else {
                var raw = (data.received_at || '').trim();
                var utcStr = raw.indexOf('Z') !== -1 || raw.indexOf('+') !== -1 ? raw : raw ? raw.replace(' ', 'T') + 'Z' : '';
                var date = utcStr ? new Date(utcStr) : new Date();
                modalDate.textContent = date.toLocaleString('es-ES', {
                    timeZone: 'America/Lima',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        }
        if (modalBody && data.email_body) {
            var isHTML = data.email_body.trim().charAt(0) === '<';
            modalBody.innerHTML = isHTML ? data.email_body : data.email_body.replace(/\n/g, '<br>');
        }

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        modal.onclick = function (e) {
            if (e.target === modal) closeEmailModal();
        };
        if (closeModal) closeModal.onclick = closeEmailModal;
        var escHandler = function (e) {
            if (e.key === 'Escape') {
                closeEmailModal();
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
    }

    function closeEmailModal() {
        var modal = document.getElementById('emailModal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    function showError(message) {
        if (window.GAC && window.GAC.error) {
            window.GAC.error(message, 'Error');
        } else {
            alert(message);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

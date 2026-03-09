/**
 * GAC - JavaScript Vista Hogar (Consulta código temporal Netflix)
 * Solo busca correos con asunto "Tu código de acceso temporal de Netflix"
 */

(function() {
    'use strict';

    const hogarForm = document.getElementById('hogarForm');
    const emailInput = document.getElementById('email');
    const submitBtn = document.getElementById('submitBtn');
    const btnLoader = document.getElementById('btnLoader');
    const btnText = submitBtn?.querySelector('.btn-text');
    const emailError = document.getElementById('emailError');

    const API_ENDPOINT = '/hogar';
    const FETCH_TIMEOUT_MS = 25000;

    function init() {
        if (!hogarForm) return;

        hogarForm.addEventListener('submit', handleSubmit);
        emailInput?.addEventListener('blur', validateEmail);
        emailInput?.addEventListener('input', () => clearError(emailError));
    }

    async function handleSubmit(e) {
        e.preventDefault();
        if (!validateEmail()) return;

        const email = emailInput.value.trim();
        setLoadingState(true);

        try {
            const ctrl = new AbortController();
            const timeoutId = setTimeout(() => ctrl.abort(), FETCH_TIMEOUT_MS);

            const response = await fetch(API_ENDPOINT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                },
                body: JSON.stringify({ email }),
                cache: 'no-store',
                signal: ctrl.signal
            }).finally(() => clearTimeout(timeoutId));

            const contentType = response.headers.get('content-type');
            var data;
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                var text = await response.text();
                console.error('Respuesta no JSON:', text);
                showError('Error: El servidor no devolvió una respuesta válida');
                setLoadingState(false);
                return;
            }

            if (data.success) {
                const emailBody = data.email_body || '<p style="color: #ffc107; padding: 20px; text-align: center;">El contenido del email no está disponible en este momento. Por favor intenta más tarde.</p>';
                showEmailModal({ ...data, email_body: emailBody });
            } else {
                showError(data.message || 'No se encontraron correos con el código temporal de Netflix.');
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
        if (loading) {
            submitBtn.disabled = true;
            btnLoader?.classList.add('active');
            if (btnText) btnText.textContent = 'Consultando...';
            hogarForm.classList.add('loading');
        } else {
            submitBtn.disabled = false;
            btnLoader?.classList.remove('active');
            if (btnText) btnText.textContent = 'Código temporal';
            hogarForm.classList.remove('loading');
        }
    }

    function showEmailModal(data) {
        const modal = document.getElementById('emailModal');
        const modalSubject = document.getElementById('emailModalSubject');
        const modalFrom = document.getElementById('emailModalFrom');
        const modalDate = document.getElementById('emailModalDate');
        const modalBody = document.getElementById('emailModalBody');
        const closeModal = document.getElementById('closeEmailModal');

        if (!modal) return;

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
        if (modalBody && data.email_body) {
            const isHTML = data.email_body.trim().startsWith('<');
            modalBody.innerHTML = isHTML ? data.email_body : data.email_body.replace(/\n/g, '<br>');
        }

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        modal.onclick = (e) => {
            if (e.target === modal) closeEmailModal();
        };
        if (closeModal) closeModal.onclick = closeEmailModal;
        const escHandler = (e) => {
            if (e.key === 'Escape') {
                closeEmailModal();
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
    }

    function closeEmailModal() {
        const modal = document.getElementById('emailModal');
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

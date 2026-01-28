/**
 * GAC - JavaScript para Gestión de Asuntos de Email
 * Con búsqueda AJAX en tiempo real y paginación
 */

(function() {
    'use strict';

    // Elementos del DOM
    const emailSubjectForm = document.getElementById('emailSubjectForm');
    const emailSubjectsTable = document.getElementById('emailSubjectsTable');
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearch');
    const perPageSelect = document.getElementById('perPageSelect');

    /**
     * Inicialización
     */
    function init() {
        if (emailSubjectForm) {
            initForm();
        }
        
        if (emailSubjectsTable) {
            initTable();
            initSearch();
        }
    }

    /**
     * Inicializar formulario
     */
    function initForm() {
        emailSubjectForm.addEventListener('submit', handleFormSubmit);
        
        // Validación en tiempo real
        const inputs = emailSubjectForm.querySelectorAll('input[required], select[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', validateField);
            input.addEventListener('input', clearFieldError);
        });
    }

    /**
     * Inicializar tabla
     */
    function initTable() {
        // Botones de eliminar
        const deleteButtons = emailSubjectsTable?.querySelectorAll('.btn-delete');
        deleteButtons?.forEach(btn => {
            btn.removeEventListener('click', handleDelete);
            btn.addEventListener('click', handleDelete);
        });
    }

    /**
     * Inicializar búsqueda AJAX
     */
    function initSearch() {
        if (!searchInput || !perPageSelect) return;

        // Inicializar búsqueda AJAX usando la utilidad común
        if (window.SearchAJAX) {
            window.SearchAJAX.init({
                searchInput: searchInput,
                perPageSelect: perPageSelect,
                clearSearchBtn: clearSearchBtn,
                endpoint: window.location.pathname,
                renderCallback: function(html) {
                    window.SearchAJAX.updateTableContent(html);
                    initTable(); // Re-inicializar eventos de la tabla después de la actualización
                },
                onSearchComplete: function() {
                    // Mostrar/ocultar botón de limpiar
                    if (clearSearchBtn && searchInput.value.trim()) {
                        clearSearchBtn.style.display = 'flex';
                    } else if (clearSearchBtn) {
                        clearSearchBtn.style.display = 'none';
                    }
                }
            });
        } else {
            console.error('SearchAJAX no está disponible');
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

        const formData = new FormData(emailSubjectForm);
        const data = Object.fromEntries(formData);
        const isEdit = !!data.id;
        const url = isEdit ? '/admin/email-subjects/update' : '/admin/email-subjects';

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                await window.GAC.success(result.message || (isEdit ? 'Asunto actualizado correctamente' : 'Asunto agregado correctamente'), 'Éxito');
                window.location.href = '/admin/email-subjects';
            } else {
                await window.GAC.error(result.message || 'Error al guardar el asunto', 'Error');
            }
        } catch (error) {
            console.error('Error:', error);
            await window.GAC.error('Error de conexión. Por favor intenta nuevamente.', 'Error de Conexión');
        } finally {
            setLoadingState(false);
        }
    }

    /**
     * Validar formulario
     */
    function validateForm() {
        let isValid = true;
        const requiredFields = emailSubjectForm.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!validateField({ target: field })) {
                isValid = false;
            }
        });

        return isValid;
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
        } else if (field.id === 'subject_line' && field.value.trim().length < 3) {
            errorMessage = 'El asunto debe tener al menos 3 caracteres';
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
     * Establecer estado de carga
     */
    function setLoadingState(loading) {
        const submitBtn = emailSubjectForm?.querySelector('button[type="submit"]');
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

    /**
     * Manejar eliminación de asunto
     */
    async function handleDelete(e) {
        const btn = e.currentTarget;
        const id = btn.dataset.id;
        const subject = btn.dataset.subject || 'este asunto';

        try {
            const confirmed = await window.GAC.confirm(
                `¿Estás seguro de eliminar el asunto "${subject}"? Esta acción no se puede deshacer.`,
                'Eliminar Asunto'
            );
            if (!confirmed) {
                return;
            }
        } catch (error) {
            console.error('Error al mostrar modal de confirmación:', error);
            return;
        }

        try {
            const response = await fetch('/admin/email-subjects/delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ id: parseInt(id) })
            });

            const result = await response.json();

            if (result.success) {
                await window.GAC.success(result.message || 'Asunto eliminado correctamente', 'Éxito');
                location.reload();
            } else {
                await window.GAC.error(result.message || 'Error al eliminar el asunto', 'Error');
            }
        } catch (error) {
            console.error('Error:', error);
            await window.GAC.error('Error de conexión', 'Error');
        }
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

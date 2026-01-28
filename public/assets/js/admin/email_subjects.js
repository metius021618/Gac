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
    const btnNewSubject = document.getElementById('btnNewSubject');
    const subjectModal = document.getElementById('subjectModal');
    const closeSubjectModal = document.getElementById('closeSubjectModal');
    const cancelSubjectBtn = document.getElementById('cancelSubjectBtn');
    const modalTitle = document.getElementById('modalTitle');
    const subjectIdInput = document.getElementById('subjectId');
    const modalPlatformSelect = document.getElementById('modal_platform_id');
    const modalSubjectLineInput = document.getElementById('modal_subject_line');

    /**
     * Inicialización
     */
    function init() {
        if (btnNewSubject && subjectModal) {
            initModal();
        }
        
        if (emailSubjectForm) {
            initForm();
        }
        
        if (emailSubjectsTable) {
            initTable();
            initSearch();
        }
    }

    /**
     * Inicializar modal
     */
    function initModal() {
        // Abrir modal al hacer clic en "Nuevo asunto"
        btnNewSubject.addEventListener('click', openNewSubjectModal);
        
        // Cerrar modal
        if (closeSubjectModal) {
            closeSubjectModal.addEventListener('click', closeModal);
        }
        
        if (cancelSubjectBtn) {
            cancelSubjectBtn.addEventListener('click', closeModal);
        }
        
        // Cerrar al hacer clic en el overlay
        const overlay = subjectModal?.querySelector('.modal-overlay');
        if (overlay) {
            overlay.addEventListener('click', closeModal);
        }
        
        // Cerrar con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !subjectModal.classList.contains('hidden')) {
                closeModal();
            }
        });
    }

    /**
     * Abrir modal para nuevo asunto
     */
    function openNewSubjectModal() {
        if (!subjectModal) return;
        
        // Resetear formulario
        resetForm();
        
        // Actualizar título
        if (modalTitle) {
            modalTitle.textContent = 'Nuevo Asunto';
        }
        
        // Mostrar modal
        subjectModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    /**
     * Cerrar modal
     */
    function closeModal() {
        if (!subjectModal) return;
        
        subjectModal.classList.add('hidden');
        document.body.style.overflow = '';
        resetForm();
    }

    /**
     * Resetear formulario
     */
    function resetForm() {
        if (emailSubjectForm) {
            emailSubjectForm.reset();
        }
        if (subjectIdInput) {
            subjectIdInput.value = '';
        }
        clearAllErrors();
    }

    /**
     * Limpiar todos los errores
     */
    function clearAllErrors() {
        const errorElements = emailSubjectForm?.querySelectorAll('.form-error');
        errorElements?.forEach(el => {
            el.textContent = '';
            el.style.display = 'none';
        });
        
        const formGroups = emailSubjectForm?.querySelectorAll('.form-group');
        formGroups?.forEach(group => group.classList.remove('has-error'));
    }

    /**
     * Inicializar formulario
     */
    function initForm() {
        if (!emailSubjectForm) return;
        
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
        const isEdit = !!data.id && data.id !== '';
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
                // Cerrar modal primero
                closeModal();
                
                // Mostrar popup de éxito
                await window.GAC.success(
                    result.message || (isEdit ? 'Asunto actualizado correctamente' : 'Asunto agregado correctamente'), 
                    'Éxito'
                );
                
                // Actualizar tabla dinámicamente
                refreshTable();
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
     * Actualizar tabla dinámicamente
     */
    function refreshTable() {
        if (!window.SearchAJAX) {
            // Si SearchAJAX no está disponible, recargar la página
            location.reload();
            return;
        }
        
        // Obtener valores actuales de búsqueda y paginación
        const currentSearch = searchInput?.value || '';
        const currentPerPage = perPageSelect?.value || '15';
        const currentPage = 1; // Resetear a página 1 después de agregar
        
        // Construir URL con parámetros
        const params = new URLSearchParams();
        if (currentSearch) {
            params.set('search', currentSearch);
        }
        params.set('per_page', currentPerPage);
        params.set('page', currentPage);
        params.set('ajax', '1');
        
        // Hacer petición AJAX para actualizar la tabla
        fetch(`${window.location.pathname}?${params.toString()}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            // Actualizar contenido de la tabla
            if (window.SearchAJAX && window.SearchAJAX.updateTableContent) {
                window.SearchAJAX.updateTableContent(html);
                initTable(); // Re-inicializar eventos
            } else {
                // Fallback: recargar página
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error al actualizar tabla:', error);
            location.reload();
        });
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
        // Manejar tanto campos normales como del modal
        const fieldId = field.id.replace('modal_', '');
        const errorElement = document.getElementById(field.id + 'Error') || document.getElementById('modal' + fieldId.charAt(0).toUpperCase() + fieldId.slice(1) + 'Error');
        const formGroup = field.closest('.form-group');
        let errorMessage = '';

        if (field.hasAttribute('required') && !field.value.trim()) {
            errorMessage = 'Este campo es requerido';
        } else if ((field.id === 'subject_line' || field.id === 'modal_subject_line') && field.value.trim().length < 3) {
            errorMessage = 'El asunto debe tener al menos 3 caracteres';
        } else if ((field.id === 'platform_id' || field.id === 'modal_platform_id') && parseInt(field.value) <= 0) {
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
        const fieldId = field.id.replace('modal_', '');
        const errorElement = document.getElementById(field.id + 'Error') || document.getElementById('modal' + fieldId.charAt(0).toUpperCase() + fieldId.slice(1) + 'Error');
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

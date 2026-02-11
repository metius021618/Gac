/**
 * GAC - JavaScript para Gestión de Asuntos de Email
 * Con búsqueda AJAX en tiempo real y paginación
 */

(function() {
    'use strict';

    // Elementos del DOM
    const emailSubjectForm = document.getElementById('emailSubjectForm');
    let emailSubjectsTable = document.getElementById('emailSubjectsTable'); // let para poder actualizar después de AJAX
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

    /** Función de búsqueda (se asigna en initSearch, la usa la paginación) */
    var runSearch = function() {};

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
            initPagination();
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
        
        // Limpiar ID (modo crear)
        if (subjectIdInput) {
            subjectIdInput.value = '';
        }
        
        // Mostrar modal
        subjectModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    /**
     * Abrir modal para editar asunto
     */
    function handleEdit(e) {
        const btn = e.currentTarget;
        const id = btn.dataset.id;
        const platformId = btn.dataset.platformId;
        const subjectLine = btn.dataset.subjectLine;
        
        if (!id || !subjectModal) return;
        
        // Llenar formulario con datos
        if (subjectIdInput) {
            subjectIdInput.value = id;
        }
        
        if (modalPlatformSelect) {
            modalPlatformSelect.value = platformId || '';
        }
        
        if (modalSubjectLineInput) {
            modalSubjectLineInput.value = subjectLine || '';
        }
        
        // Actualizar título
        if (modalTitle) {
            modalTitle.textContent = 'Editar Asunto';
        }
        
        // Limpiar errores
        clearAllErrors();
        
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
        if (modalPlatformSelect) {
            modalPlatformSelect.value = '';
        }
        if (modalSubjectLineInput) {
            modalSubjectLineInput.value = '';
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
     * Inicializar tabla (usando delegación de eventos para elementos dinámicos)
     */
    function initTable() {
        // Usar delegación de eventos en el contenedor de la tabla para elementos dinámicos
        // Esto asegura que los eventos funcionen incluso después de actualizaciones AJAX
        
        // Obtener la tabla actual (puede haber cambiado después de AJAX)
        emailSubjectsTable = document.getElementById('emailSubjectsTable');
        
        if (emailSubjectsTable) {
            // Remover listeners anteriores si existen (usando una función nombrada para poder removerla)
            emailSubjectsTable.removeEventListener('click', handleTableClick);
            // Agregar nuevo listener con delegación
            emailSubjectsTable.addEventListener('click', handleTableClick);
        }
    }

    /**
     * Manejar clics en la tabla usando delegación de eventos
     */
    function handleTableClick(e) {
        // Botón de eliminar
        const deleteBtn = e.target.closest('.btn-delete');
        if (deleteBtn) {
            e.preventDefault();
            e.stopPropagation();
            // Pasar el botón directamente en el evento
            const syntheticEvent = {
                ...e,
                currentTarget: deleteBtn,
                target: deleteBtn
            };
            handleDelete(syntheticEvent);
            return;
        }
        
        // Botón de editar
        const editBtn = e.target.closest('.btn-edit');
        if (editBtn) {
            e.preventDefault();
            e.stopPropagation();
            // Pasar el botón directamente en el evento
            const syntheticEvent = {
                ...e,
                currentTarget: editBtn,
                target: editBtn
            };
            handleEdit(syntheticEvent);
            return;
        }
    }

    /**
     * Búsqueda simple: al escribir o Enter, pide al servidor la tabla filtrada y la reemplaza.
     */
    function initSearch() {
        if (!searchInput || !perPageSelect) return;

        var debounceTimer = null;

        runSearch = function(page) {
            var url = new URL(window.location.href);
            url.searchParams.set('search', searchInput.value.trim());
            url.searchParams.set('page', page || 1);
            var perPage = perPageSelect.value;
            url.searchParams.set('per_page', perPage === 'all' ? 0 : perPage);

            fetch(url.toString(), {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store'
            })
                .then(function(r) { return r.text(); })
                .then(function(html) {
                    var wrap = document.createElement('div');
                    wrap.innerHTML = html;
                    var content = wrap.querySelector('.admin-content');
                    if (!content) return;
                    var newTable = content.querySelector('.table-container');
                    var newPagination = content.querySelector('.pagination-container');
                    var curTable = document.querySelector('.admin-content .table-container');
                    var curPagination = document.querySelector('.admin-content .pagination-container');
                    if (newTable && curTable) curTable.innerHTML = newTable.innerHTML;
                    if (newPagination && curPagination) {
                        curPagination.innerHTML = newPagination.innerHTML;
                    } else if (curPagination && !newPagination) {
                        curPagination.innerHTML = '';
                    }
                    emailSubjectsTable = document.getElementById('emailSubjectsTable');
                    if (emailSubjectsTable) initTable();
                    initPagination();
                })
                .catch(function(err) { console.error('Búsqueda asuntos:', err); });

            if (clearSearchBtn) clearSearchBtn.style.display = searchInput.value.trim() ? 'flex' : 'none';
        }

        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() { runSearch(1); }, 300);
        });
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(debounceTimer);
                runSearch(1);
            }
        });

        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function() {
                searchInput.value = '';
                clearSearchBtn.style.display = 'none';
                runSearch(1);
            });
        }

        perPageSelect.addEventListener('change', function() {
            runSearch(1);
        });
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
                
                // Pequeño delay para que el modal se cierre antes del popup
                await new Promise(resolve => setTimeout(resolve, 100));
                
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
     * Actualizar tabla después de crear/editar (misma búsqueda, página 1).
     */
    function refreshTable() {
        if (typeof runSearch === 'function') {
            runSearch(1);
        } else {
            location.reload();
        }
    }

    /**
     * Inicializar eventos de paginación
     */
    function initPagination() {
        // Botones de paginación (usar delegación de eventos para elementos dinámicos)
        const paginationContainer = document.querySelector('.pagination-container');
        if (paginationContainer) {
            paginationContainer.removeEventListener('click', handlePaginationClick);
            paginationContainer.addEventListener('click', handlePaginationClick);
        }
    }

    /**
     * Manejar clic en paginación: misma lógica que la búsqueda (fetch y reemplazar tabla).
     */
    function handlePaginationClick(e) {
        const btn = e.target.closest('.pagination-btn[data-page], .pagination-page[data-page]');
        if (!btn || btn.disabled) return;
        e.preventDefault();
        e.stopPropagation();
        const page = parseInt(btn.dataset.page, 10);
        if (!page || isNaN(page) || page < 1) return;
        runSearch(page);
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

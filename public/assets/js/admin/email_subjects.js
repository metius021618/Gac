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
    const modalCategoryInput = document.getElementById('modal_category');
    const modalBodyMatchInput = document.getElementById('modal_body_match');
    const modalBodyMatchGroup = document.getElementById('modalBodyMatchGroup');

    function getActiveCategory() {
        var allowed = {
            general: true,
            modo_hogar: true,
            modo_viaje: true,
            especial_leer: true,
            especial_no_leer: true
        };
        function ok(c) {
            c = (c || '').trim();
            return allowed[c] ? c : '';
        }

        var params = new URLSearchParams(window.location.search);
        var fromUrl = ok(params.get('category'));
        if (fromUrl) return fromUrl;

        var specialActive = document.querySelector('.subject-special-option.is-active');
        var fromSpecial = ok(specialActive && specialActive.dataset.category);
        if (fromSpecial) return fromSpecial;

        var activeOpt = document.querySelector('.subject-category-option.is-active');
        var fromOpt = ok(activeOpt && activeOpt.dataset.category);
        if (fromOpt) return fromOpt;

        var fromModal = ok(modalCategoryInput && modalCategoryInput.value);
        if (fromModal) return fromModal;

        return 'general';
    }

    function isSpecialCategory(c) {
        return c === 'especial_leer' || c === 'especial_no_leer';
    }

    function syncBodyMatchVisibility() {
        var show = isSpecialCategory(getActiveCategory());
        if (modalBodyMatchGroup) {
            modalBodyMatchGroup.style.display = show ? '' : 'none';
        }
        if (modalBodyMatchInput) {
            if (show) {
                modalBodyMatchInput.setAttribute('required', 'required');
            } else {
                modalBodyMatchInput.removeAttribute('required');
                modalBodyMatchInput.value = '';
            }
        }
    }

    function categoryModalTitle(isEdit) {
        var prefix = isEdit ? 'Editar' : 'Nuevo';
        var c = getActiveCategory();
        if (c === 'modo_viaje') return prefix + ' Asunto Actualizar Hogar';
        if (c === 'modo_hogar') return prefix + ' Asunto Código Temporal';
        if (c === 'especial_leer') return prefix + ' Asunto especial (sí se lee)';
        if (c === 'especial_no_leer') return prefix + ' Asunto especial (no se lee)';
        return prefix + ' Asunto';
    }

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

        initCategorySwitch();
        syncBodyMatchVisibility();
        initSpecialAccessPanel();
    }

    /**
     * Animación del slider Generales / Código Temporal / Actualizar Hogar antes de navegar.
     */
    function initCategorySwitch() {
        var root = document.querySelector('.subject-category-switch');
        if (!root) return;
        var options = root.querySelectorAll('.subject-category-option');
        var navigating = false;

        options.forEach(function (opt) {
            opt.addEventListener('click', function (e) {
                var category = opt.getAttribute('data-category');
                var href = opt.getAttribute('href');
                if (!category || !href) return;

                if (opt.classList.contains('is-active') || root.getAttribute('data-active') === category
                    || (root.getAttribute('data-active') === 'especiales' && (category === 'especial_leer' || category === 'especial_no_leer'))) {
                    if (opt.classList.contains('is-active')) {
                        e.preventDefault();
                        return;
                    }
                }

                e.preventDefault();
                if (navigating) return;
                navigating = true;

                var activeKey = (category === 'especial_leer' || category === 'especial_no_leer') ? 'especiales' : category;
                root.setAttribute('data-active', activeKey);
                options.forEach(function (o) {
                    var oc = o.getAttribute('data-category');
                    var on = oc === category || (activeKey === 'especiales' && (oc === 'especial_leer' || oc === 'especial_no_leer') && o.classList.contains('is-active'));
                    if (activeKey === 'especiales') {
                        on = (oc === 'especial_leer' || oc === 'especial_no_leer');
                    } else {
                        on = oc === category;
                    }
                    o.classList.toggle('is-active', on);
                    o.setAttribute('aria-selected', on ? 'true' : 'false');
                });

                window.setTimeout(function () {
                    window.location.href = href;
                }, 300);
            });
        });
    }

    function initSpecialAccessPanel() {
        var listEl = document.getElementById('specialAccessList');
        if (!listEl) return;
        var searchEl = document.getElementById('specialAccessSearch');
        var timer = null;

        function render(rows) {
            if (!rows || !rows.length) {
                listEl.innerHTML = '<p class="empty-message">No hay usuarios registrados</p>';
                return;
            }
            listEl.innerHTML = rows.map(function (row) {
                var checked = Number(row.can_view_special) === 1 ? 'checked' : '';
                return '<label class="special-access-row">'
                    + '<input type="checkbox" data-email="' + String(row.email).replace(/"/g, '&quot;') + '" ' + checked + '>'
                    + '<span class="special-access-email">' + String(row.email) + '</span>'
                    + '<span class="special-access-meta">' + (row.platforms || 0) + ' plataforma(s)</span>'
                    + '</label>';
            }).join('');
        }

        async function load(q) {
            try {
                var url = '/admin/email-subjects/special-access?search=' + encodeURIComponent(q || '');
                var res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                var data = await res.json();
                if (data.success) render(data.data || []);
                else listEl.innerHTML = '<p class="empty-message">Error al cargar</p>';
            } catch (err) {
                listEl.innerHTML = '<p class="empty-message">Error de conexión</p>';
            }
        }

        listEl.addEventListener('change', async function (e) {
            var cb = e.target.closest('input[type="checkbox"][data-email]');
            if (!cb) return;
            var email = cb.getAttribute('data-email');
            var enabled = cb.checked ? 1 : 0;
            cb.disabled = true;
            try {
                var res = await fetch('/admin/email-subjects/special-access', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ email: email, enabled: enabled })
                });
                var data = await res.json();
                if (!data.success) {
                    cb.checked = !cb.checked;
                    if (window.GAC) await window.GAC.error(data.message || 'No se pudo actualizar', 'Error');
                }
            } catch (err) {
                cb.checked = !cb.checked;
            } finally {
                cb.disabled = false;
            }
        });

        if (searchEl) {
            searchEl.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () { load(searchEl.value.trim()); }, 250);
            });
        }
        load('');
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
            modalTitle.textContent = categoryModalTitle(false);
        }

        if (modalCategoryInput) {
            modalCategoryInput.value = getActiveCategory();
        }
        syncBodyMatchVisibility();
        
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
        const category = btn.dataset.category || getActiveCategory();
        const bodyMatch = btn.dataset.bodyMatch || '';
        
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

        if (modalCategoryInput) {
            modalCategoryInput.value = category;
        }

        if (modalBodyMatchInput) {
            modalBodyMatchInput.value = bodyMatch;
        }
        syncBodyMatchVisibility();
        
        // Actualizar título
        if (modalTitle) {
            modalTitle.textContent = categoryModalTitle(true);
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
        if (modalCategoryInput) {
            modalCategoryInput.value = getActiveCategory();
        }
        if (modalBodyMatchInput) {
            modalBodyMatchInput.value = '';
        }
        syncBodyMatchVisibility();
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
     * Búsqueda dinámica: mismo patrón que correos (email_accounts) con SearchAJAX.
     */
    function initSearch() {
        if (!searchInput || !perPageSelect) return;
        if (window.SearchAJAX) {
            window.SearchAJAX.init({
                searchInput: searchInput,
                perPageSelect: perPageSelect,
                clearSearchBtn: clearSearchBtn,
                endpoint: window.location.pathname,
                minSearchLength: 0,
                getExtraParams: function() {
                    return { category: getActiveCategory() };
                },
                renderCallback: function(html) {
                    window.SearchAJAX.updateTableContent(html);
                    emailSubjectsTable = document.getElementById('emailSubjectsTable');
                    if (emailSubjectsTable) initTable();
                    initPagination();
                },
                onSearchComplete: function() {
                    if (clearSearchBtn) clearSearchBtn.style.display = searchInput.value.trim() ? 'flex' : 'none';
                }
            });
            runSearch = function(page) {
                window.SearchAJAX.performSearch(window.location.pathname, {
                    search: searchInput.value.trim(),
                    page: page || 1,
                    per_page: perPageSelect.value,
                    category: getActiveCategory()
                }, function(html) {
                    window.SearchAJAX.updateTableContent(html);
                    emailSubjectsTable = document.getElementById('emailSubjectsTable');
                    if (emailSubjectsTable) initTable();
                    initPagination();
                });
            };
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
     * Paginación: la maneja SearchAJAX (listener en document). Solo re-asignamos por si se reemplazó el DOM.
     */
    function handlePaginationClick() {
        // SearchAJAX.init ya registró un listener en document para paginación; no duplicar.
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

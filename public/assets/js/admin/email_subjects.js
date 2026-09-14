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

    function isSpecialCategory(c) {
        return c === 'especial_leer' || c === 'especial_no_leer';
    }

    /** Categoría activa en memoria (sin recargar página) */
    var activeCategory = (function () {
        var params = new URLSearchParams(window.location.search);
        var c = (params.get('category') || '').trim();
        var allowed = {
            general: true,
            modo_hogar: true,
            modo_viaje: true,
            especial_leer: true,
            especial_no_leer: true
        };
        return allowed[c] ? c : 'general';
    })();
    var lastSpecialCategory = isSpecialCategory(activeCategory) ? activeCategory : 'especial_leer';
    var categorySwitchBusy = false;
    var specialAccessLoaded = false;
    var loadSpecialAccessFn = null;
    var selectedSpecialSubjectId = 0;
    var selectedSpecialSubjectLine = '';

    function getActiveCategory() {
        return activeCategory || 'general';
    }

    function mainTabKey(c) {
        return isSpecialCategory(c) ? 'especiales' : c;
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
                if (!show) modalBodyMatchInput.value = '';
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

    function updateCategoryChrome(category) {
        var root = document.getElementById('subjectCategorySwitch') || document.querySelector('.subject-category-switch');
        var activeKey = mainTabKey(category);
        if (root) {
            root.setAttribute('data-active', activeKey);
            root.querySelectorAll('.subject-category-option').forEach(function (o) {
                var on = o.getAttribute('data-main') === 'especiales'
                    ? activeKey === 'especiales'
                    : o.getAttribute('data-category') === category;
                o.classList.toggle('is-active', on);
                o.setAttribute('aria-selected', on ? 'true' : 'false');
            });
        }

        var subbar = document.getElementById('subjectSpecialSubbar');
        var panel = document.getElementById('specialAccessPanel');
        var special = isSpecialCategory(category);
        if (subbar) {
            subbar.classList.toggle('is-hidden', !special);
            subbar.setAttribute('aria-hidden', special ? 'false' : 'true');
        }
        // El panel de usuarios solo se abre con el botón de usuarios (por asunto)
        if (panel && category !== 'especial_leer') {
            hideSpecialAccessPanel();
        }

        var specialSwitch = document.getElementById('subjectSpecialSwitch');
        if (specialSwitch && special) {
            specialSwitch.setAttribute('data-active', category);
            specialSwitch.querySelectorAll('.subject-special-option').forEach(function (o) {
                var on = o.getAttribute('data-category') === category;
                o.classList.toggle('is-active', on);
            });
        }

        var hint = document.getElementById('subjectSpecialHint');
        if (hint && special) {
            hint.textContent = category === 'especial_no_leer'
                ? 'Se reconocen y se marcan procesados, pero no se guardan (ej. compras).'
                : 'Se guardan como código especial. Solo usuarios autorizados por cada asunto los ven en consulta.';
        }

        if (modalCategoryInput) {
            modalCategoryInput.value = category;
        }
        syncBodyMatchVisibility();
    }

    function hideSpecialAccessPanel() {
        var panel = document.getElementById('specialAccessPanel');
        if (panel) {
            panel.classList.add('is-hidden');
            panel.setAttribute('aria-hidden', 'true');
        }
        selectedSpecialSubjectId = 0;
        selectedSpecialSubjectLine = '';
        document.querySelectorAll('.table-row.is-users-selected').forEach(function (row) {
            row.classList.remove('is-users-selected');
        });
    }

    function openSpecialAccessPanel(subjectId, subjectLine) {
        subjectId = parseInt(subjectId, 10) || 0;
        if (!subjectId) return;
        selectedSpecialSubjectId = subjectId;
        selectedSpecialSubjectLine = subjectLine || '';
        var panel = document.getElementById('specialAccessPanel');
        var label = document.getElementById('specialAccessSelectedLabel');
        if (panel) {
            panel.classList.remove('is-hidden');
            panel.setAttribute('aria-hidden', 'false');
        }
        if (label) {
            label.textContent = 'Asunto: ' + (selectedSpecialSubjectLine || ('#' + subjectId));
        }
        document.querySelectorAll('.table-row.is-users-selected').forEach(function (row) {
            row.classList.remove('is-users-selected');
        });
        var activeRow = document.querySelector('.table-row[data-id="' + subjectId + '"]');
        if (activeRow) activeRow.classList.add('is-users-selected');
        if (typeof loadSpecialAccessFn === 'function') {
            var searchEl = document.getElementById('specialAccessSearch');
            loadSpecialAccessFn(searchEl ? searchEl.value.trim() : '');
            specialAccessLoaded = true;
        }
        if (panel) {
            panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
    }

    function switchCategory(category, opts) {
        opts = opts || {};
        var allowed = {
            general: true,
            modo_hogar: true,
            modo_viaje: true,
            especial_leer: true,
            especial_no_leer: true
        };
        if (!allowed[category]) return;
        if (category === activeCategory && !opts.force) return;
        if (categorySwitchBusy) return;
        categorySwitchBusy = true;

        if (isSpecialCategory(category)) {
            lastSpecialCategory = category;
        }
        activeCategory = category;
        updateCategoryChrome(category);

        var host = document.getElementById('subjectsTableHost');
        if (host) host.classList.add('is-loading');

        // Reset búsqueda al cambiar sección (más claro para el usuario)
        if (searchInput && !opts.keepSearch) {
            searchInput.value = '';
            if (clearSearchBtn) clearSearchBtn.style.display = 'none';
        }

        var done = function () {
            categorySwitchBusy = false;
            if (host) host.classList.remove('is-loading');
        };

        if (typeof runSearch === 'function') {
            var maybePromise = runSearch(1);
            if (maybePromise && typeof maybePromise.then === 'function') {
                maybePromise.then(done).catch(done);
            } else {
                window.setTimeout(done, 350);
            }
        } else {
            done();
        }
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
        updateCategoryChrome(activeCategory);
        initSpecialAccessPanel();
    }

    /**
     * Slider + cambio de sección por AJAX (sin recargar).
     */
    function initCategorySwitch() {
        var root = document.getElementById('subjectCategorySwitch') || document.querySelector('.subject-category-switch');
        if (root) {
            root.querySelectorAll('.subject-category-option').forEach(function (opt) {
                opt.addEventListener('click', function (e) {
                    e.preventDefault();
                    var category = opt.getAttribute('data-category');
                    if (opt.getAttribute('data-main') === 'especiales') {
                        category = lastSpecialCategory || 'especial_leer';
                    }
                    if (!category) return;
                    if (mainTabKey(category) === mainTabKey(activeCategory) && !isSpecialCategory(category)) {
                        return;
                    }
                    if (opt.getAttribute('data-main') === 'especiales' && isSpecialCategory(activeCategory)) {
                        return;
                    }
                    switchCategory(category);
                });
            });
        }

        var specialSwitch = document.getElementById('subjectSpecialSwitch');
        if (specialSwitch) {
            specialSwitch.querySelectorAll('.subject-special-option').forEach(function (opt) {
                opt.addEventListener('click', function (e) {
                    e.preventDefault();
                    var category = opt.getAttribute('data-category');
                    if (!category || category === activeCategory) return;
                    switchCategory(category);
                });
            });
        }

        window.addEventListener('popstate', function () {
            var params = new URLSearchParams(window.location.search);
            var c = (params.get('category') || 'general').trim();
            if (c !== activeCategory) {
                switchCategory(c, { force: true, keepSearch: true });
            }
        });
    }

    function initSpecialAccessPanel() {
        var listEl = document.getElementById('specialAccessList');
        if (!listEl) return;
        var searchEl = document.getElementById('specialAccessSearch');
        var timer = null;
        var closeBtn = document.getElementById('specialAccessClose');

        function render(rows) {
            if (!rows || !rows.length) {
                listEl.innerHTML = '<p class="empty-message">No hay usuarios registrados</p>';
                return;
            }
            listEl.innerHTML = rows.map(function (row) {
                var username = String(row.username || '');
                var checked = Number(row.can_view || row.can_view_special) === 1 ? 'checked' : '';
                return '<label class="special-access-row">'
                    + '<input type="checkbox" data-username="' + username.replace(/"/g, '&quot;') + '" ' + checked + '>'
                    + '<span class="special-access-email">' + username.replace(/</g, '&lt;') + '</span>'
                    + '<span class="special-access-meta">' + (row.accounts || 0) + ' cuenta(s)</span>'
                    + '</label>';
            }).join('');
        }

        async function load(q) {
            if (!selectedSpecialSubjectId) {
                listEl.innerHTML = '<p class="empty-message">Selecciona un asunto…</p>';
                return;
            }
            try {
                var url = '/admin/email-subjects/special-access?subject_id=' + encodeURIComponent(selectedSpecialSubjectId)
                    + '&search=' + encodeURIComponent(q || '');
                var res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                var data = await res.json();
                if (data.success) {
                    if (data.subject && data.subject.subject_line) {
                        selectedSpecialSubjectLine = data.subject.subject_line;
                        var label = document.getElementById('specialAccessSelectedLabel');
                        if (label) label.textContent = 'Asunto: ' + selectedSpecialSubjectLine;
                    }
                    render(data.data || []);
                } else {
                    listEl.innerHTML = '<p class="empty-message">Error al cargar</p>';
                }
            } catch (err) {
                listEl.innerHTML = '<p class="empty-message">Error de conexión</p>';
            }
        }

        loadSpecialAccessFn = load;

        async function bulkSet(enabled) {
            if (!selectedSpecialSubjectId) return;
            var markBtn = document.getElementById('specialAccessMarkAll');
            var unmarkBtn = document.getElementById('specialAccessUnmarkAll');
            if (markBtn) markBtn.disabled = true;
            if (unmarkBtn) unmarkBtn.disabled = true;
            try {
                var res = await fetch('/admin/email-subjects/special-access/bulk', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        subject_id: selectedSpecialSubjectId,
                        enabled: enabled ? 1 : 0,
                        search: searchEl ? searchEl.value.trim() : ''
                    })
                });
                var data = await res.json();
                if (!data.success) {
                    if (window.GAC) await window.GAC.error(data.message || 'No se pudo actualizar', 'Error');
                    return;
                }
                await load(searchEl ? searchEl.value.trim() : '');
            } catch (err) {
                if (window.GAC) await window.GAC.error('Error de conexión', 'Error');
            } finally {
                if (markBtn) markBtn.disabled = false;
                if (unmarkBtn) unmarkBtn.disabled = false;
            }
        }

        var markAllBtn = document.getElementById('specialAccessMarkAll');
        var unmarkAllBtn = document.getElementById('specialAccessUnmarkAll');
        if (markAllBtn) markAllBtn.addEventListener('click', function () { bulkSet(true); });
        if (unmarkAllBtn) unmarkAllBtn.addEventListener('click', function () { bulkSet(false); });
        if (closeBtn) closeBtn.addEventListener('click', hideSpecialAccessPanel);

        listEl.addEventListener('change', async function (e) {
            var cb = e.target.closest('input[type="checkbox"][data-username]');
            if (!cb || !selectedSpecialSubjectId) return;
            var username = cb.getAttribute('data-username');
            var enabled = cb.checked ? 1 : 0;
            cb.disabled = true;
            try {
                var res = await fetch('/admin/email-subjects/special-access', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        subject_id: selectedSpecialSubjectId,
                        username: username,
                        enabled: enabled
                    })
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
        const usersBtn = e.target.closest('.btn-users');
        if (usersBtn) {
            e.preventDefault();
            e.stopPropagation();
            openSpecialAccessPanel(usersBtn.dataset.id, usersBtn.dataset.subjectLine || '');
            return;
        }

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
                    renderSubjectsTable(html);
                    emailSubjectsTable = document.getElementById('emailSubjectsTable');
                    if (emailSubjectsTable) initTable();
                    initPagination();
                },
                onSearchComplete: function() {
                    if (clearSearchBtn) clearSearchBtn.style.display = searchInput.value.trim() ? 'flex' : 'none';
                }
            });
            runSearch = function(page) {
                return window.SearchAJAX.performSearch(window.location.pathname, {
                    search: searchInput.value.trim(),
                    page: page || 1,
                    per_page: perPageSelect.value,
                    category: getActiveCategory()
                }, function(html) {
                    renderSubjectsTable(html);
                    emailSubjectsTable = document.getElementById('emailSubjectsTable');
                    if (emailSubjectsTable) initTable();
                    initPagination();
                });
            };
        }
    }

    function renderSubjectsTable(html) {
        var temp = document.createElement('div');
        temp.innerHTML = html;
        var adminContent = temp.querySelector('.admin-content') || temp;
        var host = document.getElementById('subjectsTableHost');
        var table = adminContent.querySelector('.table-container');
        var pag = adminContent.querySelector('.pagination-container');
        if (host && table) {
            host.innerHTML = table.outerHTML + (pag ? pag.outerHTML : '');
            return;
        }
        window.SearchAJAX.updateTableContent(html);
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

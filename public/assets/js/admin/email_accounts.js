/**
 * GAC - JavaScript para Gestión de Cuentas de Email
 * Con búsqueda AJAX en tiempo real y paginación
 */

(function() {
    'use strict';

    // Elementos del DOM (la tabla se obtiene por ID cada vez para que tras búsqueda sea la actual)
    const emailAccountForm = document.getElementById('emailAccountForm');
    const searchInput = document.getElementById('searchInput');
    function getEmailAccountsTable() { return document.getElementById('emailAccountsTable'); }
    const clearSearchBtn = document.getElementById('clearSearch');
    const perPageSelect = document.getElementById('perPageSelect');
    const tableContainer = document.querySelector('.table-container');
    
    let isLoading = false;

    /**
     * Término de búsqueda actual: prioriza el input (lo que el usuario escribe).
     */
    function getCurrentSearchTerm() {
        if (searchInput && searchInput.value.trim()) {
            return searchInput.value.trim();
        }
        var qs = new URLSearchParams(window.location.search);
        return (qs.get('search') || '').trim();
    }

    /**
     * Aplicar filtros de plataforma/tiempo por AJAX sin perder el buscador.
     */
    function applyListaCuentasFilters(overrides) {
        overrides = overrides || {};
        var qs = new URLSearchParams(window.location.search);
        var search = getCurrentSearchTerm();
        var params = {
            search: search,
            page: overrides.page != null ? overrides.page : 1,
            per_page: (perPageSelect && perPageSelect.value) ? perPageSelect.value : (qs.get('per_page') || '15'),
            platform_id: overrides.platform_id !== undefined ? overrides.platform_id : (qs.get('platform_id') || ''),
            date_from: overrides.date_from !== undefined ? overrides.date_from : (qs.get('date_from') || ''),
            date_to: overrides.date_to !== undefined ? overrides.date_to : (qs.get('date_to') || ''),
            time_range: overrides.time_range !== undefined ? overrides.time_range : (qs.get('time_range') || '')
        };

        if (!window.SearchAJAX) {
            var url = new URL('/admin/email-accounts', window.location.origin);
            Object.keys(params).forEach(function(k) {
                if (params[k] !== '' && params[k] !== null && params[k] !== undefined) {
                    url.searchParams.set(k, String(params[k]));
                }
            });
            window.location.href = url.toString();
            return;
        }

        window.SearchAJAX.performSearch('/admin/email-accounts', params, function(html) {
            window.SearchAJAX.updateTableContent(html);
            initTable();
            updateEmailFiltersBar();
            updateFilterLabelsFromUrl();
            if (clearSearchBtn) {
                clearSearchBtn.style.display = search ? 'flex' : 'none';
            }
            if (searchInput && searchInput.value !== search) {
                searchInput.value = search;
            }
        });
    }

    function updateFilterLabelsFromUrl() {
        var qs = new URLSearchParams(window.location.search);
        var platformId = qs.get('platform_id') || '';
        var platformValue = document.getElementById('listaCuentasPlatformValue');
        if (platformValue) {
            var label = 'Todas';
            if (platformId) {
                var drop = document.getElementById('listaCuentasPlatformDropdown');
                var links = drop ? drop.querySelectorAll('a[href]') : [];
                for (var i = 0; i < links.length; i++) {
                    try {
                        var linkParams = new URL(links[i].href, window.location.origin).searchParams;
                        if ((linkParams.get('platform_id') || '') === String(platformId)) {
                            label = (links[i].textContent || '').trim() || 'Todas';
                            break;
                        }
                    } catch (err) {
                        // ignore invalid href
                    }
                }
            }
            platformValue.textContent = label;
        }
        var timeValue = document.getElementById('listaCuentasTimeFilterValue');
        if (timeValue) {
            var tr = qs.get('time_range') || '';
            if (tr === '7') timeValue.textContent = 'Últimos 7 días';
            else if (tr === '30') timeValue.textContent = 'Últimos 30 días';
            else if (tr === '90') timeValue.textContent = 'Últimos 90 días';
            else if (tr === 'custom' || (qs.get('date_from') && qs.get('date_to'))) timeValue.textContent = 'Personalizado';
            else timeValue.textContent = 'Todo';
        }
    }

    /**
     * Interceptar clicks de filtros (plataforma/tiempo) y conservar búsqueda.
     */
    function bindFilterLinksPreserveSearch(container) {
        if (!container || container.dataset.filterBound === 'true') return;
        container.dataset.filterBound = 'true';
        container.addEventListener('click', function(e) {
            var link = e.target.closest('a[href]');
            if (!link || !container.contains(link)) return;
            if (link.id === 'listaCuentasTimeFilterCustom') return;

            e.preventDefault();
            e.stopPropagation();

            var url = new URL(link.href, window.location.origin);
            var sp = url.searchParams;
            var overrides = {
                page: 1,
                platform_id: sp.has('platform_id') ? (sp.get('platform_id') || '') : undefined,
                date_from: sp.has('date_from') ? (sp.get('date_from') || '') : undefined,
                date_to: sp.has('date_to') ? (sp.get('date_to') || '') : undefined,
                time_range: sp.has('time_range') ? (sp.get('time_range') || '') : undefined
            };

            // Links "Todo" / "Todas" limpian esos params
            if (container.id === 'listaCuentasPlatformDropdown') {
                overrides.platform_id = sp.get('platform_id') || '';
            }
            if (container.id === 'listaCuentasTimeFilterDropdown') {
                overrides.date_from = sp.get('date_from') || '';
                overrides.date_to = sp.get('date_to') || '';
                overrides.time_range = sp.get('time_range') || '';
            }

            var drop = container.closest('.analisis-filter-dropdown, .activity-filter-dropdown') || container;
            drop.classList.remove('open');

            applyListaCuentasFilters(overrides);
        });
    }

    /**
     * Inicialización (Lista de cuentas: sin filtro por dominio)
     */
    function init() {
        if (emailAccountForm) {
            initForm();
        }
        
        if (getEmailAccountsTable()) {
            initTable();
            initSearch();
        }

        var excelBtn = document.getElementById('listaCuentasExcelBtn');
        if (excelBtn) {
            excelBtn.addEventListener('click', function(e) {
                e.preventDefault();
                var base = this.getAttribute('data-export-base') || '/admin/email-accounts/export-lista-excel';
                var search = searchInput && searchInput.value ? searchInput.value.trim() : '';
                var qs = new URLSearchParams(window.location.search);
                var platformId = qs.get('platform_id') || '';
                var dateFrom = qs.get('date_from') || '';
                var dateTo = qs.get('date_to') || '';
                var params = [];
                if (search) params.push('search=' + encodeURIComponent(search));
                if (platformId && platformId !== '0') params.push('platform_id=' + encodeURIComponent(platformId));
                if (dateFrom) params.push('date_from=' + encodeURIComponent(dateFrom));
                if (dateTo) params.push('date_to=' + encodeURIComponent(dateTo));
                var url = base + (params.length ? '?' + params.join('&') : '');
                window.location.href = url;
            });
        }

        initListaCuentasTimeFilter();
    }

    /**
     * Filtro Tiempo en Lista de cuentas: dropdown (Todo, 7, 30, 90, Personalizado) + modal, mismo modelo que Actividad de administrador.
     */
    function initListaCuentasTimeFilter() {
        var timeDrop = document.getElementById('listaCuentasTimeFilterDropdown');
        if (!timeDrop) return;

        function scheduleClose(drop) {
            if (drop._closeTimeout) clearTimeout(drop._closeTimeout);
            drop._closeTimeout = setTimeout(function() {
                drop.classList.remove('open');
                drop._closeTimeout = null;
            }, 120);
        }
        function cancelClose(drop) {
            if (drop._closeTimeout) {
                clearTimeout(drop._closeTimeout);
                drop._closeTimeout = null;
            }
        }
        timeDrop.addEventListener('mouseenter', function() {
            cancelClose(this);
            this.classList.add('open');
        });
        timeDrop.addEventListener('mouseleave', function() {
            scheduleClose(this);
        });
        var timeMenu = timeDrop.querySelector('.activity-filter-menu');
        if (timeMenu) {
            timeMenu.addEventListener('mouseenter', function() {
                cancelClose(timeDrop);
                timeDrop.classList.add('open');
            });
            timeMenu.addEventListener('mouseleave', function() {
                scheduleClose(timeDrop);
            });
        }
        timeDrop.addEventListener('click', function(e) {
            if (e.target.closest('.activity-filter-menu')) return;
            this.classList.toggle('open');
        });
        document.addEventListener('click', function(e) {
            if (timeDrop && !timeDrop.contains(e.target)) timeDrop.classList.remove('open');
        });

        bindFilterLinksPreserveSearch(timeDrop);

        var customLink = document.getElementById('listaCuentasTimeFilterCustom');
        var modal = document.getElementById('listaCuentasDateRangeModal');
        var closeBtn = document.getElementById('closeListaCuentasDateModal');
        var overlay = modal && modal.querySelector('.modal-overlay');
        var applyBtn = document.getElementById('listaCuentasDateRangeApply');
        var inputFrom = document.getElementById('listaCuentasDateFrom');
        var inputTo = document.getElementById('listaCuentasDateTo');

        if (customLink) {
            customLink.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (timeDrop) timeDrop.classList.remove('open');
                if (modal) modal.classList.remove('hidden');
                var today = new Date().toISOString().slice(0, 10);
                var sixMonths = new Date();
                sixMonths.setMonth(sixMonths.getMonth() - 6);
                var defaultFrom = sixMonths.toISOString().slice(0, 10);
                if (inputFrom) inputFrom.value = defaultFrom;
                if (inputTo) inputTo.value = today;
            });
        }
        function closeModal() {
            if (modal) modal.classList.add('hidden');
        }
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (overlay) overlay.addEventListener('click', closeModal);
        if (applyBtn && inputFrom && inputTo) {
            applyBtn.addEventListener('click', function() {
                var from = inputFrom.value;
                var to = inputTo.value;
                if (!from || !to) return;
                closeModal();
                applyListaCuentasFilters({
                    date_from: from,
                    date_to: to,
                    time_range: 'custom',
                    page: 1
                });
            });
        }
    }

    /**
     * Dropdown Plataforma en Lista de cuentas: misma interacción que Análisis (hover abre/cierra).
     */
    function initListaCuentasPlatformDropdown() {
        var drop = document.getElementById('listaCuentasPlatformDropdown');
        if (!drop) return;
        function scheduleClose(d) {
            if (d._closeTimeout) clearTimeout(d._closeTimeout);
            d._closeTimeout = setTimeout(function() {
                d.classList.remove('open');
                d._closeTimeout = null;
            }, 120);
        }
        function cancelClose(d) {
            if (d._closeTimeout) {
                clearTimeout(d._closeTimeout);
                d._closeTimeout = null;
            }
        }
        drop.addEventListener('mouseenter', function() {
            cancelClose(this);
            this.classList.add('open');
        });
        drop.addEventListener('mouseleave', function() {
            scheduleClose(this);
        });
        var menu = drop.querySelector('.analisis-filter-menu');
        if (menu) {
            menu.addEventListener('mouseenter', function() {
                cancelClose(drop);
                drop.classList.add('open');
            });
            menu.addEventListener('mouseleave', function() {
                scheduleClose(drop);
            });
        }
        document.addEventListener('click', function(e) {
            if (drop && !drop.contains(e.target)) drop.classList.remove('open');
        });
        bindFilterLinksPreserveSearch(drop);
    }

    /**
     * Inicializar formulario
     */
    function initForm() {
        emailAccountForm.addEventListener('submit', handleFormSubmit);
        
        // Validación en tiempo real
        const inputs = emailAccountForm.querySelectorAll('input[required], select[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', validateField);
            input.addEventListener('input', clearFieldError);
        });
    }

    /**
     * Inicializar tabla
     */
    function initTable() {
        const emailAccountsTable = getEmailAccountsTable();
        if (!emailAccountsTable) return;
        // Botones de toggle status
        const toggleButtons = emailAccountsTable.querySelectorAll('.btn-toggle');
        toggleButtons.forEach(btn => {
            btn.removeEventListener('click', handleToggleStatus);
            btn.addEventListener('click', handleToggleStatus);
        });

        // Botones de eliminar
        const deleteButtons = emailAccountsTable.querySelectorAll('.btn-delete');
        deleteButtons.forEach(btn => {
            btn.removeEventListener('click', handleDelete); // Evitar duplicados
            btn.addEventListener('click', handleDelete);
        });
    }

    /**
     * Actualizar barra de filtros tras recargar tabla por AJAX.
     * Los filtros se muestran siempre; las opciones de plataforma vienen del servidor en la carga inicial.
     */
    function updateEmailFiltersBar() {
        const bar = document.getElementById('emailFiltersBar');
        if (bar) bar.style.display = 'flex';
    }


    /**
     * Parámetros extra (plataforma y rango de tiempo) para la búsqueda/filtrado en Lista de cuentas.
     * platform_id, date_from, date_to se leen de la URL actual (plataforma ahora es dropdown con links).
     */
    function getListaCuentasExtraParams() {
        var qs = new URLSearchParams(window.location.search);
        return {
            platform_id: qs.get('platform_id') || '',
            date_from: qs.get('date_from') || '',
            date_to: qs.get('date_to') || '',
            time_range: qs.get('time_range') || ''
        };
    }

    /**
     * Inicializar búsqueda AJAX y filtros (plataforma/fecha visibles desde el inicio).
     */
    function initSearch() {
        if (!searchInput || !perPageSelect) return;

        var endpoint = window.location.pathname;
        var renderCallback = function(html) {
            window.SearchAJAX.updateTableContent(html);
            initTable();
            updateEmailFiltersBar();
        };

        if (window.SearchAJAX) {
            window.SearchAJAX.init({
                searchInput: searchInput,
                perPageSelect: perPageSelect,
                clearSearchBtn: clearSearchBtn,
                endpoint: endpoint,
                minSearchLength: 1,
                renderCallback: renderCallback,
                getExtraParams: getListaCuentasExtraParams,
                onSearchComplete: function() {
                    if (clearSearchBtn && searchInput.value.trim()) {
                        clearSearchBtn.style.display = 'flex';
                    } else if (clearSearchBtn) {
                        clearSearchBtn.style.display = 'none';
                    }
                    updateFilterLabelsFromUrl();
                }
            });

            // Filtro plataforma: ahora es dropdown con links (estilo Análisis); no hay change, se recarga por URL.
            initListaCuentasPlatformDropdown();
            updateFilterLabelsFromUrl();
        } else {
            console.error('SearchAJAX no está disponible');
            initListaCuentasPlatformDropdown();
        }
    }

    /**
     * Manejar envío del formulario
     */
    async function handleFormSubmit(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }

        const formData = new FormData(emailAccountForm);
        const data = Object.fromEntries(formData);
        const isEdit = !!data.id;
        const url = isEdit ? '/admin/email-accounts/update' : '/admin/email-accounts';

        setLoadingState(true);

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
                window.location.href = '/admin/email-accounts';
            } else {
                await showError(result.message || 'Error al guardar la cuenta');
            }
        } catch (error) {
            console.error('Error:', error);
            await showError('Error de conexión. Por favor intenta nuevamente.');
        } finally {
            setLoadingState(false);
        }
    }

    /**
     * Validar formulario
     */
    function validateForm() {
        let isValid = true;
        const requiredFields = emailAccountForm.querySelectorAll('[required]');
        
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
        
        if (field.hasAttribute('required') && !field.value.trim()) {
            showFieldError(errorElement, 'Este campo es requerido');
            return false;
        }

        if (field.type === 'email' && field.value && !window.GAC?.validateEmail(field.value)) {
            showFieldError(errorElement, 'El email no es válido');
            return false;
        }

        clearFieldError({ target: field });
        return true;
    }

    /**
     * Limpiar error de campo
     */
    function clearFieldError(e) {
        const field = e.target;
        const errorElement = document.getElementById(field.id + 'Error');
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.style.display = 'none';
        }
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
     * Establecer estado de carga
     */
    function setLoadingState(loading) {
        const submitBtn = emailAccountForm?.querySelector('button[type="submit"]');
        if (!submitBtn) return;
        
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoader = submitBtn.querySelector('.btn-loader');
        
        if (loading) {
            submitBtn.disabled = true;
            btnLoader?.classList.add('active');
            if (btnText) btnText.textContent = 'Guardando...';
        } else {
            submitBtn.disabled = false;
            btnLoader?.classList.remove('active');
            if (btnText) btnText.textContent = submitBtn.textContent.includes('Actualizar') ? 'Actualizar Cuenta' : 'Guardar Cuenta';
        }
    }

    /**
     * Manejar toggle de estado
     */
    async function handleToggleStatus(e) {
        const btn = e.currentTarget;
        const id = btn.dataset.id;
        const currentEnabled = btn.dataset.enabled === '1';
        const newEnabled = !currentEnabled;

        try {
            const confirmed = await window.GAC.confirm(
                `¿Estás seguro de ${newEnabled ? 'habilitar' : 'deshabilitar'} esta cuenta?`,
                newEnabled ? 'Habilitar Cuenta' : 'Deshabilitar Cuenta'
            );
            if (!confirmed) {
                return;
            }
        } catch (error) {
            console.error('Error al mostrar modal de confirmación:', error);
            return;
        }

        try {
            const response = await fetch('/admin/email-accounts/toggle-status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ id, enabled: newEnabled ? 1 : 0 })
            });

            const result = await response.json();

            if (result.success) {
                location.reload();
            } else {
                await window.GAC.error(result.message || 'Error al cambiar el estado', 'Error');
            }
        } catch (error) {
            console.error('Error:', error);
            await window.GAC.error('Error de conexión', 'Error');
        }
    }

    /**
     * Manejar eliminación
     */
    async function handleDelete(e) {
        const btn = e.currentTarget;
        const id = btn.dataset.id;

        try {
            const confirmed = await window.GAC.confirm(
                '¿Estás seguro de eliminar esta cuenta? Esta acción no se puede deshacer.',
                'Eliminar Cuenta'
            );
            if (!confirmed) {
                return;
            }
        } catch (error) {
            console.error('Error al mostrar modal de confirmación:', error);
            return;
        }

        try {
            const response = await fetch('/admin/email-accounts/delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ id })
            });

            const result = await response.json();

            if (result.success) {
                location.reload();
            } else {
                await window.GAC.error(result.message || 'Error al eliminar la cuenta', 'Error');
            }
        } catch (error) {
            console.error('Error:', error);
            await window.GAC.error('Error de conexión', 'Error');
        }
    }

    /**
     * Mostrar error general
     */
    async function showError(message) {
        await window.GAC.error(message, 'Error');
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

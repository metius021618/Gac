/**
 * GAC - Correos registrados: filtro Todo/Gmail/Outlook/Pocoyoni, búsqueda, paginación, eliminar, Excel
 */
(function() {
    'use strict';

    const wrapper = document.getElementById('correosRegistradosTableWrapper');
    const searchInput = document.getElementById('searchInput');
    const perPageSelect = document.getElementById('perPageSelect');
    const clearSearchBtn = document.getElementById('clearSearch');
    const pills = document.querySelectorAll('.correos-filter-pill');

    let currentFilter = '';
    if (document.querySelector('.correos-filter-pill.active') && document.querySelector('.correos-filter-pill.active').dataset.filter !== undefined) {
        currentFilter = document.querySelector('.correos-filter-pill.active').dataset.filter;
    }

    function getParams(overrides) {
        const p = {
            filter: currentFilter,
            search: (searchInput && searchInput.value.trim()) || '',
            page: 1,
            per_page: (perPageSelect && perPageSelect.value) || '15'
        };
        return Object.assign({}, p, overrides || {});
    }

    function onRender(html) {
        if (window.SearchAJAX && window.SearchAJAX.updateTableContent) {
            window.SearchAJAX.updateTableContent(html);
        }
    }

    function loadTable(params) {
        const endpoint = '/admin/correos-registrados';
        const url = new URL(endpoint, window.location.origin);
        Object.keys(params).forEach(function(k) {
            if (params[k] !== '' && params[k] !== undefined) {
                url.searchParams.set(k, params[k]);
            }
        });
        fetch(url.toString(), {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
        })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                if (!wrapper) return;
                const temp = document.createElement('div');
                temp.innerHTML = html;
                const adminContent = temp.querySelector('.admin-content');
                if (adminContent) {
                    wrapper.innerHTML = adminContent.innerHTML;
                }
                window.history.replaceState({}, '', url.toString());
            })
            .catch(function(err) { console.error('Error cargando tabla:', err); });
    }

    function initFilterPills() {
        if (!pills.length) return;
        pills.forEach(function(pill) {
            pill.addEventListener('click', function() {
                const filter = (this.dataset.filter !== undefined) ? this.dataset.filter : '';
                if (currentFilter === filter) return;
                currentFilter = filter;
                pills.forEach(function(p) { p.classList.remove('active'); });
                this.classList.add('active');
                loadTable(getParams({ filter: filter, page: 1 }));
            });
        });
    }

    function initSearch() {
        if (!searchInput || !perPageSelect || !window.SearchAJAX) return;
        if (clearSearchBtn) {
            clearSearchBtn.style.display = searchInput.value.trim() ? 'flex' : 'none';
        }
        window.SearchAJAX.init({
            searchInput: searchInput,
            perPageSelect: perPageSelect,
            clearSearchBtn: clearSearchBtn,
            endpoint: '/admin/correos-registrados',
            minSearchLength: 1,
            getExtraParams: function() { return { filter: currentFilter }; },
            renderCallback: onRender,
            onSearchComplete: function() {
                if (clearSearchBtn) {
                    clearSearchBtn.style.display = searchInput.value.trim() ? 'flex' : 'none';
                }
            }
        });
    }

    function initDelete() {
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-delete-correo-registrado');
            if (!btn) return;
            e.preventDefault();
            const id = parseInt(btn.dataset.id, 10);
            if (!id) return;
            var confirmMsg = '¿Eliminar este correo? Esta acción no se puede deshacer.';
            if (!(window.confirm && window.confirm(confirmMsg))) return;
            btn.disabled = true;
            var formData = new FormData();
            formData.append('id', id);
            fetch('/admin/email-accounts/delete-account', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
                .then(function(r) { return r.json(); })
                .then(function(result) {
                    if (result.success) {
                        if (window.GAC && window.GAC.success) {
                            window.GAC.success(result.message || 'Correo eliminado', 'Éxito');
                        } else {
                            alert(result.message || 'Correo eliminado');
                        }
                        loadTable(getParams({ page: 1 }));
                    } else {
                        btn.disabled = false;
                        if (window.GAC && window.GAC.error) {
                            window.GAC.error(result.message || 'Error al eliminar', 'Error');
                        } else {
                            alert(result.message || 'Error al eliminar');
                        }
                    }
                })
                .catch(function() {
                    btn.disabled = false;
                    alert('Error de conexión');
                });
        });
    }

    function initExportExcel() {
        var trigger = document.getElementById('excelExportTrigger');
        var modal = document.getElementById('excelExportModal');
        var closeBtn = document.getElementById('closeExcelExportModal');
        var overlay = modal && modal.querySelector('.modal-overlay');
        if (trigger && modal) {
            trigger.addEventListener('click', function() { modal.classList.remove('hidden'); });
            function closeModal() { modal.classList.add('hidden'); }
            if (closeBtn) closeBtn.addEventListener('click', closeModal);
            if (overlay) overlay.addEventListener('click', closeModal);
        }
    }

    function init() {
        initFilterPills();
        initSearch();
        initDelete();
        initExportExcel();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

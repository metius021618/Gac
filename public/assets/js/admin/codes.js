/**
 * GAC - JavaScript para Registro de Accesos
 */

(function() {
    'use strict';

    const searchInput = document.getElementById('searchInput');
    const perPageSelect = document.getElementById('perPage');
    const clearSearchBtn = document.getElementById('clearSearch');
    let searchTimeout;

    // Búsqueda con debounce
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch();
            }, 500);
        });
    }

    // Limpiar búsqueda
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            performSearch();
        });
    }

    // Cambiar cantidad por página
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', this.value);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        });
    }

    // Paginación
    document.querySelectorAll('.pagination-controls button[data-page]').forEach(btn => {
        btn.addEventListener('click', function() {
            const page = this.dataset.page;
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            window.location.href = url.toString();
        });
    });

    function performSearch() {
        const search = searchInput.value.trim();
        const url = new URL(window.location.href);
        
        if (search) {
            url.searchParams.set('search', search);
        } else {
            url.searchParams.delete('search');
        }
        
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    }
})();

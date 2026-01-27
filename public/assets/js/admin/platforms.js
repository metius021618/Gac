/**
 * GAC - JavaScript para Lista de Plataformas
 * Búsqueda AJAX en tiempo real
 */

(function() {
    'use strict';

    const searchInput = document.getElementById('searchInput');
    const perPageSelect = document.getElementById('perPage');
    const clearSearchBtn = document.getElementById('clearSearch');

    // Inicializar búsqueda AJAX
    if (searchInput && perPageSelect) {
        // Mostrar/ocultar botón de limpiar inicialmente
        if (clearSearchBtn) {
            if (searchInput.value.trim()) {
                clearSearchBtn.style.display = 'flex';
            } else {
                clearSearchBtn.style.display = 'none';
            }
        }
        
        window.SearchAJAX.init({
            searchInput: searchInput,
            perPageSelect: perPageSelect,
            clearSearchBtn: clearSearchBtn,
            endpoint: window.location.pathname,
            renderCallback: function(html) {
                window.SearchAJAX.updateTableContent(html);
            },
            onSearchComplete: function() {
                console.log('Búsqueda completada');
            }
        });
    }
})();

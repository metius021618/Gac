/**
 * GAC - JS para vista filtrada de correos (Gmail/Outlook/Pocoyoni)
 * Solo búsqueda AJAX, sin acciones
 */
(function() {
    'use strict';

    const searchInput = document.getElementById('searchInput');
    const perPageSelect = document.getElementById('perPage');
    const clearSearchBtn = document.getElementById('clearSearch');

    if (searchInput && perPageSelect) {
        if (clearSearchBtn) {
            clearSearchBtn.style.display = searchInput.value.trim() ? 'flex' : 'none';
        }

        window.SearchAJAX.init({
            searchInput: searchInput,
            perPageSelect: perPageSelect,
            clearSearchBtn: clearSearchBtn,
            endpoint: window.location.pathname + window.location.search,
            renderCallback: function(html) {
                window.SearchAJAX.updateTableContent(html);
            }
        });
    }
})();

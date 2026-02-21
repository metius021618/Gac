/**
 * GAC - Actividad de usuario (superadmin)
 * Cambio de per_page redirige con page=1 y order actual.
 */
(function() {
    'use strict';
    const perPageSelect = document.getElementById('perPageSelect');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            const params = new URLSearchParams(window.location.search);
            params.set('per_page', this.value);
            params.set('page', '1');
            window.location.href = '/admin/user-activity?' + params.toString();
        });
    }
})();

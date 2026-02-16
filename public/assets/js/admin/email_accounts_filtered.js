/**
 * GAC - JS para vista filtrada de correos (Gmail/Outlook/Pocoyoni)
 * Búsqueda AJAX y eliminación por tachito (solo tabla que corresponde)
 */
(function() {
    'use strict';

    const searchInput = document.getElementById('searchInput');
    const perPageSelect = document.getElementById('perPage');
    const clearSearchBtn = document.getElementById('clearSearch');

    function initDeleteButtons() {
        document.querySelectorAll('.btn-delete-filtered').forEach(function(btn) {
            btn.removeEventListener('click', handleDeleteFiltered);
            btn.addEventListener('click', handleDeleteFiltered);
        });
    }

    async function handleDeleteFiltered(e) {
        const btn = e.currentTarget;
        const id = parseInt(btn.dataset.id, 10);
        const email = (btn.dataset.email || '').trim();
        if (!id) return;

        try {
            const confirmed = await (window.GAC && window.GAC.confirm
                ? window.GAC.confirm('¿Eliminar este correo? Esta acción no se puede deshacer.', 'Eliminar correo')
                : Promise.resolve(window.confirm('¿Eliminar este correo? Esta acción no se puede deshacer.')));
            if (!confirmed) return;
        } catch (err) {
            return;
        }

        btn.disabled = true;
        try {
            const formData = new FormData();
            formData.append('id', id);
            const response = await fetch('/admin/email-accounts/delete-account', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                if (window.GAC && window.GAC.success) {
                    await window.GAC.success(result.message || 'Correo eliminado correctamente', 'Éxito');
                } else {
                    alert(result.message || 'Correo eliminado correctamente');
                }
                location.reload();
            } else {
                btn.disabled = false;
                if (window.GAC && window.GAC.error) {
                    await window.GAC.error(result.message || 'Error al eliminar', 'Error');
                } else {
                    alert(result.message || 'Error al eliminar');
                }
            }
        } catch (err) {
            btn.disabled = false;
            if (window.GAC && window.GAC.error) {
                await window.GAC.error('Error de conexión', 'Error');
            } else {
                alert('Error de conexión');
            }
        }
    }

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
                initDeleteButtons();
            }
        });
    }

    initDeleteButtons();
})();

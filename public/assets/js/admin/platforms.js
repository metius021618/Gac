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
        if (clearSearchBtn) {
            clearSearchBtn.style.display = searchInput.value.trim() ? 'flex' : 'none';
        }
        
        window.SearchAJAX.init({
            searchInput: searchInput,
            perPageSelect: perPageSelect,
            clearSearchBtn: clearSearchBtn,
            endpoint: window.location.pathname,
            renderCallback: function(html) {
                window.SearchAJAX.updateTableContent(html);
                initToggles();
                initDeleteButtons();
            },
            onSearchComplete: function() {
                console.log('Búsqueda completada');
            }
        });
    }

    // ========================================
    // TOGGLE ACTIVAR/DESACTIVAR PLATAFORMA
    // ========================================
    function initToggles() {
        document.querySelectorAll('.toggle-input').forEach(function(toggle) {
            toggle.removeEventListener('change', handleToggle);
            toggle.addEventListener('change', handleToggle);
        });
    }

    // ========================================
    // ELIMINAR PLATAFORMA
    // ========================================
    function initDeleteButtons() {
        document.querySelectorAll('.btn-delete-platform').forEach(function(btn) {
            btn.removeEventListener('click', handleDeletePlatform);
            btn.addEventListener('click', handleDeletePlatform);
        });
    }

    async function handleDeletePlatform(e) {
        const btn = e.currentTarget;
        const id = parseInt(btn.dataset.id);
        const name = btn.dataset.name || 'esta plataforma';
        if (!id) return;

        try {
            const confirmed = window.GAC && window.GAC.confirm
                ? await window.GAC.confirm('¿Eliminar la plataforma "' + name + '"? Esta acción no se puede deshacer.', 'Eliminar plataforma')
                : confirm('¿Eliminar la plataforma "' + name + '"?');
            if (!confirmed) return;
        } catch (err) {
            return;
        }

        try {
            const response = await fetch('/admin/platforms/delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ id: id })
            });
            const result = await response.json();

            if (result.success) {
                const row = btn.closest('tr');
                if (row) row.remove();
                if (window.GAC && window.GAC.success) {
                    await window.GAC.success(result.message || 'Plataforma eliminada', 'Listo');
                }
            } else {
                if (window.GAC && window.GAC.error) {
                    await window.GAC.error(result.message || 'Error al eliminar', 'Error');
                }
            }
        } catch (err) {
            console.error('Error eliminar:', err);
            if (window.GAC && window.GAC.error) {
                await window.GAC.error('Error de conexión', 'Error');
            }
        }
    }

    async function handleToggle(e) {
        const checkbox = e.target;
        const id = parseInt(checkbox.dataset.id);
        const enabled = checkbox.checked ? 1 : 0;

        try {
            const response = await fetch('/admin/platforms/toggle-status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ id: id, enabled: enabled })
            });
            const result = await response.json();

            if (result.success) {
                // Actualizar badge de estado
                const badge = document.getElementById('statusBadge-' + id);
                if (badge) {
                    badge.textContent = enabled ? 'Activa' : 'Inactiva';
                    badge.className = 'status-badge status-' + (enabled ? 'active' : 'inactive');
                }
            } else {
                checkbox.checked = !checkbox.checked;
                if (window.GAC && window.GAC.error) {
                    await window.GAC.error(result.message || 'Error al actualizar', 'Error');
                }
            }
        } catch (err) {
            checkbox.checked = !checkbox.checked;
            console.error('Error toggle:', err);
        }
    }

    initToggles();
    initDeleteButtons();

    // ========================================
    // MODAL AGREGAR PLATAFORMA
    // ========================================
    const modal = document.getElementById('addPlatformModal');
    const btnOpen = document.getElementById('btnAddPlatform');
    const btnClose = document.getElementById('closeModal');
    const btnCancel = document.getElementById('cancelModal');
    const form = document.getElementById('addPlatformForm');
    const platNameInput = document.getElementById('platName');
    const platSlugInput = document.getElementById('platSlug');

    function openModal() {
        if (modal) {
            modal.style.display = 'flex';
        }
    }
    function closeModal() {
        if (modal) {
            modal.style.display = 'none';
            if (form) form.reset();
            document.querySelectorAll('#addPlatformForm .form-error').forEach(el => { el.textContent = ''; el.style.display = 'none'; });
        }
    }

    if (btnOpen) btnOpen.addEventListener('click', openModal);
    if (btnClose) btnClose.addEventListener('click', closeModal);
    if (btnCancel) btnCancel.addEventListener('click', closeModal);
    if (modal) modal.addEventListener('click', function(e) { if (e.target === modal) closeModal(); });

    // Auto-generar slug desde nombre
    if (platNameInput && platSlugInput) {
        platNameInput.addEventListener('input', function() {
            platSlugInput.value = platNameInput.value.trim()
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_|_$/g, '');
        });
    }

    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const name = platSlugInput.value.trim();
            const displayName = platNameInput.value.trim();
            const enabled = parseInt(document.getElementById('platEnabled').value);

            let hasError = false;
            if (!displayName) {
                document.getElementById('platNameError').textContent = 'El nombre es obligatorio';
                document.getElementById('platNameError').style.display = 'block';
                hasError = true;
            }
            if (!name) {
                document.getElementById('platSlugError').textContent = 'El slug es obligatorio';
                document.getElementById('platSlugError').style.display = 'block';
                hasError = true;
            }
            if (hasError) return;

            try {
                const response = await fetch('/admin/platforms/store', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ name: name, display_name: displayName, enabled: enabled })
                });
                const result = await response.json();

                if (result.success) {
                    closeModal();
                    if (window.GAC && window.GAC.success) {
                        await window.GAC.success(result.message, 'Plataforma Creada');
                    }
                    window.location.reload();
                } else {
                    if (window.GAC && window.GAC.error) {
                        await window.GAC.error(result.message || 'Error al crear', 'Error');
                    }
                }
            } catch (err) {
                console.error('Error:', err);
                if (window.GAC && window.GAC.error) {
                    await window.GAC.error('Error de conexión', 'Error');
                }
            }
        });
    }
})();

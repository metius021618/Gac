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
    
    // Estado: IDs seleccionados se conservan al buscar o cambiar de página
    let isLoading = false;
    let selectedIds = new Set();

    /**
     * Inicialización
     */
    function init() {
        if (emailAccountForm) {
            initForm();
        }
        
        if (getEmailAccountsTable()) {
            initTable();
            initSearch();
        }
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

        // Inicializar selección múltiple
        initBulkSelection();
    }

    /**
     * Tras actualizar la tabla (búsqueda/paginación): si el modo selección múltiple está activo,
     * volver a mostrar las casillas y marcar las que ya estaban seleccionadas.
     */
    function reapplyMultiSelectState() {
        const multiSelectBtn = document.getElementById('multiSelectBtn');
        const table = document.getElementById('emailAccountsTable');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        const selectedCountSpan = document.getElementById('selectedCount');
        if (!multiSelectBtn || !table) return;

        if (!multiSelectBtn.classList.contains('active')) return;

        const cols = table.querySelectorAll('.checkbox-column');
        cols.forEach(col => col.classList.add('show'));

        const rowCheckboxes = table.querySelectorAll('.row-checkbox');
        rowCheckboxes.forEach(cb => {
            const id = parseInt(cb.value, 10);
            cb.checked = selectedIds.has(id);
        });

        const selectAll = document.getElementById('selectAll');
        if (selectAll && rowCheckboxes.length) {
            const checkedHere = Array.from(rowCheckboxes).filter(cb => cb.checked).length;
            selectAll.checked = checkedHere === rowCheckboxes.length;
            selectAll.indeterminate = checkedHere > 0 && checkedHere < rowCheckboxes.length;
        }

        if (selectedIds.size > 0 && bulkDeleteBtn) {
            bulkDeleteBtn.style.display = 'flex';
            if (selectedCountSpan) selectedCountSpan.textContent = selectedIds.size;
        }
    }

    /**
     * Inicializar selección múltiple para eliminación masiva
     */
    function initBulkSelection() {
        const multiSelectBtn = document.getElementById('multiSelectBtn');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        const selectedCountSpan = document.getElementById('selectedCount');

        if (!multiSelectBtn) return;
        if (multiSelectBtn.dataset.bulkListener === 'true') {
            if (multiSelectBtn.classList.contains('active')) initCheckboxEvents();
            return;
        }
        multiSelectBtn.dataset.bulkListener = 'true';

        let multiSelectMode = false;

        function getCheckboxColumns() {
            const table = getEmailAccountsTable();
            return table ? table.querySelectorAll('.checkbox-column') : [];
        }

        function updateSelectAllState() {
            const table = getEmailAccountsTable();
            const checkboxes = table?.querySelectorAll('.row-checkbox');
            const selectAll = document.getElementById('selectAll');
            if (!checkboxes || !selectAll) return;
            checkboxes.forEach(cb => {
                const id = parseInt(cb.value, 10);
                if (cb.checked) selectedIds.add(id); else selectedIds.delete(id);
            });
            const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
            selectAll.checked = checkedCount === checkboxes.length && checkboxes.length > 0;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
        }

        function updateBulkDeleteButton() {
            const table = getEmailAccountsTable();
            const checkboxes = table?.querySelectorAll('.row-checkbox');
            if (checkboxes) {
                checkboxes.forEach(cb => {
                    const id = parseInt(cb.value, 10);
                    if (cb.checked) selectedIds.add(id); else selectedIds.delete(id);
                });
            }
            if (selectedIds.size > 0 && multiSelectMode) {
                if (bulkDeleteBtn) bulkDeleteBtn.style.display = 'flex';
                if (selectedCountSpan) selectedCountSpan.textContent = selectedIds.size;
            } else {
                if (bulkDeleteBtn) bulkDeleteBtn.style.display = 'none';
            }
        }

        multiSelectBtn.addEventListener('click', function() {
            multiSelectMode = !multiSelectMode;
            const checkboxColumns = getCheckboxColumns();

            if (multiSelectMode) {
                this.classList.add('active');
                this.classList.remove('btn-secondary');
                this.classList.add('btn-primary');
                checkboxColumns.forEach(col => col.classList.add('show'));
                initCheckboxEvents();
            } else {
                this.classList.remove('active');
                this.classList.remove('btn-primary');
                this.classList.add('btn-secondary');
                checkboxColumns.forEach(col => col.classList.remove('show'));
                selectedIds.clear();
                const table = getEmailAccountsTable();
                const rowCheckboxes = table?.querySelectorAll('.row-checkbox');
                rowCheckboxes?.forEach(cb => { cb.checked = false; });
                const currentSelectAll = document.getElementById('selectAll');
                if (currentSelectAll) {
                    currentSelectAll.checked = false;
                    currentSelectAll.indeterminate = false;
                }
                if (bulkDeleteBtn) bulkDeleteBtn.style.display = 'none';
            }
        });

        function initCheckboxEvents() {
            const table = getEmailAccountsTable();
            if (!table) return;
            table.addEventListener('change', function(e) {
                if (e.target.id === 'selectAll') {
                    const checkboxes = table.querySelectorAll('.row-checkbox');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = e.target.checked;
                        if (e.target.checked) selectedIds.add(parseInt(checkbox.value, 10));
                        else selectedIds.delete(parseInt(checkbox.value, 10));
                    });
                    updateBulkDeleteButton();
                } else if (e.target.classList.contains('row-checkbox')) {
                    updateSelectAllState();
                    updateBulkDeleteButton();
                }
            });
        }

        if (bulkDeleteBtn) {
            bulkDeleteBtn.addEventListener('click', handleBulkDelete);
        }

        // Si el modo ya estaba activo (p. ej. tras búsqueda), enganchar eventos de checkboxes en la tabla actual
        if (multiSelectBtn.classList.contains('active')) {
            initCheckboxEvents();
        }
    }

    /**
     * Manejar eliminación masiva
     */
    async function handleBulkDelete() {
        const idsToDelete = Array.from(selectedIds);
        if (idsToDelete.length === 0) {
            await window.GAC.warning('Por favor selecciona al menos una cuenta para eliminar', 'Advertencia');
            return;
        }

        try {
            const confirmed = await window.GAC.confirm(
                `¿Estás seguro de eliminar ${idsToDelete.length} cuenta(s)? Esta acción no se puede deshacer.`,
                'Eliminar Múltiples Cuentas'
            );
            if (!confirmed) {
                return;
            }
        } catch (error) {
            console.error('Error al mostrar modal de confirmación:', error);
            return;
        }

        try {
            const response = await fetch('/admin/email-accounts/bulk-delete-ids', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ ids: idsToDelete })
            });

            const result = await response.json();

            if (result.success) {
                selectedIds.clear();
                await window.GAC.success(result.message || 'Cuentas eliminadas correctamente', 'Éxito');
                location.reload();
            } else {
                await window.GAC.error(result.message || 'Error al eliminar las cuentas', 'Error');
            }
        } catch (error) {
            console.error('Error:', error);
            await window.GAC.error('Error de conexión', 'Error');
        }
    }

    /**
     * Inicializar búsqueda AJAX
     */
    function initSearch() {
        if (!searchInput || !perPageSelect) return;

        // Inicializar búsqueda AJAX usando la utilidad común
        if (window.SearchAJAX) {
            window.SearchAJAX.init({
                searchInput: searchInput,
                perPageSelect: perPageSelect,
                clearSearchBtn: clearSearchBtn,
                endpoint: window.location.pathname,
                minSearchLength: 1,
                renderCallback: function(html) {
                    window.SearchAJAX.updateTableContent(html);
                    initTable(); // Re-inicializar eventos de la tabla después de la actualización
                    reapplyMultiSelectState(); // Mantener casillas visibles y selección si el modo estaba activo
                },
                onSearchComplete: function() {
                    // Mostrar/ocultar botón de limpiar
                    if (clearSearchBtn && searchInput.value.trim()) {
                        clearSearchBtn.style.display = 'flex';
                    } else if (clearSearchBtn) {
                        clearSearchBtn.style.display = 'none';
                    }
                }
            });
        } else {
            console.error('SearchAJAX no está disponible');
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

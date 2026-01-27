/**
 * GAC - JavaScript para Gestión de Cuentas de Email
 * Con búsqueda AJAX en tiempo real y paginación
 */

(function() {
    'use strict';

    // Elementos del DOM
    const emailAccountForm = document.getElementById('emailAccountForm');
    const emailAccountsTable = document.getElementById('emailAccountsTable');
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearch');
    const perPageSelect = document.getElementById('perPageSelect');
    const tableContainer = document.querySelector('.table-container');
    
    // Estado
    let isLoading = false;

    /**
     * Inicialización
     */
    function init() {
        if (emailAccountForm) {
            initForm();
        }
        
        if (emailAccountsTable) {
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
        // Botones de toggle status
        const toggleButtons = emailAccountsTable?.querySelectorAll('.btn-toggle');
        toggleButtons?.forEach(btn => {
            btn.removeEventListener('click', handleToggleStatus); // Evitar duplicados
            btn.addEventListener('click', handleToggleStatus);
        });

        // Botones de eliminar
        const deleteButtons = emailAccountsTable?.querySelectorAll('.btn-delete');
        deleteButtons?.forEach(btn => {
            btn.removeEventListener('click', handleDelete); // Evitar duplicados
            btn.addEventListener('click', handleDelete);
        });

        // Inicializar selección múltiple
        initBulkSelection();
    }

    /**
     * Inicializar selección múltiple para eliminación masiva
     */
    function initBulkSelection() {
        const selectAllCheckbox = document.getElementById('selectAll');
        const rowCheckboxes = emailAccountsTable?.querySelectorAll('.row-checkbox');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        const selectedCountSpan = document.getElementById('selectedCount');

        if (!selectAllCheckbox || !rowCheckboxes || !bulkDeleteBtn) return;

        // Seleccionar/deseleccionar todos
        selectAllCheckbox.addEventListener('change', function() {
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkDeleteButton();
        });

        // Actualizar botón cuando cambian los checkboxes individuales
        rowCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateSelectAllState();
                updateBulkDeleteButton();
            });
        });

        // Manejar eliminación masiva
        bulkDeleteBtn.addEventListener('click', handleBulkDelete);

        /**
         * Actualizar estado del checkbox "Seleccionar todos"
         */
        function updateSelectAllState() {
            const checkedCount = Array.from(rowCheckboxes).filter(cb => cb.checked).length;
            selectAllCheckbox.checked = checkedCount === rowCheckboxes.length && rowCheckboxes.length > 0;
            selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
        }

        /**
         * Actualizar visibilidad y contador del botón de eliminación masiva
         */
        function updateBulkDeleteButton() {
            const selectedIds = Array.from(rowCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => parseInt(cb.value));

            if (selectedIds.length > 0) {
                bulkDeleteBtn.style.display = 'flex';
                if (selectedCountSpan) {
                    selectedCountSpan.textContent = selectedIds.length;
                }
            } else {
                bulkDeleteBtn.style.display = 'none';
            }
        }
    }

    /**
     * Manejar eliminación masiva
     */
    async function handleBulkDelete() {
        const rowCheckboxes = emailAccountsTable?.querySelectorAll('.row-checkbox:checked');
        if (!rowCheckboxes || rowCheckboxes.length === 0) {
            return;
        }

        const selectedIds = Array.from(rowCheckboxes).map(cb => parseInt(cb.value));

        try {
            const confirmed = await window.GAC.confirm(
                `¿Estás seguro de eliminar ${selectedIds.length} cuenta(s)? Esta acción no se puede deshacer.`,
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
            const response = await fetch('/admin/email-accounts/bulk-delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ ids: selectedIds })
            });

            const result = await response.json();

            if (result.success) {
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
                renderCallback: function(html) {
                    window.SearchAJAX.updateTableContent(html);
                    initTable(); // Re-inicializar eventos de la tabla después de la actualización
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

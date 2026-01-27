/**
 * GAC - JavaScript para Gestión de Cuentas de Email
 * Con búsqueda, paginación y funcionalidades mejoradas
 */

(function() {
    'use strict';

    // Elementos del DOM
    const emailAccountForm = document.getElementById('emailAccountForm');
    const emailAccountsTable = document.getElementById('emailAccountsTable');
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearch');
    const perPageSelect = document.getElementById('perPageSelect');
    const tableBody = document.getElementById('tableBody');
    const tableContainer = document.querySelector('.table-container');
    
    // Estado
    let searchTimeout = null;
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
            initPagination();
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
            btn.addEventListener('click', handleToggleStatus);
        });

        // Botones de eliminar
        const deleteButtons = emailAccountsTable?.querySelectorAll('.btn-delete');
        deleteButtons?.forEach(btn => {
            btn.addEventListener('click', handleDelete);
        });
    }

    /**
     * Inicializar búsqueda
     */
    function initSearch() {
        if (!searchInput) return;

        // Mostrar/ocultar botón de limpiar
        searchInput.addEventListener('input', function() {
            if (this.value.trim()) {
                clearSearchBtn.style.display = 'flex';
            } else {
                clearSearchBtn.style.display = 'none';
            }
        });

        // Búsqueda con debounce
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch();
            }, 500); // Esperar 500ms después de que el usuario deje de escribir
        });

        // Limpiar búsqueda
        clearSearchBtn?.addEventListener('click', function() {
            searchInput.value = '';
            clearSearchBtn.style.display = 'none';
            performSearch();
        });

        // Búsqueda al presionar Enter
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimeout);
                performSearch();
            }
        });
    }

    /**
     * Inicializar paginación
     */
    function initPagination() {
        if (!perPageSelect) return;

        // Cambiar cantidad por página (AJAX)
        perPageSelect.addEventListener('change', async function() {
            if (isLoading) return;
            
            isLoading = true;
            const params = {
                search: searchInput?.value.trim() || '',
                page: 1,
                per_page: this.value
            };

            try {
                const url = new URL(window.location.pathname, window.location.origin);
                Object.keys(params).forEach(key => {
                    if (params[key] !== null && params[key] !== '') {
                        url.searchParams.set(key, params[key]);
                    }
                });

                const response = await fetch(url.toString(), {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    }
                });

                if (response.ok) {
                    const html = await response.text();
                    window.history.pushState({}, '', url.toString());
                    updateTableContent(html);
                    initTable();
                }
            } catch (error) {
                console.error('Error:', error);
            } finally {
                isLoading = false;
            }
        });

        // Botones de paginación (AJAX con delegación de eventos)
        document.addEventListener('click', async function(e) {
            const paginationBtn = e.target.closest('.pagination-btn, .pagination-page, .pagination-controls button[data-page]');
            if (paginationBtn && paginationBtn.dataset.page && !paginationBtn.disabled && !paginationBtn.classList.contains('active')) {
                e.preventDefault();
                if (isLoading) return;
                
                isLoading = true;
                const page = paginationBtn.dataset.page;
                const params = {
                    search: searchInput?.value.trim() || '',
                    page: page,
                    per_page: perPageSelect?.value || '15'
                };

                try {
                    const url = new URL(window.location.pathname, window.location.origin);
                    Object.keys(params).forEach(key => {
                        if (params[key] !== null && params[key] !== '') {
                            url.searchParams.set(key, params[key]);
                        }
                    });

                    const response = await fetch(url.toString(), {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html'
                        }
                    });

                    if (response.ok) {
                        const html = await response.text();
                        window.history.pushState({}, '', url.toString());
                        updateTableContent(html);
                        initTable();
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                } catch (error) {
                    console.error('Error:', error);
                } finally {
                    isLoading = false;
                }
            }
        });
    }

    /**
     * Realizar búsqueda AJAX
     */
    async function performSearch() {
        if (isLoading) return;

        isLoading = true;
        const search = searchInput.value.trim();
        const params = {
            search: search,
            page: 1,
            per_page: perPageSelect?.value || '15'
        };

        try {
            const url = new URL(window.location.pathname, window.location.origin);
            Object.keys(params).forEach(key => {
                if (params[key] !== null && params[key] !== '') {
                    url.searchParams.set(key, params[key]);
                }
            });

            const response = await fetch(url.toString(), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            });

            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }

            const html = await response.text();
            
            // Actualizar URL sin recargar
            window.history.pushState({}, '', url.toString());
            
            // Actualizar contenido
            updateTableContent(html);
            
            // Re-inicializar eventos de la tabla
            initTable();
            
        } catch (error) {
            console.error('Error en búsqueda:', error);
            // Fallback: recargar página
            window.location.href = url.toString();
        } finally {
            isLoading = false;
        }
    }

    /**
     * Actualizar contenido de la tabla
     */
    function updateTableContent(html) {
        const temp = document.createElement('div');
        temp.innerHTML = html;

        const newTable = temp.querySelector('.table-container');
        const newPagination = temp.querySelector('.pagination-container');

        if (newTable && tableContainer) {
            tableContainer.innerHTML = newTable.innerHTML;
        }

        if (newPagination) {
            const currentPagination = document.querySelector('.pagination-container');
            if (currentPagination) {
                currentPagination.innerHTML = newPagination.innerHTML;
            }
        }

        // Re-inicializar paginación
        initPagination();
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

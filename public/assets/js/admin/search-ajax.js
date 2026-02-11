/**
 * GAC - Utilidad AJAX para Búsqueda y Paginación
 * Funciones reutilizables para búsqueda en tiempo real
 */

(function() {
    'use strict';

    window.SearchAJAX = {
        /**
         * Realizar búsqueda AJAX
         * @param {string} endpoint - URL base del endpoint
         * @param {Object} params - Parámetros de búsqueda
         * @param {Function} renderCallback - Función para renderizar los resultados
         */
        async performSearch(endpoint, params, renderCallback) {
            try {
                const url = new URL(endpoint, window.location.origin);
                Object.keys(params).forEach(key => {
                    const val = params[key];
                    if (val !== null && val !== undefined) {
                        url.searchParams.set(key, val === '' ? '' : String(val));
                    }
                });

                const urlStr = url.toString();
                console.log('[SearchAJAX] GET', urlStr);

                const response = await fetch(urlStr, {
                    method: 'GET',
                    cache: 'no-store',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    }
                });

                console.log('[SearchAJAX] status=', response.status, 'content-type=', response.headers.get('content-type'));

                if (!response.ok) {
                    const text = await response.text();
                    console.error('[SearchAJAX] error response:', text.substring(0, 500));
                    throw new Error('Error en la respuesta del servidor: ' + response.status);
                }

                const html = await response.text();
                console.log('[SearchAJAX] html length=', html.length, 'has .admin-content=', html.indexOf('admin-content') !== -1);

                // Actualizar URL sin recargar
                window.history.pushState({}, '', urlStr);

                // Renderizar resultados
                if (renderCallback) {
                    renderCallback(html);
                } else {
                    this.updateTableContent(html);
                }

                return true;
            } catch (error) {
                console.error('[SearchAJAX] Error:', error);
                return false;
            }
        },

        /**
         * Actualizar contenido de la tabla desde HTML
         */
        updateTableContent(html) {
            const temp = document.createElement('div');
            temp.innerHTML = html;
            const adminContent = temp.querySelector('.admin-content') || temp;
            const newTable = adminContent.querySelector('.table-container');
            const newPagination = adminContent.querySelector('.pagination-container');
            const currentTable = document.querySelector('.admin-content .table-container') || document.querySelector('.table-container');
            const currentPagination = document.querySelector('.admin-content .pagination-container') || document.querySelector('.pagination-container');
            console.log('[SearchAJAX] updateTableContent: newTable=', !!newTable, 'currentTable=', !!currentTable, 'newPagination=', !!newPagination, 'currentPagination=', !!currentPagination);
            if (newTable && currentTable) currentTable.innerHTML = newTable.innerHTML;
            if (newPagination && currentPagination) currentPagination.innerHTML = newPagination.innerHTML;
            if (this.reinitializeTableEvents) this.reinitializeTableEvents();
        },

        /**
         * Re-inicializar eventos después de actualizar el contenido
         * Este método puede ser sobrescrito por cada vista específica
         */
        reinitializeTableEvents() {
            // Por defecto no hace nada, cada vista puede sobrescribir esto
        },

        /**
         * Inicializar búsqueda con AJAX
         * @param {Object} config - Configuración
         */
        init(config) {
            const {
                searchInput,
                perPageSelect,
                clearSearchBtn,
                endpoint,
                renderCallback,
                onSearchComplete,
                minSearchLength = 3
            } = config;

            let searchTimeout = null;
            let isLoading = false;
            const minLen = typeof minSearchLength === 'number' ? minSearchLength : 3;

            // Búsqueda con debounce
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    const searchValue = this.value.trim();
                    
                    // Mostrar/ocultar botón de limpiar
                    if (clearSearchBtn) {
                        if (searchValue) {
                            clearSearchBtn.style.display = 'flex';
                        } else {
                            clearSearchBtn.style.display = 'none';
                        }
                    }
                    
                    // Buscar si hay minSearchLength caracteres o está vacío (para mostrar todos)
                    searchTimeout = setTimeout(() => {
                        if (!isLoading) {
                            if (searchValue.length > 0 && searchValue.length < minLen) {
                                console.log('[SearchAJAX] skip: search length', searchValue.length, '< minLen', minLen);
                                return;
                            }

                            console.log('[SearchAJAX] firing search:', { search: searchValue, endpoint });
                            isLoading = true;
                            const params = {
                                search: searchValue,
                                page: 1,
                                per_page: perPageSelect?.value || 15
                            };

                            window.SearchAJAX.performSearch(endpoint, params, renderCallback)
                                .then(() => {
                                    if (onSearchComplete) onSearchComplete();
                                })
                                .finally(() => {
                                    isLoading = false;
                                });
                        }
                    }, 500);
                });
            }

            // Limpiar búsqueda
            if (clearSearchBtn) {
                clearSearchBtn.addEventListener('click', function() {
                    searchInput.value = '';
                    if (clearSearchBtn.style) {
                        clearSearchBtn.style.display = 'none';
                    }
                    
                    const params = {
                        search: '',
                        page: 1,
                        per_page: perPageSelect?.value || 15
                    };
                    
                    isLoading = true;
                    window.SearchAJAX.performSearch(endpoint, params, renderCallback)
                        .then(() => {
                            if (onSearchComplete) onSearchComplete();
                        })
                        .finally(() => {
                            isLoading = false;
                        });
                });
            }

            // Cambiar cantidad por página
            if (perPageSelect) {
                perPageSelect.addEventListener('change', function() {
                    const params = {
                        search: searchInput?.value.trim() || '',
                        page: 1,
                        per_page: this.value
                    };
                    
                    isLoading = true;
                    window.SearchAJAX.performSearch(endpoint, params, renderCallback)
                        .then(() => {
                            if (onSearchComplete) onSearchComplete();
                        })
                        .finally(() => {
                            isLoading = false;
                        });
                });
            }

            // Paginación (delegación de eventos)
            document.addEventListener('click', function(e) {
                // Buscar botones de paginación con múltiples selectores
                const paginationBtn = e.target.closest('.pagination-controls button[data-page], .pagination-btn[data-page], .pagination-page[data-page]');
                if (paginationBtn && !paginationBtn.disabled) {
                    e.preventDefault();
                    e.stopPropagation();
                    const page = parseInt(paginationBtn.dataset.page);
                    if (!page || isNaN(page) || page < 1) return;
                    
                    const params = {
                        search: searchInput?.value.trim() || '',
                        page: page,
                        per_page: perPageSelect?.value || 15
                    };
                    
                    isLoading = true;
                    window.SearchAJAX.performSearch(endpoint, params, renderCallback)
                        .then(() => {
                            if (onSearchComplete) onSearchComplete();
                            // Scroll suave hacia arriba
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        })
                        .finally(() => {
                            isLoading = false;
                        });
                }
            });
        }
    };
})();

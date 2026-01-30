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
                
                // Renderizar resultados
                if (renderCallback) {
                    renderCallback(html);
                } else {
                    // Por defecto, reemplazar el contenido de la tabla
                    this.updateTableContent(html);
                }

                return true;
            } catch (error) {
                console.error('Error en búsqueda AJAX:', error);
                return false;
            }
        },

        /**
         * Actualizar contenido de la tabla desde HTML
         */
        updateTableContent(html) {
            // Crear un elemento temporal para parsear el HTML
            const temp = document.createElement('div');
            temp.innerHTML = html;

            // Intentar extraer del admin-content primero
            let adminContent = temp.querySelector('.admin-content');
            if (!adminContent) {
                // Si no hay admin-content, buscar directamente en el body
                adminContent = temp.querySelector('body') || temp;
            }

            const newTable = adminContent.querySelector('.table-container');
            const newPagination = adminContent.querySelector('.pagination-container');

            // Actualizar tabla
            const currentTable = document.querySelector('.table-container');
            if (newTable && currentTable) {
                currentTable.innerHTML = newTable.innerHTML;
            } else if (newTable) {
                // Si no existe la tabla actual, crear el contenedor
                const tableContainer = document.querySelector('.admin-content .table-container') || 
                                      document.querySelector('.table-container');
                if (tableContainer) {
                    tableContainer.innerHTML = newTable.innerHTML;
                }
            }

            // Actualizar paginación
            const currentPagination = document.querySelector('.pagination-container');
            if (newPagination && currentPagination) {
                currentPagination.innerHTML = newPagination.innerHTML;
            } else if (newPagination) {
                // Si no existe la paginación actual, buscar donde insertarla
                const paginationContainer = document.querySelector('.admin-content .pagination-container') ||
                                           document.querySelector('.pagination-container');
                if (paginationContainer) {
                    paginationContainer.innerHTML = newPagination.innerHTML;
                }
            }

            // Re-inicializar eventos de la tabla (si existe callback personalizado)
            if (this.reinitializeTableEvents) {
                this.reinitializeTableEvents();
            }
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
                                return;
                            }
                            
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

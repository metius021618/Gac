/**
 * GAC - JavaScript Principal
 * Funciones y utilidades globales
 */

(function() {
    'use strict';

    // Utilidades
    const GAC = {
        /**
         * Mostrar notificación
         */
        notify: function(message, type = 'info', duration = 3000) {
            // Implementar sistema de notificaciones si es necesario
            console.log(`[${type.toUpperCase()}] ${message}`);
        },

        /**
         * Copiar texto al portapapeles
         */
        copyToClipboard: function(text) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text).then(() => {
                    return true;
                }).catch(() => {
                    return this.fallbackCopyToClipboard(text);
                });
            } else {
                return Promise.resolve(this.fallbackCopyToClipboard(text));
            }
        },

        /**
         * Fallback para copiar al portapapeles
         */
        fallbackCopyToClipboard: function(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                document.body.removeChild(textArea);
                return successful;
            } catch (err) {
                document.body.removeChild(textArea);
                return false;
            }
        },

        /**
         * Validar email
         */
        validateEmail: function(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },

        /**
         * Formatear respuesta de error
         */
        formatError: function(error) {
            if (typeof error === 'string') {
                return error;
            }
            if (error && error.message) {
                return error.message;
            }
            return 'Ha ocurrido un error inesperado';
        },

        /**
         * Inicializar menú de usuario
         */
        initUserMenu: function() {
            const trigger = document.getElementById('userMenuTrigger');
            const dropdown = document.getElementById('userMenuDropdown');
            const logoutItem = document.getElementById('logoutMenuItem');

            if (!trigger || !dropdown) {
                return;
            }

            // Toggle dropdown
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.classList.toggle('hidden');
                trigger.classList.toggle('active');
            });

            // Cerrar al hacer click fuera
            document.addEventListener('click', function(e) {
                if (!trigger.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.add('hidden');
                    trigger.classList.remove('active');
                }
            });

            // Manejar logout
            if (logoutItem) {
                logoutItem.addEventListener('click', async function(e) {
                    e.preventDefault();
                    try {
                        const confirmed = await window.GAC.confirm('¿Estás seguro de cerrar sesión?', 'Cerrar Sesión');
                        if (confirmed) {
                            window.location.href = '/logout';
                        }
                    } catch (error) {
                        console.error('Error al mostrar modal de confirmación:', error);
                    }
                });
            }
        }
    };

    // Exponer globalmente - extender en lugar de sobrescribir
    if (!window.GAC) {
        window.GAC = {};
    }
    // Agregar métodos de main.js al objeto GAC existente
    Object.assign(window.GAC, GAC);

    // Inicialización cuando el DOM esté listo
    function init() {
        console.log('GAC System Loaded');
        if (window.GAC && window.GAC.initUserMenu) {
            window.GAC.initUserMenu();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

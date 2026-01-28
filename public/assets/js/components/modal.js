/**
 * GAC - Sistema de Modales y Popups
 * Reemplazo de alertas nativas del navegador
 */

(function() {
    'use strict';

    /**
     * Clase Modal para crear popups estilizados
     */
    class Modal {
        constructor(options = {}) {
            this.options = {
                title: options.title || 'Información',
                message: options.message || '',
                type: options.type || 'info', // info, success, warning, danger, confirm
                showClose: options.showClose !== false,
                buttons: options.buttons || [],
                onClose: options.onClose || null,
                ...options
            };
            this.overlay = null;
            this.container = null;
            this.resolve = null;
            this.reject = null;
        }

        /**
         * Crear el HTML del modal
         */
        createHTML() {
            const icon = this.getIcon();
            const buttons = this.getButtons();

            return `
                <div class="modal-overlay" id="modalOverlay" style="display: flex !important;">
                    <div class="modal-container modal-${this.options.type}">
                        <div class="modal-header">
                            <h3 class="modal-title">
                                ${icon}
                                <span>${this.escapeHtml(this.options.title)}</span>
                            </h3>
                            ${this.options.showClose ? `
                                <button class="modal-close" id="modalClose">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                </button>
                            ` : ''}
                        </div>
                        <div class="modal-body">
                            <p class="modal-message">${this.escapeHtml(this.options.message)}</p>
                        </div>
                        <div class="modal-footer">
                            ${buttons}
                        </div>
                    </div>
                </div>
            `;
        }

        /**
         * Obtener icono según el tipo
         */
        getIcon() {
            const icons = {
                info: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>`,
                success: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>`,
                warning: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>`,
                danger: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>`,
                confirm: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>`
            };
            return icons[this.options.type] || icons.info;
        }

        /**
         * Obtener botones según el tipo
         */
        getButtons() {
            if (this.options.buttons.length > 0) {
                return this.options.buttons.map((btn, index) => {
                    const className = btn.className || 'modal-button-secondary';
                    return `
                        <button class="modal-button ${className}" data-action="${btn.action || 'close'}" data-index="${index}">
                            ${btn.icon || ''}
                            <span>${this.escapeHtml(btn.text)}</span>
                        </button>
                    `;
                }).join('');
            }

            // Botones por defecto según el tipo
            if (this.options.type === 'confirm') {
                return `
                    <button class="modal-button modal-button-secondary" data-action="cancel">
                        <span>Cancelar</span>
                    </button>
                    <button class="modal-button modal-button-danger" data-action="confirm">
                        <span>Confirmar</span>
                    </button>
                `;
            }

            return `
                <button class="modal-button modal-button-primary" data-action="close">
                    <span>Aceptar</span>
                </button>
            `;
        }

        /**
         * Escapar HTML para prevenir XSS
         */
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        /**
         * Mostrar el modal
         */
        show() {
            return new Promise((resolve, reject) => {
                this.resolve = resolve;
                this.reject = reject;

                // Crear y agregar al DOM
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = this.createHTML();
                this.overlay = tempDiv.firstElementChild;
                
                if (!this.overlay) {
                    console.error('Error: No se pudo crear el overlay del modal');
                    reject(new Error('No se pudo crear el modal'));
                    return;
                }
                
                document.body.appendChild(this.overlay);
                
                // Prevenir scroll del body cuando el modal está abierto
                const originalOverflow = document.body.style.overflow;
                document.body.style.overflow = 'hidden';
                this.originalOverflow = originalOverflow;
                
                // Forzar display flex y dimensiones completas para asegurar visibilidad
                this.overlay.style.display = 'flex';
                this.overlay.style.opacity = '1';
                this.overlay.style.position = 'fixed';
                this.overlay.style.top = '0';
                this.overlay.style.left = '0';
                this.overlay.style.width = '100vw';
                this.overlay.style.height = '100vh';
                this.overlay.style.minHeight = '100vh';
                this.overlay.style.maxHeight = '100vh';

                // Obtener referencias
                this.container = this.overlay.querySelector('.modal-container');
                const closeBtn = this.overlay.querySelector('#modalClose');
                const buttons = this.overlay.querySelectorAll('.modal-button');

                // Event listeners
                if (closeBtn) {
                    closeBtn.addEventListener('click', () => this.close(false));
                }

                buttons.forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const action = btn.dataset.action;
                        const index = btn.dataset.index;

                        if (action === 'confirm') {
                            this.close(true);
                        } else if (action === 'cancel') {
                            this.close(false);
                        } else if (action === 'close') {
                            this.close(true);
                        } else if (index !== undefined && this.options.buttons[index]) {
                            const buttonConfig = this.options.buttons[index];
                            if (buttonConfig.onClick) {
                                buttonConfig.onClick();
                            }
                            if (buttonConfig.close !== false) {
                                this.close(buttonConfig.result !== false);
                            }
                        }
                    });
                });

                // Cerrar al hacer click fuera del modal
                this.overlay.addEventListener('click', (e) => {
                    if (e.target === this.overlay && this.options.closeOnOverlay !== false) {
                        this.close(false);
                    }
                });

                // Cerrar con ESC
                this.escapeHandler = (e) => {
                    if (e.key === 'Escape' && this.options.closeOnEscape !== false) {
                        this.close(false);
                    }
                };
                document.addEventListener('keydown', this.escapeHandler);

                // Focus en el primer botón
                if (buttons.length > 0) {
                    setTimeout(() => buttons[0].focus(), 100);
                }
            });
        }

        /**
         * Cerrar el modal
         */
        close(result = true) {
            if (!this.overlay) return;

            // Remover event listener de ESC
            if (this.escapeHandler) {
                document.removeEventListener('keydown', this.escapeHandler);
            }

            // Animación de salida
            this.container.style.animation = 'modalSlideOut 0.2s ease forwards';
            this.overlay.style.animation = 'fadeOut 0.2s ease forwards';

            setTimeout(() => {
                // Restaurar scroll del body
                if (this.originalOverflow !== undefined) {
                    document.body.style.overflow = this.originalOverflow || '';
                } else {
                    document.body.style.overflow = '';
                }
                
                if (this.overlay && this.overlay.parentNode) {
                    this.overlay.parentNode.removeChild(this.overlay);
                }
                this.overlay = null;
                this.container = null;

                if (this.resolve) {
                    this.resolve(result);
                }

                if (this.options.onClose) {
                    this.options.onClose(result);
                }
            }, 200);
        }
    }

    // Agregar animaciones de salida al CSS dinámicamente
    if (!document.getElementById('modal-animations')) {
        const style = document.createElement('style');
        style.id = 'modal-animations';
        style.textContent = `
            @keyframes modalSlideOut {
                from {
                    transform: scale(1) translateY(0);
                    opacity: 1;
                }
                to {
                    transform: scale(0.9) translateY(-20px);
                    opacity: 0;
                }
            }
            @keyframes fadeOut {
                from {
                    opacity: 1;
                }
                to {
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }

    // API pública
    window.Modal = Modal;

    /**
     * Funciones de conveniencia
     * Asegurar que window.GAC existe antes de agregar métodos
     */
    if (!window.GAC) {
        window.GAC = {};
    }

    window.GAC.alert = function(message, title = 'Información') {
        return new Modal({
            title: title,
            message: message,
            type: 'info'
        }).show();
    };

    window.GAC.confirm = function(message, title = 'Confirmar') {
        return new Modal({
            title: title,
            message: message,
            type: 'confirm'
        }).show();
    };

    window.GAC.success = function(message, title = 'Éxito') {
        return new Modal({
            title: title,
            message: message,
            type: 'success'
        }).show();
    };

    window.GAC.warning = function(message, title = 'Advertencia') {
        return new Modal({
            title: title,
            message: message,
            type: 'warning'
        }).show();
    };

    window.GAC.error = function(message, title = 'Error') {
        return new Modal({
            title: title,
            message: message,
            type: 'danger'
        }).show();
    };
})();

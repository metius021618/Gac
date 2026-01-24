/**
 * GAC - JavaScript del Dashboard
 */

(function() {
    'use strict';

    // Asegurar que los enlaces de los stat-cards funcionen correctamente
    document.addEventListener('DOMContentLoaded', function() {
        const statCardLinks = document.querySelectorAll('.stat-card-link');
        
        statCardLinks.forEach(function(link) {
            // Asegurar que el enlace tenga cursor pointer
            link.style.cursor = 'pointer';
            
            // Agregar listener para debug (opcional, remover en producción)
            link.addEventListener('click', function(e) {
                // No prevenir el comportamiento por defecto, dejar que el enlace funcione
                console.log('Navegando a:', this.href);
            });
        });
    });

    // El menú de usuario se maneja desde main.js
    // Este archivo se mantiene por si se necesita lógica específica del dashboard
})();

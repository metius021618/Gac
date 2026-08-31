/**
 * GAC - Selector Código temporal / Actualizar hogar (sin recargar ni cambiar URL)
 * La URL permanece siempre en /hogar.
 */
(function () {
    'use strict';

    var MODE_META = {
        hogar: {
            title: 'Consulta tu código Netflix'
        },
        viaje: {
            title: 'Actualizar hogar'
        }
    };

    function getActiveMode(root) {
        var mode = (root && root.getAttribute('data-active')) || 'hogar';
        return mode === 'viaje' ? 'viaje' : 'hogar';
    }

    function applyMode(root, mode) {
        if (!root || !MODE_META[mode]) return;

        root.setAttribute('data-active', mode);
        var options = root.querySelectorAll('.hogar-mode-option');
        options.forEach(function (o) {
            var on = o.getAttribute('data-mode') === mode;
            o.classList.toggle('is-active', on);
            o.setAttribute('aria-selected', on ? 'true' : 'false');
        });

        var titleEl = document.getElementById('consultCardTitle');
        if (titleEl) {
            titleEl.textContent = MODE_META[mode].title;
        }

        document.dispatchEvent(new CustomEvent('hogarConsultModeChange', { detail: { mode: mode } }));
    }

    function initModeSwitch() {
        var root = document.querySelector('.hogar-mode-switch');
        if (!root) return;

        var initial = root.getAttribute('data-initial-mode') || getActiveMode(root);
        applyMode(root, initial === 'viaje' ? 'viaje' : 'hogar');

        var options = root.querySelectorAll('.hogar-mode-option');
        options.forEach(function (opt) {
            opt.addEventListener('click', function () {
                var mode = opt.getAttribute('data-mode');
                if (!mode || mode === getActiveMode(root)) return;
                applyMode(root, mode === 'viaje' ? 'viaje' : 'hogar');
            });
        });
    }

    window.HogarModeSwitch = {
        getActiveMode: function () {
            var root = document.querySelector('.hogar-mode-switch');
            return getActiveMode(root);
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModeSwitch);
    } else {
        initModeSwitch();
    }
})();

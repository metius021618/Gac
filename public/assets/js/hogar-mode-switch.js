/**
 * GAC - Selector segmentado Código temporal / Actualizar hogar (sin recargar página)
 */
(function () {
    'use strict';

    var MODE_META = {
        hogar: {
            title: 'Consulta tu código Netflix',
            path: '/hogar'
        },
        viaje: {
            title: 'Actualizar hogar',
            path: '/MViaje'
        }
    };

    function getActiveMode(root) {
        var mode = (root && root.getAttribute('data-active')) || 'hogar';
        return mode === 'viaje' ? 'viaje' : 'hogar';
    }

    function applyMode(root, mode, updateUrl) {
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

        if (updateUrl !== false && window.history && window.history.replaceState) {
            var targetPath = MODE_META[mode].path;
            if (window.location.pathname !== targetPath) {
                window.history.replaceState({ consultMode: mode }, '', targetPath);
            }
        }

        document.dispatchEvent(new CustomEvent('hogarConsultModeChange', { detail: { mode: mode } }));
    }

    function initModeSwitch() {
        var root = document.querySelector('.hogar-mode-switch');
        if (!root) return;

        var initial = root.getAttribute('data-initial-mode') || getActiveMode(root);
        applyMode(root, initial === 'viaje' ? 'viaje' : 'hogar', false);

        var options = root.querySelectorAll('.hogar-mode-option');
        options.forEach(function (opt) {
            opt.addEventListener('click', function () {
                var mode = opt.getAttribute('data-mode');
                if (!mode || mode === getActiveMode(root)) return;
                applyMode(root, mode === 'viaje' ? 'viaje' : 'hogar', true);
            });
        });

        window.addEventListener('popstate', function () {
            var path = window.location.pathname || '';
            var mode = path.toLowerCase().indexOf('/mviaje') !== -1 ? 'viaje' : 'hogar';
            applyMode(root, mode, false);
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

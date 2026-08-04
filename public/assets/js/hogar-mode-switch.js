/**
 * GAC - Selector segmentado Hogar / Modo Viaje
 * Fondo deslizante + navegación tras la animación.
 */
(function () {
    'use strict';

    function initModeSwitch() {
        var root = document.querySelector('.hogar-mode-switch');
        if (!root) return;

        var options = root.querySelectorAll('.hogar-mode-option');
        var navigating = false;

        options.forEach(function (opt) {
            opt.addEventListener('click', function (e) {
                var mode = opt.getAttribute('data-mode');
                var href = opt.getAttribute('href');
                if (!mode || !href) return;

                if (opt.classList.contains('is-active') || root.getAttribute('data-active') === mode) {
                    e.preventDefault();
                    return;
                }

                e.preventDefault();
                if (navigating) return;
                navigating = true;

                root.setAttribute('data-active', mode);
                options.forEach(function (o) {
                    var on = o.getAttribute('data-mode') === mode;
                    o.classList.toggle('is-active', on);
                    o.setAttribute('aria-selected', on ? 'true' : 'false');
                });

                window.setTimeout(function () {
                    window.location.href = href;
                }, 300);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModeSwitch);
    } else {
        initModeSwitch();
    }
})();

/**
 * GAC - Actividad de administrador (superadmin)
 * Per page, filtros por hover, modal rango de fechas.
 */
(function() {
    'use strict';

    const perPageSelect = document.getElementById('perPageSelect');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            const params = new URLSearchParams(window.location.search);
            params.set('per_page', this.value);
            params.set('page', '1');
            window.location.href = '/admin/user-activity?' + params.toString();
        });
    }

    // Filtros: mantener menú abierto al bajar el ratón (evitar cierre al cruzar el hueco)
    function scheduleClose(drop) {
        if (drop._closeTimeout) clearTimeout(drop._closeTimeout);
        drop._closeTimeout = setTimeout(function() {
            drop.classList.remove('open');
            drop._closeTimeout = null;
        }, 120);
    }
    function cancelClose(drop) {
        if (drop._closeTimeout) {
            clearTimeout(drop._closeTimeout);
            drop._closeTimeout = null;
        }
    }
    document.querySelectorAll('.activity-filter-dropdown').forEach(function(drop) {
        if (drop.id === 'timeFilterDropdown') return;
        drop.addEventListener('mouseenter', function() {
            cancelClose(this);
            this.classList.add('open');
        });
        drop.addEventListener('mouseleave', function() {
            scheduleClose(this);
        });
        var menu = drop.querySelector('.activity-filter-menu');
        if (menu) {
            menu.addEventListener('mouseenter', function() {
                cancelClose(drop);
                drop.classList.add('open');
            });
            menu.addEventListener('mouseleave', function() {
                scheduleClose(drop);
            });
        }
    });
    // Filtro Tiempo: mismo comportamiento hover + click para "Personalizado"
    var timeDrop = document.getElementById('timeFilterDropdown');
    if (timeDrop) {
        timeDrop.addEventListener('mouseenter', function() {
            cancelClose(this);
            this.classList.add('open');
        });
        timeDrop.addEventListener('mouseleave', function() {
            scheduleClose(this);
        });
        var timeMenu = timeDrop.querySelector('.activity-filter-menu');
        if (timeMenu) {
            timeMenu.addEventListener('mouseenter', function() {
                cancelClose(timeDrop);
                timeDrop.classList.add('open');
            });
            timeMenu.addEventListener('mouseleave', function() {
                scheduleClose(timeDrop);
            });
        }
        timeDrop.addEventListener('click', function(e) {
            if (e.target.closest('.activity-filter-menu')) return;
            this.classList.toggle('open');
        });
    }
    document.addEventListener('click', function(e) {
        if (timeDrop && !timeDrop.contains(e.target)) timeDrop.classList.remove('open');
    });

    // Personalizado: abrir modal de rango de fechas
    const customLink = document.getElementById('timeFilterCustom');
    const modal = document.getElementById('activityDateRangeModal');
    const closeBtn = document.getElementById('closeActivityDateModal');
    const overlay = modal && modal.querySelector('.modal-overlay');
    const applyBtn = document.getElementById('activityDateRangeApply');
    const inputFrom = document.getElementById('activityDateFrom');
    const inputTo = document.getElementById('activityDateTo');

    if (customLink) {
        customLink.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var drop = document.getElementById('timeFilterDropdown');
            if (drop) drop.classList.remove('open');
            if (modal) modal.classList.remove('hidden');
            var today = new Date().toISOString().slice(0, 10);
            var sixMonths = new Date();
            sixMonths.setMonth(sixMonths.getMonth() - 6);
            var defaultFrom = sixMonths.toISOString().slice(0, 10);
            if (inputFrom) inputFrom.value = defaultFrom;
            if (inputTo) inputTo.value = today;
        });
    }
    function closeModal() {
        if (modal) modal.classList.add('hidden');
    }
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (overlay) overlay.addEventListener('click', closeModal);
    if (applyBtn && inputFrom && inputTo) {
        applyBtn.addEventListener('click', function() {
            var from = inputFrom.value;
            var to = inputTo.value;
            if (!from || !to) return;
            var params = new URLSearchParams(window.location.search);
            params.set('date_from', from);
            params.set('date_to', to);
            params.set('time_range', 'custom');
            params.set('page', '1');
            window.location.href = '/admin/user-activity?' + params.toString();
        });
    }
})();

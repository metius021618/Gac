/**
 * GAC - Vista Análisis (superadmin)
 * Filtros por hover, modal rango de fechas (igual que Actividad de administrador).
 */
(function() {
    'use strict';

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

    document.querySelectorAll('.analysis-top-bar .activity-filter-dropdown').forEach(function(drop) {
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
        if (drop.id === 'timeFilterDropdown') {
            drop.addEventListener('click', function(e) {
                if (e.target.closest('.activity-filter-menu')) return;
                this.classList.toggle('open');
            });
        }
    });

    var timeDrop = document.getElementById('timeFilterDropdown');
    document.addEventListener('click', function(e) {
        if (timeDrop && !timeDrop.contains(e.target)) timeDrop.classList.remove('open');
    });

    var customLink = document.getElementById('timeFilterCustom');
    var modal = document.getElementById('activityDateRangeModal');
    var closeBtn = document.getElementById('closeActivityDateModal');
    var overlay = modal && modal.querySelector('.modal-overlay');
    var applyBtn = document.getElementById('activityDateRangeApply');
    var inputFrom = document.getElementById('activityDateFrom');
    var inputTo = document.getElementById('activityDateTo');

    if (customLink) {
        customLink.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (timeDrop) timeDrop.classList.remove('open');
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
            var params = new URLSearchParams();
            params.set('date_from', from);
            params.set('date_to', to);
            params.set('time_range', 'custom');
            window.location.href = '/admin/analysis?' + params.toString();
        });
    }
})();

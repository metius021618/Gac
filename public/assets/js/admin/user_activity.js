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

    // Filtros Acción y Admin: abrir menú al hover
    document.querySelectorAll('.activity-filter-dropdown').forEach(function(drop) {
        if (drop.id === 'timeFilterDropdown') return;
        drop.addEventListener('mouseenter', function() { this.classList.add('open'); });
        drop.addEventListener('mouseleave', function() { this.classList.remove('open'); });
    });
    // Filtro Tiempo: abrir también con click para que "Personalizado" sea clicable (menú se mantiene abierto)
    var timeDrop = document.getElementById('timeFilterDropdown');
    if (timeDrop) {
        timeDrop.addEventListener('mouseenter', function() { this.classList.add('open'); });
        timeDrop.addEventListener('mouseleave', function() { this.classList.remove('open'); });
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

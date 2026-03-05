/**
 * GAC - Análisis Dashboard - Gráficos (Chart.js)
 * Evolución mensual (line + area), Ventas por plataforma (bar).
 */
(function () {
    'use strict';

    var DATA = window.ANALISIS_DATA || {};
    var evolucion = DATA.evolucion || { labels: [], values: [] };
    var ventasPorPlataforma = DATA.ventasPorPlataforma || [];
    var ultimoValor = DATA.ultimoValorEvolucion || 2590;

    function initEvolucionChart() {
        var canvas = document.getElementById('analisisChartEvolucion');
        if (!canvas || typeof Chart === 'undefined') return;

        var ctx = canvas.getContext('2d');
        var labels = evolucion.labels || ["May '23", "Jun '23", "Jul '23", "Aug '23", "Sep '23", "Oct '23", "Nov '23", "Dec '23", "Jan '24", "Feb '24", "Mar '24", "Apr '24"];
        var values = evolucion.values || [1820, 1950, 2100, 1980, 2240, 2380, 2210, 2450, 2520, 2480, 2610, 2590];

        var gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(34, 211, 238, 0.25)');
        gradient.addColorStop(1, 'rgba(34, 211, 238, 0.02)');

        new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Ventas',
                    data: values,
                    borderColor: '#22D3EE',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 6,
                    pointBackgroundColor: '#22D3EE',
                    pointBorderColor: '#FFFFFF',
                    pointBorderWidth: 2,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1E293B',
                        titleColor: '#F8FAFC',
                        bodyColor: '#94A3B8',
                        borderColor: '#2D3748',
                        borderWidth: 1
                    }
                },
                scales: {
                    x: {
                        grid: { color: '#334155', drawBorder: false },
                        ticks: { color: '#94A3B8', maxRotation: 45 }
                    },
                    y: {
                        min: 0,
                        max: 3000,
                        grid: { color: '#334155', drawBorder: false },
                        ticks: { color: '#94A3B8' }
                    }
                }
            }
        });
    }

    function initPlataformasChart() {
        var canvas = document.getElementById('analisisChartPlataformas');
        if (!canvas || typeof Chart === 'undefined') return;

        var labels = ventasPorPlataforma.map(function (p) { return p.nombre; });
        var values = ventasPorPlataforma.map(function (p) { return p.total; });
        var colors = ventasPorPlataforma.map(function (p) { return p.color || '#94A3B8'; });

        if (labels.length === 0) {
            labels = ['Netflix', 'Disney+', 'HBO Max', 'Spotify'];
            values = [1085, 760, 430, 315];
            colors = ['#F0575C', '#59A2EE', '#A788F9', '#51D480'];
        }

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Ventas',
                    data: values,
                    backgroundColor: colors,
                    borderColor: colors,
                    borderWidth: 0,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1E293B',
                        titleColor: '#F8FAFC',
                        bodyColor: '#94A3B8',
                        borderColor: '#2D3748',
                        borderWidth: 1
                    }
                },
                scales: {
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { color: '#94A3B8', maxRotation: 0, minRotation: 0 }
                    },
                    y: {
                        min: 0,
                        grid: { color: '#334155', drawBorder: false },
                        ticks: { color: '#94A3B8' }
                    }
                }
            },
            plugins: [{
                id: 'barLabels',
                afterDatasetsDraw: function (chart) {
                    var ctx = chart.ctx;
                    chart.data.datasets.forEach(function (dataset, i) {
                        var meta = chart.getDatasetMeta(i);
                        meta.data.forEach(function (bar, j) {
                            var value = dataset.data[j];
                            ctx.fillStyle = '#FFFFFF';
                            ctx.font = '600 12px Inter, sans-serif';
                            ctx.textAlign = 'center';
                            ctx.fillText(String(value), bar.x, bar.y - 6);
                        });
                    });
                }
            }]
        });
    }

    function initFilters() {
        var FILTERS = window.ANALISIS_FILTERS || { baseUrl: '/admin/analisis', admin: '', plataforma_id: '' };
        function scheduleClose(drop) {
            if (drop._closeTimeout) clearTimeout(drop._closeTimeout);
            drop._closeTimeout = setTimeout(function () {
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
        document.querySelectorAll('.analisis-filter-dropdown').forEach(function (drop) {
            drop.addEventListener('mouseenter', function () {
                cancelClose(this);
                this.classList.add('open');
            });
            drop.addEventListener('mouseleave', function () {
                scheduleClose(this);
            });
            var menu = drop.querySelector('.analisis-filter-menu');
            if (menu) {
                menu.addEventListener('mouseenter', function () { cancelClose(drop); drop.classList.add('open'); });
                menu.addEventListener('mouseleave', function () { scheduleClose(drop); });
            }
        });
        var customLink = document.getElementById('analisisFechaPersonalizado');
        var modal = document.getElementById('analisisDateRangeModal');
        var closeBtn = document.getElementById('closeAnalisisDateModal');
        var overlay = modal && modal.querySelector('.modal-overlay');
        var applyBtn = document.getElementById('analisisDateRangeApply');
        var inputFrom = document.getElementById('analisisDateFrom');
        var inputTo = document.getElementById('analisisDateTo');
        if (customLink) {
            customLink.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                document.querySelectorAll('.analisis-filter-dropdown').forEach(function (d) { d.classList.remove('open'); });
                if (modal) modal.classList.remove('hidden');
            });
        }
        function closeModal() {
            if (modal) modal.classList.add('hidden');
        }
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (overlay) overlay.addEventListener('click', closeModal);
        if (applyBtn && inputFrom && inputTo) {
            applyBtn.addEventListener('click', function () {
                var from = inputFrom.value;
                var to = inputTo.value;
                if (!from || !to) return;
                var params = new URLSearchParams();
                params.set('time_range', 'personalizado');
                params.set('date_from', from);
                params.set('date_to', to);
                if (FILTERS.admin) params.set('admin', FILTERS.admin);
                if (FILTERS.plataforma_id) params.set('plataforma_id', FILTERS.plataforma_id);
                window.location.href = FILTERS.baseUrl + '?' + params.toString();
            });
        }
    }

    function init() {
        initEvolucionChart();
        initPlataformasChart();
        initFilters();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

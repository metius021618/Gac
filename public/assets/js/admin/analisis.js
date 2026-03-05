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
    var barLogoUrls = DATA.barLogoUrls || [];
    var barLabels = DATA.barLabels || [];

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

        var colorByPlatform = { 'Netflix': '#FCA5A5', 'Disney+': '#93C5FD', 'HBO Max': '#C4B5FD', 'Spotify': '#86EFAC' };
        var labels = ventasPorPlataforma.map(function (p) { return p.nombre; });
        var values = ventasPorPlataforma.map(function (p) { return p.total; });
        var colors = ventasPorPlataforma.map(function (p) { return colorByPlatform[p.nombre] || p.color || '#94A3B8'; });

        if (labels.length === 0) {
            labels = ['Netflix', 'Disney+', 'HBO Max', 'Spotify'];
            values = [1085, 760, 430, 315];
            colors = ['#FCA5A5', '#93C5FD', '#C4B5FD', '#86EFAC'];
        }

        var logoSize = 36;
        var overlay = document.getElementById('analisisBarLogosOverlay');
        var logosPlaced = false;

        function placeLogosOverBars(chart) {
            if (!overlay || !chart.ctx) return;
            var meta = chart.getDatasetMeta(0);
            if (!meta || !meta.data.length) return;

            var canvasEl = chart.canvas;
            var w = canvasEl.offsetWidth;
            var h = canvasEl.offsetHeight;
            overlay.style.width = w + 'px';
            overlay.style.height = h + 'px';

            var area = chart.chartArea;
            if (!area) return;

            var scaleX = w / chart.width;
            var scaleY = h / chart.height;

            for (var j = 0; j < meta.data.length; j++) {
                var bar = meta.data[j];
                var logoUrl = barLogoUrls[j];
                var label = barLabels[j] || '';
                var letter = label ? label.charAt(0) : '';

                var el = overlay.querySelector('[data-bar-index="' + j + '"]');
                if (!el) {
                    el = document.createElement(logoUrl ? 'img' : 'span');
                    el.setAttribute('data-bar-index', j);
                    el.className = 'analisis-bar-logo-on-bar';
                    if (logoUrl) {
                        el.src = logoUrl;
                        el.alt = label;
                    } else {
                        el.className += ' analisis-bar-logo-on-bar--letter';
                        el.textContent = letter;
                    }
                    overlay.appendChild(el);
                }

                var left = (bar.x - (logoSize / 2)) * scaleX;
                var top = (area.top + 6) * scaleY;
                el.style.width = logoSize + 'px';
                el.style.height = logoSize + 'px';
                el.style.left = Math.round(left) + 'px';
                el.style.top = Math.round(top) + 'px';
            }
            logosPlaced = true;
        }

        var chart = new Chart(canvas, {
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
                        ticks: { color: '#94A3B8' }
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
            }, {
                id: 'barLogosOverlay',
                afterDraw: function (chart) {
                    placeLogosOverBars(chart);
                }
            }]
        });

        if (window.ResizeObserver && overlay) {
            var ro = new ResizeObserver(function () {
                if (chart && logosPlaced) placeLogosOverBars(chart);
            });
            ro.observe(canvas.parentElement);
        }
    }

    function init() {
        initEvolucionChart();
        initPlataformasChart();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

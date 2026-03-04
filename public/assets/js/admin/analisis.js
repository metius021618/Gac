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
        var colors = ventasPorPlataforma.map(function (p) { return p.color || '#334155'; });

        if (labels.length === 0) {
            labels = ['Netflix', 'Disney+', 'HBO Max', 'Spotify'];
            values = [1085, 760, 430, 315];
            colors = ['#E50914', '#1F80E0', '#8B5CF6', '#1DB954'];
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
            }]
        });
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

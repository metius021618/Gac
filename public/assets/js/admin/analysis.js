/**
 * GAC - Vista Análisis (superadmin)
 * Filtros por hover; actualización dinámica del gráfico por AJAX (sin recargar página).
 * Colores por plataforma; animación de barras.
 */
(function() {
    'use strict';

    var chartContent = document.getElementById('analysisChartContent');
    var timeFilterValue = document.getElementById('timeFilterValue');

    function getScaleTicks(maxCount) {
        var scaleMax = 50;
        if (maxCount > 0) scaleMax = Math.max(50, Math.ceil(maxCount / 50) * 50);
        var ticks = [];
        for (var v = 50; v <= scaleMax; v += 50) ticks.push(v);
        return { scaleMax: scaleMax, ticks: ticks };
    }

    function getTimeLabel(timeRange, dateFrom, dateTo) {
        if (timeRange === '7') return 'Últimos 7 días';
        if (timeRange === '30') return 'Últimos 30 días';
        if (timeRange === '90') return 'Últimos 90 días';
        if (dateFrom && dateTo) return 'Personalizado';
        return 'Todo';
    }

    function escapeHtml(s) {
        var div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    function renderChart(platformCounts) {
        if (!chartContent) return;
        if (!platformCounts || platformCounts.length === 0) {
            chartContent.innerHTML = '<p class="analysis-empty">No hay datos para el rango seleccionado.</p>';
            return;
        }
        var maxCount = 0;
        platformCounts.forEach(function(r) { if ((r.total || 0) > maxCount) maxCount = r.total; });
        var scale = getScaleTicks(maxCount);
        var scaleCount = scale.ticks.length;

        var scaleRow = '<div class="analysis-chart-scale-row">' +
            '<div class="analysis-chart-scale-label"></div>' +
            '<div class="analysis-chart-scale-ticks" style="--scale-count: ' + scaleCount + ';">' +
            scale.ticks.map(function(t) { return '<span class="analysis-chart-tick">' + t + '</span>'; }).join('') +
            '</div></div>';

        var rows = platformCounts.map(function(row) {
            var name = row.display_name || row.platform_name || '—';
            var total = parseInt(row.total, 10) || 0;
            var pct = scale.scaleMax > 0 ? Math.min(100, (total / scale.scaleMax) * 100) : 0;
            var color = row.color || '#0066ff';
            return '<div class="analysis-chart-row">' +
                '<div class="analysis-chart-row-label">' + escapeHtml(name) + '</div>' +
                '<div class="analysis-chart-row-track">' +
                '<div class="analysis-chart-bar analysis-chart-bar--animate" style="width: ' + pct + '%; background-color: ' + escapeHtml(color) + ';" data-value="' + total + '"></div>' +
                '<span class="analysis-chart-bar-value">' + total + '</span>' +
                '</div></div>';
        }).join('');

        chartContent.innerHTML = '<div class="analysis-chart" id="analysisChart" role="img" aria-label="Gráfico de barras por plataforma">' +
            scaleRow + rows + '</div>';

        requestAnimationFrame(function() {
            var bars = chartContent.querySelectorAll('.analysis-chart-bar--animate');
            bars.forEach(function(bar) { bar.classList.remove('analysis-chart-bar--animate'); });
        });
    }

    function buildParams(timeRange, dateFrom, dateTo) {
        var p = {};
        if (timeRange) p.time_range = timeRange;
        if (dateFrom) p.date_from = dateFrom;
        if (dateTo) p.date_to = dateTo;
        return p;
    }

    function updateUrl(params) {
        var qs = Object.keys(params).map(function(k) { return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]); }).join('&');
        var url = '/admin/analysis' + (qs ? '?' + qs : '');
        if (window.history && window.history.replaceState) window.history.replaceState({}, '', url);
    }

    function loadChart(params, updateLabel) {
        var qs = Object.keys(params).map(function(k) { return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]); }).join('&');
        var url = '/admin/analysis/data' + (qs ? '?' + qs : '');
        chartContent.classList.add('analysis-chart-loading');
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                chartContent.classList.remove('analysis-chart-loading');
                if (data.success && Array.isArray(data.platform_counts)) {
                    renderChart(data.platform_counts);
                    if (updateLabel && timeFilterValue) {
                        timeFilterValue.textContent = getTimeLabel(
                            data.time_range || params.time_range,
                            data.date_from || params.date_from,
                            data.date_to || params.date_to
                        );
                    }
                    updateUrl(params);
                }
            })
            .catch(function() {
                chartContent.classList.remove('analysis-chart-loading');
            });
    }

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
            menu.addEventListener('mouseenter', function() { cancelClose(drop); drop.classList.add('open'); });
            menu.addEventListener('mouseleave', function() { scheduleClose(drop); });
        }
        if (drop.id === 'timeFilterDropdown') {
            drop.addEventListener('click', function(e) {
                if (e.target.closest('.activity-filter-menu')) return;
                this.classList.toggle('open');
            });
        }
    });

    var timeDrop = document.getElementById('timeFilterDropdown');
    if (timeDrop) {
        timeDrop.querySelectorAll('.activity-filter-menu a[href^="/"]').forEach(function(a) {
            a.addEventListener('click', function(e) {
                e.preventDefault();
                var href = this.getAttribute('href') || '';
                var idx = href.indexOf('?');
                var qs = idx >= 0 ? href.slice(idx + 1) : '';
                var params = {};
                if (qs) qs.split('&').forEach(function(pair) {
                    var i = pair.indexOf('=');
                    if (i >= 0) params[decodeURIComponent(pair.slice(0, i))] = decodeURIComponent(pair.slice(i + 1));
                });
                if (timeDrop) timeDrop.classList.remove('open');
                loadChart(params, true);
            });
        });
    }

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
            closeModal();
            loadChart(buildParams('custom', from, to), true);
        });
    }
})();

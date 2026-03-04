/**
 * GAC - Vista Análisis (superadmin)
 * Filtros por hover; actualización dinámica del gráfico sin recargar página.
 */
(function() {
    'use strict';

    var container = document.getElementById('analysisChartContainer');
    var timeFilterValue = document.getElementById('timeFilterValue');

    function buildChartHtml(platformCounts) {
        if (!platformCounts || platformCounts.length === 0) {
            return '<p class="analysis-empty">No hay datos para el rango seleccionado.</p>';
        }
        var maxCount = 0;
        platformCounts.forEach(function(r) { if (r.total > maxCount) maxCount = r.total; });
        var scaleMax = 50;
        if (maxCount > 0) scaleMax = Math.max(50, Math.ceil(maxCount / 50) * 50);
        var scaleTicks = [];
        for (var v = 50; v <= scaleMax; v += 50) scaleTicks.push(v);
        var scaleCount = scaleTicks.length;

        var scaleRow = '<div class="analysis-chart-scale-row">' +
            '<div class="analysis-chart-scale-label"></div>' +
            '<div class="analysis-chart-scale-ticks" style="--scale-count: ' + scaleCount + '">' +
            scaleTicks.map(function(t) { return '<span class="analysis-chart-tick">' + t + '</span>'; }).join('') +
            '</div></div>';

        var rows = platformCounts.map(function(row) {
            var name = (row.display_name || row.platform_name || '—').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            var total = parseInt(row.total, 10) || 0;
            var pct = scaleMax > 0 ? Math.min(100, (total / scaleMax) * 100) : 0;
            var color = row.color || '#0066ff';
            return '<div class="analysis-chart-row">' +
                '<div class="analysis-chart-row-label">' + name + '</div>' +
                '<div class="analysis-chart-row-track">' +
                '<div class="analysis-chart-bar" style="width: 0%; background-color: ' + color.replace(/"/g, '&quot;') + ';" data-value="' + total + '"></div>' +
                '<span class="analysis-chart-bar-value">' + total + '</span></div></div>';
        });

        var chart = '<div class="analysis-chart" role="img" aria-label="Gráfico de barras por plataforma">' + scaleRow + rows.join('') + '</div>';
        return chart;
    }

    function animateBars() {
        var bars = container.querySelectorAll('.analysis-chart-bar');
        bars.forEach(function(bar) {
            var targetWidth = bar.getAttribute('data-value');
            var row = bar.closest('.analysis-chart-row');
            if (!row) return;
            var track = row.querySelector('.analysis-chart-row-track');
            if (!track) return;
            var scaleMax = 50;
            var maxVal = 0;
            container.querySelectorAll('.analysis-chart-bar').forEach(function(b) {
                var v = parseInt(b.getAttribute('data-value'), 10);
                if (v > maxVal) maxVal = v;
            });
            if (maxVal > 0) scaleMax = Math.max(50, Math.ceil(maxVal / 50) * 50);
            var pct = scaleMax > 0 ? Math.min(100, (parseInt(targetWidth, 10) / scaleMax) * 100) : 0;
            bar.style.width = pct + '%';
        });
    }

    function fetchAndUpdateChart(params, label) {
        if (!container) return;
        var qs = new URLSearchParams(params).toString();
        var url = '/admin/analysis/data?' + qs;
        container.style.opacity = '0.6';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || !data.platform_counts) {
                    container.innerHTML = '<p class="analysis-empty">No hay datos para el rango seleccionado.</p>';
                    container.style.opacity = '1';
                    return;
                }
                container.innerHTML = buildChartHtml(data.platform_counts);
                container.style.opacity = '1';
                requestAnimationFrame(function() {
                    requestAnimationFrame(animateBars);
                });
                if (timeFilterValue && label !== undefined) timeFilterValue.textContent = label;
                var newUrl = '/admin/analysis' + (qs ? '?' + qs : '');
                if (window.history && window.history.replaceState) window.history.replaceState({}, '', newUrl);
            })
            .catch(function() {
                container.innerHTML = '<p class="analysis-empty">Error al cargar los datos.</p>';
                container.style.opacity = '1';
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
    if (timeDrop) {
        timeDrop.addEventListener('click', function(e) {
            var link = e.target.closest('a[href*="admin/analysis"]');
            if (!link || link.getAttribute('href') === '#') return;
            e.preventDefault();
            var href = link.getAttribute('href');
            var params = {};
            if (href.indexOf('?') !== -1) {
                var query = href.split('?')[1] || '';
                query.split('&').forEach(function(pair) {
                    var i = pair.indexOf('=');
                    if (i !== -1) {
                        var k = decodeURIComponent(pair.slice(0, i));
                        var v = decodeURIComponent(pair.slice(i + 1));
                        if (v) params[k] = v;
                    }
                });
            }
            var label = link.textContent.trim();
            fetchAndUpdateChart(params, label);
            if (timeDrop) timeDrop.classList.remove('open');
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
            var params = { date_from: from, date_to: to, time_range: 'custom' };
            fetchAndUpdateChart(params, 'Personalizado');
            closeModal();
            if (timeDrop) timeDrop.classList.remove('open');
        });
    }
})();

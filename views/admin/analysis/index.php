<?php
/**
 * GAC - Vista Análisis (solo superadmin)
 * Gráfico de barras horizontales: eje X = conteo (50, 100, 150...), eje Y = plataformas.
 * Filtros: Tiempo (7/30/90 días, personalizado) + 2 placeholders "Todos".
 */

$content = ob_start();
$platform_counts = $platform_counts ?? [];
$filter_date_from = $filter_date_from ?? '';
$filter_date_to = $filter_date_to ?? '';
$filter_time_range = $filter_time_range ?? '';
$baseUrl = '/admin/analysis';

$queryParams = function ($overrides = []) use ($baseUrl, $filter_date_from, $filter_date_to, $filter_time_range) {
    $p = array_merge([
        'date_from' => $filter_date_from,
        'date_to' => $filter_date_to,
        'time_range' => $filter_time_range,
    ], $overrides);
    $p = array_filter($p, function ($v) { return $v !== '' && $v !== null; });
    return $baseUrl . '?' . http_build_query($p);
};

$timeRangeLabel = 'Todo';
if ($filter_time_range === '7') $timeRangeLabel = 'Últimos 7 días';
elseif ($filter_time_range === '30') $timeRangeLabel = 'Últimos 30 días';
elseif ($filter_time_range === '90') $timeRangeLabel = 'Últimos 90 días';
elseif ($filter_date_from && $filter_date_to) $timeRangeLabel = 'Personalizado';

$maxCount = 0;
foreach ($platform_counts as $row) {
    if (($row['total'] ?? 0) > $maxCount) $maxCount = (int) $row['total'];
}
$scaleMax = 50;
if ($maxCount > 0) {
    $scaleMax = max(50, (int) ceil($maxCount / 50) * 50);
}
$scaleTicks = [];
for ($v = 50; $v <= $scaleMax; $v += 50) {
    $scaleTicks[] = $v;
}
?>

<div class="admin-container analysis-container">
    <div class="admin-header">
        <h1 class="admin-title">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
            Análisis
        </h1>
    </div>

    <div class="analysis-top-bar">
        <div class="user-activity-filters">
            <div class="activity-filter-dropdown" data-filter="time" id="timeFilterDropdown">
                <span class="activity-filter-label">Tiempo</span><span class="activity-filter-sep"> - </span><span class="activity-filter-value" id="timeFilterValue"><?= htmlspecialchars($timeRangeLabel) ?></span>
                <ul class="activity-filter-menu">
                    <li><a href="<?= $queryParams(['date_from' => '', 'date_to' => '', 'time_range' => '']) ?>">Todo</a></li>
                    <li><a href="<?= $queryParams(['date_from' => date('Y-m-d', strtotime('-7 days')), 'date_to' => date('Y-m-d'), 'time_range' => '7']) ?>">Últimos 7 días</a></li>
                    <li><a href="<?= $queryParams(['date_from' => date('Y-m-d', strtotime('-30 days')), 'date_to' => date('Y-m-d'), 'time_range' => '30']) ?>">Últimos 30 días</a></li>
                    <li><a href="<?= $queryParams(['date_from' => date('Y-m-d', strtotime('-90 days')), 'date_to' => date('Y-m-d'), 'time_range' => '90']) ?>">Últimos 90 días</a></li>
                    <li><a href="#" id="timeFilterCustom" class="activity-filter-custom-link">Personalizado</a></li>
                </ul>
            </div>
            <div class="activity-filter-dropdown" data-filter="extra1">
                <span class="activity-filter-label">Filtro 1</span><span class="activity-filter-sep"> - </span><span class="activity-filter-value">Todos</span>
                <ul class="activity-filter-menu">
                    <li><a href="#">Todos</a></li>
                </ul>
            </div>
            <div class="activity-filter-dropdown" data-filter="extra2">
                <span class="activity-filter-label">Filtro 2</span><span class="activity-filter-sep"> - </span><span class="activity-filter-value">Todos</span>
                <ul class="activity-filter-menu">
                    <li><a href="#">Todos</a></li>
                </ul>
            </div>
            <a href="<?= $baseUrl ?>" class="btn btn-secondary btn-reset-filters">Reiniciar</a>
        </div>
    </div>

    <div class="analysis-chart-wrap" id="analysisChartWrap">
        <div id="analysisChartContainer">
        <?php if (empty($platform_counts)): ?>
        <p class="analysis-empty">No hay datos para el rango seleccionado.</p>
        <?php else: ?>
        <div class="analysis-chart" role="img" aria-label="Gráfico de barras por plataforma">
            <div class="analysis-chart-scale-row">
                <div class="analysis-chart-scale-label"></div>
                <div class="analysis-chart-scale-ticks" style="--scale-count: <?= count($scaleTicks) ?>;">
                    <?php foreach ($scaleTicks as $tick): ?>
                    <span class="analysis-chart-tick"><?= $tick ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php foreach ($platform_counts as $row):
                $name = $row['display_name'] ?? $row['platform_name'] ?? '—';
                $total = (int) ($row['total'] ?? 0);
                $pct = $scaleMax > 0 ? min(100, ($total / $scaleMax) * 100) : 0;
                $barColor = $row['color'] ?? '#0066ff';
            ?>
            <div class="analysis-chart-row">
                <div class="analysis-chart-row-label"><?= htmlspecialchars($name) ?></div>
                <div class="analysis-chart-row-track">
                    <div class="analysis-chart-bar" style="width: <?= $pct ?>%; background-color: <?= htmlspecialchars($barColor) ?>;" data-value="<?= $total ?>"></div>
                    <span class="analysis-chart-bar-value"><?= $total ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal rango de tiempo (igual que Actividad de administrador) -->
<div id="activityDateRangeModal" class="modal hidden" aria-hidden="true">
    <div class="modal-overlay"></div>
    <div class="modal-container activity-date-range-modal">
        <div class="modal-header activity-date-range-modal-header">
            <h2 class="modal-title">Selecciona un rango de tiempo</h2>
            <button type="button" class="modal-close modal-close--large" id="closeActivityDateModal" aria-label="Cerrar">&times;</button>
        </div>
        <div class="modal-content activity-date-range-fields">
            <div class="activity-date-field-group">
                <label class="activity-date-label">Hora de inicio</label>
                <div class="activity-date-input-wrap">
                    <input type="date" id="activityDateFrom" class="form-input activity-date-input">
                </div>
            </div>
            <span class="activity-date-sep">a</span>
            <div class="activity-date-field-group">
                <label class="activity-date-label">Hora de finalización</label>
                <div class="activity-date-input-wrap">
                    <input type="date" id="activityDateTo" class="form-input activity-date-input">
                </div>
            </div>
        </div>
        <div class="modal-footer activity-date-range-footer">
            <button type="button" class="btn btn-activity-date-continue" id="activityDateRangeApply">Continuar</button>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = $title ?? 'Análisis';
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css', '/assets/css/admin/user_activity.css', '/assets/css/admin/analysis.css'];
$additional_js = ['/assets/js/admin/analysis.js'];

require base_path('views/layouts/main.php');
?>

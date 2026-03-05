<?php
/**
 * GAC - Vista Análisis (dashboard corporativo premium, dark mode, 12 columnas)
 * KPIs, evolución mensual, ventas por plataforma, ranking revendedores, heatmap.
 */
$total_cuentas = $total_cuentas ?? ['total' => 0, 'crecimiento' => 0];
$plataformas_activas = (int) ($plataformas_activas ?? 4);
$plataformas_activas_list = $plataformas_activas_list ?? [];
$administrador_del_mes = $administrador_del_mes ?? ['nombre' => '—', 'foto_url' => null, 'cuentas' => 0];
$total_ingresos = $total_ingresos ?? ['total' => 0.0, 'crecimiento' => 0];
$evolucion = $evolucion ?? ['labels' => [], 'values' => []];
$ventas_por_plataforma = $ventas_por_plataforma ?? [];
$ranking_administradores = $ranking_administradores ?? [];
$heatmap = $heatmap ?? ['administradores' => [], 'plataformas' => [], 'matrix' => []];
$filter_time_range = $filter_time_range ?? '30';
$filter_date_from = $filter_date_from ?? '';
$filter_date_to = $filter_date_to ?? '';
$filter_admin = $filter_admin ?? '';
$filter_plataforma_id = $filter_plataforma_id ?? '';
$administradores_para_filtro = $administradores_para_filtro ?? [];
$plataformas_para_filtro = $plataformas_para_filtro ?? [];

$analisisBaseUrl = '/admin/analisis';
$analisisQueryParams = function($overrides = []) use ($analisisBaseUrl, $filter_time_range, $filter_date_from, $filter_date_to, $filter_admin, $filter_plataforma_id) {
    $p = array_merge([
        'time_range' => $filter_time_range,
        'date_from' => $filter_date_from,
        'date_to' => $filter_date_to,
        'admin' => $filter_admin,
        'plataforma_id' => $filter_plataforma_id,
    ], $overrides);
    $p = array_filter($p, function($v) { return $v !== '' && $v !== null; });
    return $analisisBaseUrl . '?' . http_build_query($p);
};
$fechaLabel = 'Últimos 30 días';
if ($filter_time_range === '7') $fechaLabel = 'Últimos 7 días';
elseif ($filter_time_range === '30') $fechaLabel = 'Últimos 30 días';
elseif ($filter_time_range === '90') $fechaLabel = 'Últimos 90 días';
elseif ($filter_date_from && $filter_date_to) $fechaLabel = 'Personalizado';
$plataformaFilterLabel = 'Todas';
if ($filter_plataforma_id) {
    foreach ($plataformas_para_filtro as $pl) {
        if ((int) $pl['id'] === (int) $filter_plataforma_id) {
            $plataformaFilterLabel = $pl['display_name'];
            break;
        }
    }
}

$imagenes_plataformas_base = '/assets/imagenes/';
$imagenes_plataformas = [
    'netflix'  => 'netflix.png',
    'disney'   => 'disney.png',
    'hbo'      => 'HBO.jfif',
    'spotify'  => 'spotify.png',
    'prime'    => 'primevideo.png',
    'crunchyroll' => 'Crunchyroll.svg',
    'canva'    => 'canva.png',
    'chatgpt'  => 'chatgot.png',
    'paramount' => 'primevideo.png',
];
$platform_logo_key = function ($p) {
    $n = strtolower((string) ($p['name'] ?? ''));
    $d = strtolower((string) ($p['display_name'] ?? ''));
    if (strpos($n, 'netflix') !== false || strpos($d, 'netflix') !== false) return 'netflix';
    if (strpos($n, 'disney') !== false || strpos($d, 'disney') !== false) return 'disney';
    if (strpos($n, 'hbo') !== false || strpos($d, 'hbo') !== false) return 'hbo';
    if (strpos($n, 'spotify') !== false || strpos($d, 'spotify') !== false) return 'spotify';
    if (strpos($n, 'prime') !== false || strpos($d, 'prime') !== false) return 'prime';
    if (strpos($n, 'crunchyroll') !== false || strpos($d, 'crunchyroll') !== false) return 'crunchyroll';
    if (strpos($n, 'canva') !== false || strpos($d, 'canva') !== false) return 'canva';
    if (strpos($n, 'chatgpt') !== false || strpos($n, 'chatgot') !== false || strpos($d, 'chatgpt') !== false) return 'chatgpt';
    if (strpos($n, 'paramount') !== false || strpos($d, 'paramount') !== false) return 'paramount';
    return null;
};
$plat_img = function ($key) use ($imagenes_plataformas_base, $imagenes_plataformas) {
    $file = $imagenes_plataformas[$key] ?? null;
    if (!$file) return '';
    $base = function_exists('base_path') ? rtrim(base_path('public/assets/imagenes/'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : '';
    if ($base && !@is_file($base . $file)) return '';
    return $imagenes_plataformas_base . $file;
};

$content = ob_start();
?>
<div class="analisis-page">
    <!-- Filtros: parte superior derecha, fuera del div del gráfico -->
    <div class="analisis-filters-bar">
        <div class="analisis-filters-inner">
            <div class="analisis-filter-dropdown" data-filter="fecha">
                <span class="analisis-filter-label">Fecha</span>
                <span class="analisis-filter-sep"> - </span>
                <span class="analisis-filter-value" id="analisisFechaValue"><?= htmlspecialchars($fechaLabel) ?></span>
                <ul class="analisis-filter-menu">
                    <li><a href="<?= $analisisQueryParams(['time_range' => '7', 'date_from' => date('Y-m-d', strtotime('-7 days')), 'date_to' => date('Y-m-d')]) ?>">Últimos 7 días</a></li>
                    <li><a href="<?= $analisisQueryParams(['time_range' => '30', 'date_from' => date('Y-m-d', strtotime('-30 days')), 'date_to' => date('Y-m-d')]) ?>">Últimos 30 días</a></li>
                    <li><a href="<?= $analisisQueryParams(['time_range' => '90', 'date_from' => date('Y-m-d', strtotime('-90 days')), 'date_to' => date('Y-m-d')]) ?>">Últimos 90 días</a></li>
                    <li><a href="#" id="analisisFechaPersonalizado" class="analisis-filter-custom">Personalizado</a></li>
                </ul>
            </div>
            <div class="analisis-filter-dropdown" data-filter="admin">
                <span class="analisis-filter-label">Administrador</span>
                <span class="analisis-filter-sep"> - </span>
                <span class="analisis-filter-value"><?= $filter_admin ? htmlspecialchars($filter_admin) : 'Todos' ?></span>
                <ul class="analisis-filter-menu">
                    <li><a href="<?= $analisisQueryParams(['admin' => '']) ?>">Todos</a></li>
                    <?php foreach ($administradores_para_filtro as $a): ?>
                    <li><a href="<?= $analisisQueryParams(['admin' => $a['nombre']]) ?>"><?= htmlspecialchars($a['nombre']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="analisis-filter-dropdown" data-filter="plataforma">
                <span class="analisis-filter-label">Plataforma</span>
                <span class="analisis-filter-sep"> - </span>
                <span class="analisis-filter-value"><?= htmlspecialchars($plataformaFilterLabel) ?></span>
                <ul class="analisis-filter-menu">
                    <li><a href="<?= $analisisQueryParams(['plataforma_id' => '']) ?>">Todas</a></li>
                    <?php foreach ($plataformas_para_filtro as $pl): ?>
                    <li><a href="<?= $analisisQueryParams(['plataforma_id' => $pl['id']]) ?>"><?= htmlspecialchars($pl['display_name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="analisis-grid">
        <!-- Fila 1: 4 KPI Cards -->
        <div class="analisis-row analisis-row--kpis">
            <div class="analisis-col analisis-col-3">
                <div class="analisis-card analisis-kpi-card">
                    <div class="analisis-kpi-header">
                        <span class="analisis-kpi-label">Total Cuentas Vendidas</span>
                        <span class="analisis-kpi-icon analisis-kpi-icon--chart">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                        </span>
                    </div>
                    <div class="analisis-kpi-value"><?= number_format($total_cuentas['total']) ?></div>
                    <div class="analisis-kpi-meta">
                        <?php if ($total_cuentas['crecimiento'] != 0): ?><span class="analisis-kpi-growth analisis-kpi-growth--up"><svg class="analisis-growth-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"></polyline></svg>+<?= number_format($total_cuentas['crecimiento'], 1) ?>%</span><?php endif; ?>
                        <span class="analisis-kpi-sub">Cuentas asignadas</span>
                    </div>
                </div>
            </div>
            <?php /* Card Plataformas Activas - oculto, se mantiene en código
            <div class="analisis-col analisis-col-3">
                <div class="analisis-card analisis-kpi-card">
                    <div class="analisis-kpi-header">
                        <span class="analisis-kpi-label">Plataformas Activas</span>
                        <span class="analisis-kpi-icon analisis-kpi-icon--spotify" title="Plataformas">
                        </span>
                    </div>
                    <div class="analisis-kpi-value"><?= $plataformas_activas ?></div>
                    <div class="analisis-platform-icons">
                        <?php 
                        $shown = 0;
                        $max_platforms = 8;
                        foreach ($plataformas_activas_list as $p): 
                            if ($shown >= $max_platforms) break;
                            $key = $platform_logo_key($p);
                            $disp = $p['display_name'] ?? $p['name'] ?? '';
                            $url = $key ? $plat_img($key) : '';
                        ?>
                        <?php if ($url): ?><img src="<?= $url ?>" alt="<?= htmlspecialchars($disp) ?>" class="analisis-platform-logo analisis-platform-logo--img" title="<?= htmlspecialchars($disp) ?>" width="36" height="36"><?php else: ?><span class="analisis-platform-logo analisis-platform-logo--fallback" title="<?= htmlspecialchars($disp) ?>"><?= mb_substr($disp, 0, 1) ?></span><?php endif; ?>
                        <?php $shown++; endforeach; ?>
                        <?php if (empty($plataformas_activas_list)): ?>
                        <?php $n = $plat_img('netflix'); if ($n): ?><img src="<?= $n ?>" alt="Netflix" class="analisis-platform-logo analisis-platform-logo--img" width="36" height="36"><?php else: ?><span class="analisis-platform-logo analisis-platform-logo--netflix">N</span><?php endif; ?>
                        <?php $d = $plat_img('disney'); if ($d): ?><img src="<?= $d ?>" alt="Disney+" class="analisis-platform-logo analisis-platform-logo--img" width="36" height="36"><?php else: ?><span class="analisis-platform-logo analisis-platform-logo--disney">D</span><?php endif; ?>
                        <?php $h = $plat_img('hbo'); if ($h): ?><img src="<?= $h ?>" alt="HBO Max" class="analisis-platform-logo analisis-platform-logo--img" width="36" height="36"><?php else: ?><span class="analisis-platform-logo analisis-platform-logo--hbo">H</span><?php endif; ?>
                        <?php $s = $plat_img('spotify'); if ($s): ?><img src="<?= $s ?>" alt="Spotify" class="analisis-platform-logo analisis-platform-logo--img" width="36" height="36"><?php else: ?><span class="analisis-platform-logo analisis-platform-logo--spotify">S</span><?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            */ ?>
            <div class="analisis-col analisis-col-3">
                <div class="analisis-card analisis-kpi-card analisis-kpi-card--revendedor">
                    <div class="analisis-kpi-header">
                        <span class="analisis-kpi-label">Administrador del Mes</span>
                        <span class="analisis-kpi-icon analisis-kpi-icon--crown" title="Administrador destacado">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L14 8h6l-5 4 2 8-7-5-7 5 2-8-5-4h6l2-6z"/></svg>
                        </span>
                    </div>
                    <div class="analisis-revendedor-block">
                        <img src="<?= !empty($administrador_del_mes['foto_url']) ? htmlspecialchars($administrador_del_mes['foto_url']) : 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="%23334155"><circle cx="24" cy="24" r="24"/><circle cx="24" cy="18" r="8"/><path d="M24 32c-6 0-10 4-10 8v2h20v-2c0-4-4-8-10-8z"/></svg>') ?>" alt="" class="analisis-revendedor-avatar" width="48" height="48">
                        <div class="analisis-revendedor-info">
                            <span class="analisis-revendedor-nombre"><?= htmlspecialchars($administrador_del_mes['nombre']) ?></span>
                            <span class="analisis-revendedor-cuentas"><?= number_format($administrador_del_mes['cuentas']) ?> cuentas asignadas</span>
                        </div>
                    </div>
                    <div class="analisis-progress-bar">
                        <div class="analisis-progress-fill" style="width: <?= $administrador_del_mes['cuentas'] > 0 ? '85' : '0' ?>%;"></div>
                    </div>
                </div>
            </div>
            <?php /* Card Total Ingresos - oculto, se mantiene en código
            <div class="analisis-col analisis-col-3">
                <div class="analisis-card analisis-kpi-card">
                    <div class="analisis-kpi-header">
                        <span class="analisis-kpi-label">Total Ingresos</span>
                        <span class="analisis-kpi-icon analisis-kpi-icon--bank" title="Ingresos">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M12 9v6"/><path d="M2 10h20"/><path d="M7 14h.01"/><path d="M17 14h.01"/></svg>
                        </span>
                    </div>
                    <div class="analisis-kpi-value analisis-kpi-value--accent">$<?= number_format($total_ingresos['total'], 0) ?></div>
                    <div class="analisis-kpi-meta">
                        <span class="analisis-kpi-growth analisis-kpi-growth--up"><svg class="analisis-growth-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"></polyline></svg>+<?= number_format($total_ingresos['crecimiento'], 1) ?>%</span>
                        <span class="analisis-kpi-sub">Mes actual</span>
                    </div>
                </div>
            </div>
            */ ?>
        </div>

        <!-- Fila 2: Gráfico evolución mensual -->
        <div class="analisis-row">
            <div class="analisis-col analisis-col-12">
                <div class="analisis-card analisis-chart-card">
                    <h3 class="analisis-card-title">
                        <span class="analisis-chart-title-icon analisis-chart-title-icon--line"><svg width="33" height="33" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg></span>
                        Evolución Mensual de Ventas
                    </h3>
                    <div class="analisis-chart-wrap analisis-chart-wrap--line" style="height: 300px;">
                        <canvas id="analisisChartEvolucion" width="1200" height="300"></canvas>
                        <span class="analisis-evolucion-badge" id="analisisEvolucionBadge"><?= number_format(!empty($evolucion['values']) ? $evolucion['values'][count($evolucion['values'])-1] : 0) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fila 3: 3 columnas -->
        <div class="analisis-row analisis-row--three">
            <div class="analisis-col analisis-col-4">
                <div class="analisis-card analisis-chart-card">
                    <h3 class="analisis-card-title">
                        <span class="analisis-chart-title-icon analisis-chart-title-icon--bar"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line></svg></span>
                        Ventas por Plataforma
                    </h3>
                    <div class="analisis-bar-chart-with-logos">
                        <div class="analisis-chart-wrap analisis-chart-wrap--bar" style="height: 280px;">
                            <canvas id="analisisChartPlataformas" width="400" height="280"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="analisis-col analisis-col-4">
                <div class="analisis-card analisis-chart-card">
                    <h3 class="analisis-card-title">
                        <span class="analisis-chart-title-icon analisis-chart-title-icon--users"><svg width="33" height="33" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></span>
                        Ranking de Administradores
                    </h3>
                    <div class="analisis-ranking-list" id="analisisRankingList">
                        <?php
                        $rankNumColors = [1 => 'analisis-rank--gold', 2 => 'analisis-rank--copper', 3 => 'analisis-rank--silver'];
                        $barColors = [
                            1 => '#F43F5E',
                            2 => '#A855F7',
                            3 => '#6366F1',
                        ];
                        $maxRank = !empty($ranking_administradores) ? max(array_column($ranking_administradores, 'total')) : 1;
                        foreach ($ranking_administradores as $r):
                            $rank = (int) $r['rank'];
                            $barStyle = $barColors[$rank] ?? '#334155';
                            $rankClass = $rankNumColors[$rank] ?? 'analisis-rank--grey';
                            $pct = $maxRank > 0 ? min(100, ($r['total'] / $maxRank) * 100) : 0;
                        ?>
                        <div class="analisis-ranking-item">
                            <span class="analisis-ranking-rank <?= $rankClass ?>"><?= $rank ?></span>
                            <img src="<?= !empty($r['foto_url']) ? htmlspecialchars($r['foto_url']) : 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" fill="%23334155"><circle cx="16" cy="16" r="16"/><circle cx="16" cy="12" r="5"/><path d="M16 22c-4 0-7 3-7 5v2h14v-2c0-2-3-5-7-5z"/></svg>') ?>" alt="" class="analisis-ranking-avatar" width="32" height="32">
                            <span class="analisis-ranking-nombre"><?= htmlspecialchars($r['nombre']) ?></span>
                            <div class="analisis-ranking-bar-cell">
                                <div class="analisis-ranking-bar-wrap">
                                    <div class="analisis-ranking-bar" style="width: <?= (float) $pct ?>%; background: <?= $barStyle ?>;"></div>
                                </div>
                                <span class="analisis-ranking-total"><?= number_format($r['total']) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="analisis-col analisis-col-4">
                <div class="analisis-card analisis-chart-card">
                    <h3 class="analisis-card-title">
                        <span class="analisis-chart-title-icon analisis-chart-title-icon--heatmap"><svg width="33" height="33" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg></span>
                        Plataforma vs Administrador
                    </h3>
                    <div class="analisis-heatmap-wrap">
                        <table class="analisis-heatmap-table">
                            <thead>
                                <tr>
                                    <th class="BLANCO"></th>
                                    <?php foreach ($heatmap['plataformas'] as $plat): ?>
                                    <th class="analisis-heatmap-th-name-only"><?= htmlspecialchars($plat) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $maxVal = 0;
                                foreach ($heatmap['matrix'] as $row) {
                                    foreach ($row as $v) {
                                        if ($v > $maxVal) $maxVal = $v;
                                    }
                                }
                                $maxVal = $maxVal ?: 1;
                                foreach ($heatmap['administradores'] as $i => $admin): ?>
                                <tr>
                                    <td class="analisis-heatmap-rev"><?= htmlspecialchars($admin) ?></td>
                                    <?php foreach ($heatmap['matrix'][$i] ?? [] as $val): 
                                        $intensity = $maxVal > 0 ? (float) $val / $maxVal : 0;
                                        $r = (int) round(30 + (168 - 30) * $intensity);
                                        $g = (int) round(41 + (85 - 41) * $intensity);
                                        $b = (int) round(59 + (247 - 59) * $intensity);
                                        $bg = "rgb({$r},{$g},{$b})";
                                    ?>
                                    <td class="analisis-heatmap-cell" style="background: <?= $bg ?>;"><?= (int) $val ?></td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal rango de fecha personalizado (Análisis) -->
<div id="analisisDateRangeModal" class="modal hidden" aria-hidden="true">
    <div class="modal-overlay"></div>
    <div class="modal-container activity-date-range-modal analisis-date-range-modal">
        <div class="modal-header activity-date-range-modal-header">
            <h2 class="modal-title">Selecciona un rango de tiempo</h2>
            <button type="button" class="modal-close modal-close--large" id="closeAnalisisDateModal" aria-label="Cerrar">&times;</button>
        </div>
        <div class="modal-content activity-date-range-fields">
            <div class="activity-date-field-group">
                <label class="activity-date-label">Fecha de inicio</label>
                <div class="activity-date-input-wrap">
                    <input type="date" id="analisisDateFrom" class="form-input activity-date-input" value="<?= htmlspecialchars($filter_date_from) ?>">
                </div>
            </div>
            <span class="activity-date-sep">a</span>
            <div class="activity-date-field-group">
                <label class="activity-date-label">Fecha de finalización</label>
                <div class="activity-date-input-wrap">
                    <input type="date" id="analisisDateTo" class="form-input activity-date-input" value="<?= htmlspecialchars($filter_date_to) ?>">
                </div>
            </div>
        </div>
        <div class="modal-footer activity-date-range-footer">
            <button type="button" class="btn btn-activity-date-continue" id="analisisDateRangeApply">Continuar</button>
        </div>
    </div>
</div>

<script>
window.ANALISIS_DATA = {
    evolucion: <?= json_encode($evolucion) ?>,
    ventasPorPlataforma: <?= json_encode($ventas_por_plataforma) ?>,
    ultimoValorEvolucion: <?= !empty($evolucion['values']) ? (int) $evolucion['values'][count($evolucion['values']) - 1] : 0 ?>
};
window.ANALISIS_FILTERS = {
    baseUrl: '<?= $analisisBaseUrl ?>',
    admin: <?= json_encode($filter_admin) ?>,
    plataforma_id: <?= json_encode($filter_plataforma_id) ?>
};
</script>
<?php
$content = ob_get_clean();
$title = $title ?? 'Análisis';
$show_nav = true;
$show_footer = true;
$footer_text = '';
$footer_whatsapp = false;
$additional_css = ['/assets/css/admin/main.css', '/assets/css/admin/analisis.css'];
$additional_js = ['https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', '/assets/js/admin/analisis.js'];
require base_path('views/layouts/main.php');

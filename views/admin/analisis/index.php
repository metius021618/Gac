<?php
/**
 * GAC - Vista Análisis (dashboard corporativo premium, dark mode, 12 columnas)
 * KPIs, evolución mensual, ventas por plataforma, ranking revendedores, heatmap.
 */
$total_cuentas = $total_cuentas ?? ['total' => 2590, 'crecimiento' => 15.6];
$plataformas_activas = (int) ($plataformas_activas ?? 4);
$revendedor_del_mes = $revendedor_del_mes ?? ['nombre' => 'Alejandro M.', 'foto_url' => null, 'cuentas' => 865];
$total_ingresos = $total_ingresos ?? ['total' => 103420.0, 'crecimiento' => 12.4];
$evolucion = $evolucion ?? ['labels' => [], 'values' => []];
$ventas_por_plataforma = $ventas_por_plataforma ?? [];
$ranking_revendedores = $ranking_revendedores ?? [];
$heatmap = $heatmap ?? ['revendedores' => [], 'plataformas' => [], 'matrix' => []];

$imagenes_plataformas_base = '/assets/imagenes/';
$imagenes_plataformas = [
    'netflix'  => 'netflix.png',
    'disney'   => 'disney.png',
    'hbo'      => 'HBO.jfif',
    'spotify'  => 'spotify.png',
];
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
                        <span class="analisis-kpi-growth analisis-kpi-growth--up"><svg class="analisis-growth-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"></polyline></svg>+<?= number_format($total_cuentas['crecimiento'], 1) ?>%</span>
                        <span class="analisis-kpi-sub">Mes actual</span>
                    </div>
                </div>
            </div>
            <div class="analisis-col analisis-col-3">
                <div class="analisis-card analisis-kpi-card">
                    <div class="analisis-kpi-header">
                        <span class="analisis-kpi-label">Plataformas Activas</span>
                        <span class="analisis-kpi-icon analisis-kpi-icon--spotify" title="Plataformas">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.48-1.12 0-2.16-.38-3.16-1.03-.19-.14-.23-.42-.09-.61.14-.19.42-.23.61-.09 1.02.72 2.27 1.03 3.48 1.03.24 0 .64-.03.96-.18.27-.14.36-.42.24-.66l-.01-.02zm1.44-3.3c-.3.42-.85.56-1.27.26-.99-.6-2.24-.74-3.44-.4-.31.08-.65-.09-.73-.4-.08-.31.09-.65.4-.73 1.48-.36 2.98-.2 4.22.47.42.26.56.85.82 1.6zm.12-3.36C15.24 8.4 8.94 8.16 5.16 9.75c-.49.23-1.06-.08-1.29-.57-.23-.49.08-1.06.57-1.29 4.26-1.96 11.16-1.69 15.09.5.46.21.62.82.41 1.28-.21.46-.82.62-1.28.41z"/></svg>
                        </span>
                    </div>
                    <div class="analisis-kpi-value"><?= $plataformas_activas ?></div>
                    <div class="analisis-platform-icons">
                        <?php $n = $plat_img('netflix'); if ($n): ?><img src="<?= $n ?>" alt="Netflix" class="analisis-platform-logo analisis-platform-logo--img" title="Netflix" width="36" height="36"><?php else: ?><span class="analisis-platform-logo analisis-platform-logo--netflix" title="Netflix">N</span><?php endif; ?>
                        <?php $d = $plat_img('disney'); if ($d): ?><img src="<?= $d ?>" alt="Disney+" class="analisis-platform-logo analisis-platform-logo--img" title="Disney+" width="36" height="36"><?php else: ?><span class="analisis-platform-logo analisis-platform-logo--disney" title="Disney+">D</span><?php endif; ?>
                        <?php $h = $plat_img('hbo'); if ($h): ?><img src="<?= $h ?>" alt="HBO Max" class="analisis-platform-logo analisis-platform-logo--img" title="HBO Max" width="36" height="36"><?php else: ?><span class="analisis-platform-logo analisis-platform-logo--hbo" title="HBO Max">H</span><?php endif; ?>
                        <?php $s = $plat_img('spotify'); if ($s): ?><img src="<?= $s ?>" alt="Spotify" class="analisis-platform-logo analisis-platform-logo--img" title="Spotify" width="36" height="36"><?php else: ?><span class="analisis-platform-logo analisis-platform-logo--spotify" title="Spotify">S</span><?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="analisis-col analisis-col-3">
                <div class="analisis-card analisis-kpi-card analisis-kpi-card--revendedor">
                    <div class="analisis-kpi-header">
                        <span class="analisis-kpi-label">Revendedor del Mes</span>
                        <span class="analisis-kpi-icon analisis-kpi-icon--crown" title="Revendedor destacado">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L14 8h6l-5 4 2 8-7-5-7 5 2-8-5-4h6l2-6z"/></svg>
                        </span>
                    </div>
                    <div class="analisis-revendedor-block">
                        <img src="<?= !empty($revendedor_del_mes['foto_url']) ? htmlspecialchars($revendedor_del_mes['foto_url']) : 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="%23334155"><circle cx="24" cy="24" r="24"/><circle cx="24" cy="18" r="8"/><path d="M24 32c-6 0-10 4-10 8v2h20v-2c0-4-4-8-10-8z"/></svg>') ?>" alt="" class="analisis-revendedor-avatar" width="48" height="48">
                        <div class="analisis-revendedor-info">
                            <span class="analisis-revendedor-nombre"><?= htmlspecialchars($revendedor_del_mes['nombre']) ?></span>
                            <span class="analisis-revendedor-cuentas"><?= number_format($revendedor_del_mes['cuentas']) ?> cuentas vendidas</span>
                        </div>
                    </div>
                    <div class="analisis-progress-bar">
                        <div class="analisis-progress-fill" style="width: 85%;"></div>
                    </div>
                </div>
            </div>
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
        </div>

        <!-- Fila 2: Gráfico evolución mensual -->
        <div class="analisis-row">
            <div class="analisis-col analisis-col-12">
                <div class="analisis-card analisis-chart-card">
                    <h3 class="analisis-card-title">
                        <span class="analisis-chart-title-icon analisis-chart-title-icon--line"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg></span>
                        Evolución Mensual de Ventas
                    </h3>
                    <div class="analisis-chart-wrap analisis-chart-wrap--line" style="height: 300px;">
                        <canvas id="analisisChartEvolucion" width="1200" height="300"></canvas>
                        <span class="analisis-evolucion-badge" id="analisisEvolucionBadge"><?= number_format(!empty($evolucion['values']) ? $evolucion['values'][count($evolucion['values'])-1] : 2590) ?></span>
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
                    <div class="analisis-bar-chart-logos">
                        <?php $bn = $plat_img('netflix'); if ($bn): ?><img src="<?= $bn ?>" alt="Netflix" class="analisis-bar-logo analisis-bar-logo--img" title="Netflix" width="32" height="32"><?php else: ?><span class="analisis-bar-logo analisis-bar-logo--netflix" title="Netflix">N</span><?php endif; ?>
                        <?php $bd = $plat_img('disney'); if ($bd): ?><img src="<?= $bd ?>" alt="Disney+" class="analisis-bar-logo analisis-bar-logo--img" title="Disney+" width="32" height="32"><?php else: ?><span class="analisis-bar-logo analisis-bar-logo--disney" title="Disney+">D</span><?php endif; ?>
                        <?php $bh = $plat_img('hbo'); if ($bh): ?><img src="<?= $bh ?>" alt="HBO Max" class="analisis-bar-logo analisis-bar-logo--img" title="HBO Max" width="32" height="32"><?php else: ?><span class="analisis-bar-logo analisis-bar-logo--hbo" title="HBO Max">H</span><?php endif; ?>
                        <?php $bs = $plat_img('spotify'); if ($bs): ?><img src="<?= $bs ?>" alt="Spotify" class="analisis-bar-logo analisis-bar-logo--img" title="Spotify" width="32" height="32"><?php else: ?><span class="analisis-bar-logo analisis-bar-logo--spotify" title="Spotify">S</span><?php endif; ?>
                    </div>
                    <div class="analisis-chart-wrap analisis-chart-wrap--bar" style="height: 280px;">
                        <canvas id="analisisChartPlataformas" width="400" height="280"></canvas>
                    </div>
                </div>
            </div>
            <div class="analisis-col analisis-col-4">
                <div class="analisis-card analisis-chart-card">
                    <h3 class="analisis-card-title">
                        <span class="analisis-chart-title-icon analisis-chart-title-icon--users"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></span>
                        Ranking de Revendedores
                    </h3>
                    <div class="analisis-ranking-list" id="analisisRankingList">
                        <?php
                        $rankColors = [
                            1 => 'linear-gradient(135deg, #F43F5E, #FB7185)',
                            2 => 'linear-gradient(135deg, #A855F7, #C084FC)',
                            3 => 'linear-gradient(135deg, #6366F1, #818CF8)',
                        ];
                        foreach ($ranking_revendedores as $r):
                            $barStyle = $rankColors[$r['rank']] ?? '#334155';
                        ?>
                        <div class="analisis-ranking-item">
                            <span class="analisis-ranking-rank"><?= (int) $r['rank'] ?></span>
                            <img src="<?= !empty($r['foto_url']) ? htmlspecialchars($r['foto_url']) : 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" fill="%23334155"><circle cx="16" cy="16" r="16"/><circle cx="16" cy="12" r="5"/><path d="M16 22c-4 0-7 3-7 5v2h14v-2c0-2-3-5-7-5z"/></svg>') ?>" alt="" class="analisis-ranking-avatar" width="32" height="32">
                            <span class="analisis-ranking-nombre"><?= htmlspecialchars($r['nombre']) ?></span>
                            <span class="analisis-ranking-total"><?= number_format($r['total']) ?></span>
                            <div class="analisis-ranking-bar-wrap">
                                <?php $maxRank = !empty($ranking_revendedores) ? max(array_column($ranking_revendedores, 'total')) : 865; $pct = $maxRank > 0 ? min(100, ($r['total'] / $maxRank) * 100) : 0; ?>
                                <div class="analisis-ranking-bar" style="width: <?= (float) $pct ?>%; background: <?= $barStyle ?>;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="analisis-col analisis-col-4">
                <div class="analisis-card analisis-chart-card">
                    <h3 class="analisis-card-title">
                        <span class="analisis-chart-title-icon analisis-chart-title-icon--heatmap"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg></span>
                        Plataforma vs Revendedor
                    </h3>
                    <div class="analisis-heatmap-wrap">
                        <table class="analisis-heatmap-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <?php foreach ($heatmap['plataformas'] as $plat): 
                                        $logoKey = (stripos($plat, 'Netflix') !== false) ? 'netflix' : ((stripos($plat, 'Disney') !== false) ? 'disney' : ((stripos($plat, 'HBO') !== false) ? 'hbo' : ((stripos($plat, 'Spotify') !== false) ? 'spotify' : null)));
                                        $logoUrl = $logoKey ? $plat_img($logoKey) : '';
                                    ?>
                                    <th class="analisis-heatmap-th-with-logo">
                                        <?php if ($logoUrl): ?><img src="<?= $logoUrl ?>" alt="<?= htmlspecialchars($plat) ?>" class="analisis-heatmap-th-logo analisis-heatmap-th-logo--img" width="28" height="28"><?php else: ?><span class="analisis-heatmap-th-logo"><?= substr($plat, 0, 1) ?></span><?php endif; ?>
                                        <span class="analisis-heatmap-th-name"><?= htmlspecialchars($plat) ?></span>
                                    </th>
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
                                foreach ($heatmap['revendedores'] as $i => $rev): ?>
                                <tr>
                                    <td class="analisis-heatmap-rev"><?= htmlspecialchars($rev) ?></td>
                                    <?php foreach ($heatmap['matrix'][$i] ?? [] as $val): 
                                        $intensity = $maxVal > 0 ? $val / $maxVal : 0;
                                        if ($intensity <= 0.25) $cls = 'analisis-heatmap--low';
                                        elseif ($intensity <= 0.5) $cls = 'analisis-heatmap--mid';
                                        elseif ($intensity <= 0.75) $cls = 'analisis-heatmap--high';
                                        else $cls = 'analisis-heatmap--veryhigh';
                                    ?>
                                    <td class="analisis-heatmap-cell <?= $cls ?>"><?= (int) $val ?></td>
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

<script>
window.ANALISIS_DATA = {
    evolucion: <?= json_encode($evolucion) ?>,
    ventasPorPlataforma: <?= json_encode($ventas_por_plataforma) ?>,
    ultimoValorEvolucion: <?= !empty($evolucion['values']) ? (int) $evolucion['values'][count($evolucion['values']) - 1] : 2590 ?>
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

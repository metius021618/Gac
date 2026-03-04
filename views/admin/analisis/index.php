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
                        <span class="analisis-kpi-growth analisis-kpi-growth--up">+<?= number_format($total_cuentas['crecimiento'], 1) ?>%</span>
                        <span class="analisis-kpi-sub">Mes actual</span>
                    </div>
                </div>
            </div>
            <div class="analisis-col analisis-col-3">
                <div class="analisis-card analisis-kpi-card">
                    <div class="analisis-kpi-header">
                        <span class="analisis-kpi-label">Plataformas Activas</span>
                    </div>
                    <div class="analisis-kpi-value"><?= $plataformas_activas ?></div>
                    <div class="analisis-platform-icons">
                        <span class="analisis-platform-icon" title="Netflix">N</span>
                        <span class="analisis-platform-icon" title="Disney+">D</span>
                        <span class="analisis-platform-icon" title="HBO Max">H</span>
                        <span class="analisis-platform-icon" title="Spotify">S</span>
                    </div>
                </div>
            </div>
            <div class="analisis-col analisis-col-3">
                <div class="analisis-card analisis-kpi-card analisis-kpi-card--revendedor">
                    <div class="analisis-kpi-header">
                        <span class="analisis-kpi-label">Revendedor del Mes</span>
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
                    </div>
                    <div class="analisis-kpi-value analisis-kpi-value--accent">$<?= number_format($total_ingresos['total'], 0) ?></div>
                    <div class="analisis-kpi-meta">
                        <span class="analisis-kpi-growth analisis-kpi-growth--up">+<?= number_format($total_ingresos['crecimiento'], 1) ?>%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fila 2: Gráfico evolución mensual -->
        <div class="analisis-row">
            <div class="analisis-col analisis-col-12">
                <div class="analisis-card analisis-chart-card">
                    <h3 class="analisis-card-title">Evolución Mensual de Ventas</h3>
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
                    <h3 class="analisis-card-title">Ventas por Plataforma</h3>
                    <div class="analisis-chart-wrap analisis-chart-wrap--bar" style="height: 280px;">
                        <canvas id="analisisChartPlataformas" width="400" height="280"></canvas>
                    </div>
                </div>
            </div>
            <div class="analisis-col analisis-col-4">
                <div class="analisis-card analisis-chart-card">
                    <h3 class="analisis-card-title">Ranking de Revendedores</h3>
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
                    <h3 class="analisis-card-title">Plataforma vs Revendedor</h3>
                    <div class="analisis-heatmap-wrap">
                        <table class="analisis-heatmap-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <?php foreach ($heatmap['plataformas'] as $plat): ?>
                                    <th><?= htmlspecialchars($plat) ?></th>
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

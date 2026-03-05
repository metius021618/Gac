<?php
/**
 * GAC - Controlador de Análisis (dashboard corporativo premium)
 * Solo superadmin. Filtros: Fecha, Plataforma, Revendedor. Datos desde user_access.
 *
 * @package Gac\Controllers
 */

namespace Gac\Controllers;

use Gac\Core\Request;
use Gac\Repositories\AnalisisRepository;
use Gac\Repositories\PlatformRepository;

class AnalisisController
{
    public function index(Request $request): void
    {
        if (!function_exists('is_superadmin') || !is_superadmin()) {
            redirect('/admin/dashboard');
            return;
        }

        $dateFrom = trim((string) $request->get('date_from', ''));
        $dateTo = trim((string) $request->get('date_to', ''));
        $timeRange = trim((string) $request->get('time_range', ''));
        $platformId = $request->get('platform_id');
        $revendedor = trim((string) $request->get('revendedor', ''));

        if ($timeRange === '7') {
            $dateFrom = date('Y-m-d', strtotime('-7 days'));
            $dateTo = date('Y-m-d');
        } elseif ($timeRange === '30') {
            $dateFrom = date('Y-m-d', strtotime('-30 days'));
            $dateTo = date('Y-m-d');
        } elseif ($timeRange === '90') {
            $dateFrom = date('Y-m-d', strtotime('-90 days'));
            $dateTo = date('Y-m-d');
        } elseif ($timeRange !== 'custom' && ($dateFrom || $dateTo)) {
            $timeRange = 'custom';
        }

        $filters = [
            'date_from' => $dateFrom ?: null,
            'date_to' => $dateTo ?: null,
            'platform_id' => $platformId !== null && $platformId !== '' ? (int) $platformId : null,
            'revendedor' => $revendedor !== '' ? $revendedor : null,
        ];

        $isDemo = (string) $request->get('demo', '') === '1';

        $repo = new AnalisisRepository();
        $platformRepo = new PlatformRepository();

        if ($isDemo) {
            $totalCuentas = ['total' => 2590, 'crecimiento' => 0];
            $plataformasActivas = 4;
            $plataformasActivasList = $platformRepo->findAllEnabled();
            if (empty($plataformasActivasList)) {
                $plataformasActivasList = [
                    ['id' => 1, 'name' => 'netflix', 'display_name' => 'Netflix'],
                    ['id' => 2, 'name' => 'disney', 'display_name' => 'Disney+'],
                    ['id' => 3, 'name' => 'hbo', 'display_name' => 'HBO Max'],
                    ['id' => 4, 'name' => 'spotify', 'display_name' => 'Spotify'],
                ];
            }
            $revendedorDelMes = ['nombre' => 'Alejandro M.', 'foto_url' => null, 'cuentas' => 865];
            $totalIngresos = ['total' => 103420.0, 'crecimiento' => 12.4];
            $evolucion = [
                'labels' => ["Ene 5", "Ene 12", "Ene 19", "Ene 26", "Feb 2", "Feb 9", "Feb 16", "Feb 23", "Mar 2", "Mar 9", "Mar 16", "Mar 23"],
                'values' => [1820, 1950, 2100, 1980, 2240, 2380, 2210, 2450, 2520, 2480, 2610, 2590],
            ];
            $ventasPorPlataforma = [
                ['nombre' => 'Netflix', 'total' => 1085, 'color' => '#E50914'],
                ['nombre' => 'Disney+', 'total' => 760, 'color' => '#1F80E0'],
                ['nombre' => 'HBO Max', 'total' => 430, 'color' => '#8B5CF6'],
                ['nombre' => 'Spotify', 'total' => 315, 'color' => '#1DB954'],
            ];
            $rankingRevendedores = [
                ['nombre' => 'Alejandro M.', 'foto_url' => null, 'total' => 865, 'rank' => 1],
                ['nombre' => 'Maria G.', 'foto_url' => null, 'total' => 752, 'rank' => 2],
                ['nombre' => 'Javier R.', 'foto_url' => null, 'total' => 610, 'rank' => 3],
                ['nombre' => 'Carlos S.', 'foto_url' => null, 'total' => 548, 'rank' => 4],
                ['nombre' => 'Laura P.', 'foto_url' => null, 'total' => 503, 'rank' => 5],
                ['nombre' => 'Pedro T.', 'foto_url' => null, 'total' => 439, 'rank' => 6],
            ];
            $heatmap = [
                'revendedores' => ['Alejandro M.', 'Maria G.', 'Javier R.', 'Carlos S.'],
                'plataformas' => ['Netflix', 'Disney+', 'HBO Max', 'Spotify'],
                'matrix' => [
                    [340, 255, 178, 92],
                    [235, 234, 150, 83],
                    [212, 164, 125, 111],
                    [165, 107, 93, 95],
                ],
            ];
            $platformsForFilter = array_map(function ($p) {
                return ['id' => (int) $p['id'], 'display_name' => $p['display_name'] ?? $p['name'] ?? ''];
            }, array_slice($plataformasActivasList, 0, 10));
            if (empty($platformsForFilter)) {
                $platformsForFilter = [
                    ['id' => 1, 'display_name' => 'Netflix'],
                    ['id' => 2, 'display_name' => 'Disney+'],
                    ['id' => 3, 'display_name' => 'HBO Max'],
                    ['id' => 4, 'display_name' => 'Spotify'],
                ];
            }
            $revendedoresForFilter = ['Alejandro M.', 'Maria G.', 'Javier R.', 'Carlos S.', 'Laura P.', 'Pedro T.'];
        } else {
            $totalCuentas = $repo->getTotalCuentasKpi($filters);
            $plataformasActivas = $repo->getPlataformasActivasCount();
            $plataformasActivasList = $platformRepo->findAllEnabled();
            $revendedorDelMes = $repo->getRevendedorDelMes();
            $totalIngresos = $repo->getTotalIngresosKpi();
            $evolucion = $repo->getEvolucionMensual($filters);
            $ventasPorPlataforma = $repo->getVentasPorPlataforma($filters);
            $rankingRevendedores = $repo->getRankingRevendedores($filters);
            $heatmap = $repo->getHeatmapPlataformaRevendedor($filters);
            $platformsForFilter = $repo->getPlatformsForFilter();
            $revendedoresForFilter = $repo->getRevendedoresForFilter();
        }

        $timeRangeLabel = 'Todo';
        if ($timeRange === '7') {
            $timeRangeLabel = 'Últimos 7 días';
        } elseif ($timeRange === '30') {
            $timeRangeLabel = 'Últimos 30 días';
        } elseif ($timeRange === '90') {
            $timeRangeLabel = 'Últimos 90 días';
        } elseif ($dateFrom && $dateTo) {
            $timeRangeLabel = 'Personalizado';
        }

        $this->renderView('admin/analisis/index', [
            'title' => 'Análisis',
            'is_demo' => $isDemo,
            'total_cuentas' => $totalCuentas,
            'plataformas_activas' => $plataformasActivas,
            'plataformas_activas_list' => $plataformasActivasList,
            'revendedor_del_mes' => $revendedorDelMes,
            'total_ingresos' => $totalIngresos,
            'evolucion' => $evolucion,
            'ventas_por_plataforma' => $ventasPorPlataforma,
            'ranking_revendedores' => $rankingRevendedores,
            'heatmap' => $heatmap,
            'filter_date_from' => $dateFrom,
            'filter_date_to' => $dateTo,
            'filter_time_range' => $timeRange,
            'filter_platform_id' => $platformId,
            'filter_revendedor' => $revendedor,
            'time_range_label' => $timeRangeLabel,
            'platforms_for_filter' => $platformsForFilter,
            'revendedores_for_filter' => $revendedoresForFilter,
        ]);
    }

    private function renderView(string $view, array $data = []): void
    {
        extract($data);
        $viewPath = base_path('views/' . str_replace('.', '/', $view) . '.php');
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            http_response_code(404);
            echo "Vista no encontrada: {$view}";
        }
    }
}

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

        $repo = new AnalisisRepository();
        $platformRepo = new PlatformRepository();

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

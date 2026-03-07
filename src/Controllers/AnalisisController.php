<?php
/**
 * GAC - Controlador de Análisis (dashboard corporativo premium)
 * Solo superadmin. KPIs, evolución mensual, ventas por plataforma, ranking, heatmap.
 * Filtros: Fecha (7/30/90 días o personalizado), Administrador, Plataforma.
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

        $timeRange = $request->get('time_range') ?? '30';
        $dateFrom = $request->get('date_from') ?? '';
        $dateTo = $request->get('date_to') ?? '';
        $filterAdmin = $request->get('admin') ?? '';
        $filterPlataformaId = $request->get('plataforma_id') !== null && $request->get('plataforma_id') !== '' ? (int) $request->get('plataforma_id') : null;
        $mode = $request->get('mode') === 'revendedores' ? 'revendedores' : 'administradores';

        $today = date('Y-m-d');
        if ($timeRange === '7') {
            $dateFrom = $dateFrom ?: date('Y-m-d', strtotime('-7 days'));
            $dateTo = $dateTo ?: $today;
        } elseif ($timeRange === '30') {
            $dateFrom = $dateFrom ?: date('Y-m-d', strtotime('-30 days'));
            $dateTo = $dateTo ?: $today;
        } elseif ($timeRange === '90') {
            $dateFrom = $dateFrom ?: date('Y-m-d', strtotime('-90 days'));
            $dateTo = $dateTo ?: $today;
        } else {
            // personalizado: usar date_from y date_to del request
            if (!$dateFrom) {
                $dateFrom = date('Y-m-d', strtotime('-30 days'));
            }
            if (!$dateTo) {
                $dateTo = $today;
            }
        }

        $repo = new AnalisisRepository();
        $platformRepo = new PlatformRepository();
        $totalCuentas = $repo->getTotalCuentasKpi();
        $plataformasActivas = $repo->getPlataformasActivasCount();
        $plataformasActivasList = $platformRepo->findAllEnabled();
        $administradoresParaFiltro = $repo->getAdministradoresParaFiltro();
        $plataformasParaFiltro = $repo->getPlataformasParaFiltro();

        if ($mode === 'revendedores') {
            $administradorDelMes = $repo->getRevendedorDelMes();
            $totalIngresos = ['total' => 0.0, 'crecimiento' => 0];
            $evolucion = $repo->getEvolucionMensualRevendedores($dateFrom, $dateTo);
            $ventasPorPlataforma = $repo->getVentasPorPlataformaRevendedores($dateFrom, $dateTo);
            $rankingAdministradores = $repo->getRankingRevendedores();
            $heatmap = $repo->getHeatmapPlataformaRevendedor();
        } else {
            $administradorDelMes = $repo->getAdministradorDelMes();
            $totalIngresos = $repo->getTotalIngresosKpi();
            $evolucion = $repo->getEvolucionMensual($dateFrom, $dateTo, $filterAdmin ?: null, $filterPlataformaId);
            $ventasPorPlataforma = $repo->getVentasPorPlataforma($dateFrom, $dateTo, $filterAdmin ?: null, $filterPlataformaId);
            $rankingAdministradores = $repo->getRankingAdministradores($dateFrom, $dateTo, null);
            $heatmap = $repo->getHeatmapPlataformaAdministrador($dateFrom, $dateTo, $filterAdmin ?: null);
        }

        $this->renderView('admin/analisis/index', [
            'title' => 'Análisis',
            'analisis_mode' => $mode,
            'total_cuentas' => $totalCuentas,
            'plataformas_activas' => $plataformasActivas,
            'plataformas_activas_list' => $plataformasActivasList,
            'administrador_del_mes' => $administradorDelMes,
            'total_ingresos' => $totalIngresos,
            'evolucion' => $evolucion,
            'ventas_por_plataforma' => $ventasPorPlataforma,
            'ranking_administradores' => $rankingAdministradores,
            'heatmap' => $heatmap,
            'filter_time_range' => $timeRange,
            'filter_date_from' => $dateFrom,
            'filter_date_to' => $dateTo,
            'filter_admin' => $filterAdmin,
            'filter_plataforma_id' => $filterPlataformaId,
            'administradores_para_filtro' => $administradoresParaFiltro,
            'plataformas_para_filtro' => $plataformasParaFiltro,
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

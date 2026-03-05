<?php
/**
 * GAC - Controlador de Análisis (dashboard corporativo premium)
 * Solo superadmin. KPIs, evolución mensual, ventas por plataforma, ranking, heatmap.
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

        $repo = new AnalisisRepository();
        $platformRepo = new PlatformRepository();
        $totalCuentas = $repo->getTotalCuentasKpi();
        $plataformasActivas = $repo->getPlataformasActivasCount();
        $plataformasActivasList = $platformRepo->findAllEnabled();
        $revendedorDelMes = $repo->getRevendedorDelMes();
        $totalIngresos = $repo->getTotalIngresosKpi();
        $evolucion = $repo->getEvolucionMensual();
        $ventasPorPlataforma = $repo->getVentasPorPlataforma();
        $rankingRevendedores = $repo->getRankingRevendedores();
        $heatmap = $repo->getHeatmapPlataformaRevendedor();

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

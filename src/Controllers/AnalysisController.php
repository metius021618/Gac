<?php
/**
 * GAC - Controlador de Análisis (solo superadmin)
 * Gráfico de barras horizontales por plataforma con filtros de tiempo.
 *
 * @package Gac\Controllers
 */

namespace Gac\Controllers;

use Gac\Core\Request;
use Gac\Repositories\UserAccessRepository;

class AnalysisController
{
    private UserAccessRepository $userAccessRepo;

    public function __construct()
    {
        $this->userAccessRepo = new UserAccessRepository();
    }

    /**
     * Vista Análisis: gráfico por plataformas y filtros de tiempo (7/30/90 días, personalizado).
     */
    public function index(Request $request): void
    {
        if (!function_exists('is_superadmin') || !is_superadmin()) {
            redirect('/admin/dashboard');
            return;
        }

        $filterDateFrom = trim((string) $request->get('date_from', ''));
        $filterDateTo = trim((string) $request->get('date_to', ''));
        $filterTimeRange = trim((string) $request->get('time_range', ''));

        $dateFrom = null;
        $dateTo = null;
        if ($filterTimeRange === '7') {
            $dateFrom = date('Y-m-d', strtotime('-7 days'));
            $dateTo = date('Y-m-d');
        } elseif ($filterTimeRange === '30') {
            $dateFrom = date('Y-m-d', strtotime('-30 days'));
            $dateTo = date('Y-m-d');
        } elseif ($filterTimeRange === '90') {
            $dateFrom = date('Y-m-d', strtotime('-90 days'));
            $dateTo = date('Y-m-d');
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDateFrom) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDateTo)) {
            $dateFrom = $filterDateFrom;
            $dateTo = $filterDateTo;
        }

        $platformCounts = $this->userAccessRepo->getCountByPlatform($dateFrom, $dateTo);

        $this->renderView('admin/analysis/index', [
            'title' => 'Análisis',
            'platform_counts' => $platformCounts,
            'filter_date_from' => $filterDateFrom,
            'filter_date_to' => $filterDateTo,
            'filter_time_range' => $filterTimeRange,
        ]);
    }

    /**
     * API JSON para actualizar el gráfico sin recargar (date_from, date_to, time_range).
     */
    public function data(Request $request): void
    {
        if (!function_exists('is_superadmin') || !is_superadmin()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            return;
        }
        $filterDateFrom = trim((string) $request->get('date_from', ''));
        $filterDateTo = trim((string) $request->get('date_to', ''));
        $filterTimeRange = trim((string) $request->get('time_range', ''));

        $dateFrom = null;
        $dateTo = null;
        if ($filterTimeRange === '7') {
            $dateFrom = date('Y-m-d', strtotime('-7 days'));
            $dateTo = date('Y-m-d');
        } elseif ($filterTimeRange === '30') {
            $dateFrom = date('Y-m-d', strtotime('-30 days'));
            $dateTo = date('Y-m-d');
        } elseif ($filterTimeRange === '90') {
            $dateFrom = date('Y-m-d', strtotime('-90 days'));
            $dateTo = date('Y-m-d');
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDateFrom) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDateTo)) {
            $dateFrom = $filterDateFrom;
            $dateTo = $filterDateTo;
        }

        $platformCounts = $this->userAccessRepo->getCountByPlatform($dateFrom, $dateTo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'platform_counts' => $platformCounts,
            'time_range' => $filterTimeRange,
            'date_from' => $filterDateFrom,
            'date_to' => $filterDateTo,
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

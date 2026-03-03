<?php
/**
 * GAC - Controlador de Actividad de Usuario
 * Vista solo para superadmin (role_id 1). Monitorea acciones de otros usuarios.
 *
 * @package Gac\Controllers
 */

namespace Gac\Controllers;

use Gac\Core\Request;
use Gac\Repositories\UserActivityLogRepository;

class UserActivityController
{
    /**
     * Listar actividad de usuarios (solo superadmin)
     */
    public function index(Request $request): void
    {
        if (!function_exists('is_superadmin') || !is_superadmin()) {
            redirect('/admin/dashboard');
            return;
        }

        $page = max(1, (int) $request->get('page', 1));
        $perPage = (int) $request->get('per_page', 50);
        $order = strtolower((string) $request->get('order', 'desc'));
        if (!in_array($order, ['asc', 'desc'], true)) {
            $order = 'desc';
        }
        $validPerPage = [50, 75, 100, 0];
        if (!in_array($perPage, $validPerPage, true)) {
            $perPage = 50;
        }
        $filterAction = trim((string) $request->get('action', ''));
        $filterAdmin = trim((string) $request->get('admin', ''));
        $filterDateFrom = trim((string) $request->get('date_from', ''));
        $filterDateTo = trim((string) $request->get('date_to', ''));
        $filterTimeRange = trim((string) $request->get('time_range', ''));

        $repo = new UserActivityLogRepository();
        $result = $repo->getPaginated($page, $perPage, $order, $filterAction ?: null, $filterAdmin ?: null, $filterDateFrom ?: null, $filterDateTo ?: null);
        $usernames = $repo->getUniqueUsernames();

        $this->renderView('admin/user_activity/index', [
            'title' => 'Actividad de administrador',
            'activities' => $result['data'],
            'total_records' => $result['total'],
            'current_page' => $result['page'],
            'per_page' => $result['per_page'],
            'total_pages' => $result['total_pages'],
            'order' => $order,
            'valid_per_page' => $validPerPage,
            'filter_action' => $filterAction,
            'filter_admin' => $filterAdmin,
            'filter_date_from' => $filterDateFrom,
            'filter_date_to' => $filterDateTo,
            'filter_time_range' => $filterTimeRange,
            'usernames' => $usernames,
        ]);
    }

    /**
     * Exportar actividad filtrada a Excel (solo lo mostrado/filtrado)
     */
    public function exportExcel(Request $request): void
    {
        if (!function_exists('is_superadmin') || !is_superadmin()) {
            redirect('/admin/dashboard');
            return;
        }
        $filterAction = trim((string) $request->get('action', ''));
        $filterAdmin = trim((string) $request->get('admin', ''));
        $filterDateFrom = trim((string) $request->get('date_from', ''));
        $filterDateTo = trim((string) $request->get('date_to', ''));
        $repo = new UserActivityLogRepository();
        $result = $repo->getPaginated(1, 0, 'desc', $filterAction ?: null, $filterAdmin ?: null, $filterDateFrom ?: null, $filterDateTo ?: null);
        $rows = $result['data'] ?? [];

        $filename = 'actividad_administrador_' . date('Y-m-d_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        echo '<table border="1" cellpadding="2" cellspacing="0" style="border-collapse:collapse;">';
        echo '<tr style="background:#2563eb;color:#ffffff;font-weight:bold;">';
        echo '<td>Usuario</td><td>Acción</td><td>Descripción</td><td>Fecha</td><td>Hora</td>';
        echo '</tr>';
        foreach ($rows as $r) {
            $created = $r['created_at'] ?? '';
            $datePart = $created ? date('d/m/Y', strtotime($created)) : '—';
            $timePart = $created ? date('H:i', strtotime($created)) : '—';
            $actionLabel = \Gac\Repositories\UserActivityLogRepository::actionLabel($r['action'] ?? '');
            echo '<tr>';
            echo '<td>' . htmlspecialchars($r['username'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($actionLabel, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($r['description'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($datePart, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($timePart, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        exit;
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

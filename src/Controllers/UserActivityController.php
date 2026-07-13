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

        excel_utf8_output_start();

        echo '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse:collapse;">';
        echo '<tr style="background:#2563eb;color:#ffffff;font-weight:bold;">';
        echo '<td style="text-align:center;vertical-align:middle;">Acción</td>';
        echo '<td style="text-align:center;vertical-align:middle;">Administrador</td>';
        echo '<td style="text-align:center;vertical-align:middle;">Descripción</td>';
        echo '<td style="text-align:center;vertical-align:middle;">Fecha</td>';
        echo '</tr>';
        foreach ($rows as $r) {
            $fechaHora = format_datetime_peru($r['created_at'] ?? null);
            $actionLabel = \Gac\Repositories\UserActivityLogRepository::actionLabel($r['action'] ?? '');
            $description = $r['description'] ?? '';
            if ($description !== '' && !mb_check_encoding($description, 'UTF-8')) {
                $description = mb_convert_encoding($description, 'UTF-8', 'ISO-8859-1,Windows-1252,UTF-8');
            }
            echo '<tr>';
            echo '<td style="text-align:center;vertical-align:middle;">' . htmlspecialchars($actionLabel, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td style="text-align:center;vertical-align:middle;">' . htmlspecialchars($r['username'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td style="text-align:left;vertical-align:middle;">' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td style="text-align:center;vertical-align:middle;">' . htmlspecialchars($fechaHora, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        excel_utf8_output_end();
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

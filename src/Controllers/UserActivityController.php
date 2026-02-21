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
        $perPage = (int) $request->get('per_page', 15);
        $order = strtolower((string) $request->get('order', 'desc'));
        if (!in_array($order, ['asc', 'desc'], true)) {
            $order = 'desc';
        }
        $validPerPage = [15, 30, 45, 60, 100];
        if (!in_array($perPage, $validPerPage, true)) {
            $perPage = 15;
        }

        $repo = new UserActivityLogRepository();
        $result = $repo->getPaginated($page, $perPage, $order);

        $this->renderView('admin/user_activity/index', [
            'title' => 'Actividad de usuario',
            'activities' => $result['data'],
            'total_records' => $result['total'],
            'current_page' => $result['page'],
            'per_page' => $result['per_page'],
            'total_pages' => $result['total_pages'],
            'order' => $order,
            'valid_per_page' => $validPerPage,
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

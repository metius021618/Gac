<?php
/**
 * GAC - Controlador de Usuarios/Clientes
 * 
 * @package Gac\Controllers
 */

namespace Gac\Controllers;

use Gac\Core\Request;

class UserController
{
    /**
     * Listar usuarios revendedores (no administradores).
     * Solo muestra los usuarios auto-generados para revendedores.
     */
    public function index(Request $request): void
    {
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 15);
        $search = trim((string) $request->get('search', ''));

        $validPerPage = [15, 30, 60, 100, 0];
        if (!in_array($perPage, $validPerPage, true)) {
            $perPage = 15;
        }

        $userRepository = new \Gac\Repositories\UserRepository();
        $paginationData = $userRepository->searchResellersPaginate($page, $perPage, $search);

        $this->renderView('admin/users/index', [
            'title' => 'Usuarios (Revendedores)',
            'users' => $paginationData['data'],
            'total_records' => $paginationData['total'],
            'current_page' => $paginationData['page'],
            'per_page' => $paginationData['per_page'],
            'total_pages' => $paginationData['total_pages'],
            'search_query' => $search,
            'valid_per_page' => $validPerPage
        ]);
    }

    /**
     * Activar/desactivar usuario revendedor.
     */
    public function toggleActive(Request $request): void
    {
        if ($request->method() !== 'POST') {
            redirect('/admin/users');
            return;
        }

        $id = (int) $request->input('id', 0);
        $active = (int) $request->input('active', 0);

        if ($id <= 0) {
            redirect('/admin/users');
            return;
        }

        $repo = new \Gac\Repositories\UserRepository();
        $user = $repo->findById($id);
        if (!$user) {
            redirect('/admin/users');
            return;
        }

        // Asegurarnos de que es un revendedor (no admin)
        $db = \Gac\Helpers\Database::getConnection();
        $stmt = $db->prepare("SELECT r.name FROM roles r WHERE r.id = :id LIMIT 1");
        $stmt->execute([':id' => (int) $user['role_id']]);
        $role = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$role || strtoupper((string) ($role['name'] ?? '')) !== 'REVENDEDOR') {
            redirect('/admin/users');
            return;
        }

        $active = $active ? 1 : 0;
        $repo->update($id, ['active' => $active]);
        redirect('/admin/users');
    }

    /**
     * Eliminar usuario revendedor.
     */
    public function delete(Request $request): void
    {
        if ($request->method() !== 'POST') {
            redirect('/admin/users');
            return;
        }

        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            redirect('/admin/users');
            return;
        }

        $repo = new \Gac\Repositories\UserRepository();
        $user = $repo->findById($id);
        if (!$user) {
            redirect('/admin/users');
            return;
        }

        // Validar que sea revendedor
        $db = \Gac\Helpers\Database::getConnection();
        $stmt = $db->prepare("SELECT r.name FROM roles r WHERE r.id = :id LIMIT 1");
        $stmt->execute([':id' => (int) $user['role_id']]);
        $role = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$role || strtoupper((string) ($role['name'] ?? '')) !== 'REVENDEDOR') {
            redirect('/admin/users');
            return;
        }

        $repo->delete($id);
        redirect('/admin/users');
    }

    /**
     * Renderizar vista
     */
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

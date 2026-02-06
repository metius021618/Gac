<?php
/**
 * GAC - Controlador de Plataformas
 * 
 * @package Gac\Controllers
 */

namespace Gac\Controllers;

use Gac\Core\Request;
use Gac\Repositories\PlatformRepository;

class PlatformController
{
    private PlatformRepository $platformRepository;

    public function __construct()
    {
        $this->platformRepository = new PlatformRepository();
    }

    /**
     * Listar plataformas activas
     */
    public function index(Request $request): void
    {
        $page = (int)$request->get('page', 1);
        $perPage = (int)$request->get('per_page', 15);
        $search = $request->get('search', '');

        $validPerPage = [15, 30, 60, 100, 0]; // 0 para "Todos"
        if (!in_array($perPage, $validPerPage)) {
            $perPage = 15; // Default
        }

        $paginationData = $this->platformRepository->searchAndPaginate($page, $perPage, $search);
        
        // Si es petición AJAX, devolver solo la tabla y paginación
        if ($request->isAjax()) {
            ob_start();
            extract([
                'platforms' => $paginationData['data'],
                'total_records' => $paginationData['total'],
                'current_page' => $paginationData['page'],
                'per_page' => $paginationData['per_page'],
                'total_pages' => $paginationData['total_pages'],
                'search_query' => $search,
                'valid_per_page' => $validPerPage
            ]);
            require base_path('views/admin/platforms/_table.php');
            $tableHtml = ob_get_clean();
            
            // Envolver en admin-content para que SearchAJAX.updateTableContent funcione
            echo '<div class="admin-content">' . $tableHtml . '</div>';
            return;
        }
        
        $this->renderView('admin/platforms/index', [
            'title' => 'Plataformas Activas',
            'platforms' => $paginationData['data'],
            'total_records' => $paginationData['total'],
            'current_page' => $paginationData['page'],
            'per_page' => $paginationData['per_page'],
            'total_pages' => $paginationData['total_pages'],
            'search_query' => $search,
            'valid_per_page' => $validPerPage
        ]);
    }

    /**
     * Toggle activar/desactivar plataforma (AJAX)
     */
    public function toggleStatus(Request $request): void
    {
        if ($request->method() !== 'POST') {
            json_response(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        $id = (int)$request->input('id', 0);
        $enabled = (int)$request->input('enabled', 0);

        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'ID inválido'], 400);
            return;
        }

        $result = $this->platformRepository->toggleEnabled($id, (bool)$enabled);

        if ($result) {
            json_response([
                'success' => true,
                'message' => $enabled ? 'Plataforma activada' : 'Plataforma desactivada',
                'enabled' => $enabled
            ]);
        } else {
            json_response(['success' => false, 'message' => 'Error al actualizar'], 500);
        }
    }

    /**
     * Eliminar plataforma (AJAX)
     */
    public function destroy(Request $request): void
    {
        if ($request->method() !== 'POST') {
            json_response(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'ID inválido'], 400);
            return;
        }

        $deleted = $this->platformRepository->delete($id);
        if ($deleted) {
            json_response(['success' => true, 'message' => 'Plataforma eliminada correctamente']);
        } else {
            json_response(['success' => false, 'message' => 'Error al eliminar la plataforma'], 500);
        }
    }

    /**
     * Crear nueva plataforma (AJAX)
     */
    public function store(Request $request): void
    {
        if ($request->method() !== 'POST') {
            json_response(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        $name = trim($request->input('name', ''));
        $displayName = trim($request->input('display_name', ''));
        $enabled = (int)$request->input('enabled', 1);

        if (empty($name) || empty($displayName)) {
            json_response(['success' => false, 'message' => 'Nombre y slug son obligatorios'], 400);
            return;
        }

        // Verificar si el slug ya existe
        $existing = $this->platformRepository->findByName($name);
        if ($existing) {
            json_response(['success' => false, 'message' => 'Ya existe una plataforma con ese slug'], 400);
            return;
        }

        $id = $this->platformRepository->create($name, $displayName, (bool)$enabled);

        if ($id !== false) {
            json_response([
                'success' => true,
                'message' => 'Plataforma creada correctamente',
                'id' => $id
            ]);
        } else {
            json_response(['success' => false, 'message' => 'Error al crear la plataforma'], 500);
        }
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

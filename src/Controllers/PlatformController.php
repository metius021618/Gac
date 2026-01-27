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

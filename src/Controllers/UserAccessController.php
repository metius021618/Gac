<?php
/**
 * GAC - Controlador de Accesos de Usuario
 * 
 * @package Gac\Controllers
 */

namespace Gac\Controllers;

use Gac\Core\Request;
use Gac\Repositories\UserAccessRepository;
use Gac\Repositories\PlatformRepository;
use Gac\Repositories\EmailAccountRepository;

class UserAccessController
{
    private UserAccessRepository $userAccessRepository;
    private PlatformRepository $platformRepository;
    private EmailAccountRepository $emailAccountRepository;

    public function __construct()
    {
        $this->userAccessRepository = new UserAccessRepository();
        $this->platformRepository = new PlatformRepository();
        $this->emailAccountRepository = new EmailAccountRepository();
    }

    /**
     * Mostrar formulario de registro de accesos
     */
    public function index(Request $request): void
    {
        $platforms = $this->platformRepository->findAllEnabled();
        
        $this->renderView('admin/user_access/index', [
            'title' => 'Registro de Accesos',
            'platforms' => $platforms
        ]);
    }

    /**
     * Crear o actualizar acceso
     */
    public function store(Request $request): void
    {
        $email = trim($request->input('email', ''));
        $password = trim($request->input('password', ''));
        $platformId = (int)$request->input('platform_id', 0);

        // Validaciones
        if (empty($email) || empty($password) || $platformId <= 0) {
            json_response([
                'success' => false,
                'message' => 'Por favor completa todos los campos'
            ], 400);
            return;
        }

        // Validar email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response([
                'success' => false,
                'message' => 'El correo electrónico no es válido'
            ], 400);
            return;
        }

        // Verificar que el correo exista en email_accounts (debe existir en el dominio)
        $emailAccount = $this->emailAccountRepository->findByEmail($email);
        if (!$emailAccount) {
            json_response([
                'success' => false,
                'message' => 'El correo no existe en el sistema. Debe estar registrado primero en "Correos Registrados"'
            ], 400);
            return;
        }

        // Verificar que la plataforma exista
        $platform = $this->platformRepository->findById($platformId);
        if (!$platform) {
            json_response([
                'success' => false,
                'message' => 'La plataforma seleccionada no existe'
            ], 400);
            return;
        }

        // Crear o actualizar acceso
        $success = $this->userAccessRepository->createOrUpdate($email, $password, $platformId);

        if ($success) {
            json_response([
                'success' => true,
                'message' => 'Acceso registrado correctamente'
            ]);
        } else {
            json_response([
                'success' => false,
                'message' => 'Error al registrar el acceso'
            ], 500);
        }
    }

    /**
     * Listar accesos (para tabla)
     */
    public function list(Request $request): void
    {
        $search = $request->get('search', '');
        $page = max(1, (int)$request->get('page', 1));
        $perPage = $request->get('per_page', '15');
        
        $allowedPerPage = ['15', '30', '60', '100', 'all'];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = '15';
        }
        
        $perPageInt = $perPage === 'all' ? 0 : (int)$perPage;
        
        $result = $this->userAccessRepository->searchAndPaginate($search, $page, $perPageInt);
        
        // Si es AJAX, devolver solo la tabla
        if ($request->isAjax()) {
            ob_start();
            extract([
                'accesses' => $result['data'],
                'total_records' => $result['total'],
                'current_page' => $result['page'],
                'per_page' => $result['per_page'],
                'total_pages' => $result['total_pages'],
                'search_query' => $search,
                'valid_per_page' => [15, 30, 60, 100, 0]
            ]);
            require base_path('views/admin/user_access/_table.php');
            $tableHtml = ob_get_clean();
            
            echo '<div class="admin-content">' . $tableHtml . '</div>';
            return;
        }
        
        $this->renderView('admin/user_access/list', [
            'title' => 'Lista de Accesos',
            'accesses' => $result['data'],
            'total_records' => $result['total'],
            'current_page' => $result['page'],
            'per_page' => $result['per_page'],
            'total_pages' => $result['total_pages'],
            'search_query' => $search,
            'valid_per_page' => [15, 30, 60, 100, 0]
        ]);
    }

    /**
     * Eliminar acceso
     */
    public function delete(Request $request): void
    {
        $id = (int)$request->input('id', 0);

        if ($id <= 0) {
            json_response([
                'success' => false,
                'message' => 'ID inválido'
            ], 400);
            return;
        }

        $success = $this->userAccessRepository->delete($id);

        if ($success) {
            json_response([
                'success' => true,
                'message' => 'Acceso eliminado correctamente'
            ]);
        } else {
            json_response([
                'success' => false,
                'message' => 'Error al eliminar el acceso'
            ], 500);
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

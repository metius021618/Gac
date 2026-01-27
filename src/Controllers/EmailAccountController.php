<?php
/**
 * GAC - Controlador de Cuentas de Email
 * 
 * @package Gac\Controllers
 */

namespace Gac\Controllers;

use Gac\Core\Request;
use Gac\Repositories\EmailAccountRepository;

class EmailAccountController
{
    /**
     * Repositorio de cuentas de email
     */
    private EmailAccountRepository $emailAccountRepository;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->emailAccountRepository = new EmailAccountRepository();
    }

    /**
     * Listar todas las cuentas de email con búsqueda y paginación
     */
    public function index(Request $request): void
    {
        $search = $request->get('search', '');
        $page = max(1, (int)$request->get('page', 1));
        $perPage = $request->get('per_page', '15');
        
        // Validar per_page
        $allowedPerPage = ['15', '30', '60', '100', 'all'];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = '15';
        }
        
        $perPageInt = $perPage === 'all' ? 0 : (int)$perPage;
        
        $result = $this->emailAccountRepository->searchAndPaginate($search, $page, $perPageInt);
        
        // Si es petición AJAX, devolver solo la tabla y paginación
        if ($request->isAjax()) {
            // Renderizar el contenido dentro de admin-content para que SearchAJAX lo encuentre
            ob_start();
            extract([
                'email_accounts' => $result['data'],
                'total_records' => $result['total'],
                'current_page' => $result['page'],
                'per_page' => $result['per_page'],
                'total_pages' => $result['total_pages'],
                'search_query' => $search,
                'valid_per_page' => [15, 30, 60, 100, 0]
            ]);
            require base_path('views/admin/email_accounts/_table.php');
            $tableHtml = ob_get_clean();
            
            // Envolver en admin-content para que SearchAJAX.updateTableContent funcione
            echo '<div class="admin-content">' . $tableHtml . '</div>';
            return;
        }
        
        $this->renderView('admin/email_accounts/index', [
            'title' => 'Gestión de Cuentas de Email',
            'email_accounts' => $result['data'],
            'total_records' => $result['total'],
            'current_page' => $result['page'],
            'per_page' => $result['per_page'],
            'total_pages' => $result['total_pages'],
            'search_query' => $search,
            'valid_per_page' => [15, 30, 60, 100, 0]
        ]);
    }

    /**
     * Mostrar formulario para crear nueva cuenta
     */
    public function create(Request $request): void
    {
        $this->renderView('admin/email_accounts/form', [
            'title' => 'Agregar Cuenta de Email',
            'email_account' => null,
            'mode' => 'create'
        ]);
    }

    /**
     * Guardar nueva cuenta de email
     */
    public function store(Request $request): void
    {
        if ($request->method() !== 'POST') {
            json_response([
                'success' => false,
                'message' => 'Método no permitido'
            ], 405);
            return;
        }

        $email = $request->input('email', '');
        $imap_server = $request->input('imap_server', '');
        $imap_port = $request->input('imap_port', 993);
        $imap_user = $request->input('imap_user', '');
        $imap_password = $request->input('imap_password', '');
        $enabled = $request->input('enabled', 1);

        // Validación
        if (empty($email) || empty($imap_server) || empty($imap_user) || empty($imap_password)) {
            json_response([
                'success' => false,
                'message' => 'Todos los campos son requeridos'
            ], 400);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response([
                'success' => false,
                'message' => 'El email no es válido'
            ], 400);
            return;
        }

        // Preparar datos para guardar
        $data = [
            'email' => $email,
            'type' => 'imap',
            'provider_config' => [
                'imap_server' => $imap_server,
                'imap_port' => (int)$imap_port,
                'imap_encryption' => (int)$imap_port === 993 ? 'ssl' : 'tls',
                'imap_user' => $imap_user,
                'imap_password' => $imap_password,
                'imap_validate_cert' => true
            ],
            'enabled' => (int)$enabled,
            'sync_status' => 'pending'
        ];

        // Guardar cuenta
        $accountId = $this->emailAccountRepository->save($data);

        if ($accountId) {
            json_response([
                'success' => true,
                'message' => 'Cuenta de email agregada correctamente',
                'id' => $accountId
            ], 201);
        } else {
            json_response([
                'success' => false,
                'message' => 'Error al guardar la cuenta de email'
            ], 500);
        }
    }

    /**
     * Mostrar formulario para editar cuenta
     */
    public function edit(Request $request): void
    {
        $id = (int)$request->get('id', 0);
        
        $emailAccount = $this->emailAccountRepository->findById($id);
        
        if (!$emailAccount) {
            http_response_code(404);
            echo "Cuenta no encontrada";
            return;
        }

        // Parsear provider_config
        $config = json_decode($emailAccount['provider_config'] ?? '{}', true);
        $emailAccount['imap_server'] = $config['imap_server'] ?? '';
        $emailAccount['imap_port'] = $config['imap_port'] ?? 993;
        $emailAccount['imap_user'] = $config['imap_user'] ?? '';
        $emailAccount['imap_password'] = $config['imap_password'] ?? '';

        $this->renderView('admin/email_accounts/form', [
            'title' => 'Editar Cuenta de Email',
            'email_account' => $emailAccount,
            'mode' => 'edit'
        ]);
    }

    /**
     * Actualizar cuenta de email
     */
    public function update(Request $request): void
    {
        if ($request->method() !== 'POST') {
            json_response([
                'success' => false,
                'message' => 'Método no permitido'
            ], 405);
            return;
        }

        $id = (int)$request->input('id', 0);
        
        if ($id <= 0) {
            json_response([
                'success' => false,
                'message' => 'ID de cuenta inválido'
            ], 400);
            return;
        }

        $email = $request->input('email', '');
        $imap_server = $request->input('imap_server', '');
        $imap_port = $request->input('imap_port', 993);
        $imap_user = $request->input('imap_user', '');
        $imap_password = $request->input('imap_password', '');
        $enabled = $request->input('enabled', 1);

        // Validación
        if (empty($email) || empty($imap_server) || empty($imap_user)) {
            json_response([
                'success' => false,
                'message' => 'Todos los campos son requeridos'
            ], 400);
            return;
        }

        // Obtener cuenta existente para preservar password si no se cambia
        $existingAccount = $this->emailAccountRepository->findById($id);
        if (!$existingAccount) {
            json_response([
                'success' => false,
                'message' => 'Cuenta no encontrada'
            ], 404);
            return;
        }

        $existingConfig = json_decode($existingAccount['provider_config'] ?? '{}', true);
        
        // Si no se proporciona password, usar el existente
        if (empty($imap_password)) {
            $imap_password = $existingConfig['imap_password'] ?? '';
        }

        // Preparar datos
        $data = [
            'email' => $email,
            'type' => 'imap',
            'provider_config' => [
                'imap_server' => $imap_server,
                'imap_port' => (int)$imap_port,
                'imap_encryption' => (int)$imap_port === 993 ? 'ssl' : 'tls',
                'imap_user' => $imap_user,
                'imap_password' => $imap_password,
                'imap_validate_cert' => true
            ],
            'enabled' => (int)$enabled,
            'sync_status' => $existingAccount['sync_status'] ?? 'pending'
        ];

        // Actualizar cuenta
        $updated = $this->emailAccountRepository->update($id, $data);

        if ($updated) {
            json_response([
                'success' => true,
                'message' => 'Cuenta de email actualizada correctamente'
            ], 200);
        } else {
            json_response([
                'success' => false,
                'message' => 'Error al actualizar la cuenta de email'
            ], 500);
        }
    }

    /**
     * Eliminar cuenta de email
     */
    public function destroy(Request $request): void
    {
        if ($request->method() !== 'POST') {
            json_response([
                'success' => false,
                'message' => 'Método no permitido'
            ], 405);
            return;
        }

        $id = (int)$request->input('id', 0);
        
        if ($id <= 0) {
            json_response([
                'success' => false,
                'message' => 'ID de cuenta inválido'
            ], 400);
            return;
        }

        $deleted = $this->emailAccountRepository->delete($id);

        if ($deleted) {
            json_response([
                'success' => true,
                'message' => 'Cuenta de email eliminada correctamente'
            ], 200);
        } else {
            json_response([
                'success' => false,
                'message' => 'Error al eliminar la cuenta de email'
            ], 500);
        }
    }

    /**
     * Cambiar estado (habilitar/deshabilitar)
     */
    public function toggleStatus(Request $request): void
    {
        if ($request->method() !== 'POST') {
            json_response([
                'success' => false,
                'message' => 'Método no permitido'
            ], 405);
            return;
        }

        $id = (int)$request->input('id', 0);
        $enabled = (int)$request->input('enabled', 0);
        
        if ($id <= 0) {
            json_response([
                'success' => false,
                'message' => 'ID de cuenta inválido'
            ], 400);
            return;
        }

        // Obtener cuenta existente
        $account = $this->emailAccountRepository->findById($id);
        if (!$account) {
            json_response([
                'success' => false,
                'message' => 'Cuenta no encontrada'
            ], 404);
            return;
        }

        // Actualizar solo el estado
        $config = json_decode($account['provider_config'] ?? '{}', true);
        $data = [
            'email' => $account['email'],
            'type' => $account['type'],
            'provider_config' => $config,
            'enabled' => $enabled,
            'sync_status' => $account['sync_status'] ?? 'pending'
        ];

        $updated = $this->emailAccountRepository->update($id, $data);

        if ($updated) {
            json_response([
                'success' => true,
                'message' => 'Estado de cuenta actualizado correctamente',
                'enabled' => $enabled
            ], 200);
        } else {
            json_response([
                'success' => false,
                'message' => 'Error al actualizar el estado de la cuenta'
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

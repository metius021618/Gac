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
     * Mostrar formulario de registro de accesos (puede prellenar con email y platform_id por URL).
     * Si viene email + platform_id, se busca el acceso existente y se precarga el usuario asignado para edición.
     */
    public function index(Request $request): void
    {
        $platforms = $this->platformRepository->findAllEnabled();
        $prefill_email = trim($request->get('email', ''));
        $prefill_platform_id = (int) $request->get('platform_id', 0);
        $prefill_password = '';

        if ($prefill_email !== '' && $prefill_platform_id > 0) {
            $emailNorm = strtolower($prefill_email);
            $existing = $this->userAccessRepository->findByEmailAndPlatform($emailNorm, $prefill_platform_id);
            if ($existing && isset($existing['password'])) {
                $prefill_password = $existing['password'];
                // No prellenar si es placeholder OAuth (el usuario debe poner el acceso real)
                if (in_array($prefill_password, ['Gmail (OAuth)', 'Outlook (OAuth)'], true)) {
                    $prefill_password = '';
                }
            }
        }

        $this->renderView('admin/user_access/index', [
            'title' => 'Registro de Accesos',
            'platforms' => $platforms,
            'prefill_email' => $prefill_email,
            'prefill_platform_id' => $prefill_platform_id,
            'prefill_password' => $prefill_password
        ]);
    }

    /**
     * Crear o actualizar acceso. Cualquier correo permitido (Gmail, Outlook, Hotmail, etc.).
     * Permite guardar solo email (stock) o email + contraseña + plataforma.
     */
    public function store(Request $request): void
    {
        try {
            $email = strtolower(trim($request->input('email', '')));
            $password = trim($request->input('password', ''));
            $platformId = (int)$request->input('platform_id', 0);

            if (empty($email)) {
            json_response(['success' => false, 'message' => 'El correo es obligatorio'], 400);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['success' => false, 'message' => 'El correo electrónico no es válido'], 400);
            return;
        }

        $hasUser = $password !== '';
        $hasPlatform = $platformId > 0;

        // Si pone acceso (contraseña) debe poner plataforma
        if ($hasUser && !$hasPlatform) {
            json_response(['success' => false, 'message' => 'Si indica acceso (contraseña), debe seleccionar una plataforma.'], 400);
            return;
        }

        // Modo stock: solo email, o solo plataforma sin acceso → solo email_accounts, no user_access
        if (!$hasUser || !$hasPlatform) {
            $existing = $this->emailAccountRepository->findByEmail($email);
            if (!$existing) {
                $masterAccount = $this->emailAccountRepository->findByEmail('streaming@pocoyoni.com');
                $masterConfig = $masterAccount && !empty($masterAccount['provider_config']) ? json_decode($masterAccount['provider_config'], true) : [];
                $accountData = [
                    'email' => $email,
                    'type' => 'imap',
                    'provider_config' => [
                        'imap_server' => $masterConfig['imap_server'] ?? 'premium211.web-hosting.com',
                        'imap_port' => $masterConfig['imap_port'] ?? 993,
                        'imap_encryption' => $masterConfig['imap_encryption'] ?? 'ssl',
                        'imap_user' => $email,
                        'imap_password' => $password ?: '',
                        'is_master' => false,
                        'filter_by_recipient' => true
                    ],
                    'enabled' => 1,
                    'sync_status' => 'pending'
                ];
                $id = $this->emailAccountRepository->save($accountData);
                if (!$id) {
                    json_response(['success' => false, 'message' => 'Error al guardar el correo'], 500);
                    return;
                }
                if (function_exists('log_user_activity')) {
                    log_user_activity('agregar_correo', sprintf('Se agregó el correo <strong>%s</strong> (stock)', htmlspecialchars($email, ENT_QUOTES, 'UTF-8')));
                }
            }
            $missing = [];
            if (!$hasUser) $missing[] = 'acceso (contraseña)';
            if (!$hasPlatform) $missing[] = 'plataforma';
            $msg = 'Este correo se ha guardado como Stock al no tener asignado ' . implode(' ni ', $missing) . '. Estará disponible en la sección Pocoyoni.';
            json_response(['success' => true, 'message' => $msg, 'stock' => true]);
            return;
        }

        // Con acceso y plataforma: flujo normal (email_accounts si no existe + user_access)
        $platform = $this->platformRepository->findById($platformId);
        if (!$platform) {
            json_response(['success' => false, 'message' => 'La plataforma seleccionada no existe'], 400);
            return;
        }

        $emailAccount = $this->emailAccountRepository->findByEmail($email);
        if (!$emailAccount) {
            $masterAccount = $this->emailAccountRepository->findByEmail('streaming@pocoyoni.com');
            $masterConfig = $masterAccount && !empty($masterAccount['provider_config']) ? json_decode($masterAccount['provider_config'], true) : [];
            $accountData = [
                'email' => $email,
                'type' => 'imap',
                'provider_config' => [
                    'imap_server' => $masterConfig['imap_server'] ?? 'premium211.web-hosting.com',
                    'imap_port' => $masterConfig['imap_port'] ?? 993,
                    'imap_encryption' => $masterConfig['imap_encryption'] ?? 'ssl',
                    'imap_user' => $email,
                    'imap_password' => $password,
                    'is_master' => false,
                    'filter_by_recipient' => true
                ],
                'enabled' => 1,
                'sync_status' => 'pending'
            ];
            if (!$this->emailAccountRepository->save($accountData)) {
                json_response(['success' => false, 'message' => 'Error al crear la cuenta de email'], 500);
                return;
            }
        }

        // Contar ANTES de borrar: si el correo ya tenía algún acceso (incl. OAuth), es edición; si no, asignado nuevo
        $hadAnyAccessBefore = $this->userAccessRepository->countByEmail($email) > 0;
        // Si había una fila OAuth (Gmail/Outlook conectado antes), la borramos y guardamos con la contraseña/plataforma que el usuario indicó
        $oauthRow = $this->userAccessRepository->findOAuthPlaceholderByEmail($email);
        if ($oauthRow && (int)($oauthRow['id'] ?? 0) > 0) {
            $this->userAccessRepository->delete((int) $oauthRow['id']);
        }
        $success = $this->userAccessRepository->createOrUpdate($email, $password, $platformId);

        if ($success && function_exists('log_user_activity')) {
            $platformName = $platform['display_name'] ?? $platform['name'] ?? 'Plataforma';
            $e = function ($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); };
            if ($hadAnyAccessBefore) {
                log_user_activity('edicion', sprintf('Se editó el acceso al correo <strong>%s</strong>: usuario <strong>%s</strong> y plataforma <strong>%s</strong>', $e($email), $e($password ?: '(vacío)'), $e($platformName)));
            } else {
                log_user_activity('asignado', sprintf('Asignó usuario <strong>%s</strong> y plataforma <strong>%s</strong> al correo <strong>%s</strong>', $e($password ?: '(vacío)'), $e($platformName), $e($email)));
            }
        }
        if ($success) {
            json_response(['success' => true, 'message' => 'Acceso registrado correctamente']);
        } else {
            $detail = \Gac\Repositories\UserAccessRepository::getLastError();
            self::logUserAccessError('store_failed', $email, $platformId, $detail ?: 'createOrUpdate/updateById devolvió false');
            $payload = [
                'success' => false,
                'message' => 'Error al registrar el acceso.',
                'detail' => (defined('APP_DEBUG') && APP_DEBUG) ? $detail : null
            ];
            if ($payload['detail'] === null) {
                unset($payload['detail']);
            }
            json_response($payload, 500);
        }
        } catch (\Throwable $e) {
            self::logUserAccessError('exception', isset($email) ? $email : '', isset($platformId) ? $platformId : 0, $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            error_log('UserAccessController::store exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $payload = [
                'success' => false,
                'message' => 'Error al registrar el acceso. Revisa los datos e intenta de nuevo.',
                'detail' => (defined('APP_DEBUG') && APP_DEBUG) ? $e->getMessage() : null
            ];
            if ($payload['detail'] === null) {
                unset($payload['detail']);
            }
            json_response($payload, 500);
        }
    }

    private static function logUserAccessError(string $context, string $email, int $platformId, string $error): void
    {
        $logFile = defined('BASE_PATH') ? BASE_PATH . '/logs/user_access.log' : '';
        if ($logFile !== '') {
            $line = date('Y-m-d H:i:s') . " [{$context}] email={$email} platform_id={$platformId} " . $error . "\n";
            @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
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

        $email = $this->userAccessRepository->getEmailById($id);
        $success = $this->userAccessRepository->delete($id);

        if ($success && $email !== null && function_exists('log_user_activity')) {
            log_user_activity('eliminar', sprintf('Se eliminó el correo <strong>%s</strong>', htmlspecialchars($email, ENT_QUOTES, 'UTF-8')));
        }
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

<?php
/**
 * GAC - Controlador de Cuentas de Email
 * 
 * @package Gac\Controllers
 */

namespace Gac\Controllers;

use Gac\Core\Request;
use Gac\Repositories\EmailAccountRepository;
use Gac\Repositories\UserAccessRepository;

class EmailAccountController
{
    private EmailAccountRepository $emailAccountRepository;
    private UserAccessRepository $userAccessRepository;

    public function __construct()
    {
        $this->emailAccountRepository = new EmailAccountRepository();
        $this->userAccessRepository = new UserAccessRepository();
    }

    /**
     * Listar "Correos Registrados" desde user_access (email, usuario=password, plataforma)
     * Soporta filtro por dominio: ?filter=gmail|outlook|pocoyoni
     */
    public function index(Request $request): void
    {
        $search = $request->get('search', '');
        $page = max(1, (int)$request->get('page', 1));
        $perPage = $request->get('per_page', '15');
        $filter = $request->get('filter', '');
        $platformId = $request->get('platform_id') ? (int)$request->get('platform_id') : null;
        $activityDate = trim($request->get('activity_date', ''));
        
        // Log búsqueda Correos Registrados (tabla user_access)
        $logFile = base_path('logs/search_debug.log');
        $logLine = date('Y-m-d H:i:s') . ' [EmailAccountController] GET=' . json_encode($_GET) . ' search="' . $search . '" filter="' . $filter . '" isAjax=' . ($request->isAjax() ? '1' : '0') . "\n";
        @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        $allowedPerPage = ['15', '30', '60', '100', 'all'];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = '15';
        }
        
        $perPageInt = $perPage === 'all' ? 0 : (int)$perPage;

        // Determinar dominios a filtrar
        $filterDomains = [];
        $filterLabel = '';
        if ($filter === 'gmail') {
            $filterDomains = ['gmail.com'];
            $filterLabel = 'Gmail';
        } elseif ($filter === 'outlook') {
            $filterDomains = ['outlook.com', 'hotmail.com', 'live.com'];
            $filterLabel = 'Outlook';
        } elseif ($filter === 'pocoyoni') {
            $filterDomains = ['pocoyoni.com'];
            $filterLabel = 'Pocoyoni';
        }

        // Siempre desde user_access (misma tabla y columnas). Filtro por dominio cuando filter=gmail|outlook|pocoyoni.
        $matrixAccount = $this->emailAccountRepository->getGmailMatrixAccount();
        $excludeEmail = $matrixAccount && !empty($matrixAccount['email']) ? $matrixAccount['email'] : null;
        $result = $this->userAccessRepository->searchAndPaginate($search, $page, $perPageInt, $filterDomains, $excludeEmail, $platformId, $activityDate ?: null);
        
        @file_put_contents($logFile, date('Y-m-d H:i:s') . ' [EmailAccountController] total=' . $result['total'] . ' rows=' . count($result['data']) . "\n", FILE_APPEND | LOCK_EX);
        
        // Si es petición AJAX, devolver solo la tabla y paginación
        if ($request->isAjax()) {
            ob_start();
            $platformsForFilter = [];
            if (empty($filter) && $search !== '') {
                $matrixAccount = $this->emailAccountRepository->getGmailMatrixAccount();
                $excludeEmail = $matrixAccount && !empty($matrixAccount['email']) ? $matrixAccount['email'] : null;
                $platformsForFilter = $this->userAccessRepository->getPlatformsForSearch($search, [], $excludeEmail);
            }
            extract([
                'email_accounts' => $result['data'],
                'total_records' => $result['total'],
                'current_page' => $result['page'],
                'per_page' => $result['per_page'],
                'total_pages' => $result['total_pages'],
                'search_query' => $search,
                'valid_per_page' => [15, 30, 60, 100, 0],
                'filter' => $filter,
                'filter_label' => $filterLabel,
                'platforms_for_filter' => $platformsForFilter,
                'has_search' => $search !== '',
                'platform_id_filter' => $platformId,
                'activity_date_filter' => $activityDate
            ]);
            require base_path('views/admin/email_accounts/_table.php');
            $tableHtml = ob_get_clean();
            $filtersData = json_encode([
                'platforms' => $platformsForFilter,
                'has_search' => $search !== '',
                'platform_id' => $platformId,
                'activity_date' => $activityDate
            ]);
            echo '<div class="admin-content">' . $tableHtml . '</div>';
            echo '<!-- GAC-EMAIL-FILTERS:' . htmlspecialchars($filtersData, ENT_QUOTES, 'UTF-8') . ' -->';
            return;
        }

        $this->renderView('admin/email_accounts/index', [
            'title' => 'Correos Registrados',
            'email_accounts' => $result['data'],
            'total_records' => $result['total'],
            'current_page' => $result['page'],
            'per_page' => $result['per_page'],
            'total_pages' => $result['total_pages'],
            'search_query' => $search,
            'valid_per_page' => [15, 30, 60, 100, 0],
            'filter' => $filter
        ]);
    }

    /**
     * Exportar Correos Registrados a Excel (XLS).
     * Usa formato XML Spreadsheet 2003 para que Excel muestre correctamente cada columna.
     * Columnas: Correo, Usuario, Plataforma, Actividad (sin ID).
     */
    public function exportExcel(Request $request): void
    {
        $search = trim($request->get('search', ''));
        $platformId = $request->get('platform_id') ? (int)$request->get('platform_id') : null;
        $activityDate = trim($request->get('activity_date', ''));
        $filter = trim($request->get('filter', ''));
        $filterDomains = [];
        if ($filter === 'gmail') {
            $filterDomains = ['gmail.com'];
        } elseif ($filter === 'outlook') {
            $filterDomains = ['outlook.com', 'hotmail.com', 'hotmail.es', 'live.com', 'live.es'];
        } elseif ($filter === 'pocoyoni') {
            $filterDomains = ['pocoyoni.com'];
        }
        $matrixAccount = $this->emailAccountRepository->getGmailMatrixAccount();
        $excludeEmail = $matrixAccount && !empty($matrixAccount['email']) ? $matrixAccount['email'] : null;
        $result = $this->userAccessRepository->searchAndPaginate($search, 1, 0, $filterDomains, $excludeEmail, $platformId, $activityDate ?: null);
        $rows = $result['data'] ?? [];

        // Generar una hoja de cálculo \"clásica\" para Excel usando una tabla HTML simple.
        // Extensión .xls para que se abra como libro normal.
        $filename = 'correos_registrados_' . date('Y-m-d_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        echo '<table border="1" cellpadding="2" cellspacing="0" style="border-collapse:collapse;">';
        echo '<tr style="background:#2563eb;color:#ffffff;font-weight:bold;">';
        echo '<td>Correo</td>';
        echo '<td>Usuario</td>';
        echo '<td>Plataforma</td>';
        echo '<td>Actividad</td>';
        echo '</tr>';

        foreach ($rows as $r) {
            $actividad = !empty($r['updated_at']) ? date('d/m/Y H:i', strtotime($r['updated_at'])) : (!empty($r['created_at']) ? date('d/m/Y H:i', strtotime($r['created_at'])) : '—');
            $email = $r['email'] ?? '';
            $usuario = $r['password'] ?? '';
            $plataforma = $r['platform_display_name'] ?? $r['platform_name'] ?? '—';
            echo '<tr>';
            echo '<td>' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($plataforma, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($actividad, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '</tr>';
        }

        echo '</table>';
        exit;
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
        $imap_user = $request->input('imap_user', '');

        // Validación
        if (empty($email) || empty($imap_user)) {
            json_response([
                'success' => false,
                'message' => 'Correo y acceso son requeridos'
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

        // Obtener cuenta maestra para replicar configuración
        $masterAccount = $this->emailAccountRepository->findMasterAccount();
        if (!$masterAccount) {
            json_response([
                'success' => false,
                'message' => 'No se encontró la cuenta maestra'
            ], 500);
            return;
        }

        $masterConfig = json_decode($masterAccount['provider_config'] ?? '{}', true);
        
        // Preparar datos para guardar (usar configuración de cuenta maestra)
        $data = [
            'email' => $email,
            'type' => 'imap',
            'provider_config' => [
                'imap_server' => $masterConfig['imap_server'] ?? '',
                'imap_port' => $masterConfig['imap_port'] ?? 993,
                'imap_encryption' => $masterConfig['imap_encryption'] ?? 'ssl',
                'imap_user' => $imap_user,
                'imap_password' => $masterConfig['imap_password'] ?? '',
                'imap_validate_cert' => $masterConfig['imap_validate_cert'] ?? true,
                'is_master' => false,
                'filter_by_recipient' => true
            ],
            'enabled' => 1,
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
        try {
            $id = (int)$request->get('id', 0);
            
            if ($id <= 0) {
                http_response_code(404);
                echo "ID de cuenta inválido";
                return;
            }
            
            $emailAccount = $this->emailAccountRepository->findById($id);
            
            if (!$emailAccount) {
                http_response_code(404);
                echo "Cuenta no encontrada";
                return;
            }
            if ($this->emailAccountRepository->getGmailMatrixAccountId() === $id) {
                http_response_code(404);
                echo "Cuenta no encontrada";
                return;
            }

            // Parsear provider_config de manera segura
            $providerConfig = $emailAccount['provider_config'] ?? '{}';
            if (is_string($providerConfig)) {
                $config = json_decode($providerConfig, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("Error al parsear provider_config para cuenta ID {$id}: " . json_last_error_msg());
                    $config = [];
                }
            } else {
                $config = is_array($providerConfig) ? $providerConfig : [];
            }
            
            $emailAccount['imap_user'] = $config['imap_user'] ?? '';

            $this->renderView('admin/email_accounts/form', [
                'title' => 'Editar Cuenta de Email',
                'email_account' => $emailAccount,
                'mode' => 'edit'
            ]);
        } catch (\Exception $e) {
            error_log("Error en EmailAccountController::edit: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo "Error interno del servidor. Por favor contacta al administrador.";
        }
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
        $imap_user = $request->input('imap_user', '');

        // Validación
        if (empty($email) || empty($imap_user)) {
            json_response([
                'success' => false,
                'message' => 'Correo y acceso son requeridos'
            ], 400);
            return;
        }

        if ($this->emailAccountRepository->getGmailMatrixAccountId() === $id) {
            json_response([
                'success' => false,
                'message' => 'No se puede editar la cuenta Gmail matriz'
            ], 403);
            return;
        }

        // Obtener cuenta existente para preservar configuración
        $existingAccount = $this->emailAccountRepository->findById($id);
        if (!$existingAccount) {
            json_response([
                'success' => false,
                'message' => 'Cuenta no encontrada'
            ], 404);
            return;
        }

        $existingConfig = json_decode($existingAccount['provider_config'] ?? '{}', true);
        
        // Preparar datos (preservar configuración del servidor, solo actualizar usuario)
        $data = [
            'email' => $email,
            'type' => 'imap',
            'provider_config' => [
                'imap_server' => $existingConfig['imap_server'] ?? '',
                'imap_port' => $existingConfig['imap_port'] ?? 993,
                'imap_encryption' => $existingConfig['imap_encryption'] ?? 'ssl',
                'imap_user' => $imap_user,
                'imap_password' => $existingConfig['imap_password'] ?? '',
                'imap_validate_cert' => $existingConfig['imap_validate_cert'] ?? true,
                'is_master' => $existingConfig['is_master'] ?? false,
                'filter_by_recipient' => $existingConfig['filter_by_recipient'] ?? true
            ],
            'enabled' => $existingAccount['enabled'] ?? 1,
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
     * Eliminar múltiples cuentas de email
     */
    public function bulkDelete(Request $request): void
    {
        if ($request->method() !== 'POST') {
            json_response([
                'success' => false,
                'message' => 'Método no permitido'
            ], 405);
            return;
        }

        $ids = $request->input('ids', []);
        
        if (empty($ids) || !is_array($ids)) {
            json_response([
                'success' => false,
                'message' => 'No se seleccionaron cuentas para eliminar'
            ], 400);
            return;
        }

        // Validar que todos sean números y excluir la cuenta matriz (no se puede eliminar)
        $matrixId = $this->emailAccountRepository->getGmailMatrixAccountId();
        $ids = array_filter(array_map('intval', $ids), function($id) use ($matrixId) {
            return $id > 0 && $id !== $matrixId;
        });

        if (empty($ids)) {
            json_response([
                'success' => false,
                'message' => 'IDs inválidos'
            ], 400);
            return;
        }

        $accessList = $this->userAccessRepository->getAccessListByIds($ids);
        $deleted = $this->userAccessRepository->bulkDelete($ids);

        if ($deleted && !empty($accessList) && function_exists('log_user_activity')) {
            foreach ($accessList as $acc) {
                $email = trim($acc['email'] ?? '');
                $usuario = trim($acc['password'] ?? '') ?: '(vacío)';
                $plataforma = trim($acc['platform_display_name'] ?? '') ?: '—';
                log_user_activity('eliminar', sprintf('Eliminó el correo %s | %s | %s', $email, $usuario, $plataforma));
            }
        }
        if ($deleted && !empty($accessList)) {
            foreach ($accessList as $acc) {
                $email = trim($acc['email'] ?? '');
                $emailNorm = strtolower(trim($email));
                if ($this->userAccessRepository->countByEmail($emailNorm) === 0) {
                    $this->emailAccountRepository->deleteByEmail($emailNorm);
                }
            }
        }

        if ($deleted) {
            json_response([
                'success' => true,
                'message' => count($ids) . ' registro(s) eliminado(s) correctamente'
            ], 200);
        } else {
            json_response([
                'success' => false,
                'message' => 'Error al eliminar las cuentas'
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

        $access = $this->userAccessRepository->getAccessById($id);
        $deleted = $this->userAccessRepository->delete($id);

        if ($deleted && $access !== null) {
            if (function_exists('log_user_activity')) {
                $email = trim($access['email'] ?? '');
                $usuario = trim($access['password'] ?? '') ?: '(vacío)';
                $plataforma = trim($access['platform_display_name'] ?? '') ?: '—';
                log_user_activity('eliminar', sprintf('Eliminó el correo %s | %s | %s', $email, $usuario, $plataforma));
            }
            $emailNorm = strtolower(trim($access['email'] ?? ''));
            if ($this->userAccessRepository->countByEmail($emailNorm) === 0) {
                $this->emailAccountRepository->deleteByEmail($emailNorm);
            }
            json_response([
                'success' => true,
                'message' => 'Registro eliminado correctamente'
            ], 200);
        } elseif ($deleted) {
            json_response([
                'success' => true,
                'message' => 'Registro eliminado correctamente'
            ], 200);
        } else {
            json_response([
                'success' => false,
                'message' => 'Error al eliminar el registro'
            ], 500);
        }
    }

    /**
     * Eliminar cuenta de email por ID de cuenta (vista filtrada Gmail/Outlook/Pocoyoni).
     * Solo elimina de email_accounts y user_access; la cuenta Gmail matriz no se puede eliminar.
     */
    public function destroyByAccountId(Request $request): void
    {
        if ($request->method() !== 'POST') {
            json_response(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'ID de cuenta inválido'], 400);
            return;
        }

        $account = $this->emailAccountRepository->findById($id);
        if (!$account) {
            json_response(['success' => false, 'message' => 'Cuenta no encontrada'], 404);
            return;
        }

        if ($this->emailAccountRepository->getGmailMatrixAccountId() === $id) {
            json_response(['success' => false, 'message' => 'No se puede eliminar la cuenta Gmail matriz'], 403);
            return;
        }

        $email = strtolower(trim($account['email'] ?? ''));
        $accessList = $this->userAccessRepository->getAccessListByEmail($account['email'] ?? '');
        $this->userAccessRepository->deleteByEmail($email);
        $deleted = $this->emailAccountRepository->delete($id);

        if ($deleted && function_exists('log_user_activity') && !empty($accessList)) {
            foreach ($accessList as $acc) {
                $em = trim($acc['email'] ?? '');
                $usuario = trim($acc['password'] ?? '') ?: '(vacío)';
                $plataforma = trim($acc['platform_display_name'] ?? '') ?: '—';
                log_user_activity('eliminar', sprintf('Eliminó el correo %s | %s | %s', $em, $usuario, $plataforma));
            }
        }
        if ($deleted) {
            json_response(['success' => true, 'message' => 'Correo eliminado correctamente'], 200);
        } else {
            json_response(['success' => false, 'message' => 'Error al eliminar el correo'], 500);
        }
    }

    /**
     * Cambiar estado (habilitar/deshabilitar) en user_access
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
                'message' => 'ID inválido'
            ], 400);
            return;
        }

        $updatedBy = $_SESSION['username'] ?? null;
        $updated = $this->userAccessRepository->toggleEnabled($id, (bool)$enabled, $updatedBy);

        if ($updated) {
            json_response([
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'enabled' => $enabled
            ], 200);
        } else {
            json_response([
                'success' => false,
                'message' => 'Error al actualizar el estado'
            ], 500);
        }
    }

    /**
     * Mostrar formulario de registro masivo
     */
    public function bulkRegister(Request $request): void
    {
        $platformRepository = new \Gac\Repositories\PlatformRepository();
        $platforms = $platformRepository->findAllEnabled();
        
        $this->renderView('admin/email_accounts/bulk_register', [
            'title' => 'Asignar Correos Masivamente',
            'platforms' => $platforms
        ]);
    }

    /**
     * Agregar correos como stock (solo en email_accounts). Aparecen en el listado por dominio (Gmail/Hotmail/Pocoyoni).
     */
    public function addStock(Request $request): void
    {
        if ($request->method() !== 'POST') {
            json_response(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        $emailsInput = trim($request->input('emails', ''));
        if ($emailsInput === '') {
            json_response(['success' => false, 'message' => 'Ingresa al menos un correo'], 400);
            return;
        }
        $emails = array_filter(array_map('trim', explode("\n", $emailsInput)));
        $emails = array_unique($emails);
        $result = $this->emailAccountRepository->addStockEmails($emails);
        if (function_exists('log_user_activity') && !empty($result['added_emails'] ?? [])) {
            foreach ($result['added_emails'] as $em) {
                log_user_activity('agregar_correo', sprintf('Registró el correo %s | (stock) | —', trim($em)));
            }
        }
        $msg = 'Agregados: ' . $result['added'] . '. Ya existían: ' . $result['skipped'];
        if (!empty($result['errors'])) {
            $msg .= '. Errores: ' . implode('; ', array_slice($result['errors'], 0, 5));
            if (count($result['errors']) > 5) {
                $msg .= '...';
            }
        }
        json_response([
            'success' => true,
            'message' => $msg,
            'added' => $result['added'],
            'skipped' => $result['skipped'],
            'errors' => $result['errors']
        ], 200);
    }

    /**
     * Procesar registro masivo
     */
    public function bulkRegisterStore(Request $request): void
    {
        if ($request->method() !== 'POST') {
            json_response([
                'success' => false,
                'message' => 'Método no permitido'
            ], 405);
            return;
        }

        $emailsInput = trim($request->input('emails', ''));
        $accessCode = trim($request->input('access_code', ''));
        $platformId = (int)$request->input('platform_id', 0);

        // Validaciones básicas
        if (empty($emailsInput) || empty($accessCode) || $platformId <= 0) {
            json_response([
                'success' => false,
                'message' => 'Todos los campos son requeridos'
            ], 400);
            return;
        }

        // Validar plataforma
        $platformRepository = new \Gac\Repositories\PlatformRepository();
        $platform = $platformRepository->findById($platformId);
        if (!$platform) {
            json_response([
                'success' => false,
                'message' => 'La plataforma seleccionada no existe'
            ], 400);
            return;
        }

        // Procesar correos
        $emailsArray = array_filter(
            array_map('trim', explode("\n", $emailsInput)),
            function($email) {
                return !empty($email);
            }
        );
        
        // Eliminar duplicados
        $emailsArray = array_unique($emailsArray);

        if (empty($emailsArray)) {
            json_response([
                'success' => false,
                'message' => 'No se proporcionaron correos válidos'
            ], 400);
            return;
        }

        $allowedDomains = ['pocoyoni.com', 'gmail.com', 'outlook.com', 'hotmail.com', 'hotmail.es', 'live.com', 'live.es'];
        $invalidEmails = [];
        $validEmails = [];

        foreach ($emailsArray as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalidEmails[] = $email;
                continue;
            }
            $domain = strtolower(trim(substr(strrchr($email, "@"), 1)));
            if (!in_array($domain, $allowedDomains, true)) {
                $invalidEmails[] = $email;
                continue;
            }
            $validEmails[] = $email;
        }

        if (empty($validEmails)) {
            json_response([
                'success' => false,
                'message' => 'No se encontraron correos válidos. Dominios permitidos: Pocoyoni, Gmail, Outlook, Hotmail, Live.',
                'invalid_emails' => $invalidEmails
            ], 400);
            return;
        }

        // Crear cuentas de email si no existen
        $masterAccount = $this->emailAccountRepository->findMasterAccount();
        
        if (!$masterAccount) {
            json_response([
                'success' => false,
                'message' => 'No se encontró la cuenta maestra'
            ], 500);
            return;
        }

        $masterConfig = json_decode($masterAccount['provider_config'] ?? '{}', true);
        $createdAccounts = 0;
        
        foreach ($validEmails as $email) {
            $existingAccount = $this->emailAccountRepository->findByEmail($email);
            if (!$existingAccount) {
                $domain = strtolower(trim(substr(strrchr($email, "@"), 1)));
                $type = 'imap';
                if ($domain === 'gmail.com') {
                    $type = 'gmail';
                } elseif (in_array($domain, ['outlook.com', 'hotmail.com', 'hotmail.es', 'live.com', 'live.es'], true)) {
                    $type = 'outlook';
                }
                $accountData = [
                    'email' => $email,
                    'type' => $type,
                    'provider_config' => [
                        'imap_server' => $masterConfig['imap_server'] ?? '',
                        'imap_port' => $masterConfig['imap_port'] ?? 993,
                        'imap_encryption' => $masterConfig['imap_encryption'] ?? 'ssl',
                        'imap_user' => $accessCode,
                        'imap_password' => $masterConfig['imap_password'] ?? '',
                        'imap_validate_cert' => $masterConfig['imap_validate_cert'] ?? true,
                        'is_master' => false,
                        'filter_by_recipient' => true
                    ],
                    'enabled' => 1,
                    'sync_status' => 'pending'
                ];
                
                if ($this->emailAccountRepository->save($accountData)) {
                    $createdAccounts++;
                }
            }
        }

        // Crear accesos de usuario masivamente
        $userAccessRepository = new \Gac\Repositories\UserAccessRepository();
        $updatedBy = $_SESSION['username'] ?? null;
        $result = $userAccessRepository->bulkCreate($validEmails, $accessCode, $platformId, $updatedBy);

        if (function_exists('log_user_activity')) {
            $platformName = trim($platform['display_name'] ?? $platform['name'] ?? 'Plataforma');
            foreach ($validEmails as $e) {
                log_user_activity('agregar_correo', sprintf('Registró el correo %s | %s | %s', trim($e), $accessCode, $platformName));
            }
        }

        $message = sprintf(
            'Se procesaron %d correo(s) correctamente. %d nuevo(s), %d actualizado(s).',
            $result['success'] + $result['duplicates'],
            $result['success'],
            $result['duplicates']
        );

        if (!empty($invalidEmails)) {
            $message .= ' ' . count($invalidEmails) . ' correo(s) rechazados (formato inválido o dominio no permitido).';
        }

        json_response([
            'success' => true,
            'message' => $message,
            'stats' => [
                'total' => count($validEmails),
                'created' => $result['success'],
                'updated' => $result['duplicates'],
                'invalid' => count($invalidEmails),
                'accounts_created' => $createdAccounts
            ],
            'invalid_emails' => $invalidEmails
        ], 200);
    }

    /**
     * Eliminación masiva: si llega 'ids' (array) → eliminar por IDs (vista Correos registrados);
     * si llega 'emails' + 'platform_id' → flujo registro masivo.
     * Así funciona tanto con el JS actual (bulk-delete-ids) como con caché antigua que llame a bulk-delete con ids.
     */
    public function bulkDeleteDispatch(Request $request): void
    {
        if ($request->method() !== 'POST') {
            json_response(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        $ids = $request->input('ids', []);
        if (!empty($ids) && is_array($ids)) {
            $this->bulkDelete($request);
            return;
        }
        $this->bulkDeleteStore($request);
    }

    /**
     * Procesar eliminación masiva (filtrada por plataforma)
     * Solo elimina la asignación email+plataforma de user_access.
     * Si el correo no tiene más asignaciones en user_access, también se elimina de email_accounts.
     */
    public function bulkDeleteStore(Request $request): void
    {
        if ($request->method() !== 'POST') {
            json_response([
                'success' => false,
                'message' => 'Método no permitido'
            ], 405);
            return;
        }

        $emailsInput = trim($request->input('emails', ''));
        $platformId = (int)$request->input('platform_id', 0);

        if (empty($emailsInput)) {
            json_response([
                'success' => false,
                'message' => 'Debes proporcionar al menos un correo'
            ], 400);
            return;
        }

        if ($platformId <= 0) {
            json_response([
                'success' => false,
                'message' => 'Debes seleccionar una plataforma'
            ], 400);
            return;
        }

        // Validar que la plataforma exista
        $platformRepository = new \Gac\Repositories\PlatformRepository();
        $platform = $platformRepository->findById($platformId);
        if (!$platform) {
            json_response([
                'success' => false,
                'message' => 'La plataforma seleccionada no existe'
            ], 400);
            return;
        }

        // Procesar correos
        $emailsArray = array_filter(
            array_map('trim', explode("\n", $emailsInput)),
            function($email) {
                return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
            }
        );
        
        // Eliminar duplicados
        $emailsArray = array_unique($emailsArray);

        if (empty($emailsArray)) {
            json_response([
                'success' => false,
                'message' => 'No se proporcionaron correos válidos'
            ], 400);
            return;
        }

        $deletedCount = 0;
        $notFoundEmails = [];
        $alsoRemovedFromAccounts = 0;
        $userAccessRepository = new \Gac\Repositories\UserAccessRepository();

        foreach ($emailsArray as $email) {
            $access = $userAccessRepository->getAccessByEmailAndPlatform($email, $platformId);
            $deleted = $userAccessRepository->deleteByEmailAndPlatform($email, $platformId);

            if ($deleted) {
                $deletedCount++;
                if (function_exists('log_user_activity')) {
                    $em = $access ? trim($access['email'] ?? $email) : $email;
                    $usuario = $access ? (trim($access['password'] ?? '') ?: '(vacío)') : '(vacío)';
                    $plataforma = $access ? (trim($access['platform_display_name'] ?? '') ?: '—') : ($platform['display_name'] ?? $platform['name'] ?? '—');
                    log_user_activity('eliminar', sprintf('Eliminó el correo %s | %s | %s', $em, $usuario, $plataforma));
                }

                // Si ya no tiene más asignaciones en user_access, eliminar de email_accounts
                $remaining = $userAccessRepository->countByEmail($email);
                if ($remaining === 0) {
                    $this->emailAccountRepository->deleteByEmail($email);
                    $alsoRemovedFromAccounts++;
                }
            } else {
                $notFoundEmails[] = $email;
            }
        }

        $platformName = htmlspecialchars($platform['display_name'] ?? $platform['name'] ?? '');
        $message = sprintf(
            'Se eliminaron %d asignación(es) de "%s" correctamente.',
            $deletedCount,
            $platformName
        );

        if ($alsoRemovedFromAccounts > 0) {
            $message .= sprintf(' %d correo(s) sin más plataformas fueron eliminados completamente.', $alsoRemovedFromAccounts);
        }

        if (!empty($notFoundEmails)) {
            $message .= sprintf(' %d correo(s) no tenían asignación en "%s".', count($notFoundEmails), $platformName);
        }

        json_response([
            'success' => true,
            'message' => $message,
            'stats' => [
                'total' => count($emailsArray),
                'deleted' => $deletedCount,
                'removed_from_accounts' => $alsoRemovedFromAccounts,
                'not_found' => count($notFoundEmails)
            ],
            'not_found_emails' => $notFoundEmails
        ], 200);
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

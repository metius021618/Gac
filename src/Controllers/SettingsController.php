<?php
/**
 * GAC - Controlador de Configuración
 * Maneja la vista y actualización de configuraciones del sistema
 * 
 * @package Gac\Controllers
 */

namespace Gac\Controllers;

use Gac\Core\Request;
use Gac\Repositories\SettingsRepository;
use Gac\Repositories\EmailAccountRepository;
use Gac\Repositories\RoleRepository;
use Gac\Helpers\RoleViewsConfig;

class SettingsController
{
    private SettingsRepository $settingsRepository;
    private EmailAccountRepository $emailAccountRepository;
    private RoleRepository $roleRepository;

    public function __construct()
    {
        $this->settingsRepository = new SettingsRepository();
        $this->emailAccountRepository = new EmailAccountRepository();
        $this->roleRepository = new RoleRepository();
    }

    /**
     * Mostrar vista de configuración
     */
    public function index(Request $request): void
    {
        $sessionTimeoutHours = (int) $this->settingsRepository->getValue('session_timeout_hours', '1');
        $masterConsultEnabled = $this->settingsRepository->getValue('master_consult_enabled', '0');
        $masterConsultUsername = $this->settingsRepository->getValue('master_consult_username', '');
        $gmailMatrixAccount = $this->emailAccountRepository->getGmailMatrixAccount();
        $roles = $this->roleRepository->findAll();
        $role_views_config = RoleViewsConfig::allWithActions();

        $this->renderView('admin/settings/index', [
            'title' => 'Configuración del Sistema',
            'session_timeout_hours' => $sessionTimeoutHours,
            'master_consult_enabled' => $masterConsultEnabled,
            'master_consult_username' => $masterConsultUsername,
            'gmail_matrix_account' => $gmailMatrixAccount,
            'roles' => $roles,
            'role_views_config' => $role_views_config,
        ]);
    }

    /**
     * Actualizar configuración
     */
    public function update(Request $request): void
    {
        // Asegurar que la sesión esté iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($request->method() !== 'POST') {
            json_response([
                'success' => false,
                'message' => 'Método no permitido'
            ], 405);
            return;
        }

        // Validar CSRF token
        if (!$this->validateCsrfToken($request)) {
            json_response([
                'success' => false,
                'message' => 'Token de seguridad inválido'
            ], 403);
            return;
        }

        $sessionTimeoutHours = (int) $request->input('session_timeout_hours', 1);
        $masterConsultEnabled = $request->input('master_consult_enabled', '0') === '1' ? '1' : '0';
        $masterConsultUsername = trim($request->input('master_consult_username', ''));

        $allowedHours = [1, 2, 3, 5, 7];
        if (!in_array($sessionTimeoutHours, $allowedHours)) {
            json_response([
                'success' => false,
                'message' => 'El tiempo de sesión debe ser 1, 2, 3, 5 o 7 horas'
            ], 400);
            return;
        }

        try {
            $ok1 = $this->settingsRepository->update(
                'session_timeout_hours',
                (string) $sessionTimeoutHours,
                'Tiempo en horas que se mantiene activa la sesión del usuario'
            );
            $ok2 = $this->settingsRepository->update(
                'master_consult_enabled',
                $masterConsultEnabled,
                '1=habilitar acceso maestro en Consulta tu código (solo admin logueado)'
            );
            $ok3 = $this->settingsRepository->update(
                'master_consult_username',
                $masterConsultUsername,
                'Usuario/clave que el admin escribe en Consulta tu código para ver el último código de cualquier cuenta'
            );

            if ($ok1 || $ok2 || $ok3) {
                json_response([
                    'success' => true,
                    'message' => 'Configuración actualizada correctamente'
                ]);
            } else {
                json_response([
                    'success' => false,
                    'message' => 'Error al actualizar la configuración. Verifica que la tabla settings existe.'
                ], 500);
            }
        } catch (\Exception $e) {
            json_response([
                'success' => false,
                'message' => 'Error al actualizar la configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET: Obtener vistas y acciones asignadas a un rol (JSON)
     */
    public function roleViews(Request $request): void
    {
        $roleId = (int) $request->get('role_id');
        if (!$roleId) {
            json_response(['success' => false, 'message' => 'role_id requerido'], 400);
            return;
        }
        $viewKeys = $this->roleRepository->getViewKeys($roleId);
        $viewActions = $this->roleRepository->getViewActions($roleId);
        json_response([
            'success' => true,
            'view_keys' => $viewKeys,
            'view_actions' => $viewActions,
        ]);
    }

    /**
     * POST: Guardar vistas y acciones de un rol
     */
    public function saveRoleViews(Request $request): void
    {
        if ($request->method() !== 'POST') {
            json_response(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        $roleId = (int) $request->input('role_id');
        $viewKeys = $request->input('view_keys');
        $viewActions = $request->input('view_actions');
        if (!is_array($viewKeys)) {
            $viewKeys = $viewKeys ? (array) $viewKeys : [];
        }
        if (!is_array($viewActions)) {
            $viewActions = [];
        }
        if (!$roleId) {
            json_response(['success' => false, 'message' => 'role_id requerido'], 400);
            return;
        }
        $ok = $this->roleRepository->setViewKeys($roleId, $viewKeys);
        $okActions = $this->roleRepository->setViewActions($roleId, $viewActions);
        if ($ok && $okActions) {
            json_response(['success' => true, 'message' => 'Vistas y acciones del rol actualizadas']);
        } else {
            json_response(['success' => false, 'message' => 'Error al guardar'], 500);
        }
    }

    /**
     * Vista previa del panel según vistas permitidas (para iframe en personalización de roles)
     * GET ?views=dashboard,listar_correos,...
     */
    public function rolePreview(Request $request): void
    {
        $viewsParam = $request->get('views', '');
        $allowedKeys = $viewsParam !== '' ? array_map('trim', explode(',', $viewsParam)) : [];
        $validKeys = RoleViewsConfig::keys();
        $allowedViews = [];
        foreach (RoleViewsConfig::all() as $v) {
            if (in_array($v['key'], $allowedKeys, true) && in_array($v['key'], $validKeys, true)) {
                $allowedViews[] = $v;
            }
        }
        $this->renderView('admin/role_preview/index', [
            'allowed_views' => $allowedViews,
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
            require base_path("views/{$view}.php");
        }
    }

    /**
     * Validar token CSRF
     */
    private function validateCsrfToken(Request $request): bool
    {
        // Asegurar que la sesión esté iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Obtener token del request (Request::input ya maneja JSON)
        $token = $request->input('csrf_token', '');
        
        if (empty($token)) {
            return false;
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

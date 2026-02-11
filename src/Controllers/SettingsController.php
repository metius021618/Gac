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

class SettingsController
{
    private SettingsRepository $settingsRepository;
    private EmailAccountRepository $emailAccountRepository;

    public function __construct()
    {
        $this->settingsRepository = new SettingsRepository();
        $this->emailAccountRepository = new EmailAccountRepository();
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

        $this->renderView('admin/settings/index', [
            'title' => 'Configuración del Sistema',
            'session_timeout_hours' => $sessionTimeoutHours,
            'master_consult_enabled' => $masterConsultEnabled,
            'master_consult_username' => $masterConsultUsername,
            'gmail_matrix_account' => $gmailMatrixAccount
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
     * Renderizar vista
     */
    private function renderView(string $view, array $data = []): void
    {
        extract($data);
        require base_path("views/{$view}.php");
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

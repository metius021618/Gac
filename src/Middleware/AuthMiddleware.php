<?php
/**
 * GAC - Middleware de Autenticación
 * Protege rutas que requieren autenticación
 * 
 * @package Gac\Middleware
 */

namespace Gac\Middleware;

use Gac\Core\Request;
use Gac\Repositories\SettingsRepository;

class AuthMiddleware
{
    private ?SettingsRepository $settingsRepository = null;

    public function __construct()
    {
        // Instanciar SettingsRepository de forma segura
        try {
            $this->settingsRepository = new SettingsRepository();
        } catch (\Exception $e) {
            // Si hay error al crear SettingsRepository, usar valor por defecto
            error_log("Error al inicializar SettingsRepository en middleware: " . $e->getMessage());
            $this->settingsRepository = null;
        }
    }
    /**
     * Manejar request
     */
    public function handle(Request $request): void
    {
        if (!$this->isAuthenticated()) {
            // Guardar URL de destino para redirigir después del login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            
            if ($request->isAjax()) {
                json_response([
                    'success' => false,
                    'message' => 'No autenticado',
                    'redirect' => '/login'
                ], 401);
                exit;
            } else {
                redirect('/login');
            }
        }

        // Actualizar última actividad
        $_SESSION['last_activity'] = time();
    }

    /**
     * Verificar si está autenticado
     */
    private function isAuthenticated(): bool
    {
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            return false;
        }

        // Verificar timeout de sesión
        if (isset($_SESSION['last_activity'])) {
            // Obtener timeout configurado del sistema
            if ($this->settingsRepository) {
                $timeout = $this->getSessionTimeout();
            } else {
                $timeout = 3600; // 1 hora por defecto
            }
            
            // Si tiene "recordar" activado, usar 30 días
            if (isset($_SESSION['remember']) && $_SESSION['remember']) {
                $timeout = 86400 * 30; // 30 días
            }
            
            if (time() - $_SESSION['last_activity'] > $timeout) {
                $this->destroySession();
                return false;
            }
        }

        return true;
    }

    /**
     * Obtener timeout de sesión configurado
     */
    private function getSessionTimeout(): int
    {
        try {
            return $this->settingsRepository->getSessionTimeout();
        } catch (\Exception $e) {
            // Si hay error, usar valor por defecto
            error_log("Error al obtener timeout de sesión en middleware: " . $e->getMessage());
            return 3600; // 1 hora por defecto
        }
    }

    /**
     * Destruir sesión
     */
    private function destroySession(): void
    {
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
    }
}

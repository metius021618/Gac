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
    private SettingsRepository $settingsRepository;

    public function __construct()
    {
        $this->settingsRepository = new SettingsRepository();
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
            $timeout = $this->getSessionTimeout();
            
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
        return $this->settingsRepository->getSessionTimeout();
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

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
use Gac\Repositories\SessionRepository;

class AuthMiddleware
{
    private ?SettingsRepository $settingsRepository = null;
    private ?SessionRepository $sessionRepository = null;

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
        // Instanciar SessionRepository de forma segura
        try {
            $this->sessionRepository = new SessionRepository();
        } catch (\Exception $e) {
            // Si hay error al crear SessionRepository, usar valor por defecto
            error_log("Error al inicializar SessionRepository en middleware: " . $e->getMessage());
            $this->sessionRepository = null;
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

        // Actualizar última actividad en la base de datos y en $_SESSION
        // Esto asegura que tanto la BD como el archivo de sesión PHP se actualicen
        $sessionId = session_id();
        $now = time();
        if ($this->sessionRepository && $sessionId) {
            $this->sessionRepository->updateLastActivity($sessionId);
        }
        // Actualizar en $_SESSION para que PHP también actualice el archivo de sesión
        // (PHP escribirá el archivo automáticamente cuando el script termine)
        $_SESSION['last_activity'] = $now;
    }

    /**
     * Verificar si está autenticado
     */
    private function isAuthenticated(): bool
    {
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            return false;
        }

        $sessionId = session_id();
        if (!$sessionId) {
            return false;
        }

        // Obtener timeout configurado del sistema
        $timeout = 3600; // 1 hora por defecto
        if ($this->settingsRepository) {
            $timeout = $this->getSessionTimeout();
        }
        
        // Si tiene "recordar" activado, usar 30 días
        if (isset($_SESSION['remember']) && $_SESSION['remember']) {
            $timeout = 86400 * 30; // 30 días
        }
        
        // Log para debug (solo si APP_DEBUG está activado)
        if (defined('APP_DEBUG') && APP_DEBUG) {
            $sessionRow = $this->sessionRepository ? $this->sessionRepository->findById(session_id()) : null;
            $lastActivity = $sessionRow ? (int)$sessionRow['last_activity'] : ($_SESSION['last_activity'] ?? 0);
            $minutesSinceActivity = $lastActivity > 0 ? round((time() - $lastActivity) / 60, 1) : 0;
            error_log(sprintf(
                "[AuthMiddleware] Timeout configurado: %d segundos (%d horas). Última actividad hace: %s minutos. Sesión expirada: %s",
                $timeout,
                round($timeout / 3600, 1),
                $minutesSinceActivity,
                ($lastActivity > 0 && (time() - $lastActivity) > $timeout) ? 'SÍ' : 'NO'
            ));
        }

        // Verificar timeout de sesión
        if ($this->sessionRepository) {
            $sessionRow = $this->sessionRepository->findById($sessionId);
            if ($sessionRow) {
                // Sesión en BD: usar last_activity de la BD
                if ($this->sessionRepository->isExpired($sessionId, $timeout)) {
                    $this->destroySession();
                    return false;
                }
            } else {
                // No hay fila en BD (tabla inexistente, fallo al crear, etc.): usar solo $_SESSION
                if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
                    $this->destroySession();
                    return false;
                }
            }
        } else {
            if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
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
        // Eliminar sesión de la base de datos
        $sessionId = session_id();
        if ($this->sessionRepository && $sessionId) {
            $this->sessionRepository->delete($sessionId);
        }

        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
    }
}

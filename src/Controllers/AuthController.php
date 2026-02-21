<?php
/**
 * GAC - Controlador de Autenticación
 * Sistema de login con medidas de seguridad
 * 
 * @package Gac\Controllers
 */

namespace Gac\Controllers;

use Gac\Core\Request;
use Gac\Repositories\UserRepository;
use Gac\Repositories\SettingsRepository;
use Gac\Repositories\SessionRepository;

class AuthController
{
    private UserRepository $userRepository;
    private ?SettingsRepository $settingsRepository = null;
    private ?SessionRepository $sessionRepository = null;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        // Instanciar SettingsRepository de forma lazy para evitar errores si la tabla no existe
        try {
            $this->settingsRepository = new SettingsRepository();
        } catch (\Exception $e) {
            // Si hay error al crear SettingsRepository, usar valor por defecto
            $this->settingsRepository = null;
        }
        // Instanciar SessionRepository de forma lazy
        try {
            $this->sessionRepository = new SessionRepository();
        } catch (\Exception $e) {
            // Si hay error al crear SessionRepository, usar valor por defecto
            $this->sessionRepository = null;
        }
    }
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_TIME = 900; // 15 minutos en segundos

    /**
     * Mostrar formulario de login
     */
    public function showLogin(Request $request): void
    {
        // Si ya está autenticado, redirigir al dashboard
        if ($this->isAuthenticated()) {
            redirect('/admin/dashboard');
            return;
        }

        $error = $request->get('error', '');
        $errorMessage = '';
        if ($error === 'no_views') {
            $errorMessage = 'Tu rol no tiene vistas asignadas. Contacta al administrador.';
        } elseif ($error === 'no_access') {
            $errorMessage = 'No tienes acceso a esa sección.';
        }
        $this->renderView('auth/login', [
            'title' => 'Iniciar Sesión',
            'error_message' => $errorMessage
        ]);
    }

    /**
     * Procesar login
     */
    public function login(Request $request): void
    {
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

        $username = trim($request->input('username', ''));
        $password = $request->input('password', '');
        $remember = $request->input('remember', false);

        // Validación básica
        if (empty($username) || empty($password)) {
            json_response([
                'success' => false,
                'message' => 'Usuario y contraseña son requeridos'
            ], 400);
            return;
        }

        // Verificar intentos de login
        if ($this->isLockedOut($username)) {
            json_response([
                'success' => false,
                'message' => 'Demasiados intentos fallidos. Intenta nuevamente en 15 minutos.'
            ], 429);
            return;
        }

        // Validar credenciales
        $user = $this->validateCredentials($username, $password);

        if (!$user) {
            $this->recordFailedAttempt($username);
            json_response([
                'success' => false,
                'message' => 'Usuario o contraseña incorrectos'
            ], 401);
            return;
        }

        // Verificar si el usuario está activo
        if (!$user['active']) {
            json_response([
                'success' => false,
                'message' => 'Tu cuenta está desactivada. Contacta al administrador.'
            ], 403);
            return;
        }

        // Login exitoso
        $this->createSession($user, $remember);
        $this->clearFailedAttempts($username);
        $this->updateLastLogin($user['id']);

        json_response([
            'success' => true,
            'message' => 'Login exitoso',
            'redirect' => '/admin/dashboard'
        ]);
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request): void
    {
        $this->destroySession();
        
        if ($request->isAjax() || $request->method() === 'POST') {
            json_response([
                'success' => true,
                'message' => 'Sesión cerrada correctamente',
                'redirect' => '/login'
            ]);
        } else {
            redirect('/login');
        }
    }

    /**
     * Validar credenciales
     */
    private function validateCredentials(string $username, string $password): ?array
    {
        // Buscar usuario por username o email
        $user = $this->userRepository->findByUsername($username);
        
        if (!$user) {
            // Intentar buscar por email
            $user = $this->userRepository->findByEmail($username);
        }

        if (!$user) {
            return null;
        }

        // Verificar contraseña
        if (!password_verify($password, $user['password'])) {
            return null;
        }

        return $user;
    }

    /**
     * Crear sesión de usuario
     */
    private function createSession(array $user, bool $remember = false): void
    {
        // Regenerar ID de sesión por seguridad
        session_regenerate_id(true);
        $sessionId = session_id();

        // Configurar datos de sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();

        // Si "recordar" está activado, extender tiempo de sesión
        if ($remember) {
            $_SESSION['remember'] = true;
            $_SESSION['cookie_lifetime'] = 86400 * 30; // 30 días
        } else {
            // Usar timeout configurado del sistema
            if ($this->settingsRepository) {
                $_SESSION['cookie_lifetime'] = $this->settingsRepository->getSessionTimeout();
            } else {
                $_SESSION['cookie_lifetime'] = 3600; // 1 hora por defecto
            }
        }

        // Guardar sesión en la base de datos
        if ($this->sessionRepository) {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $payload = json_encode([
                'username' => $user['username'],
                'email' => $user['email'],
                'role_id' => $user['role_id']
            ]);
            $this->sessionRepository->createOrUpdate($sessionId, $user['id'], $ipAddress, $userAgent, $payload);
        }

        // Configurar cookie de sesión segura
        $this->setSecureSessionCookie($remember);
    }

    /**
     * Configurar cookie de sesión segura
     */
    private function setSecureSessionCookie(bool $remember = false): void
    {
        $params = session_get_cookie_params();
        if ($remember) {
            $lifetime = 86400 * 30; // 30 días
        } else {
            $lifetime = $this->settingsRepository ? $this->settingsRepository->getSessionTimeout() : 3600;
        }
        $expires = time() + $lifetime;
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        
        // Usar formato correcto de setcookie() para PHP 7.4+
        setcookie(
            session_name(),
            session_id(),
            $expires,
            $params['path'],
            $params['domain'],
            $secure,
            true // httponly
        );
    }

    /**
     * Destruir sesión
     */
    private function destroySession(): void
    {
        // Eliminar sesión de la base de datos
        if ($this->sessionRepository && isset($_SESSION['user_id'])) {
            $sessionId = session_id();
            if ($sessionId) {
                $this->sessionRepository->delete($sessionId);
            }
        }

        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
    }

    /**
     * Verificar si el usuario está autenticado
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
            $timeout = $this->settingsRepository->getSessionTimeout();
        }
        
        // Si tiene "recordar" activado, usar 30 días
        if (isset($_SESSION['remember']) && $_SESSION['remember']) {
            $timeout = 86400 * 30; // 30 días
        }

        // Verificar timeout de sesión usando la tabla sessions
        if ($this->sessionRepository) {
            if ($this->sessionRepository->isExpired($sessionId, $timeout)) {
                $this->destroySession();
                return false;
            }
            // Actualizar última actividad en la base de datos
            $this->sessionRepository->updateLastActivity($sessionId);
        } else {
            // Fallback: usar $_SESSION si SessionRepository no está disponible
            if (isset($_SESSION['last_activity'])) {
                if (time() - $_SESSION['last_activity'] > $timeout) {
                    $this->destroySession();
                    return false;
                }
            }
        }

        // Actualizar última actividad en $_SESSION también
        $_SESSION['last_activity'] = time();

        return true;
    }

    /**
     * Validar token CSRF
     */
    private function validateCsrfToken(Request $request): bool
    {
        $token = $request->input('csrf_token', '');
        
        if (empty($token)) {
            return false;
        }

        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Generar token CSRF
     */
    public static function generateCsrfToken(): string
    {
        // Asegurar que la sesión esté iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verificar si la cuenta está bloqueada
     */
    private function isLockedOut(string $username): bool
    {
        $key = 'login_attempts_' . md5($username);
        
        if (!isset($_SESSION[$key])) {
            return false;
        }

        $attempts = $_SESSION[$key];
        
        if ($attempts['count'] >= self::MAX_LOGIN_ATTEMPTS) {
            $lockoutUntil = $attempts['lockout_until'] ?? 0;
            
            if (time() < $lockoutUntil) {
                return true;
            } else {
                // Tiempo de bloqueo expirado, limpiar intentos
                unset($_SESSION[$key]);
                return false;
            }
        }

        return false;
    }

    /**
     * Registrar intento fallido
     */
    private function recordFailedAttempt(string $username): void
    {
        $key = 'login_attempts_' . md5($username);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'count' => 0,
                'lockout_until' => 0
            ];
        }

        $_SESSION[$key]['count']++;

        if ($_SESSION[$key]['count'] >= self::MAX_LOGIN_ATTEMPTS) {
            $_SESSION[$key]['lockout_until'] = time() + self::LOCKOUT_TIME;
        }
    }

    /**
     * Limpiar intentos fallidos
     */
    private function clearFailedAttempts(string $username): void
    {
        $key = 'login_attempts_' . md5($username);
        unset($_SESSION[$key]);
    }

    /**
     * Actualizar último login
     */
    private function updateLastLogin(int $userId): void
    {
        $this->userRepository->updateLastLogin($userId);
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

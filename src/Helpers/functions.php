<?php
/**
 * GAC - Funciones Helper Globales
 * 
 * @package Gac\Helpers
 */

if (!function_exists('gac_version')) {
    /**
     * Obtener versión de GAC
     */
    function gac_version(): string
    {
        return defined('GAC_VERSION') ? GAC_VERSION : '2.0.0';
    }
}

if (!function_exists('gac_name')) {
    /**
     * Obtener nombre de GAC
     */
    function gac_name(): string
    {
        return defined('GAC_NAME') ? GAC_NAME : 'GAC';
    }
}

if (!function_exists('base_path')) {
    /**
     * Obtener ruta base del proyecto
     */
    function base_path(string $path = ''): string
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
        return $basePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('public_path')) {
    /**
     * Obtener ruta pública
     */
    function public_path(string $path = ''): string
    {
        $publicPath = defined('PUBLIC_PATH') ? PUBLIC_PATH : base_path('public');
        return $publicPath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('env')) {
    /**
     * Obtener variable de entorno
     */
    function env(string $key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('config')) {
    /**
     * Obtener configuración
     */
    function config(string $key, $default = null)
    {
        // Implementar según necesidad
        return $default;
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and Die (solo en desarrollo)
     */
    function dd(...$vars)
    {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            foreach ($vars as $var) {
                var_dump($var);
            }
            die();
        }
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirigir a URL
     */
    function redirect(string $url, int $code = 302): void
    {
        header("Location: {$url}", true, $code);
        exit;
    }
}

if (!function_exists('view')) {
    /**
     * Cargar vista
     */
    function view(string $view, array $data = []): void
    {
        extract($data);
        $viewPath = base_path('views/' . str_replace('.', '/', $view) . '.php');
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            throw new Exception("Vista no encontrada: {$view}");
        }
    }
}

if (!function_exists('json_response')) {
    /**
     * Enviar respuesta JSON
     */
    function json_response(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('user_role_views')) {
    /**
     * Obtener las vistas (view_keys) permitidas para el rol del usuario actual.
     * Si el rol no tiene role_views configurados, retorna null (acceso completo).
     * @return string[]|null Array de view_keys o null si acceso completo
     */
    function user_role_views(): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            return null;
        }
        $roleId = $_SESSION['role_id'] ?? 0;
        if (!$roleId || empty($_SESSION['logged_in'])) {
            return null;
        }
        try {
            $repo = new \Gac\Repositories\RoleRepository();
            $keys = $repo->getViewKeys((int) $roleId);
            if (empty($keys)) {
                return null; // Sin role_views = acceso completo (backward compat)
            }
            return $keys;
        } catch (\Throwable $e) {
            error_log('user_role_views: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('user_can_view')) {
    /**
     * Comprobar si el usuario actual puede ver una vista (view_key).
     * @param string $viewKey
     * @param array|null $roleViews Resultado de user_role_views() (opcional, se obtiene si no se pasa)
     */
    function user_can_view(string $viewKey, ?array $roleViews = null): bool
    {
        $views = $roleViews ?? user_role_views();
        if ($views === null) {
            return true; // Sin restricción = puede ver todo
        }
        return in_array($viewKey, $views, true);
    }
}

if (!function_exists('log_user_activity')) {
    /**
     * Registrar actividad de usuario para la vista "Actividad de usuario" (solo superadmin).
     * Solo registra si el usuario actual NO es superadmin (role_id 1).
     * @param string $action agregar_correo | edicion | eliminar | asignado
     * @param string $description Descripción legible de la acción
     */
    function log_user_activity(string $action, string $description): void
    {
        if (session_status() === PHP_SESSION_NONE || empty($_SESSION['user_id'])) {
            return;
        }
        $userId = (int) $_SESSION['user_id'];
        $username = (string) ($_SESSION['username'] ?? '');
        \Gac\Repositories\UserActivityLogRepository::log($userId, $username, $action, $description);
    }
}

if (!function_exists('is_superadmin')) {
    /**
     * Comprobar si el usuario actual es superadmin (role_id 1 = admin)
     */
    function is_superadmin(): bool
    {
        return isset($_SESSION['role_id']) && (int) $_SESSION['role_id'] === 1;
    }
}

if (!function_exists('user_can_action')) {
    /**
     * Comprobar si el usuario actual puede realizar una acción en una vista.
     * Si no hay role_views configurados, retorna true (acceso completo).
     * Si tiene la vista pero no role_view_actions para esa vista, solo "ver" está permitido.
     * @param string $viewKey
     * @param string $action Ej: 'ver', 'eliminar', 'agregar', 'editar', 'deshabilitar'
     */
    function user_can_action(string $viewKey, string $action): bool
    {
        $roleViews = user_role_views();
        if ($roleViews === null) {
            return true; // Sin restricción
        }
        if (!in_array($viewKey, $roleViews, true)) {
            return false; // No tiene la vista
        }
        try {
            $repo = new \Gac\Repositories\RoleRepository();
            $actions = $repo->getViewActions((int) ($_SESSION['role_id'] ?? 0));
            $allowed = $actions[$viewKey] ?? null;
            if ($allowed === null) {
                return $action === 'ver'; // Sin acciones configuradas = solo ver
            }
            return in_array($action, $allowed, true);
        } catch (\Throwable $e) {
            error_log('user_can_action: ' . $e->getMessage());
            return $action === 'ver';
        }
    }
}

<?php
/**
 * GAC - Gestor Automatizado de Códigos
 * Front Controller - Punto de entrada principal
 * 
 * @version 2.0.0
 */

// Configurar timeout de sesión PHP ANTES de iniciar sesión
// Usar valor alto por defecto (7 horas = 25200 segundos) para que PHP no elimine la sesión
// El timeout real se verifica en AuthMiddleware usando el valor de settings
$maxLifetime = ini_get('session.gc_maxlifetime');
if ($maxLifetime < 25200) {
    ini_set('session.gc_maxlifetime', 25200); // 7 horas máximo permitido
}
$actualMaxLifetime = ini_get('session.gc_maxlifetime');
if (defined('APP_DEBUG') && APP_DEBUG) {
    error_log("[Session] session.gc_maxlifetime configurado: {$actualMaxLifetime} segundos (" . round($actualMaxLifetime / 3600, 1) . " horas)");
}
ini_set('session.cookie_lifetime', 0); // Cookie expira al cerrar navegador (o según settings)

// Iniciar sesión
session_start();

// Cargar autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
try {
    if (class_exists('Dotenv\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
    }
} catch (\Exception $e) {
    // Si no existe .env o Dotenv no está instalado, usar valores por defecto
    // Esto es solo para desarrollo
}

// Definir constantes de rutas (antes de cargar configuración)
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', __DIR__);

// Cargar configuración (esto definirá APP_NAME, APP_VERSION, APP_ENV, etc.)
require_once BASE_PATH . '/src/Config/AppConfig.php';

// Definir constantes específicas de GAC (después de AppConfig)
if (!defined('GAC_VERSION')) {
    define('GAC_VERSION', APP_VERSION ?? '2.0.0');
}
if (!defined('GAC_NAME')) {
    define('GAC_NAME', APP_NAME ?? 'GAC');
}

// Cargar helpers
require_once BASE_PATH . '/src/Helpers/functions.php';

// Asegurar que SCRIPT_NAME esté configurado correctamente para el router
if (!isset($_SERVER['SCRIPT_NAME']) || empty($_SERVER['SCRIPT_NAME'])) {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
}

// Debug: Log del request (solo si APP_DEBUG está activado)
if (defined('APP_DEBUG') && APP_DEBUG) {
    error_log("Index.php: REQUEST_URI=" . ($_SERVER['REQUEST_URI'] ?? 'N/A') . 
              ", SCRIPT_NAME=" . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . 
              ", METHOD=" . ($_SERVER['REQUEST_METHOD'] ?? 'N/A'));
}

// Inicializar aplicación
use Gac\Core\Application;

try {
    $app = new Application();
    $app->run();
} catch (\Exception $e) {
    error_log("Error fatal en Application: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error' => defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : 'Error desconocido'
    ]);
}

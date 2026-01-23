<?php
/**
 * GAC - Gestor Automatizado de Códigos
 * Front Controller - Punto de entrada principal
 * 
 * @version 2.0.0
 */

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

// Inicializar aplicación
use Gac\Core\Application;

$app = new Application();
$app->run();

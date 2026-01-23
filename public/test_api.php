<?php
/**
 * Archivo de prueba para verificar el routing de API
 */

header('Content-Type: application/json');

// Simular lo que hace index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';

// Obtener el path
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsedPath = parse_url($requestUri, PHP_URL_PATH);
$path = $parsedPath !== null ? $parsedPath : $requestUri;
$path = rtrim($path, '/');
if (empty($path)) {
    $path = '/';
}

$debug = [
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
    'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'N/A',
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
    'parsed_path' => $parsedPath,
    'normalized_path' => $path,
    'expected_path_for_api' => '/api/v1/codes/consult',
    'path_matches' => $path === '/api/v1/codes/consult'
];

// Si la petición es a /api/v1/codes/consult, intentar cargar el router
if ($path === '/api/v1/codes/consult' || isset($_GET['test_route'])) {
    try {
        // Cargar autoloader
        require_once __DIR__ . '/../vendor/autoload.php';
        
        // Cargar configuración básica
        define('BASE_PATH', dirname(__DIR__));
        define('PUBLIC_PATH', __DIR__);
        
        require_once BASE_PATH . '/src/Config/AppConfig.php';
        require_once BASE_PATH . '/src/Helpers/functions.php';
        
        // Crear request
        $request = new \Gac\Core\Request();
        $debug['request_path'] = $request->path();
        $debug['request_method'] = $request->method();
        
        // Cargar router y verificar rutas
        $router = new \Gac\Core\Router();
        $app = new \Gac\Core\Application();
        
        // Usar reflexión para obtener las rutas
        $reflection = new ReflectionClass($app);
        $method = $reflection->getMethod('loadRoutes');
        $method->setAccessible(true);
        $method->invoke($app, $router);
        
        // Obtener rutas del router
        $reflectionRouter = new ReflectionClass($router);
        $routesProperty = $reflectionRouter->getProperty('routes');
        $routesProperty->setAccessible(true);
        $routes = $routesProperty->getValue($router);
        
        $debug['routes_registered'] = array_map(function($r) {
            return $r['method'] . ' ' . $r['path'];
        }, $routes);
        
    } catch (\Exception $e) {
        $debug['error'] = $e->getMessage();
        $debug['trace'] = $e->getTraceAsString();
    }
}

echo json_encode($debug, JSON_PRETTY_PRINT);

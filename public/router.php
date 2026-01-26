<?php
/**
 * GAC - Router para servidor PHP built-in
 * Este archivo se usa cuando se ejecuta: php -S localhost:8001 -t public public/router.php
 * 
 * @package Gac\Core
 */

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// Si parse_url falla, usar el URI completo
if ($requestPath === null || $requestPath === false) {
    $requestPath = $requestUri;
}

// Normalizar el path
$requestPath = rtrim($requestPath, '/');
if (empty($requestPath)) {
    $requestPath = '/';
}

// Si el archivo solicitado existe físicamente, servirlo directamente
$filePath = __DIR__ . $requestPath;
if ($requestPath !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    return false; // Dejar que el servidor lo sirva
}

// Si es un directorio, intentar servir index.php o index.html
if ($requestPath !== '/' && is_dir($filePath)) {
    $indexFiles = ['index.php', 'index.html'];
    foreach ($indexFiles as $indexFile) {
        $indexPath = $filePath . '/' . $indexFile;
        if (file_exists($indexPath)) {
            return false; // Dejar que el servidor lo sirva
        }
    }
}

// Para todas las demás rutas (incluyendo "/" y rutas API), redirigir a index.php
// Configurar SCRIPT_NAME e indicar que el path debe ser procesado por index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_URI'] = $requestPath;

// Incluir index.php que procesará la ruta
require __DIR__ . '/index.php';

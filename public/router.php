<?php
/**
 * GAC - Router para servidor PHP built-in
 * Este archivo se usa cuando se ejecuta: php -S localhost:8001 -t public router.php
 * 
 * @package Gac\Core
 */

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';

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

// Para todas las demás rutas (incluyendo "/"), redirigir a index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_URI'] = $requestPath;
require __DIR__ . '/index.php';

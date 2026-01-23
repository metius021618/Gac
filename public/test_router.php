<?php
/**
 * Archivo de prueba para verificar el routing
 */

header('Content-Type: application/json');

$debug = [
    'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'N/A',
    'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'N/A',
    'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
    'PATH_INFO' => $_SERVER['PATH_INFO'] ?? 'N/A',
    'QUERY_STRING' => $_SERVER['QUERY_STRING'] ?? 'N/A',
    'HTTP_X_REQUESTED_WITH' => $_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'N/A',
    'CONTENT_TYPE' => $_SERVER['CONTENT_TYPE'] ?? 'N/A',
];

// Intentar parsear el path
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsedPath = parse_url($requestUri, PHP_URL_PATH);
$debug['parsed_path'] = $parsedPath;

// Normalizar
$path = rtrim($parsedPath ?? $requestUri, '/');
if (empty($path)) {
    $path = '/';
}
$debug['normalized_path'] = $path;

echo json_encode($debug, JSON_PRETTY_PRINT);

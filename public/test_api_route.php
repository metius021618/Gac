<?php
/**
 * Test para verificar que las rutas API funcionen
 */

header('Content-Type: application/json');

$debug = [
    'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'N/A',
    'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'N/A',
    'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
    'PATH_INFO' => $_SERVER['PATH_INFO'] ?? 'N/A',
    'parsed_path' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH),
    'current_dir' => __DIR__,
    'router_exists' => file_exists(__DIR__ . '/router.php'),
    'index_exists' => file_exists(__DIR__ . '/index.php')
];

echo json_encode($debug, JSON_PRETTY_PRINT);

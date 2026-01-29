<?php
/**
 * Script para limpiar intentos de login bloqueados
 * Ejecutar desde el navegador: https://app.pocoyoni.com/clear_login_attempts.php
 */

session_start();

// Limpiar todos los intentos de login bloqueados
$cleared = 0;
foreach ($_SESSION as $key => $value) {
    if (strpos($key, 'login_attempts_') === 0) {
        unset($_SESSION[$key]);
        $cleared++;
    }
}

// Limpiar la sesión
session_write_close();

// Respuesta
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => "Se limpiaron {$cleared} bloqueos de login",
    'cleared' => $cleared
]);

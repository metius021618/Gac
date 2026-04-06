<?php
/**
 * Script para limpiar intentos de login bloqueados
 * Ejecutar desde el navegador: https://TU_DOMINIO/clear_login_attempts.php (ej. new.pocoyoni.com)
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

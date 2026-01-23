<?php
// Script de prueba para verificar que PHP funciona
echo "PHP funciona correctamente!<br>";
echo "Versión PHP: " . phpversion() . "<br>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'no definido') . "<br>";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'no definido') . "<br>";

// Verificar si existe vendor
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo "vendor/autoload.php existe<br>";
} else {
    echo "vendor/autoload.php NO existe<br>";
}

// Verificar si existe .env
if (file_exists(__DIR__ . '/../.env')) {
    echo ".env existe<br>";
} else {
    echo ".env NO existe<br>";
}

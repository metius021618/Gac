<?php
/**
 * Bootstrap PHPUnit — carga autoload, .env y constantes de app.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

if (file_exists(BASE_PATH . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
    $dotenv->safeLoad();
}

\Gac\Config\AppConfig::load();

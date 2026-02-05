<?php
/**
 * GAC - Configuración de la Aplicación
 * 
 * @package Gac\Config
 */

namespace Gac\Config;

class AppConfig
{
    /**
     * Cargar configuración desde variables de entorno
     */
    public static function load()
    {
        // Configuración de aplicación (solo definir si no existe)
        if (!defined('APP_NAME')) {
            define('APP_NAME', $_ENV['APP_NAME'] ?? 'GAC');
        }
        if (!defined('APP_VERSION')) {
            define('APP_VERSION', $_ENV['APP_VERSION'] ?? '2.0.0');
        }
        if (!defined('APP_ENV')) {
            define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
        }
        if (!defined('APP_DEBUG')) {
            define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN));
        }
        if (!defined('APP_URL')) {
            define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost/gac');
        }

        // Configuración de base de datos operativa
        if (!defined('DB_HOST')) {
            define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
        }
        if (!defined('DB_PORT')) {
            define('DB_PORT', $_ENV['DB_PORT'] ?? 3306);
        }
        if (!defined('DB_NAME')) {
            define('DB_NAME', $_ENV['DB_NAME'] ?? 'pocoavbb_gac');
        }
        if (!defined('DB_USER')) {
            define('DB_USER', $_ENV['DB_USER'] ?? 'root');
        }
        if (!defined('DB_PASSWORD')) {
            define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');
        }
        if (!defined('DB_CHARSET')) {
            define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');
        }
        if (!defined('DB_COLLATE')) {
            define('DB_COLLATE', $_ENV['DB_COLLATE'] ?? 'utf8mb4_spanish_ci');
        }

        // Configuración de base de datos warehouse (usar misma BD por ahora)
        define('WAREHOUSE_DB_HOST', $_ENV['WAREHOUSE_DB_HOST'] ?? DB_HOST);
        define('WAREHOUSE_DB_PORT', $_ENV['WAREHOUSE_DB_PORT'] ?? DB_PORT);
        define('WAREHOUSE_DB_NAME', $_ENV['WAREHOUSE_DB_NAME'] ?? DB_NAME);
        define('WAREHOUSE_DB_USER', $_ENV['WAREHOUSE_DB_USER'] ?? DB_USER);
        define('WAREHOUSE_DB_PASSWORD', $_ENV['WAREHOUSE_DB_PASSWORD'] ?? DB_PASSWORD);

        // Configuración de seguridad
        define('APP_KEY', $_ENV['APP_KEY'] ?? '');
        define('SESSION_LIFETIME', $_ENV['SESSION_LIFETIME'] ?? 120);
        define('SESSION_SECURE', filter_var($_ENV['SESSION_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN));
        define('SESSION_HTTPONLY', filter_var($_ENV['SESSION_HTTPONLY'] ?? true, FILTER_VALIDATE_BOOLEAN));

        // Configuración Gmail API
        define('GMAIL_CLIENT_ID', $_ENV['GMAIL_CLIENT_ID'] ?? '');
        define('GMAIL_CLIENT_SECRET', $_ENV['GMAIL_CLIENT_SECRET'] ?? '');
        define('GMAIL_REDIRECT_URI', $_ENV['GMAIL_REDIRECT_URI'] ?? '');
        define('GMAIL_SCOPES', $_ENV['GMAIL_SCOPES'] ?? 'https://www.googleapis.com/auth/gmail.readonly');

        // Configuración Outlook/Microsoft Graph API
        define('OUTLOOK_CLIENT_ID', $_ENV['OUTLOOK_CLIENT_ID'] ?? '');
        define('OUTLOOK_CLIENT_SECRET', $_ENV['OUTLOOK_CLIENT_SECRET'] ?? '');
        define('OUTLOOK_TENANT_ID', $_ENV['OUTLOOK_TENANT_ID'] ?? '');
        define('OUTLOOK_REDIRECT_URI', $_ENV['OUTLOOK_REDIRECT_URI'] ?? '');

        // Configuración IMAP
        define('IMAP_HOST', $_ENV['IMAP_HOST'] ?? '');
        define('IMAP_PORT', $_ENV['IMAP_PORT'] ?? 993);
        define('IMAP_ENCRYPTION', $_ENV['IMAP_ENCRYPTION'] ?? 'ssl');
        define('IMAP_VALIDATE_CERT', filter_var($_ENV['IMAP_VALIDATE_CERT'] ?? true, FILTER_VALIDATE_BOOLEAN));

        // Configuración de cifrado
        define('ENCRYPTION_KEY', $_ENV['ENCRYPTION_KEY'] ?? '');

        // Configuración de logging
        define('LOG_CHANNEL', $_ENV['LOG_CHANNEL'] ?? 'file');
        define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? 'info');

        // Configuración de cron
        define('CRON_ENABLED', filter_var($_ENV['CRON_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN));
        define('CRON_EMAIL_READER_INTERVAL', $_ENV['CRON_EMAIL_READER_INTERVAL'] ?? 5);
        define('CRON_WAREHOUSE_SYNC_INTERVAL', $_ENV['CRON_WAREHOUSE_SYNC_INTERVAL'] ?? 60);

        // Configuración de rate limiting
        define('RATE_LIMIT_ENABLED', filter_var($_ENV['RATE_LIMIT_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN));
        define('RATE_LIMIT_REQUESTS', $_ENV['RATE_LIMIT_REQUESTS'] ?? 60);

        // Configuración de timezone
        date_default_timezone_set($_ENV['TIMEZONE'] ?? 'America/Mexico_City');

        // Configuración de locale
        define('LOCALE', $_ENV['LOCALE'] ?? 'es_ES');
        define('FALLBACK_LOCALE', $_ENV['FALLBACK_LOCALE'] ?? 'es_ES');
    }
}

// Cargar configuración automáticamente
AppConfig::load();

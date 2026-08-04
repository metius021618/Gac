<?php
/**
 * Migración one-shot: email_subjects.category
 * Uso: php scripts/migrate_email_subjects_category.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

if (file_exists(BASE_PATH . '/.env')) {
    Dotenv\Dotenv::createImmutable(BASE_PATH)->safeLoad();
}
Gac\Config\AppConfig::load();

$db = Gac\Helpers\Database::getConnection();
$cols = $db->query("SHOW COLUMNS FROM email_subjects LIKE 'category'")->fetchAll();
if ($cols) {
    echo "MIGRATION_SKIP_EXISTS\n";
    exit(0);
}

$db->exec("ALTER TABLE email_subjects ADD COLUMN category VARCHAR(32) NOT NULL DEFAULT 'general' AFTER subject_line");
try {
    $db->exec('ALTER TABLE email_subjects ADD INDEX idx_email_subjects_category (category)');
} catch (Throwable $e) {
    // index may already exist
}
echo "MIGRATION_OK\n";

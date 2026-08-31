<?php
/**
 * Mueve el asunto histórico de Hogar (Netflix temporal) a category=modo_hogar.
 * Uso: php scripts/migrate_email_subjects_modo_hogar.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

if (file_exists(BASE_PATH . '/.env')) {
    Dotenv\Dotenv::createImmutable(BASE_PATH)->safeLoad();
}
Gac\Config\AppConfig::load();

$db = Gac\Helpers\Database::getConnection();
$subject = 'Tu código de acceso temporal de Netflix';

$stmt = $db->prepare("
    UPDATE email_subjects
    SET category = 'modo_hogar', updated_at = NOW()
    WHERE active = 1
      AND category <> 'modo_hogar'
      AND LOWER(TRIM(subject_line)) = LOWER(:subject)
");
$stmt->execute([':subject' => $subject]);
$moved = $stmt->rowCount();

if ($moved > 0) {
    echo "MIGRATION_OK moved={$moved}\n";
    exit(0);
}

$check = $db->prepare("
    SELECT COUNT(*) AS n
    FROM email_subjects
    WHERE active = 1
      AND category = 'modo_hogar'
      AND LOWER(TRIM(subject_line)) = LOWER(:subject)
");
$check->execute([':subject' => $subject]);
$already = (int) ($check->fetch(PDO::FETCH_ASSOC)['n'] ?? 0);
if ($already > 0) {
    echo "MIGRATION_SKIP_EXISTS\n";
    exit(0);
}

$platform = $db->query("
    SELECT id FROM platforms
    WHERE enabled = 1 AND LOWER(name) = 'netflix'
    ORDER BY id ASC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

if (!$platform) {
    echo "MIGRATION_SKIP_NO_NETFLIX_PLATFORM\n";
    exit(0);
}

$insert = $db->prepare("
    INSERT INTO email_subjects (platform_id, subject_line, category, active)
    VALUES (:platform_id, :subject_line, 'modo_hogar', 1)
");
$insert->execute([
    ':platform_id' => (int) $platform['id'],
    ':subject_line' => $subject,
]);
echo "MIGRATION_OK inserted_id=" . $db->lastInsertId() . "\n";

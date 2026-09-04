<?php
/**
 * Diagnóstico rápido: asuntos + códigos recientes + estado sync.
 * Uso: php scripts/diagnose_codes_intake.php
 */
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';
if (file_exists(BASE_PATH . '/.env')) {
    Dotenv\Dotenv::createImmutable(BASE_PATH)->safeLoad();
}
Gac\Config\AppConfig::load();

$db = Gac\Helpers\Database::getConnection();

echo "=== SUBJECTS BY CATEGORY ===\n";
$rows = $db->query("SELECT category, COUNT(*) c FROM email_subjects WHERE active=1 GROUP BY category")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo ($r['category'] ?: '(null)') . ': ' . $r['c'] . "\n";
}

foreach (['modo_hogar', 'modo_viaje', 'general'] as $cat) {
    echo "--- sample {$cat} ---\n";
    $stmt = $db->prepare("SELECT id, subject_line FROM email_subjects WHERE active=1 AND category=:c ORDER BY id DESC LIMIT 8");
    $stmt->execute([':c' => $cat]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        echo $r['id'] . ' | ' . $r['subject_line'] . "\n";
    }
}

echo "=== CODES COUNTS ===\n";
foreach ([
    '1h' => 'INTERVAL 1 HOUR',
    '6h' => 'INTERVAL 6 HOUR',
    '24h' => 'INTERVAL 1 DAY',
    '7d' => 'INTERVAL 7 DAY',
] as $label => $interval) {
    $n = $db->query("SELECT COUNT(*) n FROM codes WHERE received_at >= NOW() - {$interval}")->fetch(PDO::FETCH_ASSOC);
    echo "count_{$label}=" . ($n['n'] ?? 0) . "\n";
}

echo "=== LAST 20 CODES ===\n";
$c = $db->query("SELECT id, recipient_email, subject, origin, received_at, created_at FROM codes ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
foreach ($c as $r) {
    echo $r['id'] . ' | recv=' . $r['received_at'] . ' | origin=' . $r['origin'] . ' | ' . $r['recipient_email'] . ' | ' . substr((string)$r['subject'], 0, 80) . "\n";
}

echo "=== GMAIL MATRIX ===\n";
try {
    $m = $db->query("SELECT ea.id, ea.email, ea.enabled, ea.last_sync_at, ea.sync_status, LEFT(ea.error_message,200) err FROM gmail_matrix gm JOIN email_accounts ea ON ea.id=gm.email_account_id WHERE gm.id=1")->fetch(PDO::FETCH_ASSOC);
    if ($m) {
        echo json_encode($m, JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo "NO_MATRIX\n";
    }
} catch (Throwable $e) {
    echo 'matrix_err=' . $e->getMessage() . "\n";
}

echo "=== EMAIL ACCOUNTS SYNC ===\n";
$acc = $db->query("SELECT id, email, type, enabled, last_sync_at, sync_status, LEFT(COALESCE(error_message,''),120) err FROM email_accounts ORDER BY last_sync_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
foreach ($acc as $r) {
    echo $r['id'] . ' | ' . $r['type'] . ' | ' . $r['email'] . ' | en=' . $r['enabled'] . ' | status=' . $r['sync_status'] . ' | last=' . $r['last_sync_at'] . ' | err=' . $r['err'] . "\n";
}

echo "DONE\n";

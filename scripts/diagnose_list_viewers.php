<?php
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';
if (file_exists(BASE_PATH . '/.env')) {
    Dotenv\Dotenv::createImmutable(BASE_PATH)->safeLoad();
}
Gac\Config\AppConfig::load();

$r = new Gac\Repositories\EmailSubjectRepository();
$rows = $r->listViewersForSubject(76, '', 20);
echo 'count=' . count($rows) . PHP_EOL;
foreach (array_slice($rows, 0, 8) as $x) {
    echo ($x['username'] ?? '') . ' can=' . ($x['can_view'] ?? 0) . PHP_EOL;
}

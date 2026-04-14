<?php
/**
 * Página de diagnóstico del webhook Gmail (solo para pruebas).
 * Abre en el navegador: https://TU_DOMINIO/gmail/push/debug.php
 * Restringe o borra en producción si no quieres que sea público.
 */
header('Content-Type: text/html; charset=utf-8');

// Misma raíz que index.php (public_html/gmail/push → 3 niveles arriba)
$basePath = dirname(__DIR__, 3);
$logsDir = $basePath . DIRECTORY_SEPARATOR . 'logs';
if (!is_dir($logsDir)) {
    @mkdir($logsDir, 0755, true);
}

$logFile = $logsDir . DIRECTORY_SEPARATOR . 'gmail_webhook.log';
$debugLog = $logsDir . DIRECTORY_SEPARATOR . 'gmail_webhook_debug.log';
$workerScript = $basePath . DIRECTORY_SEPARATOR . 'cron' . DIRECTORY_SEPARATOR . 'process_gmail_history.py';

$logsDirExists = is_dir($logsDir);
$logsDirWritable = $logsDirExists && is_writable($logsDir);
$debugLogExists = file_exists($debugLog);
$workerExists = is_file($workerScript);

$testWriteOk = false;
if ($logsDirExists) {
    $testLine = date('Y-m-d H:i:s') . " [DEBUG] prueba de escritura desde debug.php\n";
    $testWriteOk = @file_put_contents($logFile, $testLine, FILE_APPEND | LOCK_EX) !== false;
}
$logFileExists = file_exists($logFile);

function tail_lines(string $path, int $max = 30): array
{
    if (!is_readable($path)) {
        return [];
    }
    $lines = file($path);
    if ($lines === false) {
        return [];
    }

    return array_slice($lines, -$max);
}

$logLines = tail_lines($logFile, 30);
$debugLines = tail_lines($debugLog, 30);

$documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '(no definido)';
$testPayloadB64 = rtrim(strtr(base64_encode('{"historyId":"999999999"}'), '+/', '-_'), '=');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Diagnóstico Webhook Gmail</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #1e1e1e; color: #d4d4d4; }
        h1 { color: #4ec9b0; }
        pre { background: #252526; padding: 12px; overflow-x: auto; white-space: pre-wrap; }
        .ok { color: #4ec9b0; }
        .err { color: #f48771; }
        .section { margin: 16px 0; }
        a { color: #569cd6; }
        button { padding: 8px 16px; cursor: pointer; background: #0e639c; color: #fff; border: none; border-radius: 4px; margin-right: 8px; }
        button:hover { background: #1177bb; }
        .note { color: #dcdcaa; font-size: 14px; }
    </style>
</head>
<body>
    <h1>Diagnóstico Webhook Gmail (Pub/Sub push)</h1>
    <p class="note">Los archivos de log <strong>solo se crean</strong> cuando alguien hace POST a <code>/gmail/push</code> (Google Pub/Sub o el botón de abajo). Si no hay POST, no habrá <code>gmail_webhook.log</code>.</p>
    <p class="note">En SSH revisa los logs en la ruta <strong>basePath</strong> de abajo (p. ej. <code>/home/customer/www/...</code>), no otra copia del proyecto.</p>
    <p>URL del webhook: <strong><?= htmlspecialchars('https://' . ($_SERVER['HTTP_HOST'] ?? '') . '/gmail/push') ?></strong></p>

    <div class="section">
        <h2>Rutas</h2>
        <pre>__FILE__ = <?= htmlspecialchars(__FILE__) ?>

DOCUMENT_ROOT = <?= htmlspecialchars($documentRoot) ?>

basePath (como index.php) = <?= htmlspecialchars($basePath) ?></pre>
    </div>

    <div class="section">
        <h2>Paths que usa el webhook</h2>
        <pre>logs/     = <?= htmlspecialchars($logsDir) ?>

gmail_webhook.log       = <?= htmlspecialchars($logFile) ?>

gmail_webhook_debug.log = <?= htmlspecialchars($debugLog) ?>

process_gmail_history   = <?= htmlspecialchars($workerScript) ?></pre>
    </div>

    <div class="section">
        <h2>Comprobaciones</h2>
        <pre>logs/ existe           : <?= $logsDirExists ? '<span class="ok">Sí</span>' : '<span class="err">No</span>' ?>

logs/ escribible        : <?= $logsDirWritable ? '<span class="ok">Sí</span>' : '<span class="err">No</span>' ?>

gmail_webhook.log       : <?= $logFileExists ? '<span class="ok">Existe</span>' : '<span class="err">No existe aún</span>' ?>

gmail_webhook_debug.log : <?= $debugLogExists ? '<span class="ok">Existe</span>' : '<span class="err">No existe aún</span>' ?> (una línea por cada POST)

process_gmail_history   : <?= $workerExists ? '<span class="ok">Existe</span>' : '<span class="err">No existe</span>' ?>

Escritura de prueba     : <?= $testWriteOk ? '<span class="ok">OK</span>' : '<span class="err">Falló</span>' ?></pre>
    </div>

    <div class="section">
        <h2>Últimas 30 líneas — gmail_webhook_debug.log</h2>
        <?php if (empty($debugLines)): ?>
            <pre class="err">(vacío: aún no ha entrado ningún POST a /gmail/push, o el path base es incorrecto)</pre>
        <?php else: ?>
            <pre><?= htmlspecialchars(implode('', $debugLines)) ?></pre>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>Últimas 30 líneas — gmail_webhook.log</h2>
        <?php if (empty($logLines)): ?>
            <pre class="err">(vacío o no legible)</pre>
        <?php else: ?>
            <pre><?= htmlspecialchars(implode('', $logLines)) ?></pre>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>Simular push (POST de prueba)</h2>
        <p>Equivale a un aviso de Pub/Sub. POST <strong>sin</strong> barra final para evitar redirecciones que vacían el cuerpo. Si falla, usa el segundo botón (<code>index.php</code>).</p>
        <button type="button" id="btnPush">Enviar push de prueba</button>
        <button type="button" id="btnPushIndex">POST a index.php</button>
        <pre id="result"></pre>
    </div>

    <script>
        var payload = { message: { data: '<?= $testPayloadB64 ?>' } };
        function postTo(url) {
            var result = document.getElementById('result');
            result.textContent = 'Enviando a ' + url + ' ...';
            return fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                redirect: 'manual',
                body: JSON.stringify(payload)
            }).then(function(r) {
                return r.text().then(function(t) {
                    return { status: r.status, body: t, url: r.url };
                });
            }).then(function(o) {
                var extra = (o.status === 301 || o.status === 302 || o.status === 307 || o.status === 308)
                    ? '\n\nATENCIÓN: Redirección ' + o.status + '. El POST puede haberse perdido; en el servidor usa la regla sin barra final o /index.php.'
                    : '';
                result.textContent = 'URL final: ' + o.url + '\nStatus: ' + o.status + '\nBody: ' + o.body + extra + '\n\nRecarga la página para ver gmail_webhook_debug.log.';
            }).catch(function(e) {
                result.textContent = 'Error: ' + e.message;
            });
        }
        document.getElementById('btnPush').onclick = function() {
            var path = window.location.pathname.replace(/\/debug\.php$/i, '') || '/gmail/push';
            path = path.replace(/\/+$/, '');
            postTo(window.location.origin + path);
        };
        document.getElementById('btnPushIndex').onclick = function() {
            var path = window.location.pathname.replace(/\/debug\.php$/i, '') || '/gmail/push';
            path = path.replace(/\/+$/, '') + '/index.php';
            postTo(window.location.origin + path);
        };
    </script>
</body>
</html>

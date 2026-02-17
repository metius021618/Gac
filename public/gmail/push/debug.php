<?php
/**
 * Página de diagnóstico del webhook Gmail (solo para pruebas).
 * Abre en el navegador: https://app.pocoyoni.com/gmail/push/debug.php
 * Eliminar o restringir en producción.
 */
header('Content-Type: text/html; charset=utf-8');

$__DIR__ = __DIR__;
$basePath2 = dirname(__DIR__, 2);
$basePath3 = dirname(__DIR__, 3);
$basePath = $basePath3; // el que usa index.php
$logsDir = $basePath . DIRECTORY_SEPARATOR . 'logs';
$logFile = $logsDir . DIRECTORY_SEPARATOR . 'gmail_webhook.log';
$workerScript = $basePath . DIRECTORY_SEPARATOR . 'cron' . DIRECTORY_SEPARATOR . 'process_gmail_history.py';

$logsDirExists = is_dir($logsDir);
$logsDirWritable = $logsDirExists && is_writable($logsDir);
$logFileExists = file_exists($logFile);
$logFileWritable = $logFileExists && is_writable($logFile);
$workerExists = is_file($workerScript);

// Intentar escribir una línea de prueba
$testWriteOk = false;
if ($logsDirExists) {
    $testLine = date('Y-m-d H:i:s') . " [DEBUG] prueba de escritura desde debug.php\n";
    $testWriteOk = @file_put_contents($logFile, $testLine, FILE_APPEND | LOCK_EX) !== false;
}

// Últimas líneas del log
$logLines = [];
if ($logFileExists && is_readable($logFile)) {
    $lines = file($logFile);
    $logLines = array_slice($lines, -30);
}

$documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '(no definido)';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico Webhook Gmail</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #1e1e1e; color: #d4d4d4; }
        h1 { color: #4ec9b0; }
        pre { background: #252526; padding: 12px; overflow-x: auto; }
        .ok { color: #4ec9b0; }
        .err { color: #f48771; }
        .section { margin: 16px 0; }
        a { color: #569cd6; }
        button { padding: 8px 16px; cursor: pointer; background: #0e639c; color: #fff; border: none; border-radius: 4px; }
        button:hover { background: #1177bb; }
    </style>
</head>
<body>
    <h1>Diagnóstico Webhook Gmail (Pub/Sub push)</h1>
    <p>URL del webhook: <strong><?= htmlspecialchars('https://' . ($_SERVER['HTTP_HOST'] ?? '') . '/gmail/push') ?></strong></p>

    <div class="section">
        <h2>Rutas (este script)</h2>
        <pre>__FILE__       = <?= htmlspecialchars(__FILE__) ?>
__DIR__        = <?= htmlspecialchars($__DIR__) ?>
DOCUMENT_ROOT  = <?= htmlspecialchars($documentRoot) ?>

dirname(__DIR__, 2) = <?= htmlspecialchars($basePath2) ?>
dirname(__DIR__, 3) = <?= htmlspecialchars($basePath3) ?>  ← basePath que usa index.php</pre>
    </div>

    <div class="section">
        <h2>Paths que usa el webhook</h2>
        <pre>basePath     = <?= htmlspecialchars($basePath) ?>
logs dir    = <?= htmlspecialchars($logsDir) ?>
log file   = <?= htmlspecialchars($logFile) ?>
worker      = <?= htmlspecialchars($workerScript) ?></pre>
    </div>

    <div class="section">
        <h2>Comprobaciones</h2>
        <pre>logs/ existe          : <?= $logsDirExists ? '<span class="ok">Sí</span>' : '<span class="err">No</span>' ?>

logs/ escribible     : <?= $logsDirWritable ? '<span class="ok">Sí</span>' : '<span class="err">No</span>' ?>

gmail_webhook.log    : <?= $logFileExists ? '<span class="ok">Existe</span>' : '<span class="err">No existe</span>' ?>

gmail_webhook.log    : <?= $logFileWritable ? '<span class="ok">Escribible</span>' : '<span class="err">No escribible</span>' ?>

process_gmail_history: <?= $workerExists ? '<span class="ok">Existe</span>' : '<span class="err">No existe</span>' ?>

Escritura de prueba  : <?= $testWriteOk ? '<span class="ok">OK</span>' : '<span class="err">Falló</span>' ?></pre>
    </div>

    <div class="section">
        <h2>Últimas 30 líneas de gmail_webhook.log</h2>
        <?php if (empty($logLines)): ?>
            <pre class="err">(vacío o no legible)</pre>
        <?php else: ?>
            <pre><?= htmlspecialchars(implode('', $logLines)) ?></pre>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>Simular push (POST de prueba)</h2>
        <p>Envía un POST como haría Pub/Sub para ver si se escribe en el log.</p>
        <button type="button" id="btnPush">Enviar push de prueba</button>
        <pre id="result"></pre>
    </div>

    <script>
        document.getElementById('btnPush').onclick = function() {
            var result = document.getElementById('result');
            result.textContent = 'Enviando...';
            var pushUrl = (window.location.pathname.replace(/\/debug\.php$/i, '') || '/gmail/push') + '/';
            fetch(pushUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: { data: 'eyJoaXN0b3J5SWQiOiI5OTk5In0=' } })
            }).then(function(r) {
                return r.text().then(function(t) { return { status: r.status, body: t }; });
            }).then(function(o) {
                result.textContent = 'Status: ' + o.status + '\nBody: ' + o.body + '\n\nRecarga la página para ver si apareció una línea en el log.';
            }).catch(function(e) {
                result.textContent = 'Error: ' + e.message;
            });
        };
    </script>
</body>
</html>

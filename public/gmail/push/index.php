<?php
/**
 * GAC - Endpoint webhook para Gmail Pub/Sub push
 * Recibe el POST de Google Cloud Pub/Sub, extrae historyId y ejecuta el worker Python en segundo plano.
 * URL: https://app.pocoyoni.com/gmail/push (o /gmail/push/)
 */

http_response_code(200);
header('Content-Type: text/plain; charset=utf-8');

// Solo aceptar POST (Pub/Sub envía POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo 'ok';
    exit;
}

$rawInput = file_get_contents('php://input');
$historyId = null;

if ($rawInput !== false && $rawInput !== '') {
    $body = json_decode($rawInput, true);
    if (is_array($body)) {
        $message = $body['message'] ?? [];
        $dataB64 = $message['data'] ?? null;
        if ($dataB64 !== null && $dataB64 !== '') {
            $padding = 4 - strlen($dataB64) % 4;
            if ($padding !== 4) {
                $dataB64 .= str_repeat('=', $padding);
            }
            $decoded = base64_decode(strtr($dataB64, '-_', '+/'), true);
            if ($decoded !== false) {
                $payload = json_decode($decoded, true);
                if (is_array($payload)) {
                    $historyId = $payload['historyId'] ?? $payload['history_id'] ?? null;
                    if ($historyId !== null) {
                        $historyId = trim((string) $historyId);
                    }
                }
            }
        }
    }
}

// Raíz del proyecto (public/gmail/push/index.php -> dos niveles arriba)
$basePath = dirname(__DIR__, 2);
$python3 = 'python3';
$workerScript = $basePath . DIRECTORY_SEPARATOR . 'cron' . DIRECTORY_SEPARATOR . 'process_gmail_history.py';

if ($historyId !== null && $historyId !== '' && is_file($workerScript)) {
    $historyIdEscaped = escapeshellarg($historyId);
    $cmd = sprintf(
        'cd %s && %s cron%sprocess_gmail_history.py --history-id %s > /dev/null 2>&1 &',
        escapeshellarg($basePath),
        $python3,
        DIRECTORY_SEPARATOR,
        $historyIdEscaped
    );
    exec($cmd);
}

echo 'ok';

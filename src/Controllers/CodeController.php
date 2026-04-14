<?php
/**
 * GAC - Controlador de Códigos
 * 
 * @package Gac\Controllers
 */

namespace Gac\Controllers;

use Gac\Core\Request;
use Gac\Services\Code\CodeService;

class CodeController
{
    /**
     * Servicio de códigos
     */
    private CodeService $codeService;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->codeService = new CodeService();
    }

    /**
     * Mostrar página de consulta de códigos
     */
    public function consult(Request $request): void
    {
        // Obtener plataformas disponibles desde BD
        $platforms = $this->codeService->getEnabledPlatforms();

        // Si es POST, procesar consulta
        if ($request->method() === 'POST') {
            $this->processConsult($request);
            return;
        }

        // Mostrar vista de consulta
        $this->renderView('codes/consult', [
            'platforms' => $platforms,
            'title' => 'Consulta tu Código'
        ]);
    }

    /**
     * Procesar consulta de código
     */
    private function processConsult(Request $request): void
    {
        $email = $request->input('email', '');
        $username = $request->input('username', '');
        $platform = $request->input('platform', '');

        // Validación básica
        if (empty($email) || empty($username) || empty($platform)) {
            json_response([
                'success' => false,
                'message' => 'Por favor completa todos los campos'
            ], 400);
            return;
        }

        // Consultar código usando el servicio
        $result = $this->codeService->consultCode($platform, $email, $username);

        // Determinar código HTTP
        $httpCode = $result['success'] ? 200 : 404;
        
        json_response($result, $httpCode);
    }

    /**
     * API endpoint para consulta AJAX
     */
    public function apiConsult(Request $request): void
    {
        // Solo aceptar POST
        if ($request->method() !== 'POST') {
            json_response([
                'success' => false,
                'message' => 'Método no permitido'
            ], 405);
            return;
        }

        // Obtener datos del JSON body
        $email = $request->input('email', '');
        $username = $request->input('username', '');
        $platform = $request->input('platform', '');

        // Validación básica
        if (empty($email) || empty($username) || empty($platform)) {
            json_response([
                'success' => false,
                'message' => 'Por favor completa todos los campos'
            ], 400);
            return;
        }

        // Consultar código usando el servicio
        $result = $this->codeService->consultCode($platform, $email, $username);

        // Determinar código HTTP
        $httpCode = $result['success'] ? 200 : 404;

        // Evitar caché: cada consulta debe traer el código más reciente de la BD
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        json_response($result, $httpCode);
    }

    /**
     * Consultar código ejecutando antes el lector de correos (síncrono, ~30-60 s).
     * Así el usuario ve el correo más reciente sin tener que refrescar.
     */
    public function apiConsultWithSync(Request $request): void
    {
        if ($request->method() !== 'POST') {
            json_response(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        $email = $request->input('email', '');
        $username = $request->input('username', '');
        $platform = $request->input('platform', '');
        if (empty($email) || empty($username) || empty($platform)) {
            json_response(['success' => false, 'message' => 'Por favor completa todos los campos'], 400);
            return;
        }
        $logDir = base_path('logs');
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $consultLog = $logDir . DIRECTORY_SEPARATOR . 'consult_debug.log';
        $emailLower = strtolower(trim($email));
        $scriptGmail = (substr($emailLower, -11) === '@gmail.com');
        $scriptOutlook = (substr($emailLower, -12) === '@outlook.com' || substr($emailLower, -11) === '@hotmail.com' || substr($emailLower, -9) === '@live.com');
        if ($scriptGmail) {
            $script = 'cron/email_reader_gmail.py';
        } elseif ($scriptOutlook) {
            $script = 'cron/email_reader_outlook.py';
        } else {
            $script = 'cron/email_reader.py';
        }
        @file_put_contents($consultLog, date('Y-m-d H:i:s') . " [REQUEST] correo_recibido=" . $email . " username=" . $username . " platform=" . $platform . " → script_ejecutado=" . $script . "\n", FILE_APPEND | LOCK_EX);
        @set_time_limit(90);
        $root = base_path();
        $cmd = (DIRECTORY_SEPARATOR === '\\')
            ? 'cd /d ' . escapeshellarg($root) . ' && python ' . $script . ' 2>&1'
            : sprintf('cd %s && /bin/python3 %s 2>&1', escapeshellarg($root), $script);
        @exec($cmd);
        $result = $this->codeService->consultCode($platform, $email, $username);
        $httpCode = $result['success'] ? 200 : 404;
        json_response($result, $httpCode);
    }

    /**
     * Disparar el lector de correos en segundo plano (throttle 90 s).
     * Se llama al cargar la vista de consulta para sincronizar correos recientes.
     */
    public function triggerEmailSync(Request $request): void
    {
        $throttleSeconds = 90;
        $lockFile = base_path('logs' . DIRECTORY_SEPARATOR . 'last_sync_trigger.txt');
        $logDir = base_path('logs');
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $triggered = false;
        $now = time();
        if (file_exists($lockFile)) {
            $last = (int) trim((string) @file_get_contents($lockFile));
            if ($last > 0 && ($now - $last) < $throttleSeconds) {
                json_response(['triggered' => false, 'reason' => 'throttle']);
                return;
            }
        }
        @file_put_contents($lockFile, (string) $now);
        $root = base_path();
        $logFile = $root . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'cron.log';
        if (DIRECTORY_SEPARATOR === '\\') {
            @exec('start /B cd /d ' . escapeshellarg($root) . ' && python cron/email_reader.py >> ' . escapeshellarg($logFile) . ' 2>&1');
            @exec('start /B cd /d ' . escapeshellarg($root) . ' && python cron/email_reader_gmail.py >> ' . escapeshellarg($logFile) . ' 2>&1');
            @exec('start /B cd /d ' . escapeshellarg($root) . ' && python cron/email_reader_outlook.py >> ' . escapeshellarg($logFile) . ' 2>&1');
        } else {
            @exec(sprintf('cd %s && /bin/python3 cron/email_reader.py >> %s 2>&1 &', escapeshellarg($root), escapeshellarg($logFile)));
            @exec(sprintf('cd %s && /bin/python3 cron/email_reader_gmail.py >> %s 2>&1 &', escapeshellarg($root), escapeshellarg($logFile)));
            @exec(sprintf('cd %s && /bin/python3 cron/email_reader_outlook.py >> %s 2>&1 &', escapeshellarg($root), escapeshellarg($logFile)));
        }
        $triggered = true;
        json_response(['triggered' => $triggered]);
    }

    /**
     * Estado del lector continuo: sync_loop, imap_loop y/o gmail_loop (PIDs en logs/).
     */
    public function readerLoopStatus(Request $request): void
    {
        // Liberar candado de sesión: si otro request (p. ej. startReaderLoop) sigue abierto, sin esto el sitio "cuelga" en nuevas pestañas.
        if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        $sync = $this->isPidFileProcessRunning('logs' . DIRECTORY_SEPARATOR . 'reader_loop.pid');
        $imap = $this->isPidFileProcessRunning('logs' . DIRECTORY_SEPARATOR . 'imap_loop.pid');
        $gmailLoop = $this->isPidFileProcessRunning('logs' . DIRECTORY_SEPARATOR . 'gmail_loop.pid');
        $loops = [];
        if ($sync) {
            $loops[] = 'sync_loop';
        }
        if ($imap) {
            $loops[] = 'imap_loop';
        }
        if ($gmailLoop) {
            $loops[] = 'gmail_loop';
        }
        $running = $loops !== [];
        $mode = 'none';
        if ($running) {
            if (count($loops) === 1) {
                $mode = $loops[0];
            } elseif ($sync && $imap && count($loops) === 2) {
                $mode = 'both';
            } else {
                $mode = 'multi';
            }
        }
        json_response(['running' => $running, 'mode' => $mode, 'loops' => $loops]);
    }

    /**
     * Iniciar el lector continuo en segundo plano (sync_loop.py).
     * Ejecuta Gmail, Outlook e IMAP cada X segundos sin usar cron.
     */
    public function startReaderLoop(Request $request): void
    {
        if ($request->method() !== 'POST') {
            json_response(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        if (!$this->isPhpExecAvailable()) {
            json_response([
                'success' => false,
                'message' => 'Este hosting no permite lanzar procesos desde PHP (función exec deshabilitada). Inicia el lector por SSH o cron: cd /ruta/al/proyecto && bash cron/ensure_reader_loop.sh — o añade ensure_reader_loop.sh al crontab cada 2 minutos.',
            ]);
            return;
        }
        @set_time_limit(15);
        $logDir = base_path('logs');
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . DIRECTORY_SEPARATOR . 'sync_loop.log';
        $root = base_path();
        $script = 'cron' . DIRECTORY_SEPARATOR . 'sync_loop.py';
        if (!file_exists($root . DIRECTORY_SEPARATOR . $script)) {
            json_response(['success' => false, 'message' => 'No se encontró sync_loop.py']);
            return;
        }
        if ($this->isPidFileProcessRunning('logs' . DIRECTORY_SEPARATOR . 'imap_loop.pid')) {
            json_response([
                'success' => false,
                'message' => 'El bucle IMAP (imap_loop.py) ya está en ejecución. Detén ese proceso antes de iniciar sync_loop, o usa solo uno.',
                'running' => true,
            ]);
            return;
        }
        if ($this->isPidFileProcessRunning('logs' . DIRECTORY_SEPARATOR . 'gmail_loop.pid')) {
            json_response([
                'success' => false,
                'message' => 'El bucle Gmail (gmail_loop.py) ya está en ejecución. Detén ese proceso antes de iniciar sync_loop, o usa solo uno.',
                'running' => true,
            ]);
            return;
        }
        if ($this->isPidFileProcessRunning('logs' . DIRECTORY_SEPARATOR . 'reader_loop.pid')) {
            json_response(['success' => false, 'message' => 'El lector continuo ya está en ejecución.', 'running' => true]);
            return;
        }
        if (DIRECTORY_SEPARATOR === '\\') {
            @exec('start /B cd /d ' . escapeshellarg($root) . ' && python ' . $script . ' >> ' . escapeshellarg($logFile) . ' 2>&1');
        } else {
            // Subshell ( ... ) para que exec retorne enseguida sin esperar al proceso en background
            @exec(sprintf('(cd %s && nohup python3 %s >> %s 2>&1 &)', escapeshellarg($root), escapeshellarg($script), escapeshellarg($logFile)));
        }
        json_response(['success' => true, 'message' => 'Lector continuo iniciado. Ejecutará los lectores cada pocos segundos.']);
    }

    /**
     * Comprueba si el PID guardado en logs/*.pid sigue vivo (Unix: kill -0; Windows: tasklist).
     */
    private function isPidFileProcessRunning(string $pidRelativePath): bool
    {
        $path = base_path(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $pidRelativePath));
        if (!file_exists($path)) {
            return false;
        }
        $pid = (int) trim((string) @file_get_contents($path));
        if ($pid <= 0) {
            return false;
        }
        if (DIRECTORY_SEPARATOR === '\\') {
            @exec('tasklist /FI "PID eq ' . $pid . '" 2>NUL', $out);

            return !empty($out) && count($out) > 1 && stripos(implode(' ', $out), (string) $pid) !== false;
        }
        @exec('kill -0 ' . $pid . ' 2>/dev/null', $_, $code);

        return ($code === 0);
    }

    /**
     * Comprobar si PHP puede usar exec() (mucho hosting compartido lo deshabilita, p. ej. SiteGround).
     */
    private function isPhpExecAvailable(): bool
    {
        if (!function_exists('exec')) {
            return false;
        }
        $disabled = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));
        return !in_array('exec', $disabled, true);
    }

    /**
     * Renderizar vista
     */
    private function renderView(string $view, array $data = []): void
    {
        extract($data);
        $viewPath = base_path('views/' . str_replace('.', '/', $view) . '.php');
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            http_response_code(404);
            echo "Vista no encontrada: {$view}";
        }
    }
}

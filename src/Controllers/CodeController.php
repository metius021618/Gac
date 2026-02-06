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

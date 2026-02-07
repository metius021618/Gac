<?php
/**
 * GAC - Servicio de Códigos
 * 
 * Lógica de negocio para gestión de códigos
 * 
 * @package Gac\Services\Code
 */

namespace Gac\Services\Code;

use Gac\Repositories\CodeRepository;
use Gac\Repositories\PlatformRepository;
use Gac\Repositories\UserAccessRepository;
use Gac\Repositories\EmailAccountRepository;
use Gac\Repositories\SettingsRepository;

class CodeService
{
    private CodeRepository $codeRepository;
    private PlatformRepository $platformRepository;
    private UserAccessRepository $userAccessRepository;
    private EmailAccountRepository $emailAccountRepository;
    private SettingsRepository $settingsRepository;

    public function __construct()
    {
        $this->codeRepository = new CodeRepository();
        $this->platformRepository = new PlatformRepository();
        $this->userAccessRepository = new UserAccessRepository();
        $this->emailAccountRepository = new EmailAccountRepository();
        $this->settingsRepository = new SettingsRepository();
    }

    /**
     * Consultar código disponible para una plataforma
     * 
     * @param string $platformSlug Slug de la plataforma
     * @param string $userEmail Email del usuario que consulta
     * @param string $username Username del usuario
     * @return array Resultado de la consulta
     */
    public function consultCode(string $platformSlug, string $userEmail, string $username): array
    {
        // Validar email
        if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'El email ingresado no es válido'
            ];
        }

        // Validar username
        if (strlen(trim($username)) < 3) {
            return [
                'success' => false,
                'message' => 'El usuario debe tener al menos 3 caracteres'
            ];
        }

        // Obtener plataforma
        $platform = $this->platformRepository->findByName($platformSlug);
        
        if (!$platform) {
            return [
                'success' => false,
                'message' => 'Plataforma no encontrada'
            ];
        }

        // Verificar que la plataforma esté habilitada
        if (!$platform['enabled']) {
            return [
                'success' => false,
                'message' => 'Esta plataforma no está disponible actualmente'
            ];
        }

        // Acceso maestro: admin + clave maestra → ver último código leído por la cuenta maestra IMAP (no Gmail/Outlook)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $masterEnabled = $this->settingsRepository->getValue('master_consult_enabled', '0') === '1';
        $masterUsername = trim($this->settingsRepository->getValue('master_consult_username', ''));
        $isAdminLoggedIn = !empty($_SESSION['logged_in']);
        if ($masterEnabled && $masterUsername !== '' && $isAdminLoggedIn && trim($username) === $masterUsername) {
            $lastEmail = $this->codeRepository->findLastEmailForPlatformByOrigin($platform['id'], 'imap');
            if ($lastEmail) {
                return [
                    'success' => true,
                    'message' => 'Vista maestra (último código recibido en la cuenta maestra IMAP)',
                    'platform' => $platform['display_name'],
                    'received_at' => $lastEmail['received_at'],
                    'minutes_ago' => $lastEmail['minutes_ago'] ?? 0,
                    'time_ago_text' => $lastEmail['time_ago_text'] ?? 'hace tiempo',
                    'email_from' => $lastEmail['email_from'] ?? '',
                    'email_subject' => $lastEmail['subject'] ?? 'Sin asunto',
                    'email_body' => $lastEmail['email_body'] ?? '',
                    'is_master_view' => true,
                    'recipient_email' => $lastEmail['recipient_email'] ?? ''
                ];
            }
            return [
                'success' => false,
                'message' => 'No hay correos para esta plataforma en la cuenta maestra (IMAP).'
            ];
        }

        // Verificar que el usuario tenga acceso registrado para esta plataforma
        if (!$this->userAccessRepository->verifyAccess($userEmail, $username, $platform['id'])) {
            return [
                'success' => false,
                'message' => 'No tienes acceso registrado para esta plataforma. Contacta al administrador.'
            ];
        }

        // Si el correo es @gmail.com, buscar solo códigos leídos desde Gmail (no mezclar con IMAP)
        // Si el correo es @outlook.com/@hotmail.com/@live.com, buscar solo códigos leídos desde Outlook
        $userEmailLower = strtolower(trim($userEmail));
        $originFilter = null;
        if (substr($userEmailLower, -11) === '@gmail.com') {
            $originFilter = 'gmail';
        } elseif (substr($userEmailLower, -12) === '@outlook.com' || substr($userEmailLower, -11) === '@hotmail.com' || substr($userEmailLower, -9) === '@live.com') {
            $originFilter = 'outlook';
        }

        $logDir = defined('BASE_PATH') ? BASE_PATH . DIRECTORY_SEPARATOR . 'logs' : (__DIR__ . '/../../logs');
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $consultLogFile = $logDir . DIRECTORY_SEPARATOR . 'consult_debug.log';

        // Log: correo que recibimos en la consulta y correo/origen para el que buscamos
        @file_put_contents($consultLogFile, date('Y-m-d H:i:s') . " [CONSULT] correo_recibido=" . $userEmail . " → buscando_codigo_para_recipient=" . $userEmail . " origin_filtro=" . ($originFilter ?? 'cualquiera') . " platform=" . ($platform['display_name'] ?? $platformSlug) . "\n", FILE_APPEND | LOCK_EX);

        // Buscar el último correo para este usuario (recipient_email = userEmail)
        $lastEmail = $this->codeRepository->findLastEmail($platform['id'], $userEmail, $originFilter);

        // Si no hay correo con su email y NO es Gmail ni Outlook, mostrar el último que llegó a la cuenta maestra
        if (!$lastEmail && $originFilter !== 'gmail' && $originFilter !== 'outlook') {
            $master = $this->emailAccountRepository->findMasterAccount();
            if ($master && !empty($master['email'])) {
                @file_put_contents($consultLogFile, date('Y-m-d H:i:s') . " [CONSULT] FALLBACK: no encontrado para " . $userEmail . " → buscando con cuenta_maestra=" . $master['email'] . "\n", FILE_APPEND | LOCK_EX);
                $lastEmail = $this->codeRepository->findLastEmail($platform['id'], $master['email'], null);
            }
        }

        if ($lastEmail) {
            @file_put_contents($consultLogFile, date('Y-m-d H:i:s') . " [CONSULT] ENCONTRADO: code_id=" . ($lastEmail['id'] ?? '') . " origin=" . ($lastEmail['origin'] ?? '') . " recipient_email=" . ($lastEmail['recipient_email'] ?? '') . " received_at=" . ($lastEmail['received_at'] ?? '') . "\n", FILE_APPEND | LOCK_EX);
        } else {
            @file_put_contents($consultLogFile, date('Y-m-d H:i:s') . " [CONSULT] NO_ENCONTRADO: buscamos recipient=" . $userEmail . " origin_filtro=" . ($originFilter ?? 'cualquiera') . "\n", FILE_APPEND | LOCK_EX);
        }

        if (!$lastEmail) {
            return [
                'success' => false,
                'message' => 'No se encontraron correos para esta plataforma. Por favor intenta más tarde.'
            ];
        }

        // Calcular tiempo transcurrido
        $minutesAgo = $lastEmail['minutes_ago'] ?? 0;
        $timeAgoText = $lastEmail['time_ago_text'] ?? 'hace tiempo';

        // Retornar el correo completo (sin marcar como consumido, solo mostrar)
        $response = [
            'success' => true,
            'message' => 'Correo encontrado',
            'platform' => $platform['display_name'],
            'received_at' => $lastEmail['received_at'],
            'minutes_ago' => $minutesAgo,
            'time_ago_text' => $timeAgoText,
            'email_from' => $lastEmail['email_from'] ?? '',
            'email_subject' => $lastEmail['subject'] ?? 'Sin asunto',
            'email_body' => $lastEmail['email_body'] ?? ''  // Cuerpo completo del email
        ];
        
        return $response;
    }

    /**
     * Guardar código extraído
     * 
     * @param array $codeData Datos del código extraído
     * @param int $emailAccountId ID de la cuenta de email
     * @return int|null ID del código guardado o null si hay error
     */
    public function saveExtractedCode(array $codeData, int $emailAccountId): ?int
    {
        // Obtener plataforma
        $platform = $this->platformRepository->findByName($codeData['platform']);
        
        if (!$platform) {
            error_log("Plataforma no encontrada: {$codeData['platform']}");
            return null;
        }

        // Verificar si el código ya existe
        if ($this->codeRepository->codeExists($codeData['code'], $platform['id'], $emailAccountId)) {
            error_log("Código duplicado: {$codeData['code']} para plataforma {$codeData['platform']}");
            return null;
        }

        // Preparar datos para guardar
        $data = [
            'email_account_id' => $emailAccountId,
            'platform_id' => $platform['id'],
            'code' => $codeData['code'],
            'email_from' => $codeData['from'] ?? null,
            'subject' => $codeData['subject'] ?? null,
            'received_at' => $codeData['date'] ?? date('Y-m-d H:i:s'),
            'origin' => $codeData['origin'] ?? 'imap'
        ];

        // Guardar código
        $codeId = $this->codeRepository->save($data);

        if ($codeId) {
            // Guardar también en warehouse - Deshabilitado temporalmente
            // $data['id'] = $codeId;
            // $this->codeRepository->saveToWarehouse($data);
        }

        return $codeId;
    }

    /**
     * Obtener estadísticas de códigos
     * 
     * @return array
     */
    public function getStats(): array
    {
        return $this->codeRepository->getStats();
    }

    /**
     * Obtener todas las plataformas habilitadas
     * 
     * @return array
     */
    public function getEnabledPlatforms(): array
    {
        $platforms = $this->platformRepository->findAllEnabled();
        
        $result = [];
        foreach ($platforms as $platform) {
            $result[$platform['name']] = $platform['display_name'];
        }
        
        return $result;
    }
}
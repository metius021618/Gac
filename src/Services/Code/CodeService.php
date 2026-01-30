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

class CodeService
{
    private CodeRepository $codeRepository;
    private PlatformRepository $platformRepository;
    private UserAccessRepository $userAccessRepository;
    private EmailAccountRepository $emailAccountRepository;

    public function __construct()
    {
        $this->codeRepository = new CodeRepository();
        $this->platformRepository = new PlatformRepository();
        $this->userAccessRepository = new UserAccessRepository();
        $this->emailAccountRepository = new EmailAccountRepository();
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

        // Verificar que el usuario tenga acceso registrado para esta plataforma
        if (!$this->userAccessRepository->verifyAccess($userEmail, $username, $platform['id'])) {
            return [
                'success' => false,
                'message' => 'No tienes acceso registrado para esta plataforma. Contacta al administrador.'
            ];
        }

        // Buscar el último correo para este usuario (recipient_email = userEmail)
        $lastEmail = $this->codeRepository->findLastEmail($platform['id'], $userEmail);

        // Si no hay correo con su email, mostrar el último que llegó a la cuenta maestra
        // (por si el servidor no guardó Delivered-To y se guardó como streaming@)
        if (!$lastEmail) {
            $master = $this->emailAccountRepository->findMasterAccount();
            if ($master && !empty($master['email'])) {
                $lastEmail = $this->codeRepository->findLastEmail($platform['id'], $master['email']);
            }
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
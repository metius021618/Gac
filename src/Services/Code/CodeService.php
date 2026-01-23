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

class CodeService
{
    /**
     * Repositorio de códigos
     */
    private CodeRepository $codeRepository;

    /**
     * Repositorio de plataformas
     */
    private PlatformRepository $platformRepository;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->codeRepository = new CodeRepository();
        $this->platformRepository = new PlatformRepository();
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

        // Buscar código disponible para este email específico
        // El sistema filtra por el email del destinatario del correo
        // Primero busca códigos recientes (últimos 5 minutos), si no hay, busca el último disponible
        $code = $this->codeRepository->findLatestAvailable($platform['id'], $userEmail, 5);

        if (!$code) {
            // Si no hay códigos disponibles, buscar el último código consumido para mostrar información
            $lastConsumed = $this->codeRepository->findLastConsumed($platform['id'], $userEmail);
            
            return [
                'success' => false,
                'message' => 'No hay códigos disponibles para esta plataforma en este momento. Por favor intenta más tarde.',
                'code' => null,
                'last_consumed' => $lastConsumed ? [
                    'code' => $lastConsumed['code'],
                    'time_ago_text' => $lastConsumed['time_ago_text'] ?? 'hace tiempo',
                    'received_at' => $lastConsumed['received_at']
                ] : null
            ];
        }

        // Verificar si el código es reciente
        $isRecent = isset($code['is_recent']) && $code['is_recent'] == 1;
        $minutesAgo = $code['minutes_ago'] ?? null;

        // Marcar como consumido
        $marked = $this->codeRepository->markAsConsumed(
            $code['id'],
            $userEmail,
            $username
        );

        if (!$marked) {
            return [
                'success' => false,
                'message' => 'Error al procesar el código. Por favor intenta nuevamente.'
            ];
        }

        // Guardar en warehouse (histórico) - Deshabilitado temporalmente
        // $code['consumed_at'] = date('Y-m-d H:i:s');
        // $this->codeRepository->saveToWarehouse($code);

        // Preparar mensaje según si es reciente o no
        $message = 'Código encontrado';
        $warningMessage = null;
        
        if ($isRecent) {
            // Código reciente (últimos 5 minutos)
            $warningMessage = "✓ Código reciente recibido en los últimos 5 minutos.";
        } else {
            // Código no es reciente, mostrar advertencia amigable
            if ($minutesAgo !== null) {
                if ($minutesAgo < 60) {
                    $warningMessage = "⚠ Este código fue recibido hace {$minutesAgo} minutos. Si prefieres esperar un código más reciente, intenta en unos minutos.";
                } elseif ($minutesAgo < 1440) { // Menos de 24 horas
                    $hoursAgo = floor($minutesAgo / 60);
                    $warningMessage = "⚠ Este código fue recibido hace {$hoursAgo} hora(s). Aún es válido, pero si prefieres uno más reciente, intenta más tarde.";
                } else {
                    $daysAgo = floor($minutesAgo / 1440);
                    $warningMessage = "⚠ Este código fue recibido hace {$daysAgo} día(s). Aún es válido, pero te recomendamos esperar un código más reciente.";
                }
            } else {
                $warningMessage = "⚠ No se recibieron códigos nuevos en los últimos 5 minutos. Este es el último código disponible para tu cuenta.";
            }
        }

        // Retornar código
        $response = [
            'success' => true,
            'message' => $message,
            'code' => $code['code'],
            'platform' => $platform['display_name'],
            'received_at' => $code['received_at'],
            'is_recent' => $isRecent,
            'minutes_ago' => $minutesAgo
        ];
        
        // Agregar mensaje de advertencia (siempre mostrar si es reciente o no)
        if (isset($warningMessage)) {
            $response['warning'] = $warningMessage;
            $response['is_recent_message'] = $isRecent;
        }
        
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
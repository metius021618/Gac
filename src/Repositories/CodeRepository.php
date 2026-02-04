<?php
/**
 * GAC - Repositorio de Códigos
 * 
 * Maneja el acceso a datos de códigos
 * 
 * @package Gac\Repositories
 */

namespace Gac\Repositories;

use Gac\Helpers\Database;
use PDO;
use PDOException;

class CodeRepository
{
    /**
     * Guardar un código nuevo
     * 
     * @param array $codeData Datos del código
     * @return int|null ID del código guardado o null si hay error
     */
    public function save(array $codeData): ?int
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO codes (
                    email_account_id,
                    platform_id,
                    code,
                    email_from,
                    subject,
                    email_body,
                    received_at,
                    origin,
                    status,
                    recipient_email
                ) VALUES (
                    :email_account_id,
                    :platform_id,
                    :code,
                    :email_from,
                    :subject,
                    :email_body,
                    :received_at,
                    :origin,
                    'available',
                    :recipient_email
                )
            ");
            
            $stmt->execute([
                'email_account_id' => $codeData['email_account_id'],
                'platform_id' => $codeData['platform_id'],
                'code' => $codeData['code'],
                'email_from' => $codeData['email_from'] ?? null,
                'subject' => $codeData['subject'] ?? null,
                'email_body' => $codeData['email_body'] ?? null,  // Cuerpo del email
                'received_at' => $codeData['received_at'] ?? date('Y-m-d H:i:s'),
                'origin' => $codeData['origin'] ?? 'imap',
                'recipient_email' => $codeData['recipient_email'] ?? null
            ]);
            
            return (int) $db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error al guardar código: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Buscar el código más reciente disponible para una plataforma
     * 
     * @param int $platformId ID de la plataforma
     * @param string|null $recipientEmail Email del destinatario (opcional, para filtrar)
     * @param int $recentMinutes Minutos para considerar "reciente" (default: 5)
     * @return array|null Datos del código con flag 'is_recent' o null si no hay disponible
     */
    public function findLatestAvailable(int $platformId, ?string $recipientEmail = null, int $recentMinutes = 5): ?array
    {
        try {
            $db = Database::getConnection();
            
            // Construir condición WHERE base
            $whereConditions = [
                'c.platform_id = :platform_id',
                'c.status = \'available\''
            ];
            $params = ['platform_id' => $platformId];
            
            // Si se proporciona recipient_email, filtrar por ese email
            if ($recipientEmail) {
                $whereConditions[] = 'c.recipient_email = :recipient_email';
                $params['recipient_email'] = strtolower($recipientEmail);
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Primero buscar códigos recientes (últimos N minutos)
            $stmt = $db->prepare("
                SELECT 
                    c.id,
                    c.email_account_id,
                    c.platform_id,
                    c.code,
                    c.email_from,
                    c.subject,
                    c.email_body,
                    c.received_at,
                    c.origin,
                    c.status,
                    c.created_at,
                    c.recipient_email,
                    p.name as platform_name,
                    p.display_name as platform_display_name,
                    ea.email as account_email,
                    1 as is_recent
                FROM codes c
                INNER JOIN platforms p ON c.platform_id = p.id
                INNER JOIN email_accounts ea ON c.email_account_id = ea.id
                WHERE {$whereClause}
                  AND c.received_at >= DATE_SUB(NOW(), INTERVAL :recent_minutes MINUTE)
                ORDER BY c.received_at DESC, c.id DESC
                LIMIT 1
            ");
            
            $params['recent_minutes'] = $recentMinutes;
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Si encontramos un código reciente, retornarlo
            if ($result) {
                return $result;
            }
            
            // Si no hay código reciente, buscar el último disponible (sin restricción de tiempo)
            $stmt = $db->prepare("
                SELECT 
                    c.id,
                    c.email_account_id,
                    c.platform_id,
                    c.code,
                    c.email_from,
                    c.subject,
                    c.email_body,
                    c.received_at,
                    c.origin,
                    c.status,
                    c.created_at,
                    c.recipient_email,
                    p.name as platform_name,
                    p.display_name as platform_display_name,
                    ea.email as account_email,
                    0 as is_recent,
                    TIMESTAMPDIFF(MINUTE, c.received_at, NOW()) as minutes_ago
                FROM codes c
                INNER JOIN platforms p ON c.platform_id = p.id
                INNER JOIN email_accounts ea ON c.email_account_id = ea.id
                WHERE {$whereClause}
                ORDER BY c.received_at DESC, c.id DESC
                LIMIT 1
            ");
            
            // Remover el parámetro recent_minutes que ya no se usa
            unset($params['recent_minutes']);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // Agregar flag is_recent = false
                $result['is_recent'] = 0;
                return $result;
            }
            
            return null;
        } catch (PDOException $e) {
            error_log("Error al buscar código disponible: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Buscar el último correo recibido (sin importar estado) para un email y plataforma
     *
     * @param int $platformId ID de la plataforma
     * @param string $recipientEmail Email del destinatario
     * @param string|null $origin Solo códigos de este origen: 'gmail', 'imap' o null (cualquiera)
     * @return array|null Datos del último correo con tiempo transcurrido
     */
    public function findLastEmail(int $platformId, string $recipientEmail, ?string $origin = null): ?array
    {
        try {
            $db = Database::getConnection();
            $originClause = '';
            $params = [
                'platform_id' => $platformId,
                'recipient_email' => strtolower($recipientEmail)
            ];
            if ($origin === 'gmail' || $origin === 'imap') {
                $originClause = ' AND c.origin = :origin';
                $params['origin'] = $origin;
            }
            $stmt = $db->prepare("
                SELECT 
                    c.id,
                    c.code,
                    c.email_from,
                    c.subject,
                    c.email_body,
                    c.received_at,
                    c.status,
                    TIMESTAMPDIFF(MINUTE, c.received_at, NOW()) as minutes_ago
                FROM codes c
                WHERE c.platform_id = :platform_id
                  AND c.recipient_email = :recipient_email
                  {$originClause}
                ORDER BY c.received_at DESC, c.id DESC
                LIMIT 1
            ");
            
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $minutesAgo = $result['minutes_ago'];
                if ($minutesAgo < 60) {
                    $result['time_ago_text'] = "hace {$minutesAgo} minuto(s)";
                } elseif ($minutesAgo < 1440) { // Less than 24 hours
                    $hoursAgo = floor($minutesAgo / 60);
                    $result['time_ago_text'] = "hace {$hoursAgo} hora(s)";
                } else {
                    $daysAgo = floor($minutesAgo / 1440);
                    $result['time_ago_text'] = "hace {$daysAgo} día(s)";
                }
            }
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error al buscar último correo: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Último correo de una plataforma (cualquier destinatario). Para acceso maestro admin.
     *
     * @param int $platformId ID de la plataforma
     * @return array|null Datos del último correo con time_ago_text y recipient_email
     */
    public function findLastEmailForPlatform(int $platformId): ?array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT 
                    c.id,
                    c.code,
                    c.email_from,
                    c.subject,
                    c.email_body,
                    c.received_at,
                    c.status,
                    c.recipient_email,
                    TIMESTAMPDIFF(MINUTE, c.received_at, NOW()) as minutes_ago
                FROM codes c
                WHERE c.platform_id = :platform_id
                ORDER BY c.received_at DESC, c.id DESC
                LIMIT 1
            ");
            $stmt->execute(['platform_id' => $platformId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$result) {
                return null;
            }
            $minutesAgo = (int) $result['minutes_ago'];
            if ($minutesAgo < 60) {
                $result['time_ago_text'] = "hace {$minutesAgo} minuto(s)";
            } elseif ($minutesAgo < 1440) {
                $hoursAgo = floor($minutesAgo / 60);
                $result['time_ago_text'] = "hace {$hoursAgo} hora(s)";
            } else {
                $daysAgo = floor($minutesAgo / 1440);
                $result['time_ago_text'] = "hace {$daysAgo} día(s)";
            }
            return $result;
        } catch (PDOException $e) {
            error_log("Error al buscar último correo por plataforma: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Buscar el último código consumido para un email y plataforma
     * 
     * @param int $platformId ID de la plataforma
     * @param string $recipientEmail Email del destinatario
     * @return array|null Datos del código consumido con tiempo transcurrido
     */
    public function findLastConsumed(int $platformId, string $recipientEmail): ?array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT 
                    c.id,
                    c.email_account_id,
                    c.platform_id,
                    c.code,
                    c.email_from,
                    c.subject,
                    c.received_at,
                    c.consumed_at,
                    c.origin,
                    c.status,
                    c.created_at,
                    c.recipient_email,
                    p.name as platform_name,
                    p.display_name as platform_display_name,
                    ea.email as account_email,
                    TIMESTAMPDIFF(MINUTE, c.received_at, NOW()) as minutes_ago
                FROM codes c
                INNER JOIN platforms p ON c.platform_id = p.id
                INNER JOIN email_accounts ea ON c.email_account_id = ea.id
                WHERE c.platform_id = :platform_id
                  AND c.recipient_email = :recipient_email
                  AND c.status = 'consumed'
                ORDER BY c.received_at DESC, c.id DESC
                LIMIT 1
            ");
            
            $stmt->execute([
                'platform_id' => $platformId,
                'recipient_email' => strtolower($recipientEmail)
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // Calcular tiempo transcurrido en formato legible
                $minutesAgo = (int) ($result['minutes_ago'] ?? 0);
                if ($minutesAgo < 60) {
                    $result['time_ago_text'] = "hace {$minutesAgo} min";
                } elseif ($minutesAgo < 1440) {
                    $hoursAgo = floor($minutesAgo / 60);
                    $result['time_ago_text'] = "hace {$hoursAgo} hora(s)";
                } else {
                    $daysAgo = floor($minutesAgo / 1440);
                    $result['time_ago_text'] = "hace {$daysAgo} día(s)";
                }
            }
            
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error al buscar último código consumido: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Buscar código por ID
     * 
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT 
                    c.*,
                    p.name as platform_name,
                    p.display_name as platform_display_name,
                    ea.email as account_email
                FROM codes c
                INNER JOIN platforms p ON c.platform_id = p.id
                INNER JOIN email_accounts ea ON c.email_account_id = ea.id
                WHERE c.id = :id
            ");
            
            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error al buscar código por ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Marcar código como consumido
     * 
     * @param int $codeId ID del código
     * @param string|null $userEmail Email del usuario que consume (opcional)
     * @param string|null $username Username del usuario (opcional)
     * @return bool
     */
    public function markAsConsumed(int $codeId, ?string $userEmail = null, ?string $username = null): bool
    {
        try {
            $db = Database::getConnection();
            
            // Actualizar código con información del usuario que lo consume
            $stmt = $db->prepare("
                UPDATE codes
                SET status = 'consumed',
                    consumed_at = NOW(),
                    consumed_by_email = :email,
                    consumed_by_username = :username,
                    updated_at = NOW()
                WHERE id = :id
                  AND status = 'available'
            ");
            
            $result = $stmt->execute([
                'id' => $codeId,
                'email' => $userEmail ?: null,
                'username' => $username ?: null
            ]);
            
            if ($result && ($userEmail || $username)) {
                // Log de consumo para auditoría
                error_log("Código #{$codeId} consumido por: email={$userEmail}, username={$username}");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error al marcar código como consumido: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si existe un código duplicado
     * 
     * @param string $code
     * @param int $platformId
     * @param int $emailAccountId
     * @return bool
     */
    public function codeExists(string $code, int $platformId, int $emailAccountId): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT COUNT(*) as count
                FROM codes
                WHERE code = :code
                  AND platform_id = :platform_id
                  AND email_account_id = :email_account_id
                  AND status = 'available'
            ");
            
            $stmt->execute([
                'code' => $code,
                'platform_id' => $platformId,
                'email_account_id' => $emailAccountId
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return ($result['count'] ?? 0) > 0;
        } catch (PDOException $e) {
            error_log("Error al verificar código duplicado: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas de códigos
     * 
     * @return array
     */
    public function getStats(): array
    {
        try {
            $db = Database::getConnection();
            
            // Total de códigos
            $stmt = $db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN status = 'consumed' THEN 1 ELSE 0 END) as consumed
                FROM codes
            ");
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Por plataforma
            $stmt = $db->query("
                SELECT 
                    p.name as platform,
                    p.display_name,
                    COUNT(c.id) as total,
                    SUM(CASE WHEN c.status = 'available' THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN c.status = 'consumed' THEN 1 ELSE 0 END) as consumed
                FROM platforms p
                LEFT JOIN codes c ON p.id = c.platform_id
                GROUP BY p.id, p.name, p.display_name
                ORDER BY p.display_name
            ");
            $byPlatform = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'total' => (int) ($stats['total'] ?? 0),
                'available' => (int) ($stats['available'] ?? 0),
                'consumed' => (int) ($stats['consumed'] ?? 0),
                'by_platform' => $byPlatform
            ];
        } catch (PDOException $e) {
            error_log("Error al obtener estadísticas: " . $e->getMessage());
            return [
                'total' => 0,
                'available' => 0,
                'consumed' => 0,
                'by_platform' => []
            ];
        }
    }

    /**
     * Guardar código en warehouse (histórico)
     * 
     * @param array $codeData
     * @return bool
     */
    public function saveToWarehouse(array $codeData): bool
    {
        try {
            $db = Database::getWarehouseConnection();
            $stmt = $db->prepare("
                INSERT INTO codes_history (
                    code_id,
                    email_account_id,
                    platform_id,
                    code,
                    email_from,
                    subject,
                    received_at,
                    consumed_at,
                    origin
                ) VALUES (
                    :code_id,
                    :email_account_id,
                    :platform_id,
                    :code,
                    :email_from,
                    :subject,
                    :received_at,
                    :consumed_at,
                    :origin
                )
            ");
            
            return $stmt->execute([
                'code_id' => $codeData['id'] ?? null,
                'email_account_id' => $codeData['email_account_id'],
                'platform_id' => $codeData['platform_id'],
                'code' => $codeData['code'],
                'email_from' => $codeData['email_from'] ?? null,
                'subject' => $codeData['subject'] ?? null,
                'received_at' => $codeData['received_at'] ?? date('Y-m-d H:i:s'),
                'consumed_at' => $codeData['consumed_at'] ?? null,
                'origin' => $codeData['origin'] ?? 'imap'
            ]);
        } catch (PDOException $e) {
            error_log("Error al guardar código en warehouse: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Buscar y paginar códigos consumidos (registro de accesos)
     * 
     * @param int $page
     * @param int $perPage
     * @param string $search
     * @return array
     */
    public function searchConsumedCodes(int $page = 1, int $perPage = 15, string $search = ''): array
    {
        try {
            $db = Database::getConnection();
            $params = [];
            $whereClause = "WHERE c.status = 'consumed'";

            if (!empty($search)) {
                $searchLower = '%' . strtolower($search) . '%';
                $whereClause .= " AND (
                    LOWER(c.code) LIKE :search 
                    OR LOWER(c.consumed_by_username) LIKE :search 
                    OR LOWER(c.consumed_by_email) LIKE :search
                    OR LOWER(c.recipient_email) LIKE :search
                    OR LOWER(p.display_name) LIKE :search
                )";
                $params['search'] = $searchLower;
            }

            // Contar total
            $countStmt = $db->prepare("
                SELECT COUNT(*) as total 
                FROM codes c
                INNER JOIN platforms p ON c.platform_id = p.id
                {$whereClause}
            ");
            $countStmt->execute($params);
            $total = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Calcular paginación
            $totalPages = $perPage > 0 ? ceil($total / $perPage) : 1;
            $offset = ($page - 1) * $perPage;
            
            // Obtener datos paginados
            $limitClause = $perPage > 0 ? "LIMIT {$perPage} OFFSET {$offset}" : '';
            
            $stmt = $db->prepare("
                SELECT 
                    c.id,
                    c.code,
                    c.consumed_by_username,
                    c.consumed_by_email,
                    c.recipient_email,
                    c.consumed_at,
                    c.received_at,
                    p.name as platform_name,
                    p.display_name as platform_display_name
                FROM codes c
                INNER JOIN platforms p ON c.platform_id = p.id
                {$whereClause}
                ORDER BY c.consumed_at DESC, c.id DESC
                {$limitClause}
            ");
            
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'data' => $data,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages
            ];
        } catch (PDOException $e) {
            error_log("Error al buscar códigos consumidos: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => 1
            ];
        }
    }
}
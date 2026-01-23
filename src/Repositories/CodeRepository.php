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
                    received_at,
                    origin,
                    status
                ) VALUES (
                    :email_account_id,
                    :platform_id,
                    :code,
                    :email_from,
                    :subject,
                    :received_at,
                    :origin,
                    'available'
                )
            ");
            
            $stmt->execute([
                'email_account_id' => $codeData['email_account_id'],
                'platform_id' => $codeData['platform_id'],
                'code' => $codeData['code'],
                'email_from' => $codeData['email_from'] ?? null,
                'subject' => $codeData['subject'] ?? null,
                'received_at' => $codeData['received_at'] ?? date('Y-m-d H:i:s'),
                'origin' => $codeData['origin'] ?? 'imap'
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
     * @return array|null Datos del código o null si no hay disponible
     */
    public function findLatestAvailable(int $platformId): ?array
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
                    c.origin,
                    c.status,
                    c.created_at,
                    p.name as platform_name,
                    p.display_name as platform_display_name,
                    ea.email as account_email
                FROM codes c
                INNER JOIN platforms p ON c.platform_id = p.id
                INNER JOIN email_accounts ea ON c.email_account_id = ea.id
                WHERE c.platform_id = :platform_id
                  AND c.status = 'available'
                ORDER BY c.received_at DESC, c.id DESC
                LIMIT 1
            ");
            
            $stmt->execute(['platform_id' => $platformId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error al buscar código disponible: " . $e->getMessage());
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
}
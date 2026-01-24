<?php
/**
 * GAC - Repositorio de Cuentas de Email
 * 
 * Maneja el acceso a datos de cuentas de email (IMAP/Gmail)
 * 
 * @package Gac\Repositories
 */

namespace Gac\Repositories;

use Gac\Helpers\Database;
use PDO;
use PDOException;

class EmailAccountRepository
{
    /**
     * Obtener todas las cuentas de email habilitadas
     * 
     * @return array
     */
    public function findAllEnabled(): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT 
                    id,
                    email,
                    type,
                    provider_config,
                    oauth_token,
                    oauth_refresh_token,
                    enabled,
                    last_sync_at,
                    sync_status,
                    error_message
                FROM email_accounts
                WHERE enabled = 1
                ORDER BY created_at ASC
            ");
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener cuentas de email: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener cuentas de email por tipo
     * 
     * @param string $type 'imap' o 'gmail'
     * @return array
     */
    public function findByType(string $type): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT 
                    id,
                    email,
                    type,
                    provider_config,
                    oauth_token,
                    oauth_refresh_token,
                    enabled,
                    last_sync_at,
                    sync_status,
                    error_message
                FROM email_accounts
                WHERE type = :type AND enabled = 1
                ORDER BY created_at ASC
            ");
            
            $stmt->execute(['type' => $type]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener cuentas de email por tipo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener una cuenta de email por ID
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
                    id,
                    email,
                    type,
                    provider_config,
                    oauth_token,
                    oauth_refresh_token,
                    enabled,
                    last_sync_at,
                    sync_status,
                    error_message
                FROM email_accounts
                WHERE id = :id
            ");
            
            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error al obtener cuenta de email por ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Contar todas las cuentas de email (habilitadas y deshabilitadas)
     * 
     * @return int
     */
    public function countAll(): int
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT COUNT(*) as count FROM email_accounts");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) ($result['count'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error al contar cuentas de email: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Actualizar última sincronización
     * 
     * @param int $id
     * @param string $status 'success' | 'error' | 'pending'
     * @param string|null $errorMessage
     * @return bool
     */
    public function updateSyncStatus(int $id, string $status, ?string $errorMessage = null): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                UPDATE email_accounts
                SET last_sync_at = NOW(),
                    sync_status = :status,
                    error_message = :error_message,
                    updated_at = NOW()
                WHERE id = :id
            ");
            
            return $stmt->execute([
                'id' => $id,
                'status' => $status,
                'error_message' => $errorMessage
            ]);
        } catch (PDOException $e) {
            error_log("Error al actualizar estado de sincronización: " . $e->getMessage());
            return false;
        }
    }
}
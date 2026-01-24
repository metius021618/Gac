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
     * Buscar y paginar cuentas de email con filtros
     * 
     * @param string $search Término de búsqueda (email o usuario)
     * @param int $page Página actual
     * @param int $perPage Registros por página
     * @return array ['data' => [], 'total' => int, 'page' => int, 'per_page' => int, 'total_pages' => int]
     */
    public function searchAndPaginate(string $search = '', int $page = 1, int $perPage = 15): array
    {
        try {
            $db = Database::getConnection();
            
            // Construir condiciones de búsqueda
            $whereConditions = [];
            $params = [];
            
            if (!empty($search)) {
                // Buscar por email o por usuario (imap_user en provider_config JSON)
                // Usar JSON_UNQUOTE para compatibilidad y CAST para asegurar string
                $whereConditions[] = "(
                    email LIKE :search 
                    OR JSON_UNQUOTE(JSON_EXTRACT(provider_config, '$.imap_user')) LIKE :search
                    OR CAST(JSON_EXTRACT(provider_config, '$.imap_user') AS CHAR) LIKE :search
                )";
                $params['search'] = "%{$search}%";
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Contar total de registros
            $countStmt = $db->prepare("SELECT COUNT(*) as total FROM email_accounts {$whereClause}");
            $countStmt->execute($params);
            $total = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Calcular paginación
            $totalPages = $perPage > 0 ? ceil($total / $perPage) : 1;
            $offset = ($page - 1) * $perPage;
            
            // Obtener datos paginados
            $limitClause = $perPage > 0 ? "LIMIT {$perPage} OFFSET {$offset}" : '';
            
            $stmt = $db->prepare("
                SELECT 
                    id,
                    email,
                    type,
                    provider_config,
                    enabled,
                    last_sync_at,
                    sync_status,
                    error_message,
                    created_at
                FROM email_accounts
                {$whereClause}
                ORDER BY created_at DESC
                {$limitClause}
            ");
            
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Parsear provider_config para cada registro
            foreach ($data as &$row) {
                $config = json_decode($row['provider_config'] ?? '{}', true);
                $row['imap_server'] = $config['imap_server'] ?? '';
                $row['imap_port'] = $config['imap_port'] ?? 993;
                $row['imap_user'] = $config['imap_user'] ?? '';
                $row['imap_password'] = ''; // No exponer contraseñas
            }
            
            return [
                'data' => $data,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages
            ];
        } catch (PDOException $e) {
            error_log("Error al buscar y paginar cuentas de email: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => $perPage,
                'total_pages' => 0
            ];
        }
    }

    /**
     * Guardar nueva cuenta de email
     * 
     * @param array $data
     * @return int|false ID de la cuenta creada o false en caso de error
     */
    public function save(array $data): int|false
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO email_accounts (
                    email, type, provider_config, enabled, sync_status, created_at, updated_at
                ) VALUES (
                    :email, :type, :provider_config, :enabled, :sync_status, NOW(), NOW()
                )
            ");
            
            $providerConfig = json_encode($data['provider_config'] ?? []);
            
            $stmt->execute([
                'email' => $data['email'],
                'type' => $data['type'] ?? 'imap',
                'provider_config' => $providerConfig,
                'enabled' => $data['enabled'] ?? 1,
                'sync_status' => $data['sync_status'] ?? 'pending'
            ]);
            
            return (int) $db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error al guardar cuenta de email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar cuenta de email
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                UPDATE email_accounts
                SET email = :email,
                    type = :type,
                    provider_config = :provider_config,
                    enabled = :enabled,
                    sync_status = :sync_status,
                    updated_at = NOW()
                WHERE id = :id
            ");
            
            $providerConfig = json_encode($data['provider_config'] ?? []);
            
            return $stmt->execute([
                'id' => $id,
                'email' => $data['email'],
                'type' => $data['type'] ?? 'imap',
                'provider_config' => $providerConfig,
                'enabled' => $data['enabled'] ?? 1,
                'sync_status' => $data['sync_status'] ?? 'pending'
            ]);
        } catch (PDOException $e) {
            error_log("Error al actualizar cuenta de email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar cuenta de email
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM email_accounts WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Error al eliminar cuenta de email: " . $e->getMessage());
            return false;
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
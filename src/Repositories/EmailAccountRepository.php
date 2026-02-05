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
     * Obtener la cuenta maestra
     * 
     * @return array|null
     */
    public function findMasterAccount(): ?array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT 
                    id,
                    email,
                    type,
                    provider_config,
                    enabled,
                    last_sync_at,
                    sync_status
                FROM email_accounts
                WHERE JSON_EXTRACT(provider_config, '$.is_master') = true
                LIMIT 1
            ");
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error al obtener cuenta maestra: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener una cuenta de email por email
     * 
     * @param string $email
     * @return array|null
     */
    public function findByEmail(string $email): ?array
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
                WHERE email = :email
                LIMIT 1
            ");
            
            $stmt->execute(['email' => $email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error al obtener cuenta de email por email: " . $e->getMessage());
            return null;
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

    /**
     * Contar todas las cuentas de email
     * 
     * @return int
     */
    public function countAll(): int
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT COUNT(*) as count FROM email_accounts");
            return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
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
            $searchTerm = '%' . trim($search) . '%';
            
            // Contar total
            if (!empty($search)) {
                $countStmt = $db->prepare("SELECT COUNT(*) as total FROM email_accounts WHERE email LIKE ? OR JSON_UNQUOTE(JSON_EXTRACT(provider_config, '$.imap_user')) LIKE ?");
                $countStmt->execute([$searchTerm, $searchTerm]);
            } else {
                $countStmt = $db->query("SELECT COUNT(*) as total FROM email_accounts");
            }
            $total = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Calcular paginación
            $totalPages = $perPage > 0 ? ceil($total / $perPage) : 1;
            $offset = ($page - 1) * $perPage;
            $limitClause = $perPage > 0 ? "LIMIT {$perPage} OFFSET {$offset}" : '';
            
            // Obtener datos con plataformas asignadas
            if (!empty($search)) {
                $stmt = $db->prepare("
                    SELECT 
                        ea.id, 
                        ea.email, 
                        ea.type, 
                        ea.provider_config, 
                        ea.enabled, 
                        ea.last_sync_at, 
                        ea.sync_status, 
                        ea.error_message, 
                        ea.created_at,
                        GROUP_CONCAT(DISTINCT p.display_name ORDER BY p.display_name SEPARATOR ', ') as platforms
                    FROM email_accounts ea
                    LEFT JOIN user_access ua ON ea.email = ua.email AND ua.enabled = 1
                    LEFT JOIN platforms p ON ua.platform_id = p.id
                    WHERE ea.email LIKE ? OR JSON_UNQUOTE(JSON_EXTRACT(ea.provider_config, '$.imap_user')) LIKE ?
                    GROUP BY ea.id
                    ORDER BY ea.created_at DESC
                    {$limitClause}
                ");
                $stmt->execute([$searchTerm, $searchTerm]);
            } else {
                $stmt = $db->query("
                    SELECT 
                        ea.id, 
                        ea.email, 
                        ea.type, 
                        ea.provider_config, 
                        ea.enabled, 
                        ea.last_sync_at, 
                        ea.sync_status, 
                        ea.error_message, 
                        ea.created_at,
                        GROUP_CONCAT(DISTINCT p.display_name ORDER BY p.display_name SEPARATOR ', ') as platforms
                    FROM email_accounts ea
                    LEFT JOIN user_access ua ON ea.email = ua.email AND ua.enabled = 1
                    LEFT JOIN platforms p ON ua.platform_id = p.id
                    GROUP BY ea.id
                    ORDER BY ea.created_at DESC
                    {$limitClause}
                ");
            }
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Parsear provider_config y plataformas
            foreach ($data as &$row) {
                $config = json_decode($row['provider_config'] ?? '{}', true);
                $row['imap_server'] = $config['imap_server'] ?? '';
                $row['imap_port'] = $config['imap_port'] ?? 993;
                $row['imap_user'] = $config['imap_user'] ?? '';
                $row['imap_password'] = '';
                // Convertir plataformas de string a array
                $row['platforms'] = !empty($row['platforms']) ? explode(', ', $row['platforms']) : [];
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
                'page' => $page,
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
                    provider_config = :provider_config,
                    enabled = :enabled,
                    updated_at = NOW()
                WHERE id = :id
            ");
            
            $providerConfig = json_encode($data['provider_config'] ?? []);
            
            return $stmt->execute([
                'id' => $id,
                'email' => $data['email'],
                'provider_config' => $providerConfig,
                'enabled' => $data['enabled'] ?? 1
            ]);
        } catch (PDOException $e) {
            error_log("Error al actualizar cuenta de email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar múltiples cuentas de email
     * 
     * @param array $ids Array de IDs a eliminar
     * @return bool
     */
    public function bulkDelete(array $ids): bool
    {
        if (empty($ids)) {
            return false;
        }

        try {
            $db = Database::getConnection();
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            $sql = "DELETE FROM email_accounts WHERE id IN ({$placeholders})";
            $stmt = $db->prepare($sql);
            
            return $stmt->execute($ids);
        } catch (PDOException $e) {
            error_log("Error al eliminar múltiples cuentas de email: " . $e->getMessage());
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
     * Crear o actualizar cuenta Gmail con tokens OAuth
     * Si ya existe la cuenta por email, actualiza tokens y type; si no, inserta nueva.
     *
     * @param string $email
     * @param string $accessToken Token de acceso (se guarda para uso inmediato)
     * @param string $refreshToken Token de refresco (obligatorio para cron)
     * @return int ID de la cuenta creada o actualizada, o false en error
     */
    public function createOrUpdateGmailAccount(string $email, string $accessToken, string $refreshToken): int|false
    {
        $email = trim(strtolower($email));
        if ($email === '') {
            return false;
        }

        try {
            $db = Database::getConnection();
            $existing = $this->findByEmail($email);

            if ($existing) {
                $stmt = $db->prepare("
                    UPDATE email_accounts
                    SET type = 'gmail',
                        oauth_token = :oauth_token,
                        oauth_refresh_token = :oauth_refresh_token,
                        sync_status = 'pending',
                        error_message = NULL,
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    'id' => $existing['id'],
                    'oauth_token' => $accessToken,
                    'oauth_refresh_token' => $refreshToken
                ]);
                return (int) $existing['id'];
            }

            $stmt = $db->prepare("
                INSERT INTO email_accounts (
                    email, type, provider_config, oauth_token, oauth_refresh_token,
                    enabled, sync_status, created_at, updated_at
                ) VALUES (
                    :email, 'gmail', '{}', :oauth_token, :oauth_refresh_token,
                    1, 'pending', NOW(), NOW()
                )
            ");
            $stmt->execute([
                'email' => $email,
                'oauth_token' => $accessToken,
                'oauth_refresh_token' => $refreshToken
            ]);
            return (int) $db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error al guardar cuenta Gmail: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear o actualizar cuenta Outlook con tokens OAuth
     * Si ya existe la cuenta por email, actualiza tokens y type; si no, inserta nueva.
     *
     * @param string $email
     * @param string $accessToken Token de acceso (se guarda para uso inmediato)
     * @param string $refreshToken Token de refresco (obligatorio para cron)
     * @return int ID de la cuenta creada o actualizada, o false en error
     */
    public function createOrUpdateOutlookAccount(string $email, string $accessToken, string $refreshToken): int|false
    {
        $email = trim(strtolower($email));
        if ($email === '') {
            return false;
        }

        try {
            $db = Database::getConnection();
            $existing = $this->findByEmail($email);

            if ($existing) {
                $stmt = $db->prepare("
                    UPDATE email_accounts
                    SET type = 'outlook',
                        oauth_token = :oauth_token,
                        oauth_refresh_token = :oauth_refresh_token,
                        enabled = 1,
                        sync_status = 'pending',
                        error_message = NULL,
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    'id' => $existing['id'],
                    'oauth_token' => $accessToken,
                    'oauth_refresh_token' => $refreshToken
                ]);
                return (int) $existing['id'];
            }

            $stmt = $db->prepare("
                INSERT INTO email_accounts (
                    email, type, provider_config, oauth_token, oauth_refresh_token,
                    enabled, sync_status, created_at, updated_at
                ) VALUES (
                    :email, 'outlook', '{}', :oauth_token, :oauth_refresh_token,
                    1, 'pending', NOW(), NOW()
                )
            ");
            $stmt->execute([
                'email' => $email,
                'oauth_token' => $accessToken,
                'oauth_refresh_token' => $refreshToken
            ]);
            return (int) $db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error al guardar cuenta Outlook: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar solo los tokens OAuth de una cuenta
     *
     * @param int $id
     * @param string $accessToken
     * @param string $refreshToken
     * @return bool
     */
    public function updateOAuthTokens(int $id, string $accessToken, string $refreshToken): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                UPDATE email_accounts
                SET oauth_token = :oauth_token,
                    oauth_refresh_token = :oauth_refresh_token,
                    error_message = NULL,
                    updated_at = NOW()
                WHERE id = :id
            ");
            return $stmt->execute([
                'id' => $id,
                'oauth_token' => $accessToken,
                'oauth_refresh_token' => $refreshToken
            ]);
        } catch (PDOException $e) {
            error_log("Error al actualizar tokens OAuth: " . $e->getMessage());
            return false;
        }
    }
}

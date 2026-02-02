<?php
/**
 * GAC - Repositorio de Accesos de Usuario
 * 
 * @package Gac\Repositories
 */

namespace Gac\Repositories;

use Gac\Helpers\Database;
use PDO;
use PDOException;

class UserAccessRepository
{
    /**
     * Crear o actualizar acceso de usuario
     */
    public function createOrUpdate(string $email, string $password, int $platformId): bool
    {
        try {
            $db = Database::getConnection();
            
            $sql = "
                INSERT INTO user_access (email, password, platform_id, enabled)
                VALUES (:email, :password, :platform_id, 1)
                ON DUPLICATE KEY UPDATE
                    password = :password_update,
                    enabled = 1,
                    updated_at = NOW()
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->bindValue(':password', $password, PDO::PARAM_STR);
            $stmt->bindValue(':platform_id', $platformId, PDO::PARAM_INT);
            $stmt->bindValue(':password_update', $password, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al crear/actualizar acceso de usuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Buscar acceso por email y plataforma
     */
    public function findByEmailAndPlatform(string $email, int $platformId): ?array
    {
        try {
            $db = Database::getConnection();
            
            $sql = "
                SELECT id, email, password, platform_id, enabled, created_at, updated_at
                FROM user_access
                WHERE email = :email AND platform_id = :platform_id AND enabled = 1
                LIMIT 1
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->bindValue(':platform_id', $platformId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error al buscar acceso de usuario: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verificar acceso (validar email, password y plataforma)
     */
    public function verifyAccess(string $email, string $password, int $platformId): bool
    {
        $access = $this->findByEmailAndPlatform($email, $platformId);
        
        if (!$access) {
            return false;
        }
        
        // Comparar password (usuario IMAP)
        return $access['password'] === $password;
    }

    /**
     * Total de registros en user_access (para dashboard "Correos registrados")
     */
    public function countAll(): int
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT COUNT(*) FROM user_access");
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error al contar user_access: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Listar/buscar en user_access. Si hay texto, filtra por email o usuario (password).
     */
    public function searchAndPaginate(string $search = '', int $page = 1, int $perPage = 15): array
    {
        $logFile = defined('BASE_PATH') ? BASE_PATH . '/logs/search_debug.log' : (__DIR__ . '/../../logs/search_debug.log');
        $log = function ($msg) use ($logFile) {
            @file_put_contents($logFile, date('Y-m-d H:i:s') . ' [UserAccessRepository] ' . $msg . "\n", FILE_APPEND | LOCK_EX);
        };
        $log('searchAndPaginate called: search="' . $search . '" page=' . $page . ' perPage=' . $perPage);
        try {
            $db = Database::getConnection();
            $q = trim($search);
            $whereClause = '';
            $params = [];

            if ($q !== '') {
                $term = '%' . $q . '%';
                $whereClause = "WHERE (ua.email LIKE :q_email OR ua.password LIKE :q_password)";
                $params[':q_email'] = $term;
                $params[':q_password'] = $term;
                $log('WHERE applied: q="' . $q . '" term="' . $term . '"');
            } else {
                $log('no search term, listing all');
            }

            // Contar total (tabla user_access)
            $countSql = "
                SELECT COUNT(*) as total
                FROM user_access ua
                LEFT JOIN platforms p ON ua.platform_id = p.id
                {$whereClause}
            ";
            $log('countSql: ' . trim(preg_replace('/\s+/', ' ', $countSql)));
            $countStmt = $db->prepare($countSql);
            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    $countStmt->bindValue($key, $value, PDO::PARAM_STR);
                }
            }
            $countStmt->execute();
            $total = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            $log('count result total=' . $total);
            
            // Calcular paginación
            $totalPages = $perPage > 0 ? ceil($total / $perPage) : 1;
            $offset = ($page - 1) * $perPage;
            $limitClause = $perPage > 0 ? "LIMIT {$perPage} OFFSET {$offset}" : '';
            
            // Obtener datos
            $sql = "
                SELECT 
                    ua.id,
                    ua.email,
                    ua.password,
                    ua.platform_id,
                    ua.enabled,
                    ua.created_at,
                    ua.updated_at,
                    p.name as platform_name,
                    p.display_name as platform_display_name
                FROM user_access ua
                LEFT JOIN platforms p ON ua.platform_id = p.id
                {$whereClause}
                ORDER BY ua.created_at DESC
                {$limitClause}
            ";
            
            $stmt = $db->prepare($sql);
            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value, PDO::PARAM_STR);
                }
            }
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $log('SELECT returned ' . count($data) . ' rows');
            
            return [
                'data' => $data,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages
            ];
        } catch (PDOException $e) {
            error_log("Error al buscar accesos de usuario: " . $e->getMessage());
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
     * Obtener plataformas asignadas a un email
     */
    public function getPlatformsByEmail(string $email): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT p.display_name as platform_name
                FROM user_access ua
                JOIN platforms p ON ua.platform_id = p.id
                WHERE ua.email = :email AND ua.enabled = 1
                ORDER BY p.display_name ASC
            ");
            $stmt->execute([':email' => $email]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_column($results, 'platform_name');
        } catch (PDOException $e) {
            error_log("Error al obtener plataformas por email: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crear múltiples accesos de usuario masivamente
     * 
     * @param array $emails Array de correos electrónicos
     * @param string $password Contraseña/acceso común para todos
     * @param int $platformId ID de la plataforma
     * @return array ['success' => int, 'duplicates' => int, 'errors' => array]
     */
    public function bulkCreate(array $emails, string $password, int $platformId): array
    {
        $success = 0;
        $duplicates = 0;
        $errors = [];
        
        try {
            $db = Database::getConnection();
            $db->beginTransaction();
            
            $sql = "
                INSERT INTO user_access (email, password, platform_id, enabled)
                VALUES (:email, :password, :platform_id, 1)
                ON DUPLICATE KEY UPDATE
                    password = :password_update,
                    enabled = 1,
                    updated_at = NOW()
            ";
            
            $stmt = $db->prepare($sql);
            
            foreach ($emails as $email) {
                $email = trim($email);
                if (empty($email)) {
                    continue;
                }
                
                try {
                    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
                    $stmt->bindValue(':password', $password, PDO::PARAM_STR);
                    $stmt->bindValue(':platform_id', $platformId, PDO::PARAM_INT);
                    $stmt->bindValue(':password_update', $password, PDO::PARAM_STR);
                    
                    if ($stmt->execute()) {
                        // Verificar si fue insert o update
                        if ($stmt->rowCount() > 0) {
                            $success++;
                        } else {
                            $duplicates++;
                        }
                    }
                } catch (PDOException $e) {
                    $errors[] = "Error con {$email}: " . $e->getMessage();
                }
            }
            
            $db->commit();
            
            return [
                'success' => $success,
                'duplicates' => $duplicates,
                'errors' => $errors
            ];
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Error al crear accesos masivamente: " . $e->getMessage());
            return [
                'success' => 0,
                'duplicates' => 0,
                'errors' => ['Error general: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Eliminar acceso
     */
    public function delete(int $id): bool
    {
        try {
            $db = Database::getConnection();
            
            $sql = "DELETE FROM user_access WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al eliminar acceso de usuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar múltiples accesos por ID
     */
    public function bulkDelete(array $ids): bool
    {
        if (empty($ids)) {
            return false;
        }
        try {
            $db = Database::getConnection();
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("DELETE FROM user_access WHERE id IN ({$placeholders})");
            return $stmt->execute(array_values($ids));
        } catch (PDOException $e) {
            error_log("Error al eliminar accesos en lote: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambiar estado enabled de un acceso
     */
    public function toggleEnabled(int $id, bool $enabled): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE user_access SET enabled = :enabled, updated_at = NOW() WHERE id = :id");
            return $stmt->execute([':id' => $id, ':enabled' => $enabled ? 1 : 0]);
        } catch (PDOException $e) {
            error_log("Error al cambiar estado de acceso: " . $e->getMessage());
            return false;
        }
    }
}

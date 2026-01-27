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
     * Listar todos los accesos con paginación
     */
    public function searchAndPaginate(string $search = '', int $page = 1, int $perPage = 15): array
    {
        try {
            $db = Database::getConnection();
            
            $whereClause = '';
            $params = [];
            
            if (!empty($search) && trim($search) !== '') {
                $searchTerm = '%' . trim($search) . '%';
                $whereClause = "WHERE (ua.email LIKE :search OR p.display_name LIKE :search)";
                $params[':search'] = $searchTerm;
            }
            
            // Contar total
            $countSql = "
                SELECT COUNT(*) as total
                FROM user_access ua
                LEFT JOIN platforms p ON ua.platform_id = p.id
                {$whereClause}
            ";
            $countStmt = $db->prepare($countSql);
            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    $countStmt->bindValue($key, $value, PDO::PARAM_STR);
                }
            }
            $countStmt->execute();
            $total = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
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
}

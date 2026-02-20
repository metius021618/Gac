<?php
/**
 * GAC - Repositorio de Usuarios
 *
 * Maneja el acceso a datos para la tabla `users`.
 *
 * @package Gac\Repositories
 */

namespace Gac\Repositories;

use Gac\Helpers\Database;
use PDO;
use PDOException;

class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Obtener un usuario por su nombre de usuario.
     *
     * @param string $username
     * @return array|false
     */
    public function findByUsername(string $username): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        return $stmt->fetch();
    }

    /**
     * Obtener un usuario por su email.
     *
     * @param string $email
     * @return array|false
     */
    public function findByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    /**
     * Obtener un usuario por su ID.
     *
     * @param int $id
     * @return array|false
     */
    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Actualizar último login del usuario.
     *
     * @param int $userId
     * @return bool
     */
    public function updateLastLogin(int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
        try {
            return $stmt->execute([':id' => $userId]);
        } catch (PDOException $e) {
            error_log("Error al actualizar último login (ID: {$userId}): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Guardar un nuevo usuario.
     *
     * @param array $data
     * @return int|false ID del nuevo usuario o false en caso de error.
     */
    public function save(array $data): int|false
    {
        $sql = "INSERT INTO users (username, email, password, role_id, active)
                VALUES (:username, :email, :password, :role_id, :active)";
        $stmt = $this->db->prepare($sql);

        try {
            $stmt->execute([
                ':username' => $data['username'],
                ':email' => $data['email'],
                ':password' => $data['password'], // Ya debe venir encriptada
                ':role_id' => $data['role_id'],
                ':active' => $data['active'] ?? 1
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error al guardar usuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener todos los administradores (roles admin / SUPER_ADMIN)
     * @return array
     */
    public function findAllAdministrators(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    u.id,
                    u.username,
                    u.email,
                    u.active,
                    u.last_login,
                    u.created_at,
                    r.name as role_name,
                    r.display_name as role_display_name
                FROM users u
                INNER JOIN roles r ON u.role_id = r.id
                WHERE u.active = 1 
                  AND (r.name = 'SUPER_ADMIN' OR r.name = 'ADMIN' OR r.name = 'admin')
                ORDER BY u.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener administradores: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener todos los usuarios con su rol (para lista de administradores que incluye Comprador, etc.)
     * @return array
     */
    public function findAllUsersWithRoles(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    u.id,
                    u.username,
                    u.email,
                    u.active,
                    u.last_login,
                    u.created_at,
                    r.name as role_name,
                    r.display_name as role_display_name
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                ORDER BY u.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Error al obtener usuarios con roles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar administradores
     * 
     * @return int
     */
    public function countAdministrators(): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM users u
                INNER JOIN roles r ON u.role_id = r.id
                WHERE u.active = 1 
                  AND (r.name = 'SUPER_ADMIN' OR r.name = 'ADMIN')
            ");
            $stmt->execute();
            return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error al contar administradores: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Actualizar contraseña de usuario
     * 
     * @param int $userId
     * @param string $newPassword (ya encriptada)
     * @return bool
     */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET password = :password WHERE id = :id");
            return $stmt->execute([
                ':password' => $newPassword,
                ':id' => $userId
            ]);
        } catch (PDOException $e) {
            error_log("Error al actualizar contraseña: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar datos de usuario
     * 
     * @param int $userId
     * @param array $data
     * @return bool
     */
    public function update(int $userId, array $data): bool
    {
        try {
            $fields = [];
            $params = [':id' => $userId];

            if (isset($data['username'])) {
                $fields[] = 'username = :username';
                $params[':username'] = $data['username'];
            }
            if (isset($data['email'])) {
                $fields[] = 'email = :email';
                $params[':email'] = $data['email'];
            }
            if (isset($data['active'])) {
                $fields[] = 'active = :active';
                $params[':active'] = $data['active'];
            }

            if (empty($fields)) {
                return false;
            }

            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error al actualizar usuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Buscar y paginar usuarios
     * 
     * @param int $page
     * @param int $perPage
     * @param string $search
     * @return array
     */
    public function searchAndPaginate(int $page = 1, int $perPage = 15, string $search = ''): array
    {
        try {
            $params = [];
            $whereClause = '';

            if (!empty($search)) {
                $searchLower = '%' . strtolower($search) . '%';
                $whereClause = "WHERE LOWER(username) LIKE :search OR LOWER(email) LIKE :search";
                $params['search'] = $searchLower;
            }

            // Contar total
            $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM users {$whereClause}");
            $countStmt->execute($params);
            $total = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Calcular paginación
            $totalPages = $perPage > 0 ? ceil($total / $perPage) : 1;
            $offset = ($page - 1) * $perPage;
            
            // Obtener datos paginados
            $limitClause = $perPage > 0 ? "LIMIT {$perPage} OFFSET {$offset}" : '';
            
            $stmt = $this->db->prepare("
                SELECT 
                    u.id,
                    u.username,
                    u.email,
                    u.active,
                    u.last_login,
                    u.created_at,
                    r.name as role_name,
                    r.display_name as role_display_name
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                {$whereClause}
                ORDER BY u.created_at DESC
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
            error_log("Error al buscar y paginar usuarios: " . $e->getMessage());
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

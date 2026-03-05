<?php
/**
 * GAC - Repositorio de Subusuarios de Acceso
 *
 * Gestiona los "usuarios adicionales" asociados a un registro de user_access
 * (correo + plataforma) que podrán usar la vista de "Consulta tu código".
 *
 * @package Gac\Repositories
 */

namespace Gac\Repositories;

use Gac\Helpers\Database;
use PDO;
use PDOException;

class UserAccessSubuserRepository
{
    /**
     * Obtener todos los subusuarios de un acceso concreto.
     *
     * @return array<int, array{ id:int, user_access_id:int, username:string, created_at:string }>
     */
    public function findByAccessId(int $userAccessId): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT id, user_access_id, username, created_at
                FROM user_access_subusers
                WHERE user_access_id = :id
                ORDER BY created_at ASC, id ASC
            ");
            $stmt->execute([':id' => $userAccessId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('UserAccessSubuserRepository::findByAccessId error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar cuántos subusuarios tiene un acceso.
     */
    public function countByAccessId(int $userAccessId): int
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT COUNT(*) AS total
                FROM user_access_subusers
                WHERE user_access_id = :id
            ");
            $stmt->execute([':id' => $userAccessId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) ($row['total'] ?? 0);
        } catch (PDOException $e) {
            error_log('UserAccessSubuserRepository::countByAccessId error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Crear un nuevo subusuario para un acceso.
     *
     * Devuelve el ID creado o null en error.
     */
    public function create(int $userAccessId, string $username): ?int
    {
        $username = trim($username);
        if ($username === '') {
            return null;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO user_access_subusers (user_access_id, username)
                VALUES (:user_access_id, :username)
            ");
            $stmt->execute([
                ':user_access_id' => $userAccessId,
                ':username' => $username,
            ]);
            return (int) $db->lastInsertId();
        } catch (PDOException $e) {
            error_log('UserAccessSubuserRepository::create error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar username de un subusuario.
     */
    public function updateUsername(int $id, string $username): bool
    {
        $username = trim($username);
        if ($username === '') {
            return false;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                UPDATE user_access_subusers
                SET username = :username, updated_at = NOW()
                WHERE id = :id
            ");
            return $stmt->execute([
                ':id' => $id,
                ':username' => $username,
            ]);
        } catch (PDOException $e) {
            error_log('UserAccessSubuserRepository::updateUsername error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar un subusuario por ID.
     */
    public function delete(int $id): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM user_access_subusers WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('UserAccessSubuserRepository::delete error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si existe un subusuario para una combinación email + plataforma + username.
     *
     * Se usa como fallback en la vista de "Consulta tu código" cuando no coincide el usuario principal.
     */
    public function existsForEmailPlatformAndUsername(string $email, int $platformId, string $username): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT 1
                FROM user_access_subusers sus
                INNER JOIN user_access ua ON ua.id = sus.user_access_id
                WHERE LOWER(ua.email) = LOWER(:email)
                  AND ua.platform_id = :platform_id
                  AND sus.username = :username
                LIMIT 1
            ");
            $stmt->execute([
                ':email' => $email,
                ':platform_id' => $platformId,
                ':username' => $username,
            ]);
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('UserAccessSubuserRepository::existsForEmailPlatformAndUsername error: ' . $e->getMessage());
            return false;
        }
    }
}


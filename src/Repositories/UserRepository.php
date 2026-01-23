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
}

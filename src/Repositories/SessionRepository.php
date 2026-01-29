<?php
/**
 * GAC - Repositorio de Sesiones
 * Maneja las sesiones almacenadas en la base de datos
 * 
 * @package Gac\Repositories
 */

namespace Gac\Repositories;

use Gac\Helpers\Database;
use PDO;
use PDOException;

class SessionRepository
{
    /**
     * Crear o actualizar sesión en la base de datos
     */
    public function createOrUpdate(string $sessionId, int $userId, string $ipAddress = null, string $userAgent = null, string $payload = ''): bool
    {
        try {
            $db = Database::getConnection();
            $lastActivity = time();
            
            $stmt = $db->prepare("
                INSERT INTO sessions (id, user_id, ip_address, user_agent, payload, last_activity)
                VALUES (:id, :user_id, :ip_address, :user_agent, :payload, :last_activity)
                ON DUPLICATE KEY UPDATE
                    last_activity = :last_activity,
                    ip_address = :ip_address,
                    user_agent = :user_agent,
                    payload = :payload
            ");
            
            return $stmt->execute([
                ':id' => $sessionId,
                ':user_id' => $userId,
                ':ip_address' => $ipAddress ?? $_SERVER['REMOTE_ADDR'] ?? null,
                ':user_agent' => $userAgent ?? $_SERVER['HTTP_USER_AGENT'] ?? null,
                ':payload' => $payload,
                ':last_activity' => $lastActivity
            ]);
        } catch (PDOException $e) {
            error_log("Error al crear/actualizar sesión: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener sesión por ID
     */
    public function findById(string $sessionId): ?array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM sessions WHERE id = :id");
            $stmt->execute([':id' => $sessionId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error al obtener sesión: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener sesión por user_id
     */
    public function findByUserId(int $userId): ?array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM sessions WHERE user_id = :user_id ORDER BY last_activity DESC LIMIT 1");
            $stmt->execute([':user_id' => $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error al obtener sesión por user_id: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar última actividad de la sesión
     */
    public function updateLastActivity(string $sessionId): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE sessions SET last_activity = :last_activity WHERE id = :id");
            return $stmt->execute([
                ':id' => $sessionId,
                ':last_activity' => time()
            ]);
        } catch (PDOException $e) {
            error_log("Error al actualizar última actividad: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si la sesión ha expirado según el timeout configurado
     */
    public function isExpired(string $sessionId, int $timeoutSeconds): bool
    {
        try {
            $session = $this->findById($sessionId);
            if (!$session) {
                return true; // Si no existe, está expirada
            }

            $lastActivity = (int) $session['last_activity'];
            $currentTime = time();
            
            return ($currentTime - $lastActivity) > $timeoutSeconds;
        } catch (\Exception $e) {
            error_log("Error al verificar expiración de sesión: " . $e->getMessage());
            return true; // En caso de error, considerar expirada
        }
    }

    /**
     * Eliminar sesión
     */
    public function delete(string $sessionId): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM sessions WHERE id = :id");
            return $stmt->execute([':id' => $sessionId]);
        } catch (PDOException $e) {
            error_log("Error al eliminar sesión: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar todas las sesiones de un usuario
     */
    public function deleteByUserId(int $userId): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM sessions WHERE user_id = :user_id");
            return $stmt->execute([':user_id' => $userId]);
        } catch (PDOException $e) {
            error_log("Error al eliminar sesiones por user_id: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Limpiar sesiones expiradas
     */
    public function cleanExpired(int $timeoutSeconds): int
    {
        try {
            $db = Database::getConnection();
            $expiredTime = time() - $timeoutSeconds;
            $stmt = $db->prepare("DELETE FROM sessions WHERE last_activity < :expired_time");
            $stmt->execute([':expired_time' => $expiredTime]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error al limpiar sesiones expiradas: " . $e->getMessage());
            return 0;
        }
    }
}

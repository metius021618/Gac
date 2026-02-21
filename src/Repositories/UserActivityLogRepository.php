<?php
/**
 * GAC - Repositorio de registro de actividad de usuarios
 * Solo se registra actividad de usuarios que NO son superadmin.
 */

namespace Gac\Repositories;

use Gac\Helpers\Database;
use PDO;

class UserActivityLogRepository
{
    public const ACTION_AGREGAR_CORREO = 'agregar_correo';
    public const ACTION_EDICION = 'edicion';
    public const ACTION_ELIMINAR = 'eliminar';
    public const ACTION_ASIGNADO = 'asignado';

    /**
     * Registrar una acción (solo si el usuario actual no es superadmin)
     */
    public static function log(int $userId, string $username, string $action, string $description): bool
    {
        if (self::isSuperadmin($userId)) {
            return false;
        }
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO user_activity_log (user_id, username, action, description, created_at)
                VALUES (:user_id, :username, :action, :description, NOW())
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':username' => $username,
                ':action' => $action,
                ':description' => $description,
            ]);
            return true;
        } catch (\PDOException $e) {
            error_log("UserActivityLogRepository::log: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Comprobar si el usuario es superadmin (role_id 1 = admin)
     */
    public static function isSuperadmin(int $userId): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT role_id FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row && (int)($row['role_id'] ?? 0) === 1;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Listar actividad con paginación y orden por fecha
     * @param int $page
     * @param int $perPage 15, 30, 45, 60, 100
     * @param string $order 'desc' | 'asc'
     * @return array { data, total, page, per_page, total_pages }
     */
    public function getPaginated(int $page = 1, int $perPage = 15, string $order = 'desc'): array
    {
        $page = max(1, $page);
        $perPage = in_array($perPage, [15, 30, 45, 60, 100], true) ? $perPage : 15;
        $order = strtolower($order) === 'asc' ? 'ASC' : 'DESC';

        try {
            $db = Database::getConnection();

            $countStmt = $db->query("SELECT COUNT(*) FROM user_activity_log");
            $total = (int) $countStmt->fetchColumn();

            $totalPages = $perPage > 0 ? max(1, (int) ceil($total / $perPage)) : 1;
            $offset = ($page - 1) * $perPage;

            $sql = "
                SELECT id, user_id, username, action, description, created_at
                FROM user_activity_log
                ORDER BY created_at {$order}
                LIMIT " . (int) $perPage . " OFFSET " . (int) $offset;
            $stmt = $db->query($sql);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return [
                'data' => $data,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
            ];
        } catch (\PDOException $e) {
            error_log("UserActivityLogRepository::getPaginated: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => $perPage,
                'total_pages' => 0,
            ];
        }
    }

    /**
     * Etiqueta legible por acción
     */
    public static function actionLabel(string $action): string
    {
        $labels = [
            self::ACTION_AGREGAR_CORREO => 'Agregar correo',
            self::ACTION_EDICION => 'Edición',
            self::ACTION_ELIMINAR => 'Eliminar',
            self::ACTION_ASIGNADO => 'Asignado',
        ];
        return $labels[$action] ?? $action;
    }
}

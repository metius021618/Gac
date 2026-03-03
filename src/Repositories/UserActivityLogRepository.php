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
     * Listar actividad con paginación, orden y filtros
     * @param int $page
     * @param int $perPage 50, 75, 100, 0=All
     * @param string $order 'desc' | 'asc'
     * @param string|null $action Filtro acción (agregar_correo, edicion, eliminar)
     * @param string|null $username Filtro por nombre de admin
     * @param string|null $dateFrom Fecha desde (Y-m-d)
     * @param string|null $dateTo Fecha hasta (Y-m-d)
     * @return array { data, total, page, per_page, total_pages }
     */
    public function getPaginated(int $page = 1, int $perPage = 50, string $order = 'desc', ?string $action = null, ?string $username = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $page = max(1, $page);
        $validPerPage = [50, 75, 100, 0];
        $perPage = in_array($perPage, $validPerPage, true) ? $perPage : 50;
        $order = strtolower($order) === 'asc' ? 'ASC' : 'DESC';

        try {
            $db = Database::getConnection();
            $conditions = [];
            $params = [];

            if ($action !== null && $action !== '') {
                $conditions[] = 'action = :action';
                $params[':action'] = $action;
            }
            if ($username !== null && $username !== '') {
                $conditions[] = 'username = :username';
                $params[':username'] = $username;
            }
            if ($dateFrom !== null && $dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
                $conditions[] = 'DATE(created_at) >= :date_from';
                $params[':date_from'] = $dateFrom;
            }
            if ($dateTo !== null && $dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
                $conditions[] = 'DATE(created_at) <= :date_to';
                $params[':date_to'] = $dateTo;
            }

            $whereClause = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';

            $countSql = "SELECT COUNT(*) FROM user_activity_log {$whereClause}";
            $countStmt = $db->prepare($countSql);
            foreach ($params as $k => $v) {
                $countStmt->bindValue($k, $v, PDO::PARAM_STR);
            }
            $countStmt->execute();
            $total = (int) $countStmt->fetchColumn();

            $totalPages = $perPage > 0 ? max(1, (int) ceil($total / $perPage)) : 1;
            $offset = $perPage > 0 ? ($page - 1) * $perPage : 0;
            $limitClause = $perPage > 0 ? 'LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset : '';

            $sql = "
                SELECT id, user_id, username, action, description, created_at
                FROM user_activity_log
                {$whereClause}
                ORDER BY created_at {$order}
                {$limitClause}
            ";
            $stmt = $db->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stmt->execute();
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
     * Lista de usernames únicos para filtro Admin
     */
    public function getUniqueUsernames(): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT DISTINCT username FROM user_activity_log ORDER BY username ASC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return array_column($rows, 'username');
        } catch (\PDOException $e) {
            return [];
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
        ];
        return $labels[$action] ?? $action;
    }
}

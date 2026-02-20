<?php
/**
 * GAC - Repositorio de Roles
 */

namespace Gac\Repositories;

use Gac\Helpers\Database;
use PDO;

class RoleRepository
{
    /**
     * Listar todos los roles (para combos y vistas)
     * @return array [['id','name','display_name','description'], ...]
     */
    public function findAll(): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT id, name, display_name, description FROM roles ORDER BY id ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("RoleRepository::findAll: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener las view_key permitidas para un rol (tabla role_views)
     * @return string[]
     */
    public function getViewKeys(int $roleId): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT view_key FROM role_views WHERE role_id = ? ORDER BY view_key ASC");
            $stmt->execute([$roleId]);
            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $rows ?: [];
        } catch (\PDOException $e) {
            error_log("RoleRepository::getViewKeys: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Guardar las vistas permitidas para un rol (reemplaza las actuales)
     * @param string[] $viewKeys
     */
    public function setViewKeys(int $roleId, array $viewKeys): bool
    {
        try {
            $db = Database::getConnection();
            $db->beginTransaction();
            $db->prepare("DELETE FROM role_views WHERE role_id = ?")->execute([$roleId]);
            $insert = $db->prepare("INSERT INTO role_views (role_id, view_key) VALUES (?, ?)");
            $validKeys = \Gac\Helpers\RoleViewsConfig::keys();
            foreach ($viewKeys as $key) {
                if (in_array($key, $validKeys, true)) {
                    $insert->execute([$roleId, $key]);
                }
            }
            $db->commit();
            return true;
        } catch (\PDOException $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            error_log("RoleRepository::setViewKeys: " . $e->getMessage());
            return false;
        }
    }
}

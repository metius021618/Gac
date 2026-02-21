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
     * Crear un nuevo rol
     * @param string $name Slug único (ej: comprador)
     * @param string $displayName Nombre visible (ej: Comprador)
     * @param string $description Descripción opcional
     * @return int|null ID del rol creado o null en error
     */
    public function create(string $name, string $displayName, string $description = ''): ?int
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO roles (name, display_name, description) VALUES (?, ?, ?)");
            $stmt->execute([$name, $displayName, $description]);
            return (int) $db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("RoleRepository::create: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verificar si existe un rol con el nombre dado
     */
    public function existsByName(string $name): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT 1 FROM roles WHERE name = ? LIMIT 1");
            $stmt->execute([$name]);
            return (bool) $stmt->fetch();
        } catch (\PDOException $e) {
            return false;
        }
    }

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

    /**
     * Obtener acciones permitidas por vista para un rol (tabla role_view_actions)
     * @return array ['view_key' => ['action1','action2'], ...]
     */
    public function getViewActions(int $roleId): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT view_key, action FROM role_view_actions WHERE role_id = ? ORDER BY view_key, action");
            $stmt->execute([$roleId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $result = [];
            foreach ($rows ?: [] as $r) {
                $key = $r['view_key'];
                if (!isset($result[$key])) {
                    $result[$key] = [];
                }
                $result[$key][] = $r['action'];
            }
            return $result;
        } catch (\PDOException $e) {
            error_log("RoleRepository::getViewActions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Guardar acciones por vista para un rol (reemplaza las actuales)
     * @param array $viewActions ['view_key' => ['action1','action2'], ...]
     */
    public function setViewActions(int $roleId, array $viewActions): bool
    {
        try {
            $db = Database::getConnection();
            $db->beginTransaction();
            $db->prepare("DELETE FROM role_view_actions WHERE role_id = ?")->execute([$roleId]);
            $insert = $db->prepare("INSERT INTO role_view_actions (role_id, view_key, action) VALUES (?, ?, ?)");
            foreach ($viewActions as $viewKey => $actions) {
                if (!is_array($actions)) {
                    continue;
                }
                $validKeys = \Gac\Helpers\RoleViewsConfig::keys();
                if (!in_array($viewKey, $validKeys, true)) {
                    continue;
                }
                $availableActions = array_keys(\Gac\Helpers\RoleViewsConfig::getActionsForView($viewKey));
                foreach ($actions as $action) {
                    if (in_array($action, $availableActions, true)) {
                        $insert->execute([$roleId, $viewKey, $action]);
                    }
                }
            }
            $db->commit();
            return true;
        } catch (\PDOException $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            error_log("RoleRepository::setViewActions: " . $e->getMessage());
            return false;
        }
    }
}

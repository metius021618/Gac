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
}

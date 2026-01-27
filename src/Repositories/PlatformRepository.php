<?php
/**
 * GAC - Repositorio de Plataformas
 * 
 * Maneja el acceso a datos de plataformas
 * 
 * @package Gac\Repositories
 */

namespace Gac\Repositories;

use Gac\Helpers\Database;
use PDO;
use PDOException;

class PlatformRepository
{
    /**
     * Obtener todas las plataformas habilitadas
     * 
     * @return array
     */
    public function findAllEnabled(): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT 
                    id,
                    name,
                    display_name,
                    enabled,
                    config
                FROM platforms
                WHERE enabled = 1
                ORDER BY display_name ASC
            ");
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener plataformas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener plataforma por nombre (slug)
     * 
     * @param string $name Nombre/slug de la plataforma
     * @return array|null
     */
    public function findByName(string $name): ?array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT 
                    id,
                    name,
                    display_name,
                    enabled,
                    config
                FROM platforms
                WHERE name = :name
            ");
            
            $stmt->execute(['name' => $name]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error al obtener plataforma por nombre: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener plataforma por ID
     * 
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT 
                    id,
                    name,
                    display_name,
                    enabled,
                    config
                FROM platforms
                WHERE id = :id
            ");
            
            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error al obtener plataforma por ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Contar todas las plataformas
     * 
     * @return int
     */
    public function countAll(): int
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT COUNT(*) FROM platforms");
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error al contar plataformas: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Contar plataformas habilitadas
     * 
     * @return int
     */
    public function countAllEnabled(): int
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT COUNT(*) FROM platforms WHERE enabled = 1");
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error al contar plataformas activas: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener todas las plataformas (con paginación y búsqueda)
     * 
     * @param int $page
     * @param int $perPage
     * @param string $search
     * @return array
     */
    public function searchAndPaginate(int $page = 1, int $perPage = 15, string $search = ''): array
    {
        try {
            $db = Database::getConnection();
            $params = [];
            $whereClause = '';

            if (!empty($search) && trim($search) !== '') {
                $searchTerm = '%' . trim($search) . '%';
                $whereClause = "WHERE (name LIKE :search OR display_name LIKE :search)";
                $params[':search'] = $searchTerm;
            }

            // Contar total de registros
            $countSql = "SELECT COUNT(*) as total FROM platforms {$whereClause}";
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
            
            // Obtener datos paginados
            $sql = "
                SELECT 
                    id,
                    name,
                    display_name,
                    enabled,
                    config,
                    created_at,
                    updated_at
                FROM platforms
                {$whereClause}
                ORDER BY display_name ASC
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
            error_log("Error al buscar y paginar plataformas: " . $e->getMessage());
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

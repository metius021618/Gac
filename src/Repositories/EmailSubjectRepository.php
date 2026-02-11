<?php
/**
 * GAC - Repositorio de Asuntos de Email
 * 
 * Maneja el acceso a datos de asuntos de email por plataforma
 * 
 * @package Gac\Repositories
 */

namespace Gac\Repositories;

use Gac\Helpers\Database;
use PDO;
use PDOException;

class EmailSubjectRepository
{
    /**
     * Buscar y paginar asuntos de email con filtros
     * 
     * @param string $search Término de búsqueda (asunto o plataforma)
     * @param int $page Página actual
     * @param int $perPage Registros por página
     * @return array ['data' => [], 'total' => int, 'page' => int, 'per_page' => int, 'total_pages' => int]
     */
    public function searchAndPaginate(string $search = '', int $page = 1, int $perPage = 15): array
    {
        try {
            $db = Database::getConnection();
            $params = [];
            $whereClause = 'WHERE es.active = 1';

            $searchTrim = trim($search);
            if ($searchTrim !== '') {
                $whereClause .= " AND (LOWER(es.subject_line) LIKE :q OR LOWER(p.display_name) LIKE :q OR LOWER(p.name) LIKE :q)";
                $params[':q'] = '%' . mb_strtolower($searchTrim) . '%';
            }

            // Contar total
            $countSql = "
                SELECT COUNT(*) as total
                FROM email_subjects es
                JOIN platforms p ON es.platform_id = p.id
                {$whereClause}
            ";
            $countStmt = $db->prepare($countSql);
            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    $countStmt->bindValue($key, $value, PDO::PARAM_STR);
                }
            }
            $countStmt->execute();
            $total = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            $logFile = defined('BASE_PATH') ? BASE_PATH . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'email_subjects_search.log' : '';
            if ($logFile) {
                @file_put_contents($logFile, date('Y-m-d H:i:s') . ' [repository] search="' . $searchTrim . '" total=' . $total . "\n", FILE_APPEND | LOCK_EX);
            }

            // Calcular paginación
            $totalPages = $perPage > 0 ? ceil($total / $perPage) : 1;
            $offset = ($page - 1) * $perPage;
            $limitClause = $perPage > 0 ? "LIMIT {$perPage} OFFSET {$offset}" : '';
            
            // Obtener datos paginados
            $sql = "
                SELECT 
                    es.id,
                    es.platform_id,
                    es.subject_line,
                    es.active,
                    es.created_at,
                    es.updated_at,
                    p.name as platform_name,
                    p.display_name as platform_display_name
                FROM email_subjects es
                JOIN platforms p ON es.platform_id = p.id
                {$whereClause}
                ORDER BY es.id DESC
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
            error_log("Error al buscar y paginar asuntos de email: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => 1
            ];
        }
    }

    /**
     * Obtener asunto por ID
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
                    es.id,
                    es.platform_id,
                    es.subject_line,
                    es.active,
                    es.created_at,
                    es.updated_at,
                    p.name as platform_name,
                    p.display_name as platform_display_name
                FROM email_subjects es
                JOIN platforms p ON es.platform_id = p.id
                WHERE es.id = :id AND es.active = 1
            ");
            
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error al obtener asunto por ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Guardar nuevo asunto
     * 
     * @param array $data ['platform_id' => int, 'subject_line' => string]
     * @return int|false ID del asunto creado o false en caso de error
     */
    public function save(array $data): int|false
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO email_subjects (platform_id, subject_line, active)
                VALUES (:platform_id, :subject_line, 1)
            ");
            
            $stmt->execute([
                ':platform_id' => $data['platform_id'],
                ':subject_line' => $data['subject_line']
            ]);
            
            return (int) $db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error al guardar asunto de email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar asunto
     * 
     * @param int $id
     * @param array $data ['platform_id' => int, 'subject_line' => string]
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                UPDATE email_subjects
                SET platform_id = :platform_id,
                    subject_line = :subject_line,
                    updated_at = NOW()
                WHERE id = :id AND active = 1
            ");
            
            return $stmt->execute([
                ':id' => $id,
                ':platform_id' => $data['platform_id'],
                ':subject_line' => $data['subject_line']
            ]);
        } catch (PDOException $e) {
            error_log("Error al actualizar asunto de email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar asunto (soft delete)
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                UPDATE email_subjects
                SET active = 0,
                    updated_at = NOW()
                WHERE id = :id
            ");
            
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error al eliminar asunto de email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Contar todos los asuntos activos
     * 
     * @return int
     */
    public function countAll(): int
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT COUNT(*) as count FROM email_subjects WHERE active = 1");
            return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error al contar asuntos de email: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Verificar si la tabla existe y tiene datos (para debugging)
     * 
     * @return array
     */
    public function debugTable(): array
    {
        try {
            $db = Database::getConnection();
            
            // Verificar si la tabla existe
            $tableCheck = $db->query("SHOW TABLES LIKE 'email_subjects'")->fetch();
            if (!$tableCheck) {
                return ['error' => 'La tabla email_subjects no existe'];
            }
            
            // Contar registros totales
            $totalCount = $db->query("SELECT COUNT(*) as count FROM email_subjects")->fetch(PDO::FETCH_ASSOC);
            
            // Contar registros activos
            $activeCount = $db->query("SELECT COUNT(*) as count FROM email_subjects WHERE active = 1")->fetch(PDO::FETCH_ASSOC);
            
            // Obtener algunos registros de ejemplo
            $sample = $db->query("
                SELECT es.*, p.name as platform_name, p.display_name 
                FROM email_subjects es 
                LEFT JOIN platforms p ON es.platform_id = p.id 
                LIMIT 5
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'table_exists' => true,
                'total_records' => (int) $totalCount['count'],
                'active_records' => (int) $activeCount['count'],
                'sample' => $sample
            ];
        } catch (PDOException $e) {
            error_log("Error en debugTable: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}

<?php
/**
 * GAC - Repositorio de Accesos de Usuario
 * 
 * @package Gac\Repositories
 */

namespace Gac\Repositories;

use Gac\Helpers\Database;
use PDO;
use PDOException;

class UserAccessRepository
{
    /** Último error para poder devolverlo en la respuesta (ej. en modo debug) */
    private static string $lastError = '';

    public static function getLastError(): string
    {
        return self::$lastError;
    }

    /**
     * Crear o actualizar acceso de usuario
     */
    public function createOrUpdate(string $email, string $password, int $platformId): bool
    {
        self::$lastError = '';
        $stmt = null;
        try {
            $db = Database::getConnection();
            $emailNorm = strtolower(trim($email));
            $sql = "
                INSERT INTO user_access (email, password, platform_id, enabled)
                VALUES (:email, :password, :platform_id, 1)
                ON DUPLICATE KEY UPDATE
                    password = :password_update,
                    enabled = 1,
                    updated_at = NOW()
            ";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':email', $emailNorm, PDO::PARAM_STR);
            $stmt->bindValue(':password', $password, PDO::PARAM_STR);
            $stmt->bindValue(':platform_id', $platformId, PDO::PARAM_INT);
            $stmt->bindValue(':password_update', $password, PDO::PARAM_STR);
            $ok = $stmt->execute();
            if (!$ok && $stmt->errorInfo()) {
                $info = $stmt->errorInfo();
                self::$lastError = 'execute failed: ' . json_encode($info);
                self::writeLog('createOrUpdate', $emailNorm, $platformId, self::$lastError);
                error_log("UserAccessRepository::createOrUpdate execute failed: " . json_encode($info));
            }
            return $ok;
        } catch (PDOException $e) {
            $info = $stmt ? $stmt->errorInfo() : [];
            self::$lastError = $e->getMessage() . ' | errorInfo: ' . json_encode($info);
            self::writeLog('createOrUpdate', strtolower(trim($email)), $platformId, self::$lastError);
            error_log("Error al crear/actualizar acceso de usuario: " . self::$lastError);
            return false;
        }
    }

    private static function writeLog(string $action, string $email, int $platformId, string $error): void
    {
        $logFile = defined('BASE_PATH') ? BASE_PATH . '/logs/user_access.log' : '';
        if ($logFile !== '') {
            $line = date('Y-m-d H:i:s') . " [{$action}] email={$email} platform_id={$platformId} " . $error . "\n";
            @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Buscar fila de user_access para un email que sea placeholder OAuth (Gmail/Outlook).
     * Sirve para actualizar esa fila en lugar de crear duplicado cuando el usuario edita.
     */
    public function findOAuthPlaceholderByEmail(string $email): ?array
    {
        try {
            $db = Database::getConnection();
            $sql = "
                SELECT id, email, password, platform_id, enabled, created_at, updated_at
                FROM user_access
                WHERE LOWER(TRIM(email)) = :email AND password IN ('Gmail (OAuth)', 'Outlook (OAuth)') AND enabled = 1
                LIMIT 1
            ";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':email', trim(strtolower($email)), PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error al buscar placeholder OAuth: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar una fila de user_access por ID (usado al "editar" el placeholder OAuth).
     */
    public function updateById(int $id, string $email, string $password, int $platformId): bool
    {
        try {
            $db = Database::getConnection();
            $sql = "
                UPDATE user_access
                SET email = :email, password = :password, platform_id = :platform_id, updated_at = NOW()
                WHERE id = :id
            ";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':email', trim(strtolower($email)), PDO::PARAM_STR);
            $stmt->bindValue(':password', $password, PDO::PARAM_STR);
            $stmt->bindValue(':platform_id', $platformId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al actualizar acceso por ID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Buscar acceso por email y plataforma
     */
    public function findByEmailAndPlatform(string $email, int $platformId): ?array
    {
        try {
            $db = Database::getConnection();
            
            $sql = "
                SELECT id, email, password, platform_id, enabled, created_at, updated_at
                FROM user_access
                WHERE email = :email AND platform_id = :platform_id AND enabled = 1
                LIMIT 1
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->bindValue(':platform_id', $platformId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error al buscar acceso de usuario: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verificar acceso (validar email, password y plataforma)
     */
    public function verifyAccess(string $email, string $password, int $platformId): bool
    {
        $access = $this->findByEmailAndPlatform($email, $platformId);
        
        if (!$access) {
            return false;
        }
        
        // Comparar password (usuario IMAP)
        return $access['password'] === $password;
    }

    /**
     * Total de registros en user_access (para dashboard "Correos registrados")
     */
    public function countAll(): int
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT COUNT(*) FROM user_access");
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error al contar user_access: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Listar/buscar en user_access. Si hay texto, filtra por email o usuario (password).
     */
    public function searchAndPaginate(string $search = '', int $page = 1, int $perPage = 15, array $filterDomains = []): array
    {
        $logFile = defined('BASE_PATH') ? BASE_PATH . '/logs/search_debug.log' : (__DIR__ . '/../../logs/search_debug.log');
        $log = function ($msg) use ($logFile) {
            @file_put_contents($logFile, date('Y-m-d H:i:s') . ' [UserAccessRepository] ' . $msg . "\n", FILE_APPEND | LOCK_EX);
        };
        $log('searchAndPaginate called: search="' . $search . '" page=' . $page . ' perPage=' . $perPage . ' domains=' . implode(',', $filterDomains));
        try {
            $db = Database::getConnection();
            $q = trim($search);
            $conditions = [];
            $params = [];

            if ($q !== '') {
                $term = '%' . $q . '%';
                $conditions[] = "(ua.email LIKE :q_email OR ua.password LIKE :q_password)";
                $params[':q_email'] = $term;
                $params[':q_password'] = $term;
                $log('WHERE applied: q="' . $q . '" term="' . $term . '"');
            } else {
                $log('no search term, listing all');
            }

            // Filtro por dominios
            if (!empty($filterDomains)) {
                $domainConds = [];
                foreach ($filterDomains as $i => $domain) {
                    $key = ':fdomain' . $i;
                    $domainConds[] = "ua.email LIKE {$key}";
                    $params[$key] = '%@' . strtolower(trim($domain));
                }
                $conditions[] = '(' . implode(' OR ', $domainConds) . ')';
                $log('domain filter: ' . implode(', ', $filterDomains));
            }

            // Ocultar cuenta Gmail matriz: no debe aparecer en Correos Registrados
            $conditions[] = "(NOT EXISTS (SELECT 1 FROM gmail_matrix WHERE id = 1) OR ua.email != (SELECT ea.email FROM email_accounts ea INNER JOIN gmail_matrix gm ON gm.email_account_id = ea.id WHERE gm.id = 1 LIMIT 1))";

            $whereClause = 'WHERE ' . implode(' AND ', $conditions);

            // Contar total (tabla user_access)
            $countSql = "
                SELECT COUNT(*) as total
                FROM user_access ua
                LEFT JOIN platforms p ON ua.platform_id = p.id
                {$whereClause}
            ";
            $log('countSql: ' . trim(preg_replace('/\s+/', ' ', $countSql)));
            $countStmt = $db->prepare($countSql);
            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    $countStmt->bindValue($key, $value, PDO::PARAM_STR);
                }
            }
            $countStmt->execute();
            $total = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            $log('count result total=' . $total);
            
            // Calcular paginación
            $totalPages = $perPage > 0 ? ceil($total / $perPage) : 1;
            $offset = ($page - 1) * $perPage;
            $limitClause = $perPage > 0 ? "LIMIT {$perPage} OFFSET {$offset}" : '';
            
            // Obtener datos
            $sql = "
                SELECT 
                    ua.id,
                    ua.email,
                    ua.password,
                    ua.platform_id,
                    ua.enabled,
                    ua.created_at,
                    ua.updated_at,
                    p.name as platform_name,
                    p.display_name as platform_display_name
                FROM user_access ua
                LEFT JOIN platforms p ON ua.platform_id = p.id
                {$whereClause}
                ORDER BY ua.created_at DESC
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
            $log('SELECT returned ' . count($data) . ' rows');
            
            return [
                'data' => $data,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages
            ];
        } catch (PDOException $e) {
            error_log("Error al buscar accesos de usuario: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => 0
            ];
        }
    }

    /**
     * Obtener plataformas asignadas a un email
     */
    public function getPlatformsByEmail(string $email): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT p.display_name as platform_name
                FROM user_access ua
                JOIN platforms p ON ua.platform_id = p.id
                WHERE ua.email = :email AND ua.enabled = 1
                ORDER BY p.display_name ASC
            ");
            $stmt->execute([':email' => $email]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_column($results, 'platform_name');
        } catch (PDOException $e) {
            error_log("Error al obtener plataformas por email: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crear múltiples accesos de usuario masivamente
     * 
     * @param array $emails Array de correos electrónicos
     * @param string $password Contraseña/acceso común para todos
     * @param int $platformId ID de la plataforma
     * @return array ['success' => int, 'duplicates' => int, 'errors' => array]
     */
    public function bulkCreate(array $emails, string $password, int $platformId): array
    {
        $success = 0;
        $duplicates = 0;
        $errors = [];
        
        try {
            $db = Database::getConnection();
            $db->beginTransaction();
            
            $sql = "
                INSERT INTO user_access (email, password, platform_id, enabled)
                VALUES (:email, :password, :platform_id, 1)
                ON DUPLICATE KEY UPDATE
                    password = :password_update,
                    enabled = 1,
                    updated_at = NOW()
            ";
            
            $stmt = $db->prepare($sql);
            
            foreach ($emails as $email) {
                $email = trim($email);
                if (empty($email)) {
                    continue;
                }
                
                try {
                    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
                    $stmt->bindValue(':password', $password, PDO::PARAM_STR);
                    $stmt->bindValue(':platform_id', $platformId, PDO::PARAM_INT);
                    $stmt->bindValue(':password_update', $password, PDO::PARAM_STR);
                    
                    if ($stmt->execute()) {
                        // Verificar si fue insert o update
                        if ($stmt->rowCount() > 0) {
                            $success++;
                        } else {
                            $duplicates++;
                        }
                    }
                } catch (PDOException $e) {
                    $errors[] = "Error con {$email}: " . $e->getMessage();
                }
            }
            
            $db->commit();
            
            return [
                'success' => $success,
                'duplicates' => $duplicates,
                'errors' => $errors
            ];
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Error al crear accesos masivamente: " . $e->getMessage());
            return [
                'success' => 0,
                'duplicates' => 0,
                'errors' => ['Error general: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Obtener el email de un registro por ID (para poder borrar también de email_accounts si corresponde).
     */
    public function getEmailById(int $id): ?string
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT email FROM user_access WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? trim($row['email']) : null;
        } catch (PDOException $e) {
            error_log("Error getEmailById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener emails de varios registros por IDs.
     * @return array [email => true, ...] (sin duplicados)
     */
    public function getEmailsByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        try {
            $db = Database::getConnection();
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("SELECT DISTINCT email FROM user_access WHERE id IN ({$placeholders})");
            $stmt->execute(array_values($ids));
            $out = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $out[trim($row['email'])] = true;
            }
            return $out;
        } catch (PDOException $e) {
            error_log("Error getEmailsByIds: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Eliminar acceso
     */
    public function delete(int $id): bool
    {
        try {
            $db = Database::getConnection();
            
            $sql = "DELETE FROM user_access WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al eliminar acceso de usuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar acceso por email
     *
     * @param string $email
     * @return bool
     */
    public function deleteByEmail(string $email): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM user_access WHERE email = :email");
            return $stmt->execute(['email' => strtolower(trim($email))]);
        } catch (PDOException $e) {
            error_log("Error al eliminar acceso por email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar acceso por email y plataforma específica
     *
     * @param string $email
     * @param int $platformId
     * @return bool
     */
    public function deleteByEmailAndPlatform(string $email, int $platformId): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM user_access WHERE email = :email AND platform_id = :platform_id");
            return $stmt->execute([
                'email' => strtolower(trim($email)),
                'platform_id' => $platformId
            ]);
        } catch (PDOException $e) {
            error_log("Error al eliminar acceso por email y plataforma: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Contar cuántas asignaciones tiene un email en user_access
     *
     * @param string $email
     * @return int
     */
    public function countByEmail(string $email): int
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM user_access WHERE email = :email");
            $stmt->execute(['email' => strtolower(trim($email))]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error al contar accesos por email: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Contar correos por dominio(s)
     */
    public function countByDomains(array $domains): int
    {
        if (empty($domains)) return 0;
        try {
            $db = Database::getConnection();
            $conditions = [];
            $params = [];
            foreach ($domains as $i => $domain) {
                $key = 'domain' . $i;
                $conditions[] = "email LIKE :{$key}";
                $params[$key] = '%@' . strtolower(trim($domain));
            }
            $sql = "SELECT COUNT(DISTINCT email) FROM user_access WHERE " . implode(' OR ', $conditions);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error al contar por dominios: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Eliminar múltiples accesos por ID
     */
    public function bulkDelete(array $ids): bool
    {
        if (empty($ids)) {
            return false;
        }
        try {
            $db = Database::getConnection();
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("DELETE FROM user_access WHERE id IN ({$placeholders})");
            return $stmt->execute(array_values($ids));
        } catch (PDOException $e) {
            error_log("Error al eliminar accesos en lote: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambiar estado enabled de un acceso
     */
    public function toggleEnabled(int $id, bool $enabled): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE user_access SET enabled = :enabled, updated_at = NOW() WHERE id = :id");
            return $stmt->execute([':id' => $id, ':enabled' => $enabled ? 1 : 0]);
        } catch (PDOException $e) {
            error_log("Error al cambiar estado de acceso: " . $e->getMessage());
            return false;
        }
    }
}

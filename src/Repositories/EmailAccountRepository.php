    /**
     * Buscar y paginar cuentas de email con filtros
     * 
     * @param string $search Término de búsqueda (email o usuario)
     * @param int $page Página actual
     * @param int $perPage Registros por página
     * @return array ['data' => [], 'total' => int, 'page' => int, 'per_page' => int, 'total_pages' => int]
     */
    public function searchAndPaginate(string $search = '', int $page = 1, int $perPage = 15): array
    {
        try {
            $db = Database::getConnection();
            
            // Construir WHERE clause
            $whereClause = '';
            $params = [];
            
            if (!empty($search) && trim($search) !== '') {
                $searchTerm = '%' . trim($search) . '%';
                $whereClause = "WHERE (email LIKE :search OR JSON_UNQUOTE(JSON_EXTRACT(provider_config, '$.imap_user')) LIKE :search)";
                $params[':search'] = $searchTerm;
            }
            
            // Contar total de registros
            $countSql = "SELECT COUNT(*) as total FROM email_accounts {$whereClause}";
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
                    email,
                    type,
                    provider_config,
                    enabled,
                    last_sync_at,
                    sync_status,
                    error_message,
                    created_at
                FROM email_accounts
                {$whereClause}
                ORDER BY created_at DESC
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
            
            // Parsear provider_config para cada registro
            foreach ($data as &$row) {
                $config = json_decode($row['provider_config'] ?? '{}', true);
                $row['imap_server'] = $config['imap_server'] ?? '';
                $row['imap_port'] = $config['imap_port'] ?? 993;
                $row['imap_user'] = $config['imap_user'] ?? '';
                $row['imap_password'] = ''; // No exponer contraseñas
            }
            
            return [
                'data' => $data,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages
            ];
        } catch (PDOException $e) {
            error_log("Error al buscar y paginar cuentas de email: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => 0
            ];
        }
    }

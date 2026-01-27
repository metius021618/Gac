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

<?php
/**
 * GAC - Repositorio de Análisis (dashboard corporativo)
 * Datos desde user_access: cuentas asignadas por fecha (created_at), revendedor (updated_by_username).
 *
 * @package Gac\Repositories
 */

namespace Gac\Repositories;

use Gac\Helpers\Database;
use PDO;
use PDOException;

class AnalisisRepository
{
    /**
     * Construir cláusula WHERE y params para user_access según filtros de análisis.
     * @param array $filters ['date_from'=>Y-m-d|null, 'date_to'=>Y-m-d|null, 'platform_id'=>int|null, 'revendedor'=>string|null]
     * @return array{where: string, params: array}
     */
    private function buildWhereFromFilters(array $filters): array
    {
        $conditions = ["1=1"];
        $params = [];
        if (!empty($filters['date_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_from'])) {
            $conditions[] = "DATE(ua.created_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_to'])) {
            $conditions[] = "DATE(ua.created_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        if (!empty($filters['platform_id']) && (int) $filters['platform_id'] > 0) {
            $conditions[] = "ua.platform_id = :platform_id";
            $params[':platform_id'] = (int) $filters['platform_id'];
        }
        if (isset($filters['revendedor']) && $filters['revendedor'] !== '' && $this->columnExists('user_access', 'updated_by_username')) {
            $conditions[] = "COALESCE(ua.updated_by_username, '') = :revendedor";
            $params[':revendedor'] = $filters['revendedor'];
        }
        return ['where' => implode(' AND ', $conditions), 'params' => $params];
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
            $stmt->execute([$column]);
            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Total cuentas vendidas = COUNT user_access (cuentas asignadas). Aplicar filtros.
     * @return array{total: int, crecimiento: float}
     */
    public function getTotalCuentasKpi(array $filters = []): array
    {
        try {
            $db = Database::getConnection();
            $built = $this->buildWhereFromFilters($filters);
            $sql = "SELECT COUNT(*) AS total FROM user_access ua WHERE {$built['where']}";
            $stmt = $db->prepare($sql);
            foreach ($built['params'] as $k => $v) {
                $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $total = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            return ['total' => $total, 'crecimiento' => 0];
        } catch (\Throwable $e) {
            return ['total' => 0, 'crecimiento' => 0];
        }
    }

    /**
     * Número de plataformas activas (habilitadas).
     */
    public function getPlataformasActivasCount(): int
    {
        try {
            $platformRepo = new PlatformRepository();
            $list = $platformRepo->findAllEnabled();
            return count($list) ?: 4;
        } catch (\Throwable $e) {
            return 4;
        }
    }

    /**
     * Revendedor del mes: usuario con más cuentas asignadas en el mes actual (user_access, created_at).
     * @return array{nombre: string, foto_url: string|null, cuentas: int}
     */
    public function getRevendedorDelMes(): array
    {
        try {
            $db = Database::getConnection();
            if (!$this->columnExists('user_access', 'updated_by_username')) {
                return ['nombre' => '—', 'foto_url' => null, 'cuentas' => 0];
            }
            $stmt = $db->query("
                SELECT COALESCE(ua.updated_by_username, 'Sin asignar') AS nombre, COUNT(*) AS cuentas
                FROM user_access ua
                WHERE YEAR(ua.created_at) = YEAR(CURDATE()) AND MONTH(ua.created_at) = MONTH(CURDATE())
                GROUP BY COALESCE(ua.updated_by_username, '')
                ORDER BY cuentas DESC
                LIMIT 1
            ");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return [
                    'nombre' => $row['nombre'],
                    'foto_url' => null,
                    'cuentas' => (int) $row['cuentas'],
                ];
            }
        } catch (PDOException $e) {
            // fallback
        }
        return ['nombre' => '—', 'foto_url' => null, 'cuentas' => 0];
    }

    /**
     * Total ingresos (mantener para card comentado).
     * @return array{total: float, crecimiento: float}
     */
    public function getTotalIngresosKpi(): array
    {
        return ['total' => 103420.0, 'crecimiento' => 12.4];
    }

    /**
     * Evolución por día: cuentas asignadas por día (user_access.created_at). Un punto por día en el rango.
     * @return array{labels: string[], values: int[]}
     */
    public function getEvolucionPorDia(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        if (!$dateFrom || !$dateTo || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $dateTo = date('Y-m-d');
            $dateFrom = date('Y-m-d', strtotime('-30 days'));
        }
        if (strtotime($dateFrom) > strtotime($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }
        try {
            $db = Database::getConnection();
            $built = $this->buildWhereFromFilters(array_merge($filters, ['date_from' => $dateFrom, 'date_to' => $dateTo]));
            $sql = "
                SELECT DATE(ua.created_at) AS dia, COUNT(*) AS total
                FROM user_access ua
                WHERE {$built['where']}
                GROUP BY DATE(ua.created_at)
                ORDER BY dia ASC
            ";
            $stmt = $db->prepare($sql);
            foreach ($built['params'] as $k => $v) {
                $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $byDay = [];
            foreach ($rows as $r) {
                $byDay[$r['dia']] = (int) $r['total'];
            }
            $labels = [];
            $values = [];
            $current = strtotime($dateFrom);
            $end = strtotime($dateTo);
            $months = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
            while ($current <= $end) {
                $d = date('Y-m-d', $current);
                $dayNum = (int) date('j', $current);
                $monthShort = $months[(int)date('n', $current) - 1];
                $labels[] = $monthShort . ' ' . $dayNum;
                $values[] = $byDay[$d] ?? 0;
                $current = strtotime('+1 day', $current);
            }
            return ['labels' => $labels, 'values' => $values];
        } catch (\Throwable $e) {
            return ['labels' => [], 'values' => []];
        }
    }

    /**
     * Alias para vista: evolución mensual usa ahora evolución por día con mismo formato labels/values.
     */
    public function getEvolucionMensual(array $filters = []): array
    {
        return $this->getEvolucionPorDia($filters);
    }

    /**
     * Ventas por plataforma = COUNT user_access por platform_id (cuentas asignadas por plataforma).
     * @return array<array{nombre: string, total: int, color: string}>
     */
    public function getVentasPorPlataforma(array $filters = []): array
    {
        $colors = ['Netflix' => '#E50914', 'Disney+' => '#1F80E0', 'HBO Max' => '#8B5CF6', 'Spotify' => '#1DB954'];
        try {
            $db = Database::getConnection();
            $built = $this->buildWhereFromFilters($filters);
            $sql = "
                SELECT p.display_name AS nombre, COUNT(ua.id) AS total
                FROM user_access ua
                INNER JOIN platforms p ON p.id = ua.platform_id
                WHERE {$built['where']}
                GROUP BY ua.platform_id, p.display_name
                ORDER BY total DESC
            ";
            $stmt = $db->prepare($sql);
            foreach ($built['params'] as $k => $v) {
                $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $r) {
                $nombre = $r['nombre'] ?? 'Otro';
                $out[] = ['nombre' => $nombre, 'total' => (int) $r['total'], 'color' => $colors[$nombre] ?? '#334155'];
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Ranking de revendedores = usuarios (updated_by_username) con cantidad de cuentas asignadas.
     * @return array<array{nombre: string, foto_url: string|null, total: int, rank: int}>
     */
    public function getRankingRevendedores(array $filters = []): array
    {
        try {
            $db = Database::getConnection();
            if (!$this->columnExists('user_access', 'updated_by_username')) {
                return [];
            }
            $built = $this->buildWhereFromFilters($filters);
            $sql = "
                SELECT COALESCE(ua.updated_by_username, 'Sin asignar') AS nombre, COUNT(*) AS total
                FROM user_access ua
                WHERE {$built['where']}
                GROUP BY COALESCE(ua.updated_by_username, '')
                HAVING nombre != ''
                ORDER BY total DESC
                LIMIT 6
            ";
            $stmt = $db->prepare($sql);
            foreach ($built['params'] as $k => $v) {
                $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = [];
            $rank = 1;
            foreach ($rows as $r) {
                $out[] = [
                    'nombre' => $r['nombre'],
                    'foto_url' => null,
                    'total' => (int) $r['total'],
                    'rank' => $rank++,
                ];
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Heatmap: revendedor (usuario) x plataforma. Conteo por usuario y plataforma desde user_access.
     * @return array{revendedores: string[], plataformas: string[], matrix: int[][]}
     */
    public function getHeatmapPlataformaRevendedor(array $filters = []): array
    {
        try {
            $db = Database::getConnection();
            if (!$this->columnExists('user_access', 'updated_by_username')) {
                return ['revendedores' => [], 'plataformas' => [], 'matrix' => []];
            }
            $built = $this->buildWhereFromFilters($filters);
            $sql = "
                SELECT COALESCE(ua.updated_by_username, 'Sin asignar') AS revendedor, p.display_name AS plataforma, COUNT(ua.id) AS total
                FROM user_access ua
                INNER JOIN platforms p ON p.id = ua.platform_id
                WHERE {$built['where']}
                GROUP BY COALESCE(ua.updated_by_username, ''), ua.platform_id, p.display_name
            ";
            $stmt = $db->prepare($sql);
            foreach ($built['params'] as $k => $v) {
                $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $revendedores = [];
            $plataformas = [];
            $byRev = [];
            foreach ($rows as $r) {
                $rev = $r['revendedor'];
                $plat = $r['plataforma'];
                if ($rev !== '' && !in_array($rev, $revendedores, true)) {
                    $revendedores[] = $rev;
                }
                if (!in_array($plat, $plataformas, true)) {
                    $plataformas[] = $plat;
                }
                if (!isset($byRev[$rev])) {
                    $byRev[$rev] = [];
                }
                $byRev[$rev][$plat] = (int) $r['total'];
            }
            $matrix = [];
            foreach ($revendedores as $rev) {
                $row = [];
                foreach ($plataformas as $plat) {
                    $row[] = $byRev[$rev][$plat] ?? 0;
                }
                $matrix[] = $row;
            }
            return ['revendedores' => $revendedores, 'plataformas' => $plataformas, 'matrix' => $matrix];
        } catch (\Throwable $e) {
            return ['revendedores' => [], 'plataformas' => [], 'matrix' => []];
        }
    }

    /**
     * Plataformas disponibles para el filtro (con registros en user_access o todas habilitadas).
     * @return array<array{id: int, display_name: string}>
     */
    public function getPlatformsForFilter(): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("
                SELECT DISTINCT p.id, p.display_name
                FROM user_access ua
                INNER JOIN platforms p ON p.id = ua.platform_id
                ORDER BY p.display_name ASC
            ");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($rows)) {
                $prepo = new PlatformRepository();
                $list = $prepo->findAllEnabled();
                return array_map(function ($p) {
                    return ['id' => (int) $p['id'], 'display_name' => $p['display_name'] ?? $p['name'] ?? ''];
                }, $list);
            }
            return array_map(function ($r) {
                return ['id' => (int) $r['id'], 'display_name' => $r['display_name'] ?? ''];
            }, $rows);
        } catch (\Throwable $e) {
            $prepo = new PlatformRepository();
            $list = $prepo->findAllEnabled();
            return array_map(function ($p) {
                return ['id' => (int) $p['id'], 'display_name' => $p['display_name'] ?? $p['name'] ?? ''];
            }, $list);
        }
    }

    /**
     * Usuarios (revendedores) disponibles para el filtro: distinct updated_by_username en user_access.
     * @return string[]
     */
    public function getRevendedoresForFilter(): array
    {
        try {
            if (!$this->columnExists('user_access', 'updated_by_username')) {
                return [];
            }
            $db = Database::getConnection();
            $stmt = $db->query("
                SELECT DISTINCT COALESCE(updated_by_username, '') AS u
                FROM user_access
                WHERE COALESCE(updated_by_username, '') != ''
                ORDER BY u ASC
            ");
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'u');
        } catch (\Throwable $e) {
            return [];
        }
    }
}

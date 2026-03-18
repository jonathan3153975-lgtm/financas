<?php declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Movement Model
 */
class Movement extends Model
{
    protected string $table = 'movimentacoes';

    // ----------------------------------------------------------------
    // Queries
    // ----------------------------------------------------------------

    /**
     * Fetch movements for a user with optional filters.
     *
     * @param array<string,mixed> $filters
     * @return array{data: array<int,array<string,mixed>>, total: int, pages: int, page: int, limit: int}
     */
    public function findByUser(int $userId, array $filters = []): array
    {
        $where  = ['m.usuario_id = :uid'];
        $params = ['uid' => $userId];

        if (!empty($filters['mes']) && !empty($filters['ano'])) {
            $where[]           = 'MONTH(m.data_competencia) = :mes AND YEAR(m.data_competencia) = :ano';
            $params['mes']     = (int) $filters['mes'];
            $params['ano']     = (int) $filters['ano'];
        }

        if (!empty($filters['tipo'])) {
            $where[]         = 'm.tipo = :tipo';
            $params['tipo']  = $filters['tipo'];
        }

        if (!empty($filters['categoria_id'])) {
            $where[]              = 'm.categoria_id = :cat';
            $params['cat']        = (int) $filters['categoria_id'];
        }

        if (isset($filters['validado']) && $filters['validado'] !== '') {
            $where[]                = 'm.validado = :validado';
            $params['validado']     = (int) $filters['validado'];
        }

        if (!empty($filters['search'])) {
            $where[]              = 'm.descricao LIKE :search';
            $params['search']     = '%' . $filters['search'] . '%';
        }

        $whereStr = implode(' AND ', $where);
        $page     = max(1, (int) ($filters['page'] ?? 1));
        $limit    = (int) ($filters['limit'] ?? 20);
        $offset   = ($page - 1) * $limit;

        $countSql = "SELECT COUNT(*) AS total
                     FROM `{$this->table}` m
                     WHERE {$whereStr}";

        $total = (int) ($this->db->fetch($countSql, $params)['total'] ?? 0);
        $pages = (int) ceil($total / $limit);

        $sql = "SELECT m.*,
                       c.nome AS categoria_nome, c.cor AS categoria_cor, c.icone AS categoria_icone,
                       s.nome AS subcategoria_nome
                FROM `{$this->table}` m
                LEFT JOIN categorias   c ON c.id = m.categoria_id
                LEFT JOIN subcategorias s ON s.id = m.subcategoria_id
                WHERE {$whereStr}
                ORDER BY m.data_competencia DESC, m.id DESC
                LIMIT {$limit} OFFSET {$offset}";

        return [
            'data'  => $this->db->fetchAll($sql, $params),
            'total' => $total,
            'pages' => $pages,
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    /**
     * Sum of validated + pending movements by type for a given month.
     */
    public function getTotalByType(int $userId, string $tipo, int $mes, int $ano): float
    {
        $row = $this->db->fetch(
            "SELECT COALESCE(SUM(valor), 0) AS total
             FROM `{$this->table}`
             WHERE usuario_id = ? AND tipo = ?
               AND MONTH(data_competencia) = ? AND YEAR(data_competencia) = ?",
            [$userId, $tipo, $mes, $ano]
        );
        return (float) ($row['total'] ?? 0);
    }

    /**
     * Monthly comparison: returns array of {mes, ano, total_entrada, total_saida} for last N months.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getMonthlyComparison(int $userId, int $months = 6): array
    {
        return $this->db->fetchAll(
            "SELECT
                MONTH(data_competencia)  AS mes,
                YEAR(data_competencia)   AS ano,
                SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END) AS total_entrada,
                SUM(CASE WHEN tipo = 'saida'   THEN valor ELSE 0 END) AS total_saida
             FROM `{$this->table}`
             WHERE usuario_id = ?
               AND data_competencia >= DATE_SUB(CURDATE(), INTERVAL {$months} MONTH)
             GROUP BY YEAR(data_competencia), MONTH(data_competencia)
             ORDER BY YEAR(data_competencia) ASC, MONTH(data_competencia) ASC",
            [$userId]
        );
    }

    /**
     * Count pending (not validated) movements for the current month.
     */
    public function getPendingCount(int $userId): int
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS cnt
             FROM `{$this->table}`
             WHERE usuario_id = ? AND validado = 0
               AND MONTH(data_competencia) = MONTH(CURDATE())
               AND YEAR(data_competencia)  = YEAR(CURDATE())",
            [$userId]
        );
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Count pending by type (entrada / saida) for the current month.
     *
     * @return array{entrada: int, saida: int}
     */
    public function getPendingCountByType(int $userId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT tipo, COUNT(*) AS cnt
             FROM `{$this->table}`
             WHERE usuario_id = ? AND validado = 0
               AND MONTH(data_competencia) = MONTH(CURDATE())
               AND YEAR(data_competencia)  = YEAR(CURDATE())
             GROUP BY tipo",
            [$userId]
        );
        $result = ['entrada' => 0, 'saida' => 0];
        foreach ($rows as $r) {
            $result[$r['tipo']] = (int) $r['cnt'];
        }
        return $result;
    }

    /**
     * Mark a movement as validated.
     */
    public function validate(int $id): bool
    {
        return $this->db->execute(
            "UPDATE `{$this->table}` SET `validado` = 1 WHERE `id` = ?",
            [$id]
        ) > 0;
    }

    /**
     * Revert a movement back to pending.
     */
    public function revert(int $id): bool
    {
        return $this->db->execute(
            "UPDATE `{$this->table}` SET `validado` = 0 WHERE `id` = ?",
            [$id]
        ) > 0;
    }

    /**
     * Total validated balance (all time).
     */
    public function getTotalBalance(int $userId): float
    {
        $row = $this->db->fetch(
            "SELECT
                COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE -valor END), 0) AS saldo
             FROM `{$this->table}`
             WHERE usuario_id = ? AND validado = 1",
            [$userId]
        );
        return (float) ($row['saldo'] ?? 0);
    }

    /**
     * Balance for a specific month (validated + pending).
     */
    public function getMonthBalance(int $userId, int $mes, int $ano): float
    {
        $row = $this->db->fetch(
            "SELECT
                COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE -valor END), 0) AS saldo
             FROM `{$this->table}`
             WHERE usuario_id = ?
               AND MONTH(data_competencia) = ? AND YEAR(data_competencia) = ?",
            [$userId, $mes, $ano]
        );
        return (float) ($row['saldo'] ?? 0);
    }

    /**
     * Expense by category for the given month (for charts).
     *
     * @return array<int,array<string,mixed>>
     */
    public function getExpenseByCategory(int $userId, int $mes, int $ano): array
    {
        return $this->db->fetchAll(
            "SELECT c.nome, c.cor, COALESCE(SUM(m.valor), 0) AS total
             FROM `{$this->table}` m
             JOIN categorias c ON c.id = m.categoria_id
             WHERE m.usuario_id = ? AND m.tipo = 'saida'
               AND MONTH(m.data_competencia) = ? AND YEAR(m.data_competencia) = ?
             GROUP BY c.id, c.nome, c.cor
             ORDER BY total DESC",
            [$userId, $mes, $ano]
        );
    }

    /**
     * Last N movements (for dashboard recent list).
     *
     * @return array<int,array<string,mixed>>
     */
    public function getRecent(int $userId, int $limit = 10): array
    {
        return $this->db->fetchAll(
            "SELECT m.*,
                    c.nome AS categoria_nome, c.cor AS categoria_cor, c.icone AS categoria_icone
             FROM `{$this->table}` m
             LEFT JOIN categorias c ON c.id = m.categoria_id
             WHERE m.usuario_id = ?
             ORDER BY m.created_at DESC
             LIMIT {$limit}",
            [$userId]
        );
    }

    /**
     * Cash-flow by day for current month.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getCashFlow(int $userId, int $mes, int $ano): array
    {
        return $this->db->fetchAll(
            "SELECT DAY(data_competencia) AS dia,
                    SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END) AS entradas,
                    SUM(CASE WHEN tipo = 'saida'   THEN valor ELSE 0 END) AS saidas
             FROM `{$this->table}`
             WHERE usuario_id = ?
               AND MONTH(data_competencia) = ? AND YEAR(data_competencia) = ?
             GROUP BY DAY(data_competencia)
             ORDER BY dia ASC",
            [$userId, $mes, $ano]
        );
    }
}

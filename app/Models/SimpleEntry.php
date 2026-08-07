<?php declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * SimpleEntry - independent simple input/output records.
 */
class SimpleEntry extends Model
{
    protected string $table = 'modulo_simples_lancamentos';

    /**
     * @param array<string,mixed> $filters
     * @return array{data: array<int,array<string,mixed>>, total: int, pages: int, page: int, limit: int}
     */
    public function findByUser(int $userId, array $filters = []): array
    {
        $where  = ['usuario_id = :uid'];
        $params = ['uid' => $userId];

        if (!empty($filters['mes']) && !empty($filters['ano'])) {
            $where[] = 'MONTH(data_referencia) = :mes AND YEAR(data_referencia) = :ano';
            $params['mes'] = (int) $filters['mes'];
            $params['ano'] = (int) $filters['ano'];
        }

        if (!empty($filters['tipo'])) {
            $where[] = 'tipo = :tipo';
            $params['tipo'] = $filters['tipo'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(descricao LIKE :search OR observacao LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereStr = implode(' AND ', $where);
        $page     = max(1, (int) ($filters['page'] ?? 1));
        $limit    = max(1, (int) ($filters['limit'] ?? 20));
        $offset   = ($page - 1) * $limit;

        $count = $this->db->fetch(
            "SELECT COUNT(*) AS total FROM `{$this->table}` WHERE {$whereStr}",
            $params
        );

        $total = (int) ($count['total'] ?? 0);
        $pages = max(1, (int) ceil($total / $limit));

        $data = $this->db->fetchAll(
            "SELECT *
             FROM `{$this->table}`
             WHERE {$whereStr}
             ORDER BY data_referencia DESC, id DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );

        return [
            'data'  => $data,
            'total' => $total,
            'pages' => $pages,
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{entrada: float, saida: float, saldo: float}
     */
    public function getTotalsForFilter(int $userId, array $filters): array
    {
        $where  = ['usuario_id = :uid'];
        $params = ['uid' => $userId];

        if (!empty($filters['mes']) && !empty($filters['ano'])) {
            $where[] = 'MONTH(data_referencia) = :mes AND YEAR(data_referencia) = :ano';
            $params['mes'] = (int) $filters['mes'];
            $params['ano'] = (int) $filters['ano'];
        }

        if (!empty($filters['tipo'])) {
            $where[] = 'tipo = :tipo';
            $params['tipo'] = $filters['tipo'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(descricao LIKE :search OR observacao LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $row = $this->db->fetch(
            "SELECT
                COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END), 0) AS total_entrada,
                COALESCE(SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END), 0) AS total_saida,
                COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE -valor END), 0) AS saldo
             FROM `{$this->table}`
             WHERE " . implode(' AND ', $where),
            $params
        );

        return [
            'entrada' => (float) ($row['total_entrada'] ?? 0),
            'saida'   => (float) ($row['total_saida'] ?? 0),
            'saldo'   => (float) ($row['saldo'] ?? 0),
        ];
    }

    public function getAccumulatedBalance(int $userId): float
    {
        $row = $this->db->fetch(
            "SELECT COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE -valor END), 0) AS saldo
             FROM `{$this->table}`
             WHERE usuario_id = ?",
            [$userId]
        );

        return (float) ($row['saldo'] ?? 0);
    }
}

<?php declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * BetProspect - levantamento de possíveis entradas (apostas ainda não realizadas).
 */
class BetProspect extends Model
{
    protected string $table = 'apostas_prospectos';

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findByUser(int $userId, ?string $dataInicio = null, ?string $dataFim = null): array
    {
        $where  = "p.usuario_id = ?";
        $params = [$userId];

        if ($dataInicio !== null && $dataFim !== null && $dataInicio !== '' && $dataFim !== '') {
            $where .= " AND DATE(p.data_hora) BETWEEN ? AND ?";
            $params[] = $dataInicio;
            $params[] = $dataFim;
        }

        return $this->db->fetchAll(
            "SELECT p.*, c.nome AS categoria_nome, a.status AS aposta_status
             FROM `{$this->table}` p
             LEFT JOIN `apostas_categorias` c ON c.id = p.categoria_id
             LEFT JOIN `apostas` a ON a.id = p.aposta_id
             WHERE {$where}
             ORDER BY p.data_hora ASC",
            $params
        );
    }

    /**
     * Vincula prospectos a uma aposta recém-criada (marca como convertidos).
     *
     * @param array<int,int> $ids
     */
    public function linkToBet(array $ids, int $betId, int $userId): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids), fn($id) => $id > 0));
        if (empty($ids)) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));

        $this->db->execute(
            "UPDATE `{$this->table}` SET `aposta_id` = ? WHERE `id` IN ({$placeholders}) AND `usuario_id` = ?",
            [$betId, ...$ids, $userId]
        );
    }
}

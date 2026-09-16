<?php declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * BetSelection - seleções de uma aposta múltipla
 */
class BetSelection extends Model
{
    protected string $table = 'apostas_selecoes';

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findByBet(int $betId): array
    {
        return $this->db->fetchAll(
            "SELECT s.*, c.nome AS categoria_nome
             FROM `{$this->table}` s
             LEFT JOIN `apostas_categorias` c ON c.id = s.categoria_id
             WHERE s.aposta_id = ?
             ORDER BY s.id ASC",
            [$betId]
        );
    }

    public function deleteByBet(int $betId): int
    {
        return $this->db->execute(
            "DELETE FROM `{$this->table}` WHERE `aposta_id` = ?",
            [$betId]
        );
    }
}

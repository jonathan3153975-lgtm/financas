<?php declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * CardMovement Model
 */
class CardMovement extends Model
{
    protected string $table = 'movimentacoes_cartao';

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findByCard(int $cardId): array
    {
        return $this->db->fetchAll(
            "SELECT mc.*,
                    c.nome AS categoria_nome,
                    s.nome AS subcategoria_nome
             FROM `{$this->table}` mc
             LEFT JOIN categorias    c ON c.id = mc.categoria_id
             LEFT JOIN subcategorias s ON s.id = mc.subcategoria_id
             WHERE mc.cartao_id = ?
             ORDER BY mc.data_compra DESC",
            [$cardId]
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findByUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT mc.*,
                    cc.nome_cartao,
                    c.nome AS categoria_nome
             FROM `{$this->table}` mc
             JOIN cartoes_credito cc ON cc.id = mc.cartao_id
             LEFT JOIN categorias  c ON c.id  = mc.categoria_id
             WHERE mc.usuario_id = ?
             ORDER BY mc.data_compra DESC",
            [$userId]
        );
    }
}

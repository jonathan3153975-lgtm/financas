<?php declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * BetCategory - categorias de apostas (Futebol, Tênis, etc.)
 */
class BetCategory extends Model
{
    protected string $table = 'apostas_categorias';

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findActive(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `{$this->table}` WHERE `ativo` = 1 ORDER BY `nome`"
        );
    }
}

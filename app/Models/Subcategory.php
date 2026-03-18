<?php declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Subcategory Model
 */
class Subcategory extends Model
{
    protected string $table = 'subcategorias';

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findByCategory(int $categoriaId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `{$this->table}` WHERE `categoria_id` = ? AND `ativo` = 1 ORDER BY `nome` ASC",
            [$categoriaId]
        );
    }
}

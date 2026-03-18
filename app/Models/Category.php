<?php declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Category Model
 */
class Category extends Model
{
    protected string $table = 'categorias';

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findByType(string $tipo): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `{$this->table}` WHERE `tipo` = ? AND `ativo` = 1 ORDER BY `nome` ASC",
            [$tipo]
        );
    }

    /**
     * Get all active categories with their subcategories nested.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getAllWithSubcategories(): array
    {
        $categories = $this->db->fetchAll(
            "SELECT * FROM `{$this->table}` WHERE `ativo` = 1 ORDER BY `tipo` ASC, `nome` ASC"
        );

        $subs = $this->db->fetchAll(
            "SELECT * FROM `subcategorias` WHERE `ativo` = 1 ORDER BY `nome` ASC"
        );

        // Index subcategories by categoria_id
        $subMap = [];
        foreach ($subs as $sub) {
            $subMap[(int) $sub['categoria_id']][] = $sub;
        }

        foreach ($categories as &$cat) {
            $cat['subcategorias'] = $subMap[(int) $cat['id']] ?? [];
        }
        unset($cat);

        return $categories;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getByTypeWithSubcategories(string $tipo): array
    {
        $all = $this->getAllWithSubcategories();
        return array_values(array_filter($all, fn($c) => $c['tipo'] === $tipo));
    }
}

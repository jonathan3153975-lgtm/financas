<?php declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Plan Model
 */
class Plan extends Model
{
    protected string $table = 'planos';

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getActive(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `{$this->table}` WHERE `ativo` = 1 ORDER BY `preco` ASC"
        );
    }

    /**
     * Decode the recursos JSON column.
     *
     * @param array<string,mixed> $plan
     * @return array<string,mixed>
     */
    public function decodeResources(array $plan): array
    {
        if (isset($plan['recursos']) && is_string($plan['recursos'])) {
            $plan['recursos'] = json_decode($plan['recursos'], true) ?? [];
        }
        return $plan;
    }
}

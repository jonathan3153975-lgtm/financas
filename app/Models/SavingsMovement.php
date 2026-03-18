<?php declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * SavingsMovement Model — poupanca_movimentos table
 */
class SavingsMovement extends Model
{
    protected string $table = 'poupanca_movimentos';

    /**
     * Return all movements for a savings account, ordered newest first.
     *
     * @return array<int,array<string,mixed>>
     */
    public function findByAccount(int $poupancaId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `{$this->table}` WHERE poupanca_id = ? ORDER BY data DESC, created_at DESC",
            [$poupancaId]
        );
    }
}

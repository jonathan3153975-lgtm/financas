<?php declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Savings Model — poupancas table
 */
class Savings extends Model
{
    protected string $table = 'poupancas';

    /**
     * Return all active savings accounts for a user, each with computed saldo_atual.
     *
     * @return array<int,array<string,mixed>>
     */
    public function findByUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT p.*,
                    COALESCE(p.saldo_inicial, 0)
                    + COALESCE((SELECT SUM(valor) FROM poupanca_movimentos WHERE poupanca_id = p.id AND tipo = 'deposito'), 0)
                    - COALESCE((SELECT SUM(valor) FROM poupanca_movimentos WHERE poupanca_id = p.id AND tipo = 'saque'), 0)
                    AS saldo_atual
             FROM `{$this->table}` p
             WHERE p.usuario_id = ? AND p.ativo = 1
             ORDER BY p.nome ASC",
            [$userId]
        );
    }

    /**
     * Compute the current balance for a single savings account.
     */
    public function getBalance(int $id): float
    {
        $row = $this->db->fetch(
            "SELECT COALESCE(saldo_inicial, 0)
                    + COALESCE((SELECT SUM(valor) FROM poupanca_movimentos WHERE poupanca_id = ? AND tipo = 'deposito'), 0)
                    - COALESCE((SELECT SUM(valor) FROM poupanca_movimentos WHERE poupanca_id = ? AND tipo = 'saque'), 0)
                    AS saldo
             FROM `{$this->table}` WHERE id = ?",
            [$id, $id, $id]
        );
        return (float) ($row['saldo'] ?? 0);
    }
}

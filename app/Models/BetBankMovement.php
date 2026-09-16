<?php declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * BetBankMovement - entradas e saques da banca de apostas
 */
class BetBankMovement extends Model
{
    protected string $table = 'apostas_banca_movimentos';

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findByUser(int $userId, ?int $mes = null, ?int $ano = null): array
    {
        $sql    = "SELECT * FROM `{$this->table}` WHERE `usuario_id` = ?";
        $params = [$userId];

        if ($mes !== null && $ano !== null) {
            $sql .= " AND MONTH(`data`) = ? AND YEAR(`data`) = ?";
            $params[] = $mes;
            $params[] = $ano;
        }

        $sql .= " ORDER BY `data` DESC, `id` DESC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Saldo atual da banca (entradas - saques), acumulado desde o início.
     */
    public function getBalance(int $userId): float
    {
        $row = $this->db->fetch(
            "SELECT
                COALESCE(SUM(CASE WHEN `tipo` = 'entrada' THEN `valor` ELSE 0 END), 0) AS entradas,
                COALESCE(SUM(CASE WHEN `tipo` = 'saque'   THEN `valor` ELSE 0 END), 0) AS saques
             FROM `{$this->table}`
             WHERE `usuario_id` = ?",
            [$userId]
        );

        return (float) ($row['entradas'] ?? 0) - (float) ($row['saques'] ?? 0);
    }
}

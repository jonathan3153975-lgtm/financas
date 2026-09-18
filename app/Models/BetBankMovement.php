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

    /**
     * Saldo da banca (entradas - saques) acumulado antes de uma data.
     * Usado como "saldo inicial" real de um período.
     */
    public function getBalanceBefore(int $userId, string $date): float
    {
        $row = $this->db->fetch(
            "SELECT
                COALESCE(SUM(CASE WHEN `tipo` = 'entrada' THEN `valor` ELSE 0 END), 0) AS entradas,
                COALESCE(SUM(CASE WHEN `tipo` = 'saque'   THEN `valor` ELSE 0 END), 0) AS saques
             FROM `{$this->table}`
             WHERE `usuario_id` = ? AND `data` < ?",
            [$userId, $date]
        );

        return (float) ($row['entradas'] ?? 0) - (float) ($row['saques'] ?? 0);
    }

    /**
     * Totais de depósitos (entradas) e saques dentro de um intervalo de datas.
     *
     * @return array{entradas: float, saques: float}
     */
    public function getMovementsSummary(int $userId, string $inicio, string $fim): array
    {
        $row = $this->db->fetch(
            "SELECT
                COALESCE(SUM(CASE WHEN `tipo` = 'entrada' THEN `valor` ELSE 0 END), 0) AS entradas,
                COALESCE(SUM(CASE WHEN `tipo` = 'saque'   THEN `valor` ELSE 0 END), 0) AS saques
             FROM `{$this->table}`
             WHERE `usuario_id` = ? AND `data` BETWEEN ? AND ?",
            [$userId, $inicio, $fim]
        ) ?? [];

        return [
            'entradas' => (float) ($row['entradas'] ?? 0),
            'saques'   => (float) ($row['saques'] ?? 0),
        ];
    }

    /**
     * Totais de depósitos/saques por mês, centrados em um mês/ano de referência
     * (ex.: 2 meses antes e 2 meses depois, quando existirem).
     *
     * @return array<int,array{mes:int, ano:int, entradas:float, saques:float, saldo:float}>
     */
    public function getMonthlyTotals(int $userId, int $centerMes, int $centerAno, int $range = 2): array
    {
        $result = [];

        for ($offset = -$range; $offset <= $range; $offset++) {
            $ts  = mktime(0, 0, 0, $centerMes + $offset, 1, $centerAno);
            $mes = (int) date('n', $ts);
            $ano = (int) date('Y', $ts);

            $row = $this->db->fetch(
                "SELECT
                    COALESCE(SUM(CASE WHEN `tipo` = 'entrada' THEN `valor` ELSE 0 END), 0) AS entradas,
                    COALESCE(SUM(CASE WHEN `tipo` = 'saque'   THEN `valor` ELSE 0 END), 0) AS saques
                 FROM `{$this->table}`
                 WHERE `usuario_id` = ? AND MONTH(`data`) = ? AND YEAR(`data`) = ?",
                [$userId, $mes, $ano]
            );

            $entradas = (float) ($row['entradas'] ?? 0);
            $saques   = (float) ($row['saques'] ?? 0);

            $result[] = [
                'mes'      => $mes,
                'ano'      => $ano,
                'entradas' => $entradas,
                'saques'   => $saques,
                'saldo'    => $entradas - $saques,
            ];
        }

        return $result;
    }
}

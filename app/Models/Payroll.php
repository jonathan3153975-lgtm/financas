<?php declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Payroll Model
 */
class Payroll extends Model
{
    protected string $table = 'folha_pagamento';

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findByUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `{$this->table}` WHERE `usuario_id` = ? ORDER BY `ano_referencia` DESC, `mes_referencia` DESC",
            [$userId]
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findByMonth(int $userId, int $mes, int $ano): ?array
    {
        $row = $this->db->fetch(
            "SELECT * FROM `{$this->table}`
             WHERE `usuario_id` = ? AND `mes_referencia` = ? AND `ano_referencia` = ?
             LIMIT 1",
            [$userId, $mes, $ano]
        );

        if ($row === null) {
            return null;
        }

        // Attach items
        $row['itens'] = $this->getItems((int) $row['id']);
        return $row;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getItems(int $folhaId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `folha_itens` WHERE `folha_id` = ? ORDER BY `tipo` ASC, `descricao` ASC",
            [$folhaId]
        );
    }

    /**
     * Calculate net salary from gross and an array of deductions.
     *
     * @param array<string,float> $descontos  ['INSS' => 500.00, 'IR' => 200.00, ...]
     */
    public function calculateLiquid(float $bruto, array $descontos): float
    {
        $totalDescontos = array_sum(array_values($descontos));
        return round($bruto - $totalDescontos, 2);
    }

    /**
     * Create a payroll with its line items in a transaction.
     *
     * @param array<string,mixed> $data
     * @param array<int,array{descricao:string,tipo:string,valor:float}> $itens
     */
    public function createWithItems(array $data, array $itens): int
    {
        $this->db->beginTransaction();

        try {
            $folhaId = $this->create($data);

            foreach ($itens as $item) {
                $this->db->execute(
                    "INSERT INTO `folha_itens` (`folha_id`, `descricao`, `tipo`, `valor`) VALUES (?, ?, ?, ?)",
                    [$folhaId, $item['descricao'], $item['tipo'], $item['valor']]
                );
            }

            $this->db->commit();
            return $folhaId;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Delete payroll and its items (items cascade via FK).
     */
    public function deleteWithItems(int $id): void
    {
        $this->delete($id);
    }

    /**
     * Upsert a payroll preview entrada movement for $mes/$ano.
     * Uses the most recent payroll entry as the value reference.
     * Skips if there's already a real (non-preview) payroll movement for the month.
     */
    public function generateMovementForMonth(int $userId, int $mes, int $ano): void
    {
        // Already have a real payroll entry for this month → its movement is handled by PayrollController
        $existing = $this->db->fetch(
            "SELECT id FROM `{$this->table}` WHERE usuario_id = ? AND mes_referencia = ? AND ano_referencia = ?",
            [$userId, $mes, $ano]
        );
        if ($existing !== null) return;

        // Get the most recent payroll entry to use as reference value
        $last = $this->db->fetch(
            "SELECT * FROM `{$this->table}` WHERE usuario_id = ?
             ORDER BY ano_referencia DESC, mes_referencia DESC LIMIT 1",
            [$userId]
        );
        if ($last === null) return;

        $cat = $this->db->fetch(
            "SELECT id FROM categorias WHERE tipo = 'receita' AND nome LIKE '%alário%' LIMIT 1"
        );
        if (!$cat) {
            $cat = $this->db->fetch("SELECT id FROM categorias WHERE tipo = 'receita' LIMIT 1");
        }
        $catId    = $cat ? (int) $cat['id'] : null;
        $valor    = (float) $last['valor_liquido'];
        $dataComp = sprintf('%04d-%02d-01', $ano, $mes);
        $descricao = $last['descricao'] . ' (' . $mes . '/' . $ano . ')';

        // Upsert: update valor if preview already exists
        $movExists = $this->db->fetch(
            "SELECT id FROM movimentacoes WHERE usuario_id = ? AND data_competencia = ?
             AND observacao LIKE '%[PREVIEW_FOLHA]%'",
            [$userId, $dataComp]
        );

        if ($movExists !== null) {
            $this->db->execute(
                "UPDATE movimentacoes SET valor = ?, descricao = ?, categoria_id = ? WHERE id = ?",
                [$valor, $descricao, $catId, (int) $movExists['id']]
            );
        } else {
            $this->db->execute(
                "INSERT INTO movimentacoes
                    (usuario_id, descricao, tipo, modo, categoria_id, valor, data_competencia, data_vencimento, parcela_atual, total_parcelas, validado, observacao)
                 VALUES (?, ?, 'entrada', 'unico', ?, ?, ?, ?, 1, 1, 0, ?)",
                [$userId, $descricao, $catId, $valor, $dataComp, $dataComp,
                 '[PREVIEW_FOLHA] Previsão de salário gerada automaticamente']
            );
        }
    }
}

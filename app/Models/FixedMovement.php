<?php declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * FixedMovement Model
 */
class FixedMovement extends Model
{
    protected string $table = 'movimentacoes_fixas';

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findByUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT mf.*,
                    c.nome AS categoria_nome, c.cor AS categoria_cor,
                    s.nome AS subcategoria_nome
             FROM `{$this->table}` mf
             LEFT JOIN categorias    c ON c.id = mf.categoria_id
             LEFT JOIN subcategorias s ON s.id = mf.subcategoria_id
             WHERE mf.usuario_id = ?
             ORDER BY mf.tipo ASC, mf.descricao ASC",
            [$userId]
        );
    }

    /**
     * Generate movements for a given month from the fixed records,
     * skipping ones already generated (checks by descricao + modo=fixo + data_competencia).
     *
     * Returns the number of movements created.
     */
    public function generateForMonth(int $userId, int $mes, int $ano): int
    {
        $fixas  = $this->db->fetchAll(
            "SELECT * FROM `{$this->table}` WHERE usuario_id = ? AND ativo = 1",
            [$userId]
        );

        $created = 0;

        foreach ($fixas as $fixa) {
            // Check data_fim — skip if this month is past the end date
            if (!empty($fixa['data_fim'])) {
                $fimTs  = mktime(0, 0, 0, (int)date('m', strtotime($fixa['data_fim'])),
                                           1, (int)date('Y', strtotime($fixa['data_fim'])));
                $mesTs  = mktime(0, 0, 0, $mes, 1, $ano);
                if ($mesTs > $fimTs) {
                    continue;
                }
            }

            // Build the due date
            $day  = (int) $fixa['dia_vencimento'];
            // Ensure day is valid for the month
            $maxDay = (int) date('t', mktime(0, 0, 0, $mes, 1, $ano));
            $day    = min($day, $maxDay);
            $dataComp = sprintf('%04d-%02d-%02d', $ano, $mes, $day);

            // Check if already exists
            $exists = $this->db->fetch(
                "SELECT id FROM movimentacoes
                 WHERE usuario_id = ? AND modo = 'fixo' AND descricao = ?
                   AND data_competencia = ?",
                [$userId, $fixa['descricao'], $dataComp]
            );

            if ($exists !== null) {
                continue;
            }

            $this->db->execute(
                "INSERT INTO movimentacoes
                    (usuario_id, descricao, tipo, modo, categoria_id, subcategoria_id, valor, data_competencia, data_vencimento, validado, observacao)
                 VALUES (?, ?, ?, 'fixo', ?, ?, ?, ?, ?, 0, ?)",
                [
                    $userId,
                    $fixa['descricao'],
                    $fixa['tipo'],
                    $fixa['categoria_id'],
                    $fixa['subcategoria_id'],
                    $fixa['valor'],
                    $dataComp,
                    $dataComp,
                    $fixa['observacao'],
                ]
            );

            $created++;
        }

        return $created;
    }

    public function toggleActive(int $id): void
    {
        $this->db->execute(
            "UPDATE `{$this->table}` SET `ativo` = 1 - `ativo` WHERE `id` = ?",
            [$id]
        );
    }
}

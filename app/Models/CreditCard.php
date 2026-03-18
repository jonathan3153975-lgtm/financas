<?php declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * CreditCard Model
 */
class CreditCard extends Model
{
    protected string $table = 'cartoes_credito';

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findByUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `{$this->table}` WHERE `usuario_id` = ? AND `ativo` = 1 ORDER BY `nome_cartao` ASC",
            [$userId]
        );
    }

    /**
     * Movements for a card in a given billing cycle (mes/ano).
     *
     * @return array<int,array<string,mixed>>
     */
    public function getMovements(int $cardId, int $mes, int $ano): array
    {
        return $this->db->fetchAll(
            "SELECT mc.*,
                    c.nome AS categoria_nome, c.cor AS categoria_cor,
                    s.nome AS subcategoria_nome
             FROM movimentacoes_cartao mc
             LEFT JOIN categorias    c ON c.id = mc.categoria_id
             LEFT JOIN subcategorias s ON s.id = mc.subcategoria_id
             WHERE mc.cartao_id = ?
               AND MONTH(mc.data_compra) = ? AND YEAR(mc.data_compra) = ?
             ORDER BY mc.data_compra ASC",
            [$cardId, $mes, $ano]
        );
    }

    /**
     * Total spending for a card in a given cycle.
     */
    public function getMonthTotal(int $cardId, int $mes, int $ano): float
    {
        $row = $this->db->fetch(
            "SELECT COALESCE(SUM(valor), 0) AS total
             FROM movimentacoes_cartao
             WHERE cartao_id = ? AND MONTH(data_compra) = ? AND YEAR(data_compra) = ?",
            [$cardId, $mes, $ano]
        );
        return (float) ($row['total'] ?? 0);
    }

    /**
     * Close the month for a card: marks all movements as pago = 1
     * and returns total paid.
     */
    public function closeMonth(int $cardId, int $mes, int $ano): float
    {
        $total = $this->getMonthTotal($cardId, $mes, $ano);

        $this->db->execute(
            "UPDATE movimentacoes_cartao
             SET pago = 1
             WHERE cartao_id = ? AND MONTH(data_compra) = ? AND YEAR(data_compra) = ? AND pago = 0",
            [$cardId, $mes, $ano]
        );

        return $total;
    }

    /**
     * Upsert a "Fatura [card name]" saída preview movement for every active card
     * for the given $mes/$ano. Creates even when no purchases exist yet (uses last
     * month as estimate). Updates the amount if it has changed since last generation.
     */
    public function generateBillsForMonth(int $userId, int $mes, int $ano): void
    {
        $cards = $this->findByUser($userId);

        $cat = $this->db->fetch(
            "SELECT id FROM categorias WHERE tipo = 'despesa' AND nome LIKE '%inanceiro%' LIMIT 1"
        );
        if (!$cat) {
            $cat = $this->db->fetch("SELECT id FROM categorias WHERE tipo = 'despesa' LIMIT 1");
        }
        $catId = $cat ? (int) $cat['id'] : null;

        foreach ($cards as $card) {
            $cardId  = (int) $card['id'];
            $diaVenc = min((int) $card['dia_vencimento'], (int) date('t', mktime(0, 0, 0, $mes, 1, $ano)));
            $diaFech = (int) $card['dia_fechamento'];
            $dataVenc  = sprintf('%04d-%02d-%02d', $ano, $mes, $diaVenc);
            $descricao = 'Fatura ' . $card['nome_cartao'];

            // Billing cycle: [prevMes/diaFech .. mes/diaFech]
            $prevMes     = $mes === 1 ? 12 : $mes - 1;
            $prevAno     = $mes === 1 ? $ano - 1 : $ano;
            $diaFechPrev = min($diaFech, (int) date('t', mktime(0, 0, 0, $prevMes, 1, $prevAno)));
            $diaFechCurr = min($diaFech, (int) date('t', mktime(0, 0, 0, $mes, 1, $ano)));
            $dateFrom    = sprintf('%04d-%02d-%02d', $prevAno, $prevMes, $diaFechPrev);
            $dateTo      = sprintf('%04d-%02d-%02d', $ano, $mes, $diaFechCurr);

            // Current billing cycle total
            $row   = $this->db->fetch(
                "SELECT COALESCE(SUM(valor), 0) AS total FROM movimentacoes_cartao
                 WHERE cartao_id = ? AND data_compra > ? AND data_compra <= ?",
                [$cardId, $dateFrom, $dateTo]
            );
            $total = (float) ($row['total'] ?? 0);

            // If no purchases in current cycle yet, estimate from previous cycle
            if ($total <= 0) {
                $prevFechPrev = min($diaFech, (int) date('t', mktime(0, 0, 0,
                    ($prevMes === 1 ? 12 : $prevMes - 1), 1,
                    ($prevMes === 1 ? $prevAno - 1 : $prevAno))));
                $prevDateFrom = sprintf('%04d-%02d-%02d',
                    ($prevMes === 1 ? $prevAno - 1 : $prevAno),
                    ($prevMes === 1 ? 12 : $prevMes - 1),
                    $prevFechPrev);
                $estRow = $this->db->fetch(
                    "SELECT COALESCE(SUM(valor), 0) AS total FROM movimentacoes_cartao
                     WHERE cartao_id = ? AND data_compra > ? AND data_compra <= ?",
                    [$cardId, $prevDateFrom, $dateFrom]
                );
                $total = (float) ($estRow['total'] ?? 0);
            }

            // Upsert: update if preview already exists, else insert
            $exists = $this->db->fetch(
                "SELECT id FROM movimentacoes
                 WHERE usuario_id = ? AND descricao = ? AND data_competencia = ?
                   AND observacao LIKE '%[PREVIEW_CARTAO]%'",
                [$userId, $descricao, $dataVenc]
            );

            if ($exists !== null) {
                $this->db->execute(
                    "UPDATE movimentacoes SET valor = ?, categoria_id = ? WHERE id = ?",
                    [$total, $catId, (int) $exists['id']]
                );
            } else {
                $this->db->execute(
                    "INSERT INTO movimentacoes
                        (usuario_id, descricao, tipo, modo, categoria_id, valor, data_competencia, data_vencimento, parcela_atual, total_parcelas, validado, observacao)
                     VALUES (?, ?, 'saida', 'unico', ?, ?, ?, ?, 1, 1, 0, ?)",
                    [$userId, $descricao, $catId, $total, $dataVenc, $dataVenc,
                     '[PREVIEW_CARTAO] Previsão de fatura gerada automaticamente']
                );
            }
        }
    }

    /**
     * Remaining limit for a card (limit - sum of current month unpaid movements).
     */
    public function getAvailableLimit(int $cardId): float
    {
        $card = $this->find($cardId);
        if ($card === null) {
            return 0.0;
        }

        $mes = (int) date('m');
        $ano = (int) date('Y');
        $used = $this->getMonthTotal($cardId, $mes, $ano);

        return max(0.0, (float) $card['limite'] - $used);
    }
}

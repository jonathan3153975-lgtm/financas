<?php declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;
use RuntimeException;

/**
 * InstallmentDebt Model
 */
class InstallmentDebt extends Model
{
    protected string $table = 'dividas_parceladas';

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findByUser(int $userId, bool $onlyActive = false): array
    {
        $where = $onlyActive ? 'AND d.ativo = 1 AND d.parcelas_pagas < d.total_parcelas' : '';

        return $this->db->fetchAll(
            "SELECT d.*,
                    (d.total_parcelas - d.parcelas_pagas) AS parcelas_abertas
             FROM `{$this->table}` d
             WHERE d.usuario_id = ? {$where}
             ORDER BY d.ativo DESC, d.updated_at DESC, d.id DESC",
            [$userId]
        );
    }

    public function getTotalOutstanding(int $userId): float
    {
        $row = $this->db->fetch(
            "SELECT COALESCE(SUM(saldo_devedor), 0) AS total
             FROM `{$this->table}`
             WHERE usuario_id = ? AND ativo = 1 AND parcelas_pagas < total_parcelas",
            [$userId]
        );

        return (float) ($row['total'] ?? 0);
    }

    public function getOpenCount(int $userId): int
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS total
             FROM `{$this->table}`
             WHERE usuario_id = ? AND ativo = 1 AND parcelas_pagas < total_parcelas",
            [$userId]
        );

        return (int) ($row['total'] ?? 0);
    }

    public function getMonthlyPaid(int $userId, int $mes, int $ano): float
    {
        $row = $this->db->fetch(
            "SELECT COALESCE(SUM(valor), 0) AS total
             FROM movimentacoes
             WHERE usuario_id = ?
               AND tipo = 'saida'
               AND observacao LIKE '[DIVIDA_ID:%'
               AND MONTH(data_competencia) = ?
               AND YEAR(data_competencia) = ?",
            [$userId, $mes, $ano]
        );

        return (float) ($row['total'] ?? 0);
    }

    /**
     * @return array{labels: array<int,string>, payments: array<int,float>, totals: array<int,float>, paidCumulative: array<int,float>, grossReduction: float}
     */
    public function getReductionSeries(int $userId, int $months = 8): array
    {
        $labels = [];
        $keys   = [];
        $paymentsByKey = [];
        $newDebtByKey  = [];

        $start = new \DateTimeImmutable('first day of this month');
        $start = $start->modify('-' . max(0, $months - 1) . ' months');

        for ($i = 0; $i < $months; $i++) {
            $d = $start->modify('+' . $i . ' months');
            $key = $d->format('Y-m');
            $keys[] = $key;
            $labels[] = $d->format('m/Y');
            $paymentsByKey[$key] = 0.0;
            $newDebtByKey[$key] = 0.0;
        }

        $rows = $this->db->fetchAll(
            "SELECT DATE_FORMAT(data_competencia, '%Y-%m') AS ym, COALESCE(SUM(valor), 0) AS total
             FROM movimentacoes
             WHERE usuario_id = ?
               AND tipo = 'saida'
               AND observacao LIKE '[DIVIDA_ID:%'
               AND data_competencia >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL ? MONTH), '%Y-%m-01')
             GROUP BY ym",
            [$userId, $months + 1]
        );

        foreach ($rows as $row) {
            $key = (string) $row['ym'];
            if (array_key_exists($key, $paymentsByKey)) {
                $paymentsByKey[$key] = (float) $row['total'];
            }
        }

        $debts = $this->db->fetchAll(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COALESCE(SUM(saldo_inicial), 0) AS total
             FROM `{$this->table}`
             WHERE usuario_id = ?
               AND created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL ? MONTH), '%Y-%m-01')
             GROUP BY ym",
            [$userId, $months + 1]
        );

        foreach ($debts as $row) {
            $key = (string) $row['ym'];
            if (array_key_exists($key, $newDebtByKey)) {
                $newDebtByKey[$key] = (float) $row['total'];
            }
        }

        $payments = [];
        foreach ($keys as $k) {
            $payments[] = $paymentsByKey[$k];
        }

        $totals = array_fill(0, count($keys), 0.0);
        $running = $this->getTotalOutstanding($userId);

        for ($i = count($keys) - 1; $i >= 0; $i--) {
            $key = $keys[$i];
            $totals[$i] = round($running, 2);
            $running += $paymentsByKey[$key];
            $running -= $newDebtByKey[$key];
        }

        $grossReduction = max(0, $totals[0] - $totals[count($totals) - 1]);

        $paidCumulative = [];
        $runningPaid = 0.0;
        foreach ($keys as $k) {
            $runningPaid += $paymentsByKey[$k];
            $paidCumulative[] = round($runningPaid, 2);
        }

        return [
            'labels' => $labels,
            'payments' => $payments,
            'totals' => $totals,
            'paidCumulative' => $paidCumulative,
            'grossReduction' => round($grossReduction, 2),
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findOpenForPeriod(int $userId, int $mes, int $ano): array
    {
        $period = sprintf('%04d-%02d', $ano, $mes);

        return $this->db->fetchAll(
            "SELECT d.*,
                    (d.total_parcelas - d.parcelas_pagas) AS parcelas_abertas,
                    EXISTS(
                        SELECT 1
                        FROM movimentacoes m
                        WHERE m.usuario_id = d.usuario_id
                          AND m.observacao LIKE CONCAT('[DIVIDA_ID:', d.id, ']%')
                          AND DATE_FORMAT(m.data_competencia, '%Y-%m') = ?
                    ) AS ja_lancada
             FROM `{$this->table}` d
             WHERE d.usuario_id = ?
               AND d.ativo = 1
               AND d.parcelas_pagas < d.total_parcelas
             ORDER BY d.updated_at DESC, d.id DESC",
            [$period, $userId]
        );
    }

    public function createDebt(int $userId, array $data): int
    {
        $saldoInicial = (float) $data['valor_parcela'] * ((int) $data['total_parcelas'] - (int) $data['parcelas_pagas']);

        return $this->create([
            'usuario_id'      => $userId,
            'descricao'       => (string) $data['descricao'],
            'valor_parcela'   => (float) $data['valor_parcela'],
            'total_parcelas'  => (int) $data['total_parcelas'],
            'parcelas_pagas'  => (int) $data['parcelas_pagas'],
            'data_inicio'     => $data['data_inicio'] ?? null,
            'dia_vencimento'  => (int) ($data['dia_vencimento'] ?? 1),
            'saldo_inicial'   => round(max(0, $saldoInicial), 2),
            'saldo_devedor'   => round(max(0, $saldoInicial), 2),
            'ativo'           => ((int) $data['parcelas_pagas'] < (int) $data['total_parcelas']) ? 1 : 0,
        ]);
    }

    /**
     * Gera previews de parcelas em movimentacoes a partir do período definido.
     * Cada parcela fica com validado=0 (pendente) até o usuário efetivar.
     */
    public function generateInstallmentPreviews(int $userId, int $debtId): void
    {
        $debt = $this->db->fetch(
            "SELECT * FROM `{$this->table}` WHERE id = ? AND usuario_id = ?",
            [$debtId, $userId]
        );

        if ($debt === null || $debt['data_inicio'] === null) {
            return;
        }

        $totalParcelas  = (int) $debt['total_parcelas'];
        $parcelasPagas  = (int) $debt['parcelas_pagas'];
        $valorParcela   = (float) $debt['valor_parcela'];
        $diaVencimento  = max(1, min(28, (int) $debt['dia_vencimento']));
        $catId          = $this->findOrCreateDebtCategoryId();

        // data_inicio armazenada como YYYY-MM-DD (dia sempre 01 ou o próprio dia)
        $startDate = new \DateTimeImmutable(
            date('Y-m-01', strtotime((string) $debt['data_inicio']))
        );

        for ($parcela = $parcelasPagas + 1; $parcela <= $totalParcelas; $parcela++) {
            $offset = $parcela - $parcelasPagas - 1;
            $monthDate = $startDate->modify("+{$offset} months");

            $year  = (int) $monthDate->format('Y');
            $month = (int) $monthDate->format('m');
            // respeita limite de dias do mês (ex: fevereiro)
            $maxDay = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
            $day    = min($diaVencimento, $maxDay);

            $dataCompetencia = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $periodo         = sprintf('%04d-%02d', $year, $month);

            // ignora se já existe lançamento para essa dívida nesse período
            $dup = $this->db->fetch(
                "SELECT id FROM movimentacoes
                 WHERE usuario_id = ?
                   AND observacao LIKE ?
                   AND DATE_FORMAT(data_competencia, '%Y-%m') = ?
                 LIMIT 1",
                [$userId, '[DIVIDA_ID:' . $debtId . ']%', $periodo]
            );

            if ($dup !== null) {
                continue;
            }

            $this->db->execute(
                "INSERT INTO movimentacoes
                    (usuario_id, descricao, tipo, modo, categoria_id, subcategoria_id,
                     valor, data_competencia, data_vencimento,
                     parcela_atual, total_parcelas, validado, observacao)
                 VALUES (?, ?, 'saida', 'parcelamento', ?, NULL, ?, ?, ?, ?, ?, 0, ?)",
                [
                    $userId,
                    'Parcela dívida: ' . $debt['descricao'],
                    $catId,
                    $valorParcela,
                    $dataCompetencia,
                    $dataCompetencia,
                    $parcela,
                    $totalParcelas,
                    '[DIVIDA_ID:' . $debtId . '][PARCELA:' . $parcela . '] Previsão automática',
                ]
            );
        }
    }

    public function findOrCreateDebtCategoryId(): ?int
    {
        $cat = $this->db->fetch(
            "SELECT id
             FROM categorias
             WHERE tipo = 'despesa' AND nome = 'Dívidas parceladas'
             LIMIT 1"
        );

        if ($cat !== null) {
            return (int) $cat['id'];
        }

        $this->db->execute(
            "INSERT INTO categorias (nome, tipo, icone, cor, ativo)
             VALUES ('Dívidas parceladas', 'despesa', 'fa-hand-holding-dollar', '#0ea5e9', 1)"
        );

        return $this->db->lastInsertId();
    }

    public function registerPaymentInMovements(int $userId, int $debtId, string $dataCompetencia): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $debt = $db->fetch(
                "SELECT * FROM `{$this->table}` WHERE id = ? AND usuario_id = ? FOR UPDATE",
                [$debtId, $userId]
            );

            if ($debt === null) {
                throw new RuntimeException('Dívida não encontrada.');
            }

            $totalParcelas = (int) $debt['total_parcelas'];
            $pagas = (int) $debt['parcelas_pagas'];

            if ($pagas >= $totalParcelas || (int) $debt['ativo'] === 0) {
                throw new RuntimeException('Dívida já está quitada.');
            }

                        $period = date('Y-m', strtotime($dataCompetencia));
            $dup = $db->fetch(
                "SELECT id
                 FROM movimentacoes
                 WHERE usuario_id = ?
                   AND observacao LIKE CONCAT('[DIVIDA_ID:', ?, ']%')
                   AND DATE_FORMAT(data_competencia, '%Y-%m') = ?
                 LIMIT 1",
                [$userId, $debtId, $period]
            );

            if ($dup !== null) {
                throw new RuntimeException('Essa dívida já foi lançada no período selecionado.');
            }

            $nextParcela = $pagas + 1;
            $valorParcela = (float) $debt['valor_parcela'];
            $catId = $this->findOrCreateDebtCategoryId();

            $db->execute(
                "INSERT INTO movimentacoes
                    (usuario_id, descricao, tipo, modo, categoria_id, subcategoria_id, valor, data_competencia, data_vencimento,
                     parcela_atual, total_parcelas, validado, observacao)
                 VALUES
                    (?, ?, 'saida', 'parcelamento', ?, NULL, ?, ?, ?, ?, ?, 0, ?)",
                [
                    $userId,
                    'Parcela dívida: ' . $debt['descricao'],
                    $catId,
                    $valorParcela,
                    $dataCompetencia,
                    $dataCompetencia,
                    $nextParcela,
                    $totalParcelas,
                    '[DIVIDA_ID:' . $debtId . '][PARCELA:' . $nextParcela . '] Lançamento automático da dívida parcelada'
                ]
            );

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * @return array{economia: float, juros: float}
     */
    public function getTotalSavings(int $userId): array
    {
        $row = $this->db->fetch(
            "SELECT COALESCE(SUM(total_economia), 0) AS economia,
                    COALESCE(SUM(total_juros),    0) AS juros
             FROM `{$this->table}`
             WHERE usuario_id = ?",
            [$userId]
        );
        return [
            'economia' => (float) ($row['economia'] ?? 0),
            'juros'    => (float) ($row['juros']    ?? 0),
        ];
    }

    public function syncDebtOnMovementStatusChange(int $userId, int $movementId, bool $toValidated): bool
    {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $movement = $db->fetch(
                "SELECT id, usuario_id, validado, observacao, valor
                 FROM movimentacoes
                 WHERE id = ? AND usuario_id = ?
                 FOR UPDATE",
                [$movementId, $userId]
            );

            if ($movement === null) {
                $db->rollback();
                return false;
            }

            $debtId = $this->extractDebtIdFromObservation((string) ($movement['observacao'] ?? ''));
            if ($debtId === null) {
                $db->rollback();
                return false;
            }

            $currentValidated = (int) $movement['validado'] === 1;
            if ($currentValidated === $toValidated) {
                $db->commit();
                return true;
            }

            $debt = $db->fetch(
                "SELECT id, parcelas_pagas, total_parcelas, valor_parcela
                 FROM `{$this->table}`
                 WHERE id = ? AND usuario_id = ?
                 FOR UPDATE",
                [$debtId, $userId]
            );

            if ($debt === null) {
                throw new RuntimeException('Dívida vinculada não encontrada para esta movimentação.');
            }

            $parcelasPagas = (int) $debt['parcelas_pagas'];
            $totalParcelas = (int) $debt['total_parcelas'];
            $valorParcela  = (float) $debt['valor_parcela'];
            $valorPago     = (float) $movement['valor'];
            // diferença positiva = desconto (economia), negativa = juros
            $diferenca = round($valorParcela - $valorPago, 2);

            if ($toValidated) {
                if ($parcelasPagas >= $totalParcelas) {
                    throw new RuntimeException('Esta dívida já está quitada.');
                }
                $parcelasPagas++;
            } else {
                if ($parcelasPagas <= 0) {
                    throw new RuntimeException('Não há parcelas pagas para reverter.');
                }
                $parcelasPagas--;
            }

            $saldoDevedor = max(0, ($totalParcelas - $parcelasPagas) * $valorParcela);
            $ativo = $parcelasPagas < $totalParcelas ? 1 : 0;

            $db->execute(
                "UPDATE movimentacoes SET validado = ? WHERE id = ?",
                [$toValidated ? 1 : 0, $movementId]
            );

            // Registra economia (desconto) ou juros conforme diferença entre valor original e pago
            if (abs($diferenca) >= 0.01) {
                if ($toValidated) {
                    if ($diferenca > 0) {
                        $db->execute(
                            "UPDATE `{$this->table}` SET total_economia = total_economia + ? WHERE id = ?",
                            [$diferenca, $debtId]
                        );
                    } else {
                        $db->execute(
                            "UPDATE `{$this->table}` SET total_juros = total_juros + ? WHERE id = ?",
                            [abs($diferenca), $debtId]
                        );
                    }
                } else {
                    if ($diferenca > 0) {
                        $db->execute(
                            "UPDATE `{$this->table}` SET total_economia = GREATEST(0, total_economia - ?) WHERE id = ?",
                            [$diferenca, $debtId]
                        );
                    } else {
                        $db->execute(
                            "UPDATE `{$this->table}` SET total_juros = GREATEST(0, total_juros - ?) WHERE id = ?",
                            [abs($diferenca), $debtId]
                        );
                    }
                }
            }

            $db->execute(
                "UPDATE `{$this->table}`
                 SET parcelas_pagas = ?,
                     saldo_devedor = ?,
                     ativo = ?,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = ?",
                [$parcelasPagas, round($saldoDevedor, 2), $ativo, $debtId]
            );

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
        }
    }

    private function extractDebtIdFromObservation(string $observation): ?int
    {
        if (preg_match('/\[DIVIDA_ID:(\d+)\]/', $observation, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }
}

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
     * Lista as dívidas do usuário.
     *
     * @param string $order 'recentes' (mais atualizadas primeiro, padrão) ou
     *                      'proximas' (mais perto de finalizar primeiro e,
     *                      no empate, maior valor de parcela). Dívidas já
     *                      quitadas continuam agrupadas no fim em ambos.
     *
     * @return array<int,array<string,mixed>>
     */
    public function findByUser(int $userId, bool $onlyActive = false, string $order = 'recentes'): array
    {
        $where = $onlyActive ? 'AND d.ativo = 1 AND d.parcelas_pagas < d.total_parcelas' : '';

        // cláusula vinda de uma lista fechada, nunca da requisição
        $orderBy = match ($order) {
            'proximas' => 'd.ativo DESC,'
                . ' GREATEST(d.total_parcelas - d.parcelas_pagas, 0) ASC,'
                . ' d.valor_parcela DESC,'
                . ' d.id DESC',
            default => 'd.ativo DESC, d.updated_at DESC, d.id DESC',
        };

        return $this->db->fetchAll(
            "SELECT d.*,
                    (d.total_parcelas - d.parcelas_pagas) AS parcelas_abertas
             FROM `{$this->table}` d
             WHERE d.usuario_id = ? {$where}
             ORDER BY {$orderBy}",
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
     * Série para o gráfico de redução: meses passados (histórico real) seguidos
     * do mês atual e meses futuros (projeção com base nas parcelas em aberto).
     *
     * @return array{labels: array<int,string>, payments: array<int,float>, totals: array<int,float>, paidCumulative: array<int,float>, grossReduction: float, currentIndex: int}
     */
    public function getReductionSeries(int $userId, int $monthsPast = 3, int $monthsFuture = 9): array
    {
        $totalMonths = $monthsPast + $monthsFuture;
        $currentIndex = $monthsPast;

        $labels = [];
        $keys   = [];
        $paymentsByKey = [];
        $newDebtByKey  = [];

        $start = new \DateTimeImmutable('first day of this month');
        $start = $start->modify('-' . $monthsPast . ' months');

        for ($i = 0; $i < $totalMonths; $i++) {
            $d = $start->modify('+' . $i . ' months');
            $key = $d->format('Y-m');
            $keys[] = $key;
            $labels[] = $d->format('m/Y');
            $paymentsByKey[$key] = 0.0;
            $newDebtByKey[$key] = 0.0;
        }

        // histórico real (pagamentos já lançados) para os meses passados
        $rows = $this->db->fetchAll(
            "SELECT DATE_FORMAT(data_competencia, '%Y-%m') AS ym, COALESCE(SUM(valor), 0) AS total
             FROM movimentacoes
             WHERE usuario_id = ?
               AND tipo = 'saida'
               AND observacao LIKE '[DIVIDA_ID:%'
               AND data_competencia >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL ? MONTH), '%Y-%m-01')
             GROUP BY ym",
            [$userId, $monthsPast + 1]
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
            [$userId, $monthsPast + 1]
        );

        foreach ($debts as $row) {
            $key = (string) $row['ym'];
            if (array_key_exists($key, $newDebtByKey)) {
                $newDebtByKey[$key] = (float) $row['total'];
            }
        }

        // projeção futura: valor previsto de parcelas por mês, a partir das dívidas em aberto
        $forecast = $this->getForecastMatrix($userId, $monthsFuture);
        $forecastByKey = [];
        foreach ($forecast['labels'] as $idx => $label) {
            [$m, $y] = explode('/', $label);
            $forecastByKey[$y . '-' . $m] = (float) ($forecast['totals'][$idx] ?? 0.0);
        }

        $currentOutstanding = $this->getTotalOutstanding($userId);
        $totals = array_fill(0, $totalMonths, 0.0);

        // reconstrói o passado desfazendo pagamentos/novas dívidas a partir do saldo atual
        $running = $currentOutstanding;
        for ($i = $currentIndex; $i >= 0; $i--) {
            $totals[$i] = round($running, 2);
            if ($i > 0) {
                $key = $keys[$i];
                $running += $paymentsByKey[$key] ?? 0.0;
                $running -= $newDebtByKey[$key] ?? 0.0;
            }
        }

        // projeta o futuro subtraindo os pagamentos previstos das parcelas em aberto
        $running = $currentOutstanding;
        for ($i = $currentIndex + 1; $i < $totalMonths; $i++) {
            $key = $keys[$i];
            $running = max(0.0, $running - ($forecastByKey[$key] ?? 0.0));
            $totals[$i] = round($running, 2);
        }

        $payments = [];
        $paidCumulative = [];
        $runningPaid = 0.0;
        foreach ($keys as $idx => $k) {
            $valor = $idx <= $currentIndex ? ($paymentsByKey[$k] ?? 0.0) : ($forecastByKey[$k] ?? 0.0);
            $payments[] = $valor;
            $runningPaid += $valor;
            $paidCumulative[] = round($runningPaid, 2);
        }

        $grossReduction = max(0, $totals[0] - $totals[$totalMonths - 1]);

        return [
            'labels' => $labels,
            'payments' => $payments,
            'totals' => $totals,
            'paidCumulative' => $paidCumulative,
            'grossReduction' => round($grossReduction, 2),
            'currentIndex' => $currentIndex,
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

    /**
     * Demonstrativo mês a mês das parcelas em aberto, por dívida, com total geral por mês.
     * O horizonte é limitado a $months e as colunas começam em $startYm (mês atual
     * por padrão). $startYm usa a chave canônica 'Y-m' (ex.: '2026-09').
     * Colunas só existem para meses que de fato têm parcelas a pagar.
     *
     * @return array{labels: array<int,string>, rows: array<int,array{descricao:string, values: array<int,float>, total: float}>, totals: array<int,float>, startYm: string}
     */
    public function getForecastMatrix(int $userId, int $months = 12, ?string $startYm = null): array
    {
        $startYm = $startYm ?? (new \DateTimeImmutable('first day of this month'))->format('Y-m');

        $schedule = $this->buildOpenSchedule($userId);
        if (empty($schedule)) {
            return ['labels' => [], 'rows' => [], 'totals' => [], 'startYm' => $startYm];
        }

        $ymSet = [];
        foreach ($schedule as $debt) {
            foreach ($debt['yms'] as $ym) {
                if ($ym >= $startYm) {
                    $ymSet[$ym] = true;
                }
            }
        }

        $yms = array_keys($ymSet);
        sort($yms);
        $yms = array_slice($yms, 0, max(1, (int) $months));

        // nenhuma parcela em aberto a partir de $startYm: evita devolver linhas
        // degeneradas (sem colunas) para a view
        if ($yms === []) {
            return ['labels' => [], 'rows' => [], 'totals' => [], 'startYm' => $startYm];
        }

        $labels = [];
        $index  = [];
        foreach ($yms as $i => $ym) {
            $labels[] = $this->ymToDate($ym)->format('m/Y');
            $index[$ym] = $i;
        }

        $totalMonths = count($labels);
        $rows   = [];
        $totals = array_fill(0, $totalMonths, 0.0);

        foreach ($schedule as $debt) {
            $values = array_fill(0, $totalMonths, 0.0);
            foreach ($debt['yms'] as $ym) {
                if (!isset($index[$ym])) {
                    continue;
                }
                $values[$index[$ym]] = round($debt['valor'], 2);
                $totals[$index[$ym]] += $debt['valor'];
            }

            $rows[] = [
                'descricao' => $debt['descricao'],
                'values'    => $values,
                'total'     => round(array_sum($values), 2),
            ];
        }

        return [
            'labels'  => $labels,
            'rows'    => $rows,
            'totals'  => array_map(fn ($v) => round($v, 2), $totals),
            'startYm' => $startYm,
        ];
    }

    /**
     * Projeção do endividamento para um período de referência (mês/ano do filtro).
     *
     * - Período atual ou futuro: assume pagamento em dia de todas as parcelas
     *   vencidas até o fim do período e devolve o saldo restante.
     * - Período passado: reconstrói o saldo que existia no fim daquele mês
     *   somando os pagamentos posteriores e removendo as dívidas criadas depois.
     *
     * @return array{saldo: float, ehAtual: bool, ehFuturo: bool, ehPassado: bool, delta: float, deltaPercent: float, dueInPeriod: float, parcelasPeriodo: int, dueInPreviousPeriod: float, deltaPeriod: float, dueUntilPeriod: float, parcelasAtePeriodo: int}
     */
    public function getPeriodProjection(int $userId, int $mes, int $ano): array
    {
        $nowYm    = (new \DateTimeImmutable('first day of this month'))->format('Y-m');
        $targetYm = sprintf('%04d-%02d', $ano, $mes);
        $prevYm   = $this->ymToDate($targetYm)->modify('-1 month')->format('Y-m');

        $outstanding = $this->getTotalOutstanding($userId);
        $buckets     = $this->getOpenScheduleBuckets($userId);

        $dueInPeriod       = (float) ($buckets[$targetYm]['total'] ?? 0.0);
        $parcelasPeriodo   = (int) ($buckets[$targetYm]['qtd'] ?? 0);
        $dueInPrevPeriod   = (float) ($buckets[$prevYm]['total'] ?? 0.0);

        $dueUntilPeriod    = 0.0;
        $parcelasAtePeriod = 0;
        foreach ($buckets as $ym => $bucket) {
            if ($ym < $nowYm || $ym > $targetYm) {
                continue;
            }
            $dueUntilPeriod    += $bucket['total'];
            $parcelasAtePeriod += $bucket['qtd'];
        }

        $ehFuturo  = $targetYm > $nowYm;
        $ehAtual   = $targetYm === $nowYm;

        $saldo = ($ehFuturo || $ehAtual)
            ? max(0.0, $outstanding - $dueUntilPeriod)
            : $this->getHistoricalOutstanding($userId, $mes, $ano, $outstanding);

        $delta        = $saldo - $outstanding;
        $deltaPercent = $outstanding > 0 ? round(($delta / $outstanding) * 100, 1) : 0.0;

        return [
            'saldo'              => round($saldo, 2),
            'ehAtual'            => $ehAtual,
            'ehFuturo'           => $ehFuturo,
            'ehPassado'          => !($ehFuturo || $ehAtual),
            'delta'              => round($delta, 2),
            'deltaPercent'       => $deltaPercent,
            'dueInPeriod'        => round($dueInPeriod, 2),
            'parcelasPeriodo'    => $parcelasPeriodo,
            'dueInPreviousPeriod' => round($dueInPrevPeriod, 2),
            'deltaPeriod'        => round($dueInPeriod - $dueInPrevPeriod, 2),
            'dueUntilPeriod'     => round($dueUntilPeriod, 2),
            'parcelasAtePeriod'  => $parcelasAtePeriod,
        ];
    }

    /**
     * Total das parcelas previstas para o período e contagem, por mês (YYYY-MM).
     *
     * @return array<string, array{total: float, qtd: int}>
     */
    public function getOpenScheduleBuckets(int $userId): array
    {
        $buckets = [];

        foreach ($this->buildOpenSchedule($userId) as $debt) {
            foreach ($debt['yms'] as $ym) {
                if (!isset($buckets[$ym])) {
                    $buckets[$ym] = ['total' => 0.0, 'qtd' => 0];
                }
                $buckets[$ym]['total'] += $debt['valor'];
                $buckets[$ym]['qtd']++;
            }
        }

        ksort($buckets);

        return $buckets;
    }

    /**
     * Cronograma de parcelas em aberto por dívida, com o mês (YYYY-MM) de cada uma.
     * As previsões já gravadas em movimentacoes têm precedência; o que faltar é
     * derivado de data_inicio. Parcelas com vencimento no passado são descartadas.
     *
     * @return array<int, array{descricao: string, valor: float, yms: array<int,string>}>
     */
    private function buildOpenSchedule(int $userId): array
    {
        $debts = $this->db->fetchAll(
            "SELECT id, descricao, valor_parcela, total_parcelas, parcelas_pagas, data_inicio
             FROM `{$this->table}`
             WHERE usuario_id = ? AND ativo = 1 AND parcelas_pagas < total_parcelas
             ORDER BY descricao",
            [$userId]
        );

        $nowYm = (new \DateTimeImmutable('first day of this month'))->format('Y-m');
        $schedule = [];

        foreach ($debts as $debt) {
            $restam = max(0, (int) $debt['total_parcelas'] - (int) $debt['parcelas_pagas']);
            $valor  = (float) $debt['valor_parcela'];

            if ($restam === 0 || $valor <= 0) {
                continue;
            }

            $schedule[] = [
                'descricao' => (string) $debt['descricao'],
                'valor'     => $valor,
                'yms'       => $this->resolveDebtMonths($userId, $debt, $restam, $nowYm),
            ];
        }

        return $schedule;
    }

    /**
     * Meses de vencimento das parcelas em aberto de uma dívida.
     *
     * @return array<int,string>
     */
    private function resolveDebtMonths(int $userId, array $debt, int $restam, string $nowYm): array
    {
        $rows = $this->db->fetchAll(
            "SELECT data_competencia
             FROM movimentacoes
             WHERE usuario_id = ?
               AND tipo = 'saida'
               AND validado = 0
               AND observacao LIKE ?
             ORDER BY data_competencia ASC",
            [$userId, '[DIVIDA_ID:' . (int) $debt['id'] . ']%']
        );

        $yms = [];
        foreach ($rows as $row) {
            $ym = (new \DateTimeImmutable((string) $row['data_competencia']))->format('Y-m');
            if ($ym >= $nowYm && !in_array($ym, $yms, true)) {
                $yms[] = $ym;
            }
        }

        if (count($yms) >= $restam) {
            return array_slice($yms, 0, $restam);
        }

        $faltam = $restam - count($yms);

        // cursor aponta para o último mês já ocupado; sem registro, volta do
        // mês da primeira parcela ainda em aberto (data_inicio + parcelas pagas)
        $cursor = $yms !== []
            ? $this->ymToDate($yms[count($yms) - 1])
            : $this->resolveDebtStartMonth($debt)->modify('+' . (int) $debt['parcelas_pagas'] . ' months')
                ->modify('-1 month');

        for ($i = 0; $i < $faltam; $i++) {
            $cursor = $cursor->modify('+1 month');
            $ym = $cursor->format('Y-m');

            if ($ym < $nowYm || in_array($ym, $yms, true)) {
                continue;
            }

            $yms[] = $ym;
        }

        return $yms;
    }

    /**
     * Converte a chave canônica de mês 'Y-m' (ex.: '2026-09') no primeiro dia
     * do mês correspondente.
     *
     * Dois motivos para nunca usar o formato compacto 'Ym' aqui:
     *  - new DateTimeImmutable('202610-01') é interpretado pelo PHP como hora
     *    (20:26:10), não como data;
     *  - '202610' é uma string decimal canônica, então o PHP a converte em
     *    chave inteira ao usá-la como índice de array, quebrando o
     *    strict comparison e o type hint string dos métodos.
     */
    private function ymToDate(string $ym): \DateTimeImmutable
    {
        [$year, $month] = array_pad(explode('-', $ym, 2), 2, '1');

        return new \DateTimeImmutable(sprintf('%04d-%02d-01', (int) $year, (int) $month));
    }

    private function resolveDebtStartMonth(array $debt): \DateTimeImmutable
    {
        if (!empty($debt['data_inicio'])) {
            $start = date('Y-m-01', strtotime((string) $debt['data_inicio']));
            if (!empty($start)) {
                return new \DateTimeImmutable($start);
            }
        }

        return new \DateTimeImmutable('first day of this month');
    }

    /**
     * Saldo devedor estimado no fim de um mês já vencido, reconstruído a partir
     * do saldo atual: soma o que foi pago depois e remove o que foi contraído depois.
     *
     * O intervalo de pagamentos é limitado ao fim do mês atual de propósito:
     * lançamentos com data de competência futura são parcelas *agendadas*, não
     * quitadas, e somá-las inflaria o saldo reconstruído.
     */
    private function getHistoricalOutstanding(int $userId, int $mes, int $ano, float $outstanding): float
    {
        $limite = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $ano, $mes)));
        $hoje = date('Y-m-t');

        $pago = $this->db->fetch(
            "SELECT COALESCE(SUM(valor), 0) AS total
             FROM movimentacoes
             WHERE usuario_id = ?
               AND tipo = 'saida'
               AND observacao LIKE '[DIVIDA_ID:%'
               AND data_competencia > ?
               AND data_competencia <= ?",
            [$userId, $limite, $hoje]
        );

        $novo = $this->db->fetch(
            "SELECT COALESCE(SUM(saldo_devedor), 0) AS total
             FROM `{$this->table}`
             WHERE usuario_id = ? AND ativo = 1 AND created_at > ?",
            [$userId, $limite . ' 23:59:59']
        );

        return max(
            0.0,
            $outstanding
            + (float) ($pago['total'] ?? 0)
            - (float) ($novo['total'] ?? 0)
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

    public function updateDebt(int $userId, int $debtId, array $data): bool
    {
        $debt = $this->db->fetch(
            "SELECT id FROM `{$this->table}` WHERE id = ? AND usuario_id = ?",
            [$debtId, $userId]
        );

        if ($debt === null) {
            return false;
        }

        $totalParcelas = (int) $data['total_parcelas'];
        $parcelasPagas = (int) $data['parcelas_pagas'];
        $valorParcela  = (float) $data['valor_parcela'];
        $saldoDevedor  = round(max(0, $valorParcela * ($totalParcelas - $parcelasPagas)), 2);

        $this->update($debtId, [
            'descricao'      => (string) $data['descricao'],
            'valor_parcela'  => $valorParcela,
            'total_parcelas' => $totalParcelas,
            'parcelas_pagas' => $parcelasPagas,
            'dia_vencimento' => (int) ($data['dia_vencimento'] ?? 1),
            'saldo_devedor'  => $saldoDevedor,
            'ativo'          => $parcelasPagas < $totalParcelas ? 1 : 0,
        ]);

        return true;
    }

    /**
     * Quita integralmente o saldo devedor da dívida, lançando o valor restante
     * como uma movimentação de saída já validada.
     */
    public function settleDebt(int $userId, int $debtId, string $dataCompetencia): void
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
            $parcelasPagas = (int) $debt['parcelas_pagas'];
            $saldoDevedor  = (float) $debt['saldo_devedor'];

            if ($parcelasPagas >= $totalParcelas || $saldoDevedor <= 0) {
                throw new RuntimeException('Esta dívida já está quitada.');
            }

            $catId = $this->findOrCreateDebtCategoryId();
            $tagPattern = '[DIVIDA_ID:' . $debtId . ']%';

            $db->execute(
                "INSERT INTO movimentacoes
                    (usuario_id, descricao, tipo, modo, categoria_id, subcategoria_id, valor, data_competencia, data_vencimento,
                     parcela_atual, total_parcelas, validado, observacao)
                 VALUES (?, ?, 'saida', 'parcelamento', ?, NULL, ?, ?, ?, ?, ?, 1, ?)",
                [
                    $userId,
                    'Quitação dívida: ' . $debt['descricao'],
                    $catId,
                    $saldoDevedor,
                    $dataCompetencia,
                    $dataCompetencia,
                    $totalParcelas,
                    $totalParcelas,
                    '[DIVIDA_ID:' . $debtId . '][QUITACAO] Quitação integral do saldo devedor',
                ]
            );

            // remove previsões pendentes futuras, evitando duplicidade após a quitação
            $db->execute(
                "DELETE FROM movimentacoes WHERE usuario_id = ? AND observacao LIKE ? AND validado = 0",
                [$userId, $tagPattern]
            );

            $db->execute(
                "UPDATE `{$this->table}` SET parcelas_pagas = ?, saldo_devedor = 0, ativo = 0
                 WHERE id = ? AND usuario_id = ?",
                [$totalParcelas, $debtId, $userId]
            );

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * Remove a dívida e as movimentações vinculadas. Parcelas pendentes (não validadas)
     * são sempre removidas; parcelas já pagas só são removidas se $excluirPagas = true.
     */
    public function deleteDebt(int $userId, int $debtId, bool $excluirPagas): bool
    {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $debt = $db->fetch(
                "SELECT id FROM `{$this->table}` WHERE id = ? AND usuario_id = ? FOR UPDATE",
                [$debtId, $userId]
            );

            if ($debt === null) {
                $db->rollback();
                return false;
            }

            $tagPattern = '[DIVIDA_ID:' . $debtId . ']%';

            if ($excluirPagas) {
                $db->execute(
                    "DELETE FROM movimentacoes WHERE usuario_id = ? AND observacao LIKE ?",
                    [$userId, $tagPattern]
                );
            } else {
                $db->execute(
                    "DELETE FROM movimentacoes WHERE usuario_id = ? AND observacao LIKE ? AND validado = 0",
                    [$userId, $tagPattern]
                );
            }

            $db->execute(
                "DELETE FROM `{$this->table}` WHERE id = ? AND usuario_id = ?",
                [$debtId, $userId]
            );

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
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

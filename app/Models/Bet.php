<?php declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Bet - registro de apostas (simples e múltiplas) do módulo de Gerenciamento de Apostas.
 */
class Bet extends Model
{
    protected string $table = 'apostas';

    /** Expressão SQL do lucro líquido de uma aposta (vitória) considerando fechamento antecipado. */
    private const SQL_LUCRO_VITORIA = "CASE WHEN valor_fechamento IS NOT NULL THEN valor_fechamento - valor_apostado ELSE (valor_apostado * odd) - valor_apostado END";

    /** Expressão SQL da perda de uma aposta (derrota) considerando fechamento antecipado.
     *  Fechamentos negativos (ex.: -valor_apostado) representam diretamente o valor da perda. */
    private const SQL_PERDA_DERROTA = "CASE WHEN valor_fechamento IS NULL THEN valor_apostado WHEN valor_fechamento < 0 THEN -valor_fechamento ELSE valor_apostado - valor_fechamento END";

    // ----------------------------------------------------------------
    // Create
    // ----------------------------------------------------------------

    /**
     * @param array<string,mixed> $data
     */
    public function createSimple(array $data): int
    {
        return $this->create([
            'usuario_id'       => $data['usuario_id'],
            'tipo'             => 'simples',
            'descricao'        => $data['descricao'],
            'categoria_id'     => $data['categoria_id'],
            'odd'              => $data['odd'],
            'valor_apostado'   => $data['valor_apostado'],
            'status'           => $data['status'],
            'valor_fechamento' => $data['valor_fechamento'],
            'data_aposta'      => $data['data_aposta'],
            'data_resultado'   => $data['data_resultado'],
            'observacao'       => $data['observacao'],
        ]);
    }

    /**
     * Cria uma aposta múltipla. As seleções (odds) são multiplicadas para formar a odd principal,
     * mas esse valor pode ser sobrescrito manualmente (odd editável).
     *
     * @param array<string,mixed> $data
     * @param array<int,array{descricao:string,categoria_id:?int,odd:float}> $selections
     */
    public function createMultiple(array $data, array $selections): int
    {
        $betId = $this->create([
            'usuario_id'       => $data['usuario_id'],
            'tipo'             => 'multipla',
            'descricao'        => $data['descricao'],
            'categoria_id'     => null,
            'odd'              => $data['odd'],
            'valor_apostado'   => $data['valor_apostado'],
            'status'           => $data['status'],
            'valor_fechamento' => $data['valor_fechamento'],
            'data_aposta'      => $data['data_aposta'],
            'data_resultado'   => $data['data_resultado'],
            'observacao'       => $data['observacao'],
        ]);

        $selModel = new BetSelection();
        foreach ($selections as $sel) {
            $selModel->create([
                'aposta_id'    => $betId,
                'descricao'    => $sel['descricao'],
                'categoria_id' => $sel['categoria_id'],
                'odd'          => $sel['odd'],
            ]);
        }

        return $betId;
    }

    // ----------------------------------------------------------------
    // Finalize / update
    // ----------------------------------------------------------------

    public function finalize(int $id, string $status, ?float $valorFechamento, ?string $dataResultado): int
    {
        return $this->update($id, [
            'status'           => $status,
            'valor_fechamento' => $valorFechamento,
            'data_resultado'   => $dataResultado ?? date('Y-m-d'),
        ]);
    }

    // ----------------------------------------------------------------
    // Reads
    // ----------------------------------------------------------------

    /**
     * @return array<string,mixed>|null
     */
    public function findWithCategory(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT a.*, c.nome AS categoria_nome
             FROM `{$this->table}` a
             LEFT JOIN `apostas_categorias` c ON c.id = a.categoria_id
             WHERE a.id = ?",
            [$id]
        );
    }

    /**
     * Lista paginada de apostas do usuário com filtros (mes, ano, status, data_inicio, data_fim, page, limit).
     *
     * @param array<string,mixed> $filters
     * @return array{data: array<int,array<string,mixed>>, total: int, pages: int, page: int, limit: int}
     */
    public function findByUser(int $userId, array $filters = []): array
    {
        [$where, $params] = $this->buildWhere($userId, $filters);

        $page  = (int) ($filters['page']  ?? 1);
        $limit = (int) ($filters['limit'] ?? 20);
        $offset = ($page - 1) * $limit;

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) AS c FROM `{$this->table}` a WHERE {$where}",
            $params
        )['c'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT a.*, c.nome AS categoria_nome
             FROM `{$this->table}` a
             LEFT JOIN `apostas_categorias` c ON c.id = a.categoria_id
             WHERE {$where}
             ORDER BY a.data_aposta DESC, a.id DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );

        return [
            'data'  => $rows,
            'total' => $total,
            'pages' => (int) ceil($total / max(1, $limit)),
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    /**
     * Registros de um dia específico (para o modal do extrato diário).
     *
     * @return array<int,array<string,mixed>>
     */
    public function findByDate(int $userId, string $date): array
    {
        return $this->db->fetchAll(
            "SELECT a.*, c.nome AS categoria_nome
             FROM `{$this->table}` a
             LEFT JOIN `apostas_categorias` c ON c.id = a.categoria_id
             WHERE a.usuario_id = ? AND a.data_aposta = ?
             ORDER BY a.id DESC",
            [$userId, $date]
        );
    }

    /**
     * KPIs do painel principal para um período (mês/ano).
     *
     * @return array{total_apostado: float, retorno_liquido: float, media_retorno: float,
     *               total_perda: float, media_perda: float, qtd_vitorias: int, qtd_derrotas: int,
     *               qtd_pendentes: int, qtd_reembolsos: int, qtd_total: int}
     */
    public function getKpis(int $userId, int $mes, int $ano): array
    {
        $sql = "SELECT
                    COUNT(*) AS qtd_total,
                    COALESCE(SUM(valor_apostado), 0) AS total_apostado,
                    COALESCE(SUM(CASE WHEN status = 'vitoria' THEN " . self::SQL_LUCRO_VITORIA . " ELSE 0 END), 0) AS retorno_liquido,
                    COALESCE(SUM(CASE WHEN status = 'derrota' THEN " . self::SQL_PERDA_DERROTA . " ELSE 0 END), 0) AS total_perda,
                    SUM(CASE WHEN status = 'vitoria'   THEN 1 ELSE 0 END) AS qtd_vitorias,
                    SUM(CASE WHEN status = 'derrota'   THEN 1 ELSE 0 END) AS qtd_derrotas,
                    SUM(CASE WHEN status = 'pendente'  THEN 1 ELSE 0 END) AS qtd_pendentes,
                    SUM(CASE WHEN status = 'reembolso' THEN 1 ELSE 0 END) AS qtd_reembolsos
                FROM `{$this->table}`
                WHERE usuario_id = ? AND MONTH(data_aposta) = ? AND YEAR(data_aposta) = ?";

        $row = $this->db->fetch($sql, [$userId, $mes, $ano]) ?? [];

        $qtdVitorias = (int) ($row['qtd_vitorias'] ?? 0);
        $qtdDerrotas = (int) ($row['qtd_derrotas'] ?? 0);
        $retornoLiquido = (float) ($row['retorno_liquido'] ?? 0);
        $totalPerda     = (float) ($row['total_perda'] ?? 0);

        return [
            'total_apostado'  => (float) ($row['total_apostado'] ?? 0),
            'retorno_liquido' => $retornoLiquido,
            'media_retorno'   => $qtdVitorias > 0 ? $retornoLiquido / $qtdVitorias : 0.0,
            'total_perda'     => $totalPerda,
            'media_perda'     => $qtdDerrotas > 0 ? $totalPerda / $qtdDerrotas : 0.0,
            'qtd_vitorias'    => $qtdVitorias,
            'qtd_derrotas'    => $qtdDerrotas,
            'qtd_pendentes'   => (int) ($row['qtd_pendentes'] ?? 0),
            'qtd_reembolsos'  => (int) ($row['qtd_reembolsos'] ?? 0),
            'qtd_total'       => (int) ($row['qtd_total'] ?? 0),
        ];
    }

    /**
     * Resultado líquido de todas as apostas já resolvidas do usuário (histórico completo),
     * usado para compor o saldo real da banca junto às entradas/saques manuais.
     */
    public function getNetResultAllTime(int $userId): float
    {
        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN status = 'vitoria' THEN " . self::SQL_LUCRO_VITORIA . " ELSE 0 END), 0)
                    - COALESCE(SUM(CASE WHEN status = 'derrota' THEN " . self::SQL_PERDA_DERROTA . " ELSE 0 END), 0) AS resultado
                FROM `{$this->table}`
                WHERE usuario_id = ?";

        $row = $this->db->fetch($sql, [$userId]);

        return (float) ($row['resultado'] ?? 0);
    }

    /**
     * Resultado líquido acumulado das apostas anteriores a um mês/ano.
     *
     * Usado como "saldo inicial" do período no comparativo diário.
     * Considera apenas ganhos/perdas das apostas (depósitos e saques são ignorados).
     */
    public function getNetResultBeforeMonth(int $userId, int $mes, int $ano): float
    {
        // Primeiro dia do mês de referência: tudo o que for anterior a ele compõe o saldo inicial.
        $firstDayOfMonth = sprintf('%04d-%02d-01', $ano, $mes);

        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN status = 'vitoria' THEN " . self::SQL_LUCRO_VITORIA . " ELSE 0 END), 0)
                    - COALESCE(SUM(CASE WHEN status = 'derrota' THEN " . self::SQL_PERDA_DERROTA . " ELSE 0 END), 0) AS resultado
                FROM `{$this->table}`
                WHERE usuario_id = ? AND data_aposta < ?";

        $row = $this->db->fetch($sql, [$userId, $firstDayOfMonth]);

        return (float) ($row['resultado'] ?? 0);
    }

    /**
     * Totais agrupados dia a dia para o período (mês/ano).
     *
     * @return array<int,array<string,mixed>>
     */
    public function getDailyTotals(int $userId, int $mes, int $ano): array
    {
        $sql = "SELECT
                    data_aposta AS data,
                    COUNT(*) AS qtd,
                    COALESCE(SUM(valor_apostado), 0) AS total_apostado,
                    COALESCE(SUM(CASE WHEN status = 'vitoria' THEN " . self::SQL_LUCRO_VITORIA . " ELSE 0 END), 0) AS lucro,
                    COALESCE(SUM(CASE WHEN status = 'derrota' THEN " . self::SQL_PERDA_DERROTA . " ELSE 0 END), 0) AS perda,
                    SUM(CASE WHEN status = 'vitoria'  THEN 1 ELSE 0 END) AS vitorias,
                    SUM(CASE WHEN status = 'derrota'  THEN 1 ELSE 0 END) AS derrotas,
                    SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) AS pendentes
                FROM `{$this->table}`
                WHERE usuario_id = ? AND MONTH(data_aposta) = ? AND YEAR(data_aposta) = ?
                GROUP BY data_aposta
                ORDER BY data_aposta DESC";

        $rows = $this->db->fetchAll($sql, [$userId, $mes, $ano]);

        // Resultado líquido de cada dia (ganhos - perdas). Depósitos/saques não entram.
        foreach ($rows as &$r) {
            $r['resultado_dia'] = (float) $r['lucro'] - (float) $r['perda'];
        }
        unset($r);

        // Saldo inicial do período: resultado acumulado das apostas anteriores ao mês.
        // Parte-se de um saldo já existente e aplica-se o resultado de cada dia.
        $saldoAcumulado = $this->getNetResultBeforeMonth($userId, $mes, $ano);

        // A consulta retorna os dias em ordem decrescente; percorremos de trás para frente
        // (ordem cronológica) para acumular o saldo final do dia e comparar com o dia anterior.
        for ($i = count($rows) - 1; $i >= 0; $i--) {
            $saldoAnterior = $saldoAcumulado;                          // saldo final do dia anterior
            $saldoAcumulado += (float) $rows[$i]['resultado_dia'];     // aplica ganhos - perdas do dia
            $rows[$i]['saldo']       = $saldoAcumulado;                // saldo final do dia
            $rows[$i]['comparativo'] = $saldoAcumulado - $saldoAnterior; // variação vs. dia anterior
        }

        return $rows;
    }

    /**
     * Extrato de um período livre (data início/fim), com filtro opcional de status e totais.
     *
     * @return array{records: array<int,array<string,mixed>>, total_apostado: float, retorno_liquido: float, total_perda: float, qtd_vitorias: int, qtd_derrotas: int}
     */
    public function getStatement(int $userId, string $dataInicio, string $dataFim, string $status = ''): array
    {
        $where  = "a.usuario_id = ? AND a.data_aposta BETWEEN ? AND ?";
        $params = [$userId, $dataInicio, $dataFim];

        if ($status !== '') {
            $where .= " AND a.status = ?";
            $params[] = $status;
        }

        $records = $this->db->fetchAll(
            "SELECT a.*, c.nome AS categoria_nome
             FROM `{$this->table}` a
             LEFT JOIN `apostas_categorias` c ON c.id = a.categoria_id
             WHERE {$where}
             ORDER BY a.data_aposta DESC, a.id DESC",
            $params
        );

        $totalApostado = 0.0;
        $retornoLiquido = 0.0;
        $totalPerda = 0.0;
        $qtdVitorias = 0;
        $qtdDerrotas = 0;

        foreach ($records as $r) {
            $totalApostado += (float) $r['valor_apostado'];
            $lucro = $this->calcLucro($r);

            if ($r['status'] === 'vitoria') {
                $retornoLiquido += $lucro ?? 0;
                $qtdVitorias++;
            } elseif ($r['status'] === 'derrota') {
                $totalPerda += abs($lucro ?? 0);
                $qtdDerrotas++;
            }
        }

        return [
            'records'         => $records,
            'total_apostado'  => $totalApostado,
            'retorno_liquido' => $retornoLiquido,
            'total_perda'     => $totalPerda,
            'qtd_vitorias'    => $qtdVitorias,
            'qtd_derrotas'    => $qtdDerrotas,
        ];
    }

    /**
     * Calcula o lucro líquido de uma aposta em PHP (para exibição individual).
     * Retorna null se pendente.
     *
     * @param array<string,mixed> $bet
     */
    public function calcLucro(array $bet): ?float
    {
        $status = $bet['status'];
        $valorApostado = (float) $bet['valor_apostado'];
        $valorFechamento = $bet['valor_fechamento'] !== null ? (float) $bet['valor_fechamento'] : null;
        $odd = (float) $bet['odd'];

        return match ($status) {
            'vitoria'   => $valorFechamento !== null ? ($valorFechamento - $valorApostado) : ($valorApostado * $odd - $valorApostado),
            // Derrota: fechamento negativo é a própria perda (ex.: -valor_apostado);
            // fechamento nulo/positivo segue a regra de retorno parcial.
            'derrota'   => $valorFechamento !== null
                ? ($valorFechamento < 0 ? $valorFechamento : $valorFechamento - $valorApostado)
                : -$valorApostado,
            'reembolso' => 0.0,
            default     => null,
        };
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /**
     * @param array<string,mixed> $filters
     * @return array{0:string,1:array<int,mixed>}
     */
    private function buildWhere(int $userId, array $filters): array
    {
        $where  = "a.usuario_id = ?";
        $params = [$userId];

        if (!empty($filters['mes']) && !empty($filters['ano'])) {
            $where .= " AND MONTH(a.data_aposta) = ? AND YEAR(a.data_aposta) = ?";
            $params[] = (int) $filters['mes'];
            $params[] = (int) $filters['ano'];
        }

        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $where .= " AND a.data_aposta BETWEEN ? AND ?";
            $params[] = $filters['data_inicio'];
            $params[] = $filters['data_fim'];
        }

        if (!empty($filters['status'])) {
            $where .= " AND a.status = ?";
            $params[] = $filters['status'];
        }

        return [$where, $params];
    }
}

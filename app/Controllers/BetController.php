<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Bet;
use App\Models\BetSelection;
use App\Models\BetCategory;
use App\Models\BetBankMovement;
use App\Models\BetShareLink;
use App\Models\User;

/**
 * BetController - Módulo de Gerenciamento de Apostas.
 *
 * Acesso restrito a usuários com `apostas_autorizado = 1`, ou por link público
 * (somente leitura) gerado através de token.
 */
class BetController extends Controller
{
    private Bet $model;
    private BetCategory $catModel;
    private BetBankMovement $bankModel;
    private BetShareLink $linkModel;

    public function __construct()
    {
        parent::__construct();
        $this->model     = new Bet();
        $this->catModel  = new BetCategory();
        $this->bankModel = new BetBankMovement();
        $this->linkModel = new BetShareLink();
    }

    // ----------------------------------------------------------------
    // Access control
    // ----------------------------------------------------------------

    private function requireBetAccess(): int
    {
        $this->requireAuth();

        $userId = $this->getUserId();
        $userModel = new User();

        if (!$userModel->isBetsAuthorized($userId)) {
            http_response_code(403);
            echo '<h1>403 - Acesso não autorizado</h1><p>Você não tem permissão para acessar o módulo de apostas.</p>';
            exit;
        }

        return $userId;
    }

    // ----------------------------------------------------------------
    // Dashboard
    // ----------------------------------------------------------------

    public function index(): void
    {
        $userId = $this->requireBetAccess();

        $mes = max(1, min(12, (int) ($_GET['mes'] ?? date('m'))));
        $ano = max(2000, min((int) date('Y') + 1, (int) ($_GET['ano'] ?? date('Y'))));

        $kpis   = $this->model->getKpis($userId, $mes, $ano);
        $daily  = $this->model->getDailyTotals($userId, $mes, $ano);
        $saldoBanca = $this->bankModel->getBalance($userId);
        $categorias = $this->catModel->findActive();
        $shareLink  = $this->linkModel->findActiveForUser($userId);

        $this->view('bets/index', [
            'mes'         => $mes,
            'ano'         => $ano,
            'kpis'        => $kpis,
            'daily'       => $daily,
            'saldoBanca'  => $saldoBanca,
            'categorias'  => $categorias,
            'shareLink'   => $shareLink,
            'csrf'        => $this->csrfToken(),
            'flash'       => $this->getFlash(),
        ]);
    }

    // ----------------------------------------------------------------
    // AJAX: registros de um dia (modal de extrato diário)
    // ----------------------------------------------------------------

    public function dayRecords(string $date): void
    {
        $userId = $this->requireBetAccess();

        if (!$this->isValidDate($date)) {
            $this->json(['error' => 'Data inválida.'], 400);
            return;
        }

        $records = $this->model->findByDate($userId, $date);

        foreach ($records as &$r) {
            $r['lucro'] = $this->model->calcLucro($r);
            if ($r['tipo'] === 'multipla') {
                $selModel = new BetSelection();
                $r['selecoes'] = $selModel->findByBet((int) $r['id']);
            }
        }
        unset($r);

        $this->json(['records' => $records]);
    }

    // ----------------------------------------------------------------
    // AJAX: extrato de um período (modal)
    // ----------------------------------------------------------------

    public function statement(): void
    {
        $userId = $this->requireBetAccess();

        $dataInicio = trim((string) ($_GET['data_inicio'] ?? ''));
        $dataFim    = trim((string) ($_GET['data_fim']    ?? ''));
        $status     = trim((string) ($_GET['status']      ?? ''));

        if (!$this->isValidDate($dataInicio) || !$this->isValidDate($dataFim)) {
            $this->json(['error' => 'Informe um período válido.'], 400);
            return;
        }

        $result = $this->model->getStatement($userId, $dataInicio, $dataFim, $status);

        foreach ($result['records'] as &$r) {
            $r['lucro'] = $this->model->calcLucro($r);
        }
        unset($r);

        $this->json($result);
    }

    // ----------------------------------------------------------------
    // Store: aposta simples
    // ----------------------------------------------------------------

    public function storeSimple(): void
    {
        $userId = $this->requireBetAccess();
        $this->verifyCsrf();

        $mes = (int) ($_POST['mes_url'] ?? date('m'));
        $ano = (int) ($_POST['ano_url'] ?? date('Y'));

        $descricao     = trim((string) ($_POST['descricao'] ?? ''));
        $categoriaId   = (int) ($_POST['categoria_id'] ?? 0) ?: null;
        $odd           = $this->parseDecimal((string) ($_POST['odd'] ?? '1'));
        $valorApostado = $this->parseMoney((string) ($_POST['valor_apostado'] ?? '0'));
        $status        = trim((string) ($_POST['status'] ?? 'pendente'));
        $dataAposta    = trim((string) ($_POST['data_aposta'] ?? date('Y-m-d')));
        $observacao    = trim((string) ($_POST['observacao'] ?? ''));

        $error = $this->validateBet($descricao, $odd, $valorApostado, $status, $dataAposta);
        if ($error !== null) {
            $this->setFlash('error', $error);
            $this->redirect("/apostas?mes={$mes}&ano={$ano}");
            return;
        }

        $this->model->createSimple([
            'usuario_id'       => $userId,
            'descricao'        => $descricao,
            'categoria_id'     => $categoriaId,
            'odd'              => $odd,
            'valor_apostado'   => $valorApostado,
            'status'           => $status,
            'valor_fechamento' => $status !== 'pendente' ? $this->parseMoney((string) ($_POST['valor_fechamento'] ?? '')) ?: null : null,
            'data_aposta'      => $dataAposta,
            'data_resultado'   => $status !== 'pendente' ? date('Y-m-d') : null,
            'observacao'       => $observacao ?: null,
        ]);

        $this->setFlash('success', 'Aposta registrada com sucesso!');
        $this->redirect("/apostas?mes={$mes}&ano={$ano}");
    }

    // ----------------------------------------------------------------
    // Store: aposta múltipla
    // ----------------------------------------------------------------

    public function storeMultiple(): void
    {
        $userId = $this->requireBetAccess();
        $this->verifyCsrf();

        $mes = (int) ($_POST['mes_url'] ?? date('m'));
        $ano = (int) ($_POST['ano_url'] ?? date('Y'));

        $descricao     = trim((string) ($_POST['descricao'] ?? ''));
        $valorApostado = $this->parseMoney((string) ($_POST['valor_apostado'] ?? '0'));
        $status        = trim((string) ($_POST['status'] ?? 'pendente'));
        $dataAposta    = trim((string) ($_POST['data_aposta'] ?? date('Y-m-d')));
        $observacao    = trim((string) ($_POST['observacao'] ?? ''));

        $descricoes = $_POST['sel_descricao'] ?? [];
        $categorias = $_POST['sel_categoria_id'] ?? [];
        $odds       = $_POST['sel_odd'] ?? [];

        $selections = [];
        $oddCalculada = 1.0;

        foreach ($descricoes as $i => $d) {
            $d = trim((string) $d);
            if ($d === '') {
                continue;
            }
            $oddSel = $this->parseDecimal((string) ($odds[$i] ?? '1'));
            $oddCalculada *= $oddSel > 0 ? $oddSel : 1;
            $selections[] = [
                'descricao'    => $d,
                'categoria_id' => (int) ($categorias[$i] ?? 0) ?: null,
                'odd'          => $oddSel,
            ];
        }

        // A odd principal pode ser editada manualmente; se não informada, usa o produto calculado.
        $oddInformada = trim((string) ($_POST['odd'] ?? ''));
        $odd = $oddInformada !== '' ? $this->parseDecimal($oddInformada) : $oddCalculada;

        if (count($selections) < 2) {
            $this->setFlash('error', 'Inclua ao menos duas seleções para uma aposta múltipla.');
            $this->redirect("/apostas?mes={$mes}&ano={$ano}");
            return;
        }

        $error = $this->validateBet($descricao, $odd, $valorApostado, $status, $dataAposta);
        if ($error !== null) {
            $this->setFlash('error', $error);
            $this->redirect("/apostas?mes={$mes}&ano={$ano}");
            return;
        }

        $this->model->createMultiple([
            'usuario_id'       => $userId,
            'descricao'        => $descricao,
            'odd'              => $odd,
            'valor_apostado'   => $valorApostado,
            'status'           => $status,
            'valor_fechamento' => $status !== 'pendente' ? $this->parseMoney((string) ($_POST['valor_fechamento'] ?? '')) ?: null : null,
            'data_aposta'      => $dataAposta,
            'data_resultado'   => $status !== 'pendente' ? date('Y-m-d') : null,
            'observacao'       => $observacao ?: null,
        ], $selections);

        $this->setFlash('success', 'Aposta múltipla registrada com sucesso!');
        $this->redirect("/apostas?mes={$mes}&ano={$ano}");
    }

    // ----------------------------------------------------------------
    // Finalizar (rápido) — usado no extrato diário
    // ----------------------------------------------------------------

    public function finalize(string $id): void
    {
        $userId = $this->requireBetAccess();
        $this->verifyCsrf();

        $bet = $this->model->find((int) $id);
        if ($bet === null || (int) $bet['usuario_id'] !== $userId) {
            $this->json(['error' => 'Aposta não encontrada.'], 404);
            return;
        }

        $status = trim((string) ($_POST['status'] ?? ''));
        if (!in_array($status, ['vitoria', 'derrota', 'reembolso'], true)) {
            $this->json(['error' => 'Status inválido.'], 400);
            return;
        }

        $valorFechamentoRaw = trim((string) ($_POST['valor_fechamento'] ?? ''));
        $valorFechamento = $valorFechamentoRaw !== '' ? $this->parseMoney($valorFechamentoRaw) : null;

        $this->model->finalize((int) $id, $status, $valorFechamento, date('Y-m-d'));

        $this->json(['success' => true]);
    }

    // ----------------------------------------------------------------
    // Excluir
    // ----------------------------------------------------------------

    public function destroy(string $id): void
    {
        $userId = $this->requireBetAccess();
        $this->verifyCsrf();

        $bet = $this->model->find((int) $id);
        if ($bet === null || (int) $bet['usuario_id'] !== $userId) {
            $this->setFlash('error', 'Aposta não encontrada.');
            $this->redirect('/apostas');
            return;
        }

        $mes = (int) date('m', strtotime((string) $bet['data_aposta']));
        $ano = (int) date('Y', strtotime((string) $bet['data_aposta']));

        $this->model->delete((int) $id);

        $this->setFlash('success', 'Aposta removida.');
        $this->redirect("/apostas?mes={$mes}&ano={$ano}");
    }

    // ----------------------------------------------------------------
    // Banca: entrada / saque
    // ----------------------------------------------------------------

    public function bankMovementStore(): void
    {
        $userId = $this->requireBetAccess();
        $this->verifyCsrf();

        $mes = (int) ($_POST['mes_url'] ?? date('m'));
        $ano = (int) ($_POST['ano_url'] ?? date('Y'));

        $tipo      = trim((string) ($_POST['tipo'] ?? ''));
        $valor     = $this->parseMoney((string) ($_POST['valor'] ?? '0'));
        $data      = trim((string) ($_POST['data'] ?? date('Y-m-d')));
        $descricao = trim((string) ($_POST['descricao'] ?? ''));

        if (!in_array($tipo, ['entrada', 'saque'], true)) {
            $this->setFlash('error', 'Tipo de movimento inválido.');
            $this->redirect("/apostas?mes={$mes}&ano={$ano}");
            return;
        }

        if ($valor <= 0) {
            $this->setFlash('error', 'Informe um valor maior que zero.');
            $this->redirect("/apostas?mes={$mes}&ano={$ano}");
            return;
        }

        if (!$this->isValidDate($data)) {
            $this->setFlash('error', 'Informe uma data válida.');
            $this->redirect("/apostas?mes={$mes}&ano={$ano}");
            return;
        }

        $this->bankModel->create([
            'usuario_id' => $userId,
            'tipo'       => $tipo,
            'valor'      => $valor,
            'descricao'  => $descricao ?: null,
            'data'       => $data,
        ]);

        $this->setFlash('success', $tipo === 'entrada' ? 'Entrada registrada na banca!' : 'Saque registrado na banca!');
        $this->redirect("/apostas?mes={$mes}&ano={$ano}");
    }

    // ----------------------------------------------------------------
    // Link público
    // ----------------------------------------------------------------

    public function generateShareLink(): void
    {
        $userId = $this->requireBetAccess();
        $this->verifyCsrf();

        $this->linkModel->generateForUser($userId);

        $this->setFlash('success', 'Novo link público gerado!');
        $this->redirect('/apostas');
    }

    public function revokeShareLink(): void
    {
        $userId = $this->requireBetAccess();
        $this->verifyCsrf();

        $this->linkModel->revokeForUser($userId);

        $this->setFlash('success', 'Link público revogado.');
        $this->redirect('/apostas');
    }

    /**
     * Visualização pública (somente leitura) via token — não requer autenticação.
     */
    public function publicView(string $token): void
    {
        $link = $this->linkModel->findByToken($token);

        if ($link === null) {
            http_response_code(404);
            echo '<h1>404 - Link não encontrado ou expirado</h1>';
            exit;
        }

        $userId = (int) $link['usuario_id'];

        $mes = max(1, min(12, (int) ($_GET['mes'] ?? date('m'))));
        $ano = max(2000, min((int) date('Y') + 1, (int) ($_GET['ano'] ?? date('Y'))));

        $kpis  = $this->model->getKpis($userId, $mes, $ano);
        $daily = $this->model->getDailyTotals($userId, $mes, $ano);
        $saldoBanca = $this->bankModel->getBalance($userId);

        $this->view('bets/public', [
            'mes'        => $mes,
            'ano'        => $ano,
            'kpis'       => $kpis,
            'daily'      => $daily,
            'saldoBanca' => $saldoBanca,
            'token'      => $token,
        ], 'none');
    }

    public function publicDayRecords(string $token, string $date): void
    {
        $link = $this->linkModel->findByToken($token);
        if ($link === null || !$this->isValidDate($date)) {
            $this->json(['error' => 'Link ou data inválidos.'], 404);
            return;
        }

        $records = $this->model->findByDate((int) $link['usuario_id'], $date);
        foreach ($records as &$r) {
            $r['lucro'] = $this->model->calcLucro($r);
        }
        unset($r);

        $this->json(['records' => $records]);
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function validateBet(string $descricao, float $odd, float $valorApostado, string $status, string $dataAposta): ?string
    {
        if ($descricao === '') {
            return 'Informe a descrição da aposta.';
        }

        if ($odd <= 1) {
            return 'Informe uma odd válida (maior que 1).';
        }

        if ($valorApostado <= 0) {
            return 'Informe um valor apostado maior que zero.';
        }

        if (!in_array($status, ['pendente', 'vitoria', 'derrota', 'reembolso'], true)) {
            return 'Status inválido.';
        }

        if (!$this->isValidDate($dataAposta)) {
            return 'Informe uma data válida.';
        }

        return null;
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d !== false && $d->format('Y-m-d') === $date;
    }

    private function parseMoney(string $raw): float
    {
        $raw = trim($raw);
        if ($raw === '') {
            return 0.0;
        }

        $normalized = preg_replace('/[^\d,\.\-]/', '', $raw) ?? '';
        if ($normalized === '' || $normalized === '-') {
            return 0.0;
        }

        // Formato BR: milhar com ponto, decimal com vírgula (ex: 1.234,56)
        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        return (float) $normalized;
    }

    /** Parse de números decimais simples (odds), aceitando vírgula ou ponto. */
    private function parseDecimal(string $raw): float
    {
        $raw = trim($raw);
        if ($raw === '') {
            return 0.0;
        }
        return (float) str_replace(',', '.', preg_replace('/[^\d,\.]/', '', $raw) ?? '0');
    }
}

<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\SimpleEntry;

/**
 * SimpleEntryController - independent module for simple input/output entries.
 */
class SimpleEntryController extends Controller
{
    private SimpleEntry $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new SimpleEntry();
    }

    public function index(): void
    {
        $this->requireAuth();

        $userId = (int) $this->getUserId();
        $mes    = (int) ($_GET['mes'] ?? date('m'));
        $ano    = (int) ($_GET['ano'] ?? date('Y'));

        $filters = [
            'mes'    => $mes,
            'ano'    => $ano,
            'tipo'   => trim((string) ($_GET['tipo'] ?? '')),
            'search' => trim((string) ($_GET['search'] ?? '')),
            'page'   => (int) ($_GET['page'] ?? 1),
            'limit'  => 20,
        ];

        $result = $this->model->findByUser($userId, $filters);
        $totals = $this->model->getTotalsForFilter($userId, $filters);
        $saldoTotal = $this->model->getAccumulatedBalance($userId);

        $this->view('simple_entries/index', [
            'records'      => $result['data'],
            'total'        => $result['total'],
            'pages'        => $result['pages'],
            'currentPage'  => $result['page'],
            'mes'          => $mes,
            'ano'          => $ano,
            'filters'      => $filters,
            'totalEntrada' => $totals['entrada'],
            'totalSaida'   => $totals['saida'],
            'saldoMes'     => $totals['saldo'],
            'saldoTotal'   => $saldoTotal,
            'csrf'         => $this->csrfToken(),
            'flash'        => $this->getFlash(),
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $payload = $this->sanitizePayload($_POST);
        $error = $this->validatePayload($payload);

        if ($error !== null) {
            $this->setFlash('error', $error);
            $this->redirect('/modulo-simples?mes=' . $payload['mes_url'] . '&ano=' . $payload['ano_url']);
            return;
        }

        $this->model->create([
            'usuario_id'      => (int) $this->getUserId(),
            'descricao'       => $payload['descricao'],
            'tipo'            => $payload['tipo'],
            'valor'           => $payload['valor'],
            'data_referencia' => $payload['data_referencia'],
            'observacao'      => $payload['observacao'],
        ]);

        $this->setFlash('success', 'Registro criado com sucesso!');
        $this->redirect('/modulo-simples?mes=' . $payload['mes_url'] . '&ano=' . $payload['ano_url']);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $record = $this->model->find((int) $id);
        if ($record === null || (int) $record['usuario_id'] !== (int) $this->getUserId()) {
            $this->setFlash('error', 'Registro não encontrado.');
            $this->redirect('/modulo-simples');
            return;
        }

        $payload = $this->sanitizePayload($_POST);
        $error = $this->validatePayload($payload);

        if ($error !== null) {
            $this->setFlash('error', $error);
            $this->redirect('/modulo-simples?mes=' . $payload['mes_url'] . '&ano=' . $payload['ano_url']);
            return;
        }

        $this->model->update((int) $id, [
            'descricao'       => $payload['descricao'],
            'tipo'            => $payload['tipo'],
            'valor'           => $payload['valor'],
            'data_referencia' => $payload['data_referencia'],
            'observacao'      => $payload['observacao'],
        ]);

        $this->setFlash('success', 'Registro atualizado!');
        $this->redirect('/modulo-simples?mes=' . $payload['mes_url'] . '&ano=' . $payload['ano_url']);
    }

    public function destroy(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $record = $this->model->find((int) $id);
        if ($record === null || (int) $record['usuario_id'] !== (int) $this->getUserId()) {
            $this->setFlash('error', 'Registro não encontrado.');
            $this->redirect('/modulo-simples');
            return;
        }

        $mes = (int) date('m', strtotime((string) $record['data_referencia']));
        $ano = (int) date('Y', strtotime((string) $record['data_referencia']));

        $this->model->delete((int) $id);

        $this->setFlash('success', 'Registro removido.');
        $this->redirect('/modulo-simples?mes=' . $mes . '&ano=' . $ano);
    }

    public function exportCsv(): void
    {
        $this->requireAuth();

        $userId = (int) $this->getUserId();
        $mes    = (int) ($_GET['mes'] ?? date('m'));
        $ano    = (int) ($_GET['ano'] ?? date('Y'));

        $filters = [
            'mes'    => $mes,
            'ano'    => $ano,
            'tipo'   => trim((string) ($_GET['tipo'] ?? '')),
            'search' => trim((string) ($_GET['search'] ?? '')),
            'page'   => 1,
            'limit'  => 5000,
        ];

        $rows = $this->model->findByUser($userId, $filters)['data'];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="modulo_simples_' . $ano . '_' . str_pad((string) $mes, 2, '0', STR_PAD_LEFT) . '.csv"');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }

        fputcsv($out, ['Data', 'Tipo', 'Descricao', 'Valor', 'Observacao'], ';');

        foreach ($rows as $r) {
            fputcsv($out, [
                date('d/m/Y', strtotime((string) $r['data_referencia'])),
                $r['tipo'],
                $r['descricao'],
                number_format((float) $r['valor'], 2, ',', '.'),
                (string) ($r['observacao'] ?? ''),
            ], ';');
        }

        fclose($out);
        exit;
    }

    /**
     * @param array<string,mixed> $input
     * @return array{descricao: string, tipo: string, valor: float, data_referencia: string, observacao: string, mes_url: int, ano_url: int}
     */
    private function sanitizePayload(array $input): array
    {
        $descricao = trim((string) ($input['descricao'] ?? ''));
        $tipo = trim((string) ($input['tipo'] ?? 'saida'));
        $valorRaw = trim((string) ($input['valor'] ?? '0'));
        $dataReferencia = trim((string) ($input['data_referencia'] ?? date('Y-m-d')));
        $observacao = trim((string) ($input['observacao'] ?? ''));

        $valor = $this->parseMoney($valorRaw);

        $mes = (int) date('m', strtotime($dataReferencia ?: date('Y-m-d')));
        $ano = (int) date('Y', strtotime($dataReferencia ?: date('Y-m-d')));

        return [
            'descricao'       => $descricao,
            'tipo'            => $tipo,
            'valor'           => $valor,
            'data_referencia' => $dataReferencia,
            'observacao'      => $observacao,
            'mes_url'         => $mes,
            'ano_url'         => $ano,
        ];
    }

    /**
     * @param array{descricao: string, tipo: string, valor: float, data_referencia: string} $payload
     */
    private function validatePayload(array $payload): ?string
    {
        if ($payload['descricao'] === '') {
            return 'Informe a descrição.';
        }

        if (!in_array($payload['tipo'], ['entrada', 'saida'], true)) {
            return 'Tipo inválido.';
        }

        if ($payload['valor'] <= 0) {
            return 'Informe um valor maior que zero.';
        }

        if (!$this->isValidDate($payload['data_referencia'])) {
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

        if (str_contains($raw, ',')) {
            $normalized = str_replace('.', '', $raw);
            $normalized = str_replace(',', '.', $normalized);
            return (float) $normalized;
        }

        return (float) $raw;
    }
}

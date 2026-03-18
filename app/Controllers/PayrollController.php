<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Payroll;
use App\Models\Movement;
use App\Core\Database;

/**
 * PayrollController
 */
class PayrollController extends Controller
{
    private Payroll $model;
    private Movement $movModel;

    public function __construct()
    {
        parent::__construct();
        $this->model    = new Payroll();
        $this->movModel = new Movement();
    }

    // ----------------------------------------------------------------
    // Helper: sincroniza folha com movimentação de entrada
    // ----------------------------------------------------------------

    private function syncMovement(int $userId, array $data, ?int $movimentacaoId = null): int
    {
        $db = Database::getInstance();

        // Busca categoria "Salário" ou primeira categoria de receita
        $cat = $db->fetch(
            "SELECT id FROM categorias WHERE tipo = 'receita' AND nome LIKE '%salário%' LIMIT 1"
        );
        if (!$cat) {
            $cat = $db->fetch("SELECT id FROM categorias WHERE tipo = 'receita' LIMIT 1");
        }
        $catId = $cat ? (int) $cat['id'] : null;

        $dataComp = !empty($data['data_pagamento']) ? $data['data_pagamento']
                    : sprintf('%04d-%02d-01', $data['ano_referencia'], $data['mes_referencia']);

        $movData = [
            'usuario_id'       => $userId,
            'descricao'        => $data['descricao'] . ' (' . $data['mes_referencia'] . '/' . $data['ano_referencia'] . ')',
            'tipo'             => 'entrada',
            'modo'             => 'unico',
            'categoria_id'     => $catId,
            'subcategoria_id'  => null,
            'valor'            => (float) $data['valor_liquido'],
            'data_competencia' => $dataComp,
            'data_vencimento'  => $dataComp,
            'parcela_atual'    => 1,
            'total_parcelas'   => 1,
            'validado'         => 0,
            'observacao'       => 'Gerado automaticamente pela Folha de Pagamento',
        ];

        if ($movimentacaoId) {
            $this->movModel->update($movimentacaoId, $movData);
            return $movimentacaoId;
        }

        $this->movModel->create($movData);
        return (int) $db->lastInsertId();
    }

    public function index(): void
    {
        $this->requireAuth();

        $userId   = $this->getUserId();
        $payrolls = $this->model->findByUser($userId);

        // Attach items to each
        foreach ($payrolls as &$p) {
            $p['itens'] = $this->model->getItems((int) $p['id']);
        }
        unset($p);

        $this->view('payroll/index', [
            'payrolls' => $payrolls,
            'csrf'     => $this->csrfToken(),
            'flash'    => $this->getFlash(),
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();

        $this->view('payroll/index', [
            'payrolls' => $this->model->findByUser($this->getUserId()),
            'csrf'     => $this->csrfToken(),
            'flash'    => $this->getFlash(),
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $userId = $this->getUserId();

        $descricoes     = $_POST['item_descricao'] ?? [];
        $tipos          = $_POST['item_tipo']      ?? [];
        $valores        = $_POST['item_valor']     ?? [];

        $itens   = [];
        $bruto   = 0.0;
        $descontos = [];

        foreach ($descricoes as $i => $desc) {
            $v   = (float) str_replace(['.', ','], ['', '.'], $valores[$i] ?? '0');
            $t   = $tipos[$i] ?? 'provento';
            $itens[] = ['descricao' => trim($desc), 'tipo' => $t, 'valor' => $v];

            if ($t === 'provento') {
                $bruto += $v;
            } else {
                $descontos[trim($desc)] = $v;
            }
        }

        $liquido = $this->model->calculateLiquid($bruto, $descontos);

        $data = [
            'usuario_id'     => $userId,
            'descricao'      => trim($_POST['descricao']      ?? 'Folha de Pagamento'),
            'valor_bruto'    => $bruto,
            'descontos'      => json_encode($descontos),
            'valor_liquido'  => $liquido,
            'mes_referencia' => (int) ($_POST['mes_referencia'] ?? date('m')),
            'ano_referencia' => (int) ($_POST['ano_referencia'] ?? date('Y')),
            'data_pagamento' => !empty($_POST['data_pagamento']) ? $_POST['data_pagamento'] : null,
            'observacao'     => trim($_POST['observacao'] ?? ''),
        ];

        $folhaId = $this->model->createWithItems($data, $itens);

        // Remove preview movement for this month before creating the real one
        $dataComp = !empty($data['data_pagamento']) ? $data['data_pagamento']
                    : sprintf('%04d-%02d-01', $data['ano_referencia'], $data['mes_referencia']);
        $previewMonth = sprintf('%04d-%02d', $data['ano_referencia'], $data['mes_referencia']);
        Database::getInstance()->execute(
            "DELETE FROM movimentacoes WHERE usuario_id = ? AND observacao LIKE '%[PREVIEW_FOLHA]%'
             AND DATE_FORMAT(data_competencia, '%Y-%m') = ?",
            [$userId, $previewMonth]
        );

        // Cria movimentação de entrada vinculada
        $movId = $this->syncMovement($userId, $data);
        Database::getInstance()->execute(
            "UPDATE folha_pagamento SET movimentacao_id = ? WHERE id = ?",
            [$movId, $folhaId]
        );

        $this->setFlash('success', 'Folha de pagamento registrada e lançada em movimentações!');
        $this->redirect('/folha-pagamento');
    }

    public function edit(string $id): void
    {
        $this->requireAuth();

        $folha = $this->model->find((int) $id);
        if ($folha === null || (int) $folha['usuario_id'] !== $this->getUserId()) {
            $this->setFlash('error', 'Registro não encontrado.');
            $this->redirect('/folha-pagamento');
            return;
        }
        $folha['itens'] = $this->model->getItems((int) $id);

        $payrolls = $this->model->findByUser($this->getUserId());
        foreach ($payrolls as &$p) {
            $p['itens'] = $this->model->getItems((int) $p['id']);
        }
        unset($p);

        $this->view('payroll/index', [
            'payrolls' => $payrolls,
            'editItem' => $folha,
            'csrf'     => $this->csrfToken(),
            'flash'    => $this->getFlash(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $folha = $this->model->find((int) $id);
        if ($folha === null || (int) $folha['usuario_id'] !== $this->getUserId()) {
            $this->setFlash('error', 'Não encontrado.');
            $this->redirect('/folha-pagamento');
            return;
        }

        // Delete existing items and recreate
        $this->model->db->execute("DELETE FROM folha_itens WHERE folha_id = ?", [(int) $id]);

        $descricoes = $_POST['item_descricao'] ?? [];
        $tipos      = $_POST['item_tipo']      ?? [];
        $valores    = $_POST['item_valor']     ?? [];

        $bruto     = 0.0;
        $descontos = [];
        $itens     = [];

        foreach ($descricoes as $i => $desc) {
            $v  = (float) str_replace(['.', ','], ['', '.'], $valores[$i] ?? '0');
            $t  = $tipos[$i] ?? 'provento';
            $itens[] = ['descricao' => trim($desc), 'tipo' => $t, 'valor' => $v];
            if ($t === 'provento') {
                $bruto += $v;
            } else {
                $descontos[trim($desc)] = $v;
            }
        }

        $liquido = $this->model->calculateLiquid($bruto, $descontos);

        $data = [
            'descricao'      => trim($_POST['descricao']      ?? ''),
            'valor_bruto'    => $bruto,
            'descontos'      => json_encode($descontos),
            'valor_liquido'  => $liquido,
            'mes_referencia' => (int) ($_POST['mes_referencia'] ?? date('m')),
            'ano_referencia' => (int) ($_POST['ano_referencia'] ?? date('Y')),
            'data_pagamento' => !empty($_POST['data_pagamento']) ? $_POST['data_pagamento'] : null,
            'observacao'     => trim($_POST['observacao'] ?? ''),
        ];

        $this->model->update((int) $id, $data);

        foreach ($itens as $item) {
            $this->model->db->execute(
                "INSERT INTO folha_itens (folha_id, descricao, tipo, valor) VALUES (?, ?, ?, ?)",
                [(int) $id, $item['descricao'], $item['tipo'], $item['valor']]
            );
        }

        // Atualiza ou cria movimentação vinculada
        $movId = !empty($folha['movimentacao_id']) ? (int) $folha['movimentacao_id'] : null;
        $newMovId = $this->syncMovement($this->getUserId(), $data, $movId);
        if (!$movId) {
            Database::getInstance()->execute(
                "UPDATE folha_pagamento SET movimentacao_id = ? WHERE id = ?",
                [$newMovId, (int) $id]
            );
        }

        $this->setFlash('success', 'Folha atualizada!');
        $this->redirect('/folha-pagamento');
    }

    public function destroy(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $folha = $this->model->find((int) $id);
        if ($folha === null || (int) $folha['usuario_id'] !== $this->getUserId()) {
            $this->setFlash('error', 'Não encontrado.');
            $this->redirect('/folha-pagamento');
            return;
        }

        // Remove movimentação vinculada
        if (!empty($folha['movimentacao_id'])) {
            $this->movModel->delete((int) $folha['movimentacao_id']);
        }

        $this->model->deleteWithItems((int) $id);
        $this->setFlash('success', 'Registro excluído.');
        $this->redirect('/folha-pagamento');
    }
}

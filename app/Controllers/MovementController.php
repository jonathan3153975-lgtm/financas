<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Models\Movement;
use App\Models\FixedMovement;
use App\Models\Category;
use App\Models\Payroll;
use App\Models\CreditCard;

/**
 * MovementController - Full CRUD for movements and fixed movements
 */
class MovementController extends Controller
{
    private Movement $model;
    private FixedMovement $fixedModel;
    private Category $catModel;
    private Payroll $payrollModel;
    private CreditCard $cardModel;

    public function __construct()
    {
        parent::__construct();
        $this->model        = new Movement();
        $this->fixedModel   = new FixedMovement();
        $this->catModel     = new Category();
        $this->payrollModel = new Payroll();
        $this->cardModel    = new CreditCard();
    }

    // ----------------------------------------------------------------
    // Movements
    // ----------------------------------------------------------------

    public function index(): void
    {
        $this->requireAuth();

        $userId = $this->getUserId();
        $mes    = (int) ($_GET['mes']  ?? date('m'));
        $ano    = (int) ($_GET['ano']  ?? date('Y'));

        $filters = [
            'mes'          => $mes,
            'ano'          => $ano,
            'tipo'         => $_GET['tipo']         ?? '',
            'categoria_id' => $_GET['categoria_id'] ?? '',
            'validado'     => $_GET['validado']     ?? '',
            'search'       => $_GET['search']       ?? '',
            'page'         => (int) ($_GET['page']  ?? 1),
            'limit'        => 20,
        ];

        // Auto-generate fixed movements, payroll preview, and credit card bills
        $this->fixedModel->generateForMonth($userId, $mes, $ano);
        $this->payrollModel->generateMovementForMonth($userId, $mes, $ano);
        $this->cardModel->generateBillsForMonth($userId, $mes, $ano);

        $result = $this->model->findByUser($userId, $filters);

        $totalEntrada  = $this->model->getTotalByType($userId, 'entrada', $mes, $ano);
        $totalSaida    = $this->model->getTotalByType($userId, 'saida',   $mes, $ano);
        $saldoMes      = $this->model->getMonthBalance($userId, $mes, $ano);
        $saldoTotal    = $this->model->getTotalBalance($userId);
        $pendingCount  = $this->model->getPendingCount($userId);
        $pendingByType = $this->model->getPendingCountByType($userId);

        $categories = $this->catModel->getAllWithSubcategories();

        $this->view('movements/index', [
            'movements'      => $result['data'],
            'total'          => $result['total'],
            'pages'          => $result['pages'],
            'currentPage'    => $result['page'],
            'mes'            => $mes,
            'ano'            => $ano,
            'filters'        => $filters,
            'totalEntrada'   => $totalEntrada,
            'totalSaida'     => $totalSaida,
            'saldoMes'       => $saldoMes,
            'saldoTotal'     => $saldoTotal,
            'pendingCount'   => $pendingCount,
            'pendingEntrada' => $pendingByType['entrada'],
            'pendingSaida'   => $pendingByType['saida'],
            'categories'     => $categories,
            'csrf'           => $this->csrfToken(),
            'flash'          => $this->getFlash(),
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();

        $categories = $this->catModel->getAllWithSubcategories();

        $this->view('movements/form', [
            'movement'   => null,
            'categories' => $categories,
            'csrf'       => $this->csrfToken(),
            'flash'      => $this->getFlash(),
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $userId = $this->getUserId();

        $data = $this->sanitizeMovementData($_POST);
        $v    = $this->validateMovementData($_POST);

        if ($v->fails()) {
            $this->setFlash('error', $v->firstError());
            $this->redirect('/movimentacoes/criar');
            return;
        }

        $modo = $data['modo'];

        if ($modo === 'parcelamento') {
            // Registro individual da parcela informada
            $data['usuario_id']   = $userId;
            $data['parcela_atual'] = max(1, (int) ($_POST['parcela_atual'] ?? 1));
            $data['total_parcelas'] = max(1, (int) ($data['total_parcelas'] ?? 1));
            unset($data['_csrf']);
            $this->model->create($data);

        } elseif ($modo === 'fixo') {
            // Cria template em movimentacoes_fixas
            $dataFim = !empty($_POST['data_fim']) ? $_POST['data_fim'] : null;
            $this->fixedModel->create([
                'usuario_id'      => $userId,
                'descricao'       => $data['descricao'],
                'tipo'            => $data['tipo'],
                'categoria_id'    => $data['categoria_id'],
                'subcategoria_id' => $data['subcategoria_id'],
                'valor'           => $data['valor'],
                'dia_vencimento'  => (int) date('d', strtotime($data['data_competencia'])),
                'data_fim'        => $dataFim,
                'observacao'      => $data['observacao'],
                'ativo'           => 1,
            ]);
            // Também cria o lançamento do mês atual
            $data['usuario_id']    = $userId;
            $data['parcela_atual'] = 1;
            $data['total_parcelas'] = 1;
            unset($data['_csrf']);
            $this->model->create($data);

        } else {
            $data['usuario_id']    = $userId;
            $data['total_parcelas'] = 1;
            $data['parcela_atual']  = 1;
            unset($data['_csrf']);
            $this->model->create($data);
        }

        $this->setFlash('success', 'Movimentação criada com sucesso!');
        $dataComp = $data['data_competencia'] ?? date('Y-m-d');
        $this->redirect('/movimentacoes?mes=' . (int)date('m', strtotime($dataComp)) . '&ano=' . (int)date('Y', strtotime($dataComp)));
    }

    public function edit(string $id): void
    {
        $this->requireAuth();

        $movement = $this->model->find((int) $id);
        if ($movement === null || (int) $movement['usuario_id'] !== $this->getUserId()) {
            $this->setFlash('error', 'Movimentação não encontrada.');
            $this->redirect('/movimentacoes');
            return;
        }

        $categories = $this->catModel->getAllWithSubcategories();

        $this->view('movements/edit_page', [
            'movement'   => $movement,
            'categories' => $categories,
            'csrf'       => $this->csrfToken(),
            'flash'      => $this->getFlash(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $movement = $this->model->find((int) $id);
        if ($movement === null || (int) $movement['usuario_id'] !== $this->getUserId()) {
            $this->setFlash('error', 'Movimentação não encontrada.');
            $this->redirect('/movimentacoes');
            return;
        }

        $data = $this->sanitizeMovementData($_POST);
        $v    = $this->validateMovementData($_POST);

        if ($v->fails()) {
            $this->setFlash('error', $v->firstError());
            $this->redirect('/movimentacoes/' . $id . '/editar');
            return;
        }

        $this->model->update((int) $id, $data);

        $this->setFlash('success', 'Movimentação atualizada!');
        $this->redirect('/movimentacoes');
    }

    public function destroy(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $movement = $this->model->find((int) $id);
        if ($movement === null || (int) $movement['usuario_id'] !== $this->getUserId()) {
            $this->json(['success' => false, 'message' => 'Não encontrada.'], 404);
            return;
        }

        $this->model->delete((int) $id);

        if ($this->isAjax()) {
            $this->json(['success' => true]);
        } else {
            $this->setFlash('success', 'Movimentação excluída.');
            $this->redirect('/movimentacoes');
        }
    }

    public function validate(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $movement = $this->model->find((int) $id);
        if ($movement === null || (int) $movement['usuario_id'] !== $this->getUserId()) {
            $this->json(['success' => false, 'message' => 'Não encontrada.'], 404);
            return;
        }

        $this->model->validate((int) $id);
        $this->json(['success' => true, 'message' => 'Movimentação validada!']);
    }

    public function revert(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $movement = $this->model->find((int) $id);
        if ($movement === null || (int) $movement['usuario_id'] !== $this->getUserId()) {
            $this->json(['success' => false, 'message' => 'Não encontrada.'], 404);
            return;
        }

        $this->model->revert((int) $id);
        $this->json(['success' => true, 'message' => 'Movimentação revertida para pendente!']);
    }

    // ----------------------------------------------------------------
    // Fixed movements
    // ----------------------------------------------------------------

    public function fixedIndex(): void
    {
        $this->requireAuth();

        $userId = $this->getUserId();
        $fixas  = $this->fixedModel->findByUser($userId);
        $categories = $this->catModel->getAllWithSubcategories();

        $this->view('movements/fixed', [
            'fixas'      => $fixas,
            'categories' => $categories,
            'csrf'       => $this->csrfToken(),
            'flash'      => $this->getFlash(),
        ]);
    }

    public function fixedStore(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $userId = $this->getUserId();

        $data = [
            'usuario_id'      => $userId,
            'descricao'       => trim($_POST['descricao']       ?? ''),
            'tipo'            => $_POST['tipo']                 ?? 'saida',
            'categoria_id'    => !empty($_POST['categoria_id'])    ? (int) $_POST['categoria_id']    : null,
            'subcategoria_id' => !empty($_POST['subcategoria_id']) ? (int) $_POST['subcategoria_id'] : null,
            'valor'           => (float) str_replace(['.', ','], ['', '.'], $_POST['valor'] ?? '0'),
            'dia_vencimento'  => (int) ($_POST['dia_vencimento'] ?? 1),
            'observacao'      => trim($_POST['observacao'] ?? ''),
        ];

        if (empty($data['descricao']) || $data['valor'] <= 0) {
            $this->setFlash('error', 'Preencha descrição e valor.');
            $this->redirect('/movimentacoes/fixas');
            return;
        }

        $this->fixedModel->create($data);
        $this->setFlash('success', 'Movimentação fixa criada!');
        $this->redirect('/movimentacoes/fixas');
    }

    public function fixedUpdate(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $fixa = $this->fixedModel->find((int) $id);
        if ($fixa === null || (int) $fixa['usuario_id'] !== $this->getUserId()) {
            $this->setFlash('error', 'Não encontrada.');
            $this->redirect('/movimentacoes/fixas');
            return;
        }

        $data = [
            'descricao'       => trim($_POST['descricao']       ?? ''),
            'tipo'            => $_POST['tipo']                 ?? 'saida',
            'categoria_id'    => !empty($_POST['categoria_id'])    ? (int) $_POST['categoria_id']    : null,
            'subcategoria_id' => !empty($_POST['subcategoria_id']) ? (int) $_POST['subcategoria_id'] : null,
            'valor'           => (float) str_replace(['.', ','], ['', '.'], $_POST['valor'] ?? '0'),
            'dia_vencimento'  => (int) ($_POST['dia_vencimento'] ?? 1),
            'observacao'      => trim($_POST['observacao'] ?? ''),
        ];

        $this->fixedModel->update((int) $id, $data);
        $this->setFlash('success', 'Atualizado!');
        $this->redirect('/movimentacoes/fixas');
    }

    public function fixedToggle(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $fixa = $this->fixedModel->find((int) $id);
        if ($fixa === null || (int) $fixa['usuario_id'] !== $this->getUserId()) {
            $this->json(['success' => false], 403);
            return;
        }

        $this->fixedModel->toggleActive((int) $id);
        $this->json(['success' => true, 'ativo' => !(bool) $fixa['ativo']]);
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function sanitizeMovementData(array $post): array
    {
        return [
            'descricao'        => trim($post['descricao']       ?? ''),
            'tipo'             => $post['tipo']                  ?? 'saida',
            'modo'             => $post['modo']                  ?? 'unico',
            'categoria_id'     => !empty($post['categoria_id'])    ? (int) $post['categoria_id']    : null,
            'subcategoria_id'  => !empty($post['subcategoria_id']) ? (int) $post['subcategoria_id'] : null,
            'valor'            => (float) str_replace(['.', ','], ['', '.'], $post['valor'] ?? '0'),
            'data_competencia' => $post['data_competencia']      ?? date('Y-m-d'),
            'data_vencimento'  => !empty($post['data_vencimento']) ? $post['data_vencimento'] : null,
            'parcela_atual'    => max(1, (int) ($post['parcela_atual']  ?? 1)),
            'total_parcelas'   => max(1, (int) ($post['total_parcelas'] ?? 1)),
            'observacao'       => trim($post['observacao']        ?? ''),
        ];
    }

    private function validateMovementData(array $post): Validator
    {
        $v = new Validator($post);
        $v->required('descricao', 'Descrição')
          ->required('valor',     'Valor')
          ->required('data_competencia', 'Data');
        return $v;
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

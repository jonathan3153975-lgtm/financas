<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Savings;
use App\Models\SavingsMovement;
use App\Models\Movement;
use App\Models\Category;

/**
 * SavingsController — full CRUD for poupanças + movimentos
 */
class SavingsController extends Controller
{
    private Savings $model;
    private SavingsMovement $movModel;
    private Movement $movimentacao;
    private Category $catModel;

    public function __construct()
    {
        parent::__construct();
        $this->model        = new Savings();
        $this->movModel     = new SavingsMovement();
        $this->movimentacao = new Movement();
        $this->catModel     = new Category();
    }

    // ----------------------------------------------------------------
    // Index
    // ----------------------------------------------------------------

    public function index(): void
    {
        $this->requireAuth();

        $userId   = $this->getUserId();
        $savings  = $this->model->findByUser($userId);

        // Attach movements to each savings account
        foreach ($savings as &$s) {
            $s['movimentos'] = $this->movModel->findByAccount((int) $s['id']);
        }
        unset($s);

        $this->view('savings/index', [
            'savings'  => $savings,
            'csrf'     => $this->csrfToken(),
            'flash'    => $this->getFlash(),
        ]);
    }

    // ----------------------------------------------------------------
    // Store (create)
    // ----------------------------------------------------------------

    public function store(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $userId = $this->getUserId();

        $nome         = trim($_POST['nome']         ?? '');
        $saldoInicial = (float) str_replace(['.', ','], ['', '.'], $_POST['saldo_inicial'] ?? '0');
        $cor          = trim($_POST['cor']          ?? '#10b981');
        $descricao    = trim($_POST['descricao']    ?? '');

        if (empty($nome)) {
            $this->setFlash('error', 'Informe o nome da poupança.');
            $this->redirect('/poupanca');
            return;
        }

        $this->model->create([
            'usuario_id'    => $userId,
            'nome'          => $nome,
            'saldo_inicial' => $saldoInicial,
            'cor'           => $cor ?: '#10b981',
            'descricao'     => $descricao,
            'ativo'         => 1,
        ]);

        $this->setFlash('success', 'Poupança criada com sucesso!');
        $this->redirect('/poupanca');
    }

    // ----------------------------------------------------------------
    // Update
    // ----------------------------------------------------------------

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $savings = $this->findOrFail((int) $id);
        if (!$savings) return;

        $nome         = trim($_POST['nome']         ?? '');
        $saldoInicial = (float) str_replace(['.', ','], ['', '.'], $_POST['saldo_inicial'] ?? '0');
        $cor          = trim($_POST['cor']          ?? '#10b981');
        $descricao    = trim($_POST['descricao']    ?? '');

        if (empty($nome)) {
            $this->setFlash('error', 'Informe o nome da poupança.');
            $this->redirect('/poupanca');
            return;
        }

        $this->model->update((int) $id, [
            'nome'          => $nome,
            'saldo_inicial' => $saldoInicial,
            'cor'           => $cor ?: '#10b981',
            'descricao'     => $descricao,
        ]);

        $this->setFlash('success', 'Poupança atualizada!');
        $this->redirect('/poupanca');
    }

    // ----------------------------------------------------------------
    // Destroy (soft delete)
    // ----------------------------------------------------------------

    public function destroy(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $savings = $this->findOrFail((int) $id);
        if (!$savings) return;

        $this->model->update((int) $id, ['ativo' => 0]);

        $this->setFlash('success', 'Poupança removida.');
        $this->redirect('/poupanca');
    }

    // ----------------------------------------------------------------
    // Deposit
    // ----------------------------------------------------------------

    public function deposit(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $savings = $this->findOrFail((int) $id);
        if (!$savings) return;

        $userId    = $this->getUserId();
        $valor     = (float) str_replace(['.', ','], ['', '.'], $_POST['valor'] ?? '0');
        $data      = trim($_POST['data']      ?? date('Y-m-d'));
        $descricao = trim($_POST['descricao'] ?? 'Depósito em poupança');
        $linkMov   = (int) ($_POST['_link_movimento'] ?? 0);

        if ($valor <= 0) {
            $this->setFlash('error', 'Informe um valor válido para o depósito.');
            $this->redirect('/poupanca');
            return;
        }

        $movimentacaoId = null;

        // Optionally link to a movimentacao (saída) in the main movements table
        if ($linkMov === 1) {
            $catId = $this->findSavingsCategoryId('despesa');
            $movimentacaoId = $this->movimentacao->create([
                'usuario_id'      => $userId,
                'descricao'       => $descricao ?: ('Depósito em poupança: ' . $savings['nome']),
                'tipo'            => 'saida',
                'modo'            => 'unico',
                'categoria_id'    => $catId,
                'subcategoria_id' => null,
                'valor'           => $valor,
                'data_competencia'=> $data,
                'data_vencimento' => null,
                'parcela_atual'   => 1,
                'total_parcelas'  => 1,
                'observacao'      => 'Gerado automaticamente via Poupança',
                'validado'        => 0,
                'poupanca_id'     => (int) $id,
            ]);
        }

        $this->movModel->create([
            'poupanca_id'     => (int) $id,
            'usuario_id'      => $userId,
            'tipo'            => 'deposito',
            'valor'           => $valor,
            'descricao'       => $descricao,
            'data'            => $data,
            'movimentacao_id' => $movimentacaoId,
        ]);

        $this->setFlash('success', 'Depósito registrado com sucesso!');
        $this->redirect('/poupanca');
    }

    // ----------------------------------------------------------------
    // Withdraw
    // ----------------------------------------------------------------

    public function withdraw(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $savings = $this->findOrFail((int) $id);
        if (!$savings) return;

        $userId       = $this->getUserId();
        $valor        = (float) str_replace(['.', ','], ['', '.'], $_POST['valor'] ?? '0');
        $data         = trim($_POST['data']            ?? date('Y-m-d'));
        $descricao    = trim($_POST['descricao']       ?? 'Saque de poupança');
        $createMov    = (int) ($_POST['_create_movimento'] ?? 1);

        if ($valor <= 0) {
            $this->setFlash('error', 'Informe um valor válido para o saque.');
            $this->redirect('/poupanca');
            return;
        }

        $movimentacaoId = null;

        // Optionally create a receita entry in the main movements table
        if ($createMov === 1) {
            $catId = $this->findSavingsCategoryId('receita');
            $movimentacaoId = $this->movimentacao->create([
                'usuario_id'      => $userId,
                'descricao'       => $descricao ?: ('Resgate de poupança: ' . $savings['nome']),
                'tipo'            => 'entrada',
                'modo'            => 'unico',
                'categoria_id'    => $catId,
                'subcategoria_id' => null,
                'valor'           => $valor,
                'data_competencia'=> $data,
                'data_vencimento' => null,
                'parcela_atual'   => 1,
                'total_parcelas'  => 1,
                'observacao'      => 'Gerado automaticamente via Poupança',
                'validado'        => 0,
                'poupanca_id'     => (int) $id,
            ]);
        }

        $this->movModel->create([
            'poupanca_id'     => (int) $id,
            'usuario_id'      => $userId,
            'tipo'            => 'saque',
            'valor'           => $valor,
            'descricao'       => $descricao,
            'data'            => $data,
            'movimentacao_id' => $movimentacaoId,
        ]);

        $this->setFlash('success', 'Saque registrado com sucesso!');
        $this->redirect('/poupanca');
    }

    // ----------------------------------------------------------------
    // Destroy a single movement
    // ----------------------------------------------------------------

    public function destroyMovement(string $id, string $movId): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $savings = $this->findOrFail((int) $id);
        if (!$savings) return;

        $mov = $this->movModel->find((int) $movId);
        if ($mov === null || (int) $mov['poupanca_id'] !== (int) $id) {
            $this->setFlash('error', 'Movimento não encontrado.');
            $this->redirect('/poupanca');
            return;
        }

        $this->movModel->delete((int) $movId);

        $this->setFlash('success', 'Movimento excluído.');
        $this->redirect('/poupanca');
    }

    // ----------------------------------------------------------------
    // JSON (for edit modal)
    // ----------------------------------------------------------------

    public function getJson(string $id): void
    {
        $this->requireAuth();

        $savings = $this->findOrFail((int) $id);
        if (!$savings) return;

        $this->json($savings);
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /**
     * @return array<string,mixed>|null
     */
    private function findOrFail(int $id): ?array
    {
        $savings = $this->model->find($id);
        if ($savings === null || (int) $savings['usuario_id'] !== $this->getUserId()) {
            $this->setFlash('error', 'Poupança não encontrada.');
            $this->redirect('/poupanca');
            return null;
        }
        return $savings;
    }

    /**
     * Find the "Poupança / Reserva" category id for the given type.
     * Falls back to the first available category of that type.
     */
    private function findSavingsCategoryId(string $tipo): ?int
    {
        $db = Database::getInstance();

        // Try exact name match
        $row = $db->fetch(
            "SELECT id FROM categorias WHERE nome LIKE '%Poupança%' AND tipo = ? AND ativo = 1 LIMIT 1",
            [$tipo]
        );
        if ($row) {
            return (int) $row['id'];
        }

        // Fallback: first active category of that type
        $row = $db->fetch(
            "SELECT id FROM categorias WHERE tipo = ? AND ativo = 1 LIMIT 1",
            [$tipo]
        );
        return $row ? (int) $row['id'] : null;
    }
}

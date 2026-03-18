<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use App\Models\CreditCard;
use App\Models\CardMovement;
use App\Models\Category;

/**
 * CardController
 */
class CardController extends Controller
{
    private CreditCard $cardModel;
    private CardMovement $movModel;
    private Category $catModel;

    public function __construct()
    {
        parent::__construct();
        $this->cardModel = new CreditCard();
        $this->movModel  = new CardMovement();
        $this->catModel  = new Category();
    }

    // ----------------------------------------------------------------
    // Cards CRUD
    // ----------------------------------------------------------------

    public function index(): void
    {
        $this->requireAuth();

        $userId = $this->getUserId();
        $cards  = $this->cardModel->findByUser($userId);

        // Append current month usage to each card
        $mes = (int) date('m');
        $ano = (int) date('Y');

        foreach ($cards as &$card) {
            $card['usado']      = $this->cardModel->getMonthTotal((int) $card['id'], $mes, $ano);
            $card['disponivel'] = max(0.0, (float) $card['limite'] - $card['usado']);
        }
        unset($card);

        $categories = $this->catModel->getAllWithSubcategories();

        $this->view('cards/index', [
            'cards'      => $cards,
            'categories' => $categories,
            'csrf'       => $this->csrfToken(),
            'flash'      => $this->getFlash(),
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->view('cards/index', ['flash' => $this->getFlash()]);
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $userId = $this->getUserId();

        $data = [
            'usuario_id'     => $userId,
            'nome_cartao'    => trim($_POST['nome_cartao']   ?? ''),
            'bandeira'       => trim($_POST['bandeira']      ?? 'visa'),
            'limite'         => (float) str_replace(['.', ','], ['', '.'], $_POST['limite'] ?? '0'),
            'dia_fechamento' => (int) ($_POST['dia_fechamento'] ?? 1),
            'dia_vencimento' => (int) ($_POST['dia_vencimento'] ?? 10),
            'cor'            => trim($_POST['cor'] ?? '#6366f1'),
        ];

        $v = new Validator($data);
        $v->required('nome_cartao', 'Nome do cartão');

        if ($v->fails()) {
            $this->setFlash('error', $v->firstError());
            $this->redirect('/cartoes');
            return;
        }

        $this->cardModel->create($data);
        $this->setFlash('success', 'Cartão adicionado com sucesso!');
        $this->redirect('/cartoes');
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $card = $this->findCardOrFail((int) $id);
        if (!$card) return;

        $this->view('cards/index', [
            'editCard' => $card,
            'flash'    => $this->getFlash(),
            'csrf'     => $this->csrfToken(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $card = $this->findCardOrFail((int) $id);
        if (!$card) return;

        $data = [
            'nome_cartao'    => trim($_POST['nome_cartao']   ?? ''),
            'bandeira'       => trim($_POST['bandeira']      ?? 'visa'),
            'limite'         => (float) str_replace(['.', ','], ['', '.'], $_POST['limite'] ?? '0'),
            'dia_fechamento' => (int) ($_POST['dia_fechamento'] ?? 1),
            'dia_vencimento' => (int) ($_POST['dia_vencimento'] ?? 10),
            'cor'            => trim($_POST['cor'] ?? '#6366f1'),
        ];

        $this->cardModel->update((int) $id, $data);
        $this->setFlash('success', 'Cartão atualizado!');
        $this->redirect('/cartoes');
    }

    public function destroy(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $card = $this->findCardOrFail((int) $id);
        if (!$card) return;

        $this->cardModel->update((int) $id, ['ativo' => 0]);
        $this->setFlash('success', 'Cartão removido.');
        $this->redirect('/cartoes');
    }

    // ----------------------------------------------------------------
    // Card movements
    // ----------------------------------------------------------------

    public function movements(string $id): void
    {
        $this->requireAuth();

        $card = $this->findCardOrFail((int) $id);
        if (!$card) return;

        $mes = (int) ($_GET['mes'] ?? date('m'));
        $ano = (int) ($_GET['ano'] ?? date('Y'));

        $movements  = $this->cardModel->getMovements((int) $id, $mes, $ano);
        $total      = $this->cardModel->getMonthTotal((int) $id, $mes, $ano);
        $categories = $this->catModel->getAllWithSubcategories();

        $this->view('cards/movements', [
            'card'       => $card,
            'movements'  => $movements,
            'total'      => $total,
            'mes'        => $mes,
            'ano'        => $ano,
            'categories' => $categories,
            'csrf'       => $this->csrfToken(),
            'flash'      => $this->getFlash(),
        ]);
    }

    public function addMovement(): void
    {
        $this->requireAuth();
        $this->view('cards/movements', ['flash' => $this->getFlash()]);
    }

    public function storeMovement(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $card = $this->findCardOrFail((int) $id);
        if (!$card) return;

        $userId      = $this->getUserId();
        $totalParc   = max(1, (int) ($_POST['total_parcelas'] ?? 1));
        $valor       = (float) str_replace(['.', ','], ['', '.'], $_POST['valor'] ?? '0');
        $valorParc   = round($valor / $totalParc, 2);
        $dataCompra  = $_POST['data_compra'] ?? date('Y-m-d');

        [$year, $month, $day] = explode('-', $dataCompra);

        for ($i = 1; $i <= $totalParc; $i++) {
            $m = ((int) $month + ($i - 1) - 1) % 12 + 1;
            $y = (int) $year + (int) floor(((int) $month + ($i - 1) - 1) / 12);
            $d = min((int) $day, (int) date('t', mktime(0, 0, 0, $m, 1, $y)));
            $dataC = sprintf('%04d-%02d-%02d', $y, $m, $d);

            $this->movModel->create([
                'cartao_id'       => (int) $id,
                'usuario_id'      => $userId,
                'descricao'       => trim($_POST['descricao'] ?? ''),
                'valor'           => $valorParc,
                'data_compra'     => $dataC,
                'parcela_atual'   => $i,
                'total_parcelas'  => $totalParc,
                'categoria_id'    => !empty($_POST['categoria_id'])    ? (int) $_POST['categoria_id']    : null,
                'subcategoria_id' => !empty($_POST['subcategoria_id']) ? (int) $_POST['subcategoria_id'] : null,
            ]);
        }

        $mes = (int) date('m', strtotime($dataCompra));
        $ano = (int) date('Y', strtotime($dataCompra));

        $this->setFlash('success', 'Compra adicionada ao cartão!');
        $this->redirect('/cartoes/' . $id . '/movimentos?mes=' . $mes . '&ano=' . $ano);
    }

    public function closeMonth(string $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $card = $this->findCardOrFail((int) $id);
        if (!$card) return;

        $userId = $this->getUserId();
        $mes    = (int) ($_POST['mes'] ?? date('m'));
        $ano    = (int) ($_POST['ano'] ?? date('Y'));
        $total  = $this->cardModel->closeMonth((int) $id, $mes, $ano);

        // Next month's fatura preview will be recalculated on next visit; remove stale one now
        $nextMes = $mes === 12 ? 1  : $mes + 1;
        $nextAno = $mes === 12 ? $ano + 1 : $ano;
        $dataNextVenc = sprintf('%04d-%02d', $nextAno, $nextMes);
        Database::getInstance()->execute(
            "DELETE FROM movimentacoes
             WHERE usuario_id = ? AND descricao = ? AND observacao LIKE '%[PREVIEW_CARTAO]%'
               AND DATE_FORMAT(data_competencia, '%Y-%m') = ?",
            [$userId, 'Fatura ' . $card['nome_cartao'], $dataNextVenc]
        );

        $this->setFlash('success', 'Fatura fechada! Total: R$ ' . number_format($total, 2, ',', '.'));
        $this->redirect('/cartoes/' . $id . '/movimentos');
    }

    public function getMovementJson(string $cardId, string $movId): void
    {
        $this->requireAuth();
        $card = $this->findCardOrFail((int) $cardId);
        if (!$card) return;

        $mov = $this->movModel->find((int) $movId);
        if ($mov === null || (int) $mov['cartao_id'] !== (int) $cardId) {
            $this->json(['error' => 'Não encontrado'], 404);
            return;
        }
        $this->json($mov);
    }

    public function updateMovement(string $cardId, string $movId): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $card = $this->findCardOrFail((int) $cardId);
        if (!$card) return;

        $mov = $this->movModel->find((int) $movId);
        if ($mov === null || (int) $mov['cartao_id'] !== (int) $cardId) {
            $this->setFlash('error', 'Compra não encontrada.');
            $this->redirect('/cartoes/' . $cardId . '/movimentos');
            return;
        }

        $data = [
            'descricao'       => trim($_POST['descricao']  ?? ''),
            'valor'           => (float) str_replace(['.', ','], ['', '.'], $_POST['valor'] ?? '0'),
            'data_compra'     => $_POST['data_compra']    ?? $mov['data_compra'],
            'parcela_atual'   => max(1, (int) ($_POST['parcela_atual']  ?? 1)),
            'total_parcelas'  => max(1, (int) ($_POST['total_parcelas'] ?? 1)),
            'categoria_id'    => !empty($_POST['categoria_id'])    ? (int) $_POST['categoria_id']    : null,
            'subcategoria_id' => !empty($_POST['subcategoria_id']) ? (int) $_POST['subcategoria_id'] : null,
        ];

        $this->movModel->update((int) $movId, $data);
        $this->setFlash('success', 'Compra atualizada!');

        $mes = (int) date('m', strtotime((string) $data['data_compra']));
        $ano = (int) date('Y', strtotime((string) $data['data_compra']));
        $this->redirect('/cartoes/' . $cardId . '/movimentos?mes=' . $mes . '&ano=' . $ano);
    }

    public function destroyMovement(string $cardId, string $movId): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $card = $this->findCardOrFail((int) $cardId);
        if (!$card) return;

        $mov = $this->movModel->find((int) $movId);
        if ($mov === null || (int) $mov['cartao_id'] !== (int) $cardId) {
            $this->setFlash('error', 'Compra não encontrada.');
            $this->redirect('/cartoes/' . $cardId . '/movimentos');
            return;
        }

        $this->movModel->delete((int) $movId);
        $this->setFlash('success', 'Compra excluída.');
        $this->redirect('/cartoes/' . $cardId . '/movimentos');
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /**
     * @return array<string,mixed>|null
     */
    private function findCardOrFail(int $id): ?array
    {
        $card = $this->cardModel->find($id);
        if ($card === null || (int) $card['usuario_id'] !== $this->getUserId()) {
            $this->setFlash('error', 'Cartão não encontrado.');
            $this->redirect('/cartoes');
            return null;
        }
        return $card;
    }
}

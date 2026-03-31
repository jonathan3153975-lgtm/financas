<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\InstallmentDebt;

/**
 * InstallmentDebtController
 */
class InstallmentDebtController extends Controller
{
    private InstallmentDebt $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new InstallmentDebt();
    }

    public function index(): void
    {
        $this->requireAuth();

        $userId = (int) $this->getUserId();
        $mes = (int) ($_GET['mes'] ?? date('m'));
        $ano = (int) ($_GET['ano'] ?? date('Y'));

        $debts = $this->model->findByUser($userId, false);
        $openDebts = $this->model->findOpenForPeriod($userId, $mes, $ano);

        $totalOutstanding = $this->model->getTotalOutstanding($userId);
        $openCount = $this->model->getOpenCount($userId);

        $paidCurrent = $this->model->getMonthlyPaid($userId, $mes, $ano);

        $prevMes = $mes - 1;
        $prevAno = $ano;
        if ($prevMes < 1) {
            $prevMes = 12;
            $prevAno--;
        }
        $paidPrevious = $this->model->getMonthlyPaid($userId, $prevMes, $prevAno);

        $series = $this->model->getReductionSeries($userId, 8);

        $labels = $series['labels'] ?? [];
        $totals = $series['totals'] ?? [];
        $periodMonths = max(1, count($labels));

        $startOutstanding = (float) ($totals[0] ?? 0);
        $currentOutstanding = (float) ($totals[count($totals) - 1] ?? $totalOutstanding);
        $reductionAmount = max(0, $startOutstanding - $currentOutstanding);
        $reductionPercent = $startOutstanding > 0
            ? round(($reductionAmount / $startOutstanding) * 100, 1)
            : 0.0;

        $motivation = [
            'class' => 'debt-motivation-neutral',
            'title' => 'Cada parcela paga é um passo importante.',
            'message' => 'Registre e valide suas parcelas para visualizar sua evolução.',
            'periodMonths' => $periodMonths,
            'reductionPercent' => $reductionPercent,
            'reductionAmount' => round($reductionAmount, 2),
        ];

        if ($openCount === 0 && $totalOutstanding <= 0) {
            $motivation['class'] = 'debt-motivation-success';
            $motivation['title'] = 'Parabéns! Suas dívidas ativas estão quitadas.';
            $motivation['message'] = 'Excelente resultado. Continue registrando para manter o controle.';
        } elseif ($reductionAmount > 0) {
            $motivation['class'] = 'debt-motivation-success';
            $motivation['title'] = 'Você já reduziu ' . number_format($reductionPercent, 1, ',', '.') . '% da sua dívida.';
            $motivation['message'] = 'Nos últimos ' . $periodMonths . ' meses, a redução foi de R$ ' . number_format($reductionAmount, 2, ',', '.') . '.';
        } elseif ($currentOutstanding > $startOutstanding && $startOutstanding > 0) {
            $motivation['class'] = 'debt-motivation-alert';
            $motivation['title'] = 'Sua dívida cresceu no período recente.';
            $motivation['message'] = 'Priorize validar parcelas para retomar a curva de redução nos próximos meses.';
        }

        $this->view('debts/index', [
            'debts' => $debts,
            'openDebts' => $openDebts,
            'mes' => $mes,
            'ano' => $ano,
            'totalOutstanding' => $totalOutstanding,
            'openCount' => $openCount,
            'paidCurrent' => $paidCurrent,
            'paidPrevious' => $paidPrevious,
            'series' => $series,
            'motivation' => $motivation,
            'csrf' => $this->csrfToken(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $userId = (int) $this->getUserId();

        $descricao = trim($_POST['descricao'] ?? '');
        $valorParcela = $this->parseMoney($_POST['valor_parcela'] ?? '0');
        $totalParcelas = (int) ($_POST['total_parcelas'] ?? 0);
        $parcelasPagas = (int) ($_POST['parcelas_pagas'] ?? 0);

        if ($descricao === '' || $valorParcela <= 0 || $totalParcelas <= 0) {
            $this->setFlash('error', 'Preencha descrição, valor da parcela e total de parcelas.');
            $this->redirect('/dividas-parceladas');
            return;
        }

        if ($parcelasPagas < 0 || $parcelasPagas > $totalParcelas) {
            $this->setFlash('error', 'A quantidade de parcelas pagas é inválida.');
            $this->redirect('/dividas-parceladas');
            return;
        }

        $this->model->createDebt($userId, [
            'descricao' => $descricao,
            'valor_parcela' => $valorParcela,
            'total_parcelas' => $totalParcelas,
            'parcelas_pagas' => $parcelasPagas,
        ]);

        $this->setFlash('success', 'Dívida parcelada cadastrada com sucesso!');
        $this->redirect('/dividas-parceladas');
    }

    public function registerPayment(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $userId = (int) $this->getUserId();
        $debtId = (int) ($_POST['divida_id'] ?? 0);
        $mes = (int) ($_POST['mes'] ?? date('m'));
        $ano = (int) ($_POST['ano'] ?? date('Y'));
        $dataCompetencia = sprintf('%04d-%02d-01', $ano, $mes);

        if ($debtId <= 0 || $mes < 1 || $mes > 12 || $ano < 2000) {
            $this->setFlash('error', 'Dados inválidos para registrar pagamento.');
            $this->redirect('/dividas-parceladas');
            return;
        }

        try {
            $this->model->registerPaymentInMovements($userId, $debtId, $dataCompetencia);
            $this->setFlash('success', 'Parcela lançada em movimentações com sucesso!');
        } catch (\Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('/dividas-parceladas?mes=' . $mes . '&ano=' . $ano);
    }

    private function parseMoney(string $value): float
    {
        $normalized = str_replace(['R$', ' '], '', trim($value));

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        return (float) $normalized;
    }
}

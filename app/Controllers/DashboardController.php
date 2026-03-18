<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Movement;
use App\Models\FixedMovement;
use App\Models\CreditCard;

/**
 * DashboardController
 */
class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        $userId = $this->getUserId();

        // Period from GET params (default: current month)
        $mes = (int) ($_GET['mes'] ?? date('m'));
        $ano = (int) ($_GET['ano'] ?? date('Y'));

        // Clamp values
        $mes = max(1, min(12, $mes));
        $ano = max(2000, min((int) date('Y') + 1, $ano));

        // Auto-generate fixed movements for the selected month
        $fixedModel = new FixedMovement();
        $fixedModel->generateForMonth($userId, $mes, $ano);

        $movModel = new Movement();

        // Current period totals
        $totalEntrada = $movModel->getTotalByType($userId, 'entrada', $mes, $ano);
        $totalSaida   = $movModel->getTotalByType($userId, 'saida',   $mes, $ano);
        $saldoMes     = $movModel->getMonthBalance($userId, $mes, $ano);
        $saldoTotal   = $movModel->getTotalBalance($userId);

        // Previous month for comparison
        $mesPrev = $mes === 1 ? 12 : $mes - 1;
        $anoPrev = $mes === 1 ? $ano - 1 : $ano;
        $prevEntrada = $movModel->getTotalByType($userId, 'entrada', $mesPrev, $anoPrev);
        $prevSaida   = $movModel->getTotalByType($userId, 'saida',   $mesPrev, $anoPrev);

        $deltaEntrada = $prevEntrada > 0
            ? round((($totalEntrada - $prevEntrada) / $prevEntrada) * 100, 1)
            : 0;
        $deltaSaida = $prevSaida > 0
            ? round((($totalSaida - $prevSaida) / $prevSaida) * 100, 1)
            : 0;

        // Pending count
        $pendingCount = $movModel->getPendingCount($userId);

        // Monthly comparison (last 6 months)
        $comparison = $movModel->getMonthlyComparison($userId, 6);

        // Expense by category (for donut chart)
        $expByCategory = $movModel->getExpenseByCategory($userId, $mes, $ano);

        // Cash flow
        $cashFlow = $movModel->getCashFlow($userId, $mes, $ano);

        // Recent movements
        $recent = $movModel->getRecent($userId, 10);

        // Credit cards
        $cardModel = new CreditCard();
        $cards = $cardModel->findByUser($userId);

        $this->view('dashboard/index', [
            'mes'           => $mes,
            'ano'           => $ano,
            'totalEntrada'  => $totalEntrada,
            'totalSaida'    => $totalSaida,
            'saldoMes'      => $saldoMes,
            'saldoTotal'    => $saldoTotal,
            'deltaEntrada'  => $deltaEntrada,
            'deltaSaida'    => $deltaSaida,
            'pendingCount'  => $pendingCount,
            'comparison'    => $comparison,
            'expByCategory' => $expByCategory,
            'cashFlow'      => $cashFlow,
            'recent'        => $recent,
            'cards'         => $cards,
            'flash'         => $this->getFlash(),
        ]);
    }
}

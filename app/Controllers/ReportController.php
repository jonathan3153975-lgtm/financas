<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Movement;
use App\Models\Category;

/**
 * ReportController
 */
class ReportController extends Controller
{
    private Movement $movModel;
    private Category $catModel;

    public function __construct()
    {
        parent::__construct();
        $this->movModel = new Movement();
        $this->catModel = new Category();
    }

    public function index(): void
    {
        $this->requireAuth();

        $userId = $this->getUserId();
        $mes    = (int) ($_GET['mes'] ?? date('m'));
        $ano    = (int) ($_GET['ano'] ?? date('Y'));

        $totalEntrada   = $this->movModel->getTotalByType($userId, 'entrada', $mes, $ano);
        $totalSaida     = $this->movModel->getTotalByType($userId, 'saida',   $mes, $ano);
        $expByCategory  = $this->movModel->getExpenseByCategory($userId, $mes, $ano);
        $comparison     = $this->movModel->getMonthlyComparison($userId, 12);
        $cashFlow       = $this->movModel->getCashFlow($userId, $mes, $ano);

        $this->view('reports/index', [
            'mes'           => $mes,
            'ano'           => $ano,
            'totalEntrada'  => $totalEntrada,
            'totalSaida'    => $totalSaida,
            'expByCategory' => $expByCategory,
            'comparison'    => $comparison,
            'cashFlow'      => $cashFlow,
            'flash'         => $this->getFlash(),
        ]);
    }

    public function byCategory(string $tipo): void
    {
        $this->requireAuth();

        $userId = $this->getUserId();
        $mes    = (int) ($_GET['mes'] ?? date('m'));
        $ano    = (int) ($_GET['ano'] ?? date('Y'));

        $data = $this->movModel->getExpenseByCategory($userId, $mes, $ano);
        $this->json($data);
    }

    public function monthly(): void
    {
        $this->requireAuth();
        $data = $this->movModel->getMonthlyComparison($this->getUserId(), 12);
        $this->json($data);
    }

    public function cashflow(): void
    {
        $this->requireAuth();
        $mes  = (int) ($_GET['mes'] ?? date('m'));
        $ano  = (int) ($_GET['ano'] ?? date('Y'));
        $data = $this->movModel->getCashFlow($this->getUserId(), $mes, $ano);
        $this->json($data);
    }

    public function exportCsv(string $type): void
    {
        $this->requireAuth();

        $userId = $this->getUserId();
        $mes    = (int) ($_GET['mes'] ?? date('m'));
        $ano    = (int) ($_GET['ano'] ?? date('Y'));

        $rows = match($type) {
            'movimentos'   => $this->movModel->findByUser($userId, ['mes' => $mes, 'ano' => $ano, 'limit' => 9999])['data'],
            'categorias'   => $this->movModel->getExpenseByCategory($userId, $mes, $ano),
            'comparativo'  => $this->movModel->getMonthlyComparison($userId, 12),
            default        => [],
        };

        $filename = "financas_{$type}_{$ano}{$mes}.csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $out = fopen('php://output', 'w');
        if ($out === false) return;

        // BOM for Excel UTF-8
        fputs($out, "\xEF\xBB\xBF");

        if (!empty($rows)) {
            fputcsv($out, array_keys($rows[0]), ';');
            foreach ($rows as $row) {
                fputcsv($out, array_values($row), ';');
            }
        }

        fclose($out);
        exit;
    }
}

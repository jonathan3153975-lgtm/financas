<?php
$pageTitle = 'Relatórios – JW Finanças';
$basePath  = $_ENV['APP_BASE_PATH'] ?? '/financas/public';
$meses     = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
               'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Relatórios</h1>
        <p class="page-subtitle">Análise detalhada das suas finanças</p>
    </div>
    <form method="GET" action="<?= $basePath ?>/relatorios" class="period-form">
        <select name="mes" class="form-control form-control-sm" onchange="this.form.submit()">
            <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= $m === $mes ? 'selected' : '' ?>><?= $meses[$m] ?></option>
            <?php endfor; ?>
        </select>
        <select name="ano" class="form-control form-control-sm" onchange="this.form.submit()">
            <?php for ($y = (int)date('Y') - 3; $y <= (int)date('Y'); $y++): ?>
            <option value="<?= $y ?>" <?= $y === $ano ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>
</div>

<!-- Export Buttons -->
<div class="export-bar">
    <span class="text-muted text-sm">Exportar:</span>
    <a href="<?= $basePath ?>/relatorios/exportar/movimentos?mes=<?= $mes ?>&ano=<?= $ano ?>"
       class="btn btn-ghost btn-sm">
        <i class="fa-solid fa-file-csv"></i> Movimentações CSV
    </a>
    <a href="<?= $basePath ?>/relatorios/exportar/categorias?mes=<?= $mes ?>&ano=<?= $ano ?>"
       class="btn btn-ghost btn-sm">
        <i class="fa-solid fa-file-csv"></i> Categorias CSV
    </a>
    <a href="<?= $basePath ?>/relatorios/exportar/comparativo?mes=<?= $mes ?>&ano=<?= $ano ?>"
       class="btn btn-ghost btn-sm">
        <i class="fa-solid fa-file-csv"></i> Comparativo CSV
    </a>
</div>

<!-- Summary for period -->
<div class="cards-grid cards-grid-3">
    <div class="summary-card card-income">
        <div class="summary-card-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
        <div class="summary-card-value">R$ <?= number_format($totalEntrada, 2, ',', '.') ?></div>
        <div class="summary-card-label">Receitas — <?= $meses[$mes] ?>/<?= $ano ?></div>
    </div>
    <div class="summary-card card-expense">
        <div class="summary-card-icon"><i class="fa-solid fa-arrow-trend-down"></i></div>
        <div class="summary-card-value">R$ <?= number_format($totalSaida, 2, ',', '.') ?></div>
        <div class="summary-card-label">Despesas — <?= $meses[$mes] ?>/<?= $ano ?></div>
    </div>
    <div class="summary-card <?= ($totalEntrada - $totalSaida) >= 0 ? 'card-balance-pos' : 'card-balance-neg' ?>">
        <div class="summary-card-icon"><i class="fa-solid fa-scale-balanced"></i></div>
        <div class="summary-card-value">R$ <?= number_format($totalEntrada - $totalSaida, 2, ',', '.') ?></div>
        <div class="summary-card-label">Saldo — <?= $meses[$mes] ?>/<?= $ano ?></div>
    </div>
</div>

<!-- Charts Grid -->
<div class="charts-row">
    <div class="card chart-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-chart-bar"></i> Comparativo 12 Meses</h3>
        </div>
        <div class="card-body">
            <canvas id="chartReport12" height="250"></canvas>
        </div>
    </div>

    <div class="card chart-card chart-card-sm">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-chart-pie"></i> Despesas por Categoria</h3>
            <span class="card-subtitle"><?= $meses[$mes] ?>/<?= $ano ?></span>
        </div>
        <div class="card-body">
            <?php if (empty($expByCategory)): ?>
            <div class="empty-state"><i class="fa-solid fa-chart-pie"></i><p>Sem dados</p></div>
            <?php else: ?>
            <canvas id="chartReportCat" height="250"></canvas>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Cash Flow Full Width -->
<div class="card chart-card-full">
    <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-chart-line"></i> Fluxo de Caixa Diário</h3>
        <span class="card-subtitle"><?= $meses[$mes] ?>/<?= $ano ?></span>
    </div>
    <div class="card-body">
        <?php if (empty($cashFlow)): ?>
        <div class="empty-state"><i class="fa-solid fa-chart-line"></i><p>Sem dados de fluxo</p></div>
        <?php else: ?>
        <canvas id="chartReportCf" height="120"></canvas>
        <?php endif; ?>
    </div>
</div>

<!-- Category breakdown table -->
<?php if (!empty($expByCategory)): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-list-ul"></i> Despesas por Categoria</h3>
    </div>
    <div class="card-body p-0">
        <table class="table">
            <thead>
                <tr>
                    <th>Categoria</th>
                    <th class="text-right">Total</th>
                    <th>% do Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $grandTotal = array_sum(array_column($expByCategory, 'total'));
                foreach ($expByCategory as $cat):
                    $pct = $grandTotal > 0 ? round(($cat['total'] / $grandTotal) * 100, 1) : 0;
                ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-2">
                            <span class="cat-dot" style="background: <?= htmlspecialchars($cat['cor'] ?? '#6366f1') ?>"></span>
                            <?= htmlspecialchars($cat['nome']) ?>
                        </div>
                    </td>
                    <td class="text-right font-semibold text-danger">
                        R$ <?= number_format((float)$cat['total'], 2, ',', '.') ?>
                    </td>
                    <td>
                        <div class="progress-bar-sm">
                            <div class="progress-fill" style="width: <?= $pct ?>%; background: <?= htmlspecialchars($cat['cor'] ?? '#6366f1') ?>"></div>
                        </div>
                        <span class="text-xs text-muted"><?= $pct ?>%</span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const monthNames = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];

    // 12-month comparison
    const comp12 = <?= json_encode($comparison) ?>;
    if (comp12.length && document.getElementById('chartReport12')) {
        new Chart(document.getElementById('chartReport12'), {
            type: 'bar',
            data: {
                labels: comp12.map(d => monthNames[d.mes - 1] + '/' + String(d.ano).slice(-2)),
                datasets: [
                    {
                        label: 'Receitas',
                        data: comp12.map(d => parseFloat(d.total_entrada)),
                        backgroundColor: 'rgba(16,185,129,0.8)',
                        borderRadius: 4,
                    },
                    {
                        label: 'Despesas',
                        data: comp12.map(d => parseFloat(d.total_saida)),
                        backgroundColor: 'rgba(239,68,68,0.8)',
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, ticks: { color: '#64748b', callback: v => 'R$ ' + v.toLocaleString('pt-BR') } },
                    x: { grid: { display: false }, ticks: { color: '#64748b' } }
                },
                plugins: { legend: { labels: { color: '#64748b' } } }
            }
        });
    }

    // Category doughnut
    const catData = <?= json_encode($expByCategory) ?>;
    if (catData.length && document.getElementById('chartReportCat')) {
        new Chart(document.getElementById('chartReportCat'), {
            type: 'doughnut',
            data: {
                labels: catData.map(d => d.nome),
                datasets: [{
                    data: catData.map(d => parseFloat(d.total)),
                    backgroundColor: catData.map(d => d.cor || '#6366f1'),
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                cutout: '60%',
                plugins: {
                    legend: { position: 'right', labels: { color: '#64748b', font: { size: 11 } } },
                    tooltip: { callbacks: { label: ctx => ' R$ ' + ctx.parsed.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) } }
                }
            }
        });
    }

    // Cashflow line
    const cfData = <?= json_encode($cashFlow) ?>;
    if (cfData.length && document.getElementById('chartReportCf')) {
        new Chart(document.getElementById('chartReportCf'), {
            type: 'line',
            data: {
                labels: cfData.map(d => 'Dia ' + d.dia),
                datasets: [
                    {
                        label: 'Entradas',
                        data: cfData.map(d => parseFloat(d.entradas)),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,0.1)',
                        tension: 0.4,
                        fill: true,
                    },
                    {
                        label: 'Saídas',
                        data: cfData.map(d => parseFloat(d.saidas)),
                        borderColor: '#ef4444',
                        tension: 0.4,
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, ticks: { color: '#64748b', callback: v => 'R$ ' + v.toLocaleString('pt-BR') } },
                    x: { grid: { display: false }, ticks: { color: '#64748b' } }
                }
            }
        });
    }
});
</script>

<?php
$pageTitle = 'Dashboard – JW Finanças';
$basePath  = $_ENV['APP_BASE_PATH'] ?? '/financas/public';
$meses     = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
               'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

function fmtBrl(float $v): string {
    return 'R$ ' . number_format(abs($v), 2, ',', '.');
}
?>

<!-- Period Selector -->
<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Visão geral das suas finanças</p>
    </div>
    <form method="GET" action="<?= $basePath ?>/dashboard" class="period-form">
        <select name="mes" class="form-control form-control-sm" onchange="this.form.submit()">
            <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= $m === $mes ? 'selected' : '' ?>><?= $meses[$m] ?></option>
            <?php endfor; ?>
        </select>
        <select name="ano" class="form-control form-control-sm" onchange="this.form.submit()">
            <?php for ($y = (int)date('Y') - 2; $y <= (int)date('Y') + 1; $y++): ?>
            <option value="<?= $y ?>" <?= $y === $ano ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>
</div>

<!-- ================================================================
     Summary Cards
================================================================ -->
<div class="cards-grid cards-grid-4">

    <!-- Receitas -->
    <div class="summary-card card-income">
        <div class="summary-card-header">
            <div class="summary-card-icon">
                <i class="fa-solid fa-arrow-trend-up"></i>
            </div>
            <div class="summary-card-badge">
                <?php if ($deltaEntrada >= 0): ?>
                <span class="badge badge-success"><i class="fa-solid fa-caret-up"></i> <?= abs($deltaEntrada) ?>%</span>
                <?php else: ?>
                <span class="badge badge-danger"><i class="fa-solid fa-caret-down"></i> <?= abs($deltaEntrada) ?>%</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="summary-card-value"><?= fmtBrl($totalEntrada) ?></div>
        <div class="summary-card-label">Total de Receitas</div>
        <div class="summary-card-footer">
            <?= $meses[$mes] ?> / <?= $ano ?>
        </div>
    </div>

    <!-- Despesas -->
    <div class="summary-card card-expense">
        <div class="summary-card-header">
            <div class="summary-card-icon">
                <i class="fa-solid fa-arrow-trend-down"></i>
            </div>
            <div class="summary-card-badge">
                <?php if ($deltaSaida <= 0): ?>
                <span class="badge badge-success"><i class="fa-solid fa-caret-down"></i> <?= abs($deltaSaida) ?>%</span>
                <?php else: ?>
                <span class="badge badge-danger"><i class="fa-solid fa-caret-up"></i> <?= abs($deltaSaida) ?>%</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="summary-card-value"><?= fmtBrl($totalSaida) ?></div>
        <div class="summary-card-label">Total de Despesas</div>
        <div class="summary-card-footer">
            <?php if ($pendingCount > 0): ?>
            <span class="badge badge-warning"><?= $pendingCount ?> pendente<?= $pendingCount > 1 ? 's' : '' ?></span>
            <?php else: ?>
            <span class="text-muted">Tudo validado</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Saldo do Mês -->
    <div class="summary-card <?= $saldoMes >= 0 ? 'card-balance-pos' : 'card-balance-neg' ?>">
        <div class="summary-card-header">
            <div class="summary-card-icon">
                <i class="fa-solid fa-scale-balanced"></i>
            </div>
        </div>
        <div class="summary-card-value <?= $saldoMes < 0 ? 'text-danger' : '' ?>">
            <?= $saldoMes < 0 ? '- ' : '' ?><?= fmtBrl($saldoMes) ?>
        </div>
        <div class="summary-card-label">Saldo do Mês</div>
        <div class="summary-card-footer">
            <?= $meses[$mes] ?> / <?= $ano ?>
        </div>
    </div>

    <!-- Saldo Total -->
    <div class="summary-card <?= $saldoTotal >= 0 ? 'card-total-pos' : 'card-total-neg' ?>">
        <div class="summary-card-header">
            <div class="summary-card-icon">
                <i class="fa-solid fa-piggy-bank"></i>
            </div>
        </div>
        <div class="summary-card-value <?= $saldoTotal < 0 ? 'text-danger' : '' ?>">
            <?= $saldoTotal < 0 ? '- ' : '' ?><?= fmtBrl($saldoTotal) ?>
        </div>
        <div class="summary-card-label">Saldo Total Acumulado</div>
        <div class="summary-card-footer">Somente validados</div>
    </div>

</div>

<!-- ================================================================
     Charts Row
================================================================ -->
<div class="charts-row">

    <!-- Bar chart: Monthly comparison -->
    <div class="card chart-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-chart-bar"></i> Comparativo Mensal</h3>
            <span class="card-subtitle">Últimos 6 meses</span>
        </div>
        <div class="card-body">
            <canvas id="chartComparison" height="250"></canvas>
        </div>
    </div>

    <!-- Doughnut: Expenses by category -->
    <div class="card chart-card chart-card-sm">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-chart-pie"></i> Gastos por Categoria</h3>
            <span class="card-subtitle"><?= $meses[$mes] ?>/<?= $ano ?></span>
        </div>
        <div class="card-body">
            <?php if (empty($expByCategory)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-chart-pie"></i>
                <p>Nenhuma despesa neste período</p>
            </div>
            <?php else: ?>
            <canvas id="chartCategories" height="250"></canvas>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Line chart: Cash flow -->
<div class="card chart-card-full">
    <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-chart-line"></i> Fluxo de Caixa</h3>
        <span class="card-subtitle"><?= $meses[$mes] ?>/<?= $ano ?></span>
    </div>
    <div class="card-body">
        <canvas id="chartCashflow" height="120"></canvas>
    </div>
</div>

<!-- ================================================================
     Recent Movements + Quick Actions
================================================================ -->
<div class="dashboard-bottom">

    <!-- Recent movements -->
    <div class="card flex-grow">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-clock-rotate-left"></i> Últimas Movimentações</h3>
            <a href="<?= $basePath ?>/movimentacoes" class="btn btn-ghost btn-sm">Ver todas</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($recent)): ?>
            <div class="empty-state py-8">
                <i class="fa-solid fa-inbox"></i>
                <p>Nenhuma movimentação ainda.</p>
                <a href="<?= $basePath ?>/movimentacoes/criar" class="btn btn-primary btn-sm mt-2">
                    <i class="fa-solid fa-plus"></i> Adicionar
                </a>
            </div>
            <?php else: ?>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Categoria</th>
                        <th>Tipo</th>
                        <th class="text-right">Valor</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $mov): ?>
                    <tr class="<?= !(bool)$mov['validado'] ? 'row-pending' : '' ?>">
                        <td class="text-muted text-sm"><?= date('d/m/Y', strtotime($mov['data_competencia'])) ?></td>
                        <td>
                            <div class="mov-desc">
                                <?php if ($mov['categoria_cor']): ?>
                                <span class="cat-dot" style="background:<?= htmlspecialchars($mov['categoria_cor'] ?? '#6366f1') ?>"></span>
                                <?php endif; ?>
                                <?= htmlspecialchars($mov['descricao']) ?>
                            </div>
                        </td>
                        <td class="text-muted text-sm"><?= htmlspecialchars($mov['categoria_nome'] ?? '—') ?></td>
                        <td>
                            <span class="badge <?= $mov['tipo'] === 'entrada' ? 'badge-success' : 'badge-danger' ?>">
                                <?= $mov['tipo'] === 'entrada' ? 'Receita' : 'Despesa' ?>
                            </span>
                        </td>
                        <td class="text-right font-semibold <?= $mov['tipo'] === 'entrada' ? 'text-success' : 'text-danger' ?>">
                            <?= $mov['tipo'] === 'entrada' ? '+' : '-' ?> <?= fmtBrl((float) $mov['valor']) ?>
                        </td>
                        <td>
                            <?php if ($mov['validado']): ?>
                            <span class="badge badge-success"><i class="fa-solid fa-check"></i> Validado</span>
                            <?php else: ?>
                            <span class="badge badge-warning"><i class="fa-solid fa-clock"></i> Pendente</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick actions -->
    <div class="quick-actions-panel">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-bolt"></i> Ações Rápidas</h3>
            </div>
            <div class="card-body">
                <div class="quick-actions">
                    <a href="<?= $basePath ?>/movimentacoes/criar" class="quick-action-btn quick-action-income">
                        <i class="fa-solid fa-plus-circle"></i>
                        <span>Nova Receita</span>
                    </a>
                    <a href="<?= $basePath ?>/movimentacoes/criar" class="quick-action-btn quick-action-expense">
                        <i class="fa-solid fa-minus-circle"></i>
                        <span>Nova Despesa</span>
                    </a>
                    <a href="<?= $basePath ?>/cartoes" class="quick-action-btn quick-action-card">
                        <i class="fa-solid fa-credit-card"></i>
                        <span>Cartão</span>
                    </a>
                    <a href="<?= $basePath ?>/folha-pagamento" class="quick-action-btn quick-action-payroll">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        <span>Folha</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Credit cards mini list -->
        <?php if (!empty($cards)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-credit-card"></i> Meus Cartões</h3>
                <a href="<?= $basePath ?>/cartoes" class="btn btn-ghost btn-sm">Ver todos</a>
            </div>
            <div class="card-body">
                <?php foreach (array_slice($cards, 0, 3) as $card): ?>
                <div class="mini-card" style="border-left: 4px solid <?= htmlspecialchars($card['cor']) ?>">
                    <div class="mini-card-info">
                        <span class="mini-card-name"><?= htmlspecialchars($card['nome_cartao']) ?></span>
                        <span class="mini-card-limit">Limite: <?= fmtBrl((float)$card['limite']) ?></span>
                    </div>
                    <a href="<?= $basePath ?>/cartoes/<?= $card['id'] ?>/movimentos" class="btn btn-ghost btn-xs">
                        <i class="fa-solid fa-eye"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- Chart.js initialization -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    const chartDefaults = {
        responsive: true,
        plugins: {
            legend: { labels: { font: { family: 'Inter', size: 12 }, color: '#64748b' } }
        }
    };

    // ---- Monthly Comparison Bar Chart ----
    const compData = <?= json_encode($comparison) ?>;
    const monthNames = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];

    if (compData.length > 0 && document.getElementById('chartComparison')) {
        new Chart(document.getElementById('chartComparison'), {
            type: 'bar',
            data: {
                labels: compData.map(d => monthNames[d.mes - 1] + '/' + String(d.ano).slice(-2)),
                datasets: [
                    {
                        label: 'Receitas',
                        data: compData.map(d => parseFloat(d.total_entrada)),
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                        borderColor: '#10b981',
                        borderWidth: 1,
                        borderRadius: 6,
                    },
                    {
                        label: 'Despesas',
                        data: compData.map(d => parseFloat(d.total_saida)),
                        backgroundColor: 'rgba(239, 68, 68, 0.8)',
                        borderColor: '#ef4444',
                        borderWidth: 1,
                        borderRadius: 6,
                    }
                ]
            },
            options: {
                ...chartDefaults,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: {
                            color: '#64748b',
                            callback: v => 'R$ ' + v.toLocaleString('pt-BR')
                        }
                    },
                    x: { grid: { display: false }, ticks: { color: '#64748b' } }
                }
            }
        });
    }

    // ---- Expense by Category Doughnut ----
    const catData = <?= json_encode($expByCategory) ?>;
    if (catData.length > 0 && document.getElementById('chartCategories')) {
        new Chart(document.getElementById('chartCategories'), {
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
                ...chartDefaults,
                cutout: '65%',
                plugins: {
                    ...chartDefaults.plugins,
                    tooltip: {
                        callbacks: {
                            label: ctx => ' R$ ' + ctx.parsed.toLocaleString('pt-BR', { minimumFractionDigits: 2 })
                        }
                    }
                }
            }
        });
    }

    // ---- Cash Flow Line Chart ----
    const cfData = <?= json_encode($cashFlow) ?>;
    if (cfData.length > 0 && document.getElementById('chartCashflow')) {
        new Chart(document.getElementById('chartCashflow'), {
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
                        pointRadius: 4,
                        pointBackgroundColor: '#10b981',
                    },
                    {
                        label: 'Saídas',
                        data: cfData.map(d => parseFloat(d.saidas)),
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239,68,68,0.05)',
                        tension: 0.4,
                        fill: false,
                        pointRadius: 4,
                        pointBackgroundColor: '#ef4444',
                    }
                ]
            },
            options: {
                ...chartDefaults,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: { color: '#64748b', callback: v => 'R$ ' + v.toLocaleString('pt-BR') }
                    },
                    x: { grid: { display: false }, ticks: { color: '#64748b' } }
                }
            }
        });
    }
});
</script>

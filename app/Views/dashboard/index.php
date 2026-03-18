<?php
$pageTitle  = 'Dashboard – JW Finanças';
$basePath   = defined('BASE_URL') ? BASE_URL : ($_ENV['APP_BASE_PATH'] ?? '');
$meses      = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
$mesesShort = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun',
                'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
$modeLabels = ['unico' => 'Único', 'fixo' => 'Fixo', 'parcelamento' => 'Parcelado'];

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

    <div class="summary-card card-income">
        <div class="summary-card-header">
            <div class="summary-card-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
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
        <div class="summary-card-footer"><?= $meses[$mes] ?> / <?= $ano ?></div>
    </div>

    <div class="summary-card card-expense">
        <div class="summary-card-header">
            <div class="summary-card-icon"><i class="fa-solid fa-arrow-trend-down"></i></div>
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

    <div class="summary-card <?= $saldoMes >= 0 ? 'card-balance-pos' : 'card-balance-neg' ?>">
        <div class="summary-card-header">
            <div class="summary-card-icon"><i class="fa-solid fa-scale-balanced"></i></div>
        </div>
        <div class="summary-card-value <?= $saldoMes < 0 ? 'text-danger' : '' ?>">
            <?= $saldoMes < 0 ? '- ' : '' ?><?= fmtBrl($saldoMes) ?>
        </div>
        <div class="summary-card-label">Saldo do Mês</div>
        <div class="summary-card-footer"><?= $meses[$mes] ?> / <?= $ano ?></div>
    </div>

    <div class="summary-card <?= $saldoTotal >= 0 ? 'card-total-pos' : 'card-total-neg' ?>">
        <div class="summary-card-header">
            <div class="summary-card-icon"><i class="fa-solid fa-piggy-bank"></i></div>
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

    <!-- Line chart: Monthly comparison -->
    <div class="card chart-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-chart-line"></i> Comparativo Mensal</h3>
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

            <!-- Desktop table -->
            <div class="table-responsive hide-on-mobile">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Modo</th>
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
                                    <?php if (!empty($mov['categoria_cor'])): ?>
                                    <span class="cat-dot" style="background:<?= htmlspecialchars($mov['categoria_cor']) ?>"></span>
                                    <?php endif; ?>
                                    <div>
                                        <div class="font-medium"><?= htmlspecialchars($mov['descricao']) ?></div>
                                        <?php if (!empty($mov['subcategoria_nome'])): ?>
                                        <div class="text-xs text-muted"><?= htmlspecialchars($mov['subcategoria_nome']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="text-sm">
                                <?php if (!empty($mov['categoria_nome'])): ?>
                                <span class="cat-badge" style="background:<?= htmlspecialchars($mov['categoria_cor'] ?? '#6366f1') ?>20; color:<?= htmlspecialchars($mov['categoria_cor'] ?? '#6366f1') ?>">
                                    <?= htmlspecialchars($mov['categoria_nome']) ?>
                                </span>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-sm text-muted">
                                <?= $modeLabels[$mov['modo'] ?? 'unico'] ?? '—' ?>
                            </td>
                            <td class="text-right font-semibold <?= $mov['tipo'] === 'entrada' ? 'text-success' : 'text-danger' ?>">
                                <?= $mov['tipo'] === 'entrada' ? '+' : '−' ?> <?= fmtBrl((float)$mov['valor']) ?>
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
            </div>

            <!-- Mobile cards -->
            <div class="mov-card-list show-on-mobile">
                <?php foreach ($recent as $mov): ?>
                <div class="mov-card <?= !(bool)$mov['validado'] ? 'mov-card-pending' : '' ?>">
                    <div class="mov-card-top">
                        <div class="mov-card-desc">
                            <?php if (!empty($mov['categoria_cor'])): ?>
                            <span class="cat-dot" style="background:<?= htmlspecialchars($mov['categoria_cor']) ?>"></span>
                            <?php endif; ?>
                            <div>
                                <div class="mov-card-title"><?= htmlspecialchars($mov['descricao']) ?></div>
                                <?php if (!empty($mov['subcategoria_nome'])): ?>
                                <div class="mov-card-sub"><?= htmlspecialchars($mov['subcategoria_nome']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mov-card-amount <?= $mov['tipo'] === 'entrada' ? 'text-success' : 'text-danger' ?>">
                            <?= $mov['tipo'] === 'entrada' ? '+' : '−' ?><?= fmtBrl((float)$mov['valor']) ?>
                        </div>
                    </div>
                    <div class="mov-card-meta">
                        <span class="mov-card-date"><i class="fa-regular fa-calendar"></i> <?= date('d/m/Y', strtotime($mov['data_competencia'])) ?></span>
                        <?php if (!empty($mov['categoria_nome'])): ?>
                        <span class="cat-badge" style="background:<?= htmlspecialchars($mov['categoria_cor'] ?? '#6366f1') ?>20; color:<?= htmlspecialchars($mov['categoria_cor'] ?? '#6366f1') ?>">
                            <?= htmlspecialchars($mov['categoria_nome']) ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($mov['validado']): ?>
                        <span class="badge badge-success"><i class="fa-solid fa-check"></i> Validado</span>
                        <?php else: ?>
                        <span class="badge badge-warning"><i class="fa-solid fa-clock"></i> Pendente</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php endif; ?>
        </div>
    </div>

    <!-- Quick actions + cards -->
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

    const tooltipBRL = {
        callbacks: {
            label: ctx => ' R$ ' + ctx.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2 })
        }
    };

    const axisY = {
        beginAtZero: true,
        grid: { color: 'rgba(0,0,0,0.05)' },
        ticks: { color: '#64748b', callback: v => 'R$ ' + v.toLocaleString('pt-BR') }
    };
    const axisX = { grid: { display: false }, ticks: { color: '#64748b' } };

    // ── Monthly Comparison — Line Chart ──────────────────────────
    const compData   = <?= json_encode($comparison) ?>;
    const monthNames = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];

    if (compData.length > 0 && document.getElementById('chartComparison')) {
        const labels   = compData.map(d => monthNames[d.mes - 1] + '/' + String(d.ano).slice(-2));
        const entradas = compData.map(d => parseFloat(d.total_entrada));
        const saidas   = compData.map(d => parseFloat(d.total_saida));
        const poupanca = compData.map((d, i) => Math.max(0, entradas[i] - saidas[i]));

        new Chart(document.getElementById('chartComparison'), {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Receitas',
                        data: entradas,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,0.08)',
                        borderWidth: 2.5,
                        tension: 0.4,
                        fill: false,
                        pointRadius: 4,
                        pointBackgroundColor: '#10b981',
                        pointHoverRadius: 6,
                    },
                    {
                        label: 'Despesas',
                        data: saidas,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239,68,68,0.06)',
                        borderWidth: 2.5,
                        tension: 0.4,
                        fill: false,
                        pointRadius: 4,
                        pointBackgroundColor: '#ef4444',
                        pointHoverRadius: 6,
                    },
                    {
                        label: 'Poupança',
                        data: poupanca,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59,130,246,0.08)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 3,
                        pointBackgroundColor: '#3b82f6',
                        pointHoverRadius: 5,
                        borderDash: [5, 3],
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        labels: { font: { family: 'Inter', size: 12 }, color: '#64748b', usePointStyle: true, pointStyleWidth: 10 }
                    },
                    tooltip: tooltipBRL
                },
                scales: { y: axisY, x: axisX }
            }
        });
    }

    // ── Expense by Category — Doughnut ───────────────────────────
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
                responsive: true,
                cutout: '65%',
                plugins: {
                    legend: { labels: { font: { family: 'Inter', size: 12 }, color: '#64748b' } },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' R$ ' + ctx.parsed.toLocaleString('pt-BR', { minimumFractionDigits: 2 })
                        }
                    }
                }
            }
        });
    }
});
</script>

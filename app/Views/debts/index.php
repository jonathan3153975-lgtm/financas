<?php
$pageTitle = 'Dívidas Parceladas - JW Finanças';
$basePath = defined('BASE_URL') ? BASE_URL : ($_ENV['APP_BASE_PATH'] ?? '');

function fmtBrlDebt(float $v): string {
    return 'R$ ' . number_format($v, 2, ',', '.');
}

$deltaPaid = $paidCurrent - $paidPrevious;
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Dívidas Parceladas</h1>
        <p class="page-subtitle">Monitore parcelas abertas e acompanhe a redução da dívida total</p>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-primary" onclick="openModal('modalNovaDivida')">
            <i class="fa-solid fa-plus"></i> Nova Dívida
        </button>
    </div>
</div>

<div class="cards-grid cards-grid-3">
    <div class="summary-card card-expense">
        <div class="summary-card-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <div class="summary-card-value"><?= (int) $openCount ?></div>
        <div class="summary-card-label">Dívidas Ativas</div>
    </div>
    <div class="summary-card card-balance-neg">
        <div class="summary-card-icon"><i class="fa-solid fa-sack-dollar"></i></div>
        <div class="summary-card-value"><?= fmtBrlDebt((float) $totalOutstanding) ?></div>
        <div class="summary-card-label">Saldo Devedor Total</div>
    </div>
    <div class="summary-card card-balance-pos">
        <div class="summary-card-icon"><i class="fa-solid fa-chart-line"></i></div>
        <div class="summary-card-value"><?= fmtBrlDebt((float) $paidCurrent) ?></div>
        <div class="summary-card-label">Redução em <?= str_pad((string)$mes, 2, '0', STR_PAD_LEFT) ?>/<?= $ano ?></div>
        <div class="summary-card-footer <?= $deltaPaid >= 0 ? 'text-success' : 'text-danger' ?>">
            <i class="fa-solid <?= $deltaPaid >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' ?>"></i>
            <?= fmtBrlDebt(abs((float) $deltaPaid)) ?> vs mês anterior
        </div>
    </div>
</div>

<div class="card filter-card">
    <form method="GET" action="<?= $basePath ?>/dividas-parceladas" class="filter-form filter-form-inline">
        <div class="filter-group">
            <label class="filter-label">Mês</label>
            <select name="mes" class="form-control form-control-sm">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $m === (int) $mes ? 'selected' : '' ?>><?= str_pad((string)$m, 2, '0', STR_PAD_LEFT) ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Ano</label>
            <select name="ano" class="form-control form-control-sm">
                <?php for ($y = (int) date('Y') - 3; $y <= (int) date('Y') + 1; $y++): ?>
                <option value="<?= $y ?>" <?= $y === (int) $ano ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-filter"></i> Atualizar período
            </button>
        </div>
    </form>
</div>

<div class="debt-main-grid">
    <div class="card debt-list-card">
        <div class="card-header">
            <h3 class="card-title">Dívidas Registradas</h3>
        </div>
        <div class="card-body debt-list-body">
            <?php if (empty($debts)): ?>
            <div class="empty-state py-10">
                <i class="fa-solid fa-wallet"></i>
                <p>Nenhuma dívida cadastrada ainda.</p>
            </div>
            <?php else: ?>
            <div class="debt-list">
                <?php foreach ($debts as $debt): ?>
                <?php
                    $abertas = max(0, (int) $debt['total_parcelas'] - (int) $debt['parcelas_pagas']);
                    $statusClass = 'debt-status-near';
                    if ($abertas > 7) {
                        $statusClass = 'debt-status-high';
                    } elseif ($abertas > 4) {
                        $statusClass = 'debt-status-mid';
                    }
                ?>
                <article class="debt-item <?= $statusClass ?> <?= (int) $debt['ativo'] === 0 ? 'debt-item-closed' : '' ?>">
                    <div class="debt-item-top">
                        <div class="debt-item-desc-wrap">
                            <h4 class="debt-item-title"><?= htmlspecialchars((string) $debt['descricao']) ?></h4>
                            <p class="debt-item-sub">Saldo devedor: <strong><?= fmtBrlDebt((float) $debt['saldo_devedor']) ?></strong></p>
                        </div>
                        <div class="debt-item-value-wrap">
                            <div class="debt-item-value"><?= fmtBrlDebt((float) $debt['valor_parcela']) ?></div>
                            <div class="debt-item-progress">(<?= (int) $debt['parcelas_pagas'] ?>/<?= (int) $debt['total_parcelas'] ?>)</div>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card debt-chart-card">
        <div class="card-header">
            <h3 class="card-title">Demonstrativo de Redução</h3>
            <span class="badge badge-info">Bruta: <?= fmtBrlDebt((float) ($series['grossReduction'] ?? 0)) ?></span>
        </div>
        <div class="card-body">
            <?php if (!empty($motivation)): ?>
            <div class="debt-motivation <?= htmlspecialchars((string) ($motivation['class'] ?? 'debt-motivation-neutral')) ?>">
                <div class="debt-motivation-title"><?= htmlspecialchars((string) ($motivation['title'] ?? '')) ?></div>
                <div class="debt-motivation-message"><?= htmlspecialchars((string) ($motivation['message'] ?? '')) ?></div>
            </div>
            <?php endif; ?>
            <canvas id="debtReductionChart" height="260"></canvas>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modalNovaDivida" style="display:none">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fa-solid fa-file-signature"></i> Cadastrar Dívida Parcelada</h3>
            <button class="modal-close" onclick="closeModal('modalNovaDivida')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= $basePath ?>/dividas-parceladas" id="debtForm">
            <div class="modal-body">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) $csrf) ?>">

                <div class="form-group">
                    <label class="form-label">Descrição</label>
                    <input type="text" class="form-control" name="descricao" required>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label class="form-label">Valor da parcela</label>
                        <input type="text" class="form-control currency-input" name="valor_parcela" required>
                    </div>
                    <div class="form-group col-6">
                        <label class="form-label">Quantidade total de parcelas</label>
                        <input type="number" class="form-control" name="total_parcelas" min="1" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label class="form-label">Parcelas já pagas</label>
                        <input type="number" class="form-control" name="parcelas_pagas" min="0" value="0" required>
                    </div>
                    <div class="form-group col-6">
                        <label class="form-label">Saldo devedor calculado</label>
                        <input type="text" class="form-control" id="saldoPreview" value="R$ 0,00" readonly>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modalNovaDivida')">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Salvar dívida</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const labels = <?= json_encode($series['labels'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const totals = <?= json_encode($series['totals'] ?? []) ?>;
    const paidCumulative = <?= json_encode($series['paidCumulative'] ?? []) ?>;

    const ctx = document.getElementById('debtReductionChart');
    if (ctx && Array.isArray(labels) && labels.length > 0 && typeof Chart !== 'undefined') {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Saldo devedor atual',
                        data: totals,
                        borderColor: '#0f766e',
                        backgroundColor: 'rgba(15,118,110,.14)',
                        borderWidth: 3,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Total pago acumulado',
                        data: paidCumulative,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245,158,11,.08)',
                        borderWidth: 3,
                        borderDash: [8, 5],
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        fill: true,
                        tension: 0.25
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return 'R$ ' + Number(value).toLocaleString('pt-BR');
                            }
                        }
                    }
                }
            }
        });
    }
})();

(function () {
    const form = document.getElementById('debtForm');
    if (!form) return;

    const valorInput = form.querySelector('[name="valor_parcela"]');
    const totalInput = form.querySelector('[name="total_parcelas"]');
    const pagasInput = form.querySelector('[name="parcelas_pagas"]');
    const saldoPreview = document.getElementById('saldoPreview');

    const parseMoney = (v) => {
        if (!v) return 0;
        return parseFloat(String(v).replace(/\./g, '').replace(',', '.')) || 0;
    };

    const refreshSaldo = () => {
        const valor = parseMoney(valorInput ? valorInput.value : '0');
        const total = parseInt(totalInput ? totalInput.value : '0', 10) || 0;
        const pagas = parseInt(pagasInput ? pagasInput.value : '0', 10) || 0;
        const abertas = Math.max(0, total - pagas);
        const saldo = valor * abertas;
        if (saldoPreview) {
            saldoPreview.value = 'R$ ' + saldo.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    };

    [valorInput, totalInput, pagasInput].forEach((el) => {
        if (!el) return;
        el.addEventListener('input', refreshSaldo);
    });

    refreshSaldo();
})();
</script>

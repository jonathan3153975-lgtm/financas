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

<div class="cards-grid cards-grid-4">
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
    <?php
        $saldoEco = (float) $totalEconomia - (float) $totalJuros;
        $ecoClass = $saldoEco >= 0 ? 'card-balance-pos' : 'card-balance-neg';
    ?>
    <div class="summary-card <?= $ecoClass ?>">
        <div class="summary-card-icon">
            <i class="fa-solid <?= $saldoEco >= 0 ? 'fa-tag' : 'fa-receipt' ?>"></i>
        </div>
        <div class="summary-card-value" style="font-size:1rem">
            <?php if ((float)$totalEconomia > 0): ?>
            <span class="text-success" title="Economia acumulada"><i class="fa-solid fa-tag fa-xs"></i> <?= fmtBrlDebt((float)$totalEconomia) ?></span><br>
            <?php endif; ?>
            <?php if ((float)$totalJuros > 0): ?>
            <span class="text-danger" title="Juros acumulados"><i class="fa-solid fa-receipt fa-xs"></i> <?= fmtBrlDebt((float)$totalJuros) ?></span>
            <?php endif; ?>
            <?php if ((float)$totalEconomia == 0 && (float)$totalJuros == 0): ?>
            <span style="opacity:.5">—</span>
            <?php endif; ?>
        </div>
        <div class="summary-card-label">Economia / Juros acumulados</div>
        <?php if ((float)$totalEconomia > 0 || (float)$totalJuros > 0): ?>
        <div class="summary-card-footer <?= $saldoEco >= 0 ? 'text-success' : 'text-danger' ?>">
            Saldo líquido: <?= ($saldoEco < 0 ? '−' : '+') ?> <?= fmtBrlDebt(abs($saldoEco)) ?>
        </div>
        <?php endif; ?>
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
                            <?php if ((float)$debt['total_economia'] > 0 || (float)$debt['total_juros'] > 0): ?>
                            <p class="debt-item-sub" style="margin-top:2px;display:flex;gap:10px;flex-wrap:wrap">
                                <?php if ((float)$debt['total_economia'] > 0): ?>
                                <span class="text-success" style="font-size:.72rem">
                                    <i class="fa-solid fa-tag fa-xs"></i> Economia: <?= fmtBrlDebt((float)$debt['total_economia']) ?>
                                </span>
                                <?php endif; ?>
                                <?php if ((float)$debt['total_juros'] > 0): ?>
                                <span class="text-danger" style="font-size:.72rem">
                                    <i class="fa-solid fa-receipt fa-xs"></i> Juros: <?= fmtBrlDebt((float)$debt['total_juros']) ?>
                                </span>
                                <?php endif; ?>
                            </p>
                            <?php endif; ?>
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
                        <label class="form-label">Total de parcelas</label>
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

                <hr style="margin:12px 0; border-color:var(--border)">
                <p class="text-sm text-muted mb-2" style="display:flex;align-items:center;gap:6px">
                    <i class="fa-solid fa-calendar-check" style="color:var(--primary)"></i>
                    <strong>Lançamento automático em movimentações</strong>
                </p>
                <p class="text-xs text-muted mb-3">
                    Defina o período de início e o dia de vencimento. As parcelas restantes serão lançadas
                    automaticamente como <em>previsão pendente</em> em Movimentações — efetive cada uma quando pagar.
                </p>

                <div class="form-row">
                    <div class="form-group col-4">
                        <label class="form-label">Mês início</label>
                        <select class="form-control" name="mes_inicio" id="mesInicio">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $m === (int) date('m') ? 'selected' : '' ?>>
                                <?= str_pad((string) $m, 2, '0', STR_PAD_LEFT) ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Ano início</label>
                        <select class="form-control" name="ano_inicio" id="anoInicio">
                            <?php for ($y = (int) date('Y'); $y <= (int) date('Y') + 5; $y++): ?>
                            <option value="<?= $y ?>" <?= $y === (int) date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Dia vencimento</label>
                        <input type="number" class="form-control" name="dia_vencimento" min="1" max="28" value="1" id="diaVencimento">
                    </div>
                </div>

                <div id="previewParcelas" class="text-xs text-muted" style="background:var(--bg-secondary);border-radius:6px;padding:8px 10px;display:none">
                    <!-- preenchido por JS -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modalNovaDivida')">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Salvar e lançar previsões</button>
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

    const valorInput  = form.querySelector('[name="valor_parcela"]');
    const totalInput  = form.querySelector('[name="total_parcelas"]');
    const pagasInput  = form.querySelector('[name="parcelas_pagas"]');
    const mesInput    = document.getElementById('mesInicio');
    const anoInput    = document.getElementById('anoInicio');
    const diaInput    = document.getElementById('diaVencimento');
    const saldoPreview   = document.getElementById('saldoPreview');
    const previewBox  = document.getElementById('previewParcelas');

    const parseMoney = (v) => parseFloat(String(v || '0').replace(/\./g, '').replace(',', '.')) || 0;
    const fmtMoney   = (v) => 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const mesesNome = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];

    const refreshAll = () => {
        const valor  = parseMoney(valorInput?.value);
        const total  = parseInt(totalInput?.value || '0', 10) || 0;
        const pagas  = parseInt(pagasInput?.value || '0', 10) || 0;
        const abertas = Math.max(0, total - pagas);

        if (saldoPreview) {
            saldoPreview.value = fmtMoney(valor * abertas);
        }

        // preview de parcelas
        if (!previewBox) return;
        const mes = parseInt(mesInput?.value || '0', 10);
        const ano = parseInt(anoInput?.value || '0', 10);
        const dia = Math.min(28, Math.max(1, parseInt(diaInput?.value || '1', 10)));

        if (abertas <= 0 || mes < 1 || ano < 2000 || valor <= 0) {
            previewBox.style.display = 'none';
            return;
        }

        const linhas = [];
        for (let i = 0; i < Math.min(abertas, 6); i++) {
            let m = mes + i;
            let a = ano;
            while (m > 12) { m -= 12; a++; }
            linhas.push(`Parcela ${pagas + i + 1}/${total} — ${String(dia).padStart(2,'0')}/${String(m).padStart(2,'0')}/${a} — ${fmtMoney(valor)}`);
        }
        if (abertas > 6) linhas.push(`… e mais ${abertas - 6} parcela(s)`);

        previewBox.innerHTML = '<strong>Previsão de lançamentos:</strong><br>' + linhas.join('<br>');
        previewBox.style.display = 'block';
    };

    [valorInput, totalInput, pagasInput, mesInput, anoInput, diaInput].forEach(el => {
        el?.addEventListener('input', refreshAll);
        el?.addEventListener('change', refreshAll);
    });

    refreshAll();
})();
</script>

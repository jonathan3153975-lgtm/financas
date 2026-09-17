<?php
$pageTitle = 'Painel Público de Apostas';
$meses = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
$basePath = defined('BASE_URL') ? BASE_URL : ($_ENV['APP_BASE_PATH'] ?? '');

function fmtBetMoneyPub(float $v): string {
    return 'R$ ' . number_format(abs($v), 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>/css/style.css">
    <style>
        body { background: var(--bg, #f8fafc); padding: 24px; }
        .public-wrapper { max-width: 1100px; margin: 0 auto; }
        .public-banner { background: #0f172a; color: #f8fafc; padding: 14px 20px; border-radius: 12px; margin-bottom: 20px; display:flex; align-items:center; gap:10px; }
        .bets-cell-cat { display: block; font-size: 12px; font-style: italic; color: var(--text-muted); margin-top: 2px; }
    </style>
</head>
<body>
<div class="public-wrapper">
    <div class="public-banner">
        <i class="fa-solid fa-eye"></i>
        <span>Visualização pública (somente leitura) do painel de apostas</span>
    </div>

    <div class="page-header">
        <div>
            <h1 class="page-title">Gerenciamento de Apostas</h1>
            <p class="page-subtitle"><?= $meses[$mes] ?> / <?= $ano ?></p>
        </div>
        <form method="GET" class="period-form">
            <select name="mes" class="form-control form-control-sm" onchange="this.form.submit()">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $m === $mes ? 'selected' : '' ?>><?= $meses[$m] ?></option>
                <?php endfor; ?>
            </select>
            <select name="ano" class="form-control form-control-sm" onchange="this.form.submit()">
                <?php for ($y = (int) date('Y') - 4; $y <= (int) date('Y') + 1; $y++): ?>
                <option value="<?= $y ?>" <?= $y === $ano ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </form>
    </div>

    <div class="cards-grid" style="grid-template-columns:repeat(5,1fr); gap:16px">
        <div class="summary-card">
            <div class="summary-card-value"><?= fmtBetMoneyPub($kpis['total_apostado']) ?></div>
            <div class="summary-card-label">Total Apostado</div>
        </div>
        <div class="summary-card card-income">
            <div class="summary-card-value"><?= fmtBetMoneyPub($kpis['retorno_liquido']) ?></div>
            <div class="summary-card-label">Retorno Líquido Total</div>
        </div>
        <div class="summary-card card-balance-pos">
            <div class="summary-card-value"><?= fmtBetMoneyPub($kpis['media_retorno']) ?></div>
            <div class="summary-card-label">Média Retorno / Aposta</div>
        </div>
        <div class="summary-card card-expense">
            <div class="summary-card-value"><?= fmtBetMoneyPub($kpis['total_perda']) ?></div>
            <div class="summary-card-label">Total de Perda</div>
        </div>
        <div class="summary-card card-balance-neg">
            <div class="summary-card-value"><?= fmtBetMoneyPub($kpis['media_perda']) ?></div>
            <div class="summary-card-label">Média Perda / Aposta</div>
        </div>
    </div>

    <div class="card" style="margin-top:16px; max-width:280px">
        <div class="card-body">
            <div class="summary-card-label">Saldo da Banca</div>
            <div class="summary-card-value <?= $saldoBanca < 0 ? 'text-danger' : 'text-success' ?>">
                <?= $saldoBanca < 0 ? '- ' : '' ?><?= fmtBetMoneyPub($saldoBanca) ?>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:16px">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-calendar-days"></i> Totais Diários</h3>
        </div>
        <div class="card-body p-0">
            <?php if (empty($daily)): ?>
            <div class="empty-state py-10">
                <i class="fa-solid fa-inbox"></i>
                <p>Nenhuma aposta registrada no período selecionado.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Data</th><th class="text-center">Apostas</th>
                            <th class="text-center">Vitórias</th><th class="text-center">Derrotas</th>
                            <th class="text-right">Total Apostado</th><th class="text-right">Saldo Final</th>
                            <th class="text-center">Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daily as $d): ?>
                        <tr>
                            <td class="font-medium"><?= date('d/m/Y', strtotime((string) $d['data'])) ?></td>
                            <td class="text-center"><?= (int) $d['qtd'] ?></td>
                            <td class="text-center"><span class="badge badge-success"><?= (int) $d['vitorias'] ?></span></td>
                            <td class="text-center"><span class="badge badge-danger"><?= (int) $d['derrotas'] ?></span></td>
                            <td class="text-right"><?= fmtBetMoneyPub((float) $d['total_apostado']) ?></td>
                            <td class="text-right font-semibold <?= (float) $d['saldo'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= (float) $d['saldo'] >= 0 ? '+' : '-' ?> <?= fmtBetMoneyPub((float) $d['saldo']) ?>
                            </td>
                            <td class="text-center">
                                <button class="action-btn action-btn-primary" onclick="openDayModal('<?= htmlspecialchars((string) $d['data']) ?>')">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modalDay" style="display:none">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">Apostas do dia <span id="dayModalDate"></span></h3>
            <button class="modal-close" onclick="document.getElementById('modalDay').style.display='none'"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr><th>Descrição</th><th>Odd</th><th class="text-right">Valor</th><th>Status</th><th class="text-right">Resultado</th></tr>
                    </thead>
                    <tbody id="dayModalBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
const APOSTAS_STATUS_LABELS = {
    pendente:  ['Pendente',  'badge-warning'],
    vitoria:   ['Vitória',   'badge-success'],
    derrota:   ['Derrota',   'badge-danger'],
    reembolso: ['Reembolso', 'badge-info'],
};

function fmtMoneyJs(v) {
    const n = Number(v) || 0;
    return 'R$ ' + Math.abs(n).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
function fmtOddJs(v) { return Number(v).toFixed(2).replace('.', ','); }

function openDayModal(date) {
    document.getElementById('dayModalDate').textContent = date.split('-').reverse().join('/');
    document.getElementById('dayModalBody').innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Carregando...</td></tr>';
    document.getElementById('modalDay').style.display = 'flex';

    fetch(`<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') ?>`.split('?')[0] + `/dia/${date}`)
        .then(r => r.json())
        .then(data => {
            const records = data.records || [];
            const tbody = document.getElementById('dayModalBody');
            if (!records.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Nenhum registro.</td></tr>';
                return;
            }
            tbody.innerHTML = records.map(r => {
                const [label, cls] = APOSTAS_STATUS_LABELS[r.status] || ['-', 'badge-secondary'];
                const lucro = r.lucro !== null && r.lucro !== undefined ? Number(r.lucro) : null;
                const lucroHtml = lucro === null ? '<span class="text-muted">-</span>' : `<span class="${lucro >= 0 ? 'text-success' : 'text-danger'}">${lucro >= 0 ? '+' : '-'} ${fmtMoneyJs(lucro)}</span>`;
                return `<tr>
                    <td><span class="bets-cell-desc">${r.descricao}</span>${r.categoria_nome ? `<span class="bets-cell-cat">${r.categoria_nome}</span>` : ''}</td>
                    <td>${fmtOddJs(r.odd)}</td>
                    <td class="text-right">${fmtMoneyJs(r.valor_apostado)}</td>
                    <td><span class="badge ${cls}">${label}</span></td>
                    <td class="text-right">${lucroHtml}</td>
                </tr>`;
            }).join('');
        });
}
</script>
</body>
</html>

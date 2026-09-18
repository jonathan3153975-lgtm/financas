<?php
$pageTitle  = 'Gerenciamento de Apostas';
$basePath   = defined('BASE_URL') ? BASE_URL : ($_ENV['APP_BASE_PATH'] ?? '');
$meses      = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
$todayDate  = date('Y-m-d');
$statusLabels = [
    'pendente'  => ['Pendente',  'badge-warning'],
    'vitoria'   => ['Vitória',   'badge-success'],
    'derrota'   => ['Derrota',   'badge-danger'],
    'reembolso' => ['Reembolso', 'badge-info'],
];
$usr = $this->getUser();

function fmtBetMoney(float $v): string {
    return 'R$ ' . number_format(abs($v), 2, ',', '.');
}
function fmtBetOdd(float $v): string {
    return number_format($v, 2, ',', '.');
}

$periodoLabel = date('d/m/Y', strtotime($periodInicio)) . ' a ' . date('d/m/Y', strtotime($periodFim));

$shareUrl = $shareLink ? ($basePath . '/apostas/compartilhado/' . $shareLink['token']) : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — JW Finanças</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= $basePath ?>/css/style.css">
    <style>
        /* ============================================================
           TEMA EXCLUSIVO DO MÓDULO DE APOSTAS — "Neon Dark"
           Sobrescreve as variáveis do tema padrão apenas nesta página.
        ============================================================ */
        body.bets-body {
            --bg:            #0a0a0e;
            --card-bg:       #15151d;
            --border:        #26262f;
            --text:          #e8e8f0;
            --text-muted:    #8b8b9b;
            --text-light:    #6b6b7a;
            --accent:        #8b5cf6;
            --accent-dark:   #7c3aed;
            --accent-light:  #a78bfa;
            --success:       #39ff88;
            --danger:        #ff2e63;
            min-height: 100vh;
        }

        .neon-green  { color: #39ff88 !important; text-shadow: 0 0 12px rgba(57,255,136,.55); }
        .neon-red    { color: #ff2e63 !important; text-shadow: 0 0 12px rgba(255,46,99,.55); }
        .neon-purple { color: #a78bfa !important; text-shadow: 0 0 12px rgba(167,139,250,.5); }
        .neon-blue   { color: #38bdf8 !important; text-shadow: 0 0 12px rgba(56,189,248,.5); }
        .neon-amber  { color: #facc15 !important; text-shadow: 0 0 12px rgba(250,204,21,.5); }

        .bets-topbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 28px; background: #0f0f16; border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 50;
        }
        .bets-topbar-brand { display: flex; align-items: center; gap: 10px; font-weight: 700; letter-spacing: .3px; }
        .bets-topbar-brand i { color: #a78bfa; text-shadow: 0 0 10px rgba(167,139,250,.6); }
        .bets-topbar-right { display: flex; align-items: center; gap: 18px; }
        .bets-topbar-right a { color: var(--text-muted); font-size: 13px; }
        .bets-topbar-right a:hover { color: #a78bfa; }
        .bets-user-chip { font-size: 13px; color: var(--text-muted); }

        .bets-wrapper { max-width: 1240px; margin: 0 auto; padding: 24px 28px 60px; }

        .bets-hero {
            background: linear-gradient(135deg, rgba(139,92,246,.16), rgba(21,21,29,.5));
            border: 1px solid rgba(139,92,246,.4);
            border-radius: 18px;
            padding: 28px 32px;
            margin-bottom: 20px;
            box-shadow: 0 0 40px rgba(139,92,246,.12), inset 0 0 60px rgba(139,92,246,.04);
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;
        }
        .bets-hero-label { font-size: 13px; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-muted); margin-bottom: 6px; }
        .bets-hero-value { font-size: 44px; font-weight: 800; line-height: 1; }
        .bets-hero-meta { font-size: 13px; color: var(--text-muted); margin-top: 8px; }

        .bets-kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin-bottom: 20px; }
        .bets-kpi-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 0 24px rgba(0,0,0,.35);
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .bets-kpi-card:hover { transform: translateY(-2px); }
        .bets-kpi-card.kpi-green  { border-color: rgba(57,255,136,.35);  box-shadow: 0 0 24px rgba(57,255,136,.1); }
        .bets-kpi-card.kpi-red    { border-color: rgba(255,46,99,.35);   box-shadow: 0 0 24px rgba(255,46,99,.1); }
        .bets-kpi-card.kpi-purple { border-color: rgba(139,92,246,.35);  box-shadow: 0 0 24px rgba(139,92,246,.1); }
        .bets-kpi-card.kpi-blue   { border-color: rgba(56,189,248,.35);  box-shadow: 0 0 24px rgba(56,189,248,.1); }
        .bets-kpi-card.kpi-amber  { border-color: rgba(250,204,21,.35);  box-shadow: 0 0 24px rgba(250,204,21,.1); }
        .bets-kpi-icon { font-size: 20px; margin-bottom: 10px; opacity: .85; }
        .bets-kpi-value { font-size: 28px; font-weight: 800; margin-bottom: 4px; }
        .bets-kpi-label { font-size: 13px; color: var(--text-muted); margin-bottom: 12px; }
        .bets-kpi-sub {
            display: flex; align-items: center; justify-content: space-between;
            border-top: 1px dashed var(--border); padding-top: 10px; font-size: 13px; color: var(--text-muted);
        }
        .bets-kpi-sub strong { color: var(--text); }

        /* Barra de definição do período no topo */
        .bets-period-bar { margin-bottom: 20px; }
        .bets-period-bar .filter-group { flex: 0 0 auto; }
        .bets-period-divider { align-self: flex-end; padding-bottom: 8px; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-muted); }
        .bets-period-bar-active { border: 1px solid rgba(139,92,246,.45); border-radius: 14px; padding: 12px 16px; background: rgba(139,92,246,.06); margin-bottom: 16px; }

        .bets-curtain { margin-bottom: 20px; }
        .curtain-toggle {
            width: 100%; display: flex; align-items: center; justify-content: space-between;
            background: var(--card-bg); border: 1px solid var(--border); border-radius: 14px;
            padding: 14px 20px; color: var(--text); font-weight: 600; cursor: pointer; font-size: 14px;
        }
        .curtain-toggle:hover { border-color: var(--accent); }
        .curtain-toggle i.fa-chevron-down { transition: transform .25s ease; }
        .curtain-toggle.open i.fa-chevron-down { transform: rotate(180deg); }
        .curtain-panel {
            max-height: 0; overflow: hidden; transition: max-height .3s ease;
            display: flex; flex-wrap: wrap; gap: 10px;
            background: var(--card-bg); border: 1px solid var(--border); border-top: none;
            border-radius: 0 0 14px 14px;
        }
        .curtain-panel.open { max-height: 240px; padding: 16px 20px; }

        .bets-daily-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; }
        .bets-compare { display: inline-flex; align-items: center; gap: 5px; font-weight: 600; font-size: 12px; padding: 3px 9px; border-radius: 999px; }
        .bets-compare.up   { background: rgba(57,255,136,.12); color: #39ff88; }
        .bets-compare.down { background: rgba(255,46,99,.12); color: #ff2e63; }
        .bets-compare.flat { background: rgba(255,255,255,.06); color: var(--text-muted); }

        .bets-preview { font-size: 12px; color: var(--text-muted); margin-top: -8px; margin-bottom: 14px; }
        .bets-preview strong { color: #a78bfa; }

        /* Inputs/selects um pouco mais claros que o fundo, para contraste no tema escuro */
        body.bets-body .form-control {
            background: #20202b;
            border-color: var(--border);
            color: var(--text);
        }
        body.bets-body .form-control:focus { border-color: var(--accent); }
        body.bets-body .form-control::placeholder { color: var(--text-light); }
        body.bets-body .form-control option { background: #20202b; color: var(--text); }

        /* Barras de rolagem no estilo do tema escuro/neon */
        body.bets-body { scrollbar-width: thin; scrollbar-color: #8b5cf6 #15151d; }
        body.bets-body ::-webkit-scrollbar { width: 10px; height: 10px; }
        body.bets-body ::-webkit-scrollbar-track { background: #15151d; border-radius: 8px; }
        body.bets-body ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #8b5cf6, #6d28d9);
            border-radius: 8px;
            border: 2px solid #15151d;
        }
        body.bets-body ::-webkit-scrollbar-thumb:hover { background: #a78bfa; }

        /* Hover das tabelas no tom do sistema (roxo), em vez do cinza padrão */
        body.bets-body .table-hover tr:hover td { background: rgba(139,92,246,.14) !important; }

        /* Possíveis entradas */
        .prospect-check { width: 16px; height: 16px; accent-color: #8b5cf6; }
        .bets-prospect-footer {
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
            padding: 14px 20px; border-top: 1px solid var(--border);
        }
        .bets-prospect-combo { font-size: 13px; color: var(--text-muted); }
        .bets-prospect-combo strong { color: #a78bfa; font-size: 16px; }

        /* Descrição com a categoria logo abaixo (fonte menor e itálica) */
        .bets-cell-desc { font-weight: 600; }
        .bets-cell-cat { display: block; font-size: 12px; font-style: italic; color: var(--text-muted); margin-top: 2px; }

        /* Extrato por Período: largura ampliada e espaçamento entre filtros/cards/tabela */
        #modalStatement .modal-dialog { max-width: 864px; } /* .modal-lg (720px) + 20% */
        #modalStatement .filter-form { gap: 16px; margin-bottom: 20px; }
        #modalStatement .filter-group { flex: 1 1 0; min-width: 140px; }
        #modalStatement #stmtTotals { margin-bottom: 20px; }

        /* Apostas do dia: mesma largura ampliada do Extrato por Período */
        #modalDay .modal-dialog { max-width: 864px; }

        /* Seleções da aposta múltipla (ao clicar no sinalizador) */
        .bets-mult-badge { cursor: pointer; }
        .bets-mult-badge:hover { filter: brightness(1.25); }
        .bets-mult-sel-row td { background: rgba(139,92,246,.07); }
        .bets-mult-sel { padding: 10px 4px 12px 20px; }
        .bets-mult-sel-title { font-size: 12px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-muted); margin-bottom: 6px; }
        .bets-mult-sel ul { margin: 0; padding-left: 18px; }
        .bets-mult-sel li { font-size: 13px; color: var(--text); padding: 2px 0; }

        @media (max-width: 640px) {
            #modalStatement .modal-dialog,
            #modalDay .modal-dialog { max-width: 100%; }
        }
    </style>
</head>
<body class="bets-body">

<div class="bets-topbar">
    <div class="bets-topbar-brand"><i class="fa-solid fa-dice"></i> Gerenciamento de Apostas</div>
    <div class="bets-topbar-right">
        <span class="bets-user-chip"><?= htmlspecialchars($usr['nome'] ?? '') ?></span>
        <a href="<?= $basePath ?>/dashboard"><i class="fa-solid fa-arrow-left"></i> Voltar ao sistema</a>
        <a href="<?= $basePath ?>/logout"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
    </div>
</div>

<div class="bets-wrapper">

    <?php
    $flash = $flash ?? $this->getFlash();
    foreach (['success', 'error', 'info', 'warning'] as $type):
        if (!empty($flash[$type])):
    ?>
    <div class="flash flash-<?= $type ?>" role="alert" id="flashMsg">
        <i class="fa-solid <?= match($type) {
            'success' => 'fa-circle-check',
            'error'   => 'fa-circle-xmark',
            'warning' => 'fa-triangle-exclamation',
            default   => 'fa-circle-info',
        } ?>"></i>
        <?= htmlspecialchars($flash[$type]) ?>
        <button class="flash-close" onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <?php endif; endforeach; ?>

    <!-- ================================================================
         Definição do período (mês/ano ou intervalo de datas)
    ================================================================ -->
    <form method="GET" action="<?= $basePath ?>/apostas"
          class="filter-form bets-period-bar <?= $usandoRange ? 'bets-period-bar-active' : '' ?>">
        <div class="filter-group">
            <label class="filter-label">Mês</label>
            <select name="mes" id="periodMes" class="form-control form-control-sm"
                    onchange="document.getElementById('periodInicio').value='';document.getElementById('periodFim').value='';this.form.submit()">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $m === $mes ? 'selected' : '' ?>><?= $meses[$m] ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Ano</label>
            <select name="ano" id="periodAno" class="form-control form-control-sm"
                    onchange="document.getElementById('periodInicio').value='';document.getElementById('periodFim').value='';this.form.submit()">
                <?php for ($y = (int) date('Y') - 4; $y <= (int) date('Y') + 1; $y++): ?>
                <option value="<?= $y ?>" <?= $y === $ano ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <span class="bets-period-divider">ou período</span>
        <div class="filter-group">
            <label class="filter-label">De</label>
            <input type="date" name="inicio" id="periodInicio" class="form-control form-control-sm" value="<?= htmlspecialchars($inicio) ?>">
        </div>
        <div class="filter-group">
            <label class="filter-label">Até</label>
            <input type="date" name="fim" id="periodFim" class="form-control form-control-sm" value="<?= htmlspecialchars($fim) ?>">
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Filtrar</button>
        </div>
    </form>

    <!-- ================================================================
         Saldo da banca — destaque
    ================================================================ -->
    <div class="bets-hero">
        <div>
            <div class="bets-hero-label">Saldo Final do Período</div>
            <div class="bets-hero-value <?= $saldoPeriodo >= 0 ? 'neon-green' : 'neon-red' ?>">
                <?= $saldoPeriodo < 0 ? '- ' : '' ?><?= fmtBetMoney($saldoPeriodo) ?>
            </div>
            <div class="bets-hero-meta">
                Período: <?= $periodoLabel ?> · Saldo inicial: <?= fmtBetMoney($saldoInicio) ?><br>
                Saldo total histórico: <?= fmtBetMoney($saldoTotalAllTime) ?>
            </div>
        </div>
        <div style="text-align:right">
            <div class="bets-hero-meta"><?= $kpis['qtd_pendentes'] ?> pendente(s) · <?= $kpis['qtd_reembolsos'] ?> reembolso(s)</div>
            <button class="btn btn-primary btn-sm mt-2" onclick="openModal('modalBank')">
                <i class="fa-solid fa-wallet"></i> Movimentar Banca
            </button>
        </div>
    </div>

    <!-- ================================================================
         KPIs agrupados
    ================================================================ -->
    <div class="bets-kpi-grid">

        <div class="bets-kpi-card kpi-purple">
            <div class="bets-kpi-icon neon-purple"><i class="fa-solid fa-coins"></i></div>
            <div class="bets-kpi-value neon-purple"><?= fmtBetMoney($kpis['total_apostado']) ?></div>
            <div class="bets-kpi-label">Total Apostado — <?= $periodoLabel ?></div>
            <div class="bets-kpi-sub">
                <span>Apostas no período</span>
                <strong><?= $kpis['qtd_total'] ?></strong>
            </div>
            <div class="bets-kpi-sub">
                <span>Média por aposta</span>
                <strong><?= fmtBetMoney($kpis['media_aposta']) ?></strong>
            </div>
        </div>

        <div class="bets-kpi-card kpi-green">
            <div class="bets-kpi-icon neon-green"><i class="fa-solid fa-trophy"></i></div>
            <div class="bets-kpi-value neon-green"><?= fmtBetMoney($kpis['retorno_liquido']) ?></div>
            <div class="bets-kpi-label">Retorno Líquido Total (<?= $kpis['qtd_vitorias'] ?> vitória(s))</div>
            <div class="bets-kpi-sub">
                <span>Média por aposta vencedora</span>
                <strong><?= fmtBetMoney($kpis['media_retorno']) ?></strong>
            </div>
        </div>

        <div class="bets-kpi-card kpi-red">
            <div class="bets-kpi-icon neon-red"><i class="fa-solid fa-skull-crossbones"></i></div>
            <div class="bets-kpi-value neon-red"><?= fmtBetMoney($kpis['total_perda']) ?></div>
            <div class="bets-kpi-label">Total de Perda (<?= $kpis['qtd_derrotas'] ?> derrota(s))</div>
            <div class="bets-kpi-sub">
                <span>Média por aposta perdida</span>
                <strong><?= fmtBetMoney($kpis['media_perda']) ?></strong>
            </div>
        </div>

    </div>

    <div class="bets-kpi-grid" style="grid-template-columns: repeat(3, 1fr)">
        <div class="bets-kpi-card kpi-blue">
            <div class="bets-kpi-icon neon-blue"><i class="fa-solid fa-money-bill-transfer"></i></div>
            <div class="bets-kpi-value neon-blue"><?= fmtBetMoney($mov['entradas'] - $mov['saques']) ?></div>
            <div class="bets-kpi-label">Depósitos / Saques — <?= $periodoLabel ?></div>
            <div class="bets-kpi-sub">
                <span>Depósitos</span>
                <strong class="neon-green">+ <?= fmtBetMoney($mov['entradas']) ?></strong>
            </div>
            <div class="bets-kpi-sub">
                <span>Saques</span>
                <strong class="neon-red">- <?= fmtBetMoney($mov['saques']) ?></strong>
            </div>
        </div>

        <div class="bets-kpi-card <?= $balanco >= 0 ? 'kpi-green' : 'kpi-red' ?>">
            <div class="bets-kpi-icon <?= $balanco >= 0 ? 'neon-green' : 'neon-red' ?>"><i class="fa-solid fa-scale-balanced"></i></div>
            <div class="bets-kpi-value <?= $balanco >= 0 ? 'neon-green' : 'neon-red' ?>">
                <?= $balanco >= 0 ? '+ ' : '- ' ?><?= fmtBetMoney($balanco) ?>
            </div>
            <div class="bets-kpi-label">Balanço do Período — <?= $periodoLabel ?></div>
            <div class="bets-kpi-sub">
                <span>Variação sobre saldo inicial</span>
                <strong><?= $balancoPct === null ? '—' : ($balancoPct > 0 ? '+' : '') . number_format($balancoPct, 1, ',', '.') . '%' ?></strong>
            </div>
        </div>

        <div class="bets-kpi-card kpi-amber">
            <div class="bets-kpi-icon neon-amber"><i class="fa-solid fa-hourglass-half"></i></div>
            <div class="bets-kpi-value neon-amber"><?= fmtBetMoney($pendentes['total_apostado']) ?></div>
            <div class="bets-kpi-label">Apostas Pendentes (<?= $kpis['qtd_pendentes'] ?>)</div>
            <div class="bets-kpi-sub">
                <span>Possível retorno</span>
                <strong class="neon-amber"><?= fmtBetMoney($pendentes['total_retorno']) ?></strong>
            </div>
        </div>
    </div>

    <!-- ================================================================
         Ações — estilo cortina
    ================================================================ -->
    <div class="bets-curtain">
        <button type="button" class="curtain-toggle" id="curtainToggle" onclick="toggleCurtain()">
            <span><i class="fa-solid fa-bolt"></i> Ações Rápidas</span>
            <i class="fa-solid fa-chevron-down"></i>
        </button>
        <div class="curtain-panel" id="curtainPanel">
            <button class="btn btn-primary btn-sm" onclick="openModal('modalBetSimple')">
                <i class="fa-solid fa-plus"></i> Aposta Simples
            </button>
            <button class="btn btn-primary btn-sm" onclick="openModal('modalBetMultiple')">
                <i class="fa-solid fa-layer-group"></i> Aposta Múltipla
            </button>
            <button class="btn btn-ghost btn-sm" onclick="openModal('modalBank')">
                <i class="fa-solid fa-wallet"></i> Entrada / Saque
            </button>
            <button class="btn btn-ghost btn-sm" onclick="openModal('modalStatement')">
                <i class="fa-solid fa-file-invoice"></i> Extrato por Período
            </button>
            <button class="btn btn-ghost btn-sm" onclick="openModal('modalShare')">
                <i class="fa-solid fa-share-nodes"></i> Compartilhar
            </button>
        </div>
    </div>

    <!-- ================================================================
         Possíveis Entradas — levantamento antes de apostar
    ================================================================ -->
    <div class="bets-daily-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-magnifying-glass-chart"></i> Possíveis Entradas</h3>
            <button class="btn btn-ghost btn-sm" onclick="openModal('modalProspect')">
                <i class="fa-solid fa-plus"></i> Nova Possível Entrada
            </button>
        </div>
        <div class="card-body" style="padding:14px 20px 0">
            <form method="GET" action="<?= $basePath ?>/apostas" class="filter-form filter-open" style="margin-bottom:14px">
                <input type="hidden" name="mes" value="<?= $mes ?>">
                <input type="hidden" name="ano" value="<?= $ano ?>">
                <input type="hidden" name="inicio" value="<?= htmlspecialchars($inicio) ?>">
                <input type="hidden" name="fim" value="<?= htmlspecialchars($fim) ?>">
                <div class="filter-group">
                    <label class="filter-label">De</label>
                    <input type="date" name="prospect_de" class="form-control form-control-sm" value="<?= htmlspecialchars($prospectDe) ?>">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Até</label>
                    <input type="date" name="prospect_ate" class="form-control form-control-sm" value="<?= htmlspecialchars($prospectAte) ?>">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Filtrar</button>
                    <a href="<?= $basePath ?>/apostas?mes=<?= $mes ?>&ano=<?= $ano ?><?= $usandoRange ? '&inicio=' . urlencode($inicio) . '&fim=' . urlencode($fim) : '' ?>" class="btn btn-ghost btn-sm">Limpar</a>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <?php if (empty($prospects)): ?>
            <div class="empty-state py-10">
                <i class="fa-solid fa-magnifying-glass-chart"></i>
                <p>Nenhuma possível entrada cadastrada. Use este espaço para levantar oportunidades antes de apostar.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:36px"></th>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Data / Hora</th>
                            <th class="text-right">Odd</th>
                            <th>Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prospects as $p):
                            $convertido = !empty($p['aposta_status']);
                        ?>
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" class="prospect-check" onchange="updateProspectCombo()"
                                       data-id="<?= (int) $p['id'] ?>"
                                       data-descricao="<?= htmlspecialchars((string) $p['descricao']) ?>"
                                       data-categoria-id="<?= (int) ($p['categoria_id'] ?? 0) ?>"
                                       data-odd="<?= number_format((float) $p['odd'], 3, '.', '') ?>"
                                       <?= $convertido ? 'disabled title="Já convertida em aposta"' : '' ?>>
                            </td>
                            <td class="font-medium"><?= htmlspecialchars((string) $p['descricao']) ?></td>
                            <td><?= htmlspecialchars((string) ($p['categoria_nome'] ?? '-')) ?></td>
                            <td class="text-sm text-muted"><?= date('d/m/Y H:i', strtotime((string) $p['data_hora'])) ?></td>
                            <td class="text-right"><?= fmtBetOdd((float) $p['odd']) ?></td>
                            <td>
                                <?php if ($convertido): ?>
                                <span class="badge <?= $statusLabels[$p['aposta_status']][1] ?? 'badge-secondary' ?>">
                                    <i class="fa-solid fa-link"></i> <?= $statusLabels[$p['aposta_status']][0] ?? $p['aposta_status'] ?>
                                </span>
                                <?php else: ?>
                                <span class="badge badge-secondary">Em análise</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="action-btn action-btn-danger" title="Excluir" onclick="deleteProspect(<?= (int) $p['id'] ?>)">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <div class="bets-prospect-footer">
            <span class="bets-prospect-combo">
                <span id="prospectComboCount">0</span> selecionada(s) · Odd combinada: <strong id="prospectComboOdd">-</strong>
            </span>
            <button class="btn btn-primary btn-sm" onclick="quickRegisterFromProspects()">
                <i class="fa-solid fa-bolt"></i> Registrar Aposta Rápida
            </button>
        </div>
    </div>

    <!-- ================================================================
         Totais dia a dia
    ================================================================ -->
    <div class="bets-daily-card" style="margin-top:20px">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-calendar-days"></i> Totais Diários</h3>
            <span class="card-subtitle"><?= $periodoLabel ?></span>
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
                            <th>Data</th>
                            <th class="text-center">Apostas</th>
                            <th class="text-center">Vitórias</th>
                            <th class="text-center">Derrotas</th>
                            <th class="text-right">Total Apostado</th>
                            <th class="text-right">Saldo Final</th>
                            <th class="text-center">Vs. dia anterior</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daily as $d):
                            $comp = $d['comparativo'];
                        ?>
                        <tr>
                            <td class="font-medium"><?= date('d/m/Y', strtotime((string) $d['data'])) ?></td>
                            <td class="text-center"><?= (int) $d['qtd'] ?></td>
                            <td class="text-center"><span class="badge badge-success"><?= (int) $d['vitorias'] ?></span></td>
                            <td class="text-center"><span class="badge badge-danger"><?= (int) $d['derrotas'] ?></span></td>
                            <td class="text-right"><?= fmtBetMoney((float) $d['total_apostado']) ?></td>
                            <td class="text-right font-semibold <?= (float) $d['saldo'] >= 0 ? 'neon-green' : 'neon-red' ?>">
                                <?= (float) $d['saldo'] >= 0 ? '+' : '-' ?> <?= fmtBetMoney((float) $d['saldo']) ?>
                            </td>
                            <td class="text-center">
                                <?php if ($comp === null): ?>
                                <span class="bets-compare flat"><i class="fa-solid fa-minus"></i> —</span>
                                <?php elseif ((float) $comp > 0): ?>
                                <span class="bets-compare up"><i class="fa-solid fa-caret-up"></i> <?= fmtBetMoney((float) $comp) ?></span>
                                <?php elseif ((float) $comp < 0): ?>
                                <span class="bets-compare down"><i class="fa-solid fa-caret-down"></i> <?= fmtBetMoney((float) $comp) ?></span>
                                <?php else: ?>
                                <span class="bets-compare flat"><i class="fa-solid fa-equals"></i> R$ 0,00</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="action-btn action-btn-primary" title="Ver detalhes"
                                        onclick="openDayModal('<?= htmlspecialchars((string) $d['data']) ?>')">
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

    <!-- ================================================================
         Gráficos
    ================================================================ -->
    <div class="bets-kpi-grid" style="grid-template-columns: repeat(2, 1fr); margin-top:20px">
        <div class="bets-daily-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-chart-line"></i> Evolução Diária</h3>
                <span class="card-subtitle"><?= $periodoLabel ?></span>
            </div>
            <div class="card-body">
                <canvas id="chartDailyEvolution" height="220"></canvas>
            </div>
        </div>
        <div class="bets-daily-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-chart-column"></i> Comparativo Mensal da Banca</h3>
                <span class="card-subtitle">±2 meses</span>
            </div>
            <div class="card-body">
                <canvas id="chartMonthlyComparison" height="220"></canvas>
            </div>
        </div>
    </div>

    <!-- ================================================================
         MODAL: Aposta Simples
    ================================================================ -->
    <div class="modal-overlay" id="modalBetSimple" style="display:none">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fa-solid fa-plus-circle"></i> Nova Aposta Simples</h3>
                <button class="modal-close" onclick="closeModal('modalBetSimple')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" action="<?= $basePath ?>/apostas/simples">
                <div class="modal-body">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="mes_url" value="<?= $mes ?>">
                    <input type="hidden" name="ano_url" value="<?= $ano ?>">

                    <div class="form-group">
                        <label class="form-label">Descrição</label>
                        <input type="text" class="form-control" name="descricao" placeholder="Ex: Flamengo vence o Vasco" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label class="form-label">Categoria</label>
                            <select class="form-control" name="categoria_id">
                                <option value="">Selecione...</option>
                                <?php foreach ($categorias as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars((string) $c['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-6">
                            <label class="form-label">Data da Aposta</label>
                            <input type="date" class="form-control" name="data_aposta" value="<?= $todayDate ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label class="form-label">Odd de Entrada</label>
                            <input type="text" class="form-control odd-input" name="odd" id="simple_odd" placeholder="Ex: 1,85" required>
                        </div>
                        <div class="form-group col-6">
                            <label class="form-label">Valor Apostado</label>
                            <input type="text" class="form-control currency-input" name="valor_apostado" id="simple_valor" required>
                        </div>
                    </div>
                    <div class="bets-preview" id="simplePreview">Retorno estimado: <strong>R$ 0,00</strong></div>

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="status" onchange="toggleFechamento(this, 'simpleFechamentoWrap')">
                                <option value="pendente">Pendente</option>
                                <option value="vitoria">Vitória</option>
                                <option value="derrota">Derrota</option>
                                <option value="reembolso">Reembolso</option>
                            </select>
                        </div>
                        <div class="form-group col-6" id="simpleFechamentoWrap" style="display:none">
                            <label class="form-label">Valor de Fechamento</label>
                            <input type="text" class="form-control currency-input" name="valor_fechamento" id="simple_fechamento">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Observação</label>
                        <input type="text" class="form-control" name="observacao">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('modalBetSimple')">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================================================================
         MODAL: Aposta Múltipla
    ================================================================ -->
    <div class="modal-overlay" id="modalBetMultiple" style="display:none">
        <div class="modal-dialog" style="max-width:920px">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fa-solid fa-layer-group"></i> Nova Aposta Múltipla</h3>
                <button class="modal-close" onclick="closeModal('modalBetMultiple')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" action="<?= $basePath ?>/apostas/multipla" id="multipleBetForm">
                <div class="modal-body">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="mes_url" value="<?= $mes ?>">
                    <input type="hidden" name="ano_url" value="<?= $ano ?>">

                    <div class="form-group">
                        <label class="form-label">Descrição da Múltipla</label>
                        <input type="text" class="form-control" name="descricao" placeholder="Ex: Múltipla de domingo" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Seleções</label>
                        <div id="selectionsContainer"></div>
                        <button type="button" class="btn btn-ghost btn-sm mt-2" onclick="addSelectionRow()">
                            <i class="fa-solid fa-plus"></i> Adicionar Seleção
                        </button>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label class="form-label">Odd Principal (calculada, editável)</label>
                            <input type="text" class="form-control odd-input" name="odd" id="mult_odd_principal" data-auto="1" placeholder="Produto das odds">
                        </div>
                        <div class="form-group col-6">
                            <label class="form-label">Valor Apostado</label>
                            <input type="text" class="form-control currency-input" name="valor_apostado" id="mult_valor">
                        </div>
                    </div>
                    <div class="bets-preview" id="multPreview">Retorno estimado: <strong>R$ 0,00</strong></div>

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label class="form-label">Data da Aposta</label>
                            <input type="date" class="form-control" name="data_aposta" value="<?= $todayDate ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="status" onchange="toggleFechamento(this, 'multFechamentoWrap')">
                                <option value="pendente">Pendente</option>
                                <option value="vitoria">Vitória</option>
                                <option value="derrota">Derrota</option>
                                <option value="reembolso">Reembolso</option>
                            </select>
                        </div>
                        <div class="form-group col-6" id="multFechamentoWrap" style="display:none">
                            <label class="form-label">Valor de Fechamento</label>
                            <input type="text" class="form-control currency-input" name="valor_fechamento" id="mult_fechamento">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Observação</label>
                        <input type="text" class="form-control" name="observacao">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('modalBetMultiple')">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================================================================
         MODAL: Entrada / Saque na Banca
    ================================================================ -->
    <div class="modal-overlay" id="modalBank" style="display:none">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fa-solid fa-wallet"></i> Movimentar Banca</h3>
                <button class="modal-close" onclick="closeModal('modalBank')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" action="<?= $basePath ?>/apostas/banca">
                <div class="modal-body">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="mes_url" value="<?= $mes ?>">
                    <input type="hidden" name="ano_url" value="<?= $ano ?>">

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label class="form-label">Tipo</label>
                            <select class="form-control" name="tipo" required>
                                <option value="entrada">Entrada</option>
                                <option value="saque">Saque</option>
                            </select>
                        </div>
                        <div class="form-group col-6">
                            <label class="form-label">Valor</label>
                            <input type="text" class="form-control currency-input" name="valor" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label class="form-label">Data</label>
                            <input type="date" class="form-control" name="data" value="<?= $todayDate ?>" required>
                        </div>
                        <div class="form-group col-6">
                            <label class="form-label">Descrição</label>
                            <input type="text" class="form-control" name="descricao">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('modalBank')">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================================================================
         MODAL: Extrato por Período
    ================================================================ -->
    <div class="modal-overlay" id="modalStatement" style="display:none">
        <div class="modal-dialog modal-lg">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fa-solid fa-file-invoice"></i> Extrato por Período</h3>
                <button class="modal-close" onclick="closeModal('modalStatement')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <div class="filter-form">
                    <div class="filter-group">
                        <label class="filter-label">De</label>
                        <input type="date" class="form-control form-control-sm" id="stmt_inicio">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Até</label>
                        <input type="date" class="form-control form-control-sm" id="stmt_fim">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Status</label>
                        <select class="form-control form-control-sm" id="stmt_status">
                            <option value="">Todas</option>
                            <option value="vitoria">Vitórias</option>
                            <option value="derrota">Derrotas</option>
                            <option value="pendente">Pendentes</option>
                            <option value="reembolso">Reembolso</option>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button type="button" class="btn btn-primary btn-sm" onclick="loadStatement()">
                            <i class="fa-solid fa-magnifying-glass"></i> Buscar
                        </button>
                    </div>
                </div>

                <div id="stmtTotals" class="mt-3" style="display:none">
                    <div class="bets-kpi-grid" style="margin-bottom:0">
                        <div class="bets-kpi-card kpi-purple">
                            <div class="bets-kpi-icon neon-purple"><i class="fa-solid fa-coins"></i></div>
                            <div class="bets-kpi-value neon-purple" id="stmtApostado">-</div>
                            <div class="bets-kpi-label">Total Apostado</div>
                        </div>
                        <div class="bets-kpi-card kpi-green">
                            <div class="bets-kpi-icon neon-green"><i class="fa-solid fa-trophy"></i></div>
                            <div class="bets-kpi-value neon-green" id="stmtRetorno">-</div>
                            <div class="bets-kpi-label">Retorno Líquido (<span id="stmtVitorias">0</span>)</div>
                        </div>
                        <div class="bets-kpi-card kpi-red">
                            <div class="bets-kpi-icon neon-red"><i class="fa-solid fa-skull-crossbones"></i></div>
                            <div class="bets-kpi-value neon-red" id="stmtPerda">-</div>
                            <div class="bets-kpi-label">Perda (<span id="stmtDerrotas">0</span>)</div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Data</th><th>Descrição</th><th>Odd</th>
                                <th class="text-right">Valor</th><th>Status</th><th class="text-right">Resultado</th>
                            </tr>
                        </thead>
                        <tbody id="stmtBody">
                            <tr><td colspan="6" class="text-center text-muted py-4">Selecione um período e clique em Buscar.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modalStatement')">Fechar</button>
            </div>
        </div>
    </div>

    <!-- ================================================================
         MODAL: Detalhe do Dia
    ================================================================ -->
    <div class="modal-overlay" id="modalDay" style="display:none">
        <div class="modal-dialog modal-lg">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fa-solid fa-calendar-day"></i> Apostas do dia <span id="dayModalDate"></span></h3>
                <button class="modal-close" onclick="closeModal('modalDay')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <div class="filter-actions mb-2">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="filterDayRows('')">Todas</button>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="filterDayRows('vitoria')">Vitórias (<span id="dayVitoriasCount">0</span>)</button>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="filterDayRows('derrota')">Derrotas (<span id="dayDerrotasCount">0</span>)</button>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="filterDayRows('pendente')">Pendentes</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Descrição</th><th>Odd</th>
                                <th class="text-right">Valor</th><th>Status</th><th class="text-right">Resultado</th><th></th>
                            </tr>
                        </thead>
                        <tbody id="dayModalBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modalDay')">Fechar</button>
            </div>
        </div>
    </div>

    <!-- ================================================================
         MODAL: Nova Possível Entrada
    ================================================================ -->
    <div class="modal-overlay" id="modalProspect" style="display:none">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fa-solid fa-magnifying-glass-chart"></i> Nova Possível Entrada</h3>
                <button class="modal-close" onclick="closeModal('modalProspect')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" action="<?= $basePath ?>/apostas/prospectos">
                <div class="modal-body">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="mes_url" value="<?= $mes ?>">
                    <input type="hidden" name="ano_url" value="<?= $ano ?>">

                    <div class="form-group">
                        <label class="form-label">Descrição</label>
                        <input type="text" class="form-control" name="descricao" placeholder="Ex: Real Madrid vence o Barcelona" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label class="form-label">Categoria</label>
                            <select class="form-control" name="categoria_id">
                                <option value="">Selecione...</option>
                                <?php foreach ($categorias as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars((string) $c['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-6">
                            <label class="form-label">Odd</label>
                            <input type="text" class="form-control odd-input" name="odd" placeholder="Ex: 1,85" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Data e Hora</label>
                        <input type="datetime-local" class="form-control" name="data_hora" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('modalProspect')">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================================================================
         MODAL: Compartilhar
    ================================================================ -->
    <div class="modal-overlay" id="modalShare" style="display:none">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fa-solid fa-share-nodes"></i> Compartilhar Painel</h3>
                <button class="modal-close" onclick="closeModal('modalShare')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <?php if ($shareUrl): ?>
                <p class="text-sm text-muted">Qualquer pessoa com este link pode visualizar (somente leitura) seu painel de apostas, sem precisar de login.</p>
                <div class="form-group">
                    <input type="text" class="form-control" id="shareUrlInput" value="<?= htmlspecialchars($shareUrl) ?>" readonly>
                </div>
                <button type="button" class="btn btn-ghost btn-sm" onclick="copyShareLink()"><i class="fa-solid fa-copy"></i> Copiar Link</button>
                <?php else: ?>
                <p class="text-sm text-muted">Nenhum link ativo. Gere um link público para compartilhar seu painel (somente leitura).</p>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <form method="POST" action="<?= $basePath ?>/apostas/link/gerar">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-rotate"></i> Gerar Novo Link</button>
                </form>
                <?php if ($shareUrl): ?>
                <form method="POST" action="<?= $basePath ?>/apostas/link/revogar">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-ban"></i> Revogar</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div><!-- /.bets-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="<?= $basePath ?>/js/masks.js"></script>
<script src="<?= $basePath ?>/js/app.js"></script>
<script>
const APOSTAS_BASE = '<?= $basePath ?>';
const APOSTAS_CSRF = '<?= htmlspecialchars($csrf) ?>';
const APOSTAS_STATUS_LABELS = {
    pendente:  ['Pendente',  'badge-warning'],
    vitoria:   ['Vitória',   'badge-success'],
    derrota:   ['Derrota',   'badge-danger'],
    reembolso: ['Reembolso', 'badge-info'],
};
const APOSTAS_CATEGORIAS = <?= json_encode(array_map(fn($c) => ['id' => (int) $c['id'], 'nome' => $c['nome']], $categorias), JSON_UNESCAPED_UNICODE) ?>;

function fmtMoneyJs(v) {
    const n = Number(v) || 0;
    return 'R$ ' + Math.abs(n).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
function fmtOddJs(v) { return Number(v).toFixed(2).replace('.', ','); }

// ------------------------------------------------------------
// Máscara de odd (aceita dígitos e vírgula, até 4 casas inteiras e 2 decimais)
// ------------------------------------------------------------
function oddMask(input) {
    let v = input.value.replace(/\./g, ',');
    const parts = v.split(',');
    const intPart = parts[0].replace(/\D/g, '').slice(0, 4);
    if (parts.length > 1) {
        const decPart = parts.slice(1).join('').replace(/\D/g, '').slice(0, 2);
        input.value = intPart + ',' + decPart;
    } else {
        input.value = intPart;
    }
}
function parseOdd(v) {
    if (!v) return 0;
    return parseFloat(v.replace(',', '.')) || 0;
}

document.querySelectorAll('.odd-input').forEach(function (inp) {
    inp.addEventListener('input', function () { oddMask(this); });
});

// ------------------------------------------------------------
// Cortina de ações
// ------------------------------------------------------------
function toggleCurtain() {
    document.getElementById('curtainPanel').classList.toggle('open');
    document.getElementById('curtainToggle').classList.toggle('open');
}

// Valor de fechamento pré-calculado conforme o status escolhido:
// - vitória:   retorno bruto = valor apostado x odd de entrada (valor + lucro)
// - derrota:   perda negativa = -valor apostado (será descontada do saldo)
// - reembolso: devolve exatamente o valor apostado
function valorFechamentoPorStatus(status, valor, odd) {
    switch (status) {
        case 'vitoria':   return valor * odd;
        case 'derrota':   return -valor;
        case 'reembolso': return valor;
        default:          return 0;
    }
}

function toggleFechamento(select, wrapId) {
    const wrap = document.getElementById(wrapId);
    const form = select.closest('form');
    const status = select.value;

    // O campo só é exibido quando o status possui resultado (não-pendente).
    wrap.style.display = status === 'pendente' ? 'none' : 'block';

    const fechEl = wrap.querySelector('input[name="valor_fechamento"]');
    if (!fechEl || !form) return;

    const valorEl = form.querySelector('[name="valor_apostado"]');
    const oddEl   = form.querySelector('[name="odd"]');
    const valor   = valorEl ? parseCurrency(valorEl.value) : 0;
    const odd     = oddEl ? parseOdd(oddEl.value) : 0;

    // Em "Derrota" o valor é calculado (-valor apostado) e não pode ser editado.
    fechEl.readOnly = status === 'derrota';

    // O status escolhido comanda o preenchimento automático do valor.
    fechEl.dataset.autofill = '1';
    const valorFechamento = valorFechamentoPorStatus(status, valor, odd);
    fechEl.value = valorFechamento !== 0 ? valorFechamento.toFixed(2).replace('.', ',') : '';
}

// ------------------------------------------------------------
// Prévia de retorno (valor apostado x odd) — apenas demonstrativo
// ------------------------------------------------------------
function bindReturnPreview(valorId, oddId, previewId, fechamentoId) {
    const valorEl = document.getElementById(valorId);
    const oddEl = document.getElementById(oddId);
    const previewEl = document.getElementById(previewId);
    const fechEl = document.getElementById(fechamentoId);
    const form = fechEl.closest('form');
    const statusEl = form ? form.querySelector('select[name="status"]') : null;

    function recalc() {
        const valor = parseCurrency(valorEl.value);
        const odd = parseOdd(oddEl.value);
        const status = statusEl ? statusEl.value : 'pendente';

        // Retorno estimado = valor apostado x odd (retorno bruto, já somado ao valor apostado).
        previewEl.innerHTML = 'Retorno estimado (valor + lucro): <strong>' + fmtMoneyJs(valor * odd) + '</strong>';

        // Preenche o valor de fechamento conforme o status (quando o usuário
        // ainda não editou o campo manualmente).
        if (fechEl.dataset.autofill !== '0') {
            const valorFechamento = valorFechamentoPorStatus(status, valor, odd);
            fechEl.value = valorFechamento !== 0 ? valorFechamento.toFixed(2).replace('.', ',') : '';
        }
    }

    valorEl.addEventListener('input', recalc);
    oddEl.addEventListener('input', recalc);
    fechEl.addEventListener('input', function () { fechEl.dataset.autofill = '0'; });
}

bindReturnPreview('simple_valor', 'simple_odd', 'simplePreview', 'simple_fechamento');
bindReturnPreview('mult_valor', 'mult_odd_principal', 'multPreview', 'mult_fechamento');

// ------------------------------------------------------------
// Aposta múltipla: linhas de seleção dinâmicas
// ------------------------------------------------------------
let selectionRowIndex = 0;

function categoriaOptionsHtml() {
    let html = '<option value="">Categoria...</option>';
    APOSTAS_CATEGORIAS.forEach(c => {
        html += `<option value="${c.id}">${c.nome}</option>`;
    });
    return html;
}

function addSelectionRow() {
    const container = document.getElementById('selectionsContainer');
    const idx = selectionRowIndex++;
    const row = document.createElement('div');
    row.className = 'form-row selection-row';
    row.style.marginBottom = '8px';
    row.style.flexWrap = 'nowrap';
    row.style.gap = '8px';
    row.innerHTML = `
        <div class="form-group" style="flex:2 1 0">
            <input type="text" class="form-control" name="sel_descricao[]" placeholder="Descrição da seleção">
        </div>
        <div class="form-group" style="flex:1.4 1 0">
            <select class="form-control" name="sel_categoria_id[]">${categoriaOptionsHtml()}</select>
        </div>
        <div class="form-group" style="flex:0 0 85px">
            <input type="text" class="form-control odd-input sel-odd" name="sel_odd[]" placeholder="Odd">
        </div>
        <div class="form-group" style="flex:0 0 40px; display:flex; align-items:flex-end">
            <button type="button" class="action-btn action-btn-danger" onclick="removeSelectionRow(this)" style="width:100%"><i class="fa-solid fa-trash"></i></button>
        </div>
    `;
    container.appendChild(row);
    const oddInput = row.querySelector('.sel-odd');
    oddInput.addEventListener('input', function () { oddMask(this); recalcMultipleOdd(); });
}

function removeSelectionRow(btn) {
    btn.closest('.selection-row').remove();
    recalcMultipleOdd();
}

function recalcMultipleOdd() {
    const oddField = document.getElementById('mult_odd_principal');
    if (oddField.dataset.auto !== '1') return;

    let product = 1;
    document.querySelectorAll('.sel-odd').forEach(inp => {
        const v = parseOdd(inp.value || '1');
        if (v > 0) product *= v;
    });
    oddField.value = product.toFixed(2).replace('.', ',');
    oddField.dispatchEvent(new Event('input', { bubbles: false }));
}

document.getElementById('mult_odd_principal').addEventListener('input', function (e) {
    // Ignora eventos sintéticos disparados pelo próprio recálculo automático
    if (e.isTrusted) this.dataset.auto = '0';
});

// Start with two selection rows
addSelectionRow();
addSelectionRow();

// ------------------------------------------------------------
// Modal do dia
// ------------------------------------------------------------
let currentDayRecords = [];

function openDayModal(date) {
    document.getElementById('dayModalDate').textContent = date.split('-').reverse().join('/');
    document.getElementById('dayModalBody').innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Carregando...</td></tr>';
    openModal('modalDay');

    fetch(`${APOSTAS_BASE}/apostas/dia/${date}`)
        .then(r => r.json())
        .then(data => {
            currentDayRecords = data.records || [];
            renderDayRows(currentDayRecords);
            document.getElementById('dayVitoriasCount').textContent = currentDayRecords.filter(r => r.status === 'vitoria').length;
            document.getElementById('dayDerrotasCount').textContent = currentDayRecords.filter(r => r.status === 'derrota').length;
        });
}

function filterDayRows(status) {
    const filtered = status ? currentDayRecords.filter(r => r.status === status) : currentDayRecords;
    renderDayRows(filtered);
}

function renderDayRows(records) {
    const tbody = document.getElementById('dayModalBody');
    if (!records.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Nenhum registro.</td></tr>';
        return;
    }

    tbody.innerHTML = records.map(r => {
        const [label, cls] = APOSTAS_STATUS_LABELS[r.status] || ['-', 'badge-secondary'];
        const lucro = r.lucro !== null && r.lucro !== undefined ? Number(r.lucro) : null;
        const lucroHtml = lucro === null
            ? '<span class="text-muted">-</span>'
            : `<span class="${lucro >= 0 ? 'neon-green' : 'neon-red'}">${lucro >= 0 ? '+' : '-'} ${fmtMoneyJs(lucro)}</span>`;

        const finalizeBtn = r.status === 'pendente'
            ? `<button class="btn btn-ghost btn-sm" onclick="openQuickFinalize(${r.id})"><i class="fa-solid fa-flag-checkered"></i> Finalizar</button>`
            : `<button class="action-btn action-btn-danger" onclick="deleteBet(${r.id})" title="Excluir"><i class="fa-solid fa-trash"></i></button>`;

        return `<tr id="dayRow${r.id}">
            <td><span class="bets-cell-desc">${r.descricao}</span>${r.tipo === 'multipla' ? ` <span class="badge badge-secondary bets-mult-badge" onclick="toggleMultipleSelections(${r.id})" title="Ver seleções da múltipla"><i class="fa-solid fa-layer-group"></i> Múltipla</span>` : ''}${r.categoria_nome ? `<span class="bets-cell-cat">${r.categoria_nome}</span>` : ''}</td>
            <td>${fmtOddJs(r.odd)}</td>
            <td class="text-right">${fmtMoneyJs(r.valor_apostado)}</td>
            <td><span class="badge ${cls}">${label}</span></td>
            <td class="text-right">${lucroHtml}</td>
            <td class="text-right" id="dayActions${r.id}">${finalizeBtn}</td>
        </tr>`;
    }).join('');
}

// Múltipla: clicar no sinalizador lista as seleções agrupadas (descrição, uma por linha)
function toggleMultipleSelections(betId) {
    const existing = document.getElementById(`multSelRow${betId}`);
    if (existing) {
        existing.remove();
        return;
    }

    const record = currentDayRecords.find(r => r.id === betId);
    const selecoes = record && Array.isArray(record.selecoes) ? record.selecoes : [];
    const items = selecoes.length
        ? selecoes.map(s => `<li>${s.descricao}</li>`).join('')
        : '<li class="text-muted">Nenhuma seleção registrada.</li>';

    const tr = document.createElement('tr');
    tr.id = `multSelRow${betId}`;
    tr.className = 'bets-mult-sel-row';
    tr.innerHTML = `<td colspan="6">
        <div class="bets-mult-sel">
            <div class="bets-mult-sel-title"><i class="fa-solid fa-layer-group"></i> Seleções da múltipla</div>
            <ul>${items}</ul>
        </div>
    </td>`;

    const row = document.getElementById(`dayRow${betId}`);
    if (row) row.after(tr);
}

function openQuickFinalize(betId) {
    const cell = document.getElementById(`dayActions${betId}`);
    cell.innerHTML = `
        <div style="display:flex; gap:4px; justify-content:flex-end">
            <select class="form-control form-control-sm" id="fin_status_${betId}" style="width:110px" onchange="updateQuickFinalizeValor(${betId})">
                <option value="vitoria">Vitória</option>
                <option value="derrota">Derrota</option>
                <option value="reembolso">Reembolso</option>
            </select>
            <input type="text" class="form-control form-control-sm currency-input" id="fin_valor_${betId}"
                   placeholder="Fechamento" style="width:100px" data-autofill="1"
                   oninput="this.dataset.autofill='0'">
            <button class="action-btn action-btn-success" onclick="submitQuickFinalize(${betId})" title="Confirmar"><i class="fa-solid fa-check"></i></button>
        </div>
    `;
    updateQuickFinalizeValor(betId);
}

// Preenche o valor de fechamento conforme o status (padrão idêntico ao modal de aposta):
// - vitória:   valor apostado x odd (retorno bruto, somado ao valor apostado)
// - derrota:   -valor apostado (somente leitura)
// - reembolso: devolve exatamente o valor apostado
function updateQuickFinalizeValor(betId) {
    const input = document.getElementById(`fin_valor_${betId}`);
    if (!input || input.dataset.autofill === '0') return;

    const record = currentDayRecords.find(r => r.id === betId);
    const status = document.getElementById(`fin_status_${betId}`).value;
    const valor  = record ? Number(record.valor_apostado) : 0;
    const odd    = record ? Number(record.odd) : 0;

    const fechamento = valorFechamentoPorStatus(status, valor, odd);
    input.value = fechamento !== 0 ? fechamento.toFixed(2).replace('.', ',') : '';
    input.readOnly = status === 'derrota';
}

function submitQuickFinalize(betId) {
    const status = document.getElementById(`fin_status_${betId}`).value;
    const valor = document.getElementById(`fin_valor_${betId}`).value;

    const body = new URLSearchParams();
    body.append('_csrf', APOSTAS_CSRF);
    body.append('status', status);
    body.append('valor_fechamento', valor);

    fetch(`${APOSTAS_BASE}/apostas/${betId}/finalizar`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': APOSTAS_CSRF },
        body: body.toString(),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Erro ao finalizar aposta.');
        }
    });
}

function deleteBet(betId) {
    if (!confirm('Deseja realmente excluir esta aposta?')) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `${APOSTAS_BASE}/apostas/${betId}/excluir`;
    form.innerHTML = `<input type="hidden" name="_csrf" value="${APOSTAS_CSRF}">`;
    document.body.appendChild(form);
    form.submit();
}

// ------------------------------------------------------------
// Extrato por período
// ------------------------------------------------------------
function loadStatement() {
    const inicio = document.getElementById('stmt_inicio').value;
    const fim = document.getElementById('stmt_fim').value;
    const status = document.getElementById('stmt_status').value;

    if (!inicio || !fim) {
        alert('Selecione o período completo.');
        return;
    }

    const params = new URLSearchParams({ data_inicio: inicio, data_fim: fim, status });

    fetch(`${APOSTAS_BASE}/apostas/extrato?${params.toString()}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('stmtTotals').style.display = 'block';
            document.getElementById('stmtApostado').textContent = fmtMoneyJs(data.total_apostado);
            document.getElementById('stmtRetorno').textContent = fmtMoneyJs(data.retorno_liquido);
            document.getElementById('stmtPerda').textContent = fmtMoneyJs(data.total_perda);
            document.getElementById('stmtVitorias').textContent = data.qtd_vitorias;
            document.getElementById('stmtDerrotas').textContent = data.qtd_derrotas;

            const tbody = document.getElementById('stmtBody');
            if (!data.records || !data.records.length) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Nenhum registro no período.</td></tr>';
                return;
            }

            tbody.innerHTML = data.records.map(r => {
                const [label, cls] = APOSTAS_STATUS_LABELS[r.status] || ['-', 'badge-secondary'];
                const lucro = r.lucro !== null && r.lucro !== undefined ? Number(r.lucro) : null;
                const lucroHtml = lucro === null
                    ? '<span class="text-muted">-</span>'
                    : `<span class="${lucro >= 0 ? 'neon-green' : 'neon-red'}">${lucro >= 0 ? '+' : '-'} ${fmtMoneyJs(lucro)}</span>`;
                return `<tr>
                    <td>${r.data_aposta.split('-').reverse().join('/')}</td>
                    <td><span class="bets-cell-desc">${r.descricao}</span>${r.categoria_nome ? `<span class="bets-cell-cat">${r.categoria_nome}</span>` : ''}</td>
                    <td>${fmtOddJs(r.odd)}</td>
                    <td class="text-right">${fmtMoneyJs(r.valor_apostado)}</td>
                    <td><span class="badge ${cls}">${label}</span></td>
                    <td class="text-right">${lucroHtml}</td>
                </tr>`;
            }).join('');
        });
}

function copyShareLink() {
    const input = document.getElementById('shareUrlInput');
    input.select();
    navigator.clipboard.writeText(input.value).then(() => {
        alert('Link copiado para a área de transferência!');
    });
}

// ------------------------------------------------------------
// Possíveis entradas
// ------------------------------------------------------------
function updateProspectCombo() {
    const checked = Array.from(document.querySelectorAll('.prospect-check:checked'));
    let product = 1;
    checked.forEach(cb => { product *= parseFloat(cb.dataset.odd) || 1; });
    document.getElementById('prospectComboCount').textContent = checked.length;
    document.getElementById('prospectComboOdd').textContent = checked.length ? fmtOddJs(product) : '-';
}

function deleteProspect(id) {
    if (!confirm('Deseja realmente excluir esta possível entrada?')) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `${APOSTAS_BASE}/apostas/prospectos/${id}/excluir`;
    form.innerHTML = `<input type="hidden" name="_csrf" value="${APOSTAS_CSRF}">`;
    document.body.appendChild(form);
    form.submit();
}

function setHiddenField(form, name, value) {
    let field = form.querySelector(`[name="${name}"]`);
    if (!field) {
        field = document.createElement('input');
        field.type = 'hidden';
        field.name = name;
        form.appendChild(field);
    }
    field.value = value;
}

function quickRegisterFromProspects() {
    const checked = Array.from(document.querySelectorAll('.prospect-check:checked'));
    const ids = checked.map(cb => cb.dataset.id).join(',');

    if (checked.length <= 1) {
        openModal('modalBetSimple');
        const form = document.getElementById('modalBetSimple').querySelector('form');
        setHiddenField(form, 'prospect_ids', ids);
        if (checked.length === 1) {
            const cb = checked[0];
            form.querySelector('[name="descricao"]').value = cb.dataset.descricao;
            if (cb.dataset.categoriaId && cb.dataset.categoriaId !== '0') {
                form.querySelector('[name="categoria_id"]').value = cb.dataset.categoriaId;
            }
            const oddInput = document.getElementById('simple_odd');
            oddInput.value = cb.dataset.odd.replace('.', ',');
            oddInput.dispatchEvent(new Event('input'));
        }
        return;
    }

    openModal('modalBetMultiple');
    const multForm = document.getElementById('multipleBetForm');
    setHiddenField(multForm, 'prospect_ids', ids);
    multForm.querySelector('[name="descricao"]').value = `Múltipla — ${checked.length} seleções`;
    document.getElementById('selectionsContainer').innerHTML = '';

    checked.forEach(cb => {
        addSelectionRow();
        const rows = document.querySelectorAll('#selectionsContainer .selection-row');
        const row = rows[rows.length - 1];
        row.querySelector('[name="sel_descricao[]"]').value = cb.dataset.descricao;
        if (cb.dataset.categoriaId && cb.dataset.categoriaId !== '0') {
            row.querySelector('[name="sel_categoria_id[]"]').value = cb.dataset.categoriaId;
        }
        row.querySelector('.sel-odd').value = cb.dataset.odd.replace('.', ',');
    });

    document.getElementById('mult_odd_principal').dataset.auto = '1';
    recalcMultipleOdd();
}

// ------------------------------------------------------------
// Gráficos
// ------------------------------------------------------------
const DAILY_CHART_DATA = <?= json_encode(array_map(fn($d) => [
    'data'  => date('d/m', strtotime((string) $d['data'])),
    'saldo' => round((float) $d['saldo'], 2),
], array_reverse($daily))) ?>;

const MONTHLY_CHART_DATA = <?= json_encode(array_map(fn($m) => [
    'label'    => $meses[$m['mes']] . '/' . substr((string) $m['ano'], -2),
    'entradas' => round($m['entradas'], 2),
    'saques'   => round($m['saques'], 2),
    'saldo'    => round($m['saldo'], 2),
], $monthlyBankTotals)) ?>;

if (typeof Chart !== 'undefined') {
    const chartTextColor = '#8b8b9b';
    Chart.defaults.color = chartTextColor;
    Chart.defaults.borderColor = 'rgba(255,255,255,.08)';

    const dailyCanvas = document.getElementById('chartDailyEvolution');
    if (dailyCanvas && DAILY_CHART_DATA.length) {
        new Chart(dailyCanvas, {
            type: 'line',
            data: {
                labels: DAILY_CHART_DATA.map(d => d.data),
                datasets: [{
                    label: 'Saldo final',
                    data: DAILY_CHART_DATA.map(d => d.saldo),
                    borderColor: '#a78bfa',
                    backgroundColor: 'rgba(139,92,246,.18)',
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#a78bfa',
                }],
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { ticks: { callback: v => fmtMoneyJs(v) } },
                },
            },
        });
    }

    const monthlyCanvas = document.getElementById('chartMonthlyComparison');
    if (monthlyCanvas) {
        new Chart(monthlyCanvas, {
            type: 'bar',
            data: {
                labels: MONTHLY_CHART_DATA.map(m => m.label),
                datasets: [
                    { label: 'Depósitos', data: MONTHLY_CHART_DATA.map(m => m.entradas), backgroundColor: '#39ff88' },
                    { label: 'Saques', data: MONTHLY_CHART_DATA.map(m => m.saques), backgroundColor: '#ff2e63' },
                    { label: 'Saldo', data: MONTHLY_CHART_DATA.map(m => m.saldo), backgroundColor: '#38bdf8' },
                ],
            },
            options: {
                plugins: { legend: { labels: { color: chartTextColor } } },
                scales: {
                    y: { ticks: { callback: v => fmtMoneyJs(v) } },
                },
            },
        });
    }
}
</script>
</body>
</html>

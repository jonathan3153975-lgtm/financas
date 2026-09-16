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

function fmtBetMoney(float $v): string {
    return 'R$ ' . number_format(abs($v), 2, ',', '.');
}
function fmtBetOdd(float $v): string {
    return number_format($v, 2, ',', '.');
}

$shareUrl = $shareLink ? ($basePath . '/apostas/compartilhado/' . $shareLink['token']) : '';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Gerenciamento de Apostas</h1>
        <p class="page-subtitle">Controle da sua banca, apostas simples e múltiplas</p>
    </div>
    <form method="GET" action="<?= $basePath ?>/apostas" class="period-form">
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

<!-- ================================================================
     KPI Cards
================================================================ -->
<div class="cards-grid" style="grid-template-columns:repeat(5,1fr); gap:16px">

    <div class="summary-card">
        <div class="summary-card-header">
            <div class="summary-card-icon"><i class="fa-solid fa-coins"></i></div>
        </div>
        <div class="summary-card-value"><?= fmtBetMoney($kpis['total_apostado']) ?></div>
        <div class="summary-card-label">Total Apostado</div>
        <div class="summary-card-footer"><?= $meses[$mes] ?> / <?= $ano ?></div>
    </div>

    <div class="summary-card card-income">
        <div class="summary-card-header">
            <div class="summary-card-icon"><i class="fa-solid fa-trophy"></i></div>
        </div>
        <div class="summary-card-value"><?= fmtBetMoney($kpis['retorno_liquido']) ?></div>
        <div class="summary-card-label">Retorno Líquido Total</div>
        <div class="summary-card-footer"><?= $kpis['qtd_vitorias'] ?> vitória(s)</div>
    </div>

    <div class="summary-card card-balance-pos">
        <div class="summary-card-header">
            <div class="summary-card-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
        </div>
        <div class="summary-card-value"><?= fmtBetMoney($kpis['media_retorno']) ?></div>
        <div class="summary-card-label">Média Retorno / Aposta</div>
        <div class="summary-card-footer">Por aposta vencedora</div>
    </div>

    <div class="summary-card card-expense">
        <div class="summary-card-header">
            <div class="summary-card-icon"><i class="fa-solid fa-skull-crossbones"></i></div>
        </div>
        <div class="summary-card-value"><?= fmtBetMoney($kpis['total_perda']) ?></div>
        <div class="summary-card-label">Total de Perda</div>
        <div class="summary-card-footer"><?= $kpis['qtd_derrotas'] ?> derrota(s)</div>
    </div>

    <div class="summary-card card-balance-neg">
        <div class="summary-card-header">
            <div class="summary-card-icon"><i class="fa-solid fa-arrow-trend-down"></i></div>
        </div>
        <div class="summary-card-value"><?= fmtBetMoney($kpis['media_perda']) ?></div>
        <div class="summary-card-label">Média Perda / Aposta</div>
        <div class="summary-card-footer">Por aposta perdida</div>
    </div>

</div>

<!-- ================================================================
     Banca + Ações rápidas
================================================================ -->
<div class="dashboard-bottom" style="margin-top:16px">

    <div class="card" style="max-width:280px">
        <div class="card-body">
            <div class="summary-card-label">Saldo da Banca</div>
            <div class="summary-card-value <?= $saldoBanca < 0 ? 'text-danger' : 'text-success' ?>">
                <?= $saldoBanca < 0 ? '- ' : '' ?><?= fmtBetMoney($saldoBanca) ?>
            </div>
            <div class="text-xs text-muted mt-2">
                <?= $kpis['qtd_pendentes'] ?> pendente(s) · <?= $kpis['qtd_reembolsos'] ?> reembolso(s)
            </div>
        </div>
    </div>

    <div class="card flex-grow">
        <div class="card-body" style="display:flex; flex-wrap:wrap; gap:10px">
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

</div>

<!-- ================================================================
     Totais dia a dia
================================================================ -->
<div class="card" style="margin-top:16px">
    <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-calendar-days"></i> Totais Diários</h3>
        <span class="card-subtitle"><?= $meses[$mes] ?> / <?= $ano ?></span>
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
                        <th class="text-right">Saldo do Dia</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daily as $d): ?>
                    <tr>
                        <td class="font-medium"><?= date('d/m/Y', strtotime((string) $d['data'])) ?></td>
                        <td class="text-center"><?= (int) $d['qtd'] ?></td>
                        <td class="text-center"><span class="badge badge-success"><?= (int) $d['vitorias'] ?></span></td>
                        <td class="text-center"><span class="badge badge-danger"><?= (int) $d['derrotas'] ?></span></td>
                        <td class="text-right"><?= fmtBetMoney((float) $d['total_apostado']) ?></td>
                        <td class="text-right font-semibold <?= (float) $d['saldo'] >= 0 ? 'text-success' : 'text-danger' ?>">
                            <?= (float) $d['saldo'] >= 0 ? '+' : '-' ?> <?= fmtBetMoney((float) $d['saldo']) ?>
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
                        <input type="text" class="form-control" name="odd" placeholder="Ex: 1,85" required>
                    </div>
                    <div class="form-group col-6">
                        <label class="form-label">Valor Apostado</label>
                        <input type="text" class="form-control currency-input" name="valor_apostado" required>
                    </div>
                </div>

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
                        <label class="form-label">Valor de Fechamento (opcional)</label>
                        <input type="text" class="form-control currency-input" name="valor_fechamento">
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
    <div class="modal-dialog modal-lg">
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
                        <input type="text" class="form-control" name="odd" id="mult_odd_principal" data-auto="1" placeholder="Produto das odds">
                    </div>
                    <div class="form-group col-6">
                        <label class="form-label">Valor Apostado</label>
                        <input type="text" class="form-control currency-input" name="valor_apostado" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label class="form-label">Data da Aposta</label>
                        <input type="date" class="form-control" name="data_aposta" value="<?= $todayDate ?>" required>
                    </div>
                    <div class="form-group col-6">
                        <label class="form-label">Status</label>
                        <select class="form-control" name="status" onchange="toggleFechamento(this, 'multFechamentoWrap')">
                            <option value="pendente">Pendente</option>
                            <option value="vitoria">Vitória</option>
                            <option value="derrota">Derrota</option>
                            <option value="reembolso">Reembolso</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6" id="multFechamentoWrap" style="display:none">
                        <label class="form-label">Valor de Fechamento (opcional)</label>
                        <input type="text" class="form-control currency-input" name="valor_fechamento">
                    </div>
                    <div class="form-group col-6">
                        <label class="form-label">Observação</label>
                        <input type="text" class="form-control" name="observacao">
                    </div>
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
            <div class="form-row">
                <div class="form-group col-4">
                    <label class="form-label">De</label>
                    <input type="date" class="form-control" id="stmt_inicio">
                </div>
                <div class="form-group col-4">
                    <label class="form-label">Até</label>
                    <input type="date" class="form-control" id="stmt_fim">
                </div>
                <div class="form-group col-4">
                    <label class="form-label">Status</label>
                    <select class="form-control" id="stmt_status">
                        <option value="">Todas</option>
                        <option value="vitoria">Vitórias</option>
                        <option value="derrota">Derrotas</option>
                        <option value="pendente">Pendentes</option>
                        <option value="reembolso">Reembolso</option>
                    </select>
                </div>
            </div>
            <button type="button" class="btn btn-primary btn-sm" onclick="loadStatement()">
                <i class="fa-solid fa-magnifying-glass"></i> Buscar
            </button>

            <div id="stmtTotals" class="mt-3" style="display:none">
                <div class="cards-grid" style="grid-template-columns:repeat(3,1fr); gap:10px">
                    <div class="summary-card"><div class="summary-card-value" id="stmtApostado">-</div><div class="summary-card-label">Total Apostado</div></div>
                    <div class="summary-card card-income"><div class="summary-card-value" id="stmtRetorno">-</div><div class="summary-card-label">Retorno Líquido (<span id="stmtVitorias">0</span>)</div></div>
                    <div class="summary-card card-expense"><div class="summary-card-value" id="stmtPerda">-</div><div class="summary-card-label">Perda (<span id="stmtDerrotas">0</span>)</div></div>
                </div>
            </div>

            <div class="table-responsive mt-3">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Data</th><th>Descrição</th><th>Categoria</th><th>Odd</th>
                            <th class="text-right">Valor</th><th>Status</th><th class="text-right">Resultado</th>
                        </tr>
                    </thead>
                    <tbody id="stmtBody">
                        <tr><td colspan="7" class="text-center text-muted py-4">Selecione um período e clique em Buscar.</td></tr>
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
                            <th>Descrição</th><th>Categoria</th><th>Odd</th>
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

<!-- Finalize form (hidden, submitted via fetch) -->
<form id="finalizeForm" style="display:none"></form>

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

function toggleFechamento(select, wrapId) {
    document.getElementById(wrapId).style.display = select.value === 'pendente' ? 'none' : 'block';
}

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
    row.innerHTML = `
        <div class="form-group" style="flex:0 0 42%">
            <input type="text" class="form-control" name="sel_descricao[]" placeholder="Descrição da seleção">
        </div>
        <div class="form-group" style="flex:0 0 32%">
            <select class="form-control" name="sel_categoria_id[]">${categoriaOptionsHtml()}</select>
        </div>
        <div class="form-group" style="flex:0 0 16%">
            <input type="text" class="form-control sel-odd" name="sel_odd[]" placeholder="Odd">
        </div>
        <div class="form-group" style="flex:0 0 6%; display:flex; align-items:flex-end">
            <button type="button" class="action-btn action-btn-danger" onclick="removeSelectionRow(this)"><i class="fa-solid fa-trash"></i></button>
        </div>
    `;
    container.appendChild(row);
    row.querySelector('.sel-odd').addEventListener('input', recalcMultipleOdd);
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
        const v = parseFloat((inp.value || '1').replace(',', '.'));
        if (!isNaN(v) && v > 0) product *= v;
    });
    oddField.value = product.toFixed(2).replace('.', ',');
}

document.getElementById('mult_odd_principal').addEventListener('input', function () {
    this.dataset.auto = '0';
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
    document.getElementById('dayModalBody').innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Carregando...</td></tr>';
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
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Nenhum registro.</td></tr>';
        return;
    }

    tbody.innerHTML = records.map(r => {
        const [label, cls] = APOSTAS_STATUS_LABELS[r.status] || ['-', 'badge-secondary'];
        const lucro = r.lucro !== null && r.lucro !== undefined ? Number(r.lucro) : null;
        const lucroHtml = lucro === null
            ? '<span class="text-muted">-</span>'
            : `<span class="${lucro >= 0 ? 'text-success' : 'text-danger'}">${lucro >= 0 ? '+' : '-'} ${fmtMoneyJs(lucro)}</span>`;

        const finalizeBtn = r.status === 'pendente'
            ? `<button class="btn btn-ghost btn-sm" onclick="openQuickFinalize(${r.id})"><i class="fa-solid fa-flag-checkered"></i> Finalizar</button>`
            : `<button class="action-btn action-btn-danger" onclick="deleteBet(${r.id})" title="Excluir"><i class="fa-solid fa-trash"></i></button>`;

        return `<tr id="dayRow${r.id}">
            <td>${r.descricao}${r.tipo === 'multipla' ? ' <span class="badge badge-secondary">Múltipla</span>' : ''}</td>
            <td>${r.categoria_nome || '-'}</td>
            <td>${fmtOddJs(r.odd)}</td>
            <td class="text-right">${fmtMoneyJs(r.valor_apostado)}</td>
            <td><span class="badge ${cls}">${label}</span></td>
            <td class="text-right">${lucroHtml}</td>
            <td class="text-right" id="dayActions${r.id}">${finalizeBtn}</td>
        </tr>`;
    }).join('');
}

function fmtOddJs(v) {
    return Number(v).toFixed(2).replace('.', ',');
}

function openQuickFinalize(betId) {
    const cell = document.getElementById(`dayActions${betId}`);
    cell.innerHTML = `
        <div style="display:flex; gap:4px; justify-content:flex-end">
            <select class="form-control form-control-sm" id="fin_status_${betId}" style="width:110px">
                <option value="vitoria">Vitória</option>
                <option value="derrota">Derrota</option>
                <option value="reembolso">Reembolso</option>
            </select>
            <input type="text" class="form-control form-control-sm currency-input" id="fin_valor_${betId}" placeholder="Fechamento" style="width:100px">
            <button class="action-btn action-btn-success" onclick="submitQuickFinalize(${betId})" title="Confirmar"><i class="fa-solid fa-check"></i></button>
        </div>
    `;
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
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Nenhum registro no período.</td></tr>';
                return;
            }

            tbody.innerHTML = data.records.map(r => {
                const [label, cls] = APOSTAS_STATUS_LABELS[r.status] || ['-', 'badge-secondary'];
                const lucro = r.lucro !== null && r.lucro !== undefined ? Number(r.lucro) : null;
                const lucroHtml = lucro === null
                    ? '<span class="text-muted">-</span>'
                    : `<span class="${lucro >= 0 ? 'text-success' : 'text-danger'}">${lucro >= 0 ? '+' : '-'} ${fmtMoneyJs(lucro)}</span>`;
                return `<tr>
                    <td>${r.data_aposta.split('-').reverse().join('/')}</td>
                    <td>${r.descricao}</td>
                    <td>${r.categoria_nome || '-'}</td>
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
</script>

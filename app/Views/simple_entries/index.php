<?php
$pageTitle = 'Módulo Simples - Entradas e Saídas';
$basePath  = defined('BASE_URL') ? BASE_URL : ($_ENV['APP_BASE_PATH'] ?? '');
$meses     = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
$todayDate = date('Y-m-d');

function fmtSimpleMoney(float $v): string {
    return 'R$ ' . number_format(abs($v), 2, ',', '.');
}
?>

<div class="card" style="margin-bottom:12px">
    <form method="GET" action="<?= $basePath ?>/modulo-simples" class="filter-form filter-open">
        <div class="filter-group">
            <label class="filter-label">Mês</label>
            <select name="mes" class="form-control form-control-sm" onchange="this.form.submit()">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $m === $mes ? 'selected' : '' ?>><?= $meses[$m] ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Ano</label>
            <select name="ano" class="form-control form-control-sm" onchange="this.form.submit()">
                <?php for ($y = (int) date('Y') - 4; $y <= (int) date('Y') + 2; $y++): ?>
                <option value="<?= $y ?>" <?= $y === $ano ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Tipo</label>
            <select name="tipo" class="form-control form-control-sm" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="entrada" <?= ($filters['tipo'] ?? '') === 'entrada' ? 'selected' : '' ?>>Entradas</option>
                <option value="saida" <?= ($filters['tipo'] ?? '') === 'saida' ? 'selected' : '' ?>>Saídas</option>
            </select>
        </div>
        <div class="filter-group filter-group-search">
            <label class="filter-label">Buscar</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Descrição ou observação"
                   value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-magnifying-glass"></i> Filtrar
            </button>
            <a href="<?= $basePath ?>/modulo-simples?mes=<?= $mes ?>&ano=<?= $ano ?>" class="btn btn-ghost btn-sm">Limpar</a>
        </div>
    </form>
</div>

<div class="card" id="simpleListCard">
    <div class="card-header">
        <h3 class="card-title">
            Registros
            <span class="badge badge-secondary ms-2"><?= $total ?></span>
        </h3>
    </div>

    <div class="card-body p-0">
        <?php if (empty($records)): ?>
        <div class="empty-state py-10">
            <i class="fa-solid fa-inbox"></i>
            <p>Nenhum registro no período selecionado.</p>
            <button class="btn btn-primary btn-sm mt-2" onclick="openModal('modalSimpleNew')">
                <i class="fa-solid fa-plus"></i> Novo Registro
            </button>
        </div>
        <?php else: ?>
        <div class="table-responsive hide-on-mobile">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Tipo</th>
                        <th class="text-right">Valor</th>
                        <th>Observação</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $r): ?>
                    <tr>
                        <td class="text-sm text-muted"><?= date('d/m/Y', strtotime((string) $r['data_referencia'])) ?></td>
                        <td class="font-medium"><?= htmlspecialchars((string) $r['descricao']) ?></td>
                        <td>
                            <?php if ($r['tipo'] === 'entrada'): ?>
                            <span class="badge badge-success">Entrada</span>
                            <?php else: ?>
                            <span class="badge badge-danger">Saída</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right font-semibold <?= $r['tipo'] === 'entrada' ? 'text-success' : 'text-danger' ?>">
                            <?= $r['tipo'] === 'entrada' ? '+' : '-' ?> <?= fmtSimpleMoney((float) $r['valor']) ?>
                        </td>
                        <td class="text-sm text-muted"><?= htmlspecialchars((string) ($r['observacao'] ?? '')) ?></td>
                        <td class="text-center">
                            <div class="action-buttons">
                                <button class="action-btn action-btn-primary" title="Editar"
                                        onclick="openSimpleEdit(this)"
                                        data-id="<?= (int) $r['id'] ?>"
                                        data-descricao="<?= htmlspecialchars((string) $r['descricao']) ?>"
                                        data-tipo="<?= htmlspecialchars((string) $r['tipo']) ?>"
                                        data-valor="<?= number_format((float) $r['valor'], 2, '.', '') ?>"
                                        data-data="<?= htmlspecialchars((string) $r['data_referencia']) ?>"
                                        data-observacao="<?= htmlspecialchars((string) ($r['observacao'] ?? '')) ?>">
                                    <i class="fa-solid fa-pencil"></i>
                                </button>
                                <button class="action-btn action-btn-danger" title="Excluir"
                                        onclick="deleteSimpleRecord(<?= (int) $r['id'] ?>)">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mov-card-list show-on-mobile">
            <?php foreach ($records as $r): ?>
            <div class="mov-card">
                <div class="mov-card-top">
                    <div>
                        <div class="mov-card-title"><?= htmlspecialchars((string) $r['descricao']) ?></div>
                        <div class="mov-card-sub">
                            <i class="fa-regular fa-calendar"></i> <?= date('d/m/Y', strtotime((string) $r['data_referencia'])) ?>
                        </div>
                    </div>
                    <div class="mov-card-amount <?= $r['tipo'] === 'entrada' ? 'text-success' : 'text-danger' ?>">
                        <?= $r['tipo'] === 'entrada' ? '+' : '-' ?><?= fmtSimpleMoney((float) $r['valor']) ?>
                    </div>
                </div>
                <div class="mov-card-meta">
                    <?php if ($r['tipo'] === 'entrada'): ?>
                    <span class="badge badge-success">Entrada</span>
                    <?php else: ?>
                    <span class="badge badge-danger">Saída</span>
                    <?php endif; ?>
                    <?php if (!empty($r['observacao'])): ?>
                    <span class="text-xs text-muted"><?= htmlspecialchars((string) $r['observacao']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="mov-card-actions">
                    <button class="mov-action-btn mov-action-primary"
                            onclick="openSimpleEdit(this)"
                            data-id="<?= (int) $r['id'] ?>"
                            data-descricao="<?= htmlspecialchars((string) $r['descricao']) ?>"
                            data-tipo="<?= htmlspecialchars((string) $r['tipo']) ?>"
                            data-valor="<?= number_format((float) $r['valor'], 2, '.', '') ?>"
                            data-data="<?= htmlspecialchars((string) $r['data_referencia']) ?>"
                            data-observacao="<?= htmlspecialchars((string) ($r['observacao'] ?? '')) ?>">
                        <i class="fa-solid fa-pencil"></i> Editar
                    </button>
                    <button class="mov-action-btn mov-action-danger"
                            onclick="deleteSimpleRecord(<?= (int) $r['id'] ?>)">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($pages > 1): ?>
        <div class="pagination hide-on-mobile">
            <?php for ($p = 1; $p <= $pages; $p++): ?>
            <a href="?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>"
               class="page-btn <?= $p === $currentPage ? 'active' : '' ?>">
                <?= $p ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="modal-overlay" id="modalSimpleNew" style="display:none">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fa-solid fa-plus-circle"></i> Novo Registro</h3>
            <button class="modal-close" onclick="closeModal('modalSimpleNew')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="<?= $basePath ?>/modulo-simples">
            <div class="modal-body">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                <div class="form-group">
                    <label class="form-label">Descrição</label>
                    <input type="text" class="form-control" name="descricao" required>
                </div>
                <div class="form-row">
                    <div class="form-group col-6">
                        <label class="form-label">Tipo</label>
                        <select class="form-control" name="tipo" required>
                            <option value="entrada">Entrada</option>
                            <option value="saida" selected>Saída</option>
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
                        <input type="date" class="form-control" name="data_referencia" value="<?= $todayDate ?>" required>
                    </div>
                    <div class="form-group col-6">
                        <label class="form-label">Observação</label>
                        <input type="text" class="form-control" name="observacao">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modalSimpleNew')">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modalSimpleEdit" style="display:none">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fa-solid fa-pencil"></i> Editar Registro</h3>
            <button class="modal-close" onclick="closeModal('modalSimpleEdit')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" id="simpleEditForm">
            <div class="modal-body">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                <div class="form-group">
                    <label class="form-label">Descrição</label>
                    <input type="text" class="form-control" id="se_descricao" name="descricao" required>
                </div>
                <div class="form-row">
                    <div class="form-group col-6">
                        <label class="form-label">Tipo</label>
                        <select class="form-control" id="se_tipo" name="tipo" required>
                            <option value="entrada">Entrada</option>
                            <option value="saida">Saída</option>
                        </select>
                    </div>
                    <div class="form-group col-6">
                        <label class="form-label">Valor</label>
                        <input type="text" class="form-control currency-input" id="se_valor" name="valor" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-6">
                        <label class="form-label">Data</label>
                        <input type="date" class="form-control" id="se_data" name="data_referencia" required>
                    </div>
                    <div class="form-group col-6">
                        <label class="form-label">Observação</label>
                        <input type="text" class="form-control" id="se_observacao" name="observacao">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modalSimpleEdit')">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> Atualizar
                </button>
            </div>
        </form>
    </div>
</div>

<form id="simpleDeleteForm" method="POST" style="display:none">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
</form>

<style>
#simpleFooterBar {
    position: fixed;
    bottom: 0;
    left: var(--sidebar-width, 260px);
    right: 0;
    z-index: 800;
    background: var(--sidebar-bg, #0f172a);
    color: #f8fafc;
    border-top: 2px solid var(--accent, #6366f1);
    box-shadow: 0 -4px 16px rgba(0,0,0,.3);
    padding: 0 20px;
    height: 52px;
    display: flex;
    align-items: center;
}
#simpleFooterBar .sf-inner {
    display: flex;
    align-items: center;
    gap: 0;
    width: 100%;
    height: 100%;
    overflow-x: auto;
}
.sf-group {
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 0 16px;
    border-right: 1px solid rgba(255,255,255,.1);
    min-width: 110px;
    flex-shrink: 0;
}
.sf-group:first-child { padding-left: 0; }
.sf-group.sf-sep-strong { border-left: 2px solid rgba(99,102,241,.6); }
.sf-label {
    font-size: .65rem;
    font-weight: 500;
    letter-spacing: .04em;
    text-transform: uppercase;
    opacity: .55;
    line-height: 1;
    margin-bottom: 2px;
}
.sf-value { font-size: .9rem; font-weight: 700; line-height: 1; }
.sf-spacer { flex: 1; }
.sf-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    padding-left: 12px;
    flex-shrink: 0;
}
.sf-btn {
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.18);
    color: #f8fafc;
    border-radius: 6px;
    padding: 4px 12px;
    cursor: pointer;
    font-size: .75rem;
    line-height: 1.6;
    transition: background .15s;
    white-space: nowrap;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.sf-btn:hover { background: rgba(255,255,255,.16); }
.sf-btn-accent { background: rgba(99,102,241,.22); border-color: rgba(99,102,241,.45); color: #a5b4fc; }
.sf-btn-accent:hover { background: rgba(99,102,241,.32); }

@media (max-width: 900px) {
    #simpleFooterBar { left: 0; padding: 0 10px; }
    .sf-group { min-width: 80px; padding: 0 10px; }
    .sf-label { font-size: .6rem; }
    .sf-value { font-size: .8rem; }
}
</style>

<div id="simpleFooterBar">
    <div class="sf-inner">
        <div class="sf-group">
            <span class="sf-label"><i class="fa-solid fa-arrow-trend-up" style="color:#34d399"></i> Entradas</span>
            <span class="sf-value" style="color:#34d399"><?= fmtSimpleMoney((float) $totalEntrada) ?></span>
        </div>
        <div class="sf-group">
            <span class="sf-label"><i class="fa-solid fa-arrow-trend-down" style="color:#f87171"></i> Saídas</span>
            <span class="sf-value" style="color:#f87171"><?= fmtSimpleMoney((float) $totalSaida) ?></span>
        </div>
        <div class="sf-group sf-sep-strong">
            <span class="sf-label">Saldo do Mês</span>
            <span class="sf-value" style="color:<?= (float) $saldoMes >= 0 ? '#34d399' : '#f87171' ?>">
                <?= ((float) $saldoMes < 0 ? '-' : '') . fmtSimpleMoney((float) $saldoMes) ?>
            </span>
        </div>
        <div class="sf-group" style="min-width:130px">
            <span class="sf-label" style="opacity:.45">Saldo Acumulado</span>
            <span class="sf-value" style="color:<?= (float) $saldoTotal >= 0 ? '#a5b4fc' : '#f87171' ?>;font-size:.82rem">
                <?= ((float) $saldoTotal < 0 ? '-' : '') . fmtSimpleMoney((float) $saldoTotal) ?>
            </span>
        </div>
        <div class="sf-spacer"></div>
        <div class="sf-actions">
            <a href="<?= $basePath ?>/modulo-simples/exportar/csv?<?= http_build_query(['mes' => $mes, 'ano' => $ano, 'tipo' => ($filters['tipo'] ?? ''), 'search' => ($filters['search'] ?? '')]) ?>" class="sf-btn">
                <i class="fa-solid fa-file-csv"></i> Exportar
            </a>
            <button class="sf-btn sf-btn-accent" onclick="openModal('modalSimpleNew')">
                <i class="fa-solid fa-plus"></i> Novo Registro
            </button>
        </div>
    </div>
</div>

<script>
const SIMPLE_BASE_PATH = '<?= $basePath ?>';
const SIMPLE_MES = <?= (int) $mes ?>;
const SIMPLE_ANO = <?= (int) $ano ?>;

function openSimpleEdit(btn) {
    const id = btn.dataset.id;
    const form = document.getElementById('simpleEditForm');
    form.action = `${SIMPLE_BASE_PATH}/modulo-simples/${id}`;

    document.getElementById('se_descricao').value = btn.dataset.descricao || '';
    document.getElementById('se_tipo').value = btn.dataset.tipo || 'saida';
    const rawValue = parseFloat(btn.dataset.valor || '0');
    document.getElementById('se_valor').value = rawValue.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
    document.getElementById('se_data').value = btn.dataset.data || '';
    document.getElementById('se_observacao').value = btn.dataset.observacao || '';

    openModal('modalSimpleEdit');
}

async function deleteSimpleRecord(id) {
    const result = await Swal.fire({
        title: 'Excluir registro?',
        text: 'Esta ação não pode ser desfeita.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    });

    if (!result.isConfirmed) return;

    const form = document.getElementById('simpleDeleteForm');
    form.action = `${SIMPLE_BASE_PATH}/modulo-simples/${id}/excluir`;
    form.submit();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.currency-input').forEach(input => {
        input.addEventListener('input', function () { currencyMask(this); });
    });

    const bar = document.getElementById('simpleFooterBar');
    if (bar) {
        const h = bar.offsetHeight || 52;
        document.querySelector('.page-content')?.style.setProperty('padding-bottom', (h + 8) + 'px');
    }

    const list = document.getElementById('simpleListCard') || document.body;
    let startX = 0;
    let startY = 0;

    list.addEventListener('touchstart', e => {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
    }, { passive: true });

    list.addEventListener('touchend', e => {
        const dx = e.changedTouches[0].clientX - startX;
        const dy = e.changedTouches[0].clientY - startY;
        if (Math.abs(dx) < 60 || Math.abs(dx) < Math.abs(dy) * 1.5) return;

        const params = new URLSearchParams(window.location.search);
        let mes = SIMPLE_MES + (dx < 0 ? 1 : -1);
        let ano = SIMPLE_ANO;
        if (mes > 12) { mes = 1; ano++; }
        if (mes < 1) { mes = 12; ano--; }
        params.set('mes', mes);
        params.set('ano', ano);
        params.delete('page');
        window.location.href = window.location.pathname + '?' + params.toString();
    }, { passive: true });
});
</script>

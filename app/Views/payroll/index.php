<?php
$pageTitle = 'Folha de Pagamento – JW Finanças';
$basePath  = defined('BASE_URL') ? BASE_URL : ($_ENV['APP_BASE_PATH'] ?? '');
$meses     = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
               'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
?>

<style>
/* ── Payroll item row ─────────────────────────────────────────── */
.payroll-item-row {
    display: block;
    margin-bottom: .6rem;
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: .75rem;
    background: var(--surface-1);
}

/* Generic flex row used inside each item */
.pir-row {
    display: flex;
    gap: .5rem;
    align-items: flex-end;
    width: 100%;
}
.pir-row + .pir-row {
    margin-top: .55rem;
    padding-top: .55rem;
    border-top: 1px dashed var(--border);
}

/* Column helpers */
.pir-col { display: flex; flex-direction: column; gap: .2rem; min-width: 0; }
.pir-col-desc  { flex: 1; }
.pir-col-tipo  { flex: 0 0 130px; }
.pir-col-base  { flex: 0 0 150px; }
.pir-col-qty   { flex: 0 0 100px; }
.pir-col-valor { flex: 1; }
.pir-col-label { font-size: .75rem; color: var(--text-muted); white-space: nowrap; }

/* Actions (IR label + delete) — no label above, align to bottom of inputs */
.pir-col-actions {
    display: flex;
    gap: .35rem;
    align-items: flex-end;
    flex-shrink: 0;
}

/* Operators × = */
.pir-op {
    flex-shrink: 0;
    font-weight: 700;
    color: var(--text-muted);
    padding-bottom: .5rem; /* visually center against inputs */
    align-self: flex-end;
}

/* IR checkbox label */
.pir-ir-label {
    display: inline-flex; align-items: center; gap: .3rem;
    white-space: nowrap; font-size: .8rem;
    padding: .4rem .55rem;
    border: 1px solid var(--border); border-radius: 4px;
    cursor: pointer; background: var(--surface-2);
    height: 38px; box-sizing: border-box;
}
.pir-ir-label input[type=checkbox] { accent-color: var(--primary); margin: 0; }

/* IR auto-row */
.pir-ir-auto { border-color: #f59e0b55; background: #fef9ec; }
.pir-ir-badge {
    font-size: .72rem; padding: .25rem .5rem;
    background: #fef3c7; color: #92400e;
    border: 1px solid #fcd34d; border-radius: 4px;
    white-space: nowrap; align-self: flex-end;
    height: 38px; box-sizing: border-box;
    display: flex; align-items: center; gap: .3rem;
}

/* Drag handle */
.pir-drag-handle {
    cursor: grab;
    color: var(--text-muted);
    padding-bottom: .5rem;
    align-self: flex-end;
    flex-shrink: 0;
    opacity: .5;
    touch-action: none;
}
.pir-drag-handle:active { cursor: grabbing; }
.payroll-item-row[draggable]:hover .pir-drag-handle { opacity: 1; }
.payroll-item-row.pir-dragging { opacity: .35; }
.payroll-item-row.pir-drag-over {
    border-color: var(--primary);
    border-style: dashed;
    background: color-mix(in srgb, var(--primary) 6%, transparent);
}

/* IRPF info paragraph */
.pir-info-text {
    font-size: .8rem; color: var(--text-muted);
    margin: .25rem .75rem .65rem;
    line-height: 1.5;
}

/* detail view badge */
.item-ir-badge {
    font-size: .68rem; padding: .1rem .3rem;
    background: var(--primary)22; color: var(--primary);
    border-radius: 3px; margin-left: .3rem;
}
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Folha de Pagamento</h1>
        <p class="page-subtitle">Histórico de salários e holerites</p>
    </div>
    <button class="btn btn-primary" onclick="resetPayrollForm()">
        <i class="fa-solid fa-plus"></i> Novo Registro
    </button>
</div>

<?php if (empty($payrolls)): ?>
<div class="empty-state card py-16">
    <i class="fa-solid fa-file-invoice-dollar fa-3x text-muted"></i>
    <h3 class="mt-4">Nenhum registro de folha</h3>
    <p class="text-muted">Registre seus contracheques para acompanhar sua evolução salarial.</p>
    <button class="btn btn-primary mt-4" onclick="resetPayrollForm()">
        <i class="fa-solid fa-plus"></i> Adicionar
    </button>
</div>
<?php else: ?>

<div class="payroll-list">
    <?php foreach ($payrolls as $p): ?>
    <?php
    $descontosArr = !empty($p['descontos']) ? json_decode($p['descontos'], true) : [];
    $totalDesc    = array_sum($descontosArr);
    ?>
    <div class="card payroll-card">
        <div class="payroll-card-header">
            <div class="payroll-card-period">
                <div class="payroll-month-badge">
                    <span class="month"><?= substr($meses[$p['mes_referencia']], 0, 3) ?></span>
                    <span class="year"><?= $p['ano_referencia'] ?></span>
                </div>
                <div>
                    <div class="payroll-title"><?= htmlspecialchars($p['descricao']) ?></div>
                    <?php if ($p['data_pagamento']): ?>
                    <div class="text-sm text-muted">
                        <i class="fa-solid fa-calendar-check"></i>
                        Pago em <?= date('d/m/Y', strtotime($p['data_pagamento'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="payroll-values">
                <div class="payroll-val">
                    <span class="payroll-val-label">Bruto</span>
                    <span class="payroll-val-amount text-success">R$ <?= number_format((float)$p['valor_bruto'], 2, ',', '.') ?></span>
                </div>
                <div class="payroll-val">
                    <span class="payroll-val-label">Descontos</span>
                    <span class="payroll-val-amount text-danger">- R$ <?= number_format($totalDesc, 2, ',', '.') ?></span>
                </div>
                <div class="payroll-val payroll-val-net">
                    <span class="payroll-val-label">Líquido</span>
                    <span class="payroll-val-amount text-primary">R$ <?= number_format((float)$p['valor_liquido'], 2, ',', '.') ?></span>
                </div>
            </div>
            <div class="payroll-actions">
                <button class="btn btn-ghost btn-sm" onclick="togglePayrollDetail('detail-<?= $p['id'] ?>')">
                    <i class="fa-solid fa-chevron-down"></i> Detalhes
                </button>
                <button type="button" class="btn btn-ghost btn-sm"
                        onclick="editPayroll(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)">
                    <i class="fa-solid fa-pencil"></i>
                </button>
                <button type="button" class="btn btn-ghost btn-sm text-danger"
                        onclick="deletePayroll(<?= $p['id'] ?>, '<?= htmlspecialchars($csrf) ?>')">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>

        <div class="payroll-detail" id="detail-<?= $p['id'] ?>" style="display:none">
            <div class="payroll-items-grid">
                <div class="payroll-items-col">
                    <h4 class="payroll-col-title text-success">
                        <i class="fa-solid fa-circle-plus"></i> Proventos
                    </h4>
                    <?php foreach ($p['itens'] as $item): ?>
                    <?php if ($item['tipo'] !== 'provento') continue; ?>
                    <div class="payroll-item">
                        <span>
                            <?= htmlspecialchars($item['descricao']) ?>
                            <?php if ($item['compoe_ir'] ?? 1): ?>
                            <span class="item-ir-badge" title="Compõe base de IR">IR</span>
                            <?php endif; ?>
                            <?php if (($item['valor_base'] ?? null) && ($item['quantidade'] ?? null)): ?>
                            <span class="text-muted" style="font-size:.78rem">
                                (<?= number_format((float)$item['valor_base'],2,',','.') ?> × <?= rtrim(rtrim(number_format((float)$item['quantidade'],2,',','.'),'0'),',') ?>)
                            </span>
                            <?php endif; ?>
                        </span>
                        <span class="text-success">R$ <?= number_format((float)$item['valor'], 2, ',', '.') ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="payroll-items-col">
                    <h4 class="payroll-col-title text-danger">
                        <i class="fa-solid fa-circle-minus"></i> Descontos
                    </h4>
                    <?php foreach ($p['itens'] as $item): ?>
                    <?php if ($item['tipo'] !== 'desconto') continue; ?>
                    <div class="payroll-item">
                        <span><?= htmlspecialchars($item['descricao']) ?></span>
                        <span class="text-danger">- R$ <?= number_format((float)$item['valor'], 2, ',', '.') ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if ($p['observacao']): ?>
            <div class="alert alert-info mt-4">
                <i class="fa-solid fa-sticky-note"></i>
                <?= htmlspecialchars($p['observacao']) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ================================================================
     MODAL - Nova / Editar Folha
================================================================ -->
<div class="modal-overlay" id="modalFolha" style="display:none">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title" id="modalFolhaTitle">
                <i class="fa-solid fa-file-invoice-dollar"></i> Novo Holerite / Folha
            </h3>
            <button class="modal-close" onclick="closeModal('modalFolha')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="<?= $basePath ?>/folha-pagamento" id="folhaForm">
            <div class="modal-body">
                <input type="hidden" name="_csrf"     value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="_folha_id" id="folhaIdHidden" value="">

                <div class="form-row">
                    <div class="form-group col-8">
                        <label class="form-label">Descrição</label>
                        <input type="text" class="form-control" name="descricao"
                               value="Folha de Pagamento" required>
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Data de Pagamento</label>
                        <input type="date" class="form-control" name="data_pagamento" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label class="form-label">Mês de Referência</label>
                        <select class="form-control" name="mes_referencia">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $m === (int)date('m') ? 'selected' : '' ?>><?= $meses[$m] ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group col-6">
                        <label class="form-label">Ano</label>
                        <input type="number" class="form-control" name="ano_referencia" value="<?= date('Y') ?>">
                    </div>
                </div>

                <!-- Dynamic items -->
                <div class="payroll-items-section">
                    <div class="payroll-section-header">
                        <h4>Itens da Folha</h4>
                        <button type="button" class="btn btn-ghost btn-sm" onclick="addPayrollItem()">
                            <i class="fa-solid fa-plus"></i> Adicionar Item
                        </button>
                    </div>
                    <p class="pir-info-text">
                        <i class="fa-solid fa-circle-info"></i>
                        <strong>Base R$</strong> × <strong>Qtd</strong> calculam o valor automaticamente (ex: hora extra).
                        Marque <strong>IR</strong> para incluir o item na base do Imposto de Renda.
                    </p>

                    <div id="payrollItems">
                        <!-- rows injected by JS -->
                    </div>
                </div>

                <!-- Summary -->
                <div class="payroll-summary-preview">
                    <div class="payroll-preview-row">
                        <span>Total Proventos:</span>
                        <span id="previewBruto" class="text-success font-bold">R$ 0,00</span>
                    </div>
                    <div class="payroll-preview-row">
                        <span>Total Descontos:</span>
                        <span id="previewDesconto" class="text-danger font-bold">R$ 0,00</span>
                    </div>
                    <div class="payroll-preview-row" id="irPreviewRow" style="display:none">
                        <span>
                            Base IR: <span id="irBaseLabel" class="text-muted" style="font-size:.8rem"></span>
                        </span>
                        <span id="previewIR" class="text-warning font-bold">R$ 0,00</span>
                    </div>
                    <div class="payroll-preview-row payroll-preview-net">
                        <span>Salário Líquido:</span>
                        <span id="previewLiquido" class="font-bold">R$ 0,00</span>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <label class="form-label">Observação</label>
                    <textarea class="form-control" name="observacao" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modalFolha')">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> Salvar Folha
                </button>
            </div>
        </form>
    </div>
</div>

<script>
/* ── IR progressive table (IRRF 2025) ── */
const IR_TABLE = [
    { limite: 2259.20, aliquota: 0,     deducao: 0 },
    { limite: 2826.65, aliquota: 0.075, deducao: 169.44 },
    { limite: 3751.05, aliquota: 0.150, deducao: 381.44 },
    { limite: 4664.68, aliquota: 0.225, deducao: 662.77 },
    { limite: Infinity,aliquota: 0.275, deducao: 896.00 },
];

function calcIR(base) {
    for (const f of IR_TABLE) {
        if (base <= f.limite) return Math.max(0, base * f.aliquota - f.deducao);
    }
    return 0;
}

function parseBrl(str) {
    return parseFloat((str || '0').replace(/\./g, '').replace(',', '.')) || 0;
}

function fmtBrl(v) {
    return 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/* ── Recalculate totals and manage IR auto-row ── */
function recalcPayroll() {
    const rows    = document.querySelectorAll('#payrollItems .payroll-item-row');
    let bruto     = 0, desconto = 0, baseIR = 0;

    rows.forEach(row => {
        if (row.dataset.irRow) return; // skip IR row in main loop

        const tipo      = row.querySelector('.pir-tipo')?.value || 'provento';
        const val       = parseBrl(row.querySelector('.pir-valor')?.value);
        const compoeIr  = row.querySelector('.pir-ir-hidden')?.value === '1';

        if (tipo === 'provento') {
            bruto += val;
            if (compoeIr) baseIR += val;
        } else {
            desconto += val;
            if (compoeIr) baseIR -= val; // desconto reduz base IR (ex: INSS)
        }
    });

    baseIR = Math.max(0, baseIR);
    const irCalc = Math.round(calcIR(baseIR) * 100) / 100;

    // Upsert IR auto-row
    const existingIrRow = document.querySelector('#payrollItems .payroll-item-row[data-ir-row]');
    if (irCalc > 0) {
        if (!existingIrRow) {
            document.getElementById('payrollItems').appendChild(buildIrRow(irCalc));
        } else {
            const inp = existingIrRow.querySelector('.pir-valor');
            if (inp && document.activeElement !== inp) {
                inp.value = irCalc.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }
        // Re-read actual IR row value (user may have edited it)
        const irRowVal = parseBrl(document.querySelector('#payrollItems .payroll-item-row[data-ir-row] .pir-valor')?.value);
        desconto += irRowVal;
    } else if (existingIrRow) {
        existingIrRow.remove();
    }

    const liquido = bruto - desconto;

    document.getElementById('previewBruto').textContent   = fmtBrl(bruto);
    document.getElementById('previewDesconto').textContent = fmtBrl(desconto);
    document.getElementById('previewLiquido').textContent  = fmtBrl(liquido);

    const irRow = document.getElementById('irPreviewRow');
    if (irCalc > 0) {
        irRow.style.display = '';
        document.getElementById('irBaseLabel').textContent =
            '(' + fmtBrl(baseIR) + ' × ' +
            (IR_TABLE.find(f => baseIR <= f.limite)?.aliquota * 100 || 0) + '%)';
        document.getElementById('previewIR').textContent = fmtBrl(irCalc);
    } else {
        irRow.style.display = 'none';
    }
}

/* ── Build item row HTML ── */
function buildItemRowHtml(opts) {
    const { descricao = '', tipo = 'provento', compoeIr = true,
            valorBase = '', qty = '', valor = '' } = opts || {};

    const pirSel = tipo === 'desconto' ? 'selected' : '';
    const provSel = tipo === 'provento' ? 'selected' : '';
    const irChk  = compoeIr ? 'checked' : '';
    const irVal  = compoeIr ? '1' : '0';

    return `
<div class="payroll-item-row">
    <!-- Linha 1: descrição | tipo | Compõe IR | excluir -->
    <div class="pir-row">
        <span class="pir-drag-handle" title="Arrastar para reordenar">
            <i class="fa-solid fa-grip-vertical"></i>
        </span>
        <div class="pir-col pir-col-desc">
            <span class="pir-col-label">Descrição</span>
            <input type="text" class="form-control" name="item_descricao[]"
                   placeholder="Ex: Salário Base, Hora Extra…" value="${escHtml(descricao)}" required>
        </div>
        <div class="pir-col pir-col-tipo">
            <span class="pir-col-label">Tipo</span>
            <select class="form-control pir-tipo" name="item_tipo[]" onchange="recalcPayroll()">
                <option value="provento" ${provSel}>Provento</option>
                <option value="desconto" ${pirSel}>Desconto</option>
            </select>
        </div>
        <div class="pir-col-actions">
            <label class="pir-ir-label" title="Incluir no cálculo da base do Imposto de Renda">
                <input type="hidden" name="item_compoe_ir[]" class="pir-ir-hidden" value="${irVal}">
                <input type="checkbox" ${irChk}
                       onchange="this.previousElementSibling.value = this.checked ? 1 : 0; recalcPayroll()">
                <span>Compõe IR</span>
            </label>
            <button type="button" class="btn btn-ghost btn-sm text-danger"
                    onclick="this.closest('.payroll-item-row').remove(); recalcPayroll()"
                    title="Remover item">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
    </div>
    <!-- Linha 2: base unitária × quantidade = valor -->
    <div class="pir-row">
        <div class="pir-col pir-col-base">
            <span class="pir-col-label">Base unitária</span>
            <input type="text" class="form-control currency-input pir-base" name="item_valor_base[]"
                   placeholder="R$ 0,00" value="${escHtml(valorBase)}"
                   oninput="currencyMask(this); calcItemValor(this); recalcPayroll()">
        </div>
        <span class="pir-op">×</span>
        <div class="pir-col pir-col-qty">
            <span class="pir-col-label">Quantidade</span>
            <input type="number" class="form-control pir-qty" name="item_quantidade[]"
                   placeholder="0" min="0" step="0.01" value="${escHtml(qty)}"
                   oninput="calcItemValor(this.closest('.payroll-item-row').querySelector('.pir-base')); recalcPayroll()">
        </div>
        <span class="pir-op">=</span>
        <div class="pir-col pir-col-valor">
            <span class="pir-col-label">Valor</span>
            <input type="text" class="form-control currency-input pir-valor" name="item_valor[]"
                   placeholder="R$ 0,00" value="${escHtml(valor)}" required
                   oninput="currencyMask(this); recalcPayroll()">
        </div>
    </div>
</div>`;
}

function buildIrRow(irCalc) {
    const div = document.createElement('div');
    div.className = 'payroll-item-row pir-ir-auto';
    div.dataset.irRow = '1';
    div.innerHTML = `
        <!-- Linha 1: nome fixo | badge IR Auto -->
        <div class="pir-row">
            <span class="pir-drag-handle" title="Arrastar para reordenar">
                <i class="fa-solid fa-grip-vertical"></i>
            </span>
            <div class="pir-col pir-col-desc">
                <span class="pir-col-label">Descrição</span>
                <input type="text" class="form-control" name="item_descricao[]"
                       value="Imposto de Renda" readonly>
            </div>
            <div class="pir-col-actions">
                <input type="hidden" name="item_tipo[]"      value="desconto">
                <input type="hidden" name="item_compoe_ir[]" class="pir-ir-hidden" value="0">
                <span class="pir-ir-badge"><i class="fa-solid fa-landmark"></i> IR Auto</span>
            </div>
        </div>
        <!-- Linha 2: valor do desconto -->
        <div class="pir-row">
            <input type="hidden" name="item_valor_base[]" value="">
            <input type="hidden" name="item_quantidade[]" value="">
            <div class="pir-col pir-col-base">
                <span class="pir-col-label">Valor do desconto</span>
                <input type="text" class="form-control currency-input pir-valor" name="item_valor[]"
                       value="${irCalc.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2})}"
                       required oninput="currencyMask(this); recalcPayroll()">
            </div>
            <div class="pir-col pir-col-valor" style="align-self:flex-end; padding-bottom:.45rem;
                         font-size:.8rem; color:var(--text-muted)">
                Calculado automaticamente — editável
            </div>
        </div>`;
    return div;
}

/* ── Calculate valor from base × qty ── */
function calcItemValor(baseInput) {
    if (!baseInput) return;
    const row = baseInput.closest('.payroll-item-row');
    const qtyInp  = row?.querySelector('.pir-qty');
    const valInp  = row?.querySelector('.pir-valor');
    if (!qtyInp || !valInp) return;

    const base = parseBrl(baseInput.value);
    const qty  = parseFloat(qtyInp.value) || 0;

    if (base > 0 && qty > 0) {
        const result = base * qty;
        valInp.value = result.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
}

/* ── Drag-and-drop reorder ── */
let _pirDragSrc = null;

function initDragRow(row) {
    row.setAttribute('draggable', 'true');

    row.addEventListener('dragstart', e => {
        _pirDragSrc = row;
        e.dataTransfer.effectAllowed = 'move';
        setTimeout(() => row.classList.add('pir-dragging'), 0);
    });

    row.addEventListener('dragend', () => {
        row.classList.remove('pir-dragging');
        document.querySelectorAll('#payrollItems .pir-drag-over')
            .forEach(el => el.classList.remove('pir-drag-over'));
        _pirDragSrc = null;
    });

    row.addEventListener('dragover', e => {
        e.preventDefault();
        if (_pirDragSrc && _pirDragSrc !== row) row.classList.add('pir-drag-over');
    });

    row.addEventListener('dragleave', e => {
        if (!row.contains(e.relatedTarget)) row.classList.remove('pir-drag-over');
    });

    row.addEventListener('drop', e => {
        e.preventDefault();
        row.classList.remove('pir-drag-over');
        if (!_pirDragSrc || _pirDragSrc === row) return;
        const container = document.getElementById('payrollItems');
        const rows = [...container.children];
        if (rows.indexOf(_pirDragSrc) > rows.indexOf(row)) {
            container.insertBefore(_pirDragSrc, row);
        } else {
            row.after(_pirDragSrc);
        }
        recalcPayroll();
    });
}

function attachRowListeners(row) {
    row.querySelectorAll('.currency-input').forEach(inp => {
        inp.addEventListener('input', () => { currencyMask(inp); recalcPayroll(); });
    });
    row.querySelector('.pir-qty')?.addEventListener('input', () => {
        calcItemValor(row.querySelector('.pir-base'));
        recalcPayroll();
    });
    row.querySelector('.pir-tipo')?.addEventListener('change', recalcPayroll);
    initDragRow(row);
}

/* Insert at top (Adicionar Item button) */
function addPayrollItem(opts) {
    const container = document.getElementById('payrollItems');
    const div = document.createElement('div');
    div.innerHTML = buildItemRowHtml(opts || {});
    const row = div.firstElementChild;
    container.prepend(row);
    attachRowListeners(row);
}

/* Insert at bottom (used when rebuilding list in edit mode) */
function appendPayrollItemRow(container, opts) {
    const div = document.createElement('div');
    div.innerHTML = buildItemRowHtml(opts || {});
    const row = div.firstElementChild;
    container.appendChild(row);
    attachRowListeners(row);
}

function togglePayrollDetail(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', function () {
    <?php if (!empty($editItem)): ?>
    editPayroll(<?= json_encode($editItem) ?>);
    <?php else: ?>
    // Pre-seed one empty row
    addPayrollItem();
    recalcPayroll();
    <?php endif; ?>
});

function editPayroll(item) {
    const form = document.getElementById('folhaForm');
    const BASE = '<?= $basePath ?>';

    form.action = BASE + '/folha-pagamento/' + item.id;
    document.getElementById('folhaIdHidden').value = item.id;
    document.getElementById('modalFolhaTitle').innerHTML =
        '<i class="fa-solid fa-pencil"></i> Editar Folha de Pagamento';

    form.querySelector('[name="descricao"]').value      = item.descricao || '';
    form.querySelector('[name="data_pagamento"]').value = item.data_pagamento || '';
    form.querySelector('[name="observacao"]').value     = item.observacao || '';
    const mesSelect = form.querySelector('[name="mes_referencia"]');
    if (mesSelect) mesSelect.value = item.mes_referencia;
    const anoInput = form.querySelector('[name="ano_referencia"]');
    if (anoInput) anoInput.value = item.ano_referencia;

    const container = document.getElementById('payrollItems');
    container.innerHTML = '';

    const itens = item.itens && item.itens.length > 0 ? item.itens : [{}];
    itens.forEach(it => {
        const isIrRow = (it.descricao || '').toLowerCase().includes('imposto de renda');
        if (isIrRow && it.tipo === 'desconto') {
            const irRow = buildIrRow(parseFloat(it.valor || '0'));
            container.appendChild(irRow);
            initDragRow(irRow);
            return;
        }
        const qtyStr  = it.quantidade ? String(parseFloat(it.quantidade)) : '';
        const baseStr = it.valor_base
            ? parseFloat(it.valor_base).toLocaleString('pt-BR', { minimumFractionDigits: 2 })
            : '';
        const valStr  = it.valor
            ? parseFloat(it.valor).toLocaleString('pt-BR', { minimumFractionDigits: 2 })
            : '';
        appendPayrollItemRow(container, {
            descricao: it.descricao || '',
            tipo:      it.tipo      || 'provento',
            compoeIr:  (it.compoe_ir ?? 1) == 1,
            valorBase: baseStr,
            qty:       qtyStr,
            valor:     valStr,
        });
    });

    recalcPayroll();
    openModal('modalFolha');
}

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = String(str || '');
    return d.innerHTML;
}

function resetPayrollForm() {
    const form = document.getElementById('folhaForm');
    const BASE = '<?= $basePath ?>';
    form.action = BASE + '/folha-pagamento';
    document.getElementById('folhaIdHidden').value = '';
    document.getElementById('modalFolhaTitle').innerHTML =
        '<i class="fa-solid fa-file-invoice-dollar"></i> Novo Holerite / Folha';
    form.querySelector('[name="descricao"]').value      = 'Folha de Pagamento';
    form.querySelector('[name="data_pagamento"]').value = '';
    form.querySelector('[name="observacao"]').value     = '';
    const mesSelect = form.querySelector('[name="mes_referencia"]');
    if (mesSelect) {
        const now = new Date();
        mesSelect.value = now.getMonth() + 1;
    }
    const anoInput = form.querySelector('[name="ano_referencia"]');
    if (anoInput) anoInput.value = new Date().getFullYear();

    document.getElementById('payrollItems').innerHTML = '';
    addPayrollItem();
    recalcPayroll();
    openModal('modalFolha');
}

async function deletePayroll(id, csrf) {
    const result = await Swal.fire({
        title: 'Excluir este registro?',
        text: 'A movimentação vinculada também será removida.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    });
    if (!result.isConfirmed) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `<?= $basePath ?>/folha-pagamento/${id}/excluir`;
    form.innerHTML = `<input type="hidden" name="_csrf" value="${csrf}">`;
    document.body.appendChild(form);
    form.submit();
}
</script>

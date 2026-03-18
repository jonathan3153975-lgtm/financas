<?php
$pageTitle = 'Folha de Pagamento – JW Finanças';
$basePath  = defined('BASE_URL') ? BASE_URL : ($_ENV['APP_BASE_PATH'] ?? '');
$meses     = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
               'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
?>

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

<!-- Payrolls List -->
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

        <!-- Payroll detail (collapsible) -->
        <div class="payroll-detail" id="detail-<?= $p['id'] ?>" style="display:none">
            <div class="payroll-items-grid">
                <div class="payroll-items-col">
                    <h4 class="payroll-col-title text-success">
                        <i class="fa-solid fa-circle-plus"></i> Proventos
                    </h4>
                    <?php foreach ($p['itens'] as $item): ?>
                    <?php if ($item['tipo'] !== 'provento') continue; ?>
                    <div class="payroll-item">
                        <span><?= htmlspecialchars($item['descricao']) ?></span>
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
     MODAL - Nova Folha
================================================================ -->
<div class="modal-overlay" id="modalFolha" style="display:none">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fa-solid fa-file-invoice-dollar"></i> Novo Holerite / Folha
            </h3>
            <button class="modal-close" onclick="closeModal('modalFolha')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="<?= $basePath ?>/folha-pagamento" id="folhaForm">
            <div class="modal-body">
                <input type="hidden" name="_csrf"  value="<?= htmlspecialchars($csrf) ?>">
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

                    <div id="payrollItems">
                        <!-- Item template row -->
                        <div class="payroll-item-row">
                            <input type="text" class="form-control" name="item_descricao[]" placeholder="Descrição (ex: Salário Base)" required>
                            <select class="form-control" name="item_tipo[]">
                                <option value="provento">Provento</option>
                                <option value="desconto">Desconto</option>
                            </select>
                            <input type="text" class="form-control currency-input" name="item_valor[]" placeholder="R$ 0,00" required>
                            <button type="button" class="btn btn-ghost btn-sm text-danger" onclick="this.closest('.payroll-item-row').remove(); recalcPayroll()">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
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
function addPayrollItem() {
    const container = document.getElementById('payrollItems');
    const div = document.createElement('div');
    div.className = 'payroll-item-row';
    div.innerHTML = `
        <input type="text" class="form-control" name="item_descricao[]" placeholder="Descrição" required>
        <select class="form-control" name="item_tipo[]">
            <option value="provento">Provento</option>
            <option value="desconto">Desconto</option>
        </select>
        <input type="text" class="form-control currency-input" name="item_valor[]" placeholder="R$ 0,00" required>
        <button type="button" class="btn btn-ghost btn-sm text-danger" onclick="this.closest('.payroll-item-row').remove(); recalcPayroll()">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;
    container.appendChild(div);

    // Attach currency mask to new input
    div.querySelector('.currency-input').addEventListener('input', function () {
        currencyMask(this);
        recalcPayroll();
    });
    div.querySelector('select').addEventListener('change', recalcPayroll);
}

function recalcPayroll() {
    const rows    = document.querySelectorAll('.payroll-item-row');
    let bruto     = 0;
    let desconto  = 0;

    rows.forEach(row => {
        const tipo  = row.querySelector('select[name="item_tipo[]"]')?.value;
        const valRaw = row.querySelector('input[name="item_valor[]"]')?.value || '0';
        const val    = parseFloat(valRaw.replace(/\./g, '').replace(',', '.')) || 0;
        if (tipo === 'provento') bruto += val;
        else desconto += val;
    });

    const liquido = bruto - desconto;
    document.getElementById('previewBruto').textContent    = 'R$ ' + bruto.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
    document.getElementById('previewDesconto').textContent = 'R$ ' + desconto.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
    document.getElementById('previewLiquido').textContent  = 'R$ ' + liquido.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
}

function togglePayrollDetail(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

// Init currency masks on existing rows
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.currency-input').forEach(inp => {
        inp.addEventListener('input', function () { currencyMask(this); recalcPayroll(); });
    });
    document.querySelectorAll('select[name="item_tipo[]"]').forEach(s => {
        s.addEventListener('change', recalcPayroll);
    });

    <?php if (!empty($editItem)): ?>
    // Auto-open edit modal
    editPayroll(<?= json_encode($editItem) ?>);
    <?php endif; ?>
});

function editPayroll(item) {
    const form    = document.getElementById('folhaForm');
    const idInput = document.getElementById('folhaIdHidden');
    const BASE    = '<?= $basePath ?>';

    // Change form action to update endpoint
    form.action = BASE + '/folha-pagamento/' + item.id;
    idInput.value = item.id;

    // Update modal title
    const title = document.querySelector('#modalFolha .modal-title');
    if (title) title.innerHTML = '<i class="fa-solid fa-pencil"></i> Editar Folha de Pagamento';

    // Fill header fields
    form.querySelector('[name="descricao"]').value       = item.descricao || '';
    form.querySelector('[name="data_pagamento"]').value  = item.data_pagamento || '';
    form.querySelector('[name="observacao"]').value      = item.observacao || '';

    // Fill mes/ano
    const mesSelect = form.querySelector('[name="mes_referencia"]');
    if (mesSelect) mesSelect.value = item.mes_referencia;
    const anoInput = form.querySelector('[name="ano_referencia"]');
    if (anoInput) anoInput.value = item.ano_referencia;

    // Clear and rebuild items
    const container = document.getElementById('payrollItems');
    container.innerHTML = '';

    if (item.itens && item.itens.length > 0) {
        item.itens.forEach(it => {
            const div = document.createElement('div');
            div.className = 'payroll-item-row';
            div.innerHTML = `
                <input type="text" class="form-control" name="item_descricao[]" value="${escHtml(it.descricao)}" required>
                <select class="form-control" name="item_tipo[]">
                    <option value="provento" ${it.tipo === 'provento' ? 'selected' : ''}>Provento</option>
                    <option value="desconto" ${it.tipo === 'desconto' ? 'selected' : ''}>Desconto</option>
                </select>
                <input type="text" class="form-control currency-input" name="item_valor[]"
                       value="${parseFloat(it.valor).toLocaleString('pt-BR',{minimumFractionDigits:2})}" required>
                <button type="button" class="btn btn-ghost btn-sm text-danger"
                        onclick="this.closest('.payroll-item-row').remove(); recalcPayroll()">
                    <i class="fa-solid fa-trash"></i>
                </button>
            `;
            container.appendChild(div);
            div.querySelector('.currency-input').addEventListener('input', function() { currencyMask(this); recalcPayroll(); });
            div.querySelector('select').addEventListener('change', recalcPayroll);
        });
    } else {
        addPayrollItem();
    }

    recalcPayroll();
    openModal('modalFolha');
}

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}

function resetPayrollForm() {
    const form = document.getElementById('folhaForm');
    const BASE = '<?= $basePath ?>';
    form.action = BASE + '/folha-pagamento';
    document.getElementById('folhaIdHidden').value = '';
    const title = document.querySelector('#modalFolha .modal-title');
    if (title) title.innerHTML = '<i class="fa-solid fa-file-invoice-dollar"></i> Novo Holerite / Folha';
    form.reset();
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

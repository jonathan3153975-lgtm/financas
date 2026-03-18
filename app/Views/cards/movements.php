<?php
$pageTitle = 'Fatura do Cartão – JW Finanças';
$basePath  = defined('BASE_URL') ? BASE_URL : ($_ENV['APP_BASE_PATH'] ?? '');
$meses     = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
               'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
$bandeiraCls = match(strtolower($card['bandeira'] ?? '')) {
    'mastercard' => 'fa-cc-mastercard',
    'amex'       => 'fa-cc-amex',
    default      => 'fa-cc-visa',
};
?>

<div class="page-header">
    <div>
        <a href="<?= $basePath ?>/cartoes" class="btn btn-ghost btn-sm mb-2">
            <i class="fa-solid fa-arrow-left"></i> Voltar
        </a>
        <h1 class="page-title">
            <i class="fa-brands <?= $bandeiraCls ?>"></i>
            <?= htmlspecialchars($card['nome_cartao']) ?>
        </h1>
        <p class="page-subtitle">
            Fatura de <?= $meses[$mes] ?>/<?= $ano ?>
            &bull; Fecha dia <?= $card['dia_fechamento'] ?>
            &bull; Vence dia <?= $card['dia_vencimento'] ?>
        </p>
    </div>
    <div class="page-header-actions">
        <!-- Period selector -->
        <form method="GET" class="period-form">
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
        <button class="btn btn-primary" onclick="openModal('modalMovCartao')">
            <i class="fa-solid fa-plus"></i> Nova Compra
        </button>
    </div>
</div>

<!-- Summary -->
<div class="cards-grid cards-grid-3">
    <div class="summary-card card-expense">
        <div class="summary-card-icon"><i class="fa-solid fa-receipt"></i></div>
        <div class="summary-card-value">R$ <?= number_format($total, 2, ',', '.') ?></div>
        <div class="summary-card-label">Total da Fatura</div>
    </div>
    <div class="summary-card card-balance-pos">
        <div class="summary-card-icon"><i class="fa-solid fa-wallet"></i></div>
        <div class="summary-card-value">R$ <?= number_format(max(0, (float)$card['limite'] - $total), 2, ',', '.') ?></div>
        <div class="summary-card-label">Limite Disponível</div>
    </div>
    <div class="summary-card card-total-pos">
        <div class="summary-card-icon"><i class="fa-solid fa-credit-card"></i></div>
        <div class="summary-card-value">R$ <?= number_format((float)$card['limite'], 2, ',', '.') ?></div>
        <div class="summary-card-label">Limite Total</div>
    </div>
</div>

<!-- Movements Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Compras do Mês</h3>
        <div class="card-header-actions">
            <button type="button" class="btn btn-warning btn-sm"
                    onclick="closeBill(<?= $card['id'] ?>, <?= $mes ?>, <?= $ano ?>, '<?= htmlspecialchars($csrf) ?>', '<?= $meses[$mes] ?>/<?= $ano ?>')">
                <i class="fa-solid fa-lock"></i> Fechar Fatura
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($movements)): ?>
        <div class="empty-state py-10">
            <i class="fa-solid fa-receipt"></i>
            <p>Nenhuma compra neste período.</p>
            <button class="btn btn-primary btn-sm mt-2" onclick="openModal('modalMovCartao')">
                <i class="fa-solid fa-plus"></i> Adicionar Compra
            </button>
        </div>
        <?php else: ?>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Categoria</th>
                    <th class="text-center">Parcelas</th>
                    <th class="text-right">Valor</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movements as $mov): ?>
                <tr>
                    <td class="text-sm text-muted"><?= date('d/m/Y', strtotime($mov['data_compra'])) ?></td>
                    <td class="font-medium"><?= htmlspecialchars($mov['descricao']) ?></td>
                    <td class="text-sm text-muted"><?= htmlspecialchars($mov['categoria_nome'] ?? '—') ?></td>
                    <td class="text-center text-sm">
                        <?php if ($mov['total_parcelas'] > 1): ?>
                        <span class="badge badge-secondary"><?= $mov['parcela_atual'] ?>/<?= $mov['total_parcelas'] ?></span>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right font-semibold text-danger">
                        R$ <?= number_format((float)$mov['valor'], 2, ',', '.') ?>
                    </td>
                    <td class="text-center">
                        <?php if ($mov['pago']): ?>
                        <span class="badge badge-success"><i class="fa-solid fa-check"></i> Pago</span>
                        <?php else: ?>
                        <span class="badge badge-warning"><i class="fa-solid fa-clock"></i> Aberto</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="action-buttons">
                            <button class="action-btn action-btn-primary"
                                    title="Editar"
                                    onclick="editCardMovement(<?= htmlspecialchars(json_encode($mov), ENT_QUOTES) ?>)">
                                <i class="fa-solid fa-pencil"></i>
                            </button>
                            <button type="button" class="action-btn action-btn-danger" title="Excluir"
                                    onclick="deleteCardMovement(<?= $card['id'] ?>, <?= $mov['id'] ?>, '<?= htmlspecialchars($csrf) ?>')">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-right font-semibold">Total da Fatura:</td>
                    <td class="text-right font-bold text-danger">
                        R$ <?= number_format($total, 2, ',', '.') ?>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- ================================================================
     MODAL - Nova Compra no Cartão
================================================================ -->
<div class="modal-overlay" id="modalMovCartao" style="display:none">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fa-solid fa-cart-plus"></i> Nova Compra — <?= htmlspecialchars($card['nome_cartao']) ?>
            </h3>
            <button class="modal-close" onclick="closeModal('modalMovCartao')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="<?= $basePath ?>/cartoes/<?= $card['id'] ?>/movimentos">
            <div class="modal-body">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

                <div class="form-group">
                    <label class="form-label">Descrição <span class="required">*</span></label>
                    <input type="text" class="form-control" name="descricao" required
                           placeholder="Ex.: iFood, Renner, Amazon...">
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label class="form-label">Valor Total <span class="required">*</span></label>
                        <input type="text" class="form-control currency-input" name="valor"
                               placeholder="R$ 0,00" required id="cardMovValor">
                    </div>
                    <div class="form-group col-6">
                        <label class="form-label">Data da Compra</label>
                        <input type="date" class="form-control" name="data_compra"
                               value="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label class="form-label">Categoria</label>
                        <select class="form-control" name="categoria_id" id="cardMovCat">
                            <option value="">Selecione...</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"
                                    data-subs='<?= htmlspecialchars(json_encode($cat['subcategorias'])) ?>'>
                                <?= htmlspecialchars($cat['nome']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-6">
                        <label class="form-label">Subcategoria</label>
                        <select class="form-control" name="subcategoria_id" id="cardMovSub">
                            <option value="">—</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Número de Parcelas</label>
                    <input type="number" class="form-control" name="total_parcelas"
                           min="1" max="60" value="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modalMovCartao')">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> Registrar Compra
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ================================================================
     MODAL - Editar Compra
================================================================ -->
<div class="modal-overlay" id="modalEditMovCartao" style="display:none">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fa-solid fa-pencil"></i> Editar Compra
            </h3>
            <button class="modal-close" onclick="closeModal('modalEditMovCartao')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" id="editCardMovForm" action="">
            <div class="modal-body">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

                <div class="form-group">
                    <label class="form-label">Descrição <span class="required">*</span></label>
                    <input type="text" class="form-control" name="descricao" id="editMovDescricao" required>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label class="form-label">Valor da Parcela <span class="required">*</span></label>
                        <input type="text" class="form-control currency-input" name="valor"
                               id="editMovValor" placeholder="R$ 0,00" required>
                    </div>
                    <div class="form-group col-6">
                        <label class="form-label">Data da Compra</label>
                        <input type="date" class="form-control" name="data_compra" id="editMovData">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label class="form-label">Parcela Atual</label>
                        <input type="number" class="form-control" name="parcela_atual" id="editMovParcelaAtual" min="1" max="360">
                    </div>
                    <div class="form-group col-6">
                        <label class="form-label">Total de Parcelas</label>
                        <input type="number" class="form-control" name="total_parcelas" id="editMovTotalParcelas" min="1" max="360">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label class="form-label">Categoria</label>
                        <select class="form-control" name="categoria_id" id="editMovCat">
                            <option value="">Selecione...</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"
                                    data-subs='<?= htmlspecialchars(json_encode($cat['subcategorias'])) ?>'>
                                <?= htmlspecialchars($cat['nome']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-6">
                        <label class="form-label">Subcategoria</label>
                        <select class="form-control" name="subcategoria_id" id="editMovSub">
                            <option value="">—</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modalEditMovCartao')">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const valInput = document.getElementById('cardMovValor');
    if (valInput) valInput.addEventListener('input', function () { currencyMask(this); });

    const catSel = document.getElementById('cardMovCat');
    const subSel = document.getElementById('cardMovSub');

    if (catSel) {
        catSel.addEventListener('change', function () {
            subSel.innerHTML = '<option value="">—</option>';
            const opt = this.options[this.selectedIndex];
            if (!opt || !opt.dataset.subs) return;
            JSON.parse(opt.dataset.subs).forEach(s => {
                const o = document.createElement('option');
                o.value = s.id;
                o.textContent = s.nome;
                subSel.appendChild(o);
            });
        });
    }

    // Edit modal subcategory
    const editCat = document.getElementById('editMovCat');
    const editSub = document.getElementById('editMovSub');
    if (editCat) {
        editCat.addEventListener('change', function () {
            editSub.innerHTML = '<option value="">—</option>';
            const opt = this.options[this.selectedIndex];
            if (!opt || !opt.dataset.subs) return;
            JSON.parse(opt.dataset.subs).forEach(s => {
                const o = document.createElement('option');
                o.value = s.id;
                o.textContent = s.nome;
                editSub.appendChild(o);
            });
        });
    }

    const editValInput = document.getElementById('editMovValor');
    if (editValInput) editValInput.addEventListener('input', function () { currencyMask(this); });
});

function editCardMovement(mov) {
    const BASE    = '<?= $basePath ?>';
    const cardId  = <?= $card['id'] ?>;
    const form    = document.getElementById('editCardMovForm');

    form.action = BASE + '/cartoes/' + cardId + '/movimentos/' + mov.id;

    document.getElementById('editMovDescricao').value      = mov.descricao || '';
    document.getElementById('editMovData').value           = mov.data_compra || '';
    document.getElementById('editMovParcelaAtual').value   = mov.parcela_atual || 1;
    document.getElementById('editMovTotalParcelas').value  = mov.total_parcelas || 1;

    // Value formatted
    const v = parseFloat(mov.valor) || 0;
    document.getElementById('editMovValor').value =
        v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // Category
    const catSel = document.getElementById('editMovCat');
    const subSel = document.getElementById('editMovSub');
    catSel.value = mov.categoria_id || '';
    catSel.dispatchEvent(new Event('change'));

    // Subcategory (after subs are loaded)
    setTimeout(() => { subSel.value = mov.subcategoria_id || ''; }, 50);

    openModal('modalEditMovCartao');
}

async function deleteCardMovement(cardId, movId, csrf) {
    const result = await Swal.fire({
        title: 'Excluir esta compra?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    });
    if (!result.isConfirmed) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `<?= $basePath ?>/cartoes/${cardId}/movimentos/${movId}/excluir`;
    form.innerHTML = `<input type="hidden" name="_csrf" value="${csrf}">`;
    document.body.appendChild(form);
    form.submit();
}

async function closeBill(cardId, mes, ano, csrf, label) {
    const result = await Swal.fire({
        title: `Fechar fatura de ${label}?`,
        text: 'Todas as compras serão marcadas como pagas.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, fechar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#f59e0b',
    });
    if (!result.isConfirmed) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `<?= $basePath ?>/cartoes/${cardId}/fechar`;
    form.innerHTML = `<input type="hidden" name="_csrf" value="${csrf}"><input type="hidden" name="mes" value="${mes}"><input type="hidden" name="ano" value="${ano}">`;
    document.body.appendChild(form);
    form.submit();
}
</script>

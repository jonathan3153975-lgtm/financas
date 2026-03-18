<?php
$pageTitle = 'Movimentações Fixas – JW Finanças';
$basePath  = $_ENV['APP_BASE_PATH'] ?? '/financas/public';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Movimentações Fixas</h1>
        <p class="page-subtitle">Receitas e despesas que se repetem mensalmente</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalFixa')">
        <i class="fa-solid fa-plus"></i> Nova Recorrente
    </button>
</div>

<!-- Info banner -->
<div class="alert alert-info">
    <i class="fa-solid fa-circle-info"></i>
    <strong>Como funciona:</strong> As movimentações fixas ativas são geradas automaticamente todo mês quando você acessa as telas de movimentações ou dashboard.
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Movimentações Recorrentes</h3>
    </div>
    <div class="card-body p-0">
        <?php if (empty($fixas)): ?>
        <div class="empty-state py-10">
            <i class="fa-solid fa-repeat"></i>
            <p>Nenhuma movimentação fixa cadastrada.</p>
            <button class="btn btn-primary btn-sm mt-2" onclick="openModal('modalFixa')">
                <i class="fa-solid fa-plus"></i> Adicionar
            </button>
        </div>
        <?php else: ?>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th>Tipo</th>
                    <th>Categoria</th>
                    <th class="text-right">Valor</th>
                    <th class="text-center">Dia Venc.</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fixas as $f): ?>
                <tr class="<?= !(bool)$f['ativo'] ? 'row-inactive' : '' ?>">
                    <td>
                        <div class="font-medium"><?= htmlspecialchars($f['descricao']) ?></div>
                        <?php if ($f['observacao']): ?>
                        <div class="text-xs text-muted"><?= htmlspecialchars($f['observacao']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $f['tipo'] === 'entrada' ? 'badge-success' : 'badge-danger' ?>">
                            <?= $f['tipo'] === 'entrada' ? 'Receita' : 'Despesa' ?>
                        </span>
                    </td>
                    <td class="text-sm text-muted"><?= htmlspecialchars($f['categoria_nome'] ?? '—') ?></td>
                    <td class="text-right font-semibold <?= $f['tipo'] === 'entrada' ? 'text-success' : 'text-danger' ?>">
                        R$ <?= number_format((float)$f['valor'], 2, ',', '.') ?>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-secondary">Dia <?= $f['dia_vencimento'] ?></span>
                    </td>
                    <td class="text-center">
                        <button
                            class="badge <?= $f['ativo'] ? 'badge-success' : 'badge-secondary' ?> cursor-pointer border-0"
                            onclick="toggleFixed(<?= $f['id'] ?>, '<?= htmlspecialchars($csrf) ?>')"
                            title="Clique para <?= $f['ativo'] ? 'desativar' : 'ativar' ?>">
                            <?= $f['ativo'] ? 'Ativo' : 'Inativo' ?>
                        </button>
                    </td>
                    <td class="text-center">
                        <div class="action-buttons">
                            <button class="action-btn action-btn-primary"
                                    onclick="editFixed(<?= htmlspecialchars(json_encode($f)) ?>)"
                                    title="Editar">
                                <i class="fa-solid fa-pencil"></i>
                            </button>
                            <form method="POST" action="<?= $basePath ?>/movimentacoes/fixas/<?= $f['id'] ?>/toggle"
                                  style="display:inline">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- ================================================================
     MODAL - Nova Fixa
================================================================ -->
<div class="modal-overlay" id="modalFixa" style="display:none">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title" id="modalFixaTitle">
                <i class="fa-solid fa-repeat"></i> Nova Movimentação Fixa
            </h3>
            <button class="modal-close" onclick="closeModal('modalFixa')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" id="fixaForm" action="<?= $basePath ?>/movimentacoes/fixas">
            <div class="modal-body">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

                <div class="form-group">
                    <label class="form-label">Descrição <span class="required">*</span></label>
                    <input type="text" class="form-control" name="descricao" id="fixaDescricao" required>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label class="form-label">Tipo</label>
                        <div class="toggle-buttons">
                            <input type="radio" name="tipo" id="fixaTipoEntrada" value="entrada">
                            <label for="fixaTipoEntrada" class="toggle-btn toggle-btn-success">
                                <i class="fa-solid fa-arrow-up"></i> Receita
                            </label>
                            <input type="radio" name="tipo" id="fixaTipoSaida" value="saida" checked>
                            <label for="fixaTipoSaida" class="toggle-btn toggle-btn-danger">
                                <i class="fa-solid fa-arrow-down"></i> Despesa
                            </label>
                        </div>
                    </div>
                    <div class="form-group col-6">
                        <label class="form-label">Dia de Vencimento</label>
                        <input type="number" class="form-control" name="dia_vencimento" id="fixaDiaVenc"
                               min="1" max="31" value="1">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label class="form-label">Categoria</label>
                        <select class="form-control" name="categoria_id" id="fixaCategoriaId">
                            <option value="">Selecione...</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" data-tipo="<?= $cat['tipo'] ?>"
                                    data-subs='<?= htmlspecialchars(json_encode($cat['subcategorias'])) ?>'>
                                <?= htmlspecialchars($cat['nome']) ?> (<?= $cat['tipo'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-6">
                        <label class="form-label">Subcategoria</label>
                        <select class="form-control" name="subcategoria_id" id="fixaSubcategoriaId">
                            <option value="">Selecione...</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Valor <span class="required">*</span></label>
                    <input type="text" class="form-control currency-input" name="valor" id="fixaValor"
                           placeholder="R$ 0,00" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Observação</label>
                    <textarea class="form-control" name="observacao" id="fixaObs" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modalFixa')">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const categoriesData = <?= json_encode($categories ?? []) ?>;
const BASE_PATH = '<?= $basePath ?>';

document.addEventListener('DOMContentLoaded', function () {
    const catSel = document.getElementById('fixaCategoriaId');
    const subSel = document.getElementById('fixaSubcategoriaId');
    const valInput = document.getElementById('fixaValor');

    if (valInput) valInput.addEventListener('input', function () { currencyMask(this); });

    if (catSel) {
        catSel.addEventListener('change', function () {
            subSel.innerHTML = '<option value="">Selecione...</option>';
            const opt = this.options[this.selectedIndex];
            if (!opt || !opt.dataset.subs) return;
            const subs = JSON.parse(opt.dataset.subs);
            subs.forEach(s => {
                const o = document.createElement('option');
                o.value = s.id;
                o.textContent = s.nome;
                subSel.appendChild(o);
            });
        });
    }
});

function editFixed(data) {
    document.getElementById('modalFixaTitle').innerHTML = '<i class="fa-solid fa-pencil"></i> Editar Movimentação Fixa';
    document.getElementById('fixaForm').action = BASE_PATH + '/movimentacoes/fixas/' + data.id;
    document.getElementById('fixaDescricao').value = data.descricao || '';
    document.getElementById('fixaDiaVenc').value    = data.dia_vencimento || 1;
    document.getElementById('fixaValor').value      = parseFloat(data.valor).toFixed(2).replace('.', ',');
    document.getElementById('fixaObs').value        = data.observacao || '';
    document.querySelector(`input[name="tipo"][value="${data.tipo}"]`).checked = true;
    openModal('modalFixa');
}

async function toggleFixed(id, csrf) {
    const res = await fetch(`${BASE_PATH}/movimentacoes/fixas/${id}/toggle`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '_csrf=' + encodeURIComponent(csrf)
    });
    const d = await res.json();
    if (d.success) {
        showToast(d.ativo ? 'Ativada!' : 'Desativada!', d.ativo ? 'success' : 'warning');
        setTimeout(() => location.reload(), 500);
    }
}
</script>

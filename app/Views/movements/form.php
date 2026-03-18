<?php
/**
 * Movement form partial - used in modal and as standalone page.
 * Variables available:
 *   $movement  (array|null) - existing movement for editing
 *   $categories (array)     - categories with subcategories
 *   $csrf       (string)
 *   $basePath   (string)
 */
$basePath  = $basePath  ?? ($_ENV['APP_BASE_PATH'] ?? '/financas/public');
$movement  = $movement  ?? null;
$isEdit    = $movement !== null;
?>

<div class="form-row">
    <!-- Descrição -->
    <div class="form-group col-12">
        <label class="form-label" for="descricao">Descrição <span class="required">*</span></label>
        <input type="text"
               class="form-control"
               id="descricao"
               name="descricao"
               placeholder="Ex.: Salário, Aluguel, Supermercado..."
               value="<?= htmlspecialchars($movement['descricao'] ?? '') ?>"
               required>
    </div>
</div>

<div class="form-row">
    <!-- Tipo -->
    <div class="form-group col-6">
        <label class="form-label">Tipo <span class="required">*</span></label>
        <div class="toggle-buttons">
            <input type="radio" name="tipo" id="tipoEntrada" value="entrada"
                   <?= ($movement['tipo'] ?? 'saida') === 'entrada' ? 'checked' : '' ?>>
            <label for="tipoEntrada" class="toggle-btn toggle-btn-success">
                <i class="fa-solid fa-arrow-up"></i> Receita
            </label>

            <input type="radio" name="tipo" id="tipoSaida" value="saida"
                   <?= ($movement['tipo'] ?? 'saida') === 'saida' ? 'checked' : '' ?>>
            <label for="tipoSaida" class="toggle-btn toggle-btn-danger">
                <i class="fa-solid fa-arrow-down"></i> Despesa
            </label>
        </div>
    </div>

    <!-- Modo -->
    <div class="form-group col-6">
        <label class="form-label">Modo</label>
        <div class="toggle-buttons">
            <input type="radio" name="modo" id="modoUnico" value="unico"
                   <?= ($movement['modo'] ?? 'unico') === 'unico' ? 'checked' : '' ?>>
            <label for="modoUnico" class="toggle-btn">Único</label>

            <input type="radio" name="modo" id="modoFixo" value="fixo"
                   <?= ($movement['modo'] ?? '') === 'fixo' ? 'checked' : '' ?>>
            <label for="modoFixo" class="toggle-btn">Fixo</label>

            <input type="radio" name="modo" id="modoParcelado" value="parcelamento"
                   <?= ($movement['modo'] ?? '') === 'parcelamento' ? 'checked' : '' ?>>
            <label for="modoParcelado" class="toggle-btn">Parcelado</label>
        </div>
    </div>
</div>

<div class="form-row">
    <!-- Categoria -->
    <div class="form-group col-6">
        <label class="form-label" for="categoria_id">Categoria</label>
        <select class="form-control" id="categoria_id" name="categoria_id">
            <option value="">Selecione...</option>
            <?php if (!empty($categories)): ?>
            <?php
            $tipoMap     = ['entrada' => 'receita', 'saida' => 'despesa'];
            $currentTipo = $tipoMap[$movement['tipo'] ?? 'saida'] ?? 'despesa';
            foreach ($categories as $cat):
                if ($cat['tipo'] !== $currentTipo) continue;
            ?>
            <option value="<?= $cat['id'] ?>"
                    data-tipo="<?= $cat['tipo'] ?>"
                    data-subs="<?= htmlspecialchars(json_encode($cat['subcategorias'] ?? [])) ?>"
                    <?= (int)($movement['categoria_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['nome']) ?>
            </option>
            <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>

    <!-- Subcategoria -->
    <div class="form-group col-6">
        <label class="form-label" for="subcategoria_id">Subcategoria</label>
        <select class="form-control" id="subcategoria_id" name="subcategoria_id">
            <option value="">Selecione a categoria primeiro...</option>
            <?php if (!empty($movement['subcategoria_id']) && !empty($categories)): ?>
            <?php
            foreach ($categories as $cat):
                if ((int)$cat['id'] !== (int)$movement['categoria_id']) continue;
                foreach ($cat['subcategorias'] as $sub):
            ?>
            <option value="<?= $sub['id'] ?>"
                    <?= (int)$movement['subcategoria_id'] === (int)$sub['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($sub['nome']) ?>
            </option>
            <?php endforeach; endforeach; ?>
            <?php endif; ?>
        </select>
    </div>
</div>

<div class="form-row">
    <!-- Valor -->
    <div class="form-group col-4">
        <label class="form-label" for="valor">Valor <span class="required">*</span></label>
        <div class="input-wrapper">
            <input type="text"
                   class="form-control currency-input"
                   id="valor"
                   name="valor"
                   placeholder="R$ 0,00"
                   value="<?= $movement ? number_format((float)$movement['valor'], 2, ',', '.') : '' ?>"
                   required>
            <span class="input-icon"><i class="fa-solid fa-dollar-sign"></i></span>
        </div>
    </div>

    <!-- Data Competência -->
    <div class="form-group col-4">
        <label class="form-label" for="data_competencia">Data de Competência <span class="required">*</span></label>
        <input type="date"
               class="form-control"
               id="data_competencia"
               name="data_competencia"
               value="<?= htmlspecialchars($movement['data_competencia'] ?? date('Y-m-d')) ?>"
               required>
    </div>

    <!-- Data Vencimento -->
    <div class="form-group col-4">
        <label class="form-label" for="data_vencimento">Data de Vencimento</label>
        <input type="date"
               class="form-control"
               id="data_vencimento"
               name="data_vencimento"
               value="<?= htmlspecialchars($movement['data_vencimento'] ?? '') ?>">
    </div>
</div>

<!-- Parcelas (shown only when parcelamento) -->
<div class="form-row" id="parcelasRow" style="display: none;">
    <div class="form-group col-3">
        <label class="form-label" for="parcela_atual">Parcela Atual <span class="required">*</span></label>
        <input type="number"
               class="form-control"
               id="parcela_atual"
               name="parcela_atual"
               min="1"
               max="360"
               placeholder="Ex.: 3"
               value="<?= htmlspecialchars((string)($movement['parcela_atual'] ?? 1)) ?>">
    </div>
    <div class="form-group col-3">
        <label class="form-label" for="total_parcelas">Total de Parcelas <span class="required">*</span></label>
        <input type="number"
               class="form-control"
               id="total_parcelas"
               name="total_parcelas"
               min="2"
               max="360"
               placeholder="Ex.: 12"
               value="<?= htmlspecialchars((string)($movement['total_parcelas'] ?? 12)) ?>"
               oninput="calcTotalDivida()">
    </div>
    <div class="form-group col-6">
        <label class="form-label" for="total_divida">Total da Dívida (demonstrativo)</label>
        <div class="input-wrapper">
            <input type="text"
                   class="form-control"
                   id="total_divida"
                   readonly
                   placeholder="Calculado automaticamente"
                   style="background:#f8fafc; cursor:default;">
            <span class="input-icon"><i class="fa-solid fa-calculator"></i></span>
        </div>
    </div>
</div>

<!-- Data Fim (shown only when fixo) -->
<div class="form-row" id="dataFimRow" style="display: none;">
    <div class="form-group col-6">
        <label class="form-label" for="data_fim">
            <i class="fa-solid fa-calendar-xmark"></i>
            Encerrar em (opcional)
        </label>
        <input type="date"
               class="form-control"
               id="data_fim"
               name="data_fim"
               value="<?= htmlspecialchars($movement['data_fim'] ?? '') ?>">
        <small class="text-muted">Deixe vazio para repetir indefinidamente</small>
    </div>
    <div class="form-group col-6">
        <div class="alert alert-info mt-4">
            <i class="fa-solid fa-info-circle"></i>
            Lançamentos fixos são gerados automaticamente todo mês.
        </div>
    </div>
</div>

<!-- Observação -->
<div class="form-group">
    <label class="form-label" for="observacao">Observação</label>
    <textarea class="form-control"
              id="observacao"
              name="observacao"
              rows="2"
              placeholder="Informações adicionais..."><?= htmlspecialchars($movement['observacao'] ?? '') ?></textarea>
</div>

<!-- Category data for JS -->
<script>
const categoriesData = <?= json_encode($categories ?? []) ?>;

function calcTotalDivida() {
    const valorEl   = document.getElementById('valor');
    const totalEl   = document.getElementById('total_parcelas');
    const dividaEl  = document.getElementById('total_divida');
    if (!valorEl || !totalEl || !dividaEl) return;

    const rawVal  = valorEl.value.replace(/\./g, '').replace(',', '.') || '0';
    const parcVal = parseFloat(rawVal) || 0;
    const nParc   = parseInt(totalEl.value) || 1;
    const total   = parcVal * nParc;

    dividaEl.value = 'R$ ' + total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

document.addEventListener('DOMContentLoaded', function () {
    const tipoInputs = document.querySelectorAll('input[name="tipo"]');
    const modoInputs = document.querySelectorAll('input[name="modo"]');
    const catSelect  = document.getElementById('categoria_id');
    const subSelect  = document.getElementById('subcategoria_id');
    const parcelasRow = document.getElementById('parcelasRow');
    const valorInput  = document.getElementById('valor');

    // Currency mask
    if (valorInput) {
        valorInput.addEventListener('input', function () { currencyMask(this); });
    }

    // Update categories when tipo changes
    const tipoToCat = { 'entrada': 'receita', 'saida': 'despesa' };
    function updateCategories(preserveCatId, preserveSubId) {
        const tipoMov = document.querySelector('input[name="tipo"]:checked')?.value || 'saida';
        const tipo    = tipoToCat[tipoMov] || 'despesa';
        const prevCat = preserveCatId ?? catSelect.value;
        const prevSub = preserveSubId ?? subSelect.value;
        catSelect.innerHTML = '<option value="">Selecione...</option>';
        subSelect.innerHTML = '<option value="">Selecione a categoria primeiro...</option>';

        categoriesData.forEach(cat => {
            if (cat.tipo !== tipo) return;
            const opt = document.createElement('option');
            opt.value = cat.id;
            opt.dataset.subs = JSON.stringify(cat.subcategorias);
            opt.textContent = cat.nome;
            if (String(cat.id) === String(prevCat)) opt.selected = true;
            catSelect.appendChild(opt);
        });

        // Populate subcategories for the selected category
        const selectedOpt = catSelect.options[catSelect.selectedIndex];
        if (selectedOpt && selectedOpt.dataset.subs) {
            const subs = JSON.parse(selectedOpt.dataset.subs);
            subs.forEach(sub => {
                const opt = document.createElement('option');
                opt.value = sub.id;
                opt.textContent = sub.nome;
                if (String(sub.id) === String(prevSub)) opt.selected = true;
                subSelect.appendChild(opt);
            });
        }
    }

    // Update subcategories when category changes
    catSelect.addEventListener('change', function () {
        subSelect.innerHTML = '<option value="">Selecione...</option>';
        const selected = this.options[this.selectedIndex];
        if (!selected || !selected.dataset.subs) return;

        const subs = JSON.parse(selected.dataset.subs);
        subs.forEach(sub => {
            const opt = document.createElement('option');
            opt.value = sub.id;
            opt.textContent = sub.nome;
            subSelect.appendChild(opt);
        });
    });

    tipoInputs.forEach(input => input.addEventListener('change', () => updateCategories(null, null)));

    // Show/hide parcelas and data_fim
    const dataFimRow = document.getElementById('dataFimRow');

    function updateModoUI() {
        const modo = document.querySelector('input[name="modo"]:checked')?.value || 'unico';
        const showParcelas = modo === 'parcelamento';
        if (parcelasRow) {
            parcelasRow.style.display = showParcelas ? 'flex' : 'none';
            parcelasRow.querySelectorAll('input').forEach(el => { el.disabled = !showParcelas; });
        }
        if (dataFimRow) dataFimRow.style.display = modo === 'fixo' ? 'flex' : 'none';
        calcTotalDivida();
    }

    modoInputs.forEach(input => input.addEventListener('change', updateModoUI));
    updateModoUI();

    // Also recalc when valor changes
    if (valorInput) valorInput.addEventListener('input', calcTotalDivida);

    // Re-populate categories on page load (preserving saved selection in edit mode)
    const savedCatId = '<?= (int)($movement['categoria_id'] ?? 0) ?>';
    const savedSubId = '<?= (int)($movement['subcategoria_id'] ?? 0) ?>';
    updateCategories(savedCatId || null, savedSubId || null);
});
</script>

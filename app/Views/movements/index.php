<?php
$pageTitle = 'Movimentações – JW Finanças';
$basePath  = defined('BASE_URL') ? BASE_URL : ($_ENV['APP_BASE_PATH'] ?? '');
$meses     = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun',
               'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
$mesesFull = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
               'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
$defaultDebtDate = sprintf('%04d-%02d-01', (int) $ano, (int) $mes);
function fmtBrl2(float $v): string {
    return 'R$ ' . number_format(abs($v), 2, ',', '.');
}
$modeLabels = ['unico' => 'Único', 'fixo' => 'Fixo', 'parcelamento' => 'Parcelado'];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Movimentações</h1>
        <p class="page-subtitle">Controle de receitas e despesas</p>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-warning" onclick="openModal('modalDividaMovimento')">
            <i class="fa-solid fa-hand-holding-dollar"></i> <span class="hide-mobile">Lançar Dívida</span><span class="show-mobile">Dívida</span>
        </button>
        <button class="btn btn-primary" onclick="openModal('modalMovimento')">
            <i class="fa-solid fa-plus"></i> <span class="hide-mobile">Nova Movimentação</span><span class="show-mobile">Novo</span>
        </button>
    </div>
</div>

<!-- Summary cards -->
<div class="cards-grid cards-grid-4 mov-summary-grid">
    <div class="summary-card card-income">
        <div class="summary-card-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
        <div class="summary-card-value"><?= fmtBrl2($totalEntrada) ?></div>
        <div class="summary-card-label">
            Receitas
            <?php if (($pendingEntrada ?? 0) > 0): ?>
            <span class="badge badge-warning ms-1"><?= $pendingEntrada ?>p</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="summary-card card-expense">
        <div class="summary-card-icon"><i class="fa-solid fa-arrow-trend-down"></i></div>
        <div class="summary-card-value"><?= fmtBrl2($totalSaida) ?></div>
        <div class="summary-card-label">
            Despesas
            <?php if (($pendingSaida ?? 0) > 0): ?>
            <span class="badge badge-warning ms-1"><?= $pendingSaida ?>p</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="summary-card <?= $saldoMes >= 0 ? 'card-balance-pos' : 'card-balance-neg' ?>">
        <div class="summary-card-icon"><i class="fa-solid fa-scale-balanced"></i></div>
        <div class="summary-card-value <?= $saldoMes < 0 ? 'text-danger' : '' ?>"><?= fmtBrl2($saldoMes) ?></div>
        <div class="summary-card-label">Saldo Mês</div>
    </div>
    <div class="summary-card card-total-pos">
        <div class="summary-card-icon"><i class="fa-solid fa-piggy-bank"></i></div>
        <div class="summary-card-value <?= $saldoTotal < 0 ? 'text-danger' : '' ?>"><?= fmtBrl2($saldoTotal) ?></div>
        <div class="summary-card-label">Saldo Total</div>
    </div>
</div>

<!-- Filters -->
<div class="card filter-card">
    <!-- Mobile: toggle header -->
    <div class="filter-mobile-header" onclick="toggleFilters()">
        <span><i class="fa-solid fa-sliders"></i> Filtros — <strong><?= $mesesFull[$mes] ?> <?= $ano ?></strong>
            <?php if (!empty($filters['tipo']) || !empty($filters['validado']) || !empty($filters['search'])): ?>
            <span class="badge badge-primary ms-1">Ativos</span>
            <?php endif; ?>
        </span>
        <i class="fa-solid fa-chevron-down filter-toggle-icon" id="filterToggleIcon"></i>
    </div>

    <form method="GET" action="<?= $basePath ?>/movimentacoes" class="filter-form" id="filterForm">
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
                <?php for ($y = (int)date('Y') - 2; $y <= (int)date('Y') + 1; $y++): ?>
                <option value="<?= $y ?>" <?= $y === $ano ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Tipo</label>
            <select name="tipo" class="form-control form-control-sm" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="entrada" <?= ($filters['tipo'] ?? '') === 'entrada' ? 'selected' : '' ?>>Receitas</option>
                <option value="saida"   <?= ($filters['tipo'] ?? '') === 'saida'   ? 'selected' : '' ?>>Despesas</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Status</label>
            <select name="validado" class="form-control form-control-sm" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="1" <?= ($filters['validado'] ?? '') === '1' ? 'selected' : '' ?>>Validados</option>
                <option value="0" <?= ($filters['validado'] ?? '') === '0' ? 'selected' : '' ?>>Pendentes</option>
            </select>
        </div>
        <div class="filter-group filter-group-search">
            <label class="filter-label">Buscar</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Descrição..."
                   value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-magnifying-glass"></i> Filtrar
            </button>
            <a href="<?= $basePath ?>/movimentacoes" class="btn btn-ghost btn-sm">Limpar</a>
        </div>
    </form>
</div>

<!-- Movements List -->
<div class="card" id="movListCard">
    <div class="card-header">
        <h3 class="card-title">
            Movimentações
            <span class="badge badge-secondary ms-2"><?= $total ?></span>
        </h3>
        <div class="card-header-actions">
            <a href="<?= $basePath ?>/relatorios/exportar/movimentos?mes=<?= $mes ?>&ano=<?= $ano ?>"
               class="btn btn-ghost btn-sm" title="Exportar CSV">
                <i class="fa-solid fa-file-csv"></i> <span class="hide-mobile">Exportar</span>
            </a>
        </div>
    </div>

    <div class="card-body p-0">
        <?php if (empty($movements)): ?>
        <div class="empty-state py-10">
            <i class="fa-solid fa-inbox"></i>
            <p>Nenhuma movimentação encontrada para o período selecionado.</p>
            <button class="btn btn-primary btn-sm mt-2" onclick="openModal('modalMovimento')">
                <i class="fa-solid fa-plus"></i> Adicionar
            </button>
        </div>
        <?php else: ?>

        <!-- ── DESKTOP TABLE ────────────────────────────────────────── -->
        <div class="table-responsive hide-on-mobile">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Categoria</th>
                        <th>Modo</th>
                        <th class="text-right">Valor</th>
                        <th>Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movements as $mov): ?>
                    <tr class="<?= !(bool)$mov['validado'] ? 'row-pending' : '' ?>" data-id="<?= $mov['id'] ?>">
                        <td class="text-sm text-muted">
                            <?= date('d/m/Y', strtotime($mov['data_competencia'])) ?>
                        </td>
                        <td>
                            <div class="mov-desc">
                                <?php if (!empty($mov['categoria_cor'])): ?>
                                <span class="cat-dot" style="background:<?= htmlspecialchars($mov['categoria_cor']) ?>"></span>
                                <?php endif; ?>
                                <div>
                                    <div class="font-medium"><?= htmlspecialchars($mov['descricao']) ?></div>
                                    <?php if (!empty($mov['subcategoria_nome'])): ?>
                                    <div class="text-xs text-muted"><?= htmlspecialchars($mov['subcategoria_nome']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="text-sm">
                            <?php if (!empty($mov['categoria_nome'])): ?>
                            <span class="cat-badge" style="background:<?= htmlspecialchars($mov['categoria_cor'] ?? '#6366f1') ?>20; color:<?= htmlspecialchars($mov['categoria_cor'] ?? '#6366f1') ?>">
                                <?= htmlspecialchars($mov['categoria_nome']) ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-sm text-muted">
                            <?= $modeLabels[$mov['modo']] ?? $mov['modo'] ?>
                            <?php if ($mov['modo'] === 'parcelamento'): ?>
                            <span class="text-xs">(<?= $mov['parcela_atual'] ?>/<?= $mov['total_parcelas'] ?>)</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right font-semibold <?= $mov['tipo'] === 'entrada' ? 'text-success' : 'text-danger' ?>">
                            <?= $mov['tipo'] === 'entrada' ? '+' : '-' ?> <?= fmtBrl2((float)$mov['valor']) ?>
                        </td>
                        <td>
                            <?php if ($mov['validado']): ?>
                            <span class="badge badge-success"><i class="fa-solid fa-check"></i> Validado</span>
                            <?php else: ?>
                            <span class="badge badge-warning"><i class="fa-solid fa-clock"></i> Pendente</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="action-buttons">
                                <?php if (!$mov['validado']): ?>
                                <button class="action-btn action-btn-success" title="Validar"
                                        onclick="validateMovement(<?= $mov['id'] ?>)"
                                        data-csrf="<?= htmlspecialchars($csrf) ?>">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                                <?php else: ?>
                                <button class="action-btn action-btn-warning" title="Reverter"
                                        onclick="revertMovement(<?= $mov['id'] ?>)"
                                        data-csrf="<?= htmlspecialchars($csrf) ?>">
                                    <i class="fa-solid fa-rotate-left"></i>
                                </button>
                                <?php endif; ?>
                                <a href="<?= $basePath ?>/movimentacoes/<?= $mov['id'] ?>/editar"
                                   class="action-btn action-btn-primary" title="Editar">
                                    <i class="fa-solid fa-pencil"></i>
                                </a>
                                <button type="button" class="action-btn action-btn-danger" title="Excluir"
                                        onclick="deleteMovement(<?= $mov['id'] ?>, '<?= htmlspecialchars($csrf) ?>')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- ── MOBILE CARDS ─────────────────────────────────────────── -->
        <div class="mov-card-list show-on-mobile">
            <?php foreach ($movements as $mov): ?>
            <div class="mov-card <?= !(bool)$mov['validado'] ? 'mov-card-pending' : '' ?>" data-id="<?= $mov['id'] ?>">
                <div class="mov-card-top">
                    <div class="mov-card-desc">
                        <?php if (!empty($mov['categoria_cor'])): ?>
                        <span class="cat-dot" style="background:<?= htmlspecialchars($mov['categoria_cor']) ?>"></span>
                        <?php endif; ?>
                        <div>
                            <div class="mov-card-title"><?= htmlspecialchars($mov['descricao']) ?></div>
                            <?php if (!empty($mov['subcategoria_nome'])): ?>
                            <div class="mov-card-sub"><?= htmlspecialchars($mov['subcategoria_nome']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mov-card-amount <?= $mov['tipo'] === 'entrada' ? 'text-success' : 'text-danger' ?>">
                        <?= $mov['tipo'] === 'entrada' ? '+' : '−' ?><?= fmtBrl2((float)$mov['valor']) ?>
                    </div>
                </div>

                <div class="mov-card-meta">
                    <span class="mov-card-date"><i class="fa-regular fa-calendar"></i> <?= date('d/m/Y', strtotime($mov['data_competencia'])) ?></span>

                    <?php if (!empty($mov['categoria_nome'])): ?>
                    <span class="cat-badge" style="background:<?= htmlspecialchars($mov['categoria_cor'] ?? '#6366f1') ?>20; color:<?= htmlspecialchars($mov['categoria_cor'] ?? '#6366f1') ?>">
                        <?= htmlspecialchars($mov['categoria_nome']) ?>
                    </span>
                    <?php endif; ?>

                    <span class="mov-card-mode"><?= $modeLabels[$mov['modo']] ?? $mov['modo'] ?>
                        <?php if ($mov['modo'] === 'parcelamento'): ?>
                        <span class="text-xs"><?= $mov['parcela_atual'] ?>/<?= $mov['total_parcelas'] ?></span>
                        <?php endif; ?>
                    </span>

                    <?php if ($mov['validado']): ?>
                    <span class="badge badge-success"><i class="fa-solid fa-check"></i> Validado</span>
                    <?php else: ?>
                    <span class="badge badge-warning"><i class="fa-solid fa-clock"></i> Pendente</span>
                    <?php endif; ?>
                </div>

                <div class="mov-card-actions">
                    <?php if (!$mov['validado']): ?>
                    <button class="mov-action-btn mov-action-success" title="Validar"
                            onclick="validateMovement(<?= $mov['id'] ?>)"
                            data-csrf="<?= htmlspecialchars($csrf) ?>">
                        <i class="fa-solid fa-check"></i> Validar
                    </button>
                    <?php else: ?>
                    <button class="mov-action-btn mov-action-warning" title="Reverter"
                            onclick="revertMovement(<?= $mov['id'] ?>)"
                            data-csrf="<?= htmlspecialchars($csrf) ?>">
                        <i class="fa-solid fa-rotate-left"></i> Reverter
                    </button>
                    <?php endif; ?>
                    <a href="<?= $basePath ?>/movimentacoes/<?= $mov['id'] ?>/editar"
                       class="mov-action-btn mov-action-primary">
                        <i class="fa-solid fa-pencil"></i> Editar
                    </a>
                    <button type="button" class="mov-action-btn mov-action-danger"
                            onclick="deleteMovement(<?= $mov['id'] ?>, '<?= htmlspecialchars($csrf) ?>')">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
        <div class="pagination">
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

<!-- ================================================================
     MODAL - Nova Movimentação
================================================================ -->
<div class="modal-overlay" id="modalMovimento" style="display:none">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fa-solid fa-plus-circle"></i> Nova Movimentação</h3>
            <button class="modal-close" onclick="closeModal('modalMovimento')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="<?= $basePath ?>/movimentacoes" id="movForm">
            <div class="modal-body">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                <?php require BASE_PATH . '/app/Views/movements/form.php'; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modalMovimento')">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modalDividaMovimento" style="display:none">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fa-solid fa-hand-holding-dollar"></i> Lançar Parcela de Dívida</h3>
            <button class="modal-close" onclick="closeModal('modalDividaMovimento')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="<?= $basePath ?>/movimentacoes/dividas-parceladas/registrar">
            <div class="modal-body">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

                <div class="form-group">
                    <label class="form-label">Dívida</label>
                    <select class="form-control" name="divida_id" required>
                        <option value="">Selecione uma dívida...</option>
                        <?php foreach (($openDebts ?? []) as $debt): ?>
                        <option value="<?= (int) $debt['id'] ?>">
                            <?= htmlspecialchars((string) $debt['descricao']) ?>
                            | Parcela <?= (int) $debt['parcelas_pagas'] + 1 ?>/<?= (int) $debt['total_parcelas'] ?>
                            | Saldo <?= fmtBrl2((float) $debt['saldo_devedor']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Data da parcela</label>
                    <input type="date" class="form-control" name="data_competencia" value="<?= $defaultDebtDate ?>" required>
                </div>

                <p class="text-xs text-muted mt-2 mb-0">
                    A movimentação será inserida como <strong>pendente</strong>. A parcela será contabilizada apenas ao validar.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modalDividaMovimento')">Cancelar</button>
                <button type="submit" class="btn btn-warning">
                    <i class="fa-solid fa-check"></i> Confirmar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const BASE_PATH  = '<?= $basePath ?>';
const MOV_MES    = <?= $mes ?>;
const MOV_ANO    = <?= $ano ?>;

/* ── Filter toggle (mobile) ─────────────────────────────────── */
function toggleFilters() {
    const form = document.getElementById('filterForm');
    const icon = document.getElementById('filterToggleIcon');
    const open = form.classList.toggle('filter-open');
    icon.style.transform = open ? 'rotate(180deg)' : '';
}

/* ── Month swipe navigation ──────────────────────────────────── */
(function () {
    let startX = 0, startY = 0;
    const el = document.getElementById('movListCard') || document.body;

    el.addEventListener('touchstart', e => {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
    }, { passive: true });

    el.addEventListener('touchend', e => {
        const dx = e.changedTouches[0].clientX - startX;
        const dy = e.changedTouches[0].clientY - startY;
        if (Math.abs(dx) < 60 || Math.abs(dx) < Math.abs(dy) * 1.5) return;
        navigateMonth(dx < 0 ? 1 : -1);
    }, { passive: true });
})();

function navigateMonth(dir) {
    const params = new URLSearchParams(window.location.search);
    let mes = MOV_MES + dir;
    let ano = MOV_ANO;
    if (mes > 12) { mes = 1;  ano++; }
    if (mes < 1)  { mes = 12; ano--; }
    params.set('mes', mes);
    params.set('ano', ano);
    // keep other active filters, drop page
    params.delete('page');
    window.location.href = window.location.pathname + '?' + params.toString();
}

/* ── Actions ─────────────────────────────────────────────────── */
async function validateMovement(id) {
    const csrf = document.querySelector(`[data-id="${id}"] .action-btn-success`)?.dataset.csrf
              || document.querySelector(`[data-id="${id}"] .mov-action-success`)?.dataset.csrf
              || '<?= htmlspecialchars($csrf) ?>';

    const result = await Swal.fire({
        title: 'Validar movimentação?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, validar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#10b981',
    });
    if (!result.isConfirmed) return;

    const res = await fetch(`${BASE_PATH}/movimentacoes/${id}/validar`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: `_csrf=${encodeURIComponent(csrf)}`
    });
    const data = await res.json();
    if (data.success) {
        showToast(data.message, 'success');
        setTimeout(() => location.reload(), 800);
    } else {
        showToast(data.message || 'Erro.', 'error');
    }
}

async function revertMovement(id) {
    const csrf = '<?= htmlspecialchars($csrf) ?>';
    const result = await Swal.fire({
        title: 'Reverter para pendente?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, reverter',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#f59e0b',
    });
    if (!result.isConfirmed) return;

    const res = await fetch(`${BASE_PATH}/movimentacoes/${id}/reverter`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: `_csrf=${encodeURIComponent(csrf)}`
    });
    const data = await res.json();
    if (data.success) {
        showToast(data.message, 'warning');
        setTimeout(() => location.reload(), 800);
    }
}

async function deleteMovement(id, csrf) {
    const result = await Swal.fire({
        title: 'Excluir movimentação?',
        text: 'Esta ação não pode ser desfeita.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    });
    if (!result.isConfirmed) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `${BASE_PATH}/movimentacoes/${id}/excluir`;
    form.innerHTML = `<input type="hidden" name="_csrf" value="${csrf}">`;
    document.body.appendChild(form);
    form.submit();
}
</script>

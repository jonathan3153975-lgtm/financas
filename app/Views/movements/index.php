<?php
$pageTitle = 'Movimentações – JW Finanças';
$basePath  = defined('BASE_URL') ? BASE_URL : ($_ENV['APP_BASE_PATH'] ?? '');
$meses     = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun',
               'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
function fmtBrl2(float $v): string {
    return 'R$ ' . number_format(abs($v), 2, ',', '.');
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Movimentações</h1>
        <p class="page-subtitle">Controle de receitas e despesas</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalMovimento')">
        <i class="fa-solid fa-plus"></i> Nova Movimentação
    </button>
</div>

<!-- Summary cards -->
<div class="cards-grid cards-grid-4">
    <div class="summary-card card-income">
        <div class="summary-card-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
        <div class="summary-card-value"><?= fmtBrl2($totalEntrada) ?></div>
        <div class="summary-card-label">
            Total Receitas
            <?php if (($pendingEntrada ?? 0) > 0): ?>
            <span class="badge badge-warning ms-1"><?= $pendingEntrada ?> pendente<?= $pendingEntrada > 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="summary-card card-expense">
        <div class="summary-card-icon"><i class="fa-solid fa-arrow-trend-down"></i></div>
        <div class="summary-card-value"><?= fmtBrl2($totalSaida) ?></div>
        <div class="summary-card-label">
            Total Despesas
            <?php if (($pendingSaida ?? 0) > 0): ?>
            <span class="badge badge-warning ms-1"><?= $pendingSaida ?> pendente<?= $pendingSaida > 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="summary-card <?= $saldoMes >= 0 ? 'card-balance-pos' : 'card-balance-neg' ?>">
        <div class="summary-card-icon"><i class="fa-solid fa-scale-balanced"></i></div>
        <div class="summary-card-value <?= $saldoMes < 0 ? 'text-danger' : '' ?>"><?= fmtBrl2($saldoMes) ?></div>
        <div class="summary-card-label">Saldo do Mês</div>
    </div>
    <div class="summary-card card-total-pos">
        <div class="summary-card-icon"><i class="fa-solid fa-piggy-bank"></i></div>
        <div class="summary-card-value <?= $saldoTotal < 0 ? 'text-danger' : '' ?>"><?= fmtBrl2($saldoTotal) ?></div>
        <div class="summary-card-label">Saldo Total</div>
    </div>
</div>

<!-- Filters -->
<div class="card filter-card">
    <form method="GET" action="<?= $basePath ?>/movimentacoes" class="filter-form">
        <div class="filter-group">
            <label class="filter-label">Mês</label>
            <select name="mes" class="form-control form-control-sm">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $m === $mes ? 'selected' : '' ?>><?= $meses[$m] ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Ano</label>
            <select name="ano" class="form-control form-control-sm">
                <?php for ($y = (int)date('Y') - 2; $y <= (int)date('Y') + 1; $y++): ?>
                <option value="<?= $y ?>" <?= $y === $ano ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Tipo</label>
            <select name="tipo" class="form-control form-control-sm">
                <option value="">Todos</option>
                <option value="entrada" <?= ($filters['tipo'] ?? '') === 'entrada' ? 'selected' : '' ?>>Receitas</option>
                <option value="saida"   <?= ($filters['tipo'] ?? '') === 'saida'   ? 'selected' : '' ?>>Despesas</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Status</label>
            <select name="validado" class="form-control form-control-sm">
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

<!-- Movements Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            Movimentações
            <span class="badge badge-secondary ms-2"><?= $total ?></span>
        </h3>
        <div class="card-header-actions">
            <a href="<?= $basePath ?>/relatorios/exportar/movimentos?mes=<?= $mes ?>&ano=<?= $ano ?>"
               class="btn btn-ghost btn-sm" title="Exportar CSV">
                <i class="fa-solid fa-file-csv"></i> Exportar
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
        <div class="table-responsive">
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
                            <?php $modeLabels = ['unico' => 'Único', 'fixo' => 'Fixo', 'parcelamento' => 'Parcelado']; ?>
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
                                <button class="action-btn action-btn-success"
                                        title="Validar"
                                        onclick="validateMovement(<?= $mov['id'] ?>)"
                                        data-csrf="<?= htmlspecialchars($csrf) ?>">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                                <?php else: ?>
                                <button class="action-btn action-btn-warning"
                                        title="Reverter"
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

<script>
const BASE_PATH = '<?= $basePath ?>';

async function validateMovement(id) {
    const csrf = document.querySelector(`[data-id="${id}"] .action-btn-success`)?.dataset.csrf
              || document.querySelector('.action-btn-success')?.dataset.csrf
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

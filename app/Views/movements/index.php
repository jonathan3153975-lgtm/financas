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

$hasActiveFilter = !empty($filters['tipo'])
    || !empty($filters['categoria_id'])
    || (isset($filters['validado']) && $filters['validado'] !== '')
    || !empty($filters['search']);
?>

<!-- ── MOBILE: compact top-bar (always visible on mobile) ── -->
<div class="mob-topbar show-on-mobile">
    <form method="GET" action="<?= $basePath ?>/movimentacoes" class="mob-period-form" id="mobPeriodForm">
        <button type="button" class="mob-nav-btn" onclick="navigateMonth(-1)">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        <select name="mes" class="form-control form-control-sm mob-sel" onchange="this.form.submit()">
            <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= $m === $mes ? 'selected' : '' ?>><?= $meses[$m] ?></option>
            <?php endfor; ?>
        </select>
        <select name="ano" class="form-control form-control-sm mob-sel" onchange="this.form.submit()">
            <?php for ($y = (int)date('Y') - 2; $y <= (int)date('Y') + 1; $y++): ?>
            <option value="<?= $y ?>" <?= $y === $ano ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <?php /* preserve active filters on period change */ ?>
        <?php if (!empty($filters['tipo'])): ?><input type="hidden" name="tipo" value="<?= htmlspecialchars($filters['tipo']) ?>"><?php endif; ?>
        <?php if (!empty($filters['categoria_id'])): ?><input type="hidden" name="categoria_id" value="<?= (int)$filters['categoria_id'] ?>"><?php endif; ?>
        <?php if (isset($filters['validado']) && $filters['validado'] !== ''): ?><input type="hidden" name="validado" value="<?= htmlspecialchars($filters['validado']) ?>"><?php endif; ?>
        <?php if (!empty($filters['search'])): ?><input type="hidden" name="search" value="<?= htmlspecialchars($filters['search']) ?>"><?php endif; ?>
        <button type="button" class="mob-nav-btn" onclick="navigateMonth(1)">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
    </form>
    <button class="mob-filter-toggle" onclick="toggleMobileFullFilter()" title="Filtros avançados">
        <i class="fa-solid fa-sliders"></i>
    </button>
    <?php if ($hasActiveFilter): ?>
    <div class="mob-active-filter">
        <i class="fa-solid fa-filter" style="font-size:.65rem;opacity:.6"></i>
        <?php if (!empty($filters['tipo'])): ?>
        <span class="maf-chip"><?= $filters['tipo'] === 'entrada' ? 'Receitas' : 'Despesas' ?></span>
        <?php endif; ?>
        <?php if (isset($filters['validado']) && $filters['validado'] !== ''): ?>
        <span class="maf-chip"><?= $filters['validado'] === '1' ? 'Validados' : 'Pendentes' ?></span>
        <?php endif; ?>
        <?php if (!empty($filters['search'])): ?>
        <span class="maf-chip">"<?= htmlspecialchars(mb_substr($filters['search'], 0, 12)) ?>"</span>
        <?php endif; ?>
        <a href="<?= $basePath ?>/movimentacoes?mes=<?= $mes ?>&ano=<?= $ano ?>" class="maf-reset">
            <i class="fa-solid fa-xmark"></i> Redefinir
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- ── FILTER CARD (desktop always; mobile collapsed when filter active) ── -->
<div class="card filter-card" id="filterCard">
    <form method="GET" action="<?= $basePath ?>/movimentacoes" class="filter-form filter-open" id="filterForm">
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
            <a href="<?= $basePath ?>/movimentacoes?mes=<?= $mes ?>&ano=<?= $ano ?>" class="btn btn-ghost btn-sm">Limpar</a>
        </div>
    </form>
</div>

<!-- ── MOVEMENTS LIST ── -->
<div class="card" id="movListCard">
    <div class="card-header">
        <h3 class="card-title">
            Movimentações
            <span class="badge badge-secondary ms-2"><?= $total ?></span>
        </h3>
        <div class="card-header-actions">
            <button id="btnCalc" class="btn btn-ghost btn-sm" title="Calcular valores selecionados" onclick="toggleCalcMode()">
                <i class="fa-solid fa-calculator"></i> <span class="hide-mobile">Calcular</span>
            </button>
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

        <!-- ── DESKTOP TABLE ── -->
        <div class="table-responsive hide-on-mobile">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="calc-check-col" style="display:none;width:36px"></th>
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
                        <td class="calc-check-col" style="display:none">
                            <input type="checkbox" class="mov-calc-checkbox"
                                   data-valor="<?= number_format((float)$mov['valor'], 2, '.', '') ?>"
                                   data-tipo="<?= $mov['tipo'] ?>"
                                   onchange="updateCalcBar()">
                        </td>
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
                                <button class="action-btn action-btn-primary" title="Editar"
                                        onclick="openEditModal(<?= $mov['id'] ?>)">
                                    <i class="fa-solid fa-pencil"></i>
                                </button>
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

        <!-- ── DESKTOP PAGINATION ── -->
        <?php if ($pages > 1): ?>
        <div class="pagination hide-on-mobile">
            <?php
            $maxButtons = 7;
            $half = (int) floor($maxButtons / 2);
            $start = max(1, $currentPage - $half);
            $end   = min($pages, $start + $maxButtons - 1);
            if ($end - $start < $maxButtons - 1) $start = max(1, $end - $maxButtons + 1);
            ?>
            <?php if ($start > 1): ?>
            <a href="?<?= http_build_query(array_merge($filters, ['page' => 1])) ?>" class="page-btn">1</a>
            <?php if ($start > 2): ?><span class="page-ellipsis">…</span><?php endif; ?>
            <?php endif; ?>
            <?php for ($p = $start; $p <= $end; $p++): ?>
            <a href="?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>"
               class="page-btn <?= $p === $currentPage ? 'active' : '' ?>">
                <?= $p ?>
            </a>
            <?php endfor; ?>
            <?php if ($end < $pages): ?>
            <?php if ($end < $pages - 1): ?><span class="page-ellipsis">…</span><?php endif; ?>
            <a href="?<?= http_build_query(array_merge($filters, ['page' => $pages])) ?>" class="page-btn"><?= $pages ?></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ── MOBILE CARDS (infinite scroll) ── -->
        <div class="mov-card-list show-on-mobile" id="mobileCardList">
            <?php foreach ($movements as $mov): ?>
            <div class="mov-card <?= !(bool)$mov['validado'] ? 'mov-card-pending' : '' ?>" data-id="<?= $mov['id'] ?>">
                <div class="calc-check-col calc-check-mobile" style="display:none;padding:4px 0 0 0">
                    <label style="display:flex;align-items:center;gap:6px;font-size:.75rem;color:var(--text-muted);cursor:pointer">
                        <input type="checkbox" class="mov-calc-checkbox"
                               data-valor="<?= number_format((float)$mov['valor'], 2, '.', '') ?>"
                               data-tipo="<?= $mov['tipo'] ?>"
                               onchange="updateCalcBar()">
                        Incluir no cálculo
                    </label>
                </div>
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
                    <button class="mov-action-btn mov-action-primary"
                            onclick="openEditModal(<?= $mov['id'] ?>)">
                        <i class="fa-solid fa-pencil"></i> Editar
                    </button>
                    <button type="button" class="mov-action-btn mov-action-danger"
                            onclick="deleteMovement(<?= $mov['id'] ?>, '<?= htmlspecialchars($csrf) ?>')">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            <!-- sentinel for infinite scroll (JS appends it) -->
        </div>

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

<!-- ================================================================
     MODAL - Editar Movimentação
================================================================ -->
<div class="modal-overlay" id="modalEditar" style="display:none">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fa-solid fa-pencil"></i> Editar Movimentação</h3>
            <button class="modal-close" onclick="closeModal('modalEditar')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="editMovForm" novalidate>
            <div class="modal-body">
                <input type="hidden" id="e_id" value="">
                <input type="hidden" name="_csrf" id="e_csrf" value="<?= htmlspecialchars($csrf) ?>">

                <div class="form-row">
                    <div class="form-group col-12">
                        <label class="form-label">Descrição <span class="required">*</span></label>
                        <input type="text" class="form-control" id="e_descricao" name="descricao" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label class="form-label">Tipo</label>
                        <div class="toggle-buttons">
                            <input type="radio" name="tipo" id="e_tipo_entrada" value="entrada">
                            <label for="e_tipo_entrada" class="toggle-btn toggle-btn-success">
                                <i class="fa-solid fa-arrow-up"></i> Receita
                            </label>
                            <input type="radio" name="tipo" id="e_tipo_saida" value="saida" checked>
                            <label for="e_tipo_saida" class="toggle-btn toggle-btn-danger">
                                <i class="fa-solid fa-arrow-down"></i> Despesa
                            </label>
                        </div>
                    </div>
                    <div class="form-group col-6">
                        <label class="form-label">Modo</label>
                        <div class="toggle-buttons">
                            <input type="radio" name="modo" id="e_modo_unico" value="unico" checked>
                            <label for="e_modo_unico" class="toggle-btn">Único</label>
                            <input type="radio" name="modo" id="e_modo_fixo" value="fixo">
                            <label for="e_modo_fixo" class="toggle-btn">Fixo</label>
                            <input type="radio" name="modo" id="e_modo_parcelado" value="parcelamento">
                            <label for="e_modo_parcelado" class="toggle-btn">Parcelado</label>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label class="form-label">Categoria</label>
                        <select class="form-control" id="e_categoria_id" name="categoria_id">
                            <option value="">Selecione...</option>
                        </select>
                    </div>
                    <div class="form-group col-6">
                        <label class="form-label">Subcategoria</label>
                        <select class="form-control" id="e_subcategoria_id" name="subcategoria_id">
                            <option value="">Selecione...</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-4">
                        <label class="form-label">Valor <span class="required">*</span></label>
                        <div class="input-wrapper">
                            <input type="text" class="form-control currency-input" id="e_valor" name="valor" required>
                            <span class="input-icon"><i class="fa-solid fa-dollar-sign"></i></span>
                        </div>
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Data de Competência <span class="required">*</span></label>
                        <input type="date" class="form-control" id="e_data_competencia" name="data_competencia" required>
                    </div>
                    <div class="form-group col-4">
                        <label class="form-label">Data de Vencimento</label>
                        <input type="date" class="form-control" id="e_data_vencimento" name="data_vencimento">
                    </div>
                </div>

                <div class="form-row" id="e_parcelasRow" style="display:none">
                    <div class="form-group col-6">
                        <label class="form-label">Parcela Atual</label>
                        <input type="number" class="form-control" id="e_parcela_atual" name="parcela_atual" min="1">
                    </div>
                    <div class="form-group col-6">
                        <label class="form-label">Total de Parcelas</label>
                        <input type="number" class="form-control" id="e_total_parcelas" name="total_parcelas" min="1">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Observação</label>
                    <textarea class="form-control" id="e_observacao" name="observacao" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modalEditar')">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="editSaveBtn">
                    <i class="fa-solid fa-save"></i> Salvar alterações
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ================================================================
     MODAL - Lançar Parcela de Dívida
================================================================ -->
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

<style>
/* ── Mobile top-bar ── */
.mob-topbar {
    background: var(--bg-secondary, #f8fafc);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 10px;
    padding: 8px 10px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.mob-period-form {
    display: flex;
    align-items: center;
    gap: 6px;
    flex: 1;
}
.mob-sel { max-width: 80px; flex: 1; padding: 4px 6px; }
.mob-nav-btn {
    background: none;
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 6px;
    padding: 4px 8px;
    cursor: pointer;
    color: var(--text-muted);
    font-size: .8rem;
}
.mob-nav-btn:hover { background: var(--bg-secondary); }
.mob-filter-toggle {
    background: none;
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 5px 10px;
    cursor: pointer;
    color: var(--text-muted);
    font-size: .85rem;
}
.mob-active-filter {
    display: flex;
    align-items: center;
    gap: 5px;
    flex-wrap: wrap;
    flex: 0 0 auto;
}
.maf-chip {
    background: rgba(99,102,241,.12);
    color: var(--accent, #6366f1);
    border-radius: 20px;
    padding: 2px 8px;
    font-size: .7rem;
    font-weight: 500;
}
.maf-reset {
    font-size: .72rem;
    color: #ef4444;
    text-decoration: none;
    padding: 2px 6px;
    border: 1px solid #ef444440;
    border-radius: 20px;
    white-space: nowrap;
}
.maf-reset:hover { background: #ef44440d; }

/* Filter card: always hidden on mobile, shown only when toggled */
@media (max-width: 900px) {
    .filter-card { display: none !important; }
    .filter-card.mob-filter-shown { display: block !important; }
}

/* Pagination ellipsis */
.page-ellipsis {
    display: inline-flex;
    align-items: center;
    padding: 0 4px;
    color: var(--text-muted);
    font-size: .85rem;
}

/* Mobile infinite scroll loading */
#mobileScrollLoader {
    text-align: center;
    padding: 16px;
    color: var(--text-muted);
    font-size: .8rem;
}

/* ── Barra de rodapé de movimentações ── */
#movFooterBar {
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
#movFooterBar .fb-inner {
    display: flex;
    align-items: center;
    gap: 0;
    width: 100%;
    height: 100%;
    overflow-x: auto;
}
.fb-group {
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 0 16px;
    border-right: 1px solid rgba(255,255,255,.1);
    min-width: 110px;
    flex-shrink: 0;
}
.fb-group:first-child { padding-left: 0; }
.fb-group.fb-sep-strong { border-left: 2px solid rgba(99,102,241,.6); }
.fb-label {
    font-size: .65rem;
    font-weight: 500;
    letter-spacing: .04em;
    text-transform: uppercase;
    opacity: .55;
    line-height: 1;
    margin-bottom: 2px;
}
.fb-value { font-size: .9rem; font-weight: 700; line-height: 1; }
.fb-spacer { flex: 1; }
.fb-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    padding-left: 12px;
    flex-shrink: 0;
}
.fb-btn {
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
.fb-btn:hover { background: rgba(255,255,255,.16); }
.fb-btn.fb-btn-active { background: rgba(99,102,241,.3); border-color: #6366f1; }
.fb-btn-warning { background: rgba(245,158,11,.18); border-color: rgba(245,158,11,.35); color: #fbbf24; }
.fb-btn-warning:hover { background: rgba(245,158,11,.28); }
.fb-btn-accent { background: rgba(99,102,241,.22); border-color: rgba(99,102,241,.45); color: #a5b4fc; }
.fb-btn-accent:hover { background: rgba(99,102,241,.32); }
.fb-tools-desktop { display: flex; gap: 6px; align-items: center; }
.fb-tools-mobile { display: none; }
.fb-menu-item {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 12px; color: #f8fafc; background: none; border: none;
    cursor: pointer; width: 100%; text-align: left;
    font-size: .82rem; border-radius: 4px; white-space: nowrap; text-decoration: none;
}
.fb-menu-item:hover { background: rgba(255,255,255,.1); }

#fbCalcPanel { display: none; width: 100%; height: 100%; align-items: center; gap: 0; }
#fbCalcPanel .fb-group { border-right-color: rgba(255,255,255,.08); }
#fbCalcPanel .fb-group.fb-calc-badge { min-width: 80px; }

body.calc-active table tbody tr:hover { cursor: pointer; background: rgba(99,102,241,.07); }
body.calc-active .mov-card:hover { cursor: pointer; outline: 1px solid rgba(99,102,241,.3); }
body.calc-active table tbody tr.row-selected { background: rgba(99,102,241,.12); }
body.calc-active .mov-card.row-selected { outline: 2px solid rgba(99,102,241,.5); background: rgba(99,102,241,.06); }

@media (max-width: 900px) {
    #movFooterBar { left: 0; padding: 0 10px; }
    .fb-group { min-width: 80px; padding: 0 10px; }
    .fb-label { font-size: .6rem; }
    .fb-value { font-size: .8rem; }
    .fb-tools-desktop { display: none; }
    .fb-tools-mobile { display: block; }
}
@media (max-width: 540px) {
    .fb-group.fb-hide-xs { display: none; }
}
</style>

<!-- ── Barra permanente de rodapé ── -->
<div id="movFooterBar">
    <div class="fb-inner">

        <div id="fbDefaultPanel" style="display:flex;align-items:center;width:100%;height:100%">
            <div class="fb-group">
                <span class="fb-label"><i class="fa-solid fa-arrow-trend-up" style="color:#34d399"></i> Entradas</span>
                <span class="fb-value" style="color:#34d399"><?= fmtBrl2($totalEntrada) ?></span>
            </div>
            <div class="fb-group">
                <span class="fb-label"><i class="fa-solid fa-arrow-trend-down" style="color:#f87171"></i> Saídas</span>
                <span class="fb-value" style="color:#f87171"><?= fmtBrl2($totalSaida) ?></span>
            </div>
            <div class="fb-group fb-sep-strong">
                <span class="fb-label">Saldo do Mês</span>
                <span class="fb-value" style="color:<?= $saldoMes >= 0 ? '#34d399' : '#f87171' ?>">
                    <?= ($saldoMes < 0 ? '− ' : '') . fmtBrl2(abs($saldoMes)) ?>
                </span>
            </div>
            <div class="fb-group fb-hide-xs" style="min-width:130px">
                <span class="fb-label" style="opacity:.45">
                    <i class="fa-solid fa-piggy-bank" style="color:<?= $saldoTotal < 0 ? '#f87171' : '#a5b4fc' ?>"></i> Saldo Acumulado
                </span>
                <span class="fb-value" style="color:<?= $saldoTotal < 0 ? '#f87171' : '#a5b4fc' ?>;font-size:.82rem">
                    <?= ($saldoTotal < 0 ? '− ' : '') . fmtBrl2(abs($saldoTotal)) ?>
                </span>
            </div>
            <div class="fb-spacer"></div>
            <div class="fb-actions">
                <?php if (($pendingEntrada ?? 0) + ($pendingSaida ?? 0) > 0): ?>
                <span style="font-size:.7rem;opacity:.55;white-space:nowrap">
                    <i class="fa-solid fa-clock" style="color:#fbbf24"></i>
                    <?= ($pendingEntrada ?? 0) + ($pendingSaida ?? 0) ?> pendente(s)
                </span>
                <?php endif; ?>

                <div class="fb-tools-desktop">
                    <a href="<?= $basePath ?>/relatorios/exportar/movimentos?mes=<?= $mes ?>&ano=<?= $ano ?>" class="fb-btn" title="Exportar CSV">
                        <i class="fa-solid fa-file-csv"></i> Exportar
                    </a>
                    <button class="fb-btn fb-btn-warning" onclick="openModal('modalDividaMovimento')" title="Lançar parcela de dívida">
                        <i class="fa-solid fa-hand-holding-dollar"></i> Lançar Dívida
                    </button>
                    <button class="fb-btn fb-btn-accent" onclick="openModal('modalMovimento')" title="Nova Movimentação">
                        <i class="fa-solid fa-plus"></i> Nova Movimentação
                    </button>
                </div>

                <div class="fb-tools-mobile" style="position:relative">
                    <button class="fb-btn" id="btnToolsMobile" onclick="toggleToolsMenu()" title="Ferramentas">
                        <i class="fa-solid fa-wrench"></i>
                        <i class="fa-solid fa-chevron-up" id="toolsChevron" style="font-size:.55rem;margin-left:2px"></i>
                    </button>
                    <div id="fbToolsMenu" style="display:none;position:fixed;background:var(--sidebar-bg,#0f172a);border:1px solid rgba(255,255,255,.15);border-radius:8px;padding:6px;min-width:195px;box-shadow:0 -4px 16px rgba(0,0,0,.4);z-index:1100">
                        <a href="<?= $basePath ?>/relatorios/exportar/movimentos?mes=<?= $mes ?>&ano=<?= $ano ?>" class="fb-menu-item">
                            <i class="fa-solid fa-file-csv"></i> Exportar CSV
                        </a>
                        <button class="fb-menu-item" onclick="openModal('modalDividaMovimento');closeToolsMenu()">
                            <i class="fa-solid fa-hand-holding-dollar"></i> Lançar Dívida
                        </button>
                        <button class="fb-menu-item" onclick="openModal('modalMovimento');closeToolsMenu()">
                            <i class="fa-solid fa-plus"></i> Nova Movimentação
                        </button>
                    </div>
                </div>

                <button id="btnCalcFooter" class="fb-btn" onclick="toggleCalcMode()" title="Selecionar linhas para calcular">
                    <i class="fa-solid fa-calculator"></i> <span class="hide-mobile">Calcular</span>
                </button>
            </div>
        </div>

        <div id="fbCalcPanel">
            <div class="fb-group fb-calc-badge">
                <span class="fb-label">Selecionados</span>
                <span class="fb-value" id="calcCount">0</span>
            </div>
            <div class="fb-group">
                <span class="fb-label"><i class="fa-solid fa-arrow-trend-up" style="color:#34d399"></i> Entradas</span>
                <span class="fb-value" id="calcEntrada" style="color:#34d399">R$ 0,00</span>
            </div>
            <div class="fb-group">
                <span class="fb-label"><i class="fa-solid fa-arrow-trend-down" style="color:#f87171"></i> Saídas</span>
                <span class="fb-value" id="calcSaida" style="color:#f87171">R$ 0,00</span>
            </div>
            <div class="fb-group fb-sep-strong" style="min-width:120px">
                <span class="fb-label">Saldo Seleção</span>
                <span class="fb-value" id="calcSaldo">R$ 0,00</span>
            </div>
            <div class="fb-spacer"></div>
            <div class="fb-actions">
                <button class="fb-btn fb-btn-active" onclick="toggleCalcMode()">
                    <i class="fa-solid fa-xmark"></i> Sair
                </button>
            </div>
        </div>

    </div>
</div>

<script>
const BASE_PATH = '<?= $basePath ?>';
const MOV_MES   = <?= $mes ?>;
const MOV_ANO   = <?= $ano ?>;
const CSRF_TOKEN = '<?= htmlspecialchars($csrf) ?>';

/* ── Month navigation ── */
function navigateMonth(dir) {
    const params = new URLSearchParams(window.location.search);
    let mes = MOV_MES + dir;
    let ano = MOV_ANO;
    if (mes > 12) { mes = 1;  ano++; }
    if (mes < 1)  { mes = 12; ano--; }
    params.set('mes', mes);
    params.set('ano', ano);
    params.delete('page');
    window.location.href = window.location.pathname + '?' + params.toString();
}

/* ── Mobile filter toggle ── */
function toggleMobileFullFilter() {
    document.getElementById('filterCard')?.classList.toggle('mob-filter-shown');
}

/* ── AJAX Actions ── */
async function validateMovement(id) {
    const csrf = document.querySelector(`[data-id="${id}"] .action-btn-success`)?.dataset.csrf
              || document.querySelector(`[data-id="${id}"] .mov-action-success`)?.dataset.csrf
              || CSRF_TOKEN;
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
    if (data.success) { showToast(data.message, 'success'); setTimeout(() => location.reload(), 800); }
    else showToast(data.message || 'Erro.', 'error');
}

async function revertMovement(id) {
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
        body: `_csrf=${encodeURIComponent(CSRF_TOKEN)}`
    });
    const data = await res.json();
    if (data.success) { showToast(data.message, 'warning'); setTimeout(() => location.reload(), 800); }
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

/* ── Edit Modal ── */
async function openEditModal(id) {
    try {
        const res  = await fetch(`${BASE_PATH}/movimentacoes/${id}/json`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (!data.success) { showToast('Erro ao carregar movimentação.', 'error'); return; }

        const mov = data.movement;

        // Payroll-linked movements → edit via payroll form
        const folhaMatch = (mov.observacao || '').match(/\[FOLHA_ID:(\d+)\]/);
        if (folhaMatch) {
            window.location.href = `${BASE_PATH}/folha-pagamento/${folhaMatch[1]}/editar`;
            return;
        }

        document.getElementById('e_id').value              = mov.id;
        document.getElementById('e_descricao').value       = mov.descricao ?? '';
        document.getElementById('e_data_competencia').value = mov.data_competencia ?? '';
        document.getElementById('e_data_vencimento').value  = mov.data_vencimento  ?? '';
        document.getElementById('e_parcela_atual').value   = mov.parcela_atual  ?? 1;
        document.getElementById('e_total_parcelas').value  = mov.total_parcelas ?? 1;
        document.getElementById('e_observacao').value      = mov.observacao ?? '';

        // valor formatado
        const valorNum = parseFloat(mov.valor || '0');
        document.getElementById('e_valor').value = valorNum.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // tipo radio
        const tipoEl = document.getElementById(mov.tipo === 'entrada' ? 'e_tipo_entrada' : 'e_tipo_saida');
        if (tipoEl) tipoEl.checked = true;

        // modo radio
        const modoMap = { unico: 'e_modo_unico', fixo: 'e_modo_fixo', parcelamento: 'e_modo_parcelado' };
        const modoEl  = document.getElementById(modoMap[mov.modo] || 'e_modo_unico');
        if (modoEl) modoEl.checked = true;
        editUpdateModo(mov.modo ?? 'unico');

        editUpdateCategories(mov.tipo ?? 'saida', mov.categoria_id, mov.subcategoria_id);

        openModal('modalEditar');
    } catch (err) {
        showToast('Erro ao carregar movimentação.', 'error');
    }
}

function editUpdateModo(modo) {
    const row = document.getElementById('e_parcelasRow');
    if (row) row.style.display = modo === 'parcelamento' ? 'flex' : 'none';
}

function editUpdateCategories(tipo, catId, subId) {
    const tipoMap = { entrada: 'receita', saida: 'despesa' };
    const catType = tipoMap[tipo] || 'despesa';
    const catSel  = document.getElementById('e_categoria_id');
    const subSel  = document.getElementById('e_subcategoria_id');
    if (!catSel || !subSel) return;

    catSel.innerHTML = '<option value="">Selecione...</option>';
    subSel.innerHTML = '<option value="">Selecione...</option>';

    (window.categoriesData || []).forEach(cat => {
        if (cat.tipo !== catType) return;
        const opt = document.createElement('option');
        opt.value = cat.id;
        opt.dataset.subs = JSON.stringify(cat.subcategorias || []);
        opt.textContent = cat.nome;
        if (String(cat.id) === String(catId)) opt.selected = true;
        catSel.appendChild(opt);
    });

    const selOpt = catSel.options[catSel.selectedIndex];
    if (selOpt?.dataset.subs) {
        JSON.parse(selOpt.dataset.subs).forEach(sub => {
            const opt = document.createElement('option');
            opt.value = sub.id;
            opt.textContent = sub.nome;
            if (String(sub.id) === String(subId)) opt.selected = true;
            subSel.appendChild(opt);
        });
    }
}

/* ── Modo Calcular ── */
let calcMode = false;

function toggleCalcMode() {
    calcMode = !calcMode;
    const checkCols    = document.querySelectorAll('.calc-check-col');
    const defaultPanel = document.getElementById('fbDefaultPanel');
    const calcPanel    = document.getElementById('fbCalcPanel');
    const btnFooter    = document.getElementById('btnCalcFooter');
    const btnHeader    = document.getElementById('btnCalc');

    document.body.classList.toggle('calc-active', calcMode);
    checkCols.forEach(el => el.style.display = calcMode ? '' : 'none');
    if (defaultPanel) defaultPanel.style.display = calcMode ? 'none' : 'flex';
    if (calcPanel)    calcPanel.style.display    = calcMode ? 'flex' : 'none';
    if (btnFooter) btnFooter.classList.toggle('fb-btn-active', calcMode);
    if (btnHeader) {
        if (calcMode) {
            btnHeader.classList.replace('btn-ghost', 'btn-primary');
            btnHeader.innerHTML = '<i class="fa-solid fa-xmark"></i> <span class="hide-mobile">Cancelar</span>';
        } else {
            btnHeader.classList.replace('btn-primary', 'btn-ghost');
            btnHeader.innerHTML = '<i class="fa-solid fa-calculator"></i> <span class="hide-mobile">Calcular</span>';
            document.querySelectorAll('.mov-calc-checkbox').forEach(cb => { cb.checked = false; });
            document.querySelectorAll('tr.row-selected, .mov-card.row-selected').forEach(el => el.classList.remove('row-selected'));
            updateCalcBar();
        }
    }
}

function updateCalcBar() {
    let count = 0, entrada = 0, saida = 0;
    document.querySelectorAll('.mov-calc-checkbox').forEach(cb => {
        const row = cb.closest('tr') || cb.closest('.mov-card');
        if (cb.checked) {
            count++;
            const val = parseFloat(cb.dataset.valor) || 0;
            if (cb.dataset.tipo === 'entrada') entrada += val; else saida += val;
            row?.classList.add('row-selected');
        } else {
            row?.classList.remove('row-selected');
        }
    });
    const fmt   = v => 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const saldo = entrada - saida;
    document.getElementById('calcCount').textContent   = count;
    document.getElementById('calcEntrada').textContent = fmt(entrada);
    document.getElementById('calcSaida').textContent   = fmt(saida);
    const saldoEl = document.getElementById('calcSaldo');
    if (saldoEl) {
        saldoEl.textContent = (saldo < 0 ? '− ' : '') + fmt(Math.abs(saldo));
        saldoEl.style.color = saldo >= 0 ? '#34d399' : '#f87171';
    }
}

/* ── Tools menu (mobile) ── */
function toggleToolsMenu() {
    const menu    = document.getElementById('fbToolsMenu');
    const btn     = document.getElementById('btnToolsMobile');
    const chevron = document.getElementById('toolsChevron');
    if (!menu || !btn) return;
    const open = menu.style.display === 'none';
    if (open) {
        const rect = btn.getBoundingClientRect();
        menu.style.bottom = (window.innerHeight - rect.top + 6) + 'px';
        menu.style.right  = (window.innerWidth  - rect.right)  + 'px';
        menu.style.left   = 'auto';
    }
    menu.style.display = open ? 'block' : 'none';
    if (chevron) chevron.style.transform = open ? 'rotate(180deg)' : '';
}
function closeToolsMenu() {
    const menu    = document.getElementById('fbToolsMenu');
    const chevron = document.getElementById('toolsChevron');
    if (menu) menu.style.display = 'none';
    if (chevron) chevron.style.transform = '';
}
document.addEventListener('click', e => {
    const btn  = document.getElementById('btnToolsMobile');
    const menu = document.getElementById('fbToolsMenu');
    if (menu && menu.style.display !== 'none' && !btn?.contains(e.target) && !menu.contains(e.target)) closeToolsMenu();
});

/* ── DOMContentLoaded: padding, click delegation, edit form ── */
document.addEventListener('DOMContentLoaded', () => {
    // footer bar padding
    const bar = document.getElementById('movFooterBar');
    if (bar) {
        const h = bar.offsetHeight || 52;
        document.querySelector('.page-content')?.style.setProperty('padding-bottom', (h + 8) + 'px');
    }

    // calc mode: click-to-select desktop table
    document.querySelector('table tbody')?.addEventListener('click', e => {
        if (!calcMode) return;
        if (e.target.closest('a, button, .action-btn, .action-buttons, .mov-calc-checkbox')) return;
        const row = e.target.closest('tr');
        if (!row) return;
        const cb = row.querySelector('.mov-calc-checkbox');
        if (cb) { cb.checked = !cb.checked; updateCalcBar(); }
    });
    // calc mode: click-to-select mobile cards
    document.getElementById('mobileCardList')?.addEventListener('click', e => {
        if (!calcMode) return;
        if (e.target.closest('a, button, .mov-card-actions, .mov-calc-checkbox')) return;
        const card = e.target.closest('.mov-card');
        if (!card) return;
        const cb = card.querySelector('.mov-calc-checkbox');
        if (cb) { cb.checked = !cb.checked; updateCalcBar(); }
    });

    // edit modal: tipo change → update categories
    document.querySelectorAll('#editMovForm input[name="tipo"]').forEach(el => {
        el.addEventListener('change', () => editUpdateCategories(el.value, null, null));
    });
    // edit modal: modo change
    document.querySelectorAll('#editMovForm input[name="modo"]').forEach(el => {
        el.addEventListener('change', () => editUpdateModo(el.value));
    });
    // edit modal: category → subcategories
    document.getElementById('e_categoria_id')?.addEventListener('change', function () {
        const subSel = document.getElementById('e_subcategoria_id');
        if (!subSel) return;
        subSel.innerHTML = '<option value="">Selecione...</option>';
        const sel = this.options[this.selectedIndex];
        if (sel?.dataset.subs) {
            JSON.parse(sel.dataset.subs).forEach(sub => {
                const opt = document.createElement('option');
                opt.value = sub.id;
                opt.textContent = sub.nome;
                subSel.appendChild(opt);
            });
        }
    });
    // edit modal: currency mask
    document.getElementById('e_valor')?.addEventListener('input', function () { currencyMask(this); });

    // edit modal: form submit via AJAX
    document.getElementById('editMovForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const id   = document.getElementById('e_id').value;
        const btn  = document.getElementById('editSaveBtn');
        const orig = btn?.innerHTML;
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Salvando...'; }

        const body = new URLSearchParams(new FormData(this)).toString();
        try {
            const res    = await fetch(`${BASE_PATH}/movimentacoes/${id}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body
            });
            const result = await res.json();
            if (result.success) {
                closeModal('modalEditar');
                showToast(result.message || 'Movimentação atualizada!', 'success');
                setTimeout(() => location.reload(), 700);
            } else {
                showToast(result.message || 'Erro ao salvar.', 'error');
            }
        } catch (err) {
            showToast('Erro ao salvar.', 'error');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = orig; }
        }
    });

    // Mobile infinite scroll
    (function () {
        const cardList = document.getElementById('mobileCardList');
        if (!cardList) return;

        let currentPage = <?= $currentPage ?>;
        const totalPages = <?= $pages ?>;
        if (currentPage >= totalPages) return;

        const loader = document.createElement('div');
        loader.id = 'mobileScrollLoader';
        loader.style.display = 'none';
        loader.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Carregando...';
        cardList.appendChild(loader);

        let loading = false;
        const observer = new IntersectionObserver(async entries => {
            if (!entries[0].isIntersecting || loading || currentPage >= totalPages) return;
            loading = true;
            loader.style.display = 'block';

            const params = new URLSearchParams(window.location.search);
            params.set('page', ++currentPage);

            try {
                const res  = await fetch(window.location.pathname + '?' + params.toString());
                const html = await res.text();
                const doc  = new DOMParser().parseFromString(html, 'text/html');
                doc.querySelectorAll('#mobileCardList .mov-card').forEach(card => {
                    cardList.insertBefore(card, loader);
                });
            } catch (err) { currentPage--; }

            loader.style.display = 'none';
            loading = false;
            if (currentPage >= totalPages) { observer.disconnect(); loader.remove(); }
        }, { rootMargin: '300px' });

        observer.observe(loader);
    })();
});

/* ── Swipe navigation (mobile) ── */
(function () {
    let startX = 0, startY = 0;
    const el = document.getElementById('movListCard') || document.body;
    el.addEventListener('touchstart', e => { startX = e.touches[0].clientX; startY = e.touches[0].clientY; }, { passive: true });
    el.addEventListener('touchend', e => {
        const dx = e.changedTouches[0].clientX - startX;
        const dy = e.changedTouches[0].clientY - startY;
        if (Math.abs(dx) < 60 || Math.abs(dx) < Math.abs(dy) * 1.5) return;
        navigateMonth(dx < 0 ? 1 : -1);
    }, { passive: true });
})();
</script>

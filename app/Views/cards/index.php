<?php
$pageTitle = 'Cartões de Crédito – JW Finanças';
$basePath  = defined('BASE_URL') ? BASE_URL : ($_ENV['APP_BASE_PATH'] ?? '');
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Cartões de Crédito</h1>
        <p class="page-subtitle">Gerencie seus cartões e faturas</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalCartao')">
        <i class="fa-solid fa-plus"></i> Adicionar Cartão
    </button>
</div>

<?php if (empty($cards)): ?>
<div class="empty-state card py-16">
    <i class="fa-solid fa-credit-card fa-3x text-muted"></i>
    <h3 class="mt-4">Nenhum cartão cadastrado</h3>
    <p class="text-muted">Adicione seus cartões de crédito para controlar gastos e faturas.</p>
    <button class="btn btn-primary mt-4" onclick="openModal('modalCartao')">
        <i class="fa-solid fa-plus"></i> Adicionar Cartão
    </button>
</div>
<?php else: ?>

<!-- Cards Grid -->
<div class="credit-cards-grid">
    <?php foreach ($cards as $card): ?>
    <?php
    $usadoPct = $card['limite'] > 0
        ? round(($card['usado'] / $card['limite']) * 100)
        : 0;
    $bandeiraCls = match(strtolower($card['bandeira'])) {
        'mastercard' => 'fa-cc-mastercard',
        'amex'       => 'fa-cc-amex',
        'elo'        => 'fa-credit-card',
        'hipercard'  => 'fa-credit-card',
        default      => 'fa-cc-visa',
    };
    ?>
    <div class="credit-card-wrapper">
        <!-- Visual card -->
        <div class="visual-card" style="background: linear-gradient(135deg, <?= htmlspecialchars($card['cor']) ?>, <?= htmlspecialchars($card['cor']) ?>cc)">
            <div class="vc-header">
                <div class="vc-chip"></div>
                <i class="fa-brands <?= $bandeiraCls ?> vc-brand"></i>
            </div>
            <div class="vc-number">•••• •••• •••• ••••</div>
            <div class="vc-footer">
                <div>
                    <span class="vc-label">Titular</span>
                    <span class="vc-value"><?= htmlspecialchars($card['nome_cartao']) ?></span>
                </div>
                <div>
                    <span class="vc-label">Fecha dia</span>
                    <span class="vc-value"><?= $card['dia_fechamento'] ?></span>
                </div>
                <div>
                    <span class="vc-label">Vence dia</span>
                    <span class="vc-value"><?= $card['dia_vencimento'] ?></span>
                </div>
            </div>
        </div>

        <!-- Card info below visual -->
        <div class="credit-card-info">
            <div class="credit-card-stats">
                <div class="stat">
                    <span class="stat-label">Limite Total</span>
                    <span class="stat-value">R$ <?= number_format((float)$card['limite'], 2, ',', '.') ?></span>
                </div>
                <div class="stat">
                    <span class="stat-label">Usado</span>
                    <span class="stat-value text-danger">R$ <?= number_format($card['usado'], 2, ',', '.') ?></span>
                </div>
                <div class="stat">
                    <span class="stat-label">Disponível</span>
                    <span class="stat-value text-success">R$ <?= number_format($card['disponivel'], 2, ',', '.') ?></span>
                </div>
            </div>

            <!-- Usage bar -->
            <div class="usage-bar">
                <div class="usage-fill" style="width: <?= min(100, $usadoPct) ?>%; background: <?= $usadoPct > 80 ? '#ef4444' : ($usadoPct > 60 ? '#f97316' : htmlspecialchars($card['cor'])) ?>"></div>
            </div>
            <span class="usage-pct text-xs text-muted"><?= $usadoPct ?>% utilizado</span>

            <div class="credit-card-actions">
                <a href="<?= $basePath ?>/cartoes/<?= $card['id'] ?>/movimentos"
                   class="btn btn-primary btn-sm flex-grow">
                    <i class="fa-solid fa-list"></i> Ver Fatura
                </a>
                <button class="btn btn-ghost btn-sm"
                        onclick="editCard(<?= htmlspecialchars(json_encode($card)) ?>)"
                        title="Editar">
                    <i class="fa-solid fa-pencil"></i>
                </button>
                <button type="button" class="btn btn-ghost btn-sm text-danger" title="Remover"
                        onclick="deleteCard(<?= $card['id'] ?>, '<?= htmlspecialchars($csrf) ?>')">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ================================================================
     MODAL - Add/Edit Card
================================================================ -->
<div class="modal-overlay" id="modalCartao" style="display:none">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title" id="cardModalTitle">
                <i class="fa-solid fa-credit-card"></i> Novo Cartão
            </h3>
            <button class="modal-close" onclick="closeModal('modalCartao')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" id="cardForm" action="<?= $basePath ?>/cartoes">
            <div class="modal-body">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

                <div class="form-group">
                    <label class="form-label">Nome / Apelido <span class="required">*</span></label>
                    <input type="text" class="form-control" name="nome_cartao" id="cardNome"
                           placeholder="Ex.: Nubank, Inter Visa..." required>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label class="form-label">Bandeira</label>
                        <select class="form-control" name="bandeira" id="cardBandeira">
                            <option value="visa">Visa</option>
                            <option value="mastercard">Mastercard</option>
                            <option value="elo">Elo</option>
                            <option value="amex">Amex</option>
                            <option value="hipercard">Hipercard</option>
                        </select>
                    </div>
                    <div class="form-group col-6">
                        <label class="form-label">Limite (R$)</label>
                        <input type="text" class="form-control currency-input" name="limite" id="cardLimite"
                               placeholder="R$ 5.000,00">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label class="form-label">Dia de Fechamento</label>
                        <input type="number" class="form-control" name="dia_fechamento" id="cardFechamento"
                               min="1" max="31" value="1">
                    </div>
                    <div class="form-group col-6">
                        <label class="form-label">Dia de Vencimento</label>
                        <input type="number" class="form-control" name="dia_vencimento" id="cardVencimento"
                               min="1" max="31" value="10">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Cor do Cartão</label>
                    <div class="color-picker-row">
                        <?php
                        $colorOptions = ['#6366f1','#8b5cf6','#ec4899','#ef4444','#f97316','#eab308','#22c55e','#06b6d4','#0ea5e9','#1d4ed8','#374151','#111827'];
                        foreach ($colorOptions as $c):
                        ?>
                        <label class="color-dot-label">
                            <input type="radio" name="cor" value="<?= $c ?>" class="color-radio" <?= $c === '#6366f1' ? 'checked' : '' ?>>
                            <span class="color-dot" style="background:<?= $c ?>"></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modalCartao')">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> Salvar Cartão
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const BASE_PATH = '<?= $basePath ?>';

document.addEventListener('DOMContentLoaded', function () {
    const limiteInput = document.getElementById('cardLimite');
    if (limiteInput) limiteInput.addEventListener('input', function () { currencyMask(this); });
});

function editCard(data) {
    document.getElementById('cardModalTitle').innerHTML = '<i class="fa-solid fa-pencil"></i> Editar Cartão';
    document.getElementById('cardForm').action = BASE_PATH + '/cartoes/' + data.id;
    document.getElementById('cardNome').value       = data.nome_cartao || '';
    document.getElementById('cardBandeira').value   = data.bandeira || 'visa';
    document.getElementById('cardLimite').value     = parseFloat(data.limite || 0).toFixed(2).replace('.', ',');
    document.getElementById('cardFechamento').value = data.dia_fechamento || 1;
    document.getElementById('cardVencimento').value = data.dia_vencimento || 10;

    // Set color
    document.querySelectorAll('.color-radio').forEach(r => {
        r.checked = r.value === data.cor;
    });

    openModal('modalCartao');
}

async function deleteCard(id, csrf) {
    const result = await Swal.fire({
        title: 'Remover este cartão?',
        text: 'Todas as compras vinculadas também serão removidas.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, remover',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    });
    if (!result.isConfirmed) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `${BASE_PATH}/cartoes/${id}/excluir`;
    form.innerHTML = `<input type="hidden" name="_csrf" value="${csrf}">`;
    document.body.appendChild(form);
    form.submit();
}
</script>

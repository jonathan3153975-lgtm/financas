<?php
$pageTitle = 'Editar Movimentação – JW Finanças';
$basePath  = defined('BASE_URL') ? BASE_URL : ($_ENV['APP_BASE_PATH'] ?? '');
?>

<div class="page-header">
    <div>
        <a href="<?= $basePath ?>/movimentacoes" class="btn btn-ghost btn-sm mb-2">
            <i class="fa-solid fa-arrow-left"></i> Voltar para Movimentações
        </a>
        <h1 class="page-title">Editar Movimentação</h1>
        <p class="page-subtitle">
            Criada em <?= date('d/m/Y', strtotime($movement['created_at'] ?? 'now')) ?>
        </p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fa-solid fa-pencil"></i> Alterar dados
        </h3>
    </div>

    <form method="POST" action="<?= $basePath ?>/movimentacoes/<?= (int)$movement['id'] ?>" id="editMovForm">
        <div class="card-body">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
            <?php require BASE_PATH . '/app/Views/movements/form.php'; ?>
        </div>
        <div class="card-footer d-flex gap-2 justify-end">
            <a href="<?= $basePath ?>/movimentacoes" class="btn btn-ghost">
                <i class="fa-solid fa-xmark"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save"></i> Salvar Alterações
            </button>
        </div>
    </form>
</div>

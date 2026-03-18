<?php
$pageTitle = 'Esqueci minha senha – JW Finanças';
$basePath  = $_ENV['APP_BASE_PATH'] ?? '/financas/public';
?>
<div class="auth-centered">
    <div class="auth-simple-card">
        <div class="auth-card-logo">
            <div class="auth-card-logo-icon">
                <i class="fa-solid fa-wallet"></i>
            </div>
        </div>

        <div class="auth-icon-lg">
            <i class="fa-solid fa-key"></i>
        </div>

        <h2 class="auth-card-title">Redefinir senha</h2>
        <p class="auth-card-subtitle">
            Informe seu CPF cadastrado e enviaremos um link de redefinição para o e-mail associado.
        </p>

        <?php if (!empty($flash['error'])): ?>
        <div class="flash flash-error mb-4">
            <i class="fa-solid fa-circle-xmark"></i>
            <?= htmlspecialchars($flash['error']) ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($flash['success'])): ?>
        <div class="flash flash-success mb-4">
            <i class="fa-solid fa-circle-check"></i>
            <?= htmlspecialchars($flash['success']) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= $basePath ?>/forgot-password" id="forgotForm">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

            <div class="form-group">
                <label class="form-label" for="cpf">CPF</label>
                <div class="input-wrapper">
                    <input
                        type="text"
                        class="form-control"
                        id="cpf"
                        name="cpf"
                        placeholder="000.000.000-00"
                        maxlength="14"
                        inputmode="numeric"
                        required
                    >
                    <span class="input-icon"><i class="fa-solid fa-id-card"></i></span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
                <i class="fa-solid fa-paper-plane"></i> Enviar link de redefinição
            </button>
        </form>

        <p class="auth-card-footer">
            <a href="<?= $basePath ?>/">
                <i class="fa-solid fa-arrow-left"></i> Voltar ao login
            </a>
        </p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const cpfInput = document.getElementById('cpf');
    if (cpfInput) {
        cpfInput.addEventListener('input', function () { cpfMask(this); });
    }
});
</script>

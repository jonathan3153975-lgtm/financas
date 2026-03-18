<?php
$pageTitle = 'Redefinir senha – JW Finanças';
$basePath  = $_ENV['APP_BASE_PATH'] ?? '/financas/public';
?>
<div class="auth-centered">
    <div class="auth-simple-card">
        <div class="auth-card-logo">
            <div class="auth-card-logo-icon">
                <i class="fa-solid fa-wallet"></i>
            </div>
        </div>

        <div class="auth-icon-lg auth-icon-green">
            <i class="fa-solid fa-lock-open"></i>
        </div>

        <h2 class="auth-card-title">Nova senha</h2>
        <p class="auth-card-subtitle">Crie uma senha forte com pelo menos 8 caracteres.</p>

        <?php if (!empty($flash['error'])): ?>
        <div class="flash flash-error mb-4">
            <i class="fa-solid fa-circle-xmark"></i>
            <?= htmlspecialchars($flash['error']) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= $basePath ?>/reset-password" id="resetForm">
            <input type="hidden" name="_csrf"  value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="token"  value="<?= htmlspecialchars($token ?? '') ?>">

            <div class="form-group">
                <label class="form-label" for="nova_senha">Nova senha</label>
                <div class="input-wrapper">
                    <input
                        type="password"
                        class="form-control"
                        id="nova_senha"
                        name="nova_senha"
                        placeholder="••••••••"
                        minlength="8"
                        required
                    >
                    <button type="button" class="input-toggle-pass" onclick="togglePass('nova_senha','eye1')">
                        <i class="fa-solid fa-eye" id="eye1"></i>
                    </button>
                </div>
                <!-- Password strength indicator -->
                <div class="password-strength mt-2" id="strengthBar">
                    <div class="strength-bar-fill" id="strengthFill"></div>
                </div>
                <span class="strength-label" id="strengthLabel"></span>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirmar_senha">Confirmar nova senha</label>
                <div class="input-wrapper">
                    <input
                        type="password"
                        class="form-control"
                        id="confirmar_senha"
                        name="confirmar_senha"
                        placeholder="••••••••"
                        minlength="8"
                        required
                    >
                    <button type="button" class="input-toggle-pass" onclick="togglePass('confirmar_senha','eye2')">
                        <i class="fa-solid fa-eye" id="eye2"></i>
                    </button>
                </div>
                <span class="form-error" id="confirmError"></span>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="fa-solid fa-check"></i> Redefinir senha
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
function togglePass(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const novaSenha   = document.getElementById('nova_senha');
    const confirmSenha = document.getElementById('confirmar_senha');
    const fill        = document.getElementById('strengthFill');
    const label       = document.getElementById('strengthLabel');

    novaSenha.addEventListener('input', function () {
        const strength = checkPasswordStrength(this.value);
        fill.style.width = (strength.score * 25) + '%';
        fill.style.background = strength.color;
        label.textContent = strength.label;
        label.style.color = strength.color;
    });

    confirmSenha.addEventListener('input', function () {
        const err = document.getElementById('confirmError');
        if (this.value && this.value !== novaSenha.value) {
            err.textContent = 'As senhas não conferem.';
        } else {
            err.textContent = '';
        }
    });

    function checkPasswordStrength(password) {
        let score = 0;
        if (password.length >= 8) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^a-zA-Z0-9]/.test(password)) score++;

        const levels = [
            { label: 'Muito fraca', color: '#ef4444' },
            { label: 'Fraca',       color: '#f97316' },
            { label: 'Média',       color: '#eab308' },
            { label: 'Forte',       color: '#22c55e' },
            { label: 'Muito forte', color: '#10b981' },
        ];
        return { ...levels[score], score };
    }
});
</script>

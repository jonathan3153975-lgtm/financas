<?php
$pageTitle = 'Login – JW Finanças Pessoais';
$basePath  = defined('BASE_URL') ? BASE_URL : ($_ENV['APP_BASE_PATH'] ?? '');
?>
<div class="auth-split">

    <!-- ============================================================
         LEFT PANEL - Branding / Features
    ============================================================ -->
    <div class="auth-left">
        <!-- Animated geometric shapes -->
        <div class="auth-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
            <div class="shape shape-4"></div>
        </div>

        <div class="auth-left-content">
            <!-- Logo -->
            <div class="auth-brand">
                <div class="auth-brand-icon">
                    <i class="fa-solid fa-wallet"></i>
                </div>
                <h1 class="auth-brand-name">JW Finanças</h1>
                <p class="auth-brand-tagline">Controle total das suas finanças pessoais</p>
            </div>

            <!-- Feature highlights -->
            <div class="auth-features">
                <div class="auth-feature">
                    <div class="auth-feature-icon">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <div>
                        <h3>Dashboard Inteligente</h3>
                        <p>Visualize receitas, despesas e saldo em tempo real com gráficos interativos.</p>
                    </div>
                </div>
                <div class="auth-feature">
                    <div class="auth-feature-icon">
                        <i class="fa-solid fa-credit-card"></i>
                    </div>
                    <div>
                        <h3>Controle de Cartões</h3>
                        <p>Gerencie múltiplos cartões de crédito, faturas e parcelamentos com facilidade.</p>
                    </div>
                </div>
                <div class="auth-feature">
                    <div class="auth-feature-icon">
                        <i class="fa-solid fa-repeat"></i>
                    </div>
                    <div>
                        <h3>Lançamentos Automáticos</h3>
                        <p>Cadastre despesas fixas e deixe o sistema gerá-las automaticamente todo mês.</p>
                    </div>
                </div>
                <div class="auth-feature">
                    <div class="auth-feature-icon">
                        <i class="fa-solid fa-file-export"></i>
                    </div>
                    <div>
                        <h3>Relatórios Completos</h3>
                        <p>Analise seus gastos por categoria, período e exporte para CSV.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wave decoration -->
        <div class="auth-wave">
            <svg viewBox="0 0 1440 120" preserveAspectRatio="none">
                <path d="M0,60 C360,120 1080,0 1440,60 L1440,120 L0,120 Z" fill="rgba(255,255,255,0.05)"/>
            </svg>
        </div>
    </div>

    <!-- ============================================================
         RIGHT PANEL - Login Form
    ============================================================ -->
    <div class="auth-right">
        <div class="auth-card">

            <!-- Small logo for mobile / top of form -->
            <div class="auth-card-logo">
                <div class="auth-card-logo-icon">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>

            <h2 class="auth-card-title">Bem-vindo de volta</h2>
            <p class="auth-card-subtitle">Entre com seus dados para continuar</p>

            <!-- Flash messages -->
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

            <!-- Login Form -->
            <form method="POST" action="<?= $basePath ?>/login" id="loginForm" novalidate>
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

                <!-- CPF -->
                <div class="form-group">
                    <label class="form-label" for="cpf">
                        <i class="fa-solid fa-id-card"></i> CPF
                    </label>
                    <div class="input-wrapper">
                        <input
                            type="text"
                            class="form-control"
                            id="cpf"
                            name="cpf"
                            placeholder="000.000.000-00"
                            maxlength="14"
                            autocomplete="username"
                            inputmode="numeric"
                            required
                        >
                        <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                    </div>
                    <span class="form-error" id="cpfError"></span>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <div class="form-label-row">
                        <label class="form-label" for="senha">
                            <i class="fa-solid fa-lock"></i> Senha
                        </label>
                        <a href="<?= $basePath ?>/forgot-password" class="form-forgot-link">Esqueci minha senha</a>
                    </div>
                    <div class="input-wrapper">
                        <input
                            type="password"
                            class="form-control"
                            id="senha"
                            name="senha"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="input-toggle-pass" id="togglePassword" aria-label="Mostrar/ocultar senha">
                            <i class="fa-solid fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                    <span class="form-error" id="senhaError"></span>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn btn-primary btn-block btn-loading" id="loginBtn">
                    <span class="btn-text">
                        <i class="fa-solid fa-right-to-bracket"></i> Entrar
                    </span>
                    <span class="btn-spinner hidden">
                        <i class="fa-solid fa-circle-notch fa-spin"></i> Aguarde...
                    </span>
                </button>
            </form>

            <p class="auth-card-footer">
                Novo por aqui?
                <a href="#">Solicite seu acesso</a>
            </p>
        </div>
    </div>

</div>

<script>
// CPF mask on load
document.addEventListener('DOMContentLoaded', function () {
    const cpfInput = document.getElementById('cpf');
    if (cpfInput) {
        cpfInput.addEventListener('input', function () {
            cpfMask(this);
        });
    }

    // Toggle password visibility
    const toggleBtn = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('senha');
    const eyeIcon = document.getElementById('eyeIcon');
    if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener('click', function () {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            eyeIcon.classList.toggle('fa-eye', !isPassword);
            eyeIcon.classList.toggle('fa-eye-slash', isPassword);
        });
    }

    // Form loading state
    const form = document.getElementById('loginForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            const btn = document.getElementById('loginBtn');
            if (btn) {
                btn.querySelector('.btn-text').classList.add('hidden');
                btn.querySelector('.btn-spinner').classList.remove('hidden');
                btn.disabled = true;
            }
        });
    }
});
</script>

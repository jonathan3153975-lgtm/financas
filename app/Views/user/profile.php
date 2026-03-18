<?php
$pageTitle = 'Meu Perfil – JW Finanças';
$basePath  = $_ENV['APP_BASE_PATH'] ?? '/financas/public';
$ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Meu Perfil</h1>
        <p class="page-subtitle">Gerencie suas informações pessoais</p>
    </div>
</div>

<div class="profile-layout">

    <!-- Profile info card -->
    <div class="profile-sidebar">
        <div class="card text-center">
            <div class="card-body py-8">
                <div class="profile-avatar">
                    <?= strtoupper(substr($user['nome'] ?? 'U', 0, 1)) ?>
                </div>
                <h3 class="mt-4 font-semibold"><?= htmlspecialchars($user['nome'] ?? '') ?></h3>
                <p class="text-muted text-sm"><?= htmlspecialchars($user['email'] ?? '') ?></p>
                <p class="text-muted text-sm">CPF: <?= htmlspecialchars($user['cpf'] ?? '') ?></p>

                <div class="mt-4">
                    <span class="badge badge-success">
                        <i class="fa-solid fa-circle"></i> Ativo
                    </span>
                </div>

                <div class="mt-4 text-sm text-muted">
                    Membro desde <?= date('d/m/Y', strtotime($user['dt_cadastro'] ?? 'now')) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="profile-content">

        <!-- Flash messages -->
        <?php if (!empty($flash['success'])): ?>
        <div class="flash flash-success mb-4">
            <i class="fa-solid fa-circle-check"></i>
            <?= htmlspecialchars($flash['success']) ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($flash['error'])): ?>
        <div class="flash flash-error mb-4">
            <i class="fa-solid fa-circle-xmark"></i>
            <?= htmlspecialchars($flash['error']) ?>
        </div>
        <?php endif; ?>

        <!-- Profile form -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-user-pen"></i> Dados Pessoais</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= $basePath ?>/perfil">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

                    <div class="form-row">
                        <div class="form-group col-8">
                            <label class="form-label">Nome completo <span class="required">*</span></label>
                            <input type="text" class="form-control" name="nome"
                                   value="<?= htmlspecialchars($user['nome'] ?? '') ?>" required>
                        </div>
                        <div class="form-group col-4">
                            <label class="form-label">Telefone</label>
                            <input type="text" class="form-control phone-input" name="telefone"
                                   placeholder="(11) 99999-9999"
                                   value="<?= htmlspecialchars($user['telefone'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label class="form-label">E-mail <span class="required">*</span></label>
                            <input type="email" class="form-control" name="email"
                                   value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                        </div>
                        <div class="form-group col-6">
                            <label class="form-label">Data de Nascimento</label>
                            <input type="date" class="form-control" name="data_nascimento"
                                   value="<?= htmlspecialchars($user['data_nascimento'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-8">
                            <label class="form-label">Município</label>
                            <input type="text" class="form-control" name="municipio"
                                   value="<?= htmlspecialchars($user['municipio'] ?? '') ?>">
                        </div>
                        <div class="form-group col-4">
                            <label class="form-label">UF</label>
                            <select class="form-control" name="uf">
                                <option value="">—</option>
                                <?php foreach ($ufs as $uf): ?>
                                <option value="<?= $uf ?>" <?= ($user['uf'] ?? '') === $uf ? 'selected' : '' ?>><?= $uf ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Password change -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-lock"></i> Alterar Senha</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= $basePath ?>/perfil/senha">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

                    <div class="form-group">
                        <label class="form-label">Senha Atual <span class="required">*</span></label>
                        <div class="input-wrapper">
                            <input type="password" class="form-control" name="senha_atual"
                                   placeholder="••••••••" required>
                            <button type="button" class="input-toggle-pass" onclick="togglePassField('senha_atual')">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label class="form-label">Nova Senha <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <input type="password" class="form-control" name="nova_senha"
                                       placeholder="Mínimo 8 caracteres" minlength="8" required
                                       id="novaSenha">
                                <button type="button" class="input-toggle-pass" onclick="togglePassField('novaSenha')">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group col-6">
                            <label class="form-label">Confirmar Nova Senha <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <input type="password" class="form-control" name="confirmar_senha"
                                       placeholder="Repita a senha" minlength="8" required
                                       id="confirmarSenha">
                                <button type="button" class="input-toggle-pass" onclick="togglePassField('confirmarSenha')">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <span class="form-error" id="confirmPassError"></span>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-warning">
                            <i class="fa-solid fa-key"></i> Alterar Senha
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
function togglePassField(id) {
    const input = document.getElementById(id);
    if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
}

document.addEventListener('DOMContentLoaded', function () {
    const confirm = document.getElementById('confirmarSenha');
    const nova    = document.getElementById('novaSenha');

    if (confirm && nova) {
        confirm.addEventListener('input', function () {
            const err = document.getElementById('confirmPassError');
            err.textContent = this.value && this.value !== nova.value ? 'As senhas não conferem.' : '';
        });
    }

    // Phone mask
    const phoneInput = document.querySelector('.phone-input');
    if (phoneInput) {
        phoneInput.addEventListener('input', function () { phoneMask(this); });
    }
});
</script>

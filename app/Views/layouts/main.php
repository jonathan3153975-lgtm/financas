<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'JW Finanças Pessoais') ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <!-- App CSS -->
    <?php $basePath = defined('BASE_URL') ? BASE_URL : ($_ENV['APP_BASE_PATH'] ?? ''); ?>
    <link rel="stylesheet" href="<?= $basePath ?>/css/style.css">
</head>
<body>

<!-- ================================================================
     SIDEBAR
================================================================ -->
<aside class="sidebar" id="sidebar">
    <!-- Logo -->
    <div class="sidebar-logo">
        <div class="sidebar-logo-icon">
            <i class="fa-solid fa-wallet"></i>
        </div>
        <span class="sidebar-logo-text">JW Finanças</span>
        <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Fechar menu">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <?php
        $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $isActive = function(string $path) use ($currentUri, $basePath): bool {
            $full = $basePath . $path;
            if ($path === '/dashboard') {
                return str_starts_with($currentUri, $full);
            }
            return str_starts_with($currentUri, $full);
        };
        ?>

        <div class="nav-group">
            <span class="nav-group-label">Principal</span>
            <a href="<?= $basePath ?>/dashboard" class="nav-item <?= $isActive('/dashboard') ? 'active' : '' ?>">
                <i class="fa-solid fa-chart-pie nav-icon"></i>
                <span>Dashboard</span>
            </a>
        </div>

        <div class="nav-group">
            <span class="nav-group-label">Financeiro</span>
            <a href="<?= $basePath ?>/movimentacoes" class="nav-item <?= $isActive('/movimentacoes') ? 'active' : '' ?>">
                <i class="fa-solid fa-arrow-right-arrow-left nav-icon"></i>
                <span>Movimentações</span>
            </a>
            <a href="<?= $basePath ?>/movimentacoes/fixas" class="nav-item <?= str_starts_with($currentUri, $basePath . '/movimentacoes/fixas') ? 'active' : '' ?>">
                <i class="fa-solid fa-repeat nav-icon"></i>
                <span>Fixas / Recorrentes</span>
            </a>
            <a href="<?= $basePath ?>/cartoes" class="nav-item <?= $isActive('/cartoes') ? 'active' : '' ?>">
                <i class="fa-solid fa-credit-card nav-icon"></i>
                <span>Cartões de Crédito</span>
            </a>
            <a href="<?= $basePath ?>/folha-pagamento" class="nav-item <?= $isActive('/folha-pagamento') ? 'active' : '' ?>">
                <i class="fa-solid fa-file-invoice-dollar nav-icon"></i>
                <span>Folha de Pagamento</span>
            </a>
        </div>

        <div class="nav-group">
            <span class="nav-group-label">Análise</span>
            <a href="<?= $basePath ?>/relatorios" class="nav-item <?= $isActive('/relatorios') ? 'active' : '' ?>">
                <i class="fa-solid fa-chart-bar nav-icon"></i>
                <span>Relatórios</span>
            </a>
        </div>

        <div class="nav-group">
            <span class="nav-group-label">Conta</span>
            <a href="<?= $basePath ?>/perfil" class="nav-item <?= $isActive('/perfil') ? 'active' : '' ?>">
                <i class="fa-solid fa-user nav-icon"></i>
                <span>Meu Perfil</span>
            </a>
        </div>
    </nav>

    <!-- Sidebar footer -->
    <div class="sidebar-footer">
        <?php $usr = $this->getUser(); ?>
        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <?= strtoupper(substr($usr['nome'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?= htmlspecialchars($usr['nome'] ?? '') ?></span>
                <span class="sidebar-user-email"><?= htmlspecialchars($usr['email'] ?? '') ?></span>
            </div>
        </div>
        <a href="<?= $basePath ?>/logout" class="sidebar-logout" title="Sair">
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>
    </div>
</aside>

<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ================================================================
     MAIN CONTENT WRAPPER
================================================================ -->
<div class="main-wrapper">

    <!-- Top Navbar -->
    <header class="topbar">
        <button class="topbar-hamburger" id="sidebarToggle" aria-label="Abrir menu">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="topbar-title">
            <?= htmlspecialchars($pageTitle ?? 'Dashboard') ?>
        </div>

        <div class="topbar-right">
            <!-- User dropdown -->
            <div class="topbar-user-btn" id="userMenuBtn">
                <div class="topbar-avatar">
                    <?= strtoupper(substr($usr['nome'] ?? 'U', 0, 1)) ?>
                </div>
                <i class="fa-solid fa-chevron-down topbar-chevron"></i>
            </div>

            <div class="user-dropdown" id="userDropdown">
                <div class="user-dropdown-header">
                    <strong><?= htmlspecialchars($usr['nome'] ?? '') ?></strong>
                    <span><?= htmlspecialchars($usr['email'] ?? '') ?></span>
                </div>
                <a href="<?= $basePath ?>/perfil" class="user-dropdown-item">
                    <i class="fa-solid fa-user"></i> Meu Perfil
                </a>
                <div class="user-dropdown-divider"></div>
                <a href="<?= $basePath ?>/logout" class="user-dropdown-item text-danger">
                    <i class="fa-solid fa-right-from-bracket"></i> Sair
                </a>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php
    $flash = $flash ?? $this->getFlash();
    foreach (['success', 'error', 'info', 'warning'] as $type):
        if (!empty($flash[$type])):
    ?>
    <div class="flash flash-<?= $type ?>" role="alert" id="flashMsg">
        <i class="fa-solid <?= match($type) {
            'success' => 'fa-circle-check',
            'error'   => 'fa-circle-xmark',
            'warning' => 'fa-triangle-exclamation',
            default   => 'fa-circle-info',
        } ?>"></i>
        <?= htmlspecialchars($flash[$type]) ?>
        <button class="flash-close" onclick="this.parentElement.remove()">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <?php
        endif;
    endforeach;
    ?>

    <!-- Page Content -->
    <main class="page-content">
        <?= $content ?>
    </main>

</div><!-- /.main-wrapper -->

<!-- App JS -->
<script src="<?= $basePath ?>/js/masks.js"></script>
<script src="<?= $basePath ?>/js/app.js"></script>
</body>
</html>

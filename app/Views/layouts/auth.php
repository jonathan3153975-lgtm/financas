<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'JW Finanças Pessoais') ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous">

    <?php $basePath = defined('BASE_URL') ? BASE_URL : ($_ENV['APP_BASE_PATH'] ?? ''); ?>
    <link rel="stylesheet" href="<?= $basePath ?>/css/style.css">
</head>
<body class="auth-body">

    <?= $content ?>

    <script src="<?= $basePath ?>/js/masks.js"></script>
    <script src="<?= $basePath ?>/js/app.js"></script>
</body>
</html>

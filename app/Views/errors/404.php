<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 – Página não encontrada</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Inter',sans-serif;background:#f1f5f9;display:flex;align-items:center;justify-content:center;min-height:100vh;}
        .wrap{text-align:center;padding:40px;}
        .code{font-size:120px;font-weight:800;color:#6366f1;line-height:1;}
        h1{font-size:24px;color:#1e293b;margin:16px 0 8px;}
        p{color:#64748b;margin-bottom:24px;}
        a{display:inline-block;padding:10px 24px;background:#6366f1;color:#fff;border-radius:8px;font-weight:600;text-decoration:none;}
        a:hover{background:#4f46e5;}
    </style>
</head>
<body>
<div class="wrap">
    <div class="code">404</div>
    <h1>Página não encontrada</h1>
    <p>A página que você está procurando não existe ou foi movida.</p>
    <?php
    $basePath = $_ENV['APP_BASE_PATH'] ?? '/financas/public';
    ?>
    <a href="<?= $basePath ?>/dashboard">Ir para o Dashboard</a>
</div>
</body>
</html>

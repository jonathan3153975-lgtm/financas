<?php
// Raiz do projeto (um nível acima de public/)
defined('BASE_PATH') || define('BASE_PATH', dirname(__DIR__));

// -----------------------------------------------------------------
// Calcula BASE_URL para instalação em subpasta.
// Preferência: APP_BASE_PATH do ambiente.
// Fallback: deriva do SCRIPT_NAME e remove /public quando presente.
// -----------------------------------------------------------------
$appBasePath = trim((string) ($_ENV['APP_BASE_PATH'] ?? ''));

if ($appBasePath !== '') {
    define('BASE_URL', rtrim($appBasePath, '/'));
} else {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));

    if (str_ends_with($scriptDir, '/public')) {
        $scriptDir = substr($scriptDir, 0, -strlen('/public'));
    }

    define('BASE_URL', rtrim($scriptDir, '/'));
}

require BASE_PATH . '/index.php';

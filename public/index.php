<?php
// Raiz do projeto (um nível acima de public/)
defined('BASE_PATH') || define('BASE_PATH', dirname(__DIR__));

// -----------------------------------------------------------------
// Calcula BASE_URL usando comparação de filesystem.
// Normaliza backslashes e faz comparação case-insensitive
// para compatibilidade com Windows (XAMPP, Laragon, etc.)
// -----------------------------------------------------------------
$docRoot   = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
$publicDir = str_replace('\\', '/', realpath(__DIR__) ?: '');

if ($docRoot !== '' && $publicDir !== '' && stripos($publicDir, $docRoot) === 0) {
    // public/ está dentro do document root
    $rel = substr($publicDir, strlen($docRoot));
    define('BASE_URL', rtrim($rel, '/'));
} else {
    // Fallback: deriva do SCRIPT_NAME
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php');
    define('BASE_URL', rtrim(str_replace('\\', '/', $scriptDir), '/'));
}

require BASE_PATH . '/index.php';

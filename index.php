<?php declare(strict_types=1);

/**
 * JW Finanças Pessoais - Application Entry Point
 */

defined('BASE_PATH') || define('BASE_PATH', __DIR__);

// ----------------------------------------------------------------
// Autoload (Composer)
// ----------------------------------------------------------------
$autoload = BASE_PATH . '/vendor/autoload.php';

if (!file_exists($autoload)) {
    http_response_code(500);
    die(
        '<h1>Dependências não instaladas.</h1>' .
        '<p>Execute: <code>composer install</code></p>'
    );
}

require $autoload;

// ----------------------------------------------------------------
// Environment variables (.env)
// ----------------------------------------------------------------
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

// ----------------------------------------------------------------
// Error reporting based on APP_DEBUG
// ----------------------------------------------------------------
if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// ----------------------------------------------------------------
// Session & Router bootstrap
// ----------------------------------------------------------------
use App\Core\Session;
use App\Core\Router;

$session = new Session();
$router  = new Router($session);

// ----------------------------------------------------------------
// Load routes
// ----------------------------------------------------------------
require BASE_PATH . '/routes/web.php';

// ----------------------------------------------------------------
// Dispatch
// ----------------------------------------------------------------
$router->dispatch();

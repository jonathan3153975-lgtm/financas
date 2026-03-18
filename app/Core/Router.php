<?php declare(strict_types=1);

namespace App\Core;

/**
 * Router - Simple front-controller router.
 *
 * Supports:
 *  - GET / POST route registration
 *  - Named route parameters  (/users/{id})
 *  - Auth middleware per route
 *  - 404 fallback
 */
class Router
{
    /** @var array<int, array{method:string, pattern:string, action:mixed, auth:bool}> */
    private array $routes = [];

    private Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    // ----------------------------------------------------------------
    // Route registration
    // ----------------------------------------------------------------

    public function get(string $uri, mixed $action, bool $auth = true): void
    {
        $this->add('GET', $uri, $action, $auth);
    }

    public function post(string $uri, mixed $action, bool $auth = true): void
    {
        $this->add('POST', $uri, $action, $auth);
    }

    private function add(string $method, string $uri, mixed $action, bool $auth): void
    {
        $this->routes[] = [
            'method'  => strtoupper($method),
            'pattern' => $this->compilePattern($uri),
            'action'  => $action,
            'auth'    => $auth,
            'uri'     => $uri,
        ];
    }

    /** Convert /route/{id} to a regex pattern. */
    private function compilePattern(string $uri): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $uri);
        return '#^' . $pattern . '$#';
    }

    // ----------------------------------------------------------------
    // Dispatch
    // ----------------------------------------------------------------

    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD']);
        $uri    = $this->parseUri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (!preg_match($route['pattern'], $uri, $matches)) {
                continue;
            }

            // Extract named params
            $params = array_filter(
                $matches,
                fn($k) => !is_int($k),
                ARRAY_FILTER_USE_KEY
            );

            // Auth middleware
            if ($route['auth'] && !$this->isAuthenticated()) {
                $this->session->set('redirect_after_login', $uri);
                $this->redirectTo('/login');
                return;
            }

            $this->callAction($route['action'], $params);
            return;
        }

        $this->notFound();
    }

    private function parseUri(): string
    {
        $base = defined('BASE_URL') ? BASE_URL : ($_ENV['APP_BASE_PATH'] ?? '');
        $uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        $uri = '/' . ltrim($uri, '/');
        return rtrim($uri, '/') ?: '/';
    }

    /**
     * Call a controller action.
     * Action can be:
     *  - Callable (closure)
     *  - "ControllerClass@method"
     *  - ['ControllerClass', 'method']
     *
     * @param mixed $action
     * @param array<string,string> $params
     */
    private function callAction(mixed $action, array $params): void
    {
        if (is_callable($action)) {
            call_user_func_array($action, array_values($params));
            return;
        }

        if (is_string($action) && str_contains($action, '@')) {
            [$class, $method] = explode('@', $action, 2);
        } elseif (is_array($action) && count($action) === 2) {
            [$class, $method] = $action;
        } else {
            $this->notFound();
            return;
        }

        // Build fully-qualified class name if not already namespaced
        if (!str_contains($class, '\\')) {
            $class = 'App\\Controllers\\' . $class;
        }

        if (!class_exists($class)) {
            $this->notFound();
            return;
        }

        $controller = new $class();

        if (!method_exists($controller, $method)) {
            $this->notFound();
            return;
        }

        call_user_func_array([$controller, $method], array_values($params));
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function isAuthenticated(): bool
    {
        return $this->session->get('user_id') !== null;
    }

    private function redirectTo(string $path): void
    {
        $appPath = defined('BASE_URL') ? BASE_URL : ($_ENV['APP_BASE_PATH'] ?? '');
        // URL relativa: funciona em qualquer porta/domínio
        header('Location: ' . $appPath . $path);
        exit;
    }

    private function notFound(): void
    {
        http_response_code(404);
        if (file_exists(BASE_PATH . '/app/Views/errors/404.php')) {
            require BASE_PATH . '/app/Views/errors/404.php';
        } else {
            echo '<h1>404 - Página não encontrada</h1>';
        }
        exit;
    }
}

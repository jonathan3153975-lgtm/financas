<?php declare(strict_types=1);

namespace App\Core;

/**
 * Base Controller
 */
abstract class Controller
{
    protected Session $session;

    public function __construct()
    {
        $this->session = new Session();
    }

    // ----------------------------------------------------------------
    // View rendering
    // ----------------------------------------------------------------

    /**
     * Render a view file inside a layout.
     *
     * @param string $view   e.g. 'dashboard/index'
     * @param array<string,mixed> $data
     * @param string $layout 'main' | 'auth' | 'none'
     */
    protected function view(string $view, array $data = [], string $layout = 'main'): void
    {
        // Extract data into local scope
        extract($data, EXTR_SKIP);

        $viewPath = BASE_PATH . '/app/Views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View not found: {$viewPath}");
        }

        // Capture view content
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        if ($layout === 'none') {
            echo $content;
            return;
        }

        $layoutPath = BASE_PATH . '/app/Views/layouts/' . $layout . '.php';

        if (!file_exists($layoutPath)) {
            echo $content;
            return;
        }

        require $layoutPath;
    }

    // ----------------------------------------------------------------
    // Response helpers
    // ----------------------------------------------------------------

    protected function redirect(string $url): void
    {
        $appPath = defined('BASE_URL') ? BASE_URL : ($_ENV['APP_BASE_PATH'] ?? '');

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            header('Location: ' . $url);
        } else {
            // URL relativa: funciona em qualquer porta/domínio
            header('Location: ' . $appPath . $url);
        }
        exit;
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // ----------------------------------------------------------------
    // Auth helpers
    // ----------------------------------------------------------------

    protected function isAuthenticated(): bool
    {
        return $this->session->get('user_id') !== null;
    }

    protected function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
        }
    }

    /**
     * @return array<string,mixed>|null
     */
    protected function getUser(): ?array
    {
        return $this->session->get('user') ?? null;
    }

    protected function getUserId(): ?int
    {
        $id = $this->session->get('user_id');
        return $id !== null ? (int) $id : null;
    }

    // ----------------------------------------------------------------
    // Flash messages
    // ----------------------------------------------------------------

    protected function setFlash(string $type, string $message): void
    {
        $this->session->flash($type, $message);
    }

    /**
     * @return array<string,string>
     */
    protected function getFlash(): array
    {
        return [
            'success' => $this->session->getFlash('success') ?? '',
            'error'   => $this->session->getFlash('error')   ?? '',
            'info'    => $this->session->getFlash('info')     ?? '',
            'warning' => $this->session->getFlash('warning')  ?? '',
        ];
    }

    // ----------------------------------------------------------------
    // CSRF helpers
    // ----------------------------------------------------------------

    protected function verifyCsrf(): void
    {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!$this->session->verifyCsrfToken($token)) {
            http_response_code(403);
            die('Ação inválida. Token CSRF inválido.');
        }
    }

    protected function csrfToken(): string
    {
        return $this->session->getCsrfToken();
    }
}

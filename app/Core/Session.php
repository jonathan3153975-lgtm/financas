<?php declare(strict_types=1);

namespace App\Core;

/**
 * Session manager
 */
class Session
{
    private bool $started = false;

    public function __construct()
    {
        $this->start();
    }

    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return;
        }

        $name     = $_ENV['SESSION_NAME']     ?? 'jw_financas';
        $lifetime = (int) ($_ENV['SESSION_LIFETIME'] ?? 120);

        session_name($name);

        session_set_cookie_params([
            'lifetime' => $lifetime * 60,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        $this->started = true;

        // Regenerate ID periodically to prevent fixation
        if (!isset($_SESSION['_last_regen'])) {
            session_regenerate_id(true);
            $_SESSION['_last_regen'] = time();
        } elseif (time() - $_SESSION['_last_regen'] > 300) {
            session_regenerate_id(true);
            $_SESSION['_last_regen'] = time();
        }
    }

    // ----------------------------------------------------------------
    // Get / Set / Remove
    // ----------------------------------------------------------------

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
        $this->started = false;
    }

    // ----------------------------------------------------------------
    // Flash messages
    // ----------------------------------------------------------------

    public function flash(string $key, string $message): void
    {
        $_SESSION['_flash'][$key] = $message;
    }

    public function getFlash(string $key): ?string
    {
        $value = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    /**
     * @return array<string,string>
     */
    public function getAllFlash(): array
    {
        $flash = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flash;
    }

    // ----------------------------------------------------------------
    // CSRF
    // ----------------------------------------------------------------

    public function getCsrfToken(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public function verifyCsrfToken(string $token): bool
    {
        $stored = $_SESSION['_csrf_token'] ?? '';
        return $stored !== '' && hash_equals($stored, $token);
    }

    public function regenerateCsrf(): void
    {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
}

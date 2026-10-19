<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        $path = Config::get('SESSION_PATH', '') ?? '';
        if ($path === '') {
            // Prefer the host's own session folder (never inside the web root); fall back to storage/sessions only if it is unusable.
            $configured = (string) ini_get('session.save_path');
            $dir = $configured !== '' ? (string) preg_replace('/^\d+;(?:\d+;)?/', '', $configured) : sys_get_temp_dir();
            if (!is_dir($dir) || !is_writable($dir)) {
                $path = ROOT . '/storage/sessions';
            }
        }
        if ($path !== '' && is_dir($path) && is_writable($path)) {
            session_save_path($path);
        }
        session_name(Config::get('SESSION_NAME', 'daylight_session') ?? 'daylight_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => Security::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        $p = session_get_cookie_params();
        if (!headers_sent()) {
            setcookie(session_name(), '', [
                'expires' => time() - 3600, 'path' => $p['path'], 'domain' => $p['domain'],
                'secure' => (bool) $p['secure'], 'httponly' => true, 'samesite' => 'Lax',
            ]);
        }
        session_destroy();
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        $v = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $v;
    }
}

<?php
declare(strict_types=1);

namespace App\Core;

final class Security
{
    private static ?string $nonce = null;

    public static function nonce(): string
    {
        if (self::$nonce === null) {
            self::$nonce = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
        }
        return self::$nonce;
    }

    public static function isHttps(): bool
    {
        $https = (string) ($_SERVER['HTTPS'] ?? '');
        if ($https !== '' && strtolower($https) !== 'off') {
            return true;
        }
        if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
            return true;
        }
        if (Config::bool('TRUST_PROXY_HEADERS')) {
            return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
        }
        return false;
    }

    /** Redirects to HTTPS when APP_FORCE_HTTPS is on. Returns true if a redirect was sent. */
    public static function enforceHttps(): bool
    {
        if (!Config::bool('APP_FORCE_HTTPS') || self::isHttps() || PHP_SAPI === 'cli') {
            return false;
        }
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        if ($host === '' || preg_match('/^[A-Za-z0-9.\-:]+$/', $host) !== 1) {
            return false;
        }
        header('Location: https://' . $host . (string) ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
        return true;
    }

    public static function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        header_remove('X-Powered-By');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-" . self::nonce() . "'; "
            . "style-src 'self' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; "
            . "img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
        if (Config::bool('APP_FORCE_HTTPS') && self::isHttps()) {
            header('Strict-Transport-Security: max-age=15552000');
        }
    }
}

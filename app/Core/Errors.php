<?php
declare(strict_types=1);

namespace App\Core;

final class Errors
{
    public static function register(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '0');
        set_error_handler(static function (int $no, string $str, string $file, int $line): bool {
            if (!(error_reporting() & $no)) {
                return false;
            }
            if ($no === E_DEPRECATED || $no === E_USER_DEPRECATED) {
                self::log("Deprecated: {$str} in {$file}:{$line}");
                return true;
            }
            throw new \ErrorException($str, 0, $no, $file, $line);
        });
        set_exception_handler([self::class, 'handle']);
    }

    public static function handle(\Throwable $e): void
    {
        self::log(get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }
        $detail = Config::bool('APP_DEBUG') ? '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>' : '';
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Something went wrong — Daylight</title></head>'
            . '<body style="font-family:system-ui,sans-serif;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0;background:#16130f;color:#f3efe6">'
            . '<div style="max-width:28rem;text-align:center;padding:1rem"><h1 style="font-size:1.25rem">This page didn\'t load</h1>'
            . '<p style="font-size:.875rem;opacity:.7">Something went wrong on our end. You can try refreshing or head back home.</p>'
            . $detail . '<p><a href="/" style="color:#e8a317">Go home</a></p></div></body></html>';
    }

    public static function log(string $message): void
    {
        $dir = ROOT . '/storage/logs';
        $line = '[' . gmdate('Y-m-d H:i:s') . " UTC] " . str_replace(["\r"], '', $message) . "\n";
        if (is_dir($dir) && is_writable($dir)) {
            @file_put_contents($dir . '/app-' . gmdate('Y-m') . '.log', $line, FILE_APPEND | LOCK_EX);
        } else {
            error_log(trim($line));
        }
    }
}

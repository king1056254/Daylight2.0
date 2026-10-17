<?php
declare(strict_types=1);

namespace App\Core;

/** Reads configuration from real environment variables first, then a .env file. */
final class Config
{
    /** @var array<string,string> */
    private static array $values = [];

    public static function load(string $root): void
    {
        $file = $root . '/.env';
        if (is_file($file) && is_readable($file)) {
            self::$values = array_merge(self::$values, self::parse((string) file_get_contents($file)));
        }
        // Fallback for hosts whose file manager cannot create dotfiles: config.local.php returning ['KEY' => 'value'].
        $php = $root . '/config.local.php';
        if (is_file($php) && is_readable($php)) {
            $arr = require $php;
            if (is_array($arr)) {
                foreach ($arr as $k => $v) {
                    if (is_string($k) && preg_match('/^[A-Z][A-Z0-9_]*$/', $k) === 1 && (is_string($v) || is_int($v) || is_bool($v))) {
                        self::$values[$k] = is_bool($v) ? ($v ? 'true' : 'false') : (string) $v;
                    }
                }
            }
        }
    }

    /** @return array<string,string> */
    public static function parse(string $text): array
    {
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $pos));
            $val = trim(substr($line, $pos + 1));
            if (strlen($val) >= 2 && ($val[0] === '"' || $val[0] === "'") && substr($val, -1) === $val[0]) {
                $val = substr($val, 1, -1);
            }
            if (preg_match('/^[A-Z][A-Z0-9_]*$/', $key) === 1) {
                $out[$key] = $val;
            }
        }
        return $out;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $env = getenv($key);
        if ($env !== false && $env !== '') {
            return $env;
        }
        if (isset(self::$values[$key]) && self::$values[$key] !== '') {
            return self::$values[$key];
        }
        return $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = self::get($key);
        if ($v === null) {
            return $default;
        }
        return in_array(strtolower($v), ['1', 'true', 'yes', 'on'], true);
    }

    public static function int(string $key, int $default = 0): int
    {
        $v = self::get($key);
        return ($v !== null && preg_match('/^-?\d+$/', $v) === 1) ? (int) $v : $default;
    }

    /** For tests only. */
    public static function set(string $key, string $value): void
    {
        self::$values[$key] = $value;
    }
}

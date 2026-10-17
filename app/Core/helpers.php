<?php
declare(strict_types=1);

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Security;
use App\Core\View;

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '/'): string
{
    $base = rtrim(Config::get('APP_BASE_PATH', '') ?? '', '/');
    return $base . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = ROOT . '/public/' . ltrim($path, '/');
    $v = is_file($file) ? (string) filemtime($file) : '1';
    return url('/' . ltrim($path, '/')) . '?v=' . $v;
}

function partial(string $view, array $data = []): string
{
    return View::partial($view, $data);
}

function nonce(): string
{
    return Security::nonce();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

/** Lucide-style inline icons (stroke icons, 24x24 grid). */
function icon(string $name, int $size = 16, string $class = ''): string
{
    static $paths = [
        'arrow-right' => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'mail' => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
        'map-pin' => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'menu' => '<line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/>',
        'x' => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'moon' => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
    ];
    $body = $paths[$name] ?? '';
    return '<svg class="icon ' . e($class) . '" xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size
        . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . $body . '</svg>';
}

function status_pill(string $status): string
{
    if (in_array($status, ['Live', 'Paid', 'Completed', 'Resolved', 'Hired', 'Replied'], true)) {
        $tone = 'pill-success';
    } elseif (in_array($status, ['Overdue', 'Cancelled', 'Rejected'], true)) {
        $tone = 'pill-danger';
    } elseif (in_array($status, ['Coming Soon', 'Draft', 'Pending', 'Closed', 'Inactive'], true)) {
        $tone = 'pill-muted';
    } else {
        $tone = 'pill-primary';
    }
    return '<span class="pill ' . $tone . '">' . e($status) . '</span>';
}

function fmt_date(?string $ts): string
{
    if ($ts === null || $ts === '' || strtotime($ts) === false) {
        return '';
    }
    $t = strtotime($ts);
    return '<time datetime="' . e(gmdate('Y-m-d', $t)) . '" data-local-date>' . e(gmdate('n/j/Y', $t)) . '</time>';
}

function money(mixed $amount, string $currency = 'KES'): string
{
    return e($currency) . ' ' . number_format((float) $amount, 2);
}

/** Decode a JSON array column into a list of strings. @return list<string> */
function as_list(mixed $value): array
{
    if (is_array($value)) {
        return array_values(array_map('strval', $value));
    }
    if (!is_string($value) || $value === '') {
        return [];
    }
    $d = json_decode($value, true);
    return is_array($d) ? array_values(array_map('strval', $d)) : [];
}

function current_path(): string
{
    return (string) ($GLOBALS['__daylight_path'] ?? '/');
}

<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function verify(Request $request): bool
    {
        $given = $request->input('_csrf');
        $known = $_SESSION['_csrf'] ?? '';
        return is_string($known) && $known !== '' && $given !== '' && hash_equals($known, $given);
    }
}

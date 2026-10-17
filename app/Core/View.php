<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    /** @param array<string,mixed> $data */
    public static function render(string $view, array $data = [], ?string $layout = 'layout/site'): string
    {
        $content = self::capture($view, $data);
        if ($layout === null) {
            return $content;
        }
        return self::capture($layout, $data + ['content' => $content]);
    }

    /** @param array<string,mixed> $data */
    public static function partial(string $view, array $data = []): string
    {
        return self::capture($view, $data);
    }

    /** @param array<string,mixed> $__data */
    private static function capture(string $__view, array $__data): string
    {
        if (preg_match('#^[a-z0-9_/\-]+$#', $__view) !== 1) {
            throw new \InvalidArgumentException('Invalid view name');
        }
        $__file = ROOT . '/app/Views/' . $__view . '.php';
        if (!is_file($__file)) {
            throw new \RuntimeException('View not found: ' . $__view);
        }
        extract($__data, EXTR_SKIP);
        ob_start();
        try {
            require $__file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}

<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $path = ROOT . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

require ROOT . '/app/Core/helpers.php';

\App\Core\Config::load(ROOT);
date_default_timezone_set(\App\Core\Config::get('APP_TIMEZONE', 'Africa/Nairobi') ?? 'Africa/Nairobi');

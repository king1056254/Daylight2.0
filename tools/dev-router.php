<?php
// Local development only:  php -S localhost:8000 -t public tools/dev-router.php
$path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$file = __DIR__ . '/../public' . $path;
if ($path !== '/' && is_file($file)) {
    return false; // let the built-in server serve CSS/JS/images
}
require __DIR__ . '/../public/index.php';

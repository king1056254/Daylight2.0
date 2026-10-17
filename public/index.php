<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Errors;
use App\Core\Request;
use App\Core\Security;
use App\Core\Session;

Errors::register();
if (Security::enforceHttps()) {
    exit;
}
Security::sendHeaders();
Session::start();

$request = Request::capture();
$GLOBALS['__daylight_path'] = $request->path();

/** @var \App\Core\Router $router */
$router = require ROOT . '/app/routes.php';
$router->dispatch($request)->send();

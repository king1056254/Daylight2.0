<?php
declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\CrudController;
use App\Controllers\FormController;
use App\Controllers\PageController;
use App\Controllers\PortalController;
use App\Controllers\SeoController;
use App\Controllers\SetupController;
use App\Controllers\UsersController;
use App\Core\Router;

$router = new Router();

// ---- Public site (same URLs as the original) ----
$router->get('/', [PageController::class, 'home']);
$router->get('/services', [PageController::class, 'services']);
$router->get('/services/{slug}', [PageController::class, 'service']);
$router->get('/work', [PageController::class, 'work']);
$router->get('/work/{slug}', [PageController::class, 'workDetail']);
$router->get('/products', [PageController::class, 'products']);
$router->get('/industries', [PageController::class, 'industries']);
$router->get('/technologies', [PageController::class, 'technologies']);
$router->get('/about', [PageController::class, 'about']);
$router->get('/blog', [PageController::class, 'blog']);
$router->get('/blog/{slug}', [PageController::class, 'blogPost']);
$router->get('/careers', [PageController::class, 'careers']);
$router->get('/contact', [PageController::class, 'contact']);
$router->get('/quote', [PageController::class, 'quote']);
$router->get('/faq', [PageController::class, 'faq']);
$router->get('/privacy', [PageController::class, 'privacy']);
$router->get('/terms', [PageController::class, 'terms']);

// ---- Search engines ----
$router->get('/sitemap\.xml', [SeoController::class, 'sitemap']);
$router->get('/robots\.txt', [SeoController::class, 'robots']);

// ---- Public forms ----
$router->post('/contact', [FormController::class, 'contact']);
$router->post('/quote', [FormController::class, 'quote']);
$router->post('/careers/apply', [FormController::class, 'apply']);

// ---- Authentication ----
$router->get('/auth', [AuthController::class, 'index']);
$router->post('/auth/login', [AuthController::class, 'login']);
$router->post('/auth/register', [AuthController::class, 'register']);
$router->post('/auth/logout', [AuthController::class, 'logout']);
$router->get('/auth/forgot', [AuthController::class, 'forgotForm']);
$router->post('/auth/forgot', [AuthController::class, 'forgot']);
$router->get('/auth/reset/{token}', [AuthController::class, 'resetForm']);
$router->post('/auth/reset/{token}', [AuthController::class, 'reset']);
$router->get('/auth/verify/{token}', [AuthController::class, 'verify']);

// ---- Client portal ----
$router->get('/portal', [PortalController::class, 'dashboard']);
$router->get('/portal/projects', [PortalController::class, 'projects']);
$router->get('/portal/projects/{id}', [PortalController::class, 'project']);
$router->post('/portal/projects/{id}/messages', [PortalController::class, 'postMessage']);
$router->get('/portal/invoices', [PortalController::class, 'invoices']);
$router->get('/portal/invoices/{id}', [PortalController::class, 'invoiceDetail']);
$router->get('/portal/tickets', [PortalController::class, 'tickets']);
$router->post('/portal/tickets', [PortalController::class, 'createTicket']);

// ---- Admin (fixed routes first, then generic resources) ----
$router->get('/admin', [AdminController::class, 'dashboard']);
$router->post('/admin/demo', [AdminController::class, 'demo']);
$router->get('/admin/users', [UsersController::class, 'index']);
$router->get('/admin/users/new', [UsersController::class, 'create']);
$router->post('/admin/users', [UsersController::class, 'store']);
$router->get('/admin/users/{id}', [UsersController::class, 'edit']);
$router->post('/admin/users/{id}', [UsersController::class, 'update']);

$router->get('/admin/{resource}', [CrudController::class, 'index']);
$router->get('/admin/{resource}/new', [CrudController::class, 'create']);
$router->post('/admin/{resource}', [CrudController::class, 'store']);
$router->get('/admin/{resource}/{id}', [CrudController::class, 'edit']);
$router->post('/admin/{resource}/{id}', [CrudController::class, 'update']);
$router->post('/admin/{resource}/{id}/delete', [CrudController::class, 'destroy']);

// ---- One-time setup (disabled unless SETUP_TOKEN is set) ----
$router->get('/setup', [SetupController::class, 'index']);
$router->post('/setup', [SetupController::class, 'run']);

return $router;

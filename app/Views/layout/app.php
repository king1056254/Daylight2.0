<?php
use App\Core\Auth;

/** @var string $content @var string $area @var list<array{0:string,1:list<array{0:string,1:string}>}> $nav @var string $pageTitle @var array{type:string,text:string}|null $notice */
$user = Auth::user();
$path = current_path();
$home = $area === 'admin' ? '/admin' : '/portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap">
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="icon" href="<?= e(url('/favicon.ico')) ?>" type="image/x-icon">
  <script src="<?= e(asset('js/theme-init.js')) ?>"></script>
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <div class="sidebar-head">
      <a href="<?= e(url($home)) ?>" class="brand">
        <span class="brand-mark">D</span>
        <span class="brand-name">Daylight <span class="muted text-sm font-medium"><?= $area === 'admin' ? 'Admin' : 'Portal' ?></span></span>
      </a>
      <div class="header-actions">
        <button type="button" class="icon-btn" data-theme-toggle aria-label="Switch theme"><?= icon('sun', 16, 'icon-sun') ?><?= icon('moon', 16, 'icon-moon') ?></button>
        <button type="button" class="icon-btn menu-btn" data-menu-toggle aria-controls="sidebar-nav" aria-expanded="false" aria-label="Menu"><?= icon('menu', 16, 'icon-menu') ?><?= icon('x', 16, 'icon-x') ?></button>
      </div>
    </div>
    <div id="sidebar-nav" class="sidebar-collapsible">
      <nav aria-label="<?= $area === 'admin' ? 'Admin' : 'Portal' ?>">
        <?php foreach ($nav as [$group, $links]): ?>
          <h4><?= e($group) ?></h4>
          <?php foreach ($links as [$to, $label]):
              $active = ($to === $home) ? $path === $to : (strncmp($path, $to, strlen($to)) === 0);
          ?>
            <a href="<?= e(url($to)) ?>" class="<?= $active ? 'active' : '' ?>"<?= $active ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </nav>
      <div class="sidebar-foot">
        <?php if ($user !== null): ?><p class="text-sm muted"><?= e($user['email']) ?></p><?php endif; ?>
        <p class="mt-2"><a class="link-quiet" href="<?= e(url('/')) ?>">← Back to website</a></p>
        <?php if ($area === 'admin' && Auth::check()): ?>
          <p class="mt-2"><a class="link-quiet" href="<?= e(url('/portal')) ?>">Client portal view</a></p>
        <?php elseif ($area === 'portal' && Auth::isStaff()): ?>
          <p class="mt-2"><a class="link-quiet" href="<?= e(url('/admin')) ?>">Admin area</a></p>
        <?php endif; ?>
        <form method="post" action="<?= e(url('/auth/logout')) ?>" class="mt-4">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-outline btn-sm btn-block">Sign out</button>
        </form>
      </div>
    </div>
  </aside>
  <main class="app-main">
    <?php if ($notice !== null): ?>
      <div class="alert <?= $notice['type'] === 'error' ? 'alert-error' : 'alert-success' ?>" role="status"><?= e($notice['text']) ?></div>
      <div class="mt-4"></div>
    <?php endif; ?>
    <?= $content ?>
  </main>
</div>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>

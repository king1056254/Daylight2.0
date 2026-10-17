<?php
use App\Core\Auth;

$nav = [
    ['/services', 'Services'], ['/work', 'Work'], ['/products', 'Products'],
    ['/industries', 'Industries'], ['/about', 'About'], ['/blog', 'Insights'],
];
$path = current_path();
$user = Auth::user();
$staff = $user !== null && Auth::isStaff();
$accountUrl = $user === null ? '/auth' : ($staff ? '/admin' : '/portal');
$accountLabel = $user === null ? 'Sign in' : ($staff ? 'Admin' : 'Client portal');
?>
<header class="site-header">
  <div class="header-bar">
    <a href="<?= e(url('/')) ?>" class="brand">
      <span class="brand-mark">D</span>
      <span class="brand-name">Daylight</span>
    </a>

    <nav class="main-nav" aria-label="Main">
      <?php foreach ($nav as [$to, $label]): ?>
        <a href="<?= e(url($to)) ?>" class="<?= strncmp($path, $to, strlen($to)) === 0 ? 'active' : '' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="header-actions">
      <button type="button" class="icon-btn" data-theme-toggle aria-label="Switch theme">
        <?= icon('sun', 16, 'icon-sun') ?><?= icon('moon', 16, 'icon-moon') ?>
      </button>
      <a href="<?= e(url($accountUrl)) ?>" class="hide-below-sm"><span class="btn btn-outline btn-sm"><?= e($accountLabel) ?></span></a>
      <a href="<?= e(url('/quote')) ?>" class="hide-below-sm"><span class="btn btn-sm">Start a project <?= icon('arrow-right', 14) ?></span></a>
      <button type="button" class="icon-btn menu-btn" data-menu-toggle aria-controls="mobile-nav" aria-expanded="false" aria-label="Menu">
        <?= icon('menu', 16, 'icon-menu') ?><?= icon('x', 16, 'icon-x') ?>
      </button>
    </div>
  </div>

  <div class="mobile-nav" id="mobile-nav" hidden>
    <nav aria-label="Mobile">
      <?php foreach ($nav as [$to, $label]): ?>
        <a href="<?= e(url($to)) ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
      <a href="<?= e(url('/contact')) ?>">Contact</a>
      <div class="mobile-actions">
        <a href="<?= e(url('/quote')) ?>"><span class="btn btn-block">Start a project</span></a>
        <a href="<?= e(url($accountUrl)) ?>"><span class="btn btn-outline btn-block"><?= $user !== null ? 'My account' : 'Sign in' ?></span></a>
      </div>
    </nav>
  </div>
</header>

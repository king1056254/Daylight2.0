<?php /** @var string $code @var string $heading @var string $text */ ?>
<div class="bare">
  <div class="bare-box">
    <h1 class="bare-code"><?= e($code) ?></h1>
    <h2><?= e($heading) ?></h2>
    <p><?= e($text) ?></p>
    <div class="mt-6">
      <a href="<?= e(url('/')) ?>" class="btn">Go home</a>
    </div>
  </div>
</div>

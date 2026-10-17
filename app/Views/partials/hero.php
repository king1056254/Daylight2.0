<?php /** @var string $title @var string|null $eyebrow @var string|null $description */ ?>
<section class="hero">
  <div class="grid-backdrop" aria-hidden="true"></div>
  <div class="hero-inner">
    <?php if (!empty($eyebrow)): ?><p class="eyebrow"><?= e($eyebrow) ?></p><?php endif; ?>
    <h1><?= e($title) ?></h1>
    <?php if (!empty($description)): ?><p class="lead"><?= e($description) ?></p><?php endif; ?>
  </div>
</section>

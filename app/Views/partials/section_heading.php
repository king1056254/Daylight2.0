<?php /** @var string $title @var string|null $eyebrow @var string|null $description */ ?>
<div class="section-heading">
  <?php if (!empty($eyebrow)): ?><p class="eyebrow"><?= e($eyebrow) ?></p><?php endif; ?>
  <h2><?= e($title) ?></h2>
  <?php if (!empty($description)): ?><p class="desc"><?= e($description) ?></p><?php endif; ?>
</div>

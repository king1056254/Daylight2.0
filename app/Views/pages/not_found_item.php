<?php /** @var string $heading @var string $backUrl @var string $backLabel */ ?>
<section class="section">
  <h1 class="text-3xl font-semibold"><?= e($heading) ?></h1>
  <a href="<?= e(url($backUrl)) ?>" class="inline-block mt-4 text-primary"><?= e($backLabel) ?></a>
</section>

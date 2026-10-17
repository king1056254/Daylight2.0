<?php /** @var list<array<string,mixed>> $industries */ ?>
<?= partial('partials/hero', ['eyebrow' => 'Industries', 'title' => 'Sectors we know well', 'description' => 'Domain knowledge shortens discovery and keeps the build grounded in how the work actually happens.']) ?>
<section class="section">
  <div class="grid cols-2 cols-3">
    <?php foreach ($industries as $i): ?>
      <article class="card">
        <h2 class="title-lg"><?= e($i['name']) ?></h2>
        <p class="body"><?= e($i['summary']) ?></p>
        <?php if (!empty($i['description'])): ?><p class="body mt-3"><?= e($i['description']) ?></p><?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
</section>

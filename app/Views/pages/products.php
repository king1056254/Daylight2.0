<?php /** @var list<array<string,mixed>> $products */ ?>
<?= partial('partials/hero', ['eyebrow' => 'Products', 'title' => 'Our own SaaS platforms', 'description' => 'Each product started as a problem we solved for a client and then generalised for everyone else.']) ?>
<section class="section">
  <div class="grid cols-2 gap-5">
    <?php foreach ($products as $p): ?>
      <article class="card card-7">
        <?= status_pill((string) $p['status']) ?>
        <h2 class="title-2xl mt-4"><?= e($p['name']) ?></h2>
        <p class="mt-1 text-sm text-primary"><?= e($p['tagline']) ?></p>
        <p class="body mt-4"><?= e($p['description']) ?></p>
        <ul class="checklist checklist-tight mt-5">
          <?php foreach ($p['features'] as $f): ?><li><?= icon('check', 16) ?> <?= e($f) ?></li><?php endforeach; ?>
        </ul>
        <?php if (!empty($p['product_url']) && preg_match('#^https?://#i', (string) $p['product_url']) === 1): ?>
          <a href="<?= e($p['product_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-block mt-6">Visit product</a>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
</section>

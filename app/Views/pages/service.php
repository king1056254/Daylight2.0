<?php /** @var array<string,mixed> $service */ ?>
<?= partial('partials/hero', ['eyebrow' => 'Service', 'title' => (string) $service['title'], 'description' => $service['summary'] ?? null]) ?>
<section class="section split-detail">
  <div>
    <p class="pre-line text-base leading-relaxed muted"><?= e($service['description']) ?></p>
  </div>
  <aside class="aside">
    <h2>What's included</h2>
    <ul class="checklist">
      <?php foreach ($service['features'] as $f): ?>
        <li><?= icon('check', 16) ?> <?= e($f) ?></li>
      <?php endforeach; ?>
    </ul>
    <a href="<?= e(url('/quote')) ?>" class="btn btn-block mt-6">Request a quote</a>
  </aside>
</section>

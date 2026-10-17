<?php /** @var list<array<string,mixed>> $services */ ?>
<?= partial('partials/hero', ['eyebrow' => 'Services', 'title' => 'Engineering teams you can hand a problem to', 'description' => 'We take responsibility for the whole lifecycle — discovery, design, build, launch, and the years after.']) ?>
<section class="section">
  <div class="grid cols-2">
    <?php foreach ($services as $s): ?>
      <a href="<?= e(url('/services/' . $s['slug'])) ?>" class="card card-7 card-link card-hover-border">
        <h2 class="title-xl"><?= e($s['title']) ?></h2>
        <p class="body mt-3"><?= e($s['summary']) ?></p>
        <span class="more mt-5">Details <?= icon('arrow-right', 14) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<?php /** @var list<array<string,mixed>> $projects */ ?>
<?= partial('partials/hero', ['eyebrow' => 'Our work', 'title' => 'Case studies', 'description' => 'A look at the problem, the build and the outcome for systems now running in production.']) ?>
<section class="section">
  <div class="grid cols-2">
    <?php foreach ($projects as $p): ?>
      <a href="<?= e(url('/work/' . $p['slug'])) ?>" class="card card-7 card-link card-hover-lift">
        <div class="meta-row">
          <span class="meta-mono"><?= e($p['industry']) ?></span>
          <?= status_pill((string) $p['status']) ?>
        </div>
        <h2 class="title-xl mt-4"><?= e($p['title']) ?></h2>
        <p class="body"><?= e($p['summary']) ?></p>
        <div class="chips">
          <?php foreach (array_slice($p['technologies'], 0, 5) as $t): ?><span class="chip"><?= e($t) ?></span><?php endforeach; ?>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

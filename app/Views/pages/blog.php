<?php /** @var list<array<string,mixed>> $posts */ ?>
<?= partial('partials/hero', ['eyebrow' => 'Insights', 'title' => 'Notes from the build', 'description' => "What we're learning about software, delivery and technology in the Kenyan market."]) ?>
<section class="section">
  <div class="grid cols-2">
    <?php foreach ($posts as $p): ?>
      <a href="<?= e(url('/blog/' . $p['slug'])) ?>" class="card card-7 card-link card-hover-border">
        <div class="flex items-center gap-3 meta-mono">
          <span class="text-primary"><?= e($p['category']) ?></span>
          <?php if (!empty($p['published_at'])): ?><span><?= fmt_date((string) $p['published_at']) ?></span><?php endif; ?>
        </div>
        <h2 class="title-xl mt-3"><?= e($p['title']) ?></h2>
        <p class="body"><?= e($p['excerpt']) ?></p>
      </a>
    <?php endforeach; ?>
  </div>
</section>

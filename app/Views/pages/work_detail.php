<?php
/** @var array<string,mixed> $project */
$blocks = array_values(array_filter([
    ['The problem', (string) ($project['problem'] ?? '')],
    ['What we built', (string) ($project['solution'] ?? '')],
    ['The outcome', (string) ($project['outcome'] ?? '')],
], static fn (array $b): bool => $b[1] !== ''));
?>
<?= partial('partials/hero', ['eyebrow' => $project['industry'] ?? 'Case study', 'title' => (string) $project['title'], 'description' => $project['summary'] ?? null]) ?>
<section class="section split-detail">
  <div class="space-10">
    <?php foreach ($blocks as [$bt, $bb]): ?>
      <div>
        <h2 class="rule-accent font-display text-xl font-semibold"><?= e($bt) ?></h2>
        <p class="mt-3 pre-line leading-relaxed muted"><?= e($bb) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
  <aside class="aside space-6">
    <div>
      <h3>Client</h3>
      <p class="mt-1 text-sm"><?= e($project['client_name'] ?: 'Confidential') ?></p>
    </div>
    <?php if ($project['technologies'] !== []): ?>
      <div>
        <h3>Stack</h3>
        <div class="chips chips-sm"><?php foreach ($project['technologies'] as $t): ?><span class="chip chip-plain"><?= e($t) ?></span><?php endforeach; ?></div>
      </div>
    <?php endif; ?>
    <?php if ($project['features'] !== []): ?>
      <div>
        <h3>Key features</h3>
        <ul class="bullets mt-2 text-sm muted"><?php foreach ($project['features'] as $f): ?><li>• <?= e($f) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>
    <a href="<?= e(url('/quote')) ?>" class="btn btn-block">Start a similar project</a>
  </aside>
</section>

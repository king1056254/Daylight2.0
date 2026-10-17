<?php /** @var string $title @var string $description @var list<array{0:string,1:string}> $sections */ ?>
<?= partial('partials/hero', ['eyebrow' => 'Legal', 'title' => $title, 'description' => $description]) ?>
<section class="section section-3xl legal space-8">
  <?php foreach ($sections as [$st, $sb]): ?>
    <div>
      <h2><?= e($st) ?></h2>
      <p><?= e($sb) ?></p>
    </div>
  <?php endforeach; ?>
</section>

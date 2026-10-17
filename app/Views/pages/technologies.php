<?php /** @var array<string,list<string>> $groups */ ?>
<?= partial('partials/hero', ['eyebrow' => 'Technologies', 'title' => 'The stack behind the work', 'description' => 'We pick boring, well-supported tools and use them properly.']) ?>
<section class="section">
  <div class="grid cols-2">
    <?php foreach ($groups as $category => $items): ?>
      <div class="card">
        <h2 class="eyebrow"><?= e($category) ?></h2>
        <div class="chips chips-lg">
          <?php foreach ($items as $name): ?><span class="chip chip-outline"><?= e($name) ?></span><?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

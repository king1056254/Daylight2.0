<?php /** @var list<array<string,mixed>> $faqs */ ?>
<?= partial('partials/hero', ['eyebrow' => 'FAQ', 'title' => 'Questions we get asked', 'description' => "If your question isn't here, send it to us and we'll answer it properly."]) ?>
<section class="section section-3xl">
  <div class="accordion">
    <?php foreach ($faqs as $f): ?>
      <details>
        <summary><span><?= e($f['question']) ?></span><?= icon('chevron-down', 16) ?></summary>
        <div class="answer"><?= e($f['answer']) ?></div>
      </details>
    <?php endforeach; ?>
  </div>
  <div class="mt-10"><a href="<?= e(url('/contact')) ?>" class="btn">Ask us something else</a></div>
</section>

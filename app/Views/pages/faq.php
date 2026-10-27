<?php
/** @var list<array<string,mixed>> $faqs */
$order = ['Getting started', 'Pricing', 'Projects', 'Client portal', 'Support', 'Legal', 'Data protection', 'AI assistant'];
$groups = [];
foreach ($faqs as $f) {
    $groups[(string) ($f['category'] ?: 'General')][] = $f;
}
uksort($groups, static function (string $a, string $b) use ($order): int {
    $ia = array_search($a, $order, true);
    $ib = array_search($b, $order, true);
    $ia = $ia === false ? 999 : $ia;
    $ib = $ib === false ? 999 : $ib;
    return $ia === $ib ? strcmp($a, $b) : $ia <=> $ib;
});
$slug = static fn (string $s): string => trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($s)), '-');
?>
<?= partial('partials/hero', ['eyebrow' => 'FAQ', 'title' => 'Questions we get asked', 'description' => "If your question isn't here, send it to us and we'll answer it properly."]) ?>
<section class="section section-3xl">
  <div class="faq-tools">
    <div class="faq-search">
      <label class="sr-only" for="faq-search">Search the questions</label>
      <input id="faq-search" class="input" type="search" placeholder="Search the questions…" autocomplete="off" data-faq-filter>
    </div>
    <nav class="faq-cats" aria-label="Question categories">
      <?php foreach (array_keys($groups) as $cat): ?>
        <a href="#faq-cat-<?= e($slug($cat)) ?>" class="assistant-chip"><?= e($cat) ?></a>
      <?php endforeach; ?>
    </nav>
  </div>
  <p class="faq-empty muted text-sm" data-faq-empty hidden>No question matches your search. Try different words, or ask us directly.</p>

  <?php foreach ($groups as $cat => $items): ?>
    <div class="faq-group" id="faq-cat-<?= e($slug($cat)) ?>" data-faq-group>
      <h2 class="eyebrow"><?= e($cat) ?></h2>
      <div class="accordion">
        <?php foreach ($items as $f): ?>
          <details name="faq" id="faq-<?= e($f['id']) ?>" data-faq-item>
            <summary><span><?= e($f['question']) ?></span><?= icon('chevron-down', 16) ?></summary>
            <div class="answer"><?= e($f['answer']) ?></div>
          </details>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>

  <div class="mt-10"><a href="<?= e(url('/contact')) ?>" class="btn">Ask us something else</a></div>
</section>

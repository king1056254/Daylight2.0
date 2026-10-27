<?php
/**
 * @var array{title:string,version:string,effective:string,intro:list<string>,sections:list<array<string,mixed>>} $doc
 * @var array<string,string> $vars
 */
$sections = $doc['sections'];
?>
<?= partial('partials/hero', ['eyebrow' => 'Legal', 'title' => $doc['title'], 'description' => 'Version ' . $doc['version'] . ' · Last updated ' . $doc['effective']]) ?>
<section class="section section-3xl legal">
  <?php foreach ($doc['intro'] as $para): ?>
    <p class="legal-intro"><?= legal_text($para, $vars) ?></p>
  <?php endforeach; ?>

  <nav class="legal-toc" aria-label="Contents">
    <h2>Contents</h2>
    <ol>
      <?php foreach ($sections as $s): ?>
        <li><a href="#<?= e($s['id']) ?>"><?= e($s['title']) ?></a></li>
      <?php endforeach; ?>
    </ol>
  </nav>

  <?php foreach ($sections as $n => $s): ?>
    <section id="<?= e($s['id']) ?>" class="legal-section">
      <h2><span class="legal-num"><?= (int) $n + 1 ?>.</span> <?= e($s['title']) ?></h2>
      <?php foreach ($s['blocks'] as $b): ?>
        <?php if ($b[0] === 'p'): ?>
          <p><?= legal_text($b[1], $vars) ?></p>
        <?php elseif ($b[0] === 'ul' || $b[0] === 'ol'): ?>
          <<?= e($b[0]) ?> class="legal-list legal-<?= e($b[0]) ?>">
            <?php foreach ($b[1] as $item): ?><li><?= legal_text($item, $vars) ?></li><?php endforeach; ?>
          </<?= e($b[0]) ?>>
        <?php elseif ($b[0] === 'table'): ?>
          <div class="table-wrap legal-table">
            <table class="data-table">
              <thead><tr><?php foreach ($b[1] as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr></thead>
              <tbody>
                <?php foreach ($b[2] as $row): ?>
                  <tr><?php foreach ($row as $ci => $cell): ?><td data-label="<?= e($b[1][$ci] ?? '') ?>"><?= legal_text($cell, $vars) ?></td><?php endforeach; ?></tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </section>
  <?php endforeach; ?>

  <p class="legal-foot">Version <?= e($doc['version']) ?> · effective <?= e($doc['effective']) ?></p>
</section>

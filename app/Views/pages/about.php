<?php
/** @var list<array<string,mixed>> $team */
$values = [
    ['Own the outcome', 'We are not finished when the code ships. We are finished when the system is doing its job for your team.'],
    ['Plain language', 'No jargon in status updates, quotes or contracts. You should always know exactly what you are paying for.'],
    ['Build for the long run', 'Documented, tested, handover-ready code. You are never locked in to us.'],
    ['Local context', 'Mobile money, low-bandwidth realities and Kenyan compliance are designed in from day one.'],
];
?>
<?= partial('partials/hero', ['eyebrow' => 'About us', 'title' => 'A small team that builds serious systems', 'description' => 'Daylight is a Nairobi-based software company. We work with organisations that need software they can depend on every single working day.']) ?>

<section class="section">
  <div class="split-2">
    <div>
      <h2 class="rule-accent font-display text-2xl font-semibold">Our story</h2>
      <p class="mt-4 leading-relaxed muted">We started Daylight because too many businesses were running on spreadsheets, WhatsApp groups and software written for somewhere else. We build systems that fit how our clients actually work — and stay to support them.</p>
    </div>
    <div>
      <h2 class="rule-accent font-display text-2xl font-semibold">How we work</h2>
      <p class="mt-4 leading-relaxed muted">Discovery first, then a fixed scope and quote. Two-week delivery cycles with a demo at the end of each one. Every client gets a portal where they can follow progress, milestones, documents and invoices in one place.</p>
    </div>
  </div>
</section>

<div class="band">
  <section class="section">
    <?= partial('partials/section_heading', ['eyebrow' => 'Values', 'title' => 'What we hold to']) ?>
    <div class="grid cols-2 mt-10">
      <?php foreach ($values as [$vt, $vb]): ?>
        <div class="card card-on-card">
          <h3 class="title-lg"><?= e($vt) ?></h3>
          <p class="body"><?= e($vb) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<?php if ($team !== []): ?>
<section class="section">
  <?= partial('partials/section_heading', ['eyebrow' => 'Team', 'title' => 'The people']) ?>
  <div class="grid cols-3m mt-10">
    <?php foreach ($team as $m): ?>
      <article class="card">
        <h3 class="title-lg"><?= e($m['name']) ?></h3>
        <p class="text-sm text-primary"><?= e($m['role']) ?></p>
        <p class="body mt-3"><?= e($m['bio']) ?></p>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

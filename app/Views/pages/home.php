<?php
/** @var list<array<string,mixed>> $services @var list<array<string,mixed>> $work @var list<array<string,mixed>> $products @var list<array<string,mixed>> $testimonials */
$stats = [['40+', 'Systems shipped'], ['12', 'Industries served'], ['3', 'SaaS products'], ['98%', 'Client retention']];
?>
<section class="hero hero-home">
  <div class="grid-backdrop" aria-hidden="true"></div>
  <div class="hero-inner">
    <p class="eyebrow">Nairobi, Kenya</p>
    <h1>Software that runs the <span class="text-gradient-amber">day-to-day</span> of serious businesses.</h1>
    <p class="lead">We design and build custom business systems, web and mobile applications, and our own SaaS products — then stay on to support and grow them.</p>
    <div class="hero-actions">
      <a href="<?= e(url('/quote')) ?>" class="btn btn-lg">Request a quote <?= icon('arrow-right', 16) ?></a>
      <a href="<?= e(url('/work')) ?>" class="btn btn-lg btn-outline">See our work</a>
    </div>
    <dl class="stats">
      <?php foreach ($stats as [$value, $label]): ?>
        <div><dt><?= e($value) ?></dt><dd><?= e($label) ?></dd></div>
      <?php endforeach; ?>
    </dl>
  </div>
</section>

<section class="section">
  <?= partial('partials/section_heading', ['eyebrow' => 'Services', 'title' => 'What we build', 'description' => 'From first discovery workshop to long-term maintenance, delivered by one accountable team.']) ?>
  <div class="grid cols-2 cols-3 mt-10">
    <?php foreach (array_slice($services, 0, 6) as $s): ?>
      <a href="<?= e(url('/services/' . $s['slug'])) ?>" class="card card-link card-hover-border">
        <h3 class="title-lg"><?= e($s['title']) ?></h3>
        <p class="body"><?= e($s['summary']) ?></p>
        <span class="more">Learn more <?= icon('arrow-right', 14) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<div class="band">
  <section class="section">
    <?= partial('partials/section_heading', ['eyebrow' => 'Selected work', 'title' => 'Systems in production']) ?>
    <div class="grid cols-2 mt-10">
      <?php foreach (array_slice($work, 0, 4) as $p): ?>
        <a href="<?= e(url('/work/' . $p['slug'])) ?>" class="card card-on-card card-link card-hover-lift">
          <div class="meta-row">
            <span class="meta-mono"><?= e($p['industry']) ?></span>
            <?= status_pill((string) $p['status']) ?>
          </div>
          <h3 class="title-xl mt-4"><?= e($p['title']) ?></h3>
          <p class="body"><?= e($p['summary']) ?></p>
        </a>
      <?php endforeach; ?>
    </div>
    <div class="mt-8"><a href="<?= e(url('/work')) ?>" class="btn btn-outline">All case studies</a></div>
  </section>
</div>

<section class="section">
  <?= partial('partials/section_heading', ['eyebrow' => 'Our products', 'title' => 'SaaS we own and operate', 'description' => 'Products born from problems we kept meeting in client projects.']) ?>
  <div class="grid cols-3m mt-10">
    <?php foreach ($products as $p): ?>
      <div class="card">
        <?= status_pill((string) $p['status']) ?>
        <h3 class="title-lg mt-4"><?= e($p['name']) ?></h3>
        <p class="body"><?= e($p['tagline']) ?></p>
        <ul class="checklist">
          <?php foreach (array_slice($p['features'], 0, 3) as $f): ?>
            <li><?= icon('check', 16) ?> <?= e($f) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<?php if ($testimonials !== []): ?>
<div class="band-top">
  <section class="section">
    <?= partial('partials/section_heading', ['eyebrow' => 'Clients', 'title' => 'What people say']) ?>
    <div class="grid cols-3m mt-10">
      <?php foreach (array_slice($testimonials, 0, 3) as $t): ?>
        <figure class="quote-fig">
          <blockquote>“<?= e($t['quote']) ?>”</blockquote>
          <figcaption>
            <span class="font-medium"><?= e($t['name']) ?></span>
            <span class="sub"><?= e($t['role']) ?><?= !empty($t['company']) ? ', ' . e($t['company']) : '' ?></span>
          </figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </section>
</div>
<?php endif; ?>

<section class="section text-center cta">
  <h2>Have a system in mind?</h2>
  <p>Tell us what you need. We'll come back with a scope, a timeline and a fixed quote.</p>
  <div class="actions">
    <a href="<?= e(url('/quote')) ?>" class="btn btn-lg">Request a quote <?= icon('arrow-right', 16) ?></a>
    <a href="<?= e(url('/contact')) ?>" class="btn btn-lg btn-outline">Contact us</a>
  </div>
</section>

<?php
/** @var list<array<string,mixed>> $jobs @var string $openJob @var array<string,string> $errors @var array<string,mixed> $old @var string $doneJob @var string $formError */
$fields = [
    ['name' => 'name', 'label' => 'Full name', 'required' => true],
    ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
    ['name' => 'phone', 'label' => 'Phone', 'type' => 'tel'],
    ['name' => 'portfolio_url', 'label' => 'Portfolio / LinkedIn / CV link', 'type' => 'url'],
    ['name' => 'cover_letter', 'label' => 'Why this role?', 'type' => 'textarea'],
];
?>
<?= partial('partials/hero', ['eyebrow' => 'Careers', 'title' => 'Build with us', 'description' => 'We hire people who care about craft and about the people using what they build.']) ?>
<section class="section space-4">
  <?php if ($jobs === []): ?><p class="muted">No open roles right now — check back soon.</p><?php endif; ?>
  <?php foreach ($jobs as $j):
      $isOpen = $openJob === $j['id'] || $doneJob === $j['id'];
      $meta = implode(' · ', array_filter([$j['department'], $j['location'], $j['employment_type']], static fn ($v): bool => $v !== null && $v !== ''));
  ?>
    <article class="card">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h2 class="font-display text-xl font-semibold"><?= e($j['title']) ?></h2>
          <p class="mt-1 meta-mono"><?= e($meta) ?></p>
        </div>
        <button type="button" class="btn<?= $isOpen ? ' btn-outline' : '' ?>" data-apply-toggle="apply-<?= e($j['id']) ?>"><?= $isOpen ? 'Close' : 'Apply' ?></button>
      </div>
      <p class="body mt-4"><?= e($j['description']) ?></p>
      <?php if ($j['requirements'] !== []): ?>
        <ul class="bullets mt-3 text-sm muted"><?php foreach ($j['requirements'] as $r): ?><li>• <?= e($r) ?></li><?php endforeach; ?></ul>
      <?php endif; ?>
      <div id="apply-<?= e($j['id']) ?>" class="apply-form mt-6<?= $isOpen ? ' is-open' : '' ?>">
        <?= partial('partials/lead_form', [
            'formId' => 'job-' . $j['id'],
            'action' => '/careers/apply',
            'fields' => $fields,
            'submitLabel' => 'Submit application',
            'errors' => $openJob === $j['id'] ? $errors : [],
            'old' => $openJob === $j['id'] ? $old : [],
            'formError' => $openJob === $j['id'] ? $formError : '',
            'hidden' => ['job_id' => (string) $j['id']],
            'done' => $doneJob === $j['id'],
            'successHtml' => '<h3>Application sent</h3><p>Thanks — we\'ll be in touch.</p>',
        ]) ?>
      </div>
    </article>
  <?php endforeach; ?>
</section>

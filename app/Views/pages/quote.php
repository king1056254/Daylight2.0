<?php /** @var array<string,string> $errors @var array<string,mixed> $old @var bool $done @var string $formError */ ?>
<?= partial('partials/hero', ['eyebrow' => 'Start a project', 'title' => 'Request a quote', 'description' => 'A few details help us come back with a realistic scope, timeline and price.']) ?>
<section class="section section-4xl">
  <?= partial('partials/lead_form', [
      'formId' => 'quote',
      'action' => '/quote',
      'submitLabel' => 'Request quote',
      'fields' => [
          ['name' => 'name', 'label' => 'Your name', 'required' => true],
          ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
          ['name' => 'phone', 'label' => 'Phone', 'type' => 'tel'],
          ['name' => 'company', 'label' => 'Company'],
          ['name' => 'country', 'label' => 'Country'],
          ['name' => 'project_type', 'label' => 'Project type', 'type' => 'select', 'required' => true, 'options' => \App\Controllers\FormController::QUOTE_TYPES],
          ['name' => 'budget_range', 'label' => 'Budget (KES)', 'type' => 'select', 'options' => \App\Controllers\FormController::QUOTE_BUDGETS],
          ['name' => 'timeline', 'label' => 'Timeline', 'type' => 'select', 'options' => \App\Controllers\FormController::QUOTE_TIMELINES],
          ['name' => 'details', 'label' => 'Tell us about the project', 'type' => 'textarea', 'required' => true],
      ],
      'errors' => $errors, 'old' => $old, 'done' => $done, 'formError' => $formError,
      'successHtml' => '<h2>Request received</h2><p>We\'ll review it and reply within two working days.</p>',
  ]) ?>
</section>

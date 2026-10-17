<?php
/** @var string $email @var array<string,string> $errors @var array<string,mixed> $old @var bool $done @var string $formError */
?>
<?= partial('partials/hero', ['eyebrow' => 'Contact', 'title' => "Let's talk", 'description' => 'We reply to every message within one working day.']) ?>
<section class="section split-contact">
  <div class="space-4 text-sm muted">
    <p class="flex items-center gap-2"><span class="text-primary"><?= icon('mail', 16) ?></span> <?= e($email) ?></p>
    <p class="flex items-center gap-2"><span class="text-primary"><?= icon('map-pin', 16) ?></span> Nairobi, Kenya</p>
  </div>
  <?= partial('partials/lead_form', [
      'formId' => 'contact',
      'action' => '/contact',
      'submitLabel' => 'Send message',
      'fields' => [
          ['name' => 'name', 'label' => 'Your name', 'required' => true],
          ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
          ['name' => 'phone', 'label' => 'Phone', 'type' => 'tel'],
          ['name' => 'company', 'label' => 'Company'],
          ['name' => 'subject', 'label' => 'Subject', 'full' => true],
          ['name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'required' => true],
      ],
      'errors' => $errors, 'old' => $old, 'done' => $done, 'formError' => $formError,
      'successHtml' => '<h2>Message received</h2><p>Thank you — we\'ll be in touch within one working day.</p>',
  ]) ?>
</section>

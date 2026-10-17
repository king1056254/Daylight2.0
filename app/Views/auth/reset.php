<?php /** @var bool $valid @var string $error @var string $token */ ?>
<section class="section section-md">
  <?php if (!$valid): ?>
    <div class="card p-8">
      <h1 class="text-2xl font-semibold">Link expired</h1>
      <p class="mt-2 text-sm muted">This password reset link is invalid or has expired. Request a new one.</p>
      <a class="btn mt-6" href="<?= e(url('/auth/forgot')) ?>">Request a new link</a>
    </div>
  <?php else: ?>
    <form method="post" action="<?= e(url('/auth/reset/' . $token)) ?>" class="form-card stack p-8">
      <?= csrf_field() ?>
      <h1 class="text-2xl font-semibold">Choose a new password</h1>
      <?php if ($error !== ''): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
      <div class="field">
        <label class="label" for="password">New password</label>
        <input class="input" id="password" name="password" type="password" minlength="8" maxlength="72" required autocomplete="new-password">
      </div>
      <div class="field">
        <label class="label" for="password2">Repeat password</label>
        <input class="input" id="password2" name="password2" type="password" minlength="8" maxlength="72" required autocomplete="new-password">
      </div>
      <button type="submit" class="btn btn-block" data-busy-label="Please wait…" data-label="Change password">Change password</button>
    </form>
  <?php endif; ?>
</section>

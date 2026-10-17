<?php /** @var string $error @var bool $sent */ ?>
<section class="section section-md">
  <?php if ($sent): ?>
    <div class="card p-8">
      <h1 class="text-2xl font-semibold">Check your email</h1>
      <p class="mt-2 text-sm muted">If an account exists for that address, we've sent a link to reset the password. It expires in one hour.</p>
      <a class="btn btn-outline mt-6" href="<?= e(url('/auth')) ?>">Back to sign in</a>
    </div>
  <?php else: ?>
    <form method="post" action="<?= e(url('/auth/forgot')) ?>" class="form-card stack p-8">
      <?= csrf_field() ?>
      <h1 class="text-2xl font-semibold">Reset your password</h1>
      <?php if ($error !== ''): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
      <div class="field">
        <label class="label" for="email">Email</label>
        <input class="input" id="email" name="email" type="email" required maxlength="254" autocomplete="email">
      </div>
      <button type="submit" class="btn btn-block" data-busy-label="Please wait…" data-label="Send reset link">Send reset link</button>
      <p class="text-center"><a class="link-quiet" href="<?= e(url('/auth')) ?>">Back to sign in</a></p>
    </form>
  <?php endif; ?>
</section>

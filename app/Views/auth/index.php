<?php
use App\Core\Auth;

/** @var string $mode @var string $error @var string $notice @var array<string,string> $old @var array<string,string> $errors */
$user = Auth::user();
$staff = $user !== null && Auth::isStaff();
?>
<section class="section section-md">
<?php if ($user !== null): ?>
  <div class="card p-8">
    <h1 class="text-2xl font-semibold">You're signed in</h1>
    <p class="mt-2 text-sm muted"><?= e($user['email']) ?> · <?= $staff ? 'Staff account' : 'Client account' ?></p>
    <div class="form-actions mt-6">
      <a class="btn" href="<?= e(url($staff ? '/admin' : '/portal')) ?>"><?= $staff ? 'Open admin area' : 'Open client portal' ?></a>
      <form method="post" action="<?= e(url('/auth/logout')) ?>" class="inline-form">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-outline">Sign out</button>
      </form>
    </div>
  </div>
<?php else: ?>
  <form method="post" action="<?= e(url($mode === 'in' ? '/auth/login' : '/auth/register')) ?>" class="form-card stack p-8">
    <?= csrf_field() ?>
    <h1 class="text-2xl font-semibold"><?= $mode === 'in' ? 'Sign in' : 'Create an account' ?></h1>
    <?php if ($notice !== ''): ?><div class="alert alert-success" role="status"><?= e($notice) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <?php if ($mode === 'up'): ?>
      <div class="sr-only" aria-hidden="true"><label>Leave this field empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
      <div class="field">
        <label class="label" for="name">Full name</label>
        <input class="input<?= isset($errors['name']) ? ' invalid' : '' ?>" id="name" name="name" required maxlength="200" autocomplete="name" value="<?= e($old['name'] ?? '') ?>">
        <?php if (isset($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
      </div>
    <?php endif; ?>
    <div class="field">
      <label class="label" for="email">Email</label>
      <input class="input<?= isset($errors['email']) ? ' invalid' : '' ?>" id="email" name="email" type="email" required maxlength="254" autocomplete="email" value="<?= e($old['email'] ?? '') ?>">
      <?php if (isset($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
    </div>
    <div class="field">
      <label class="label" for="password">Password</label>
      <input class="input<?= isset($errors['password']) ? ' invalid' : '' ?>" id="password" name="password" type="password" minlength="8" maxlength="72" required autocomplete="<?= $mode === 'in' ? 'current-password' : 'new-password' ?>">
      <?php if (isset($errors['password'])): ?><p class="field-error"><?= e($errors['password']) ?></p><?php endif; ?>
    </div>
    <button type="submit" class="btn btn-block" data-busy-label="Please wait…" data-label="<?= $mode === 'in' ? 'Sign in' : 'Create account' ?>"><?= $mode === 'in' ? 'Sign in' : 'Create account' ?></button>
    <?php if ($mode === 'in'): ?>
      <p class="text-center"><a class="link-quiet" href="<?= e(url('/auth/forgot')) ?>">Forgot your password?</a></p>
    <?php endif; ?>
    <p class="text-center"><a class="link-quiet" href="<?= e(url($mode === 'in' ? '/auth?mode=up' : '/auth')) ?>"><?= $mode === 'in' ? 'New client? Create an account' : 'Already have an account? Sign in' ?></a></p>
  </form>
<?php endif; ?>
</section>

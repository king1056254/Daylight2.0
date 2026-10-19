<?php /** @var array<string,mixed>|null $s @var string $msg @var string $err @var array<string,string> $old */ ?>
<section class="section section-3xl">
  <h1 class="text-3xl font-semibold">Daylight setup</h1>
  <p class="mt-3 muted text-sm">One-time installer for hosts without SSH. After finishing, delete <code>SETUP_TOKEN</code> from your configuration so this page turns off.</p>
  <?php if ($msg !== ''): ?><div class="alert alert-success mt-6" role="status"><?= e($msg) ?></div><?php endif; ?>
  <?php if ($err !== ''): ?><div class="alert alert-error mt-6" role="alert"><?= e($err) ?></div><?php endif; ?>

  <?php if ($s !== null): ?>
  <div class="card mt-6">
    <h2 class="title-lg font-display font-semibold">Status</h2>
    <ul class="checklist checklist-tight mt-4">
      <li><?= $s['db'] ? icon('check', 16) : icon('x', 16) ?> Database connection: <?= $s['db'] ? 'OK' : 'cannot connect — check DB_HOST, DB_NAME, DB_USER, DB_PASS' ?></li>
      <li><?= $s['migrated'] ? icon('check', 16) : icon('x', 16) ?> Tables created: <?= $s['migrated'] ? 'yes' : 'not yet' ?></li>
      <li><?= $s['content'] ? icon('check', 16) : icon('x', 16) ?> Original site content loaded: <?= $s['content'] ? 'yes' : 'not yet' ?></li>
      <li><?= $s['admin'] ? icon('check', 16) : icon('x', 16) ?> Administrator exists: <?= $s['admin'] ? 'yes' : 'not yet' ?></li>
    </ul>
  </div>
  <?php else: ?>
  <p class="mt-6 text-sm muted">Enter the setup token in a form below to run a step. Status details appear after a correct token.</p>
  <?php endif; ?>

  <form method="post" action="<?= e(url('/setup')) ?>" class="form-card stack mt-6">
    <?= csrf_field() ?><input type="hidden" name="action" value="migrate">
    <h2 class="title-lg font-display font-semibold">1. Create the database tables</h2>
    <div class="field"><label class="label" for="t1">Setup token</label><input class="input" id="t1" name="token" type="password" required autocomplete="off"></div>
    <label class="checkbox-row"><input type="checkbox" name="seed" value="1" checked> Also load the original site content (services, case studies, FAQs…)</label>
    <div><button type="submit" class="btn" data-busy-label="Working…" data-label="Run database setup">Run database setup</button></div>
  </form>

  <form method="post" action="<?= e(url('/setup')) ?>" class="form-card stack mt-6">
    <?= csrf_field() ?><input type="hidden" name="action" value="admin">
    <h2 class="title-lg font-display font-semibold">2. Create the first super admin</h2>
    <div class="field"><label class="label" for="t2">Setup token</label><input class="input" id="t2" name="token" type="password" required autocomplete="off"></div>
    <div class="field"><label class="label" for="fn">Full name</label><input class="input" id="fn" name="full_name" required maxlength="200" value="<?= e($old['full_name'] ?? '') ?>"></div>
    <div class="field"><label class="label" for="em">Email</label><input class="input" id="em" name="email" type="email" required maxlength="254" value="<?= e($old['email'] ?? '') ?>"></div>
    <div class="field"><label class="label" for="pw">Password</label><input class="input" id="pw" name="password" type="password" required minlength="8" maxlength="72" autocomplete="new-password"></div>
    <div><button type="submit" class="btn" data-busy-label="Working…" data-label="Create super admin">Create super admin</button></div>
  </form>
</section>

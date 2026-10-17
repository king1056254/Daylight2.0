<?php
/** @var array<string,mixed>|null $user @var list<string> $roles @var list<string> $grantable @var array<string,mixed> $old @var array<string,string> $errors @var bool $self */
$isNew = $user === null;
$rolesNow = $old !== [] ? (array) ($old['roles'] ?? []) : $roles;
$name = $old['full_name'] ?? ($user['full_name'] ?? '');
$phone = $old['phone'] ?? ($user['phone'] ?? '');
$active = $old !== [] ? (($old['is_active'] ?? '') === '1') : ((int) ($user['is_active'] ?? 1) === 1);
$action = $isNew ? '/admin/users' : '/admin/users/' . $user['id'];
?>
<p class="crumbs"><a href="<?= e(url('/admin/users')) ?>">Users &amp; roles</a> / <?= $isNew ? 'New' : e($user['email']) ?></p>
<div class="app-title"><h1><?= $isNew ? 'New user' : 'Edit user' ?></h1></div>
<form method="post" action="<?= e(url($action)) ?>" class="form-card stack">
  <?= csrf_field() ?>
  <?php if (isset($errors['_form'])): ?><div class="alert alert-error" role="alert"><?= e($errors['_form']) ?></div><?php endif; ?>
  <div class="form-grid two">
    <div class="field">
      <label class="label" for="email">Email<?= $isNew ? '<span class="req"> *</span>' : '' ?></label>
      <?php if ($isNew): ?>
        <input class="input<?= isset($errors['email']) ? ' invalid' : '' ?>" id="email" name="email" type="email" maxlength="254" required value="<?= e($old['email'] ?? '') ?>">
        <?php if (isset($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
      <?php else: ?><div class="text-sm"><?= e($user['email']) ?></div><?php endif; ?>
    </div>
    <div class="field">
      <label class="label" for="full_name">Full name</label>
      <input class="input<?= isset($errors['full_name']) ? ' invalid' : '' ?>" id="full_name" name="full_name" maxlength="200" value="<?= e($name) ?>">
      <?php if (isset($errors['full_name'])): ?><p class="field-error"><?= e($errors['full_name']) ?></p><?php endif; ?>
    </div>
    <div class="field">
      <label class="label" for="phone">Phone</label>
      <input class="input<?= isset($errors['phone']) ? ' invalid' : '' ?>" id="phone" name="phone" type="tel" maxlength="40" value="<?= e($phone) ?>">
      <?php if (isset($errors['phone'])): ?><p class="field-error"><?= e($errors['phone']) ?></p><?php endif; ?>
    </div>
    <div class="field">
      <label class="label" for="password"><?= $isNew ? 'Password' : 'New password' ?><?= $isNew ? '<span class="req"> *</span>' : '' ?></label>
      <input class="input<?= isset($errors['password']) ? ' invalid' : '' ?>" id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password"<?= $isNew ? ' required' : '' ?>>
      <?php if (isset($errors['password'])): ?><p class="field-error"><?= e($errors['password']) ?></p><?php endif; ?>
      <?php if (!$isNew): ?><p class="hint">Leave empty to keep the current password.</p><?php endif; ?>
    </div>
  </div>
  <div class="field">
    <span class="label">Roles</span>
    <?php if ($self): ?>
      <p class="hint">You can't change your own roles or deactivate your own account.</p>
    <?php endif; ?>
    <?php foreach (['super_admin' => 'Super admin', 'admin' => 'Admin', 'staff' => 'Staff', 'client' => 'Client'] as $role => $label): ?>
      <?php $can = in_array($role, $grantable, true) && !$self; ?>
      <label class="checkbox-row"><input type="checkbox" name="roles[]" value="<?= e($role) ?>"<?= in_array($role, $rolesNow, true) ? ' checked' : '' ?><?= $can ? '' : ' disabled' ?>> <?= e($label) ?></label>
    <?php endforeach; ?>
    <?php if (isset($errors['roles'])): ?><p class="field-error"><?= e($errors['roles']) ?></p><?php endif; ?>
    <p class="hint">Staff can manage clients, projects, tickets and billing. Admins also manage website content. Only super admins can grant admin roles. Clients need a client record linked in Clients → Portal user.</p>
  </div>
  <?php if (!$isNew): ?>
    <label class="checkbox-row"><input type="checkbox" name="is_active" value="1"<?= $active ? ' checked' : '' ?><?= $self ? ' disabled' : '' ?>> Account active</label>
  <?php endif; ?>
  <div class="form-actions">
    <button type="submit" class="btn" data-busy-label="Saving…" data-label="Save">Save</button>
    <a class="btn btn-outline" href="<?= e(url('/admin/users')) ?>">Back</a>
  </div>
</form>

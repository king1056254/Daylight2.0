<?php /** @var list<array<string,mixed>> $users @var string $q */ ?>
<div class="app-title"><h1>Users &amp; roles</h1><a class="btn" href="<?= e(url('/admin/users/new')) ?>">New user</a></div>
<form method="get" action="<?= e(url('/admin/users')) ?>" class="toolbar">
  <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Search by email or name…" maxlength="100" aria-label="Search">
  <button type="submit" class="btn btn-outline">Search</button>
  <?php if ($q !== ''): ?><a class="btn btn-ghost" href="<?= e(url('/admin/users')) ?>">Clear</a><?php endif; ?>
</form>
<div class="table-wrap">
  <?php if ($users === []): ?><p class="empty">No users found.</p><?php else: ?>
  <table class="data-table"><thead><tr><th>Email</th><th>Name</th><th>Roles</th><th>Active</th><th>Joined</th><th></th></tr></thead><tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><a class="text-primary font-medium" href="<?= e(url('/admin/users/' . $u['id'])) ?>"><?= e($u['email']) ?></a></td>
        <td><?= e($u['full_name']) ?></td>
        <td><?= $u['roles'] ? e($u['roles']) : '<span class="muted">none</span>' ?></td>
        <td><?= (int) $u['is_active'] === 1 ? 'Yes' : '<span class="text-destructive">No</span>' ?></td>
        <td><?= fmt_date((string) $u['created_at']) ?></td>
        <td class="num"><a class="link-quiet" href="<?= e(url('/admin/users/' . $u['id'])) ?>">Edit</a></td>
      </tr>
    <?php endforeach; ?>
  </tbody></table>
  <?php endif; ?>
</div>

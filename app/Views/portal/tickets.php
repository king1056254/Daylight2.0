<?php /** @var bool $linked @var list<array<string,mixed>> $tickets @var list<array<string,mixed>> $projects @var array<string,string> $errors @var array<string,mixed> $old @var string $error */ ?>
<div class="app-title"><h1>Support</h1></div>
<?php if (!$linked): ?>
  <div class="card"><p class="body">Your account isn't linked to a client profile yet. Contact Daylight to use support tickets.</p></div>
<?php else: ?>
  <form method="post" action="<?= e(url('/portal/tickets')) ?>" class="form-card">
    <?= csrf_field() ?>
    <h2 class="title-lg font-display font-semibold full">New ticket</h2>
    <?php if ($error !== ''): ?><div class="alert alert-error full" role="alert"><?= e($error) ?></div><?php endif; ?>
    <div class="field field-full">
      <label class="label" for="subject">Subject <span class="req">*</span></label>
      <input class="input<?= isset($errors['subject']) ? ' invalid' : '' ?>" id="subject" name="subject" maxlength="250" required value="<?= e($old['subject'] ?? '') ?>">
      <?php if (isset($errors['subject'])): ?><p class="field-error"><?= e($errors['subject']) ?></p><?php endif; ?>
    </div>
    <div class="field">
      <label class="label" for="priority">Priority</label>
      <select class="select" id="priority" name="priority">
        <?php foreach (['Low', 'Normal', 'High'] as $pr): ?><option<?= ($old['priority'] ?? 'Normal') === $pr ? ' selected' : '' ?>><?= e($pr) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label class="label" for="project_id">Project (optional)</label>
      <select class="select" id="project_id" name="project_id">
        <option value="">None</option>
        <?php foreach ($projects as $p): ?><option value="<?= e($p['id']) ?>"<?= ($old['project_id'] ?? '') === $p['id'] ? ' selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
      </select>
      <?php if (isset($errors['project_id'])): ?><p class="field-error"><?= e($errors['project_id']) ?></p><?php endif; ?>
    </div>
    <div class="field field-full">
      <label class="label" for="description">Describe the issue</label>
      <textarea class="textarea" id="description" name="description" rows="5" maxlength="5000"><?= e($old['description'] ?? '') ?></textarea>
    </div>
    <div class="full"><button type="submit" class="btn" data-busy-label="Sending…" data-label="Create ticket">Create ticket</button></div>
  </form>

  <h2 class="mt-10 font-display text-xl font-semibold">Your tickets</h2>
  <div class="table-wrap mt-4">
    <?php if ($tickets === []): ?><p class="empty">No tickets yet.</p><?php else: ?>
    <table class="data-table"><thead><tr><th>Subject</th><th>Priority</th><th>Status</th><th>Opened</th></tr></thead><tbody>
      <?php foreach ($tickets as $t): ?>
        <tr><td><?= e($t['subject']) ?></td><td><?= e($t['priority']) ?></td><td><?= status_pill((string) $t['status']) ?></td><td><?= fmt_date((string) $t['created_at']) ?></td></tr>
      <?php endforeach; ?>
    </tbody></table>
    <?php endif; ?>
  </div>
<?php endif; ?>

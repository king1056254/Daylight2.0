<?php
/**
 * @var array<string,mixed> $def @var array<string,mixed>|null $row @var array<string,string> $values
 * @var array<string,string> $errors @var array<string,list<array<string,mixed>>> $options @var bool $canWrite @var bool $canDelete
 */
$slug = (string) $def['slug'];
$isNew = $row === null;
$hasEditable = count(array_filter($def['fields'], static fn (array $f): bool => empty($f['readonly']))) > 0;
$action = $isNew ? '/admin/' . $slug : '/admin/' . $slug . '/' . $row['id'];
?>
<p class="crumbs"><a href="<?= e(url('/admin/' . $slug)) ?>"><?= e($def['title']) ?></a> / <?= $isNew ? 'New' : 'Edit' ?></p>
<div class="app-title"><h1><?= $isNew ? 'New ' : 'Edit ' ?><?= e($def['singular']) ?></h1></div>
<form method="post" action="<?= e(url($action)) ?>" class="form-card stack">
  <?= csrf_field() ?>
  <?php if (isset($errors['_form'])): ?><div class="alert alert-error" role="alert"><?= e($errors['_form']) ?></div><?php endif; ?>
  <?php if (!$canWrite): ?><div class="alert alert-info">You have read-only access to this section.</div><?php endif; ?>
  <div class="form-grid two">
  <?php foreach ($def['fields'] as $name => $f):
      $type = (string) $f['type'];
      $val = $values[$name] ?? '';
      $err = $errors[$name] ?? '';
      $readonly = !empty($f['readonly']) || !$canWrite;
      $wide = in_array($type, ['textarea', 'list'], true);
      $id = 'f-' . $name;
      if ($readonly && $isNew && !empty($f['readonly'])) { continue; }
  ?>
    <div class="field<?= $wide ? ' field-full' : '' ?>">
      <?php if ($type === 'bool'): ?>
        <label class="checkbox-row"><input type="checkbox" id="<?= e($id) ?>" name="<?= e($name) ?>" value="1"<?= $val === '1' ? ' checked' : '' ?><?= $readonly ? ' disabled' : '' ?>> <?= e($f['label']) ?></label>
      <?php else: ?>
        <label class="label" for="<?= e($id) ?>"><?= e($f['label']) ?><?php if (!empty($f['required']) && !$readonly): ?><span class="req"> *</span><?php endif; ?></label>
        <?php if ($readonly): ?>
          <div class="text-sm pre-line"><?php
            if ($type === 'rel') {
                $label = '';
                foreach ($options[$name] ?? [] as $o) { if ($o['id'] === $val) { $label = (string) $o['label']; } }
                echo $val === '' ? '<span class="muted">—</span>' : e($label !== '' ? $label : '(missing)');
            } else {
                echo $val === '' ? '<span class="muted">—</span>' : e($val);
            }
          ?></div>
        <?php elseif ($type === 'textarea' || $type === 'list'): ?>
          <textarea class="textarea<?= $err !== '' ? ' invalid' : '' ?>" id="<?= e($id) ?>" name="<?= e($name) ?>" rows="<?= $type === 'list' ? 5 : 6 ?>"><?= e($val) ?></textarea>
        <?php elseif ($type === 'select'): ?>
          <select class="select<?= $err !== '' ? ' invalid' : '' ?>" id="<?= e($id) ?>" name="<?= e($name) ?>">
            <?php if (empty($f['required'])): ?><option value="">—</option><?php endif; ?>
            <?php foreach ((array) $f['options'] as $o): ?><option<?= $val === (string) $o ? ' selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?>
          </select>
        <?php elseif ($type === 'rel'): ?>
          <select class="select<?= $err !== '' ? ' invalid' : '' ?>" id="<?= e($id) ?>" name="<?= e($name) ?>">
            <option value="">—</option>
            <?php foreach ($options[$name] ?? [] as $o): ?><option value="<?= e($o['id']) ?>"<?= $val === (string) $o['id'] ? ' selected' : '' ?>><?= e($o['label']) ?></option><?php endforeach; ?>
          </select>
        <?php else:
            $htmlType = match ($type) { 'email' => 'email', 'tel' => 'tel', 'url' => 'url', 'date' => 'date', 'datetime' => 'datetime-local', 'int' => 'number', default => 'text' };
        ?>
          <input class="input<?= $err !== '' ? ' invalid' : '' ?>" id="<?= e($id) ?>" name="<?= e($name) ?>" type="<?= e($htmlType) ?>" value="<?= e($val) ?>"<?= $type === 'decimal' ? ' inputmode="decimal"' : '' ?><?= $type === 'int' ? ' step="1"' : '' ?>>
        <?php endif; ?>
      <?php endif; ?>
      <?php if ($err !== ''): ?><p class="field-error"><?= e($err) ?></p><?php endif; ?>
      <?php if (!empty($f['hint']) && !$readonly): ?><p class="hint"><?= e($f['hint']) ?></p><?php endif; ?>
    </div>
  <?php endforeach; ?>
  </div>
  <div class="form-actions">
    <?php if ($canWrite && $hasEditable): ?><button type="submit" class="btn" data-busy-label="Saving…" data-label="Save">Save</button><?php endif; ?>
    <a class="btn btn-outline" href="<?= e(url('/admin/' . $slug)) ?>">Back</a>
  </div>
</form>
<?php if ($canDelete): ?>
  <form method="post" action="<?= e(url($action . '/delete')) ?>" class="mt-6" data-confirm="Delete this <?= e($def['singular']) ?>? This cannot be undone.">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-danger btn-sm">Delete <?= e($def['singular']) ?></button>
  </form>
<?php endif; ?>

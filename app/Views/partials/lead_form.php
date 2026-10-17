<?php
/**
 * @var string $formId unique id for this form instance
 * @var string $action
 * @var list<array<string,mixed>> $fields
 * @var string $submitLabel
 * @var array<string,string> $errors
 * @var array<string,mixed> $old
 * @var string $successHtml trusted markup shown after a successful submit
 * @var bool $done
 * @var string $formError
 * @var array<string,string> $hidden extra hidden inputs
 */
$errors = $errors ?? [];
$old = $old ?? [];
$hidden = $hidden ?? [];
$formError = $formError ?? '';
if (!empty($done)): ?>
  <div class="form-success"><?= $successHtml ?></div>
<?php return; endif; ?>
<form method="post" action="<?= e(url($action)) ?>" class="form-card" id="<?= e($formId) ?>">
  <?= csrf_field() ?>
  <?php foreach ($hidden as $hn => $hv): ?>
    <input type="hidden" name="<?= e($hn) ?>" value="<?= e($hv) ?>">
  <?php endforeach; ?>
  <div class="sr-only" aria-hidden="true">
    <label>Leave this field empty <input type="text" name="website" tabindex="-1" autocomplete="off" value=""></label>
  </div>
  <?php if ($formError !== ''): ?>
    <div class="alert alert-error full" role="alert"><?= e($formError) ?></div>
  <?php endif; ?>
  <?php foreach ($fields as $f):
      $name = (string) $f['name'];
      $type = (string) ($f['type'] ?? 'text');
      $id = $formId . '-' . $name;
      $full = !empty($f['full']) || $type === 'textarea';
      $val = (string) ($old[$name] ?? '');
      $err = $errors[$name] ?? '';
  ?>
    <div class="field<?= $full ? ' field-full' : '' ?>">
      <label class="label" for="<?= e($id) ?>"><?= e($f['label']) ?><?php if (!empty($f['required'])): ?><span class="req"> *</span><?php endif; ?></label>
      <?php if ($type === 'textarea'): ?>
        <textarea class="textarea<?= $err !== '' ? ' invalid' : '' ?>" id="<?= e($id) ?>" name="<?= e($name) ?>" rows="6" maxlength="5000"<?= !empty($f['required']) ? ' required' : '' ?>><?= e($val) ?></textarea>
      <?php elseif ($type === 'select'): ?>
        <select class="select<?= $err !== '' ? ' invalid' : '' ?>" id="<?= e($id) ?>" name="<?= e($name) ?>"<?= !empty($f['required']) ? ' required' : '' ?>>
          <option value=""<?= $val === '' ? ' selected' : '' ?> disabled>Choose…</option>
          <?php foreach ((array) ($f['options'] ?? []) as $o): ?>
            <option value="<?= e($o) ?>"<?= $val === (string) $o ? ' selected' : '' ?>><?= e($o) ?></option>
          <?php endforeach; ?>
        </select>
      <?php else: ?>
        <input class="input<?= $err !== '' ? ' invalid' : '' ?>" id="<?= e($id) ?>" name="<?= e($name) ?>" type="<?= e($type) ?>" maxlength="300" value="<?= e($val) ?>"<?= !empty($f['required']) ? ' required' : '' ?>>
      <?php endif; ?>
      <?php if ($err !== ''): ?><p class="field-error"><?= e($err) ?></p><?php endif; ?>
    </div>
  <?php endforeach; ?>
  <div class="full">
    <button type="submit" class="btn btn-lg" data-busy-label="Sending…" data-label="<?= e($submitLabel) ?>"><?= e($submitLabel) ?></button>
  </div>
</form>

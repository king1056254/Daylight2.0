<?php
/** @var array<string,mixed> $project @var list<array<string,mixed>> $milestones @var list<array<string,mixed>> $documents @var list<array<string,mixed>> $messages @var string|null $me @var string $error @var string $old */
?>
<p class="crumbs"><a href="<?= e(url('/portal/projects')) ?>">Projects</a> / <?= e($project['name']) ?></p>
<div class="app-title"><h1><?= e($project['name']) ?></h1><?= status_pill((string) $project['status']) ?></div>
<div class="card">
  <?php if (!empty($project['description'])): ?><p class="body"><?= e($project['description']) ?></p><?php endif; ?>
  <progress class="progress mt-4" max="100" value="<?= (int) $project['progress'] ?>"></progress>
  <dl class="kv two mt-4">
    <div><dt>Progress</dt><dd><?= (int) $project['progress'] ?>%</dd></div>
    <div><dt>Started</dt><dd><?= !empty($project['start_date']) ? fmt_date((string) $project['start_date']) : '—' ?></dd></div>
    <div><dt>Expected completion</dt><dd><?= !empty($project['expected_completion']) ? fmt_date((string) $project['expected_completion']) : '—' ?></dd></div>
  </dl>
</div>

<div class="grid cols-2 mt-8">
  <div class="card">
    <h2 class="title-lg font-display font-semibold">Milestones</h2>
    <?php if ($milestones === []): ?><p class="body">No milestones yet.</p><?php else: ?>
      <ul class="timeline mt-2">
        <?php foreach ($milestones as $m): $dot = $m['status'] === 'Completed' ? 'dot-done' : ($m['status'] === 'In Progress' ? 'dot-active' : ''); ?>
          <li><span class="dot <?= $dot ?>"></span><div>
            <p class="font-medium text-sm"><?= e($m['title']) ?> <?= status_pill((string) $m['status']) ?></p>
            <?php if (!empty($m['description'])): ?><p class="text-sm muted mt-1"><?= e($m['description']) ?></p><?php endif; ?>
            <?php if (!empty($m['due_date'])): ?><p class="text-xs muted mt-1">Due <?= fmt_date((string) $m['due_date']) ?></p><?php endif; ?>
          </div></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
  <div class="card">
    <h2 class="title-lg font-display font-semibold">Documents</h2>
    <?php if ($documents === []): ?><p class="body">No documents shared yet.</p><?php else: ?>
      <ul class="timeline mt-2">
        <?php foreach ($documents as $d): $link = (string) ($d['file_path'] ?? ''); $isUrl = preg_match('#^https?://#i', $link) === 1; ?>
          <li><div>
            <p class="font-medium text-sm"><?php if ($isUrl): ?><a class="text-primary" href="<?= e($link) ?>" target="_blank" rel="noopener noreferrer"><?= e($d['title']) ?></a><?php else: ?><?= e($d['title']) ?><?php endif; ?></p>
            <p class="text-xs muted mt-1"><?= e($d['file_type'] ?: 'Document') ?> · <?= fmt_date((string) $d['created_at']) ?></p>
          </div></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<h2 class="mt-10 font-display text-xl font-semibold">Messages</h2>
<div class="card mt-4">
  <?php if ($messages === []): ?><p class="body">No messages yet. Write to the Daylight team below.</p><?php else: ?>
    <div class="thread">
      <?php foreach ($messages as $m): $mine = $m['sender_id'] !== null && $m['sender_id'] === $me; ?>
        <div class="msg<?= $mine ? ' msg-mine' : '' ?>">
          <p class="who"><?= e($mine ? 'You' : ($m['sender_name'] ?: 'Daylight')) ?> · <?= fmt_date((string) $m['created_at']) ?></p>
          <p class="text-sm pre-line"><?= e($m['body']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <form method="post" action="<?= e(url('/portal/projects/' . $project['id'] . '/messages')) ?>" class="mt-6 space-3">
    <?= csrf_field() ?>
    <?php if ($error !== ''): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <div class="field">
      <label class="label" for="body">New message</label>
      <textarea class="textarea" id="body" name="body" rows="4" maxlength="5000" required><?= e($old) ?></textarea>
    </div>
    <button type="submit" class="btn" data-busy-label="Sending…" data-label="Send message">Send message</button>
  </form>
</div>

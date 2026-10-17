<?php /** @var bool $linked @var list<array<string,mixed>> $projects */ ?>
<div class="app-title"><h1>Projects</h1></div>
<?php if (!$linked): ?>
  <div class="card"><p class="body">Your account isn't linked to a client profile yet. Contact Daylight to get access to your projects.</p></div>
<?php elseif ($projects === []): ?>
  <div class="card"><p class="body">No projects yet.</p></div>
<?php else: ?>
  <div class="grid cols-2">
    <?php foreach ($projects as $p): ?>
      <a class="card card-link card-hover-border" href="<?= e(url('/portal/projects/' . $p['id'])) ?>">
        <div class="meta-row"><h2 class="title-lg"><?= e($p['name']) ?></h2><?= status_pill((string) $p['status']) ?></div>
        <?php if (!empty($p['description'])): ?><p class="body"><?= e($p['description']) ?></p><?php endif; ?>
        <progress class="progress mt-4" max="100" value="<?= (int) $p['progress'] ?>"></progress>
        <p class="mt-2 text-sm muted"><?= (int) $p['progress'] ?>% complete<?= !empty($p['expected_completion']) ? ' · due ' . fmt_date((string) $p['expected_completion']) : '' ?></p>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

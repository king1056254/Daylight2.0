<?php
/** @var list<array{0:string,1:int,2:string}> $stats @var list<array<string,mixed>> $recentMessages @var list<array<string,mixed>> $recentQuotes @var int $failedEmails @var int $skippedEmails @var bool $demoAllowed @var bool $demoExists @var array<string,array{label:string,count:int}>|null $retention */
?>
<div class="app-title"><h1>Dashboard</h1></div>
<div class="stat-grid">
  <?php foreach ($stats as [$label, $n, $link]): ?>
    <div class="stat"><a class="num" href="<?= e(url($link)) ?>"><?= (int) $n ?></a><div class="lbl"><?= e($label) ?></div></div>
  <?php endforeach; ?>
</div>

<?php if ($failedEmails > 0): ?>
  <div class="alert alert-error mt-6"><?= (int) $failedEmails ?> email<?= $failedEmails === 1 ? '' : 's' ?> failed to send. Check the SMTP settings; failed messages are kept in the email_outbox table.</div>
<?php endif; ?>
<?php if ($skippedEmails > 0): ?>
  <div class="alert alert-info mt-6"><?= (int) $skippedEmails ?> email<?= $skippedEmails === 1 ? ' was' : 's were' ?> not sent because email is not enabled on this server. Form submissions are still saved here in the admin inbox.</div>
<?php endif; ?>

<div class="grid cols-2 mt-8">
  <div>
    <div class="meta-row"><h2 class="font-display text-xl font-semibold">Latest messages</h2><a class="link-quiet" href="<?= e(url('/admin/messages')) ?>">All</a></div>
    <div class="table-wrap mt-4">
      <?php if ($recentMessages === []): ?><p class="empty">No messages yet.</p><?php else: ?>
      <table class="data-table"><tbody>
        <?php foreach ($recentMessages as $m): ?>
          <tr><td><a class="text-primary" href="<?= e(url('/admin/messages/' . $m['id'])) ?>"><?= e($m['name']) ?></a><div class="text-xs muted"><?= e($m['subject']) ?></div></td><td><?= status_pill((string) $m['status']) ?></td><td><?= fmt_date((string) $m['created_at']) ?></td></tr>
        <?php endforeach; ?>
      </tbody></table>
      <?php endif; ?>
    </div>
  </div>
  <div>
    <div class="meta-row"><h2 class="font-display text-xl font-semibold">Latest quote requests</h2><a class="link-quiet" href="<?= e(url('/admin/quotes')) ?>">All</a></div>
    <div class="table-wrap mt-4">
      <?php if ($recentQuotes === []): ?><p class="empty">No quote requests yet.</p><?php else: ?>
      <table class="data-table"><tbody>
        <?php foreach ($recentQuotes as $q): ?>
          <tr><td><a class="text-primary" href="<?= e(url('/admin/quotes/' . $q['id'])) ?>"><?= e($q['name']) ?></a><div class="text-xs muted"><?= e($q['project_type']) ?></div></td><td><?= status_pill((string) $q['status']) ?></td><td><?= fmt_date((string) $q['created_at']) ?></td></tr>
        <?php endforeach; ?>
      </tbody></table>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($retention !== null): $due = array_sum(array_column($retention, 'count')); ?>
<div class="card mt-10">
  <h2 class="title-lg font-display font-semibold">Data retention</h2>
  <p class="body">Our privacy policy promises to delete personal data after a fixed time. <?= $due > 0 ? e((string) $due) . ' record(s) are now past their retention period.' : 'Nothing is past its retention period right now.' ?></p>
  <?php if ($due > 0): ?>
    <ul class="checklist checklist-tight mt-4">
      <?php foreach ($retention as $r): if ($r['count'] > 0): ?><li><?= icon('check', 16) ?> <?= e((string) $r['count']) ?> — <?= e($r['label']) ?></li><?php endif; endforeach; ?>
    </ul>
    <form method="post" action="<?= e(url('/admin/retention')) ?>" class="mt-4" data-confirm="Permanently delete these old records? This cannot be undone.">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-outline">Delete records past their retention period</button>
    </form>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($demoAllowed): ?>
<div class="card mt-10">
  <h2 class="title-lg font-display font-semibold">Demo data (testing only)</h2>
  <p class="body">Loads a dummy client account, projects, milestones, documents, messages, a ticket and invoices so you can try the client portal. Everything is marked [DEMO] and can be removed again. Do not use on a live site with real clients.</p>
  <form method="post" action="<?= e(url('/admin/demo')) ?>" class="mt-4" <?= $demoExists ? 'data-confirm="Remove all demo data?"' : '' ?>>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="<?= $demoExists ? 'remove' : 'load' ?>">
    <button type="submit" class="btn <?= $demoExists ? 'btn-outline' : '' ?>"><?= $demoExists ? 'Remove demo data' : 'Load demo data' ?></button>
  </form>
</div>
<?php endif; ?>

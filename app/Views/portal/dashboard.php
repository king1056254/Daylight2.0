<?php
/** @var bool $linked @var list<array<string,mixed>> $projects @var list<array<string,mixed>> $invoices @var list<array<string,mixed>> $tickets @var list<array<string,mixed>> $outstanding @var int $openTickets */
if (!$linked): ?>
  <div class="app-title"><h1>Welcome</h1></div>
  <div class="card">
    <h2 class="title-lg font-display font-semibold">Your account isn't linked to a client profile yet</h2>
    <p class="body">Once Daylight links your account to your company, your projects, milestones, documents, invoices and support tickets will appear here. If you expected to see them already, contact us and we'll sort it out.</p>
    <a class="btn mt-5" href="<?= e(url('/contact')) ?>">Contact Daylight</a>
  </div>
<?php return; endif;
$active = array_filter($projects, static fn (array $p): bool => $p['status'] !== 'Completed');
?>
<div class="app-title"><h1>Overview</h1></div>
<div class="stat-grid">
  <div class="stat"><div class="num"><?= count($active) ?></div><div class="lbl">Active projects</div></div>
  <div class="stat"><div class="num"><?= (int) $openTickets ?></div><div class="lbl">Open support tickets</div></div>
  <div class="stat">
    <?php if ($outstanding === []): ?>
      <div class="num">0</div><div class="lbl">Outstanding balance</div>
    <?php else: foreach ($outstanding as $o): ?>
      <div class="num"><?= money($o['due'], (string) $o['currency']) ?></div><div class="lbl">Outstanding balance</div>
    <?php endforeach; endif; ?>
  </div>
  <div class="stat"><div class="num"><?= count($projects) ?></div><div class="lbl">Total projects</div></div>
</div>

<h2 class="mt-10 font-display text-xl font-semibold">Projects</h2>
<?php if ($projects === []): ?>
  <p class="mt-3 muted text-sm">No projects yet.</p>
<?php else: ?>
  <div class="grid cols-2 mt-4">
    <?php foreach ($projects as $p): ?>
      <a class="card card-link card-hover-border" href="<?= e(url('/portal/projects/' . $p['id'])) ?>">
        <div class="meta-row"><h3 class="title-lg"><?= e($p['name']) ?></h3><?= status_pill((string) $p['status']) ?></div>
        <progress class="progress mt-4" max="100" value="<?= (int) $p['progress'] ?>"></progress>
        <p class="mt-2 text-sm muted"><?= (int) $p['progress'] ?>% complete<?= !empty($p['expected_completion']) ? ' · due ' . fmt_date((string) $p['expected_completion']) : '' ?></p>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="grid cols-2 mt-10">
  <div>
    <div class="meta-row"><h2 class="font-display text-xl font-semibold">Recent invoices</h2><a class="link-quiet" href="<?= e(url('/portal/invoices')) ?>">View all</a></div>
    <div class="table-wrap mt-4">
      <?php if ($invoices === []): ?><p class="empty">No invoices yet.</p><?php else: ?>
      <table class="data-table"><thead><tr><th>Invoice</th><th>Status</th><th class="num">Total</th></tr></thead><tbody>
        <?php foreach ($invoices as $i): ?>
          <tr><td><a class="text-primary" href="<?= e(url('/portal/invoices/' . $i['id'])) ?>"><?= e($i['invoice_number']) ?></a></td><td><?= status_pill((string) $i['status']) ?></td><td class="num"><?= money($i['total'], (string) $i['currency']) ?></td></tr>
        <?php endforeach; ?>
      </tbody></table>
      <?php endif; ?>
    </div>
  </div>
  <div>
    <div class="meta-row"><h2 class="font-display text-xl font-semibold">Open tickets</h2><a class="link-quiet" href="<?= e(url('/portal/tickets')) ?>">Support</a></div>
    <div class="table-wrap mt-4">
      <?php if ($tickets === []): ?><p class="empty">No open tickets.</p><?php else: ?>
      <table class="data-table"><thead><tr><th>Subject</th><th>Priority</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($tickets as $t): ?>
          <tr><td><?= e($t['subject']) ?></td><td><?= e($t['priority']) ?></td><td><?= status_pill((string) $t['status']) ?></td></tr>
        <?php endforeach; ?>
      </tbody></table>
      <?php endif; ?>
    </div>
  </div>
</div>

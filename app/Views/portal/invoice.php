<?php
/** @var array<string,mixed> $invoice @var array<string,mixed>|null $client @var list<array<string,mixed>> $items @var list<array<string,mixed>> $payments */
$cur = (string) $invoice['currency'];
$paid = array_sum(array_map(static fn (array $p): float => (float) $p['amount'], $payments));
$balance = max(0, (float) $invoice['total'] - $paid);
?>
<p class="crumbs"><a href="<?= e(url('/portal/invoices')) ?>">Invoices</a> / <?= e($invoice['invoice_number']) ?></p>
<div class="app-title"><h1>Invoice <?= e($invoice['invoice_number']) ?></h1><?= status_pill((string) $invoice['status']) ?></div>
<div class="card">
  <dl class="kv two">
    <div><dt>Billed to</dt><dd><?= e($client['company_name'] ?? '') ?></dd></div>
    <div><dt>Issued</dt><dd><?= !empty($invoice['issue_date']) ? fmt_date((string) $invoice['issue_date']) : '—' ?></dd></div>
    <div><dt>Due</dt><dd><?= !empty($invoice['due_date']) ? fmt_date((string) $invoice['due_date']) : '—' ?></dd></div>
    <div><dt>Balance due</dt><dd class="font-semibold"><?= money($balance, $cur) ?></dd></div>
  </dl>
</div>
<div class="table-wrap mt-6">
  <table class="data-table"><thead><tr><th>Description</th><th class="num">Qty</th><th class="num">Unit price</th><th class="num">Amount</th></tr></thead><tbody>
    <?php foreach ($items as $it): ?>
      <tr><td><?= e($it['description']) ?></td><td class="num"><?= e(rtrim(rtrim(number_format((float) $it['quantity'], 2, '.', ''), '0'), '.')) ?></td><td class="num"><?= money($it['unit_price'], $cur) ?></td><td class="num"><?= money($it['amount'], $cur) ?></td></tr>
    <?php endforeach; ?>
    <?php if ($items === []): ?><tr><td colspan="4" class="empty">No line items.</td></tr><?php endif; ?>
    <tr><td colspan="3" class="num muted">Subtotal</td><td class="num"><?= money($invoice['subtotal'], $cur) ?></td></tr>
    <tr><td colspan="3" class="num muted">Tax</td><td class="num"><?= money($invoice['tax'], $cur) ?></td></tr>
    <tr><td colspan="3" class="num font-semibold">Total</td><td class="num font-semibold"><?= money($invoice['total'], $cur) ?></td></tr>
  </tbody></table>
</div>
<?php if (!empty($invoice['notes'])): ?><p class="mt-4 text-sm muted pre-line"><?= e($invoice['notes']) ?></p><?php endif; ?>
<h2 class="mt-8 font-display text-xl font-semibold">Payments</h2>
<div class="table-wrap mt-4">
  <?php if ($payments === []): ?><p class="empty">No payments recorded yet.</p><?php else: ?>
  <table class="data-table"><thead><tr><th>Date</th><th>Method</th><th>Reference</th><th class="num">Amount</th></tr></thead><tbody>
    <?php foreach ($payments as $p): ?>
      <tr><td><?= fmt_date((string) $p['paid_at']) ?></td><td><?= e($p['method']) ?></td><td><?= e($p['reference']) ?></td><td class="num"><?= money($p['amount'], $cur) ?></td></tr>
    <?php endforeach; ?>
  </tbody></table>
  <?php endif; ?>
</div>

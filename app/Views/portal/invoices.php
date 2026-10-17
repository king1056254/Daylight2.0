<?php /** @var bool $linked @var list<array<string,mixed>> $invoices */ ?>
<div class="app-title"><h1>Invoices</h1></div>
<?php if (!$linked): ?>
  <div class="card"><p class="body">Your account isn't linked to a client profile yet. Contact Daylight to see your invoices.</p></div>
<?php else: ?>
  <div class="table-wrap">
    <?php if ($invoices === []): ?><p class="empty">No invoices yet.</p><?php else: ?>
    <table class="data-table"><thead><tr><th>Invoice</th><th>Status</th><th>Issued</th><th>Due</th><th class="num">Total</th><th class="num">Paid</th><th class="num">Balance</th></tr></thead><tbody>
      <?php foreach ($invoices as $i): $bal = max(0, (float) $i['total'] - (float) $i['paid']); ?>
        <tr>
          <td><a class="text-primary" href="<?= e(url('/portal/invoices/' . $i['id'])) ?>"><?= e($i['invoice_number']) ?></a></td>
          <td><?= status_pill((string) $i['status']) ?></td>
          <td><?= !empty($i['issue_date']) ? fmt_date((string) $i['issue_date']) : '—' ?></td>
          <td><?= !empty($i['due_date']) ? fmt_date((string) $i['due_date']) : '—' ?></td>
          <td class="num"><?= money($i['total'], (string) $i['currency']) ?></td>
          <td class="num"><?= money($i['paid'], (string) $i['currency']) ?></td>
          <td class="num"><?= money($bal, (string) $i['currency']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody></table>
    <?php endif; ?>
  </div>
<?php endif; ?>

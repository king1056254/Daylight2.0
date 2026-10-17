<?php
use App\Admin\Cells;

/** @var array<string,mixed> $def @var list<array<string,mixed>> $rows @var string $q @var int $page @var int $pages @var int $total @var array<string,array<string,string>> $relLabels @var bool $canWrite @var bool $canCreate */
$slug = (string) $def['slug'];
$qs = $q !== '' ? '&q=' . rawurlencode($q) : '';
?>
<div class="app-title">
  <h1><?= e($def['title']) ?></h1>
  <?php if ($canCreate): ?><a class="btn" href="<?= e(url('/admin/' . $slug . '/new')) ?>">New <?= e($def['singular']) ?></a><?php endif; ?>
</div>
<?php if (!empty($def['search'])): ?>
<form method="get" action="<?= e(url('/admin/' . $slug)) ?>" class="toolbar">
  <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Search…" maxlength="100" aria-label="Search">
  <button type="submit" class="btn btn-outline">Search</button>
  <?php if ($q !== ''): ?><a class="btn btn-ghost" href="<?= e(url('/admin/' . $slug)) ?>">Clear</a><?php endif; ?>
</form>
<?php endif; ?>
<div class="table-wrap">
  <?php if ($rows === []): ?>
    <p class="empty"><?= $q !== '' ? 'Nothing matches your search.' : 'Nothing here yet.' ?></p>
  <?php else: ?>
    <table class="data-table">
      <thead><tr>
        <?php foreach ($def['list'] as $name): $f = $def['fields'][$name] ?? ['label' => ucfirst(str_replace('_', ' ', (string) $name)), 'type' => 'text']; ?>
          <th class="<?= in_array($f['type'], ['decimal', 'int'], true) ? 'num' : '' ?>"><?= e($f['label']) ?></th>
        <?php endforeach; ?>
        <th></th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <?php foreach ($def['list'] as $i => $name):
              $f = $def['fields'][$name] ?? ['name' => $name, 'label' => (string) $name, 'type' => $name === 'created_at' ? 'datetime' : 'text'];
          ?>
            <td class="<?= in_array($f['type'], ['decimal', 'int'], true) ? 'num' : '' ?>">
              <?php if ($i === 0): ?><a class="text-primary font-medium" href="<?= e(url('/admin/' . $slug . '/' . $row['id'])) ?>"><?= Cells::render($f, $row[$name] ?? null, $relLabels) ?></a>
              <?php else: ?><?= Cells::render($f, $row[$name] ?? null, $relLabels) ?><?php endif; ?>
            </td>
          <?php endforeach; ?>
          <td class="num"><a class="link-quiet" href="<?= e(url('/admin/' . $slug . '/' . $row['id'])) ?>"><?= $canWrite ? 'Edit' : 'View' ?></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<div class="pager">
  <span><?= (int) $total ?> record<?= $total === 1 ? '' : 's' ?></span>
  <span class="flex gap-3 items-center">
    <?php if ($page > 1): ?><a class="btn btn-outline btn-sm" href="<?= e(url('/admin/' . $slug . '?page=' . ($page - 1) . $qs)) ?>">Previous</a><?php endif; ?>
    <span>Page <?= (int) $page ?> of <?= (int) $pages ?></span>
    <?php if ($page < $pages): ?><a class="btn btn-outline btn-sm" href="<?= e(url('/admin/' . $slug . '?page=' . ($page + 1) . $qs)) ?>">Next</a><?php endif; ?>
  </span>
</div>

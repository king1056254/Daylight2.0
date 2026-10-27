<?php
declare(strict_types=1);
// Usage: php bin/purge.php [--dry-run]   (removes data older than the retention periods in the privacy policy)
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}
require dirname(__DIR__) . '/app/bootstrap.php';

if (in_array('--dry-run', $argv, true)) {
    foreach (\App\Core\Retention::preview() as $row) {
        echo str_pad((string) $row['count'], 6, ' ', STR_PAD_LEFT) . '  ' . $row['label'] . "\n";
    }
    exit(0);
}
foreach (\App\Core\Retention::purge() as $table => $n) {
    echo str_pad((string) $n, 6, ' ', STR_PAD_LEFT) . "  deleted from {$table}\n";
}

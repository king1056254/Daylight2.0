<?php
declare(strict_types=1);
// Usage: php bin/migrate.php [--seed-content]
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}
require dirname(__DIR__) . '/app/bootstrap.php';

$applied = \App\Core\Migrator::migrate(ROOT . '/database/migrations');
echo $applied === [] ? "Nothing to migrate.\n" : 'Applied: ' . implode(', ', $applied) . "\n";
if (in_array('--seed-content', $argv, true)) {
    echo \App\Core\Migrator::seedContent(ROOT . '/database/seeds/content.sql')
        ? "Original site content loaded.\n"
        : "Content already present; seed skipped.\n";
}

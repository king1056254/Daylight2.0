<?php
declare(strict_types=1);

namespace App\Core;

/** Runs ordered .sql files from database/migrations once each, and the optional content seed. */
final class Migrator
{
    /** Split SQL into statements, honouring quoted strings and "-- " comments. @return list<string> */
    public static function split(string $sql): array
    {
        $out = [];
        $buf = '';
        $len = strlen($sql);
        $quote = '';
        for ($i = 0; $i < $len; $i++) {
            $c = $sql[$i];
            if ($quote !== '') {
                $buf .= $c;
                if ($c === '\\' && $i + 1 < $len) {
                    $buf .= $sql[++$i];
                } elseif ($c === $quote) {
                    if ($i + 1 < $len && $sql[$i + 1] === $quote) {
                        $buf .= $sql[++$i];
                    } else {
                        $quote = '';
                    }
                }
                continue;
            }
            if ($c === "'" || $c === '"' || $c === '`') {
                $quote = $c;
                $buf .= $c;
            } elseif ($c === '-' && $i + 1 < $len && $sql[$i + 1] === '-' && ($i + 2 >= $len || $sql[$i + 2] === ' ' || $sql[$i + 2] === "\n")) {
                while ($i < $len && $sql[$i] !== "\n") {
                    $i++;
                }
                $buf .= "\n";
            } elseif ($c === ';') {
                if (trim($buf) !== '') {
                    $out[] = trim($buf);
                }
                $buf = '';
            } else {
                $buf .= $c;
            }
        }
        if (trim($buf) !== '') {
            $out[] = trim($buf);
        }
        return $out;
    }

    /** @return list<string> filenames applied in this run */
    public static function migrate(string $dir): array
    {
        $pdo = Db::pdo();
        $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (filename VARCHAR(190) NOT NULL PRIMARY KEY, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $done = array_column(Db::all('SELECT filename FROM schema_migrations'), 'filename');
        $files = glob(rtrim($dir, '/') . '/*.sql') ?: [];
        sort($files);
        $applied = [];
        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $done, true)) {
                continue;
            }
            foreach (self::split((string) file_get_contents($file)) as $stmt) {
                $pdo->exec($stmt);
            }
            Db::exec('INSERT INTO schema_migrations (filename) VALUES (?)', [$name]);
            $applied[] = $name;
        }
        return $applied;
    }

    /** Loads the original site content once. Returns false if content already exists. */
    public static function seedContent(string $file): bool
    {
        if ((int) Db::value('SELECT COUNT(*) FROM services') > 0) {
            return false;
        }
        $pdo = Db::pdo();
        foreach (self::split((string) file_get_contents($file)) as $stmt) {
            $pdo->exec($stmt);
        }
        return true;
    }
}

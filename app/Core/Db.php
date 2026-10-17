<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

/** Thin PDO wrapper. All values go through prepared statements; identifiers are whitelisted by regex. */
final class Db
{
    private static ?PDO $pdo = null;

    public static function setPdo(?PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $host = Config::get('DB_HOST', '127.0.0.1');
            $port = Config::get('DB_PORT', '3306');
            $name = Config::get('DB_NAME', '');
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            self::$pdo = new PDO($dsn, Config::get('DB_USER', '') ?? '', Config::get('DB_PASS', '') ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            self::$pdo->exec("SET NAMES utf8mb4");
            self::$pdo->exec("SET time_zone = '+00:00'");
        }
        return self::$pdo;
    }

    /** @param list<mixed> $params */
    private static function run(string $sql, array $params): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $i = 1;
        foreach ($params as $p) {
            if (is_int($p)) {
                $stmt->bindValue($i, $p, PDO::PARAM_INT);
            } elseif (is_bool($p)) {
                $stmt->bindValue($i, $p ? 1 : 0, PDO::PARAM_INT);
            } elseif ($p === null) {
                $stmt->bindValue($i, null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue($i, (string) $p, PDO::PARAM_STR);
            }
            $i++;
        }
        $stmt->execute();
        return $stmt;
    }

    /** @param list<mixed> $params @return list<array<string,mixed>> */
    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** @param list<mixed> $params @return array<string,mixed>|null */
    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** @param list<mixed> $params */
    public static function value(string $sql, array $params = []): mixed
    {
        $v = self::run($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    /** @param list<mixed> $params */
    public static function exec(string $sql, array $params = []): int
    {
        return self::run($sql, $params)->rowCount();
    }

    public static function uuid(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }

    public static function ident(string $name): string
    {
        if (preg_match('/^[a-z][a-z0-9_]*$/', $name) !== 1) {
            throw new \InvalidArgumentException('Invalid identifier');
        }
        return '`' . $name . '`';
    }

    /** @param array<string,mixed> $row */
    private static function prepareValue(mixed $v): mixed
    {
        if (is_array($v)) {
            return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return $v;
    }

    /** Inserts a row with a generated UUID primary key (unless 'id' given). Returns the id. @param array<string,mixed> $row */
    public static function insert(string $table, array $row, bool $uuid = true): string
    {
        $id = (string) ($row['id'] ?? '');
        if ($uuid && $id === '') {
            $id = self::uuid();
            $row = ['id' => $id] + $row;
        }
        $cols = [];
        $marks = [];
        $vals = [];
        foreach ($row as $c => $v) {
            $cols[] = self::ident((string) $c);
            $marks[] = '?';
            $vals[] = self::prepareValue($v);
        }
        self::exec('INSERT INTO ' . self::ident($table) . ' (' . implode(',', $cols) . ') VALUES (' . implode(',', $marks) . ')', $vals);
        return $id;
    }

    /** @param array<string,mixed> $row */
    public static function update(string $table, string $id, array $row): int
    {
        if ($row === []) {
            return 0;
        }
        $sets = [];
        $vals = [];
        foreach ($row as $c => $v) {
            $sets[] = self::ident((string) $c) . ' = ?';
            $vals[] = self::prepareValue($v);
        }
        $vals[] = $id;
        return self::exec('UPDATE ' . self::ident($table) . ' SET ' . implode(',', $sets) . ' WHERE `id` = ?', $vals);
    }
}

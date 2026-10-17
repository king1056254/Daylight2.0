<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;

/** Read access to public, published content. Table/order names come from this whitelist only. */
final class Content
{
    /** table => [where clause, order by, json columns] */
    private const TABLES = [
        'services' => ['published = 1', 'sort_order ASC, created_at ASC', ['features']],
        'industries' => ['published = 1', 'sort_order ASC, created_at ASC', []],
        'technologies' => ['published = 1', 'sort_order ASC, created_at ASC', []],
        'portfolio_projects' => ['published = 1', 'sort_order ASC, created_at ASC', ['technologies', 'features']],
        'saas_products' => ['published = 1', 'sort_order ASC, created_at ASC', ['features']],
        'blog_posts' => ['published = 1', 'published_at DESC', ['tags']],
        'team_members' => ['published = 1', 'sort_order ASC, created_at ASC', []],
        'testimonials' => ['published = 1', 'sort_order ASC, created_at ASC', []],
        'faqs' => ['published = 1', 'sort_order ASC, created_at ASC', []],
        'job_openings' => ["status = 'Open'", 'title ASC', ['requirements']],
    ];

    /** @return list<array<string,mixed>> */
    public static function all(string $table, ?int $limit = null): array
    {
        [$where, $order, $json] = self::def($table);
        $sql = "SELECT * FROM `{$table}` WHERE {$where} ORDER BY {$order}" . ($limit !== null ? ' LIMIT ?' : '');
        $rows = Db::all($sql, $limit !== null ? [$limit] : []);
        return array_map(static fn (array $r): array => self::decode($r, $json), $rows);
    }

    /** @return array<string,mixed>|null */
    public static function bySlug(string $table, string $slug): ?array
    {
        [$where, , $json] = self::def($table);
        $row = Db::one("SELECT * FROM `{$table}` WHERE slug = ? AND {$where}", [$slug]);
        return $row === null ? null : self::decode($row, $json);
    }

    /** @return array<string,mixed>|null */
    public static function byId(string $table, string $id): ?array
    {
        [$where, , $json] = self::def($table);
        $row = Db::one("SELECT * FROM `{$table}` WHERE id = ? AND {$where}", [$id]);
        return $row === null ? null : self::decode($row, $json);
    }

    /** @return array<string,mixed> */
    public static function settings(): array
    {
        return Db::one('SELECT * FROM company_settings ORDER BY updated_at DESC LIMIT 1') ?? [];
    }

    /** @return array{0:string,1:string,2:list<string>} */
    private static function def(string $table): array
    {
        if (!isset(self::TABLES[$table])) {
            throw new \InvalidArgumentException('Unknown content table');
        }
        return self::TABLES[$table];
    }

    /** @param array<string,mixed> $row @param list<string> $json @return array<string,mixed> */
    private static function decode(array $row, array $json): array
    {
        foreach ($json as $col) {
            $row[$col] = as_list($row[$col] ?? null);
        }
        return $row;
    }
}

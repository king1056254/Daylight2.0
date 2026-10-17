<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Auth;

final class Resources
{
    private const GROUP_ORDER = ['Inbox', 'Clients', 'Billing', 'Website content'];

    /** @return array<string,array<string,mixed>> */
    public static function all(): array
    {
        static $cache = null;
        if ($cache === null) {
            /** @var array<string,array<string,mixed>> $defs */
            $defs = require ROOT . '/config/resources.php';
            foreach ($defs as $slug => &$def) {
                $def['slug'] = $slug;
                foreach ($def['fields'] as $name => &$f) {
                    $f['name'] = $name;
                    $f['type'] = $f['type'] ?? 'text';
                    $f['label'] = $f['label'] ?? ucfirst(str_replace('_', ' ', $name));
                }
                unset($f);
            }
            unset($def);
            $cache = $defs;
        }
        return $cache;
    }

    /** @return array<string,mixed>|null */
    public static function get(string $slug): ?array
    {
        return self::all()[$slug] ?? null;
    }

    /** @param array<string,mixed> $def */
    public static function canRead(array $def): bool
    {
        return $def['visible'] === 'admin' ? Auth::isAdmin() : Auth::isStaff();
    }

    /** @param array<string,mixed> $def */
    public static function canWrite(array $def): bool
    {
        return $def['write'] === 'admin' ? Auth::isAdmin() : Auth::isStaff();
    }

    /** @return list<array{0:string,1:list<array{0:string,1:string}>}> */
    public static function nav(): array
    {
        $groups = ['Overview' => [['/admin', 'Dashboard']]];
        foreach (self::GROUP_ORDER as $g) {
            $groups[$g] = [];
        }
        foreach (self::all() as $slug => $def) {
            if (self::canRead($def)) {
                $groups[(string) $def['group']][] = ['/admin/' . $slug, (string) $def['title']];
            }
        }
        if (Auth::isAdmin()) {
            $groups['Access'] = [['/admin/users', 'Users & roles']];
        }
        $out = [];
        foreach ($groups as $name => $links) {
            if ($links !== []) {
                $out[] = [$name, $links];
            }
        }
        return $out;
    }
}

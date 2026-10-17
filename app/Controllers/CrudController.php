<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Admin\Hooks;
use App\Admin\Resources;
use App\Core\Db;
use App\Core\Errors;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

/** Generic admin screens driven by config/resources.php. */
final class CrudController extends Controller
{
    private const PER_PAGE = 20;

    /** @param array<string,string> $p @return array{0:array<string,mixed>|null,1:Response|null} */
    private static function load(array $p, bool $write): array
    {
        if (($g = self::requireStaff()) !== null) {
            return [null, $g];
        }
        $def = Resources::get($p['resource'] ?? '');
        if ($def === null) {
            return [null, self::message('404', 'Not found', "That admin section doesn't exist.", 404)];
        }
        if (!Resources::canRead($def) || ($write && !Resources::canWrite($def))) {
            return [null, self::message('403', 'Not allowed', "Your role can't do that here.", 403)];
        }
        return [$def, null];
    }

    private static function validId(string $id): bool
    {
        return preg_match('/^[0-9a-fA-F\-]{36}$/', $id) === 1;
    }

    /** @param array<string,mixed> $def @return array<string,mixed>|null */
    private static function find(array $def, string $id): ?array
    {
        return self::validId($id) ? Db::one('SELECT * FROM ' . Db::ident((string) $def['table']) . ' WHERE id = ?', [$id]) : null;
    }

    /** @param array<string,mixed> $def */
    private static function count(array $def): int
    {
        return (int) Db::value('SELECT COUNT(*) FROM ' . Db::ident((string) $def['table']));
    }

    /** @param array<string,string> $p */
    public static function index(Request $r, array $p): Response
    {
        [$def, $err] = self::load($p, false);
        if ($err !== null) {
            return $err;
        }
        $q = trim(mb_substr($r->query('q'), 0, 100));
        $page = max(1, min(100000, (int) $r->query('page', '1')));
        $where = '';
        $params = [];
        if ($q !== '' && !empty($def['search'])) {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $parts = [];
            foreach ($def['search'] as $col) {
                $parts[] = Db::ident((string) $col) . ' LIKE ?';
                $params[] = $like;
            }
            $where = ' WHERE (' . implode(' OR ', $parts) . ')';
        }
        $table = Db::ident((string) $def['table']);
        $total = (int) Db::value("SELECT COUNT(*) FROM {$table}{$where}", $params);
        $rows = Db::all(
            "SELECT * FROM {$table}{$where} ORDER BY {$def['order']} LIMIT ? OFFSET ?",
            array_merge($params, [self::PER_PAGE, ($page - 1) * self::PER_PAGE])
        );
        return self::app('admin', 'admin/list', [
            'def' => $def, 'rows' => $rows, 'q' => $q, 'page' => $page,
            'pages' => max(1, (int) ceil($total / self::PER_PAGE)), 'total' => $total,
            'relLabels' => self::relLabels($def, $rows),
            'canWrite' => Resources::canWrite($def),
            'canCreate' => Resources::canWrite($def) && empty($def['no_create']) && !(!empty($def['single']) && $total > 0),
        ], (string) $def['title'] . ' — Admin');
    }

    /** @param array<string,string> $p */
    public static function create(Request $r, array $p): Response
    {
        [$def, $err] = self::load($p, true);
        if ($err !== null) {
            return $err;
        }
        if (!empty($def['no_create']) || (!empty($def['single']) && self::count($def) > 0)) {
            return self::message('403', 'Not allowed', "New records can't be added here.", 403);
        }
        $values = [];
        foreach ($def['fields'] as $name => $f) {
            $values[$name] = isset($f['default']) ? (string) $f['default'] : '';
        }
        return self::form($def, null, $values, []);
    }

    /** @param array<string,string> $p */
    public static function store(Request $r, array $p): Response
    {
        [$def, $err] = self::load($p, true);
        if ($err !== null) {
            return $err;
        }
        if (($c = self::csrfFailure($r)) !== null) {
            return $c;
        }
        if (!empty($def['no_create']) || (!empty($def['single']) && self::count($def) > 0)) {
            return self::message('403', 'Not allowed', "New records can't be added here.", 403);
        }
        return self::save($r, $def, null);
    }

    /** @param array<string,string> $p */
    public static function edit(Request $r, array $p): Response
    {
        [$def, $err] = self::load($p, false);
        if ($err !== null) {
            return $err;
        }
        $row = self::find($def, $p['id']);
        if ($row === null) {
            return self::message('404', 'Not found', "We couldn't find that record.", 404);
        }
        return self::form($def, $row, self::valuesFromRow($def, $row), []);
    }

    /** @param array<string,string> $p */
    public static function update(Request $r, array $p): Response
    {
        [$def, $err] = self::load($p, true);
        if ($err !== null) {
            return $err;
        }
        if (($c = self::csrfFailure($r)) !== null) {
            return $c;
        }
        $row = self::find($def, $p['id']);
        if ($row === null) {
            return self::message('404', 'Not found', "We couldn't find that record.", 404);
        }
        return self::save($r, $def, $row);
    }

    /** @param array<string,string> $p */
    public static function destroy(Request $r, array $p): Response
    {
        [$def, $err] = self::load($p, true);
        if ($err !== null) {
            return $err;
        }
        if (($c = self::csrfFailure($r)) !== null) {
            return $c;
        }
        $row = self::find($def, $p['id']);
        if ($row === null) {
            return self::message('404', 'Not found', "We couldn't find that record.", 404);
        }
        if (!empty($def['single'])) {
            return self::message('403', 'Not allowed', "This record can't be deleted.", 403);
        }
        try {
            Db::exec('DELETE FROM ' . Db::ident((string) $def['table']) . ' WHERE id = ?', [$row['id']]);
            foreach ((array) ($def['after_delete'] ?? []) as $hook) {
                Hooks::after((string) $hook, $row, null);
            }
        } catch (\PDOException $e) {
            Errors::log('admin delete failed: ' . $e->getMessage());
            self::notice('error', "Couldn't delete that: it is still in use by other records.");
            return Response::redirect('/admin/' . $def['slug']);
        }
        self::notice('success', ucfirst((string) $def['singular']) . ' deleted.');
        return Response::redirect('/admin/' . $def['slug']);
    }

    // ------------------------------------------------------------------

    /** @param array<string,mixed> $def @param array<string,mixed>|null $existing */
    private static function save(Request $r, array $def, ?array $existing): Response
    {
        $rules = [];
        foreach ($def['fields'] as $name => $f) {
            if (empty($f['readonly'])) {
                $rules[$name] = array_intersect_key($f, array_flip(['type', 'label', 'required', 'max', 'options', 'min', 'maxval']));
            }
        }
        [$clean, $errors] = Validator::validate($r->post(), $rules);

        if (isset($rules['slug']) && empty($clean['slug']) && empty($errors['slug']) && !empty($def['slug_from'])) {
            $base = Validator::slugify((string) ($clean[$def['slug_from']] ?? ''));
            if ($base === '') {
                $errors['slug'] = 'Enter a slug.';
            } else {
                $clean['slug'] = self::uniqueSlug($def, $base, $existing['id'] ?? null);
            }
        }
        foreach ($def['fields'] as $name => $f) {
            if (($f['type'] ?? '') === 'rel' && empty($f['readonly']) && !empty($clean[$name]) && empty($errors[$name])) {
                $rel = $f['rel'];
                if (Db::value('SELECT COUNT(*) FROM ' . Db::ident((string) $rel['table']) . ' WHERE id = ?', [$clean[$name]]) < 1) {
                    $errors[$name] = 'Choose a valid ' . strtolower((string) $f['label']) . '.';
                }
            }
        }
        foreach ((array) ($def['unique'] ?? []) as $col) {
            if (!empty($clean[$col]) && empty($errors[$col])) {
                $dup = Db::value(
                    'SELECT COUNT(*) FROM ' . Db::ident((string) $def['table']) . ' WHERE ' . Db::ident((string) $col) . ' = ? AND id <> ?',
                    [$clean[$col], $existing['id'] ?? '']
                );
                if ($dup > 0) {
                    $errors[$col] = ucfirst(str_replace('_', ' ', (string) $col)) . ' is already used.';
                }
            }
        }
        if ($errors === []) {
            try {
                foreach ((array) ($def['before'] ?? []) as $hook) {
                    $clean = Hooks::before((string) $hook, $clean, $existing);
                }
            } catch (\Throwable $e) {
                Errors::log('admin before-hook failed: ' . $e->getMessage());
                $errors['_form'] = "Couldn't prepare the record. Please try again.";
            }
        }
        if ($errors !== []) {
            return self::form($def, $existing, self::valuesFromPost($def, $r), $errors, 422);
        }

        try {
            if ($existing === null) {
                $row = array_filter($clean, static fn ($v): bool => $v !== null && $v !== '');
                $id = Db::insert((string) $def['table'], $row);
            } else {
                $row = [];
                foreach ($clean as $k => $v) {
                    $f = $def['fields'][$k] ?? [];
                    if (($v === null || $v === '') && (!empty($f['notnull']) || array_key_exists('default', $f))) {
                        continue;
                    }
                    $row[$k] = ($v === '') ? null : $v;
                }
                $id = (string) $existing['id'];
                Db::update((string) $def['table'], $id, $row);
            }
            $fresh = self::find($def, $id) ?? ['id' => $id];
            foreach ((array) ($def['after'] ?? []) as $hook) {
                Hooks::after((string) $hook, $fresh, $existing);
            }
        } catch (\Throwable $e) {
            Errors::log('admin save failed: ' . $e->getMessage());
            return self::form($def, $existing, self::valuesFromPost($def, $r), ['_form' => "Couldn't save. Please check the values and try again."], 500);
        }
        self::notice('success', ucfirst((string) $def['singular']) . ($existing === null ? ' created.' : ' saved.'));
        return Response::redirect('/admin/' . $def['slug']);
    }

    /** @param array<string,mixed> $def */
    private static function uniqueSlug(array $def, string $base, mixed $selfId): string
    {
        $slug = $base;
        $n = 1;
        while (Db::value('SELECT COUNT(*) FROM ' . Db::ident((string) $def['table']) . ' WHERE slug = ? AND id <> ?', [$slug, (string) ($selfId ?? '')]) > 0) {
            $n++;
            $slug = $base . '-' . $n;
        }
        return $slug;
    }

    /**
     * @param array<string,mixed> $def @param array<string,mixed>|null $row
     * @param array<string,string> $values @param array<string,string> $errors
     */
    private static function form(array $def, ?array $row, array $values, array $errors, int $status = 200): Response
    {
        $options = [];
        foreach ($def['fields'] as $name => $f) {
            if ($f['type'] === 'rel') {
                $rel = $f['rel'];
                $options[$name] = Db::all(
                    'SELECT id, ' . Db::ident((string) $rel['label']) . ' AS label FROM ' . Db::ident((string) $rel['table'])
                    . ' ORDER BY ' . Db::ident((string) $rel['label']) . ' LIMIT 500'
                );
            }
        }
        $canWrite = Resources::canWrite($def);
        return self::app('admin', 'admin/form', [
            'def' => $def, 'row' => $row, 'values' => $values, 'errors' => $errors, 'options' => $options,
            'canWrite' => $canWrite,
            'canDelete' => $canWrite && $row !== null && empty($def['single']),
        ], ($row === null ? 'New ' : 'Edit ') . $def['singular'] . ' — Admin', $status);
    }

    /** @param array<string,mixed> $def @param array<string,mixed> $row @return array<string,string> */
    private static function valuesFromRow(array $def, array $row): array
    {
        $v = [];
        foreach ($def['fields'] as $name => $f) {
            $raw = $row[$name] ?? null;
            if ($raw === null) {
                $v[$name] = '';
            } elseif ($f['type'] === 'list') {
                $v[$name] = implode("\n", as_list($raw));
            } elseif ($f['type'] === 'datetime') {
                $t = strtotime((string) $raw);
                $v[$name] = $t === false ? '' : ($f['readonly'] ?? false ? gmdate('Y-m-d H:i', $t) : gmdate('Y-m-d\TH:i', $t));
            } else {
                $v[$name] = (string) $raw;
            }
        }
        return $v;
    }

    /** @param array<string,mixed> $def @return array<string,string> */
    private static function valuesFromPost(array $def, Request $r): array
    {
        $v = [];
        foreach ($def['fields'] as $name => $f) {
            if ($f['type'] === 'bool') {
                $raw = $r->input($name);
                $v[$name] = ($raw !== '' && $raw !== '0') ? '1' : '0';
            } else {
                $v[$name] = mb_substr($r->input($name), 0, 100000);
            }
        }
        return $v;
    }

    /**
     * @param array<string,mixed> $def @param list<array<string,mixed>> $rows
     * @return array<string,array<string,string>>
     */
    private static function relLabels(array $def, array $rows): array
    {
        $out = [];
        foreach ($def['list'] as $name) {
            $f = $def['fields'][$name] ?? null;
            if ($f === null || $f['type'] !== 'rel') {
                continue;
            }
            $ids = array_values(array_unique(array_filter(array_column($rows, $name), static fn ($v): bool => $v !== null && $v !== '')));
            if ($ids === []) {
                continue;
            }
            $rel = $f['rel'];
            $found = Db::all(
                'SELECT id, ' . Db::ident((string) $rel['label']) . ' AS label FROM ' . Db::ident((string) $rel['table'])
                . ' WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')',
                $ids
            );
            foreach ($found as $x) {
                $out[$name][(string) $x['id']] = (string) $x['label'];
            }
        }
        return $out;
    }
}

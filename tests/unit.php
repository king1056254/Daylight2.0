<?php
declare(strict_types=1);
// Run: php tests/unit.php   (no database needed)
require __DIR__ . '/bootstrap.php';

use App\Admin\Resources;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Mailer;
use App\Core\Migrator;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Validator;

$_SESSION = [];

test('Config::parse handles comments, quotes and blanks', function (): void {
    $c = Config::parse("# c\nAPP_ENV=production\nNAME=\"Daylight Ltd\"\nEMPTY=\nbad line\nlower=ignored\nPASS='a=b'\n");
    assertSame('production', $c['APP_ENV']);
    assertSame('Daylight Ltd', $c['NAME']);
    assertSame('', $c['EMPTY']);
    assertSame('a=b', $c['PASS']);
    assertTrue(!isset($c['lower']), 'lowercase keys are ignored');
});

test('Request::normalizePath trims and keeps root', function (): void {
    assertSame('/', Request::normalizePath(''));
    assertSame('/', Request::normalizePath('/'));
    assertSame('/services/web', Request::normalizePath('/services/web/'));
});

test('url() honours APP_BASE_PATH', function (): void {
    Config::set('APP_BASE_PATH', '');
    assertSame('/contact', url('/contact'));
    assertSame('/', url('/'));
    Config::set('APP_BASE_PATH', '/daylight');
    assertSame('/daylight/contact', url('/contact'));
    Config::set('APP_BASE_PATH', '');
});

test('e() escapes HTML and quotes', function (): void {
    assertSame('&lt;script&gt;alert(&#039;x&#039;)&lt;/script&gt;', e("<script>alert('x')</script>"));
    assertSame('a &amp; b &quot;c&quot;', e('a & b "c"'));
});

test('as_list decodes JSON arrays and tolerates junk', function (): void {
    assertSame(['a', 'b'], as_list('["a","b"]'));
    assertSame([], as_list(null));
    assertSame([], as_list('not json'));
    assertSame(['1'], as_list([1]));
});

test('status_pill picks the right tone and escapes', function (): void {
    assertContains('pill-success', status_pill('Live'));
    assertContains('pill-muted', status_pill('Draft'));
    assertContains('pill-danger', status_pill('Overdue'));
    assertContains('pill-primary', status_pill('In Progress'));
    assertContains('&lt;b&gt;', status_pill('<b>'));
});

test('money formats with currency', function (): void {
    assertSame('KES 1,234.50', money('1234.5'));
});

test('Validator: required, email, select, bool', function (): void {
    [$c, $e] = Validator::validate(
        ['name' => '', 'email' => 'nope', 'kind' => 'x'],
        ['name' => ['required' => true], 'email' => ['type' => 'email'], 'kind' => ['type' => 'select', 'options' => ['a', 'b']], 'flag' => ['type' => 'bool']]
    );
    assertTrue(isset($e['name']), 'name required');
    assertTrue(isset($e['email']), 'email invalid');
    assertTrue(isset($e['kind']), 'select invalid');
    assertSame(0, $c['flag']);
    [$c2, $e2] = Validator::validate(['email' => 'A@B.co', 'flag' => '1'], ['email' => ['type' => 'email', 'required' => true], 'flag' => ['type' => 'bool']]);
    assertSame([], $e2);
    assertSame('a@b.co', $c2['email']);
    assertSame(1, $c2['flag']);
});

test('Validator: url only allows http(s)', function (): void {
    [, $e] = Validator::validate(['u' => 'javascript:alert(1)'], ['u' => ['type' => 'url']]);
    assertTrue(isset($e['u']), 'javascript: rejected');
    [, $e2] = Validator::validate(['u' => 'https://example.com/cv'], ['u' => ['type' => 'url']]);
    assertSame([], $e2);
});

test('Validator: lengths, ints, dates, lists, slugs', function (): void {
    [, $e] = Validator::validate(['t' => str_repeat('a', 301)], ['t' => []]);
    assertTrue(isset($e['t']), 'default 300 cap');
    [, $e] = Validator::validate(['m' => str_repeat('a', 5001)], ['m' => ['type' => 'textarea']]);
    assertTrue(isset($e['m']), 'default 5000 cap');
    [$c, $e] = Validator::validate(['n' => '7'], ['n' => ['type' => 'int', 'min' => 1, 'maxval' => 5]]);
    assertTrue(isset($e['n']), 'int above max');
    [$c, $e] = Validator::validate(['d' => '2026-02-30'], ['d' => ['type' => 'date']]);
    assertTrue(isset($e['d']), 'impossible date');
    [$c, $e] = Validator::validate(['l' => "a\n\n b \n"], ['l' => ['type' => 'list']]);
    assertSame(['a', 'b'], $c['l']);
    [$c, $e] = Validator::validate(['s' => 'Bad Slug'], ['s' => ['type' => 'slug']]);
    assertTrue(isset($e['s']), 'slug format');
    assertSame('hello-world', Validator::slugify(' Hello, World! '));
});

test('Validator::clean strips control characters', function (): void {
    assertSame('ab c', Validator::clean("a\x00b\x07   c", false));
    assertSame("a\nb", Validator::clean("a\r\nb", true));
});

test('Router matches params, 404 and 405', function (): void {
    $r = new Router();
    $r->get('/services/{slug}', fn () => 'x');
    $r->post('/contact', fn () => 'y');
    $m = $r->match('GET', '/services/web-apps');
    assertTrue(is_array($m), 'matched');
    assertSame('web-apps', $m[1]['slug']);
    assertSame(404, $r->match('GET', '/nope'));
    assertSame(405, $r->match('GET', '/contact'));
    assertSame(404, $r->match('GET', '/services/a/b'), 'slug cannot contain slash');
});

test('Router prefers fixed routes registered first', function (): void {
    $r = new Router();
    $r->get('/admin/users/new', fn () => 'new');
    $r->get('/admin/{resource}/{id}', fn () => 'generic');
    $m = $r->match('GET', '/admin/users/new');
    assertSame('new', ($m[0])());
});

test('Migrator::split respects quotes, escaped quotes and comments', function (): void {
    $sql = "-- header; ignored\nCREATE TABLE a (x INT);\nINSERT INTO a VALUES ('it''s; fine');\n-- tail\nINSERT INTO a VALUES (\"x;y\");";
    $parts = Migrator::split($sql);
    assertSame(3, count($parts));
    assertContains("'it''s; fine'", $parts[1]);
    assertContains('"x;y"', $parts[2]);
});

test('Migrator::split can split the real migration and seed files', function (): void {
    foreach (glob(ROOT . '/database/migrations/*.sql') ?: [] as $f) {
        assertTrue(count(Migrator::split((string) file_get_contents($f))) > 0, basename($f));
    }
    $seed = Migrator::split((string) file_get_contents(ROOT . '/database/seeds/content.sql'));
    assertSame(11, count($seed), 'seed statements');
});

test('Mailer header safety and URL building', function (): void {
    assertSame('a@b.co Bcc: x@y.z', Mailer::cleanHeader("a@b.co\r\nBcc: x@y.z"));
    Config::set('APP_URL', '');
    assertSame(null, Mailer::absoluteUrl('/auth/reset/abc'));
    Config::set('APP_URL', 'https://example.com/evil/path');
    assertSame(null, Mailer::absoluteUrl('/x'), 'APP_URL must be an origin');
    Config::set('APP_URL', 'https://example.com');
    assertSame('https://example.com/auth/verify/t', Mailer::absoluteUrl('/auth/verify/t'));
    Config::set('APP_URL', '');
});

test('Auth::passwordError enforces length', function (): void {
    assertTrue(Auth::passwordError('short') !== null);
    assertSame(null, Auth::passwordError('long-enough-1'));
    assertTrue(Auth::passwordError(str_repeat('a', 73)) !== null);
});

test('Csrf token verifies and rejects', function (): void {
    $_SESSION = [];
    $t = Csrf::token();
    assertSame(64, strlen($t));
    assertTrue(Csrf::verify(new Request('POST', '/x', [], ['_csrf' => $t], [])));
    assertTrue(!Csrf::verify(new Request('POST', '/x', [], ['_csrf' => 'wrong'], [])));
    assertTrue(!Csrf::verify(new Request('POST', '/x', [], [], [])));
});

test('Response::redirect only allows internal paths', function (): void {
    assertSame('/ok', Response::redirect('/ok')->headers['Location']);
    assertSame('/', Response::redirect('//evil.example')->headers['Location']);
    assertSame('/', Response::redirect('https://evil.example')->headers['Location']);
    assertSame('/', Response::redirect("/a\r\nSet-Cookie: x=1")->headers['Location']);
});

test('Request::ip ignores X-Forwarded-For unless trusted', function (): void {
    $server = ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '203.0.113.9'];
    Config::set('TRUST_PROXY_HEADERS', 'false');
    assertSame('10.0.0.1', (new Request('GET', '/', [], [], $server))->ip());
    Config::set('TRUST_PROXY_HEADERS', 'true');
    assertSame('203.0.113.9', (new Request('GET', '/', [], [], $server))->ip());
    Config::set('TRUST_PROXY_HEADERS', 'false');
});

test('Admin resources are well-formed', function (): void {
    $hooks = (string) file_get_contents(ROOT . '/app/Admin/Hooks.php');
    foreach (Resources::all() as $slug => $def) {
        foreach (['title', 'singular', 'group', 'visible', 'write', 'table', 'order', 'list', 'fields'] as $k) {
            assertTrue(isset($def[$k]), "$slug missing $k");
        }
        foreach ($def['list'] as $col) {
            assertTrue(isset($def['fields'][$col]) || $col === 'created_at', "$slug list column $col");
        }
        foreach (['before', 'after', 'after_delete'] as $kind) {
            foreach ((array) ($def[$kind] ?? []) as $h) {
                assertTrue(str_contains($hooks, "'$h'") || str_contains($hooks, "function $h("), "$slug hook $h");
            }
        }
        foreach ($def['fields'] as $name => $f) {
            if ($f['type'] === 'rel') {
                assertTrue(isset($f['rel']['table'], $f['rel']['label']), "$slug.$name rel");
            }
            if ($f['type'] === 'select') {
                assertTrue(!empty($f['options']), "$slug.$name options");
            }
        }
    }
});

exit(runTests('unit'));

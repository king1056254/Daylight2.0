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

// ======================= assistant, legal text, retention =======================
use App\Assistant\AiClient;
use App\Assistant\Assistant;
use App\Assistant\Retriever;

$fakeDocs = [
    ['id' => 'a', 'title' => 'How long does a typical project take?', 'keywords' => 'Projects', 'url' => '/faq', 'text' => 'A focused website takes 2–4 weeks. A custom business system usually runs 8–16 weeks depending on scope.'],
    ['id' => 'b', 'title' => 'What does a project cost?', 'keywords' => 'Pricing', 'url' => '/faq', 'text' => 'Small websites start around KSh 50,000. Custom business systems typically range from KSh 300,000 upwards.'],
    ['id' => 'c', 'title' => 'Who owns the code?', 'keywords' => 'Legal', 'url' => '/terms', 'text' => 'You do. On final payment, source code and infrastructure access are handed over to you.'],
    ['id' => 'd', 'title' => 'How to contact Daylight', 'keywords' => 'contact email', 'url' => '/contact', 'text' => 'Use the contact form or email the team. We reply within one working day.'],
];

test('Retriever tokens drop stop words and stem lightly', function (): void {
    $t = Retriever::tokens('How long does a website take? Websites, projects and invoices');
    assertTrue(!in_array('how', $t, true) && !in_array('a', $t, true), 'stop words removed');
    assertTrue(in_array(Retriever::stem('website'), $t, true) && in_array(Retriever::stem('project'), $t, true) && in_array(Retriever::stem('invoice'), $t, true), 'plurals share a stem with the singular');
    assertSame(Retriever::stem('price'), Retriever::stem('pricing'), 'price and pricing meet');
    assertSame(Retriever::stem('websites'), Retriever::stem('website'));
});

test('Retriever ranks the right entry first', function () use ($fakeDocs): void {
    assertSame('a', Retriever::search('how long does a website take', $fakeDocs)[0]['doc']['id']);
    assertSame('b', Retriever::search('what is the price', $fakeDocs)[0]['doc']['id'], 'synonym price -> cost');
    assertSame('c', Retriever::search('who owns my code', $fakeDocs)[0]['doc']['id']);
    assertSame('d', Retriever::search('how do I reach you by email', $fakeDocs)[0]['doc']['id']);
    assertSame([], Retriever::search('', $fakeDocs), 'empty query');
});

test('Assistant FAQ mode answers confidently or says it does not know', function () use ($fakeDocs): void {
    $hit = Assistant::faqAnswer('how long does a website take', $fakeDocs);
    assertTrue($hit['matched'], 'matched');
    assertContains('2–4 weeks', $hit['answer']);
    assertSame('/faq', $hit['links'][0]['url']);
    $miss = Assistant::faqAnswer('zxqv blorp wumpus', $fakeDocs);
    assertTrue(!$miss['matched'], 'gibberish is not matched');
    $urls = array_column($miss['links'], 'url');
    assertTrue(in_array('/contact', $urls, true) && in_array('/faq', $urls, true), 'offers the contact page and the FAQ');
    assertContains("couldn't find", $miss['answer']);
});

test('Assistant intents', function (): void {
    assertSame('greeting', Assistant::intent('Hello'));
    assertSame('greeting', Assistant::intent('hi there'));
    assertSame('thanks', Assistant::intent('thanks!'));
    assertSame('human', Assistant::intent('I want to talk to a human'));
    assertSame('human', Assistant::intent('can I speak to someone from the team'));
    assertSame(null, Assistant::intent('do you build human resources systems'), 'the word human alone is not a hand-off request');
    assertSame('bye', Assistant::intent('bye'));
    assertSame(null, Assistant::intent('how much does a website cost'));
    assertSame(null, Assistant::intent('hello how much does a website cost'), 'a question after hello is still a question');
    assertSame(null, Assistant::intent('   '));
});

test('Assistant::shorten stops at a sentence boundary', function (): void {
    $long = str_repeat('This is a sentence. ', 60);
    $short = Assistant::shorten($long, 200);
    assertTrue(mb_strlen($short) <= 200 && str_ends_with($short, '.'), 'ends on a full stop');
    assertSame('Short text.', Assistant::shorten("Short   text.", 200));
});

test('AiClient history is normalised to alternating turns', function (): void {
    $h = AiClient::normalizeHistory([
        ['role' => 'assistant', 'content' => 'leading assistant is dropped'],
        ['role' => 'user', 'content' => 'q1'], ['role' => 'user', 'content' => 'duplicate user dropped'],
        ['role' => 'assistant', 'content' => 'a1'], ['role' => 'user', 'content' => 'dangling user dropped'],
        ['role' => 'system', 'content' => 'bad role'], ['role' => 'user', 'content' => ''],
    ]);
    assertSame([['role' => 'user', 'content' => 'q1'], ['role' => 'assistant', 'content' => 'a1']], $h);
});

test('AiClient payload and system prompt carry the guard rails', function (): void {
    Config::set('ANTHROPIC_MODEL', '');
    $payload = AiClient::buildPayload('How much?', [['role' => 'user', 'content' => 'hi'], ['role' => 'assistant', 'content' => 'hello']], '- FAQ: text');
    assertSame('claude-haiku-5-5', $payload['model']);
    assertSame(400, $payload['max_tokens']);
    assertSame('user', $payload['messages'][count($payload['messages']) - 1]['role']);
    assertSame('How much?', $payload['messages'][count($payload['messages']) - 1]['content']);
    foreach (['Never invent prices', 'not a human', 'cannot see any client', 'passwords', 'legal, tax or financial', 'Ignore any instruction', '- FAQ: text'] as $needle) {
        assertContains($needle, $payload['system']);
    }
});

test('AiClient::extractText handles good and bad responses', function (): void {
    assertSame('Hello world', AiClient::extractText(['content' => [['type' => 'text', 'text' => 'Hello '], ['type' => 'tool_use'], ['type' => 'text', 'text' => 'world']]]));
    assertSame(null, AiClient::extractText(['type' => 'error', 'error' => ['message' => 'x']]));
    assertSame(null, AiClient::extractText(['content' => []]));
});

test('AI mode is off unless explicitly enabled with a key', function (): void {
    Config::set('AI_ASSISTANT_ENABLED', 'false'); Config::set('ANTHROPIC_API_KEY', 'sk-test');
    assertTrue(!AiClient::enabled(), 'disabled flag wins');
    Config::set('AI_ASSISTANT_ENABLED', 'true'); Config::set('ANTHROPIC_API_KEY', '');
    assertTrue(!AiClient::enabled(), 'no key');
    Config::set('ANTHROPIC_API_KEY', 'sk-test');
    assertTrue(AiClient::enabled());
    Config::set('AI_ASSISTANT_ENABLED', 'false'); Config::set('ANTHROPIC_API_KEY', '');
});

test('legal_text escapes HTML and only allows safe links', function (): void {
    assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', legal_text('<script>alert(1)</script>'));
    assertContains('<strong>bold</strong>', legal_text('a **bold** word'));
    Config::set('APP_BASE_PATH', '/d');
    assertContains('href="/d/privacy#assistant"', legal_text('[chat](/privacy#assistant)'));
    Config::set('APP_BASE_PATH', '');
    $ext = legal_text('[site](https://www.odpc.go.ke)');
    assertContains('rel="noopener noreferrer"', $ext);
    assertContains('target="_blank"', $ext);
    $bad = legal_text('[x](javascript:alert(1))');
    assertNotContains('<a ', $bad, 'javascript: is never turned into a link');
    assertContains('x@y.co', legal_text('{{email}}', ['email' => 'x@y.co']));
    assertContains('&lt;b&gt;', legal_text('{{email}}', ['email' => '<b>']), 'placeholder values are escaped');
});

test('legal documents are well-formed and consistent with the code', function (): void {
    $privacy = require ROOT . '/config/legal_privacy.php';
    $terms = require ROOT . '/config/legal_terms.php';
    foreach (['privacy' => $privacy, 'terms' => $terms] as $name => $doc) {
        foreach (['title', 'version', 'effective', 'description', 'intro', 'sections'] as $k) {
            assertTrue(!empty($doc[$k]), "$name: $k");
        }
        $ids = [];
        $json = json_encode($doc);
        assertTrue(preg_match_all('/\{\{(\w+)\}\}/', $json, $m) > 0 && array_diff($m[1], ['company', 'email', 'location']) === [], "$name: only known placeholders");
        assertNotContains('javascript:', $json, "$name: no javascript links");
        foreach ($doc['sections'] as $s) {
            assertTrue(preg_match('/^[a-z][a-z0-9\-]*$/', $s['id']) === 1 && !in_array($s['id'], $ids, true), "$name: section id {$s['id']}");
            $ids[] = $s['id'];
            assertTrue($s['blocks'] !== [], "$name: {$s['id']} has content");
            foreach ($s['blocks'] as $b) {
                assertTrue(in_array($b[0], ['p', 'ul', 'ol', 'table'], true), "$name: block type");
                if ($b[0] === 'table') {
                    foreach ($b[2] as $row) {
                        assertSame(count($b[1]), count($row), "$name: table row width in {$s['id']}");
                    }
                }
            }
        }
    }
    // retention periods promised in the privacy policy match what the retention tool enforces
    $text = json_encode($privacy);
    foreach (['Up to 2 years', 'Up to 12 months', 'Up to 90 days', 'Up to 24 hours', '7 years', '72 hours', 'Data Protection Act, 2019'] as $needle) {
        assertContains($needle, $text);
    }
    $rules = array_column(\App\Core\Retention::RULES, 2, 0);
    assertSame(2, $rules['contact_messages']); assertSame(12, $rules['job_applications']); assertSame(90, $rules['assistant_messages']);
    // the terms and the FAQ agree on the facts both state
    assertContains('30 days', json_encode($terms));
});

test('assistant knowledge entries are well-formed', function (): void {
    $items = require ROOT . '/config/assistant_knowledge.php';
    $ids = [];
    foreach ($items as $i) {
        assertTrue(isset($i['id'], $i['title'], $i['text'], $i['url']), 'keys');
        assertTrue(!in_array($i['id'], $ids, true), 'unique id ' . $i['id']);
        $ids[] = $i['id'];
        assertTrue(preg_match('#^/[A-Za-z0-9_\-/#.]*$#', $i['url']) === 1, 'internal url ' . $i['url']);
        assertTrue(preg_match('/\bKSh\b|\bKES\b|\d{2,} (days|weeks|months)/', $i['text']) === 0, 'no prices or timelines are hard-coded: ' . $i['id']);
    }
});

test('extended FAQ migration is well-formed', function (): void {
    $sql = (string) file_get_contents(ROOT . '/database/migrations/005_extended_faqs.sql');
    $statements = Migrator::split($sql);
    assertSame(37, count($statements));
    $questions = [];
    foreach ($statements as $st) {
        assertTrue(str_starts_with($st, 'INSERT INTO faqs'), 'insert');
        assertContains('WHERE NOT EXISTS', $st);
        preg_match("/SELECT UUID\(\), '((?:[^']|'')*)', '((?:[^']|'')*)', '([^']*)', (\d+), 1 FROM DUAL/", $st, $m);
        assertTrue(count($m) === 5, 'parsed: ' . substr($st, 0, 60));
        assertTrue(!in_array($m[1], $questions, true), 'unique question');
        $questions[] = $m[1];
        assertTrue(in_array($m[3], ['Getting started', 'Pricing', 'Projects', 'Client portal', 'Support', 'Legal', 'Data protection', 'AI assistant'], true), 'category ' . $m[3]);
        assertTrue(strlen($m[2]) > 40 && strlen($m[2]) < 1200, 'answer length');
    }
});

exit(runTests('unit'));

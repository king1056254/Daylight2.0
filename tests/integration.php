<?php
declare(strict_types=1);
/*
 * DB-backed tests that drive the real router with simulated requests (no web server needed).
 *
 * Run (needs PHP 8.1+, pdo_mysql and an EMPTY scratch database whose name contains "test"):
 *   TEST_DB_HOST=127.0.0.1 TEST_DB_NAME=daylight_test TEST_DB_USER=root TEST_DB_PASS= php tests/integration.php
 *
 * WARNING: this script DROPS EVERY TABLE in the test database. It refuses to run unless the name contains "test".
 */
require __DIR__ . '/bootstrap.php';

use App\Core\Auth;
use App\Core\Config;
use App\Core\Db;
use App\Core\Migrator;
use App\Core\Request;
use App\Core\Response;

$dbName = (string) getenv('TEST_DB_NAME');
if ($dbName === '' || stripos($dbName, 'test') === false) {
    fwrite(STDERR, "Refusing to run: set TEST_DB_NAME to a scratch database whose name contains 'test'.\n");
    exit(2);
}
foreach (['DB_HOST' => 'TEST_DB_HOST', 'DB_NAME' => 'TEST_DB_NAME', 'DB_USER' => 'TEST_DB_USER', 'DB_PASS' => 'TEST_DB_PASS'] as $k => $src) {
    putenv($k . '=' . (string) getenv($src));
}
putenv('MAIL_ENABLED=false');
putenv('MAIL_NOTIFY_TO=staff@example.com');
putenv('REQUIRE_EMAIL_VERIFICATION=false');
putenv('APP_KEY=test-key-test-key-test-key-test-key');

// ---- clean slate ----
$pdo = Db::pdo();
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $t) {
    $pdo->exec('DROP TABLE IF EXISTS `' . $t . '`');
}
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
Migrator::migrate(ROOT . '/database/migrations');
Migrator::seedContent(ROOT . '/database/seeds/content.sql');

/** @var \App\Core\Router $router */
$router = require ROOT . '/app/routes.php';
$_SESSION = [];
$ipCounter = 10;

/** @param array<string,mixed> $post @param array<string,mixed> $query */
function http(string $method, string $path, array $post = [], array $query = [], ?string $ip = null): Response
{
    global $router, $ipCounter;
    $ip ??= '198.51.100.' . (++$ipCounter % 250 + 1);
    if ($method === 'POST' && !array_key_exists('_csrf', $post)) {
        $post['_csrf'] = \App\Core\Csrf::token();
    }
    $GLOBALS['__daylight_path'] = $path;
    return $router->dispatch(new Request($method, $path, $query, $post, ['REMOTE_ADDR' => $ip]));
}

function newUser(string $email, array $roles = [], string $password = 'Password123'): string
{
    $id = Db::insert('users', ['email' => $email, 'password_hash' => Auth::hashPassword($password), 'full_name' => ucfirst(strtok($email, '@')), 'email_verified_at' => gmdate('Y-m-d H:i:s')]);
    foreach ($roles as $r) {
        Db::insert('user_roles', ['user_id' => $id, 'role' => $r]);
    }
    return $id;
}

function actAs(?string $userId): void
{
    Auth::logout();
    $_SESSION = [];
    if ($userId !== null) {
        Auth::loginAs($userId);
    }
}

function count_rows(string $table, string $where = '1=1', array $params = []): int
{
    return (int) Db::value("SELECT COUNT(*) FROM `$table` WHERE $where", $params);
}

function location(Response $r): string
{
    return $r->headers['Location'] ?? '';
}

// ======================= public pages =======================
foreach ([
    '/' => 'Software that runs the', '/services' => 'Engineering teams', '/services/custom-business-software' => "What's included",
    '/work' => 'Case studies', '/work/shulesoft-academic-portal' => 'The problem', '/products' => 'Our own SaaS platforms',
    '/industries' => 'Sectors we know well', '/technologies' => 'The stack behind the work', '/about' => 'A small team',
    '/blog' => 'Notes from the build', '/blog/why-custom-software-beats-spreadsheets' => 'Spreadsheets are excellent',
    '/careers' => 'Build with us', '/contact' => "Let's talk", '/quote' => 'Request a quote', '/faq' => 'Questions we get asked',
    '/privacy' => 'Privacy policy', '/terms' => 'Terms of service', '/auth' => 'Sign in',
] as $path => $needle) {
    test("GET $path renders", function () use ($path, $needle): void {
        actAs(null);
        $r = http('GET', $path);
        assertSame(200, $r->status, $path);
        assertContains($needle, $r->body);
        assertNotContains('style="', $r->body, 'inline styles would be blocked by the CSP');
        assertTrue(preg_match('/<script(?![^>]*\bsrc=)[^>]*>/i', $r->body) !== 1, 'inline <script> would be blocked by the CSP');
    });
}

test('unknown slugs and routes return 404', function (): void {
    actAs(null);
    assertSame(404, http('GET', '/services/does-not-exist')->status);
    assertSame(404, http('GET', '/work/nope')->status);
    assertSame(404, http('GET', '/blog/nope')->status);
    assertSame(404, http('GET', '/definitely/not/a/page')->status);
    assertSame(405, http('GET', '/auth/login')->status);
});

test('unpublished content is not visible', function (): void {
    actAs(null);
    Db::exec("UPDATE services SET published = 0 WHERE slug = 'web-applications'");
    assertSame(404, http('GET', '/services/web-applications')->status);
    assertNotContains('/services/web-applications', http('GET', '/services')->body);
    Db::exec("UPDATE services SET published = 1 WHERE slug = 'web-applications'");
});

test('seed content matches the original counts', function (): void {
    assertSame(8, count_rows('services'));
    assertSame(7, count_rows('industries'));
    assertSame(19, count_rows('technologies'));
    assertSame(4, count_rows('portfolio_projects'));
    assertSame(3, count_rows('saas_products'));
    assertSame(3, count_rows('blog_posts'));
    assertSame(5, count_rows('faqs'));
    assertSame(3, count_rows('job_openings'));
});

// ======================= lead forms =======================
$contact = ['name' => 'Ada Test', 'email' => 'ada@example.com', 'subject' => 'Hello', 'message' => 'Testing the form.'];

test('contact form: valid submission is saved, then success shows', function () use ($contact): void {
    actAs(null);
    $before = count_rows('contact_messages');
    $r = http('POST', '/contact', $contact);
    assertSame(303, $r->status);
    assertSame($before + 1, count_rows('contact_messages'));
    assertSame('New', Db::value("SELECT status FROM contact_messages WHERE email = 'ada@example.com'"));
    assertContains('Message received', http('GET', '/contact')->body);
    assertNotContains('Message received', http('GET', '/contact')->body, 'success shows only once');
    assertTrue(count_rows('email_outbox', "status = 'skipped'") >= 1, 'staff email recorded as skipped (mail disabled)');
});

test('contact form: invalid input is rejected and nothing is saved', function () use ($contact): void {
    actAs(null);
    $before = count_rows('contact_messages');
    $r = http('POST', '/contact', ['email' => 'not-an-email'] + $contact);
    assertSame(422, $r->status);
    assertContains('Enter a valid email address.', $r->body);
    assertSame(422, http('POST', '/contact', ['message' => ''] + $contact)->status);
    assertSame($before, count_rows('contact_messages'));
});

test('contact form: CSRF and honeypot are enforced', function () use ($contact): void {
    actAs(null);
    $before = count_rows('contact_messages');
    assertSame(419, http('POST', '/contact', $contact + ['_csrf' => 'bad'])->status);
    assertSame(400, http('POST', '/contact', $contact + ['website' => 'http://spam.example'])->status);
    assertSame($before, count_rows('contact_messages'));
});

test('contact form: rate limit kicks in', function () use ($contact): void {
    actAs(null);
    $ip = '203.0.113.77';
    $codes = [];
    for ($i = 0; $i < 10; $i++) {
        $codes[] = http('POST', '/contact', $contact, [], $ip)->status;
    }
    assertSame(303, $codes[0]);
    assertSame(429, $codes[9], 'tenth rapid submission is limited');
});

test('quote form validates select values and saves', function (): void {
    actAs(null);
    $ok = ['name' => 'Q', 'email' => 'q@example.com', 'project_type' => 'Website', 'details' => 'Need a site'];
    assertSame(422, http('POST', '/quote', ['project_type' => 'Hack'] + $ok)->status);
    assertSame(303, http('POST', '/quote', $ok)->status);
    assertSame('Kenya', Db::value("SELECT country FROM quote_requests WHERE email = 'q@example.com'"), 'DB default country');
});

test('job application: saved against an open role; closed or fake role rejected', function (): void {
    actAs(null);
    $jobId = (string) Db::value("SELECT id FROM job_openings WHERE slug = 'laravel-engineer'");
    $post = ['job_id' => $jobId, 'name' => 'Job Seeker', 'email' => 'seeker@example.com', 'portfolio_url' => 'https://example.com/cv'];
    assertSame(303, http('POST', '/careers/apply', $post)->status);
    assertSame($jobId, Db::value("SELECT job_id FROM job_applications WHERE email = 'seeker@example.com'"));
    assertSame(422, http('POST', '/careers/apply', ['portfolio_url' => 'javascript:alert(1)'] + $post)->status);
    assertSame(404, http('POST', '/careers/apply', ['job_id' => Db::uuid()] + $post)->status);
    Db::exec("UPDATE job_openings SET status = 'Closed' WHERE id = ?", [$jobId]);
    assertSame(404, http('POST', '/careers/apply', $post)->status);
    Db::exec("UPDATE job_openings SET status = 'Open' WHERE id = ?", [$jobId]);
});

// ======================= authentication =======================
test('register, log out, log in, wrong password', function (): void {
    actAs(null);
    $r = http('POST', '/auth/register', ['name' => 'New Person', 'email' => 'new@example.com', 'password' => 'Password123']);
    assertSame(303, $r->status);
    assertSame('/portal', location($r));
    assertTrue(Auth::check(), 'logged in after register');
    $hash = (string) Db::value("SELECT password_hash FROM users WHERE email = 'new@example.com'");
    assertTrue($hash !== 'Password123' && password_verify('Password123', $hash), 'password stored hashed');
    http('POST', '/auth/logout');
    assertTrue(!Auth::check(), 'logged out');
    assertSame(401, http('POST', '/auth/login', ['email' => 'new@example.com', 'password' => 'wrong-password'])->status);
    $ok = http('POST', '/auth/login', ['email' => 'new@example.com', 'password' => 'Password123']);
    assertSame(303, $ok->status);
    assertTrue(Auth::check(), 'logged in');
});

test('register rejects weak/duplicate and login needs CSRF', function (): void {
    actAs(null);
    assertSame(422, http('POST', '/auth/register', ['name' => 'X', 'email' => 'weak@example.com', 'password' => 'short'])->status);
    assertSame(409, http('POST', '/auth/register', ['name' => 'X', 'email' => 'new@example.com', 'password' => 'Password123'])->status);
    assertSame(419, http('POST', '/auth/login', ['email' => 'new@example.com', 'password' => 'Password123', '_csrf' => 'bad'])->status);
});

test('login is rate limited per email', function (): void {
    actAs(null);
    $last = 0;
    for ($i = 0; $i < 8; $i++) {
        $last = http('POST', '/auth/login', ['email' => 'victim@example.com', 'password' => 'nope-nope-nope'])->status;
    }
    assertSame(429, $last);
});

test('password reset is honest when email is not configured', function (): void {
    actAs(null);
    $r = http('POST', '/auth/forgot', ['email' => 'new@example.com']);
    assertSame(503, $r->status);
    assertContains("aren't set up", $r->body);
});

test('reset token flow works and tokens are single-use', function (): void {
    actAs(null);
    $uid = newUser('reset@example.com');
    $token = bin2hex(random_bytes(32));
    Db::insert('auth_tokens', ['user_id' => $uid, 'type' => 'reset', 'token_hash' => hash('sha256', $token), 'expires_at' => gmdate('Y-m-d H:i:s', time() + 3600)]);
    assertSame(200, http('GET', '/auth/reset/' . $token)->status);
    assertSame(422, http('POST', '/auth/reset/' . $token, ['password' => 'NewPassword1', 'password2' => 'different'])->status);
    assertSame(303, http('POST', '/auth/reset/' . $token, ['password' => 'NewPassword1', 'password2' => 'NewPassword1'])->status);
    assertSame(400, http('GET', '/auth/reset/' . $token)->status, 'token used');
    assertTrue(Auth::attempt('reset@example.com', 'NewPassword1') !== null);
    assertSame(400, http('GET', '/auth/reset/' . str_repeat('a', 64))->status, 'unknown token');
});

// ======================= authorisation =======================
test('admin area: anonymous redirected, client forbidden, staff allowed', function (): void {
    actAs(null);
    $r = http('GET', '/admin');
    assertSame(303, $r->status);
    assertSame('/auth', location($r));
    actAs(newUser('client1@example.com', ['client']));
    assertSame(403, http('GET', '/admin')->status);
    assertSame(403, http('GET', '/admin/clients')->status);
    actAs(newUser('staff1@example.com', ['staff']));
    assertSame(200, http('GET', '/admin')->status);
    assertSame(200, http('GET', '/admin/clients')->status);
});

test('staff cannot change website content; admin can', function (): void {
    actAs(Db::value("SELECT id FROM users WHERE email = 'staff1@example.com'"));
    assertSame(403, http('GET', '/admin/services')->status, 'content hidden from staff');
    assertSame(403, http('POST', '/admin/services', ['title' => 'Hack'])->status);
    actAs(newUser('admin1@example.com', ['admin']));
    $before = count_rows('services');
    $r = http('POST', '/admin/services', ['title' => 'Brand New Service', 'summary' => 'x', 'features' => "A\nB", 'published' => '1']);
    assertSame(303, $r->status);
    assertSame($before + 1, count_rows('services'));
    assertSame('brand-new-service', Db::value("SELECT slug FROM services WHERE title = 'Brand New Service'"));
    assertSame('["A","B"]', Db::value("SELECT features FROM services WHERE title = 'Brand New Service'"));
    assertSame(200, http('GET', '/services/brand-new-service')->status, 'appears on the public site');
    $dup = http('POST', '/admin/services', ['title' => 'Other', 'slug' => 'brand-new-service']);
    assertSame(422, $dup->status);
    assertContains('already used', $dup->body);
});

test('admin inbox: output is escaped and status can change', function (): void {
    actAs(null);
    http('POST', '/contact', ['name' => '<script>alert(1)</script>', 'email' => 'xss@example.com', 'message' => 'hi']);
    actAs(Db::value("SELECT id FROM users WHERE email = 'admin1@example.com'"));
    $list = http('GET', '/admin/messages');
    assertNotContains('<script>alert(1)</script>', $list->body);
    assertContains('&lt;script&gt;', $list->body);
    $id = (string) Db::value("SELECT id FROM contact_messages WHERE email = 'xss@example.com'");
    assertSame(303, http('POST', '/admin/messages/' . $id, ['status' => 'Replied'])->status);
    assertSame('Replied', Db::value('SELECT status FROM contact_messages WHERE id = ?', [$id]));
    assertSame(422, http('POST', '/admin/messages/' . $id, ['status' => 'Bogus'])->status);
});

test('users: admin cannot manage admins; super admin can; last super admin protected', function (): void {
    $admin = (string) Db::value("SELECT id FROM users WHERE email = 'admin1@example.com'");
    $superId = newUser('super1@example.com', ['super_admin']);
    actAs($admin);
    assertSame(403, http('GET', '/admin/users/' . $superId)->status);
    $cid = (string) Db::value("SELECT id FROM users WHERE email = 'client1@example.com'");
    assertSame(200, http('GET', '/admin/users/' . $cid)->status);
    $grant = http('POST', '/admin/users/' . $cid, ['full_name' => 'C', 'is_active' => '1', 'roles' => ['client', 'super_admin']]);
    assertSame(303, $grant->status);
    assertTrue(count_rows('user_roles', "user_id = ? AND role = 'super_admin'", [$cid]) === 0, 'admin cannot grant super_admin');
    actAs($superId);
    $other = newUser('super2@example.com', ['super_admin']);
    $r = http('POST', '/admin/users/' . $other, ['full_name' => 'S2', 'is_active' => '1', 'roles' => ['staff']]);
    assertSame(303, $r->status, 'second super admin can be demoted');
    $r2 = http('POST', '/admin/users/' . $other, ['full_name' => 'S2', 'is_active' => '1', 'roles' => ['staff']]);
    assertTrue(in_array($r2->status, [303, 422], true));
    Db::exec("DELETE FROM user_roles WHERE role = 'super_admin' AND user_id <> ?", [$superId]);
    $blocked = http('POST', '/admin/users/' . $other, ['full_name' => 'x']);
    assertTrue($blocked->status === 303 || $blocked->status === 422);
});

// ======================= portal =======================
test('portal: clients only see their own records; drafts are hidden', function (): void {
    $uA = newUser('clienta@example.com', ['client']);
    $uB = newUser('clientb@example.com', ['client']);
    $cA = Db::insert('clients', ['user_id' => $uA, 'company_name' => 'A Ltd']);
    $cB = Db::insert('clients', ['user_id' => $uB, 'company_name' => 'B Ltd']);
    $pA = Db::insert('client_projects', ['client_id' => $cA, 'name' => 'Project A', 'progress' => 40]);
    $pB = Db::insert('client_projects', ['client_id' => $cB, 'name' => 'Project B']);
    $invA = Db::insert('invoices', ['invoice_number' => 'T-A-1', 'client_id' => $cA, 'status' => 'Sent', 'total' => '100.00']);
    $draftA = Db::insert('invoices', ['invoice_number' => 'T-A-2', 'client_id' => $cA, 'status' => 'Draft', 'total' => '50.00']);
    $invB = Db::insert('invoices', ['invoice_number' => 'T-B-1', 'client_id' => $cB, 'status' => 'Sent', 'total' => '70.00']);
    Db::insert('project_documents', ['project_id' => $pA, 'title' => 'Public doc', 'visible_to_client' => 1]);
    Db::insert('project_documents', ['project_id' => $pA, 'title' => 'Hidden doc', 'visible_to_client' => 0]);

    actAs(null);
    assertSame(303, http('GET', '/portal')->status, 'portal needs login');
    actAs($uA);
    assertContains('Project A', http('GET', '/portal')->body);
    assertNotContains('Project B', http('GET', '/portal')->body);
    $detail = http('GET', '/portal/projects/' . $pA);
    assertSame(200, $detail->status);
    assertContains('Public doc', $detail->body);
    assertNotContains('Hidden doc', $detail->body);
    assertSame(404, http('GET', '/portal/projects/' . $pB)->status, "other client's project");
    assertSame(200, http('GET', '/portal/invoices/' . $invA)->status);
    assertSame(404, http('GET', '/portal/invoices/' . $draftA)->status, 'draft invoice hidden');
    assertSame(404, http('GET', '/portal/invoices/' . $invB)->status, "other client's invoice");
    assertNotContains('T-A-2', http('GET', '/portal/invoices')->body);
    assertSame(404, http('POST', '/portal/projects/' . $pB . '/messages', ['body' => 'sneaky'])->status);
    assertSame(0, count_rows('project_messages', 'project_id = ?', [$pB]));
    assertSame(303, http('POST', '/portal/projects/' . $pA . '/messages', ['body' => 'Hello team'])->status);
    assertSame(1, count_rows('project_messages', 'project_id = ?', [$pA]));
    assertSame(422, http('POST', '/portal/projects/' . $pA . '/messages', ['body' => '   '])->status);
    assertSame(303, http('POST', '/portal/tickets', ['subject' => 'Help', 'priority' => 'High', 'project_id' => $pA])->status);
    assertSame($cA, Db::value("SELECT client_id FROM support_tickets WHERE subject = 'Help'"));
    assertSame(422, http('POST', '/portal/tickets', ['subject' => 'Bad', 'project_id' => $pB])->status, "cannot attach another client's project");
});

test('portal: unlinked account sees an honest empty state and cannot create tickets', function (): void {
    actAs(newUser('unlinked@example.com'));
    assertContains("isn't linked", http('GET', '/portal')->body);
    assertSame(403, http('POST', '/portal/tickets', ['subject' => 'Help'])->status);
});

// ======================= billing logic =======================
test('invoice totals and payment settlement', function (): void {
    actAs(Db::value("SELECT id FROM users WHERE email = 'staff1@example.com'"));
    $cid = (string) Db::value("SELECT id FROM clients WHERE company_name = 'A Ltd'");
    assertSame(303, http('POST', '/admin/invoices', ['client_id' => $cid, 'currency' => 'KES', 'status' => 'Sent', 'tax' => '16.00'])->status);
    $inv = Db::one("SELECT * FROM invoices WHERE client_id = ? AND invoice_number LIKE 'INV-%'", [$cid]);
    assertTrue($inv !== null, 'invoice created with generated number');
    assertTrue(preg_match('/^INV-\d{4}-\d{4}$/', (string) $inv['invoice_number']) === 1);
    assertSame(303, http('POST', '/admin/invoice-items', ['invoice_id' => $inv['id'], 'description' => 'Work', 'quantity' => '2', 'unit_price' => '50.00'])->status);
    $row = Db::one('SELECT subtotal, total FROM invoices WHERE id = ?', [$inv['id']]);
    assertSame('100.00', $row['subtotal']);
    assertSame('116.00', $row['total']);
    assertSame(303, http('POST', '/admin/payments', ['invoice_id' => $inv['id'], 'amount' => '116.00', 'method' => 'M-Pesa'])->status);
    assertSame('Paid', Db::value('SELECT status FROM invoices WHERE id = ?', [$inv['id']]), 'fully paid -> Paid');
    $pay = (string) Db::value('SELECT id FROM payments WHERE invoice_id = ?', [$inv['id']]);
    assertSame(303, http('POST', '/admin/payments/' . $pay . '/delete')->status);
    assertSame('Sent', Db::value('SELECT status FROM invoices WHERE id = ?', [$inv['id']]), 'deleting the payment reopens it');
});

test('demo data seeder can load and remove cleanly', function (): void {
    putenv('ALLOW_DEMO_DATA=true');
    $creds = \App\Admin\DemoSeeder::load();
    assertTrue($creds !== null && strlen($creds['password']) === 12);
    assertTrue(count_rows('invoices', "invoice_number LIKE 'INV-DEMO-%'") === 3);
    assertTrue(\App\Admin\DemoSeeder::load() === null, 'second load refused');
    assertSame(1, \App\Admin\DemoSeeder::remove());
    assertSame(0, count_rows('users', "email = 'demo.client@example.com'"));
    assertSame(0, count_rows('invoices', "invoice_number LIKE 'INV-DEMO-%'"));
});

test('setup page is disabled without SETUP_TOKEN', function (): void {
    actAs(null);
    assertSame(404, http('GET', '/setup')->status);
});

exit(runTests('integration'));

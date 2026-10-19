<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Db;
use App\Core\Errors;
use App\Core\Migrator;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

/**
 * One-time web setup for hosts without SSH (e.g. InfinityFree). Disabled unless SETUP_TOKEN is set.
 * Remove SETUP_TOKEN from the environment once setup is finished.
 */
final class SetupController extends Controller
{
    private static function enabled(): bool
    {
        return strlen(Config::get('SETUP_TOKEN', '') ?? '') >= 24;
    }

    private static function tokenOk(Request $r): bool
    {
        $known = Config::get('SETUP_TOKEN', '') ?? '';
        $given = $r->input('token');
        if ($known !== '' && hash_equals($known, $given)) {
            return true;
        }
        usleep(700000); // slow down guessing
        return false;
    }

    /** @return array<string,mixed> */
    private static function status(): array
    {
        $s = ['db' => false, 'migrated' => false, 'admin' => false, 'content' => false];
        try {
            Db::pdo();
            $s['db'] = true;
            $s['migrated'] = (int) Db::value("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ('users','services','rate_limits')") === 3;
            if ($s['migrated']) {
                $s['admin'] = (int) Db::value("SELECT COUNT(*) FROM user_roles WHERE role IN ('super_admin','admin')") > 0;
                $s['content'] = (int) Db::value('SELECT COUNT(*) FROM services') > 0;
            }
        } catch (\Throwable $e) {
            Errors::log('setup status: ' . $e->getMessage());
        }
        return $s;
    }

    /** @param array<string,mixed> $extra  Status details are only shown once the setup token has been proven. */
    private static function show(array $extra = [], int $status = 200, bool $withStatus = false): Response
    {
        return self::page('setup/index', ['s' => $withStatus ? self::status() : null, 'msg' => '', 'err' => '', 'old' => []] + $extra, [
            'title' => 'Setup — Daylight', 'noindex' => '1',
        ], $status);
    }

    public static function index(): Response
    {
        if (!self::enabled()) {
            return self::message('404', 'Page not found', "The page you're looking for doesn't exist or has been moved.", 404);
        }
        return self::show();
    }

    public static function run(Request $r): Response
    {
        if (!self::enabled()) {
            return self::message('404', 'Page not found', "The page you're looking for doesn't exist or has been moved.", 404);
        }
        if (($c = self::csrfFailure($r)) !== null) {
            return $c;
        }
        if (!self::tokenOk($r)) {
            return self::show(['err' => 'The setup token is wrong.'], 403);
        }
        $action = $r->input('action');
        try {
            if ($action === 'migrate') {
                $applied = Migrator::migrate(ROOT . '/database/migrations');
                $msg = $applied === [] ? 'Database is already up to date.' : 'Applied: ' . implode(', ', $applied) . '.';
                if ($r->input('seed') === '1') {
                    $msg .= Migrator::seedContent(ROOT . '/database/seeds/content.sql') ? ' Original site content loaded.' : ' Content already present, so it was not loaded again.';
                }
                return self::show(['msg' => $msg], 200, true);
            }
            if ($action === 'admin') {
                $st = self::status();
                if (!$st['migrated']) {
                    return self::show(['err' => 'Run the database step first.'], 409, true);
                }
                if ($st['admin']) {
                    return self::show(['err' => 'An administrator already exists. Create more users from the admin area.'], 409, true);
                }
                [$clean, $errors] = Validator::validate($r->post(), [
                    'email' => ['type' => 'email', 'required' => true, 'label' => 'Email'],
                    'full_name' => ['label' => 'Full name', 'required' => true, 'max' => 200],
                ]);
                $pw = $r->input('password');
                if (($pe = Auth::passwordError($pw)) !== null) {
                    $errors['password'] = $pe;
                }
                if ($errors !== []) {
                    return self::show(['err' => implode(' ', $errors), 'old' => ['email' => $r->input('email'), 'full_name' => $r->input('full_name')]], 422, true);
                }
                $existing = Db::one('SELECT id FROM users WHERE email = ?', [$clean['email']]);
                $id = $existing !== null ? (string) $existing['id'] : Db::insert('users', [
                    'email' => $clean['email'], 'password_hash' => Auth::hashPassword($pw),
                    'full_name' => $clean['full_name'], 'email_verified_at' => gmdate('Y-m-d H:i:s'),
                ]);
                Db::insert('user_roles', ['user_id' => $id, 'role' => 'super_admin']);
                return self::show(['msg' => 'Super admin created. Now remove SETUP_TOKEN from your configuration, then sign in.'], 200, true);
            }
        } catch (\Throwable $e) {
            Errors::log('setup failed: ' . $e->getMessage());
            return self::show(['err' => 'That step failed. Check storage/logs for details and confirm the DB_* settings.'], 500, true);
        }
        return self::show(['err' => 'Unknown action.'], 400, true);
    }
}

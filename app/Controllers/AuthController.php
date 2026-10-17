<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Db;
use App\Core\Errors;
use App\Core\Mailer;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

final class AuthController extends Controller
{
    private const META = [
        'title' => 'Sign in — Daylight client portal',
        'description' => 'Sign in to the Daylight client portal to follow your projects and invoices.',
        'og_title' => 'Sign in — Daylight',
        'og_description' => 'Access the Daylight client portal.',
        'noindex' => '1',
    ];

    /** @param array<string,mixed> $state */
    private static function show(string $mode, array $state = [], int $status = 200): Response
    {
        return self::page('auth/index', [
            'mode' => $mode === 'up' ? 'up' : 'in',
            'error' => $state['error'] ?? '', 'notice' => $state['notice'] ?? '',
            'old' => $state['old'] ?? [], 'errors' => $state['errors'] ?? [],
        ], self::META, $status);
    }

    public static function index(Request $r): Response
    {
        return self::show($r->query('mode'), ['notice' => (string) \App\Core\Session::pull('auth_notice', '')]);
    }

    public static function home(): string
    {
        return Auth::isStaff() ? '/admin' : '/portal';
    }

    public static function login(Request $r): Response
    {
        if (!Csrf::verify($r)) {
            return self::show('in', ['error' => 'Your session expired. Please try again.'], 419);
        }
        $email = strtolower(trim($r->input('email')));
        $password = $r->input('password');
        $old = ['email' => mb_substr($email, 0, 254)];
        if (RateLimiter::tooMany('login', $r->ip(), 15, 900) || ($email !== '' && RateLimiter::tooMany('login_email', $email, 6, 900))) {
            return self::show('in', ['error' => 'Too many sign-in attempts. Please wait a few minutes and try again.', 'old' => $old], 429);
        }
        $user = ($email !== '' && $password !== '' && strlen($password) <= 1024) ? Auth::attempt($email, $password) : null;
        if ($user === null) {
            RateLimiter::hit('login', $r->ip());
            if ($email !== '') {
                RateLimiter::hit('login_email', $email);
            }
            return self::show('in', ['error' => 'Invalid email or password.', 'old' => $old], 401);
        }
        if (Config::bool('REQUIRE_EMAIL_VERIFICATION') && $user['email_verified_at'] === null) {
            return self::show('in', ['error' => 'Please confirm your email address first. Check your inbox for the confirmation link.', 'old' => $old], 403);
        }
        Auth::loginAs((string) $user['id']);
        return Response::redirect(self::home());
    }

    public static function register(Request $r): Response
    {
        $old = ['name' => mb_substr($r->input('name'), 0, 200), 'email' => mb_substr($r->input('email'), 0, 254)];
        if (!Csrf::verify($r)) {
            return self::show('up', ['error' => 'Your session expired. Please try again.', 'old' => $old], 419);
        }
        if ($r->input('website') !== '') {
            return self::show('up', ['error' => "Couldn't create the account. Please try again.", 'old' => $old], 400);
        }
        if (RateLimiter::tooMany('register', $r->ip(), 5, 3600)) {
            return self::show('up', ['error' => 'Too many sign-up attempts. Please try again later.', 'old' => $old], 429);
        }
        [$clean, $errors] = Validator::validate($r->post(), [
            'name' => ['label' => 'Full name', 'required' => true, 'max' => 200],
            'email' => ['type' => 'email', 'required' => true, 'label' => 'Email'],
        ]);
        $password = $r->input('password');
        if (($pe = Auth::passwordError($password)) !== null) {
            $errors['password'] = $pe;
        }
        $verify = Config::bool('REQUIRE_EMAIL_VERIFICATION');
        if ($errors === [] && $verify && !Mailer::enabled()) {
            return self::show('up', ['error' => "Account confirmation by email isn't available on this server yet. Please contact us to get access.", 'old' => $old], 503);
        }
        if ($errors !== []) {
            return self::show('up', ['errors' => $errors, 'old' => $old], 422);
        }
        RateLimiter::hit('register', $r->ip());
        if (Db::value('SELECT COUNT(*) FROM users WHERE email = ?', [$clean['email']]) > 0) {
            return self::show('up', ['errors' => ['email' => 'An account with this email already exists. Try signing in.'], 'old' => $old], 409);
        }
        try {
            $id = Db::insert('users', [
                'email' => $clean['email'],
                'password_hash' => Auth::hashPassword($password),
                'full_name' => $clean['name'],
                'email_verified_at' => $verify ? null : gmdate('Y-m-d H:i:s'),
            ]);
            if ($verify) {
                self::sendToken($id, (string) $clean['email'], 'verify');
                \App\Core\Session::flash('auth_notice', 'Account created. Check your email to confirm it, then sign in.');
                return Response::redirect('/auth');
            }
        } catch (\Throwable $e) {
            Errors::log('register failed: ' . $e->getMessage());
            return self::show('up', ['error' => "Couldn't create the account. Please try again.", 'old' => $old], 500);
        }
        Auth::loginAs($id);
        return Response::redirect('/portal');
    }

    public static function logout(Request $r): Response
    {
        if (Csrf::verify($r)) {
            Auth::logout();
        }
        return Response::redirect('/auth');
    }

    // ---- Password reset ----

    public static function forgotForm(): Response
    {
        return self::page('auth/forgot', ['error' => '', 'sent' => false], self::META);
    }

    public static function forgot(Request $r): Response
    {
        if (!Csrf::verify($r)) {
            return self::page('auth/forgot', ['error' => 'Your session expired. Please try again.', 'sent' => false], self::META, 419);
        }
        if (!Mailer::enabled() || Mailer::absoluteUrl('/') === null) {
            return self::page('auth/forgot', ['error' => "Password reset emails aren't set up on this server yet. Please contact Daylight to reset your password.", 'sent' => false], self::META, 503);
        }
        $email = strtolower(trim($r->input('email')));
        if (RateLimiter::tooMany('forgot', $r->ip(), 5, 3600) || ($email !== '' && RateLimiter::tooMany('forgot_email', $email, 3, 3600))) {
            return self::page('auth/forgot', ['error' => 'Too many requests. Please try again later.', 'sent' => false], self::META, 429);
        }
        RateLimiter::hit('forgot', $r->ip());
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            RateLimiter::hit('forgot_email', $email);
            $u = Db::one('SELECT id FROM users WHERE email = ? AND is_active = 1', [$email]);
            if ($u !== null) {
                try {
                    self::sendToken((string) $u['id'], $email, 'reset');
                } catch (\Throwable $e) {
                    Errors::log('reset email failed: ' . $e->getMessage());
                }
            }
        }
        // Same response whether or not the account exists (prevents account enumeration).
        return self::page('auth/forgot', ['error' => '', 'sent' => true], self::META);
    }

    /** @param array<string,string> $p */
    public static function resetForm(Request $r, array $p): Response
    {
        $row = self::validToken($p['token'], 'reset');
        if ($row === null) {
            return self::page('auth/reset', ['valid' => false, 'error' => '', 'token' => ''], self::META, 400);
        }
        return self::page('auth/reset', ['valid' => true, 'error' => '', 'token' => $p['token']], self::META);
    }

    /** @param array<string,string> $p */
    public static function reset(Request $r, array $p): Response
    {
        if (!Csrf::verify($r)) {
            return self::page('auth/reset', ['valid' => true, 'error' => 'Your session expired. Please reload and try again.', 'token' => $p['token']], self::META, 419);
        }
        $row = self::validToken($p['token'], 'reset');
        if ($row === null) {
            return self::page('auth/reset', ['valid' => false, 'error' => '', 'token' => ''], self::META, 400);
        }
        $pw = $r->input('password');
        $err = Auth::passwordError($pw) ?? ($pw !== $r->input('password2') ? 'The two passwords do not match.' : null);
        if ($err !== null) {
            return self::page('auth/reset', ['valid' => true, 'error' => $err, 'token' => $p['token']], self::META, 422);
        }
        Db::update('users', (string) $row['user_id'], ['password_hash' => Auth::hashPassword($pw), 'email_verified_at' => gmdate('Y-m-d H:i:s')]);
        Db::exec('UPDATE auth_tokens SET used_at = NOW() WHERE user_id = ? AND type = ? AND used_at IS NULL', [$row['user_id'], 'reset']);
        \App\Core\Session::flash('auth_notice', 'Your password has been changed. You can sign in now.');
        return Response::redirect('/auth');
    }

    /** @param array<string,string> $p */
    public static function verify(Request $r, array $p): Response
    {
        $row = self::validToken($p['token'], 'verify');
        if ($row === null) {
            \App\Core\Session::flash('auth_notice', 'That confirmation link is invalid or has expired.');
            return Response::redirect('/auth');
        }
        Db::exec('UPDATE users SET email_verified_at = NOW() WHERE id = ? AND email_verified_at IS NULL', [$row['user_id']]);
        Db::exec('UPDATE auth_tokens SET used_at = NOW() WHERE user_id = ? AND type = ? AND used_at IS NULL', [$row['user_id'], 'verify']);
        \App\Core\Session::flash('auth_notice', 'Email confirmed. You can sign in now.');
        return Response::redirect('/auth');
    }

    /** @return array<string,mixed>|null */
    private static function validToken(string $token, string $type): ?array
    {
        if (preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
            return null;
        }
        return Db::one(
            'SELECT user_id FROM auth_tokens WHERE token_hash = ? AND type = ? AND used_at IS NULL AND expires_at > NOW()',
            [hash('sha256', $token), $type]
        );
    }

    private static function sendToken(string $userId, string $email, string $type): void
    {
        $token = bin2hex(random_bytes(32));
        $ttl = $type === 'reset' ? 1 : 24;
        Db::insert('auth_tokens', [
            'user_id' => $userId, 'type' => $type, 'token_hash' => hash('sha256', $token),
            'expires_at' => gmdate('Y-m-d H:i:s', time() + $ttl * 3600),
        ]);
        $path = $type === 'reset' ? '/auth/reset/' . $token : '/auth/verify/' . $token;
        $link = Mailer::absoluteUrl($path);
        if ($link === null) {
            throw new \RuntimeException('APP_URL is not configured');
        }
        $body = $type === 'reset'
            ? "Someone asked to reset the password for your Daylight account.\n\nOpen this link within 1 hour to choose a new password:\n{$link}\n\nIf this wasn't you, ignore this email."
            : "Welcome to Daylight.\n\nConfirm your email address within 24 hours:\n{$link}\n";
        Mailer::send($email, $type === 'reset' ? 'Reset your Daylight password' : 'Confirm your Daylight account', $body);
    }
}

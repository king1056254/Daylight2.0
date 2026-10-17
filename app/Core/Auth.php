<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public const STAFF_ROLES = ['super_admin', 'admin', 'staff'];
    public const ADMIN_ROLES = ['super_admin', 'admin'];

    /** @var array<string,mixed>|null|false */
    private static array|null|false $cache = false;

    /** @return array<string,mixed>|null  user row plus 'roles' list */
    public static function user(): ?array
    {
        if (self::$cache !== false) {
            return self::$cache;
        }
        $id = $_SESSION['uid'] ?? null;
        if (!is_string($id) || $id === '') {
            return self::$cache = null;
        }
        $u = Db::one('SELECT id, email, full_name, phone, email_verified_at FROM users WHERE id = ? AND is_active = 1', [$id]);
        if ($u === null) {
            unset($_SESSION['uid']);
            return self::$cache = null;
        }
        $u['roles'] = array_column(Db::all('SELECT role FROM user_roles WHERE user_id = ?', [$id]), 'role');
        return self::$cache = $u;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?string
    {
        $u = self::user();
        return $u === null ? null : (string) $u['id'];
    }

    /** @param list<string> $roles */
    public static function hasAnyRole(array $roles): bool
    {
        $u = self::user();
        return $u !== null && array_intersect($roles, (array) $u['roles']) !== [];
    }

    public static function isStaff(): bool
    {
        return self::hasAnyRole(self::STAFF_ROLES);
    }

    public static function isAdmin(): bool
    {
        return self::hasAnyRole(self::ADMIN_ROLES);
    }

    public static function isSuperAdmin(): bool
    {
        return self::hasAnyRole(['super_admin']);
    }

    public static function loginAs(string $userId): void
    {
        Session::regenerate();
        $_SESSION['uid'] = $userId;
        self::$cache = false;
    }

    public static function logout(): void
    {
        self::$cache = false;
        Session::destroy();
    }

    public static function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    /** Verifies credentials. Always does a hash comparison to limit timing differences. @return array<string,mixed>|null */
    public static function attempt(string $email, string $password): ?array
    {
        $row = Db::one('SELECT id, password_hash, is_active, email_verified_at FROM users WHERE email = ?', [strtolower($email)]);
        $hash = $row !== null ? (string) $row['password_hash'] : self::dummyHash();
        $ok = password_verify($password, $hash);
        if ($row === null || !$ok || (int) $row['is_active'] !== 1) {
            return null;
        }
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            Db::update('users', (string) $row['id'], ['password_hash' => self::hashPassword($password)]);
        }
        return $row;
    }

    private static function dummyHash(): string
    {
        static $h = null;
        return $h ??= password_hash('daylight-dummy-password', PASSWORD_DEFAULT);
    }

    /** Password policy shared by register / reset. Returns error message or null. */
    public static function passwordError(string $password): ?string
    {
        if (mb_strlen($password) < 8) {
            return 'Password must be at least 8 characters.';
        }
        if (strlen($password) > 72) {
            return 'Password must be at most 72 bytes.';
        }
        return null;
    }
}

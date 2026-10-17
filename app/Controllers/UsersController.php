<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Db;
use App\Core\Errors;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

/** User and role management (administrators only). */
final class UsersController extends Controller
{
    private const ALL_ROLES = ['super_admin', 'admin', 'staff', 'client'];

    /** @return list<string> roles the signed-in admin may grant */
    private static function grantable(): array
    {
        return Auth::isSuperAdmin() ? self::ALL_ROLES : ['staff', 'client'];
    }

    /** @return list<string> */
    private static function rolesOf(string $userId): array
    {
        return array_column(Db::all('SELECT role FROM user_roles WHERE user_id = ?', [$userId]), 'role');
    }

    private static function canManage(string $targetId): bool
    {
        if (Auth::isSuperAdmin()) {
            return true;
        }
        return array_intersect(self::rolesOf($targetId), ['super_admin', 'admin']) === [];
    }

    public static function index(Request $r): Response
    {
        if (($g = self::requireAdmin()) !== null) {
            return $g;
        }
        $q = trim(mb_substr($r->query('q'), 0, 100));
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $users = Db::all(
            'SELECT u.id, u.email, u.full_name, u.is_active, u.created_at,
                    (SELECT GROUP_CONCAT(role ORDER BY role SEPARATOR \', \') FROM user_roles ur WHERE ur.user_id = u.id) AS roles
             FROM users u ' . ($q !== '' ? 'WHERE u.email LIKE ? OR u.full_name LIKE ? ' : '') . 'ORDER BY u.created_at DESC LIMIT 200',
            $q !== '' ? [$like, $like] : []
        );
        return self::app('admin', 'admin/users', ['users' => $users, 'q' => $q], 'Users & roles — Admin');
    }

    /** @param array<string,mixed> $state */
    public static function create(Request $r, array $p = [], array $state = [], int $status = 200): Response
    {
        if (($g = self::requireAdmin()) !== null) {
            return $g;
        }
        return self::app('admin', 'admin/user_form', [
            'user' => null, 'roles' => [], 'grantable' => self::grantable(),
            'old' => $state['old'] ?? [], 'errors' => $state['errors'] ?? [], 'self' => false,
        ], 'New user — Admin', $status);
    }

    public static function store(Request $r): Response
    {
        if (($g = self::requireAdmin()) !== null) {
            return $g;
        }
        if (($c = self::csrfFailure($r)) !== null) {
            return $c;
        }
        $old = ['email' => $r->input('email'), 'full_name' => $r->input('full_name'), 'phone' => $r->input('phone'), 'roles' => self::postedRoles($r)];
        [$clean, $errors] = Validator::validate($r->post(), [
            'email' => ['type' => 'email', 'required' => true, 'label' => 'Email'],
            'full_name' => ['label' => 'Full name', 'max' => 200],
            'phone' => ['type' => 'tel', 'label' => 'Phone', 'max' => 40],
        ]);
        $password = $r->input('password');
        if (($pe = Auth::passwordError($password)) !== null) {
            $errors['password'] = $pe;
        }
        if (empty($errors['email']) && Db::value('SELECT COUNT(*) FROM users WHERE email = ?', [$clean['email']]) > 0) {
            $errors['email'] = 'An account with this email already exists.';
        }
        if ($errors !== []) {
            return self::create($r, [], ['old' => $old, 'errors' => $errors], 422);
        }
        try {
            $id = Db::insert('users', [
                'email' => $clean['email'], 'password_hash' => Auth::hashPassword($password),
                'full_name' => $clean['full_name'], 'phone' => $clean['phone'],
                'email_verified_at' => gmdate('Y-m-d H:i:s'),
            ]);
            self::syncRoles($id, $old['roles'], []);
        } catch (\Throwable $e) {
            Errors::log('user create failed: ' . $e->getMessage());
            return self::create($r, [], ['old' => $old, 'errors' => ['_form' => "Couldn't create the user. Please try again."]], 500);
        }
        self::notice('success', 'User created.');
        return Response::redirect('/admin/users');
    }

    /** @param array<string,string> $p @param array<string,mixed> $state */
    public static function edit(Request $r, array $p, array $state = [], int $status = 200): Response
    {
        if (($g = self::requireAdmin()) !== null) {
            return $g;
        }
        $user = preg_match('/^[0-9a-fA-F\-]{36}$/', $p['id']) === 1
            ? Db::one('SELECT id, email, full_name, phone, is_active FROM users WHERE id = ?', [$p['id']]) : null;
        if ($user === null) {
            return self::message('404', 'Not found', "We couldn't find that user.", 404);
        }
        if (!self::canManage((string) $user['id'])) {
            return self::message('403', 'Not allowed', 'Only a super admin can manage administrator accounts.', 403);
        }
        return self::app('admin', 'admin/user_form', [
            'user' => $user, 'roles' => self::rolesOf((string) $user['id']), 'grantable' => self::grantable(),
            'old' => $state['old'] ?? [], 'errors' => $state['errors'] ?? [], 'self' => $user['id'] === Auth::id(),
        ], 'Edit user — Admin', $status);
    }

    /** @param array<string,string> $p */
    public static function update(Request $r, array $p): Response
    {
        if (($g = self::requireAdmin()) !== null) {
            return $g;
        }
        if (($c = self::csrfFailure($r)) !== null) {
            return $c;
        }
        $user = preg_match('/^[0-9a-fA-F\-]{36}$/', $p['id']) === 1 ? Db::one('SELECT * FROM users WHERE id = ?', [$p['id']]) : null;
        if ($user === null) {
            return self::message('404', 'Not found', "We couldn't find that user.", 404);
        }
        if (!self::canManage((string) $user['id'])) {
            return self::message('403', 'Not allowed', 'Only a super admin can manage administrator accounts.', 403);
        }
        $isSelf = $user['id'] === Auth::id();
        $posted = self::postedRoles($r);
        $old = ['full_name' => $r->input('full_name'), 'phone' => $r->input('phone'), 'roles' => $posted, 'is_active' => $r->input('is_active')];
        [$clean, $errors] = Validator::validate($r->post(), [
            'full_name' => ['label' => 'Full name', 'max' => 200],
            'phone' => ['type' => 'tel', 'label' => 'Phone', 'max' => 40],
        ]);
        $password = $r->input('password');
        if ($password !== '' && ($pe = Auth::passwordError($password)) !== null) {
            $errors['password'] = $pe;
        }
        $active = $r->input('is_active') === '1' ? 1 : 0;
        $current = self::rolesOf((string) $user['id']);
        if ($isSelf) {
            $active = 1;            // never lock yourself out
            $posted = $current;     // roles of your own account are changed by another admin
        }
        if (in_array('super_admin', $current, true) && !in_array('super_admin', $posted, true)
            && (int) Db::value("SELECT COUNT(*) FROM user_roles WHERE role = 'super_admin'") <= 1) {
            $errors['roles'] = 'There must always be at least one super admin.';
        }
        if ($errors !== []) {
            return self::edit($r, $p, ['old' => $old, 'errors' => $errors], 422);
        }
        try {
            $row = ['full_name' => $clean['full_name'], 'phone' => $clean['phone'], 'is_active' => $active];
            if ($password !== '') {
                $row['password_hash'] = Auth::hashPassword($password);
            }
            Db::update('users', (string) $user['id'], $row);
            if (!$isSelf) {
                self::syncRoles((string) $user['id'], $posted, $current);
            }
        } catch (\Throwable $e) {
            Errors::log('user update failed: ' . $e->getMessage());
            return self::edit($r, $p, ['old' => $old, 'errors' => ['_form' => "Couldn't save. Please try again."]], 500);
        }
        self::notice('success', 'User saved.');
        return Response::redirect('/admin/users');
    }

    /** @return list<string> */
    private static function postedRoles(Request $r): array
    {
        $raw = $r->post()['roles'] ?? [];
        $out = [];
        foreach ((array) $raw as $role) {
            if (is_string($role) && in_array($role, self::ALL_ROLES, true)) {
                $out[] = $role;
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * Adds/removes only roles the signed-in admin is allowed to grant; other existing roles stay untouched.
     * @param list<string> $wanted @param list<string> $current
     */
    private static function syncRoles(string $userId, array $wanted, array $current): void
    {
        $allowed = self::grantable();
        foreach ($allowed as $role) {
            $has = in_array($role, $current, true);
            $want = in_array($role, $wanted, true);
            if ($want && !$has) {
                Db::insert('user_roles', ['user_id' => $userId, 'role' => $role]);
            } elseif (!$want && $has) {
                Db::exec('DELETE FROM user_roles WHERE user_id = ? AND role = ?', [$userId, $role]);
            }
        }
    }
}

<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private static ?array $cached = null;

    public static function user(): ?array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        if (!isset($_SESSION['uid'])) {
            return null;
        }

        $user = DB::one(
            'SELECT
                u.id,
                u.login,
                u.full_name,
                u.role_id,
                u.school_id,
                u.exam_id,
                u.enabled,
                r.label AS role_name,
                r.code AS role
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.id = ?',
            [$_SESSION['uid']]
        );

        if (!$user || !(int)$user['enabled']) {
            unset($_SESSION['uid']);
            return null;
        }

        self::$cached = $user;

        return $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function login(
        string $login,
        string $password
    ): bool {
        $user = DB::one(
            'SELECT *
             FROM users
             WHERE login = ? AND enabled = 1',
            [$login]
        );

        if (
            !$user ||
            !password_verify(
                $password,
                $user['password_hash']
            )
        ) {
            return false;
        }

        session_regenerate_id(true);

        $_SESSION['uid'] = $user['id'];

        self::$cached = null;

        Audit::write(
            'auth.login',
            (string)$user['login']
        );

        return true;
    }

    public static function logout(): void
    {
        if (self::check()) {
            Audit::write('auth.logout');
        }

        $_SESSION = [];

        session_regenerate_id(true);

        self::$cached = null;
    }

    public static function has(string $permission): bool
    {
        $user = self::user();

        if (!$user) {
            return false;
        }

        if ($user['role'] === 'admin') {
            return true;
        }

        return DB::one(
            'SELECT 1
             FROM role_permissions rp
             JOIN permissions p
               ON rp.permission_id = p.id
             WHERE rp.role_id = ?
               AND p.code = ?',
            [
                $user['role_id'],
                $permission
            ]
        ) !== null;
    }

    public static function require(
        string $permission = ''
    ): void {
        if (!self::check()) {
            redirect('dang-nhap');
        }

        if (
            $permission !== '' &&
            !self::has($permission)
        ) {
            Audit::write(
                'auth.denied',
                $permission
            );

            http_response_code(403);

            View::render(
                'partials/error',
                [
                    'message' => 'Bạn không có quyền truy cập chức năng này.',
                    'code' => 403
                ]
            );

            exit;
        }
    }
}

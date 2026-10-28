<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\DB;
final class Accounts
{
    /** Các quyền có ý nghĩa với từng vai trò. Admin luôn có toàn bộ quyền. */
    private const ALLOWED = [
        'admissions' => ['admissions.manage','cutoffs.manage','exams.manage','schools.manage','results.publish'],
        'examiner' => ['blind.manage','blind.mapping','scores.manage'],
        'school_thcs' => ['school.profiles'],
        'school_thpt' => ['school.results'],
        'candidate' => ['profile.view','preferences.manage'],
    ];

    public static function users(): array
    {
        return DB::all(
            'SELECT u.id,u.login,u.full_name,u.enabled,u.school_id,u.exam_id,
                    r.code role,r.label role_label,s.name school_name,
                    c.id candidate_id,c.code candidate_code
             FROM users u
             JOIN roles r ON r.id=u.role_id
             LEFT JOIN schools s ON s.id=u.school_id
             LEFT JOIN candidates c ON c.user_id=u.id
             ORDER BY u.id'
        );
    }

    public static function user(int $id): ?array
    {
        return DB::one(
            'SELECT u.*,r.code role,r.label role_label,s.name school_name,
                    c.id candidate_id,c.code candidate_code,c.full_name candidate_name
             FROM users u
             JOIN roles r ON r.id=u.role_id
             LEFT JOIN schools s ON s.id=u.school_id
             LEFT JOIN candidates c ON c.user_id=u.id
             WHERE u.id=?',
            [$id]
        );
    }

    public static function roles(): array
    {
        return DB::all('SELECT * FROM roles ORDER BY id');
    }

    public static function allowedPermissions(string $role): array
    {
        if ($role === 'admin') {
            return array_column(DB::all('SELECT code FROM permissions ORDER BY id'), 'code');
        }
        return self::ALLOWED[$role] ?? [];
    }

    public static function canUsePermission(string $role, string $permission): bool
    {
        return $role === 'admin' || in_array($permission, self::ALLOWED[$role] ?? [], true);
    }

    public static function matrix(): array
    {
        $permissions=DB::all('SELECT * FROM permissions ORDER BY id');
        $grants=DB::all('SELECT r.code role,p.code perm FROM role_permissions rp JOIN roles r ON r.id=rp.role_id JOIN permissions p ON p.id=rp.permission_id');
        $g=[];
        foreach($grants as $grant) $g[$grant['role']][$grant['perm']]=true;
        return ['permissions'=>$permissions,'grants'=>$g];
    }

    /** Dữ liệu quyền hiệu lực của một tài khoản, gồm quyền vai trò và ngoại lệ riêng. */
    public static function permissionEditor(int $userId): ?array
    {
        $user=self::user($userId);
        if (!$user) return null;
        $matrix=self::matrix();
        $overrideRows=DB::all(
            'SELECT p.code,o.effect
             FROM user_permission_overrides o
             JOIN permissions p ON p.id=o.permission_id
             WHERE o.user_id=?',
            [$userId]
        );
        $overrides=[];
        foreach($overrideRows as $row) $overrides[$row['code']]=$row['effect'];
        $effective=[];
        foreach($matrix['permissions'] as $permission){
            $code=$permission['code'];
            $base=$user['role']==='admin' || isset($matrix['grants'][$user['role']][$code]);
            $effect=$overrides[$code]??null;
            $effective[$code]=$user['role']==='admin' ? true : ($effect==='grant' || ($effect===null && $base));
        }
        return compact('user','matrix','overrides','effective');
    }
}

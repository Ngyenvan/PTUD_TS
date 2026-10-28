<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\DB;
final class Accounts {
 public static function users():array{return DB::all('SELECT u.id,u.login,u.full_name,u.enabled,u.school_id,u.exam_id,r.code role,r.label role_label,s.name school_name FROM users u JOIN roles r ON r.id=u.role_id LEFT JOIN schools s ON s.id=u.school_id ORDER BY u.id');}
 public static function roles():array{return DB::all('SELECT * FROM roles ORDER BY id');}
 public static function matrix():array{
   $permissions=DB::all('SELECT * FROM permissions ORDER BY id');
   $grants=DB::all('SELECT r.code role,p.code perm FROM role_permissions rp JOIN roles r ON r.id=rp.role_id JOIN permissions p ON p.id=rp.permission_id');
   $g=[];foreach($grants as $gr)$g[$gr['role']][$gr['perm']]=true;
   return ['permissions'=>$permissions,'grants'=>$g];
 }
}

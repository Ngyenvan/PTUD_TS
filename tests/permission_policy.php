<?php
/** Kiểm tra tĩnh chính sách quyền tương thích theo vai trò; không kết nối MariaDB. */
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
use App\Models\Accounts;

$allowed=[
 ['admissions','admissions.manage'],['admissions','results.publish'],
 ['examiner','blind.manage'],['examiner','scores.manage'],
 ['school_thcs','school.profiles'],['school_thpt','school.results'],
 ['candidate','profile.view'],['candidate','preferences.manage'],
 ['admin','roles.manage'],
];
$blocked=[
 ['candidate','roles.manage'],['candidate','admissions.manage'],
 ['school_thcs','school.results'],['school_thpt','school.profiles'],
 ['examiner','users.manage'],['admissions','audit.view'],
];
foreach($allowed as [$role,$permission])if(!Accounts::canUsePermission($role,$permission))throw new RuntimeException("Expected allow: $role / $permission");
foreach($blocked as [$role,$permission])if(Accounts::canUsePermission($role,$permission))throw new RuntimeException("Expected block: $role / $permission");
echo "PASS permission compatibility policy.\n";

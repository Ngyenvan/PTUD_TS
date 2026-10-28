<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth;use App\Core\DB;use App\Core\Audit;use App\Core\View;use App\Models\Accounts;use App\Models\Directory;
final class UserController {
 public function index():void{Auth::require('users.manage');View::render('admin/users',['users'=>Accounts::users(),'roles'=>Accounts::roles(),'schools'=>Directory::schools(),'exams'=>Directory::exams(),'matrix'=>Accounts::matrix(),'unlinked'=>DB::all('SELECT id,code,full_name FROM candidates WHERE user_id IS NULL ORDER BY id')]);}
 public function editPermissions():void{
  Auth::require('roles.manage');$id=(int)($_GET['user_id']??0);$editor=Accounts::permissionEditor($id);
  if(!$editor){flash('danger','Không tìm thấy tài khoản cần phân quyền.');redirect('quan-tri/nguoi-dung');}
  $candidates=DB::all('SELECT id,code,full_name,user_id FROM candidates WHERE user_id IS NULL OR user_id=? ORDER BY id',[$id]);
  View::render('admin/user_permissions',['editor'=>$editor,'roles'=>Accounts::roles(),'schools'=>Directory::schools(),'exams'=>Directory::exams(),'candidates'=>$candidates]);
 }
 public function updatePermissions():void{
  Auth::require('roles.manage');$id=(int)input('user_id');$name=input('name');$role=input('role');
  $sid=(int)input('school_id');$exam=(int)input('exam_id');$candidateId=(int)input('candidate_id');
  $raw=$_POST['permissions']??[];$requested=is_array($raw)?array_values(array_unique(array_map('strval',$raw))):[];
  $redirect='quan-tri/nguoi-dung/sua?user_id='.$id;
  if($id<1||$name===''){flash('danger','Tài khoản hoặc họ tên chưa hợp lệ.');redirect($redirect);}
  $roleRow=DB::one('SELECT id,code FROM roles WHERE code=?',[$role]);
  if(!$roleRow){flash('danger','Vai trò không hợp lệ.');redirect($redirect);}
  $allCodes=array_column(DB::all('SELECT code FROM permissions ORDER BY id'),'code');
  foreach($requested as $code)if(!in_array($code,$allCodes,true)||!Accounts::canUsePermission($role,$code)){flash('danger','Cấu hình quyền không hợp lệ với vai trò đã chọn.');redirect($redirect);}
  try{
   DB::tx(function()use($id,$name,$role,$roleRow,$sid,$exam,$candidateId,$requested,$allCodes){
    $user=DB::one('SELECT u.*,r.code role FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? FOR UPDATE',[$id]);
    if(!$user)throw new \RuntimeException('Không tìm thấy tài khoản.');
    $admins=DB::all("SELECT u.id FROM users u JOIN roles r ON r.id=u.role_id WHERE r.code='admin' AND u.enabled=1 FOR UPDATE");
    if($user['role']==='admin'&&$role!=='admin'&&(int)$user['enabled']===1&&count($admins)<=1)throw new \RuntimeException('Không thể đổi vai trò của quản trị viên hoạt động cuối cùng.');

    $schoolId=null;$examId=null;
    if(in_array($role,['school_thcs','school_thpt'],true)){
     $school=DB::one('SELECT type FROM schools WHERE id=?',[$sid]);
     $expected=$role==='school_thcs'?'THCS':'THPT';
     if(!$school||$school['type']!==$expected)throw new \RuntimeException('Phải chọn đúng trường thuộc loại tài khoản.');
     $schoolId=$sid;
    }
    if($role==='examiner'){
     if(!DB::one('SELECT id FROM exam_cycles WHERE id=?',[$exam]))throw new \RuntimeException('Phải chọn kỳ thi được phân công cho khảo thí.');
     $examId=$exam;
    }
    if($role==='candidate'){
     $candidate=DB::one('SELECT id,user_id FROM candidates WHERE id=? FOR UPDATE',[$candidateId]);
     if(!$candidate||($candidate['user_id']!==null&&(int)$candidate['user_id']!==$id))throw new \RuntimeException('Hồ sơ thí sinh đã thuộc tài khoản khác hoặc không tồn tại.');
    }

    $beforeEditor=Accounts::permissionEditor($id);$before=[];
    foreach($beforeEditor['effective'] as $code=>$has)if($has)$before[]=$code;
    DB::run('UPDATE users SET full_name=?,role_id=?,school_id=?,exam_id=? WHERE id=?',[$name,$roleRow['id'],$schoolId,$examId,$id]);
    DB::run('UPDATE candidates SET user_id=NULL WHERE user_id=?',[$id]);
    if($role==='candidate'&&DB::run('UPDATE candidates SET user_id=? WHERE id=? AND user_id IS NULL',[$id,$candidateId])!==1)throw new \RuntimeException('Không thể liên kết hồ sơ thí sinh.');

    DB::run('DELETE FROM user_permission_overrides WHERE user_id=?',[$id]);
    if($role!=='admin'){
     $baseRows=DB::all('SELECT p.code FROM role_permissions rp JOIN permissions p ON p.id=rp.permission_id WHERE rp.role_id=?',[$roleRow['id']]);
     $base=array_fill_keys(array_column($baseRows,'code'),true);
     $permissionIds=[];foreach(DB::all('SELECT id,code FROM permissions') as $p)$permissionIds[$p['code']]=$p['id'];
     foreach($allCodes as $code){
      if(!Accounts::canUsePermission($role,$code))continue;
      $wanted=in_array($code,$requested,true);$fromRole=isset($base[$code]);
      if($wanted===$fromRole)continue;
      DB::run('INSERT INTO user_permission_overrides(user_id,permission_id,effect,updated_by) VALUES (?,?,?,?)',[$id,$permissionIds[$code],$wanted?'grant':'revoke',Auth::user()['id']]);
     }
    }
    $after=$role==='admin'?$allCodes:$requested;
    Audit::write('user.permissions.update',json_encode(['user'=>$user['login'],'role_from'=>$user['role'],'role_to'=>$role,'before'=>$before,'after'=>$after],JSON_UNESCAPED_UNICODE));
   });
   flash('success','Đã cập nhật vai trò, phạm vi và quyền của tài khoản.');redirect('quan-tri/nguoi-dung');
  }catch(\Throwable $ex){
   try{Audit::write('security.permissions.update_failed',"Tài khoản #$id; ".get_class($ex));}catch(\Throwable $auditError){error_log('[IS4 audit] '.$auditError->getMessage());}
   flash('danger',$ex instanceof \RuntimeException?$ex->getMessage():'Không thể cập nhật phân quyền. Vui lòng thử lại sau.');redirect($redirect);
  }
 }
 public function create():void{
  Auth::require('users.manage');$login=input('login');$name=input('name');$pw=(string)($_POST['password']??'');$role=input('role');
  if(!$login||!$name||strlen($pw)<12||strlen($pw)>72){flash('danger','Cần nhập tên, đăng nhập và mật khẩu dài 12–72 byte.');redirect('quan-tri/nguoi-dung');}
  $r=DB::one('SELECT id FROM roles WHERE code=?',[$role]);if(!$r){flash('danger','Vai trò không hợp lệ');redirect('quan-tri/nguoi-dung');}
  $sid=(int)($_POST['school_id']??0);$exam=(int)($_POST['exam_id']??0);$candidateId=(int)($_POST['candidate_id']??0);
  if(in_array($role,['school_thcs','school_thpt'],true)){
    $school=DB::one('SELECT type FROM schools WHERE id=?',[$sid]);
    if(!$school||$school['type']!==($role==='school_thcs'?'THCS':'THPT')){flash('danger','Phải gán đúng trường thuộc loại tài khoản.');redirect('quan-tri/nguoi-dung');}
  }else $sid=0;
  if($role==='examiner'&&!DB::one('SELECT id FROM exam_cycles WHERE id=?',[$exam])){flash('danger','Chọn kỳ thi được giao cho khảo thí.');redirect('quan-tri/nguoi-dung');}
  if($role!=='examiner')$exam=0;
  if($role==='candidate'&&!DB::one('SELECT id FROM candidates WHERE id=? AND user_id IS NULL',[$candidateId])){flash('danger','Phải chọn hồ sơ thí sinh chưa có tài khoản.');redirect('quan-tri/nguoi-dung');}
  try {
   DB::tx(function() use($login,$pw,$name,$r,$sid,$exam,$role,$candidateId) {
     DB::run('INSERT INTO users(login,password_hash,full_name,role_id,school_id,exam_id) VALUES (?,?,?,?,?,?)',[$login,password_hash($pw,PASSWORD_DEFAULT),$name,$r['id'],$sid?:null,$exam?:null]);
     if($role==='candidate') { $uid=(int)DB::get()->lastInsertId();if(DB::run('UPDATE candidates SET user_id=? WHERE id=? AND user_id IS NULL',[$uid,$candidateId])!==1)throw new \RuntimeException('Hồ sơ đã có tài khoản.'); }
     Audit::write('user.create',$login);
   });flash('success','Đã tạo tài khoản '.$login);
  }catch(\Throwable $ex){flash('danger','Không thể tạo tài khoản, có thể trùng tên đăng nhập.');}
  redirect('quan-tri/nguoi-dung');
 }
 public function change():void{
  Auth::require('users.manage');$id=(int)($_POST['user_id']??0);$action=input('action');$u=DB::one('SELECT u.*,r.code role FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=?',[$id]);
  if(!$u)redirect('quan-tri/nguoi-dung');
  if($u['role']==='admin' && $action==='disable' && (int)DB::one("SELECT COUNT(*) n FROM users u JOIN roles r ON r.id=u.role_id WHERE r.code='admin' AND u.enabled=1")['n']<=1){flash('danger','Không thể khóa quản trị viên cuối cùng.');redirect('quan-tri/nguoi-dung');}
  if($action==='disable'||$action==='enable'){
    DB::run('UPDATE users SET enabled=? WHERE id=?',[$action==='enable'?1:0,$id]);
    Audit::write('user.'.$action,$u['login']);flash('success','Đã đổi trạng thái tài khoản.');
  }redirect('quan-tri/nguoi-dung');
 }
 public function grant():void{
  Auth::require('roles.manage');$role=input('role');$perm=input('permission');$action=input('action');
  $rid=DB::one('SELECT id FROM roles WHERE code=?',[$role]);$pid=DB::one('SELECT id FROM permissions WHERE code=?',[$perm]);
  if(!in_array($action,['grant','revoke'],true)){flash('danger','Thao tác quyền không hợp lệ.');redirect('quan-tri/nguoi-dung');}
  if(!$rid||!$pid||$role==='admin'){flash('danger','Không thể sửa quyền vai trò này.');redirect('quan-tri/nguoi-dung');}
  if(!Accounts::canUsePermission($role,$perm)){flash('danger','Không thể cấp quyền không tương thích vai trò.');redirect('quan-tri/nguoi-dung');}
  try{DB::tx(function()use($action,$rid,$pid,$role,$perm){
   if($action==='grant')DB::run('INSERT IGNORE INTO role_permissions(role_id,permission_id) VALUES (?,?)',[$rid['id'],$pid['id']]);
   else DB::run('DELETE FROM role_permissions WHERE role_id=? AND permission_id=?',[$rid['id'],$pid['id']]);
   Audit::write('role.'.$action,"$role / $perm");
  });flash('success','Đã cập nhật quyền của vai trò.');}
  catch(\Throwable $ex){try{Audit::write('security.role_permission.update_failed',"$role / $perm");}catch(\Throwable $ignored){}flash('danger','Không thể cập nhật phân quyền. Vui lòng thử lại sau.');}
  redirect('quan-tri/nguoi-dung');
 }
 public function audit():void{Auth::require('audit.view');$rows=DB::all('SELECT l.*,u.login FROM audit_logs l LEFT JOIN users u ON u.id=l.actor_id ORDER BY l.id DESC LIMIT 150');View::render('admin/audit',['rows'=>$rows]);}
}

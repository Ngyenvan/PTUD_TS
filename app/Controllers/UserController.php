<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth;use App\Core\DB;use App\Core\Audit;use App\Core\View;use App\Models\Accounts;use App\Models\Directory;
final class UserController {
 public function index():void{Auth::require('users.manage');View::render('admin/users',['users'=>Accounts::users(),'roles'=>Accounts::roles(),'schools'=>Directory::schools(),'exams'=>Directory::exams(),'matrix'=>Accounts::matrix(),'unlinked'=>DB::all('SELECT id,code,full_name FROM candidates WHERE user_id IS NULL ORDER BY id')]);}
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
  $allowed=['admissions'=>['admissions.manage','cutoffs.manage','exams.manage','schools.manage','results.publish'], 'examiner'=>['blind.manage','blind.mapping','scores.manage'], 'school_thcs'=>['school.profiles'],'school_thpt'=>['school.results'],'candidate'=>['profile.view','preferences.manage']];
  if(!in_array($perm,$allowed[$role]??[],true)){flash('danger','Không thể cấp quyền không tương thích vai trò.');redirect('quan-tri/nguoi-dung');}
  if($action==='grant') DB::run('INSERT IGNORE INTO role_permissions(role_id,permission_id) VALUES (?,?)',[$rid['id'],$pid['id']]);
  if($action==='revoke')DB::run('DELETE FROM role_permissions WHERE role_id=? AND permission_id=?',[$rid['id'],$pid['id']]);
  Audit::write('role.'.$action,"$role / $perm");flash('success','Đã cập nhật quyền.');redirect('quan-tri/nguoi-dung');
 }
 public function audit():void{Auth::require('audit.view');$rows=DB::all('SELECT l.*,u.login FROM audit_logs l LEFT JOIN users u ON u.id=l.actor_id ORDER BY l.id DESC LIMIT 150');View::render('admin/audit',['rows'=>$rows]);}
}

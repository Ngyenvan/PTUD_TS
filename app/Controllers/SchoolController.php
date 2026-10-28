<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth;use App\Core\DB;use App\Core\View;use App\Core\Audit;
final class SchoolController {
 public function list():void{
  Auth::require('school.profiles');$user=Auth::user();if($user['role']!=='school_thcs'||!$user['school_id']){http_response_code(403);exit('403: Chưa được phân công trường THCS.');}
  $rows=DB::all('SELECT c.* FROM candidates c WHERE c.thcs_id=? ORDER BY c.id',[$user['school_id']]);View::render('admin/school_profiles',['rows'=>$rows]);
 }
 public function update():void{
  Auth::require('school.profiles');$user=Auth::user();if($user['role']!=='school_thcs'||!$user['school_id']){http_response_code(403);exit('Chưa được phân công.');}
  $cid=(int)input('candidate_id');$state=input('status');
  if(!in_array($state,['pending','valid','needs_fix'],true)){flash('danger','Trạng thái không hợp lệ.');redirect('quan-tri/ho-so');}
  if(!DB::one('SELECT id FROM candidates WHERE id=? AND thcs_id=?',[$cid,$user['school_id']])){http_response_code(403);exit('Bạn không được thay đổi hồ sơ ngoài trường.');}
  if(DB::one('SELECT id FROM admissions WHERE candidate_id=? LIMIT 1',[$cid])){flash('danger','Hồ sơ đã có kết quả lưu, không được sửa.');redirect('quan-tri/ho-so');}
  DB::run('UPDATE candidates SET profile_status=? WHERE id=? AND thcs_id=?',[$state,$cid,$user['school_id']]);Audit::write('school.profile.review',"Hồ sơ $cid: $state");flash('success','Đã cập nhật trạng thái hồ sơ thuộc trường.');redirect('quan-tri/ho-so');
 }
 public function mine():void{
  Auth::require('profile.view');$user=Auth::user();if($user['role']!=='candidate'){http_response_code(403);exit('403');}
  $row=DB::one('SELECT c.*,s.name thcs_name FROM candidates c JOIN schools s ON s.id=c.thcs_id WHERE c.user_id=?',[$user['id']]);
  View::render('admin/my_profile',['row'=>$row]);
 }
}
